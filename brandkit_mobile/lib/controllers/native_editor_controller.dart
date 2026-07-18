import '../utils/safe_double.dart';
import 'package:get/get.dart';

import 'package:flutter/material.dart';

import 'dart:convert';

import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';

import 'dart:ui' as ui;
import 'dart:math';
import '../config/app_config.dart';

import '../services/api_service.dart';
import '../utils/template_json_cache.dart';

import '../controllers/home_controller.dart';


class NativeEditorController extends GetxController {
  // The complete living JSON configuration of the template
  final RxMap<String, dynamic> templateConfig = <String, dynamic>{}.obs;

  // Frame API Integration
  final RxList<dynamic> frames = <dynamic>[].obs;
  final RxBool isLoadingFrames = false.obs;

  // The ID or name of the currently selected layer
  final RxString selectedLayerId = ''.obs;
  
  // The currently active contextual tool (e.g. 'Edit', 'Font', 'Nudge')
  final RxString activeTool = ''.obs;
  final RxInt layerUpdateTrigger = 0.obs; // Forces Obx rebuild on layer property changes
  
  // Flag used to differentiate between canvas background taps and layer taps

  @override
  void onInit() {
    super.onInit();
    // Default config if none is provided
    templateConfig.value = {
      'width': 1080,
      'height': 1080,
      'layers': []
    };
    _pushHistory();
    fetchFramesList();
  }

  Future<void> fetchFramesList() async {
    try {
      isLoadingFrames.value = true;
      String bcId = '';
      if (Get.isRegistered<HomeController>()) {
        bcId = Get.find<HomeController>().businessCategoryId.value;
      }
      final prefs = await SharedPreferences.getInstance();
      final userId = prefs.getString('userId') ?? '';
      final response = await ApiService.get('/get-all-frames?business_category_id=$bcId&userId=$userId');
      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data is Map && data['success'] == true) {
          frames.value = data['data'] ?? [];
        } else if (data is List) {
          frames.value = data;
        } else if (data is Map && data['data'] != null) {
          frames.value = data['data'];
        }
      }
    } catch (e) {
      debugPrint('Error fetching frames: $e');
    } finally {
      isLoadingFrames.value = false;
    }
  }

  Future<Map<String, dynamic>?> fetchTemplateJson(String zipName) async {
    try {
      final cachedTs = await TemplateJsonCache.getTimestamp(zipName);
      String url = '/api/template/$zipName';
      if (cachedTs != null) {
        url += '?last_updated=$cachedTs';
      }

      final response = await ApiService.get(url);

      if (response.statusCode == 304) {
        final cached = await TemplateJsonCache.getCached(zipName);
        if (cached != null) {
          return jsonDecode(cached);
        }
      }

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['success'] == true && data['json'] != null) {
          await TemplateJsonCache.save(zipName, jsonEncode(data['json']), data['updated_at']);
          return data['json'];
        }
      }
    } catch (e) {
      debugPrint('Error fetchTemplateJson: $e');
    }
    return null;
  }

  List<dynamic> get filteredFrames {
    if (!Get.isRegistered<HomeController>()) return frames;
    final hc = Get.find<HomeController>();
    
    int activeEmails = (hc.businessEmail.value.isNotEmpty ? 1 : 0) + hc.extraEmails.length;
    int activePhones = (hc.businessPhone.value.isNotEmpty ? 1 : 0) + hc.extraPhones.length;
    int activeWebsites = (hc.businessWebsite.value.isNotEmpty ? 1 : 0) + hc.extraWebsites.length;
    int activeAddresses = (hc.businessAddress.value.isNotEmpty ? 1 : 0) + hc.extraAddresses.length;
    
    final hf = hc.hiddenFrameFields;
    if (hf['emails'] != null) activeEmails -= (hf['emails'] as List).length;
    if (hf['mobile_numbers'] != null) activePhones -= (hf['mobile_numbers'] as List).length;
    if (hf['websites'] != null) activeWebsites -= (hf['websites'] as List).length;
    if (hf['addresses'] != null) activeAddresses -= (hf['addresses'] as List).length;
    
    activeEmails = activeEmails.clamp(0, 99);
    activePhones = activePhones.clamp(0, 99);
    activeWebsites = activeWebsites.clamp(0, 99);
    activeAddresses = activeAddresses.clamp(0, 99);

    return frames.where((frame) {
      int reqEmail = int.tryParse(frame['req_email']?.toString() ?? '0') ?? 0;
      int reqPhone = int.tryParse(frame['req_phone']?.toString() ?? '0') ?? 0;
      int reqWeb = int.tryParse(frame['req_website']?.toString() ?? '0') ?? 0;
      int reqAddress = int.tryParse(frame['req_address']?.toString() ?? '0') ?? 0;
      
      if (reqEmail != activeEmails) return false;
      if (reqPhone != activePhones) return false;
      if (reqWeb != activeWebsites) return false;
      if (reqAddress != activeAddresses) return false;
      
      return true;
    }).toList();
  }

  // History stack for Undo/Redo
  var historyStack = <String>[].obs;
  var historyIndex = (-1).obs;

  // Configuration for the template
  String templateBaseUrl = '';
  String uploadsBaseUrl = '';
  String? baseImgUrl;

  /// Initializes the editor with the starting JSON configuration
  void initConfig(Map<String, dynamic> initialConfig, String tplBaseUrl, String upBaseUrl, String? baseImg, String editorType) {
    templateConfig.assignAll(jsonDecode(jsonEncode(initialConfig))); // deep copy
    templateBaseUrl = tplBaseUrl;
    uploadsBaseUrl = upBaseUrl;
    baseImgUrl = baseImg;
    templateConfig['type'] = editorType;

    // Default render_version to 1 for legacy frames without versioning
    templateConfig['render_version'] ??= 1;

    if (editorType == 'business_custom_frame') {
      _injectDynamicBusinessFrame();
    }

    if (templateConfig['layers'] != null) {
      String base = ApiService.baseUrl;
      Uri baseUri = Uri.parse(base);
      List<String> segments = baseUri.pathSegments.toList();
      if (segments.isNotEmpty) {
        segments.removeLast(); // Remove API segment (e.g., '123456')
      }
      base = baseUri.replace(pathSegments: segments).toString();
      if (!base.endsWith('/')) {
        base += '/';
      }

      // Use templateBaseUrl (derived from preview image) as primary frame base,
      // falling back to API base only if templateBaseUrl was not provided.
      // This ensures ../skins/ paths resolve to uploads/template/FrameName/skins/
      String frameBaseUrl = templateBaseUrl.isNotEmpty ? templateBaseUrl : base;
      if (!frameBaseUrl.endsWith('/')) {
        frameBaseUrl += '/';
      }
      final String fullUrl = (initialConfig['full_url'] ?? '').toString();
      if (fullUrl.isNotEmpty) {
        int skinsIndex = fullUrl.indexOf('/skins/');
        if (skinsIndex != -1) {
          frameBaseUrl = fullUrl.substring(0, skinsIndex) + '/';
        }
      }

      // Self-heal frameBaseUrl if it does not contain /uploads/template/
      String zipName = (initialConfig['zip_name'] ?? initialConfig['path'] ?? templateConfig['zip_name'] ?? templateConfig['path'] ?? '').toString().replaceAll('/', '').trim();
      if (zipName.isEmpty && templateConfig['layers'] is List) {
        final reg = RegExp(r'(?:\.\./)?skins/([^/]+)/');
        for (var layer in (templateConfig['layers'] as List)) {
          if (layer is Map) {
            for (var urlField in ['src', '_fallback_src']) {
              if (layer[urlField] != null) {
                final match = reg.firstMatch(layer[urlField].toString());
                if (match != null && match.group(1) != null) {
                  zipName = match.group(1)!.trim();
                  break;
                }
              }
            }
          }
          if (zipName.isNotEmpty) break;
        }
      }

      if (zipName.isNotEmpty && !frameBaseUrl.contains('/uploads/template/$zipName')) {
        frameBaseUrl = '${base}uploads/template/$zipName/';
        this.templateBaseUrl = frameBaseUrl;
      } else if (this.templateBaseUrl.isEmpty || !this.templateBaseUrl.contains('/uploads/template/')) {
        this.templateBaseUrl = frameBaseUrl;
      }

      for (var layer in (templateConfig['layers'] as List)) {
        if (layer is Map<String, dynamic>) {
          for (var urlField in ['src', '_fallback_src']) {
            if (layer[urlField] != null && layer[urlField].toString().isNotEmpty) {
              String srcStr = layer[urlField].toString();
              if (srcStr.startsWith('data:') || srcStr.startsWith('http')) {
                if (srcStr.contains('/skins/skins/')) {
                  srcStr = srcStr.replaceAll('/skins/skins/', '/skins/');
                  layer[urlField] = srcStr;
                }
                if (zipName.isNotEmpty && srcStr.contains('/uploads/skins/$zipName/')) {
                  srcStr = srcStr.replaceAll('/uploads/skins/$zipName/', '/uploads/template/$zipName/skins/$zipName/');
                  layer[urlField] = srcStr;
                }
              } else if (srcStr.startsWith('/')) {
                layer[urlField] = '$base${srcStr.substring(1)}';
              } else if (srcStr.startsWith('../')) {
                layer[urlField] = '$frameBaseUrl${srcStr.replaceFirst('../', '')}';
              } else if (srcStr.startsWith('skins/')) {
                layer[urlField] = '$frameBaseUrl$srcStr';
              } else if (srcStr.startsWith('uploads/')) {
                layer[urlField] = '$base$srcStr';
              } else {
                if (zipName.isNotEmpty) {
                  layer[urlField] = '${frameBaseUrl}skins/$zipName/$srcStr';
                } else {
                  layer[urlField] = '${frameBaseUrl}skins/$srcStr';
                }
              }
              if (layer[urlField] != null && !layer[urlField].toString().startsWith('data:')) {
                layer[urlField] = layer[urlField].toString().replaceAll(' ', '-').replaceAll('%20', '-');
              }
            }
          }
        }
      }

      _deduplicateLayerNames(templateConfig['layers']);
    }

    _pushHistory();
    
    // Run brightness detection on initial load too (not just on frame switch)
    _applyInitialBrightness();

    // Apply business details to layers immediately if they are already loaded
    reapplyBusinessProfile();
  }

  void _deduplicateLayerNames(List<dynamic> layers) {
    Map<String, int> nameCounts = {};
    for (var layer in layers) {
      if (layer is Map<String, dynamic>) {
        String baseName = (layer['name'] ?? layer['id'] ?? 'layer').toString();
        if (nameCounts.containsKey(baseName)) {
          int count = nameCounts[baseName]! + 1;
          nameCounts[baseName] = count;
          layer['name'] = '${baseName}_$count';
          layer['id'] = layer['name'];
        } else {
          nameCounts[baseName] = 1;
          if (layer['name'] == null) {
            layer['name'] = baseName;
          }
          layer['id'] = layer['name'];
        }
      }
    }
  }

  void _injectDynamicBusinessFrame() {
    if (!Get.isRegistered<HomeController>()) return;
    final homeCtrl = Get.find<HomeController>();
    
    List<dynamic> layers = templateConfig['layers'] ?? [];
    templateConfig['layers'] = layers;

    // Check if frame layers already exist (so we don't duplicate)
    bool hasFrame = layers.any((l) => l['_businessKey'] != null || l['_isFrameLayer'] == true);
    if (hasFrame) return;

    final double cW = safeDouble((templateConfig['info']?['width'] ?? templateConfig['width'] ?? 1080) as num);
    final double cH = safeDouble((templateConfig['info']?['height'] ?? templateConfig['height'] ?? 1080) as num);

    // Default business layers (matching web editor addBusinessElements)
    
    // Logo
    if (homeCtrl.businessLogo.value.isNotEmpty) {
      layers.add({
        'name': '_b_logo',
        'type': 'image',
        'src': homeCtrl.businessLogo.value,
        'x': cW * 0.08, 'y': cH * 0.08,
        'w': cW * 0.15, 'h': cW * 0.15,
        'z_index': 99,
        '_isFrameLayer': true,
        '_businessKey': 'logo',
      });
    }

    // Name
    if (homeCtrl.businessName.value.isNotEmpty) {
      layers.add({
        'name': '_b_name',
        'type': 'text',
        'text': homeCtrl.businessName.value,
        'x': cW * 0.08, 'y': cH * 0.82,
        'w': cW * 0.6, 'h': 40,
        'fontSize': 36,
        'fontFamily': 'Inter',
        'weight': 'bold',
        'color': '#000000',
        'z_index': 99,
        '_isFrameLayer': true,
        '_businessKey': 'name',
        'opacity': 0.0, // hidden by default like web editor
      });
    }

    // Phone
    if (homeCtrl.businessPhone.value.isNotEmpty) {
      layers.add({
        'name': '_b_phone',
        'type': 'text',
        'text': homeCtrl.businessPhone.value.replaceAll(' ', '\u00A0'),
        'x': cW * 0.12, 'y': cH * 0.88,
        'w': cW * 0.4, 'h': 26,
        'fontSize': 22,
        'fontFamily': 'Inter',
        'weight': 'bold',
        'color': '#000000',
        'z_index': 99,
        '_isFrameLayer': true,
        '_businessKey': 'phone',
        'opacity': 0.0,
      });
    }

    // Email
    if (homeCtrl.businessEmail.value.isNotEmpty) {
      layers.add({
        'name': '_b_email',
        'type': 'text',
        'text': homeCtrl.businessEmail.value,
        'x': cW * 0.08, 'y': cH * 0.92,
        'w': cW * 0.5, 'h': 26,
        'fontSize': 18,
        'fontFamily': 'Inter',
        'weight': 'normal',
        'color': '#000000',
        'z_index': 99,
        '_isFrameLayer': true,
        '_businessKey': 'email',
        'opacity': 0.0,
      });
    }

    // Website
    if (homeCtrl.businessWebsite.value.isNotEmpty) {
      layers.add({
        'name': '_b_website',
        'type': 'text',
        'text': homeCtrl.businessWebsite.value,
        'x': cW * 0.5, 'y': cH * 0.88,
        'w': cW * 0.45, 'h': 26,
        'fontSize': 18,
        'fontFamily': 'Inter',
        'weight': 'normal',
        'color': '#000000',
        'z_index': 99,
        '_isFrameLayer': true,
        '_businessKey': 'website',
        'opacity': 0.0,
      });
    }

    // Address
    if (homeCtrl.businessAddress.value.isNotEmpty) {
      layers.add({
        'name': '_b_address',
        'type': 'text',
        'text': homeCtrl.businessAddress.value,
        'x': cW * 0.5, 'y': cH * 0.92,
        'w': cW * 0.45, 'h': 26,
        'fontSize': 16,
        'fontFamily': 'Inter',
        'weight': 'normal',
        'color': '#000000',
        'z_index': 99,
        '_isFrameLayer': true,
        '_businessKey': 'address',
        'opacity': 0.0,
      });
    }
  }

  /// Re-applies the loaded business profile details to the template layers.
  /// Resolves the race condition where layers initialize beforeHomeController fetches business details.
  void reapplyBusinessProfile() {
    if (!Get.isRegistered<HomeController>()) return;
    final homeCtrl = Get.find<HomeController>();

    final layers = templateConfig['layers'] as List<dynamic>? ?? [];
    if (layers.isEmpty) return;

    Map<String, int> bizKeyCounter = {
      'phone': 0, 'email': 0, 'website': 0, 'address': 0,
    };

    bool updated = false;
    for (var layer in layers) {
      if (layer is Map<String, dynamic> && layer['_businessKey'] != null) {
        final String key = layer['_businessKey'].toString();
        if (layer['type'] == 'text') {
          if (key == 'name') {
            if (layer['text'] != homeCtrl.businessName.value) {
              layer['text'] = homeCtrl.businessName.value;
              updated = true;
            }
          } else if (key == 'phone') {
            int idx = bizKeyCounter['phone']!;
            String targetVal = '';
            if (idx == 0) {
              targetVal = homeCtrl.businessPhone.value.replaceAll(' ', '\u00A0');
            } else if (idx - 1 < homeCtrl.extraPhones.length) {
              targetVal = homeCtrl.extraPhones[idx - 1].replaceAll(' ', '\u00A0');
            }
            bizKeyCounter['phone'] = idx + 1;
            if (targetVal.isNotEmpty && layer['text'] != targetVal) {
              layer['text'] = targetVal;
              updated = true;
            }
          } else if (key == 'email') {
            int idx = bizKeyCounter['email']!;
            String targetVal = '';
            if (idx == 0) {
              targetVal = homeCtrl.businessEmail.value;
            } else if (idx - 1 < homeCtrl.extraEmails.length) {
              targetVal = homeCtrl.extraEmails[idx - 1];
            }
            bizKeyCounter['email'] = idx + 1;
            if (targetVal.isNotEmpty && layer['text'] != targetVal) {
              layer['text'] = targetVal;
              updated = true;
            }
          } else if (key == 'website') {
            int idx = bizKeyCounter['website']!;
            String targetVal = '';
            if (idx == 0) {
              targetVal = homeCtrl.businessWebsite.value;
            } else if (idx - 1 < homeCtrl.extraWebsites.length) {
              targetVal = homeCtrl.extraWebsites[idx - 1];
            }
            bizKeyCounter['website'] = idx + 1;
            if (targetVal.isNotEmpty && layer['text'] != targetVal) {
              layer['text'] = targetVal;
              updated = true;
            }
          } else if (key == 'address') {
            int idx = bizKeyCounter['address']!;
            String targetVal = '';
            if (idx == 0) {
              targetVal = homeCtrl.businessAddress.value;
            } else if (idx - 1 < homeCtrl.extraAddresses.length) {
              targetVal = homeCtrl.extraAddresses[idx - 1];
            }
            bizKeyCounter['address'] = idx + 1;
            if (targetVal.isNotEmpty && layer['text'] != targetVal) {
              layer['text'] = targetVal;
              updated = true;
            }
          }
        } else if (layer['type'] == 'image' || layer['type'] == 'icon') {
          if (key == 'logo' && homeCtrl.businessLogo.value.isNotEmpty) {
            String logoPath = homeCtrl.businessLogo.value;
            if (!logoPath.startsWith('http')) {
              logoPath = ApiService.baseUrl.replaceAll('/api', '') + '/' + (logoPath.startsWith('/') ? logoPath.substring(1) : logoPath);
            }
            if (layer['src'] != logoPath) {
              layer['src'] = logoPath;
              updated = true;
            }
          }
        }
      }
    }

    if (updated) {
      debugPrint('[NATIVE_EDITOR] Business profile reapplied successfully.');
      layerUpdateTrigger.value++;
      templateConfig.refresh();
    }
  }

  /// Auto-detect contact/social icons by proximity to contact text layers
  void _autoDetectContactIcons(List<dynamic> layers) {
    try {
      for (var imgLayer in layers) {
        if (imgLayer is Map<String, dynamic>) {
          final String type = (imgLayer['type'] ?? '').toString();
          if (type == 'image' || type == 'icon' || type == 'shape') {
            // Skip if it already has a business key
            if (imgLayer['_businessKey'] != null) continue;

            double iw = safeDouble((imgLayer['w'] ?? imgLayer['width'] ?? 0) as num);
            double ih = safeDouble((imgLayer['h'] ?? imgLayer['height'] ?? 0) as num);
            if (iw <= 0 || ih <= 0 || iw > 80 || ih > 80) continue; // Must be a small icon asset

            double ix = safeDouble((imgLayer['x'] ?? 0) as num);
            double iy = safeDouble((imgLayer['y'] ?? 0) as num);
            double icY = iy + ih / 2;

            Map<String, dynamic>? bestTextLayer;
            double bestDist = 999999;

            for (var txtLayer in layers) {
              if (txtLayer is Map<String, dynamic> && txtLayer['type'] == 'text') {
                String? txtBizKey = txtLayer['_businessKey']?.toString();
                if (txtBizKey == null || txtBizKey.isEmpty) {
                  final String tname = (txtLayer['name'] ?? txtLayer['id'] ?? '').toString().toLowerCase();
                  if (tname.contains('phone') || tname.contains('mobile') || tname.contains('whatsapp') || tname.contains('number') || tname.contains('tel')) txtBizKey = 'phone';
                  else if (tname.contains('email') || tname.contains('mail')) txtBizKey = 'email';
                  else if (tname.contains('website') || tname.contains('web') || tname.contains('url')) txtBizKey = 'website';
                  else if (tname.contains('address') || tname.contains('location')) txtBizKey = 'address';
                }
                if (txtBizKey == null || txtBizKey.isEmpty) continue;

                double tx = safeDouble((txtLayer['x'] ?? 0) as num);
                double ty = safeDouble((txtLayer['y'] ?? 0) as num);
                double tw = safeDouble((txtLayer['w'] ?? txtLayer['width'] ?? 0) as num);
                double th = safeDouble((txtLayer['h'] ?? txtLayer['height'] ?? 0) as num);
                double tcY = ty + th / 2;

                // Check vertical alignment (diff in center Y <= 35)
                double diffY = (icY - tcY).abs();
                if (diffY > 35) continue;

                // Check horizontal proximity (gap <= 120)
                double gap = 999999;
                if (ix + iw <= tx) {
                  gap = tx - (ix + iw); // Icon is to the left
                } else if (tx + tw <= ix) {
                  gap = ix - (tx + tw); // Icon is to the right
                } else {
                  gap = 0; // Horizontally overlapping
                }

                if (gap > 120) continue;

                double dist = diffY + gap;
                if (dist < bestDist) {
                  bestDist = dist;
                  bestTextLayer = txtLayer;
                }
              }
            }

            if (bestTextLayer != null) {
              String bizKey = bestTextLayer['_businessKey']?.toString() ?? '';
              if (bizKey.isEmpty) {
                final String tname = (bestTextLayer['name'] ?? bestTextLayer['id'] ?? '').toString().toLowerCase();
                if (tname.contains('phone') || tname.contains('mobile') || tname.contains('whatsapp') || tname.contains('number') || tname.contains('tel')) bizKey = 'phone';
                else if (tname.contains('email') || tname.contains('mail')) bizKey = 'email';
                else if (tname.contains('website') || tname.contains('web') || tname.contains('url')) bizKey = 'website';
                else if (tname.contains('address') || tname.contains('location')) bizKey = 'address';
              }
              if (bizKey.isNotEmpty) {
                imgLayer['_businessKey'] = bizKey;
                debugPrint('[ICON_AUTO_DETECT] Coupled icon "${imgLayer['name']}" to text "${bestTextLayer['name']}" (key=$bizKey, dist=${bestDist.toInt()})');
              }
            }
          }
        }
      }
    } catch (e) {
      debugPrint('[ICON_AUTO_DETECT] Error: $e');
    }
  }

  /// Applies brightness-based color theming on initial load
  Future<void> _applyInitialBrightness() async {
    final layers = templateConfig['layers'] as List<dynamic>? ?? [];
    if (layers.isEmpty) return;
    
    _autoDetectContactIcons(layers);
    
    // Build shape layers list (same logic as loadNewFrame)
    List<Map<String, dynamic>> shapeLayers = [];
    for (var layer in layers) {
      final String layerName = (layer['name'] ?? layer['id'] ?? '').toString().toLowerCase();
      final bool isBg = layer['is_background'] == true || 
                        (layer['is_background'] == null && ['image1', 'main_image', 'bg', 'background', '_frame_bg'].contains(layerName));
      
      if (!isBg && (layer['type'] == 'image' || layer['type'] == 'rect' || layer['type'] == 'shape')) {
        bool isContactIcon = ['phone', 'email', 'website', 'address', 'social'].any((e) => layerName.contains(e));
        if (!isContactIcon || layer['type'] != 'image') {
          double rawW = safeDouble((layer['w'] ?? layer['width'] ?? 0) as num);
          double rawH = safeDouble((layer['h'] ?? layer['height'] ?? 0) as num);
          bool isShapeMarked = layer['is_shape'] == true;
          if (layer['type'] != 'image' || rawW > 200 || rawH > 200 || isShapeMarked) {
            if (rawW > 20 && rawH > 10) {
              shapeLayers.add({
                'x': safeDouble((layer['x'] ?? 0) as num),
                'y': safeDouble((layer['y'] ?? 0) as num),
                'w': rawW,
                'h': rawH,
                'fill': layer['fill'] ?? layer['tint_color'] ?? layer['color'],
                'src': layer['src'],
                'z_index': safeDouble((layer['z_index'] ?? 0) as num).toInt(),
              });
            }
          }
        }
      }
    }
    
    // Run async brightness detection - pass layers as the "newLayers" parameter
    final layerMaps = layers.whereType<Map<String, dynamic>>().toList();
    _asyncApplyBrightness({}, layerMaps, layers, shapeLayers);
  }

  /// Pushes the current state to the history stack
  void _pushHistory() {
    // If we're not at the end of the history stack, discard the future steps
    if (historyIndex.value < historyStack.length - 1) {
      historyStack.removeRange(historyIndex.value + 1, historyStack.length);
    }

    // Save deep copy as JSON string
    final snapshot = jsonEncode(templateConfig);
    historyStack.add(snapshot);

    // Limit history size to 20 to save memory
    if (historyStack.length > 20) {
      historyStack.removeAt(0);
    } else {
      historyIndex.value++;
    }
  }

  void undo() {
    if (historyIndex.value > 0) {
      historyIndex.value--;
      templateConfig.assignAll(jsonDecode(historyStack[historyIndex.value]));
    }
  }

  void redo() {
    if (historyIndex.value < historyStack.length - 1) {
      historyIndex.value++;
      templateConfig.assignAll(jsonDecode(historyStack[historyIndex.value]));
    }
  }

  void selectLayer(String layerName) {
    selectedLayerId.value = layerName;
    activeTool.value = '';
  }

  void deselectAll() {
    selectedLayerId.value = '';
    activeTool.value = '';
  }

  void updateLayerBounds(String layerName, double x, double y, double w, double h, double angle) {
    final layers = templateConfig['layers'] as List<dynamic>?;
    if (layers == null) return;

    for (var layer in layers) {
      if ((layer['name'] ?? layer['id']).toString() == layerName) {
        layer['x'] = x;
        layer['y'] = y;
        layer['w'] = w;
        layer['width'] = w;
        layer['h'] = h;
        layer['height'] = h;
        layer['angle'] = angle;
        break;
      }
    }
    
    // Force Obx rebuild in InteractiveLayer — templateConfig.refresh() alone
    // doesn't trigger deep nested Map property changes for GetX tracking.
    layerUpdateTrigger.value++;
    templateConfig.refresh();
  }

  // To be called when a drag/scale operation finishes
  void commitLayerChange() {
    _pushHistory();
  }

  void toggleVisibility(String layerName, bool isVisible) {
    final layers = templateConfig['layers'] as List<dynamic>?;
    if (layers == null) return;

    final targets = layerName.split(',').map((e) => e.trim().toLowerCase()).toList();

    for (var layer in layers) {
      final String rawName = (layer['name'] ?? layer['id']).toString().toLowerCase().trim();
      final String nameWithoutSpaces = rawName.replaceAll(RegExp(r'[\s\-_]'), '');
      
      bool matches = false;
      for (String target in targets) {
        final t = target.replaceAll(RegExp(r'[\s\-_]'), '');
        if (nameWithoutSpaces == t) {
          matches = true;
          break;
        }
      }
      
      // Also match by _businessKey (frame layers have arbitrary names but tagged _businessKey)
      if (!matches && layer['_businessKey'] != null) {
        final String bizKey = layer['_businessKey'].toString().toLowerCase().trim();
        for (String target in targets) {
          final t = target.replaceAll(RegExp(r'[\s\-_]'), '');
          if (bizKey == t || t.contains(bizKey) || bizKey.contains(t)) {
            matches = true;
            break;
          }
        }
      }
      
      if (matches) {
        layer['opacity'] = isVisible ? 1.0 : 0.0;
      }
    }
    
    layerUpdateTrigger.value++; // Force explicit UI rebuild
    templateConfig.refresh();
    _pushHistory();
  }

  bool isLayerVisible(String layerName) {
    final layers = templateConfig['layers'] as List<dynamic>?;
    if (layers == null) return false;

    final targets = layerName.split(',').map((e) => e.trim().toLowerCase()).toList();

    for (var layer in layers) {
      final String rawName = (layer['name'] ?? layer['id']).toString().toLowerCase().trim();
      final String nameWithoutSpaces = rawName.replaceAll(RegExp(r'[\s\-_]'), '');
      
      bool matches = false;
      for (String target in targets) {
        final t = target.replaceAll(RegExp(r'[\s\-_]'), '');
        if (nameWithoutSpaces == t) {
          matches = true;
          break;
        }
      }
      
      // Also match by _businessKey (frame layers have arbitrary names but tagged _businessKey)
      if (!matches && layer['_businessKey'] != null) {
        final String bizKey = layer['_businessKey'].toString().toLowerCase().trim();
        for (String target in targets) {
          final t = target.replaceAll(RegExp(r'[\s\-_]'), '');
          if (bizKey == t || t.contains(bizKey) || bizKey.contains(t)) {
            matches = true;
            debugPrint('[VISIBLE_CHECK] "$rawName" MATCHED via bizKey="$bizKey" target="$t" opacity=${layer['opacity']}');
            break;
          }
        }
      }
      
      if (matches) {
        final opacity = layer['opacity'];
        final result = (opacity ?? 1.0) is num ? (opacity ?? 1.0 as num) > 0.0 : true;
        debugPrint('[VISIBLE_CHECK] "$rawName" → opacity=$opacity result=$result (targets=$targets)');
        return result;
      }
    }
    debugPrint('[VISIBLE_CHECK] NO MATCH for targets=$targets');
    return false;
  }

  void updateLayerProperty(String layerName, String property, dynamic value) {
    final layers = templateConfig['layers'] as List<dynamic>?;
    if (layers == null) return;

    if (layerName == '_frame_bg' && property == 'src') {
      // Clean up old frame layers except _frame_bg (which we are updating/adding)
      layers.removeWhere((l) => (l['_is_frame_layer'] == true || l['_isFrameLayer'] == true) && (l['name'] ?? l['id']).toString() != '_frame_bg');
    }

    bool found = false;
    for (var layer in layers) {
      if ((layer['name'] ?? layer['id']).toString() == layerName) {
        layer[property] = value;
        found = true;
        break;
      }
    }
    
    // Inject _frame_bg if missing and we are trying to update its src (simple PNG frame swap)
    if (!found && layerName == '_frame_bg' && property == 'src') {
      final double canvasW = safeDouble(templateConfig['info']?['width'] ?? templateConfig['width'] ?? 1080);
      final double canvasH = safeDouble(templateConfig['info']?['height'] ?? templateConfig['height'] ?? 1080);
      
      int maxZIndex = 0;
      for (var l in layers) {
        int z = (l['z_index'] ?? 0) is int ? (l['z_index'] ?? 0) : ((l['z_index'] ?? 0) as num).toInt();
        if (z > maxZIndex) maxZIndex = z;
      }
      
      layers.add({
        'name': '_frame_bg',
        'id': '_frame_bg',
        'type': 'image',
        'src': value,
        'opacity': 1.0,
        'x': 0,
        'y': 0,
        'w': canvasW,
        'h': canvasH,
        'width': canvasW,
        'height': canvasH,
        '_is_frame_layer': true,
        '_isFrameLayer': true,
        'z_index': maxZIndex + 1,
      });
    }
    
    templateConfig.refresh();
    _pushHistory();
  }

  void updateLayerProperties(String layerName, Map<String, dynamic> properties) {
    final layers = templateConfig['layers'] as List<dynamic>?;
    if (layers == null) return;

    for (var layer in layers) {
      if ((layer['name'] ?? layer['id']).toString() == layerName) {
        properties.forEach((key, value) {
          layer[key] = value;
        });
        break;
      }
    }
    
    templateConfig.refresh();
    _pushHistory();
  }

  void deleteLayer(String layerName) {
    final layers = templateConfig['layers'] as List<dynamic>?;
    if (layers == null) return;

    layers.removeWhere((layer) => (layer['name'] ?? layer['id']).toString() == layerName);
    if (selectedLayerId.value == layerName) selectedLayerId.value = '';
    
    templateConfig.refresh();
    _pushHistory();
  }

  void addLayer(Map<String, dynamic> newLayer) {
    var layers = templateConfig['layers'] as List<dynamic>?;
    if (layers == null) {
      templateConfig['layers'] = [];
      layers = templateConfig['layers'];
    }

    layers!.add(newLayer);
    templateConfig.refresh();
    _pushHistory();
  }

  void moveLayer(String layerName, int newIndex) {
    final layers = templateConfig['layers'] as List<dynamic>?;
    if (layers == null) return;

    int oldIndex = layers.indexWhere((l) => l['name'] == layerName);
    if (oldIndex != -1) {
      final layer = layers.removeAt(oldIndex);
      if (newIndex > layers.length) newIndex = layers.length;
      if (newIndex < 0) newIndex = 0;
      layers.insert(newIndex, layer);
      
      templateConfig.refresh();
      _pushHistory();
    }
  }

  // --- API INTEGRATIONS ---

  Future<Map<String, dynamic>?> generateAIText(String prompt, String language, {Map<String, dynamic>? product}) async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final userId = prefs.getString('userId') ?? '';
      
      // Collect text layers
      final List<Map<String, dynamic>> canvasLayers = [];
      final layers = templateConfig['layers'] as List<dynamic>? ?? [];
      
      for (var rawLayer in layers) {
        final layer = Map<String, dynamic>.from(rawLayer);
        if (layer['type'] == 'text') {
          final String lname = (layer['name'] ?? layer['id'] ?? '').toString().toLowerCase();
          
          // Skip if hidden or contact details
          final bool isContactInfo = [
            'phone', 'email', 'website', 'address', 'call', 'mobile', 'contact', 'whatsapp', 'tel',
            'mail', 'web', 'url', 'location', 'facebook', 'instagram', 'twitter', 'youtube', 'linkedin'
          ].any((key) => lname.contains(key));
          
          if (!isContactInfo) {
            final String currentText = (layer['text'] ?? '').toString().trim();
            final bool isSkippable = ['www.', 'http', '.com', '.in', '@', '+91'].any((p) => currentText.toLowerCase().contains(p));
            final bool isAiProtected = layer['ai_protected'] == true || layer['ai_protected'] == '1' || layer['ai_protected'] == 1;
            
            if (!isSkippable && currentText.isNotEmpty && !isAiProtected) {
              int maxChars = (layer['ai_max_chars'] != null) ? int.tryParse(layer['ai_max_chars'].toString()) ?? 0 
                           : (layer['_ai_max_chars'] != null) ? int.tryParse(layer['_ai_max_chars'].toString()) ?? 0 : 0;
                           
              if (maxChars <= 0) {
                // Auto-calculate maximum capacity based on physical dimensions
                double w = double.tryParse(layer['w']?.toString() ?? '0') ?? 0;
                double h = double.tryParse(layer['h']?.toString() ?? '0') ?? 0;
                double size = double.tryParse(layer['size']?.toString() ?? '20') ?? 20;
                
                if (w > 0 && h > 0 && size > 0) {
                  // Average character width is roughly 55% of font size
                  double charWidth = size * 0.55;
                  int charsPerLine = (w / charWidth).floor();
                  
                  // Average line height is roughly 1.2x font size
                  double lineHeight = size * 1.2;
                  int lines = (h / lineHeight).floor();
                  if (lines < 1) lines = 1;
                  
                  maxChars = charsPerLine * lines;
                }
                
                // Fallback if dimensions are missing or very weird
                if (maxChars <= 0) {
                  maxChars = (currentText.length * 2).clamp(50, 1000);
                }
              }
              
              canvasLayers.add({
                'name': layer['name'] ?? layer['id'] ?? 'text_${canvasLayers.length}',
                'current_text': currentText,
                'max_chars': maxChars,
                'ai_role': layer['ai_role'] ?? layer['_ai_role'] ?? ''
              });
            }
          }
        }
      }

      if (canvasLayers.isEmpty) {
        debugPrint('No text layers found for AI generation.');
        return null;
      }

      final payload = {
        'frame_id': templateConfig['info']?['id']?.toString() ?? '',
        'manual_prompt': prompt,
        'canvas_layers': canvasLayers,
        'language': language,
      };

      // Add product data if available
      if (product != null) {
        payload['product_id'] = (product['id'] ?? '').toString();
        payload['product_name'] = (product['_display_name'] ?? product['title'] ?? product['name'] ?? '').toString();
        payload['product_description'] = (product['description'] ?? product['short_description'] ?? '').toString();
        payload['product_price'] = (product['_display_price'] ?? product['price'] ?? '').toString();
        payload['product_category'] = (product['_display_category'] ?? product['category_name'] ?? product['category'] ?? '').toString();
        payload['product_sku'] = (product['sku'] ?? '').toString();
      }

      debugPrint('[AI Generate] Payload: $payload');

      final response = await ApiService.post('/editor/ai-content/generate?userId=$userId', payload);

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['success'] == true && data['content'] != null) {
          return Map<String, dynamic>.from(data['content']);
        }
      }
      return null;
    } catch (e) {
      debugPrint('Error generating AI text: $e');
      return null;
    }
  }

  static final Map<String, bool> _brightnessCache = {};

  Future<void> loadNewFrame(Map<String, dynamic> newFrameJson) async {
    try {
      final currentLayers = templateConfig['layers'] as List<dynamic>? ?? [];
      
      dynamic rawNewLayers = newFrameJson['layers'];
      if (rawNewLayers == null && newFrameJson['config'] != null) {
        if (newFrameJson['config'] is Map) {
          rawNewLayers = newFrameJson['config']['layers'];
        } else if (newFrameJson['config'] is String) {
          try {
            rawNewLayers = jsonDecode(newFrameJson['config'])['layers'];
          } catch(e) {}
        }
      }
      if (rawNewLayers == null && newFrameJson['json'] != null) {
        if (newFrameJson['json'] is Map) {
          rawNewLayers = newFrameJson['json']['layers'];
        } else if (newFrameJson['json'] is String) {
          try {
            rawNewLayers = jsonDecode(newFrameJson['json'])['layers'];
          } catch(e) {}
        }
      }
      
      // 3. Fallback to API if not in config and we have a zip_name
      if (rawNewLayers == null && newFrameJson['zip_name'] != null) {
        final fetchedJson = await fetchTemplateJson(newFrameJson['zip_name']);
        if (fetchedJson != null) {
          newFrameJson['config'] = fetchedJson; // save it so we don't fetch again unnecessarily
          rawNewLayers = fetchedJson['layers'];
        }
      }

      if (rawNewLayers == null) rawNewLayers = [];

      final newLayers = jsonDecode(jsonEncode(rawNewLayers)) as List<dynamic>;
      debugPrint('🔴 [DIAGNOSIS] loadNewFrame newLayers count: ${newLayers.length}');
      for (var l in newLayers) {
        debugPrint('🔴 [DIAGNOSIS] newLayer: ${l['name']} type=${l['type']}');
      }
      
      _autoDetectContactIcons(newLayers);
      
      Map<String, String> userTexts = {};
      for (var l in currentLayers) {
        final name = (l['name'] ?? l['id'] ?? '').toString();
        if (l['type'] == 'text' && l['text'] != null) {
          userTexts[name] = l['text'].toString();
        }
      }

      final type = Get.parameters['type'] ?? templateConfig['type'] ?? 'business_custom_frame';

      final preservedLayers = currentLayers.where((l) {
        if (l['_is_frame_layer'] == true) return false;
        
        final name = (l['name'] ?? l['id'] ?? '').toString().toLowerCase();
        final bool isBg = l['is_background'] == true || (l['is_background'] == null && (name == 'image1' || name == 'main_image' || name == 'bg' || name.contains('background')));
        final bool isUserAdded = name.startsWith('new ') || name.startsWith('logo ') || name.startsWith('product ') || name.startsWith('sticker ') || name.startsWith('text ');
        
        if (type == 'business_custom_frame' || type == 'custom') {
            return true; // Preserve all native layers of the custom post
        }
        
        return isBg || isUserAdded;
      }).toList();
      
      dynamic configJson = newFrameJson['config'] ?? newFrameJson['json'];
      if (configJson is String) {
        try { configJson = jsonDecode(configJson); } catch(e) {}
      }

      dynamic templateInfo = templateConfig['info'];
      if (templateInfo is String) {
        try { templateInfo = jsonDecode(templateInfo); } catch(e) {}
      }
      double canvasW = safeDouble(templateInfo?['width'] ?? templateConfig['width'] ?? 1080);
      double canvasH = safeDouble(templateInfo?['height'] ?? templateConfig['height'] ?? 1080);
      
      dynamic frameInfo = newFrameJson['info'];
      if (frameInfo is String) {
        try { frameInfo = jsonDecode(frameInfo); } catch(e) {}
      }
      dynamic configInfo = configJson?['info'];
      if (configInfo is String) {
        try { configInfo = jsonDecode(configInfo); } catch(e) {}
      }

      double frameW = safeDouble(newFrameJson['width'] ?? frameInfo?['width'] ?? configInfo?['width'] ?? configJson?['width'] ?? 0);
      double frameH = safeDouble(newFrameJson['height'] ?? frameInfo?['height'] ?? configInfo?['height'] ?? configJson?['height'] ?? 0);
      
      if (frameW <= 0 || frameH <= 0) {
        for (var layer in newLayers) {
          double r = safeDouble((layer['x'] ?? 0) as num) + safeDouble((layer['width'] ?? layer['w'] ?? 0) as num);
          double b = safeDouble((layer['y'] ?? 0) as num) + safeDouble((layer['height'] ?? layer['h'] ?? 0) as num);
          if (r > frameW) frameW = r;
          if (b > frameH) frameH = b;
        }
      }
      if (frameW <= 0) frameW = 1080;
      if (frameH <= 0) frameH = 1080;

      bool hasBg = newLayers.any((l) {
        String n = (l['name'] ?? l['id'] ?? '').toString().toLowerCase();
        return n == 'bg' || n == '_frame_bg' || n.contains('background') || l['is_background'] == true;
      });

      // FIX: If the frame JSON lacks a background layer, but provides a full_url, auto-create the frame background
      // Only do this for legacy frames (V1-V3) since V4+ frames are fully constructed from component layers.
      final int renderVersion = (newFrameJson['render_version'] is int) 
          ? newFrameJson['render_version'] 
          : safeDouble(newFrameJson['render_version'] ?? 1).toInt();

      if (renderVersion < 4 && !hasBg && newFrameJson['full_url'] != null && newFrameJson['full_url'].toString().isNotEmpty) {
        newLayers.insert(0, {
          'name': '_frame_bg',
          'id': '_frame_bg',
          'type': 'image',
          'src': newFrameJson['full_url'],
          'opacity': 1.0,
          'x': 0,
          'y': 0,
          'w': frameW,
          'h': frameH,
          'width': frameW,
          'height': frameH,
          'z_index': 0,
        });
      }

      double scaleX = canvasW / frameW;
      double scaleY = canvasH / frameH;

      List<Map<String, dynamic>> shapeLayers = [];
      double docPPI = 72.0;
      try {
        if (newFrameJson['info'] != null) {
          if (newFrameJson['info'] is String) {
            final parsedInfo = jsonDecode(newFrameJson['info']);
            if (parsedInfo['ppi'] != null) docPPI = safeDouble(parsedInfo['ppi'] as num);
          } else if (newFrameJson['info']['ppi'] != null) {
            docPPI = safeDouble(newFrameJson['info']['ppi'] as num);
          }
        }
      } catch (e) {
        debugPrint('[PPI] Error: $e');
      }
      double ppiScale = docPPI / 72.0;

      // Counters for multi-value business fields
      Map<String, int> bizKeyCounter = {
        'phone': 0, 'email': 0, 'website': 0, 'address': 0,
      };

      for (var newLayer in newLayers) {
        double rawW = safeDouble((newLayer['w'] ?? newLayer['width'] ?? 0) as num);
        double rawH = safeDouble((newLayer['h'] ?? newLayer['height'] ?? 0) as num);
        String name = (newLayer['name'] ?? newLayer['id'] ?? '').toString();
        String layerName = name.toLowerCase();

        newLayer['_is_frame_layer'] = true;

        for (String urlField in ['src', '_fallback_src']) {
          if (newLayer[urlField] != null && newLayer[urlField].toString().isNotEmpty) {
            String srcStr = newLayer[urlField].toString();
            if (!srcStr.startsWith('http') && !srcStr.startsWith('data:')) {
              String base = ApiService.baseUrl;
              Uri baseUri = Uri.parse(base);
              List<String> segments = baseUri.pathSegments.toList();
              if (segments.isNotEmpty) {
                segments.removeLast(); // Remove API segment (e.g., '123456')
              }
              base = baseUri.replace(pathSegments: segments).toString();
              if (!base.endsWith('/')) {
                base += '/';
              }
              
              String zipName = (newFrameJson['zip_name'] ?? newFrameJson['path'] ?? '').toString().replaceAll('/', '').trim();
              String frameBaseUrl = base;
              if (zipName.isNotEmpty) {
                frameBaseUrl = '${base}uploads/template/$zipName/';
              } else if (newFrameJson['full_url'] != null) {
                String fullUrl = newFrameJson['full_url'].toString();
                int skinsIndex = fullUrl.indexOf('/skins/');
                if (skinsIndex != -1) {
                  frameBaseUrl = fullUrl.substring(0, skinsIndex) + '/';
                }
              }

              if (srcStr.startsWith('data:') || srcStr.startsWith('http')) {
                newLayer[urlField] = srcStr;
              } else if (srcStr.startsWith('/')) {
                newLayer[urlField] = '$base${srcStr.substring(1)}';
              } else if (srcStr.startsWith('../')) {
                newLayer[urlField] = '$frameBaseUrl${srcStr.replaceFirst('../', '')}';
              } else if (srcStr.startsWith('uploads/')) {
                newLayer[urlField] = '$base$srcStr';
              } else {
                newLayer[urlField] = '${frameBaseUrl}skins/$srcStr';
              }
            }
            
            // Server zip extraction replaces spaces with hyphens.
            // Replace proactively to avoid a 404 delay before the fallback kicks in.
            if (!newLayer[urlField].toString().startsWith('data:')) {
              newLayer[urlField] = newLayer[urlField].toString().replaceAll(' ', '-').replaceAll('%20', '-');
            }
          }
        }

        if ((layerName == '_frame_bg' || layerName == '_frame' || layerName == 'frame') && (rawW <= 0 || rawH <= 0)) {
          rawW = frameW;
          rawH = frameH;
        }

        if (newLayer['x'] != null) newLayer['x'] = safeDouble(newLayer['x'] as num) * scaleX;
        if (newLayer['y'] != null) newLayer['y'] = safeDouble(newLayer['y'] as num) * scaleY;
        
        if (newLayer['w'] != null) newLayer['w'] = rawW * scaleX;
        if (newLayer['width'] != null) newLayer['width'] = rawW * scaleX;
        if (newLayer['h'] != null) newLayer['h'] = rawH * scaleY;
        if (newLayer['height'] != null) newLayer['height'] = rawH * scaleY;
        
        if (newLayer['fontSize'] != null) newLayer['fontSize'] = safeDouble(newLayer['fontSize'] as num) * ppiScale * scaleY;
        if (newLayer['font_size'] != null) newLayer['font_size'] = safeDouble(newLayer['font_size'] as num) * ppiScale * scaleY;
        if (newLayer['size'] != null) newLayer['size'] = safeDouble(newLayer['size'] as num) * ppiScale * scaleY;

        newLayer['_isFrameLayer'] = true;
        
        String bLow = layerName;
        if (newLayer['type'] == 'text') {
          if (bLow.contains('name') || bLow.contains('business_name')) newLayer['_businessKey'] = 'name';
          else if (bLow.contains('phone') || bLow.contains('mobile') || bLow.contains('whatsapp') || bLow.contains('number') || bLow.contains('tel')) newLayer['_businessKey'] = 'phone';
          else if (bLow.contains('email') || bLow.contains('mail')) newLayer['_businessKey'] = 'email';
          else if (bLow.contains('website') || bLow.contains('web') || bLow.contains('url')) newLayer['_businessKey'] = 'website';
          else if (bLow.contains('address') || bLow.contains('location')) newLayer['_businessKey'] = 'address';
          bool hasValidUserText = userTexts.containsKey(name) && userTexts[name] != null && userTexts[name]!.trim().isNotEmpty;
          
          if (hasValidUserText && (name.startsWith('_b_') || newLayer['_businessKey'] != null)) {
            newLayer['text'] = userTexts[name]; 
          } else if (Get.isRegistered<HomeController>() && newLayer['_businessKey'] != null) {
            final homeCtrl = Get.find<HomeController>();
            if (newLayer['_businessKey'] == 'name') {
              newLayer['text'] = homeCtrl.businessName.value;
            } else if (newLayer['_businessKey'] == 'phone') {
              int idx = bizKeyCounter['phone']!;
              if (idx == 0) {
                newLayer['text'] = homeCtrl.businessPhone.value.replaceAll(' ', '\u00A0');
              } else if (idx - 1 < homeCtrl.extraPhones.length) {
                newLayer['text'] = homeCtrl.extraPhones[idx - 1].replaceAll(' ', '\u00A0');
              }
              bizKeyCounter['phone'] = idx + 1;
            } else if (newLayer['_businessKey'] == 'email') {
              int idx = bizKeyCounter['email']!;
              if (idx == 0) {
                newLayer['text'] = homeCtrl.businessEmail.value;
              } else if (idx - 1 < homeCtrl.extraEmails.length) {
                newLayer['text'] = homeCtrl.extraEmails[idx - 1];
              }
              bizKeyCounter['email'] = idx + 1;
            } else if (newLayer['_businessKey'] == 'website') {
              int idx = bizKeyCounter['website']!;
              if (idx == 0) {
                newLayer['text'] = homeCtrl.businessWebsite.value;
              } else if (idx - 1 < homeCtrl.extraWebsites.length) {
                newLayer['text'] = homeCtrl.extraWebsites[idx - 1];
              }
              bizKeyCounter['website'] = idx + 1;
            } else if (newLayer['_businessKey'] == 'address') {
              int idx = bizKeyCounter['address']!;
              if (idx == 0) {
                newLayer['text'] = homeCtrl.businessAddress.value;
              } else if (idx - 1 < homeCtrl.extraAddresses.length) {
                newLayer['text'] = homeCtrl.extraAddresses[idx - 1];
              }
              bizKeyCounter['address'] = idx + 1;
            }
          }
        } else if (newLayer['type'] == 'image') {
          if (newLayer['_businessKey'] == null) {
            if (bLow.contains('phone') || bLow.contains('call') || bLow.contains('mobile') || bLow.contains('contact') || bLow.contains('whatsapp') || bLow.contains('tel') || bLow.contains('ph')) newLayer['_businessKey'] = 'phone';
            else if (bLow.contains('email') || bLow.contains('mail')) newLayer['_businessKey'] = 'email';
            else if (bLow.contains('website') || bLow.contains('web') || bLow.contains('url')) newLayer['_businessKey'] = 'website';
            else if (bLow.contains('address') || bLow.contains('location')) newLayer['_businessKey'] = 'address';
            else if (bLow.contains('icon') || bLow.contains('facebook') || bLow.contains('instagram') || bLow.contains('twitter') || bLow.contains('youtube') || bLow.contains('social') || bLow.contains('linkedin')) newLayer['_businessKey'] = 'social';
          }
          if (bLow.contains('logo') && !bLow.contains('email') && !bLow.contains('call') && !bLow.contains('phone') && !bLow.contains('web')) {
            newLayer['_businessKey'] = 'logo';
            if (Get.isRegistered<HomeController>()) {
              final homeCtrl = Get.find<HomeController>();
              if (homeCtrl.businessLogo.value.isNotEmpty) {
                String logoPath = homeCtrl.businessLogo.value;
                if (!logoPath.startsWith('http')) {
                  if (logoPath.startsWith('/')) {
                    logoPath = logoPath.substring(1);
                  }
                  // Replace api from base url if present
                  String base = ApiService.baseUrl.replaceAll('/api', '');
                  if (!base.endsWith('/')) {
                    base += '/';
                  }
                  logoPath = base + logoPath;
                }
                newLayer['src'] = logoPath;
              }
            }
          }
        }

        if (newLayer['type'] == 'image' || newLayer['type'] == 'rect' || newLayer['type'] == 'shape') {
          if (newLayer['is_background'] != true && !(newLayer['is_background'] == null && ['image1', 'main_image', 'bg', 'background', '_frame_bg'].contains(layerName))) {
            if (newLayer['type'] != 'image' || !['phone', 'email', 'website', 'address', 'social'].any((e) => layerName.contains(e))) {
              bool isShapeMarked = newLayer['is_shape'] == true;
              if (newLayer['type'] != 'image' || rawW > 200 || rawH > 200 || isShapeMarked) {
               double px = safeDouble((newLayer['x'] ?? 0) as num);
               double py = safeDouble((newLayer['y'] ?? 0) as num);
               double pw = safeDouble((newLayer['w'] ?? newLayer['width'] ?? 0) as num);
               double ph = safeDouble((newLayer['h'] ?? newLayer['height'] ?? 0) as num);
                if (pw > 20 && ph > 10) {
                  shapeLayers.add({
                    'x': px,
                    'y': py,
                    'w': pw,
                    'h': ph,
                    'fill': newLayer['fill'] ?? newLayer['tint_color'] ?? newLayer['color'],
                    'src': newLayer['src'],
                    'z_index': safeDouble((newLayer['z_index'] ?? 0) as num).toInt(),
                  });
                }
              }
            }
          }
        }

        // ══ FRAME LAYER DIAGNOSTICS ══
        debugPrint('╔══ [FRAME_LOAD] ══════════════════════════════════════');
        debugPrint('║ name="$name" type=${newLayer['type']}');
        debugPrint('║ _is_frame_layer=${newLayer['_is_frame_layer']}');
        debugPrint('║ _businessKey=${newLayer['_businessKey']}');
        debugPrint('║ tint_color=${newLayer['tint_color']}');
        debugPrint('║ fill=${newLayer['fill']}');
        debugPrint('║ color=${newLayer['color']}');
        debugPrint('║ is_shape=${newLayer['is_shape']}');
        debugPrint('║ src=${newLayer['src']?.toString().substring(0, (newLayer['src']?.toString().length ?? 0) > 60 ? 60 : (newLayer['src']?.toString().length ?? 0))}...');
        debugPrint('╚══════════════════════════════════════════════════════');
      }

      // 1. Find the maximum z_index from native/preserved layers
      int maxNativeZIndex = 0;
      for (var l in preservedLayers) {
        int z = (l['z_index'] ?? 0) is int ? (l['z_index'] ?? 0) : ((l['z_index'] ?? 0) as num).toInt();
        if (z > maxNativeZIndex) maxNativeZIndex = z;
      }

      // 2. Boost all frame layer z_index values so they render ON TOP of native layers
      for (var newLayer in newLayers) {
        int frameZ = (newLayer['z_index'] ?? 0) is int ? (newLayer['z_index'] ?? 0) : ((newLayer['z_index'] ?? 0) as num).toInt();
        newLayer['z_index'] = maxNativeZIndex + frameZ + 1;
      }

      // 3. For custom templates: Remove native layers whose _businessKey matches a frame layer's _businessKey
      //    This prevents duplicate logos, phone icons, etc.
      if (type == 'business_custom_frame' || type == 'custom') {
        final Set<String> frameBusinessKeys = {};
        for (var nl in newLayers) {
          final bk = nl['_businessKey'];
          if (bk != null && bk.toString().isNotEmpty) {
            frameBusinessKeys.add(bk.toString());
          }
        }
        if (frameBusinessKeys.isNotEmpty) {
          preservedLayers.removeWhere((l) {
            if (l['_is_frame_layer'] == true) return false; // Don't touch other frame layers
            final bk = l['_businessKey'];
            if (bk != null && frameBusinessKeys.contains(bk.toString())) {
              debugPrint('[FRAME] Removing native layer with businessKey=$bk to avoid duplicate');
              return true;
            }
            return false;
          });
        }
      }

      // 4. Add frame layers after preserved layers
      for (var newLayer in newLayers) {
        preservedLayers.add(newLayer);
      }
      
      // 5. Deduplicate: within the same source (frame vs native), remove duplicates by name.
      //    Frame layers should NOT overwrite native layers with different purposes even if same name.
      final seenNames = <String>{};
      final uniqueLayers = <Map<String, dynamic>>[];
      for (var layer in preservedLayers.reversed) {
        final name = (layer['name'] ?? layer['id'] ?? '').toString();
        if (name.isNotEmpty) {
          String dedupeKey = layer['_is_frame_layer'] == true ? 'frame_$name' : 'native_$name';
          int suffix = 1;
          String finalKey = dedupeKey;
          while (seenNames.contains(finalKey)) {
            finalKey = '${dedupeKey}_$suffix';
            suffix++;
          }
          seenNames.add(finalKey);
          uniqueLayers.add(layer);
        } else {
          uniqueLayers.add(layer);
        }
      }
      
      var finalLayersList = uniqueLayers.reversed.toList();
      _deduplicateLayerNames(finalLayersList);
      templateConfig['layers'] = finalLayersList;

      int newRenderVersion = 1;
      if (newFrameJson['render_version'] != null) {
        newRenderVersion = (newFrameJson['render_version'] is int) ? newFrameJson['render_version'] : safeDouble(newFrameJson['render_version'] as num).toInt();
      } else if (configJson != null && configJson['render_version'] != null) {
        newRenderVersion = (configJson['render_version'] is int) ? configJson['render_version'] : safeDouble(configJson['render_version'] as num).toInt();
      } else if (newFrameJson['info'] != null && newFrameJson['info'] is Map && newFrameJson['info']['render_version'] != null) {
        newRenderVersion = (newFrameJson['info']['render_version'] is int) ? newFrameJson['info']['render_version'] : safeDouble(newFrameJson['info']['render_version'] as num).toInt();
      }
      if (newRenderVersion > (templateConfig['render_version'] as int? ?? 1)) {
        templateConfig['render_version'] = newRenderVersion;
      } else if (newFrameJson['render_version'] != null || (configJson != null && configJson['render_version'] != null)) {
        templateConfig['render_version'] = newRenderVersion;
      }

      templateConfig.refresh();
      _pushHistory();

      // 2. Asynchronous Brightness Check
      _asyncApplyBrightness(newFrameJson, newLayers, preservedLayers, shapeLayers);

    } catch (e, stack) {
      debugPrint('[LOAD_FRAME] Error: $e\n$stack');
    }
  }

  Future<void> _asyncApplyBrightness(
    Map<String, dynamic> newFrameJson,
    List<dynamic> newLayers,
    List<dynamic> preservedLayers,
    List<Map<String, dynamic>> shapeLayers
  ) async {
    try {
      // Process shape images to find their actual brightness
      for (var shape in shapeLayers) {
        if (shape['fill'] == null && shape['src'] != null && shape['src'].toString().isNotEmpty) {
          String sUrl = shape['src'].toString();
          // Normalize URL
          if (!sUrl.startsWith('http')) {
            String baseUrl = templateBaseUrl.isNotEmpty ? templateBaseUrl : AppConfig.baseUrl.replaceAll('/123456', '') + '/public';
            if (sUrl.startsWith('../')) {
              sUrl = '$baseUrl/${sUrl.replaceFirst('../', '')}';
            } else if (sUrl.startsWith('uploads/')) {
              sUrl = '$baseUrl/$sUrl';
            } else {
              sUrl = '$baseUrl/skins/$sUrl';
            }
          }
          
          bool shapeIsDark = false;
          if (shape['tint_color'] != null && shape['tint_color'].toString().isNotEmpty) {
            final Color tint = _parseColor(shape['tint_color'].toString(), fallback: Colors.white);
            final double luminance = (0.299 * tint.red + 0.587 * tint.green + 0.114 * tint.blue);
            shapeIsDark = luminance < 128;
            _brightnessCache[sUrl] = shapeIsDark;
          } else if (_brightnessCache.containsKey(sUrl)) {
            shapeIsDark = _brightnessCache[sUrl]!;
          } else {
            try {
              final resp = await http.get(Uri.parse(sUrl));
              if (resp.statusCode == 200) {
                final codec = await ui.instantiateImageCodec(resp.bodyBytes);
                final frameInfo = await codec.getNextFrame();
                final img = frameInfo.image;
                final data = (await img.toByteData())?.buffer.asUint8List();
                if (data != null) {
                  double totalLuminance = 0;
                  int sampleCount = 0;
                  for (int i = 0; i < data.length; i += 4 * 10) {
                    int r = data[i], g = data[i+1], b = data[i+2], a = data[i+3];
                    if (a > 30) { // Only sample non-transparent pixels
                      totalLuminance += (0.299 * r + 0.587 * g + 0.114 * b);
                      sampleCount++;
                    }
                  }
                  if (sampleCount > 0) {
                    double avgBrightness = totalLuminance / sampleCount;
                    shapeIsDark = avgBrightness < 128;
                    _brightnessCache[sUrl] = shapeIsDark;
                    debugPrint('[SHAPE_BRIGHTNESS] Computed for ${shape['src']} -> avg=$avgBrightness, isDark=$shapeIsDark');
                  }
                }
              }
            } catch (e) {
              debugPrint('[SHAPE_BRIGHTNESS] Error for ${shape['src']}: $e');
            }
          }
          shape['shapeIsDark'] = shapeIsDark;
          shape['hasComputedBrightness'] = true;
        }
      }

      bool templateIsDark = false;
      
      // Priority 1: Use the controller's stored baseImgUrl (set at init from designUrl)
      String imgUrl = baseImgUrl ?? '';
      debugPrint('[BRIGHTNESS] Step 1 - controller.baseImgUrl = "$imgUrl"');
      
      // Priority 2: Check templateConfig for designUrl
      if (imgUrl.isEmpty && templateConfig['designUrl'] != null && templateConfig['designUrl'].toString().isNotEmpty) {
        imgUrl = templateConfig['designUrl'].toString();
        debugPrint('[BRIGHTNESS] Step 2 - templateConfig.designUrl = "$imgUrl"');
      }
      
      // Priority 3: Check newFrameJson for image
      if (imgUrl.isEmpty && newFrameJson['image'] != null && newFrameJson['image'].toString().isNotEmpty) {
        imgUrl = newFrameJson['image'].toString();
        debugPrint('[BRIGHTNESS] Step 3 - newFrameJson.image = "$imgUrl"');
      }
      
      // Priority 4: Find background layer
      if (imgUrl.isEmpty) {
        dynamic bgLayer;
        try {
          bgLayer = preservedLayers.firstWhere(
            (l) => l['is_background'] == true || 
                   (l['is_background'] == null && (l['name'] == 'image1' || 
                   l['name'] == 'main_image' || 
                   l['name'] == 'bg' || 
                   l['name'] == 'background')),
          );
        } catch (_) {
          bgLayer = null;
        }
        if (bgLayer != null && bgLayer['src'] != null) {
          imgUrl = bgLayer['src'].toString();
          debugPrint('[BRIGHTNESS] Step 4 - bgLayer.src = "$imgUrl"');
        }
      }

      if (imgUrl.isEmpty) {
        debugPrint('[BRIGHTNESS] ERROR: No image URL found for brightness detection!');
        // Default to dark since most templates are dark
        templateIsDark = true;
        debugPrint('[BRIGHTNESS] Defaulting templateIsDark=true');
      }

      if (imgUrl.isNotEmpty) {
        // Make URL absolute if needed
        if (!imgUrl.startsWith('http')) {
          String baseUrl = templateBaseUrl.isNotEmpty ? templateBaseUrl : AppConfig.baseUrl.replaceAll('/123456', '') + '/public';
          if (imgUrl.startsWith('../')) {
            imgUrl = '$baseUrl/${imgUrl.replaceFirst('../', '')}';
          } else if (imgUrl.startsWith('uploads/')) {
            imgUrl = '$baseUrl/$imgUrl';
          } else {
            imgUrl = '$baseUrl/skins/$imgUrl';
          }
        }
        debugPrint('[BRIGHTNESS] Final URL = "$imgUrl"');
        
        if (_brightnessCache.containsKey(imgUrl)) {
          templateIsDark = _brightnessCache[imgUrl]!;
          debugPrint('[BRIGHTNESS] CACHED result: templateIsDark=$templateIsDark');
        } else {
          try {
            final resp = await http.get(Uri.parse(imgUrl));
            debugPrint('[BRIGHTNESS] HTTP status=${resp.statusCode}, bodyLen=${resp.bodyBytes.length}');
            if (resp.statusCode == 200) {
              final codec = await ui.instantiateImageCodec(resp.bodyBytes);
              final frameInfo = await codec.getNextFrame();
              final img = frameInfo.image;
              final data = (await img.toByteData())?.buffer.asUint8List();
              if (data != null) {
                double totalLuminance = 0;
                int sampleCount = 0;
                // Sample the ENTIRE image, not just bottom 30%
                for (int i = 0; i < data.length; i += 4 * 20) {
                  int r = data[i], g = data[i+1], b = data[i+2];
                  totalLuminance += (0.299 * r + 0.587 * g + 0.114 * b);
                  sampleCount++;
                }
                if (sampleCount > 0) {
                  double avgBrightness = totalLuminance / sampleCount;
                  templateIsDark = avgBrightness < 128;
                  _brightnessCache[imgUrl] = templateIsDark;
                  debugPrint('[BRIGHTNESS] COMPUTED: avgBrightness=${avgBrightness.toStringAsFixed(1)}, templateIsDark=$templateIsDark (samples=$sampleCount)');
                }
              }
            }
          } catch (e) {
            debugPrint('[BRIGHTNESS] Image fetch/decode error: $e');
            templateIsDark = true; // Default to dark on error
          }
        }
      }

      // Apply colors and refresh UI
      debugPrint('[BRIGHTNESS] Applying colors: templateIsDark=$templateIsDark, shapeLayers=${shapeLayers.length}, textLayers=${newLayers.where((l) => l['type'] == 'text').length}');
      bool needsRefresh = false;
      
      // PASS 0: Logo Background Plate (auto-detect dark background)
      // When background is dark, inject a white rectangle behind the frame's logo
      // so it remains visible on any background color.
      if (templateIsDark) {
        final layers = templateConfig['layers'] as List;
        
        // Find the frame logo layer
        Map<String, dynamic>? logoLayer;
        int logoIndex = -1;
        for (int i = 0; i < layers.length; i++) {
          final l = layers[i];
          if (l['_businessKey'] == 'logo' && 
              (l['_isFrameLayer'] == true || l['_is_frame_layer'] == true)) {
            logoLayer = l;
            logoIndex = i;
            break;
          }
        }
        
        if (logoLayer != null && logoIndex >= 0) {
          // Remove any existing plate (prevents duplicates on frame switch)
          layers.removeWhere((l) => l['name'] == '_logo_bg_plate');
          
          // Recalculate logoIndex after removal
          logoIndex = layers.indexOf(logoLayer);
          
          // Calculate plate dimensions
          double logoX = safeDouble(logoLayer['x'] ?? 0);
          double logoY = safeDouble(logoLayer['y'] ?? 0);
          double logoW = safeDouble(logoLayer['w'] ?? logoLayer['width'] ?? 0);
          double logoH = safeDouble(logoLayer['h'] ?? logoLayer['height'] ?? 0);
          
          double paddingX = 10.0;
          double paddingBottom = 10.0;
          double paddingTop = 20.0;
          
          double plateX = logoX - paddingX;          // 10px left gap
          double plateY = logoY - paddingTop;        // 20px top gap
          double plateW = logoW + (paddingX * 2);    // 10px left + 10px right
          double plateH = logoH + paddingTop + paddingBottom;   // 20px top + logo + 10px bottom
          
          // Create the plate layer
          Map<String, dynamic> plateLayer = {
            'name': '_logo_bg_plate',
            'type': 'solid_rect',
            'x': plateX,
            'y': plateY,
            'w': plateW,
            'h': plateH,
            'color': '#FFFFFF',
            'z_index': (logoLayer['z_index'] ?? 99) is int 
                ? (logoLayer['z_index'] ?? 99) - 1 
                : ((logoLayer['z_index'] ?? 99) as num).toInt() - 1,
            '_isFrameLayer': true,
            '_is_logo_plate': true,
          };
          
          // Insert BEFORE the logo so it renders behind it
          if (logoIndex >= 0 && logoIndex <= layers.length) {
            layers.insert(logoIndex, plateLayer);
          }
          needsRefresh = true;
          
          debugPrint('[LOGO_PLATE] ✅ Injected white plate: '
            'x=$plateX y=$plateY w=$plateW h=$plateH '
            'logoAt=($logoX,$logoY,$logoW,$logoH)');
        }
      } else {
        // Light background: remove any existing plate
        final layers = templateConfig['layers'] as List;
        final removed = layers.where((l) => l['name'] == '_logo_bg_plate').length;
        layers.removeWhere((l) => l['name'] == '_logo_bg_plate');
        if (removed > 0) {
          needsRefresh = true;
          debugPrint('[LOGO_PLATE] ❌ Removed plate (light background)');
        }
      }

      int renderVersion = (templateConfig['render_version'] ?? 1) as int;

      // PASS 1: Apply colors to TEXT layers first
      for (var newLayer in newLayers) {
        if (newLayer['type'] == 'text') {
          if (_applyDynamicTextColor(newLayer, templateIsDark, shapeLayers, renderVersion: renderVersion)) {
            needsRefresh = true;
          }
        }
      }
      
      // PASS 2: Apply colors to ICON layers - match paired text color (same as web editor)
      for (var newLayer in newLayers) {
        final String lname = (newLayer['name'] ?? newLayer['id'] ?? '').toString().toLowerCase();
        bool isIcon = (newLayer['type'] == 'image' || newLayer['type'] == 'icon') && (
          ['phone', 'email', 'website', 'address', 'social'].contains(newLayer['_businessKey']) ||
          ['phone', 'email', 'website', 'address', 'call', 'mobile', 'contact', 'whatsapp', 'tel',
           'mail', 'web', 'url', 'location', 'icon', 'facebook', 'instagram', 'twitter', 'youtube',
           'social', 'linkedin'].any((key) => lname.contains(key)) ||
          newLayer['_originalType'] == 'icon' ||
          (newLayer['_source_meta'] is Map && newLayer['_source_meta']['type'] == 'icon')
        );
        if (!isIcon) continue;
        
        String? matchedTextColor;
        
        // V7+ uses independent background color detection for icons, NOT pairing!
        if (renderVersion < 7) {
          // Determine the business key for this icon to find matching text
          String? iconBizKey = newLayer['_businessKey']?.toString();
          if (iconBizKey == null || iconBizKey.isEmpty) {
            if (lname.contains('phone') || lname.contains('call') || lname.contains('mobile') || lname.contains('contact') || lname.contains('whatsapp') || lname.contains('tel')) iconBizKey = 'phone';
            else if (lname.contains('email') || lname.contains('mail')) iconBizKey = 'email';
            else if (lname.contains('web') || lname.contains('url')) iconBizKey = 'website';
            else if (lname.contains('address') || lname.contains('location')) iconBizKey = 'address';
            else iconBizKey = 'social';
          }
          
          // Try to find matching text layer and copy its color
          if (iconBizKey != 'social') {
            for (var textLayer in newLayers) {
              if (textLayer['type'] != 'text') continue;
              String? textBizKey = textLayer['_businessKey']?.toString();
              if (textBizKey == null || textBizKey.isEmpty) {
                final String tname = (textLayer['name'] ?? textLayer['id'] ?? '').toString().toLowerCase();
                if (tname.contains('phone') || tname.contains('mobile') || tname.contains('number') || tname.contains('call')) textBizKey = 'phone';
                else if (tname.contains('email') || tname.contains('mail')) textBizKey = 'email';
                else if (tname.contains('web') || tname.contains('url')) textBizKey = 'website';
                else if (tname.contains('address') || tname.contains('location')) textBizKey = 'address';
              }
              if (textBizKey == iconBizKey) {
                matchedTextColor = textLayer['font_color'] ?? textLayer['color'];
                break;
              }
            }
          }
        }
        
        String layerName = (newLayer['name'] ?? '').toString();
        if (_applyDynamicTextColor(newLayer, templateIsDark, shapeLayers, matchedColor: matchedTextColor, renderVersion: renderVersion)) {
          needsRefresh = true;
        }
      }
      
      // PASS 3: Apply colors to general ICON-TYPE layers (V7+)
      if (renderVersion >= 7) {
        for (var newLayer in newLayers) {
          if (newLayer['type'] == 'icon' && newLayer['_pass2_colored'] != true) {
            if (_applyDynamicTextColor(newLayer, templateIsDark, shapeLayers, renderVersion: renderVersion)) {
              needsRefresh = true;
            }
          }
        }
      }
      
      debugPrint('[BRIGHTNESS] needsRefresh=$needsRefresh');
      if (needsRefresh) {
        templateConfig.refresh();
      }
    } catch (e) {
      debugPrint('[BRIGHTNESS_ASYNC] Error: $e');
    }
  }

  bool _applyDynamicTextColor(
    Map<String, dynamic> layer,
    bool templateIsDark,
    List<Map<String, dynamic>> shapeLayers,
    {String? matchedColor, int renderVersion = 1}
  ) {
    final String diagName = (layer['name'] ?? layer['id'] ?? '').toString();
    debugPrint('[COLOR_DIAG] Starting _applyDynamicTextColor for layer: "$diagName" (type: ${layer['type']})');
    
    // DO NOT override user colors for Custom templates / frames!
    final type = Get.parameters['type'] ?? templateConfig['type'] ?? 'business_custom_frame';
    final tLow = type.toString().toLowerCase();
    debugPrint('[COLOR_DIAG] Editor Type: $type ($tLow)');
    if (tLow.contains('custom') || tLow == 'post' || tLow == 'business_custom_frame') {
      debugPrint('[COLOR_DIAG] ❌ SKIPPED: Template type is custom or business_custom_frame');
      return false; // Skip auto-color for custom templates to preserve original ZIP colors
    }

    bool isText = layer['type'] == 'text';
    final String lname = (layer['name'] ?? layer['id'] ?? '').toString().toLowerCase();
    
    // Only target true contact/social icons. Do not broadly target all "icon" layers.
    bool isContactIcon = (layer['type'] == 'image' || layer['type'] == 'icon') && (
                  ['phone', 'email', 'website', 'address', 'social'].contains(layer['_businessKey']) ||
                  ['phone', 'email', 'website', 'address', 'call', 'mobile', 'contact', 'whatsapp', 'tel',
                   'mail', 'web', 'url', 'location', 'facebook', 'instagram', 'twitter', 'youtube',
                   'social', 'linkedin'].any((key) => lname.contains(key))
                  );

    // For V7+, we allow all icons to be processed by the Smart Color Adaptation logic.
    bool isIcon = renderVersion >= 7 ? (layer['type'] == 'icon' || isContactIcon) : isContactIcon;
    debugPrint('[COLOR_DIAG] isText=$isText, isContactIcon=$isContactIcon, isIcon=$isIcon');

    // DO NOT override colors for frame layers — EXCEPT for text and contact icons.
    if (layer['_is_frame_layer'] == true || layer['_isFrameLayer'] == true) {
      if (!isText && !isIcon) {
        debugPrint('[COLOR_DIAG] ❌ SKIPPED: Regular frame layer that is not text or icon');
        return false;
      }
    }

    if (!isText && !isIcon) {
      debugPrint('[COLOR_DIAG] ❌ SKIPPED: Not text or contact icon');
      return false;
    }

    final String layerName = (layer['name'] ?? layer['id'] ?? '').toString();
    debugPrint('[COLOR_DIAG] ✅ PROCEEDING for "$layerName" (isText=$isText, isIcon=$isIcon)');
    
    final double textX = safeDouble(layer['x'] ?? 0);
    final double textY = safeDouble(layer['y'] ?? 0);
    final double textW = safeDouble(layer['w'] ?? layer['width'] ?? 0);
    final double textH = safeDouble(layer['h'] ?? layer['height'] ?? 0);
    final double textCenterX = textX + textW / 2;
    final double textCenterY = textY + textH / 2;

    bool overlapsShape = false;
    bool shapeIsDark = false;

    // V8+ Z-Index accurate collision detection
    List<Map<String, dynamic>> shapesToCheck = shapeLayers;
    if (renderVersion >= 8) {
      shapesToCheck = List.from(shapeLayers);
      shapesToCheck.sort((a, b) {
        int za = (a['z_index'] ?? 0) as int;
        int zb = (b['z_index'] ?? 0) as int;
        return zb.compareTo(za); // Descending order
      });
    }

    for (var shape in shapesToCheck) {
      final String shapeName = (shape['name'] ?? shape['id'] ?? '').toString().toLowerCase();
      final String currentName = (layer['name'] ?? layer['id'] ?? '').toString().toLowerCase();
      if (shapeName == currentName && shapeName.isNotEmpty) {
        continue;
      }
      
      final double sx = safeDouble(shape['x'] ?? 0);
      final double sy = safeDouble(shape['y'] ?? 0);
      final double sw = safeDouble(shape['w'] ?? shape['width'] ?? 0);
      final double sh = safeDouble(shape['h'] ?? shape['height'] ?? 0);

      if (textCenterX >= sx && textCenterX <= (sx + sw) &&
          textCenterY >= sy && textCenterY <= (sy + sh)) {
        overlapsShape = true;
        
        if (shape['hasComputedBrightness'] == true) {
          shapeIsDark = shape['shapeIsDark'] == true;
          debugPrint('[COLOR] "$layerName" overlaps image shape with computed shapeIsDark=$shapeIsDark (src=${shape['src']})');
        } else {
          // Parse the shape's fill color to determine its brightness
          final fillVal = shape['fill']?.toString() ?? '#FFFFFF';
          final Color shapeColor = _parseColor(fillVal, fallback: Colors.white);
          
          // Compute brightness (standard formula)
          final double luminance = (0.299 * shapeColor.red + 0.587 * shapeColor.green + 0.114 * shapeColor.blue);
          shapeIsDark = luminance < 128;
          
          debugPrint('[COLOR] "$layerName" overlaps shape at (${sx.toInt()},${sy.toInt()},${sw.toInt()},${sh.toInt()}) with fill="$fillVal" (shapeIsDark=$shapeIsDark)');
        }
        break;
      }
    }

    if (layer['original_color'] == null) {
      layer['original_color'] = layer['color'] ?? layer['tint_color'] ?? '0xFFFFFFFF';
    }

    bool changed = false;
    String newColor;
    if (matchedColor != null) {
      newColor = matchedColor;
    } else {
      bool isBgDark = overlapsShape ? shapeIsDark : templateIsDark;
      final String origColorStr = layer['original_color'].toString();
      final Color origColor = _parseColor(origColorStr, fallback: isBgDark ? Colors.white : Colors.black);

      if (renderVersion >= 7) {
        // V7+ WCAG Smart Contrast Logic
        Color bgColor = overlapsShape 
            ? _parseColor((shapesToCheck.firstWhere((s) {
                final double sx = safeDouble(s['x'] ?? 0);
                final double sy = safeDouble(s['y'] ?? 0);
                final double sw = safeDouble(s['w'] ?? s['width'] ?? 0);
                final double sh = safeDouble(s['h'] ?? s['height'] ?? 0);
                return textCenterX >= sx && textCenterX <= (sx + sw) && textCenterY >= sy && textCenterY <= (sy + sh);
              }, orElse: () => {'fill': isBgDark ? '#000000' : '#FFFFFF'})['fill'] ?? (isBgDark ? '#000000' : '#FFFFFF')).toString(), fallback: isBgDark ? Colors.black : Colors.white)
            : (isBgDark ? const Color(0xFF000000) : const Color(0xFFFFFFFF));
        
        double contrastRatio = _computeContrastRatio(origColor, bgColor);
        
        if (contrastRatio >= 2.0) {
          // It's readable! Keep the original color.
          newColor = origColorStr;
        } else {
          // Clash! It's not readable. Override to White or Black based on background.
          newColor = isBgDark ? '0xFFFFFFFF' : '0xFF000000';
        }
        debugPrint('[COLOR_DIAG] V7 SmartColor: bgIsDark=$isBgDark, origColor=$origColor, bgColor=$bgColor, contrast=$contrastRatio -> Result: $newColor');
      } else {
        // V1-V6 Legacy Logic
        if (overlapsShape) {
          newColor = shapeIsDark ? '0xFFFFFFFF' : '0xFF000000';
        } else {
          final double origLuminance = (0.299 * origColor.red + 0.587 * origColor.green + 0.114 * origColor.blue);
          bool isOrigDark = origLuminance < 128;
          
          if (isBgDark) {
            // Background is Dark. Needs Light color.
            if (!isOrigDark) {
              newColor = origColorStr; // Already light, keep original
            } else {
              newColor = '0xFFFFFFFF'; // Dark on dark, force white
            }
          } else {
            // Background is Light. Needs Dark color.
            if (isOrigDark) {
              newColor = origColorStr; // Already dark, keep original
            } else {
              newColor = '0xFF000000'; // Light on light, force black
            }
          }
        }
      }
    }
    if (isText) {
      debugPrint('[COLOR] TEXT "$layerName" → templateIsDark=$templateIsDark overlapsShape=$overlapsShape → color=$newColor (was: ${layer['color']})');
      if (layer['color'] != newColor) changed = true;
      layer['color'] = newColor;
      layer['font_color'] = newColor;
    } else if (isIcon) {
      debugPrint('[COLOR] ICON "$layerName" → templateIsDark=$templateIsDark overlapsShape=$overlapsShape → tint=$newColor (was: ${layer['tint_color']})');
      if (layer['tint_color'] != newColor || layer['color'] != newColor) changed = true;
      layer['tint_color'] = newColor;
      layer['color'] = newColor;
      // All icons use font_color in _buildIconLayer (both type=='icon' and type=='image' with icon metadata)
      layer['font_color'] = newColor;
    }
    return changed;
  }

  Color _parseColor(String colorStr, {Color fallback = const Color(0xFF000000)}) {
    if (colorStr.isEmpty) return fallback;
    
    // Handle rgb(r,g,b) format
    if (colorStr.startsWith('rgb') && !colorStr.startsWith('rgba')) {
      try {
        final parts = colorStr
            .replaceAll(RegExp(r'[a-zA-Z\(\)]'), '')
            .split(',');
        if (parts.length >= 3) {
          return Color.fromARGB(
            255,
            int.parse(parts[0].trim()),
            int.parse(parts[1].trim()),
            int.parse(parts[2].trim()),
          );
        }
      } catch (_) {}
      return fallback;
    }
    
    // Handle rgba(r,g,b,a) format
    if (colorStr.startsWith('rgba')) {
      try {
        final parts = colorStr
            .replaceAll(RegExp(r'[a-zA-Z\(\)]'), '')
            .split(',');
        if (parts.length >= 4) {
          return Color.fromARGB(
            (double.parse(parts[3]) * 255).round(),
            int.parse(parts[0]),
            int.parse(parts[1]),
            int.parse(parts[2]),
          );
        }
      } catch (_) {}
      return fallback;
    }
    
    String hex = colorStr.replaceAll('#', '').replaceAll('0x', '').replaceAll('0X', '');
    if (hex.length == 6) hex = 'FF$hex';
    
    if (hex.length == 8) {
      int? parsed = int.tryParse(hex, radix: 16);
      if (parsed != null) return Color(parsed);
    }
    
    return fallback;
  }

  double _computeContrastRatio(Color fg, Color bg) {
    double fgL = _relativeLuminance(fg);
    double bgL = _relativeLuminance(bg);
    double lighter = max(fgL, bgL);
    double darker = min(fgL, bgL);
    return (lighter + 0.05) / (darker + 0.05);
  }

  double _relativeLuminance(Color c) {
    double r = c.red / 255.0;
    double g = c.green / 255.0;
    double b = c.blue / 255.0;
    r = r <= 0.03928 ? r / 12.92 : pow((r + 0.055) / 1.055, 2.4) as double;
    g = g <= 0.03928 ? g / 12.92 : pow((g + 0.055) / 1.055, 2.4) as double;
    b = b <= 0.03928 ? b / 12.92 : pow((b + 0.055) / 1.055, 2.4) as double;
    return 0.2126 * r + 0.7152 * g + 0.0722 * b;
  }
}



import '../utils/safe_double.dart';
import 'package:get/get.dart';

import 'package:flutter/material.dart';

import 'dart:convert';

import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';

import 'dart:ui' as ui;
import '../config/app_config.dart';

import '../services/api_service.dart';
import '../utils/dynamic_color_resolver.dart';
import '../utils/template_json_cache.dart';

import '../controllers/home_controller.dart';
import 'package:flutter_cache_manager/flutter_cache_manager.dart';
import 'package:cached_network_image/cached_network_image.dart';
import 'dart:async';
import 'package:flutter/foundation.dart';

class NativeEditorController extends GetxController {
  static int? timelineTapTime;
  static int? timelineLoadStart;
  static int? timelinePrecacheStart;
  static int? timelinePrecacheEnd;
  static int? timelineMergeComplete;
  static int? timelineConfigUpdated;
  static int? timelineRefreshComplete;
  static int? timelineObxRebuild;
  static int? timelineCanvasBuild;
  static int? timelineInteractiveLayerBuild;
  static bool timelinePlaceholderCalled = false;
  static bool timelineImageBuilderCalled = false;

  static final Rx<Uint8List?> transitionSnapshot = Rx<Uint8List?>(null);
  Future<void> Function()? captureCanvasCallback;

  // The complete living JSON configuration of the template
  final RxMap<String, dynamic> templateConfig = <String, dynamic>{}.obs;

  // Frame API Integration
  final RxList<dynamic> frames = <dynamic>[].obs;
  final RxBool isLoadingFrames = false.obs;
  final RxBool isCanvasLoading = false.obs;
  final RxString frameTransitionPreviewUrl = ''.obs;
  final RxInt frameTransitionGeneration = 0.obs;
  final RxInt editorSessionGeneration = 0.obs;
  int _pendingV10TransitionGeneration = 0;
  int _frameLoadGeneration = 0;
  bool _initialFrameRequested = false;
  bool _initialFrameApplied = false;
  bool _initialFrameApplying = false;
  final RxString loadingFrameId =
      ''.obs; // ID of the frame currently loading (for thumbnail indicator)

  // The ID or name of the currently selected layer
  final RxString selectedLayerId = ''.obs;

  // The currently active contextual tool (e.g. 'Edit', 'Font', 'Nudge')
  final RxString activeTool = ''.obs;
  final RxInt layerUpdateTrigger =
      0.obs; // Forces Obx rebuild on layer property changes

  // Flag used to differentiate between canvas background taps and layer taps

  @override
  void onInit() {
    super.onInit();
    // Default config if none is provided
    templateConfig.value = {'width': 1080, 'height': 1080, 'layers': []};
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
      final response = await ApiService.get(
        '/get-all-frames?business_category_id=$bcId&userId=$userId',
      );
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
      // Pre-cache ALL frame images in the background for instant switching
      _backgroundPrecacheAllFrames();
      unawaited(_applyInitialFrameWhenReady());
    }
  }

  /// Silently pre-downloads all images from all frames to disk cache.
  /// This runs in the background after frame list loads so that when
  /// a user taps any frame, images are already on disk → instant render.
  void _backgroundPrecacheAllFrames() {
    Future(() async {
      int cachedCount = 0;
      final String apiBase = ApiService.baseUrl;
      final Uri baseUri = Uri.parse(apiBase);
      List<String> segments = baseUri.pathSegments.toList();
      if (segments.isNotEmpty) segments.removeLast();
      final String serverBase =
          baseUri.replace(pathSegments: segments).toString().endsWith('/')
          ? baseUri.replace(pathSegments: segments).toString()
          : '${baseUri.replace(pathSegments: segments).toString()}/';

      for (var frame in frames) {
        // Get layers from config
        List<dynamic>? layers;
        final config = frame['config'];
        final jsonField = frame['json'] ?? frame['json_rules'];

        if (config != null) {
          if (config is Map && config['layers'] != null) {
            layers = config['layers'] as List<dynamic>;
          } else if (config is String) {
            try {
              layers = jsonDecode(config)['layers'];
            } catch (_) {}
          }
        } else if (jsonField != null) {
          if (jsonField is Map && jsonField['layers'] != null) {
            layers = jsonField['layers'] as List<dynamic>;
          } else if (jsonField is String) {
            try {
              layers = jsonDecode(jsonField)['layers'];
            } catch (_) {}
          }
        }

        // Also pre-cache the full_url (frame overlay image)
        final String fullUrl = (frame['full_url'] ?? '').toString();
        if (fullUrl.isNotEmpty && fullUrl.startsWith('http')) {
          try {
            final cached = await DefaultCacheManager().getFileFromCache(
              fullUrl,
            );
            if (cached == null) {
              await DefaultCacheManager().downloadFile(fullUrl);
              cachedCount++;
            }
          } catch (_) {}
        }

        // Pre-cache all image layer sources
        if (layers != null) {
          for (var layer in layers) {
            if (layer['type'] == 'image' && layer['src'] != null) {
              String src = layer['src'].toString();
              if (src.isEmpty || src.startsWith('data:')) continue;

              // Resolve relative URL to absolute
              if (!src.startsWith('http')) {
                String zipName = (frame['zip_name'] ?? frame['path'] ?? '')
                    .toString()
                    .replaceAll('/', '')
                    .trim();
                String frameBaseUrl = serverBase;
                if (zipName.isNotEmpty) {
                  frameBaseUrl = '${serverBase}uploads/template/$zipName/';
                } else if (fullUrl.isNotEmpty) {
                  int skinsIndex = fullUrl.indexOf('/skins/');
                  if (skinsIndex != -1) {
                    frameBaseUrl = '${fullUrl.substring(0, skinsIndex)}/';
                  }
                }

                if (src.startsWith('/')) {
                  src = '$serverBase${src.substring(1)}';
                } else if (src.startsWith('../')) {
                  src = '$frameBaseUrl${src.replaceFirst('../', '')}';
                } else if (src.startsWith('uploads/')) {
                  src = '$serverBase$src';
                } else {
                  src = '${frameBaseUrl}skins/$src';
                }
              }

              src = src.replaceAll(' ', '-').replaceAll('%20', '-');

              try {
                final cached = await DefaultCacheManager().getFileFromCache(
                  src,
                );
                if (cached == null) {
                  await DefaultCacheManager().downloadFile(src);
                  cachedCount++;
                }
              } catch (_) {}
            }
          }
        }
      }
      debugPrint(
        '✅ [PRE-CACHE] Background pre-cached $cachedCount images from ${frames.length} frames',
      );
    });
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
          await TemplateJsonCache.save(
            zipName,
            jsonEncode(data['json']),
            data['updated_at'],
          );
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

    int activeEmails =
        (hc.businessEmail.value.isNotEmpty ? 1 : 0) + hc.extraEmails.length;
    int activePhones =
        (hc.businessPhone.value.isNotEmpty ? 1 : 0) + hc.extraPhones.length;
    int activeWebsites =
        (hc.businessWebsite.value.isNotEmpty ? 1 : 0) + hc.extraWebsites.length;
    int activeAddresses =
        (hc.businessAddress.value.isNotEmpty ? 1 : 0) +
        hc.extraAddresses.length;

    final hf = hc.hiddenFrameFields;
    if (hf['emails'] != null) activeEmails -= (hf['emails'] as List).length;
    if (hf['mobile_numbers'] != null)
      activePhones -= (hf['mobile_numbers'] as List).length;
    if (hf['websites'] != null)
      activeWebsites -= (hf['websites'] as List).length;
    if (hf['addresses'] != null)
      activeAddresses -= (hf['addresses'] as List).length;

    activeEmails = activeEmails.clamp(0, 99);
    activePhones = activePhones.clamp(0, 99);
    activeWebsites = activeWebsites.clamp(0, 99);
    activeAddresses = activeAddresses.clamp(0, 99);

    return frames.where((frame) {
      int reqEmail = int.tryParse(frame['req_email']?.toString() ?? '0') ?? 0;
      int reqPhone = int.tryParse(frame['req_phone']?.toString() ?? '0') ?? 0;
      int reqWeb = int.tryParse(frame['req_website']?.toString() ?? '0') ?? 0;
      int reqAddress =
          int.tryParse(frame['req_address']?.toString() ?? '0') ?? 0;

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
  void initConfig(
    Map<String, dynamic> initialConfig,
    String tplBaseUrl,
    String upBaseUrl,
    String? baseImg,
    String editorType,
  ) {
    _resetEditorSession();
    templateConfig.assignAll(
      jsonDecode(jsonEncode(initialConfig)),
    ); // deep copy
    templateBaseUrl = tplBaseUrl;
    uploadsBaseUrl = upBaseUrl;
    baseImgUrl = baseImg;
    templateConfig['type'] = editorType;

    // Default render_version to 1 for legacy frames without versioning
    templateConfig['render_version'] ??= 1;
    _ensureV10LayerIds(templateConfig['layers']);

    historyStack.clear();
    historyIndex.value = -1;

    if (editorType == 'business_custom_frame') {
      _injectDynamicBusinessFrame();
    }

    // A frame list may already be cached, or it may still be arriving from
    // the API. In either case apply exactly one default frame for this newly
    // opened template; `_applyInitialFrameWhenReady` handles both timings.
    _initialFrameRequested = true;
    unawaited(_applyInitialFrameWhenReady());

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
      String zipName =
          (initialConfig['zip_name'] ??
                  initialConfig['path'] ??
                  templateConfig['zip_name'] ??
                  templateConfig['path'] ??
                  '')
              .toString()
              .replaceAll('/', '')
              .trim();
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

      if (zipName.isNotEmpty &&
          !frameBaseUrl.contains('/uploads/template/$zipName')) {
        frameBaseUrl = '${base}uploads/template/$zipName/';
        this.templateBaseUrl = frameBaseUrl;
      } else if (this.templateBaseUrl.isEmpty ||
          !this.templateBaseUrl.contains('/uploads/template/')) {
        this.templateBaseUrl = frameBaseUrl;
      }

      for (var layer in (templateConfig['layers'] as List)) {
        if (layer is Map<String, dynamic>) {
          for (var urlField in ['src', '_fallback_src']) {
            if (layer[urlField] != null &&
                layer[urlField].toString().isNotEmpty) {
              String srcStr = layer[urlField].toString();
              if (srcStr.startsWith('data:') || srcStr.startsWith('http')) {
                if (srcStr.contains('/skins/skins/')) {
                  srcStr = srcStr.replaceAll('/skins/skins/', '/skins/');
                  layer[urlField] = srcStr;
                }
                if (zipName.isNotEmpty &&
                    srcStr.contains('/uploads/skins/$zipName/')) {
                  srcStr = srcStr.replaceAll(
                    '/uploads/skins/$zipName/',
                    '/uploads/template/$zipName/skins/$zipName/',
                  );
                  layer[urlField] = srcStr;
                }
              } else if (srcStr.startsWith('/')) {
                layer[urlField] = '$base${srcStr.substring(1)}';
              } else if (srcStr.startsWith('../')) {
                layer[urlField] =
                    '$frameBaseUrl${srcStr.replaceFirst('../', '')}';
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
              if (layer[urlField] != null &&
                  !layer[urlField].toString().startsWith('data:')) {
                layer[urlField] = layer[urlField]
                    .toString()
                    .replaceAll(' ', '-')
                    .replaceAll('%20', '-');
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

  /// Starts every template with a clean canvas state. Transition snapshots are
  /// static so they outlive an editor route unless explicitly cleared; without
  /// this reset a newly opened template can visually retain the old template
  /// until a frame is selected.
  void _resetEditorSession() {
    editorSessionGeneration.value++;
    _frameLoadGeneration++;
    _pendingV10TransitionGeneration = 0;
    _initialFrameRequested = false;
    _initialFrameApplied = false;
    _initialFrameApplying = false;

    selectedLayerId.value = '';
    activeTool.value = '';
    loadingFrameId.value = '';
    isCanvasLoading.value = false;
    frameTransitionPreviewUrl.value = '';
    NativeEditorController.transitionSnapshot.value = null;
  }

  /// Applies the first compatible frame exactly once when a template opens.
  /// The latest selection always wins because `loadNewFrame` is generation
  /// guarded; a user tap can never be overwritten by a late auto-load.
  Future<void> _applyInitialFrameWhenReady() async {
    if (!_initialFrameRequested ||
        _initialFrameApplied ||
        _initialFrameApplying ||
        isLoadingFrames.value ||
        filteredFrames.isEmpty) {
      return;
    }

    final dynamic first = filteredFrames.first;
    if (first is! Map) return;
    final int session = editorSessionGeneration.value;
    _initialFrameApplying = true;
    _initialFrameApplied = true;

    try {
      final frame = Map<String, dynamic>.from(first);
      final String frameId = (frame['id'] ?? frame['zip_name'] ?? '').toString();
      if (frameId.isNotEmpty) loadingFrameId.value = frameId;
      await loadNewFrame(_normaliseFrameRecord(frame));
    } finally {
      if (session == editorSessionGeneration.value) {
        _initialFrameApplying = false;
      }
    }
  }

  /// Makes catalogue records safe for the frame merger regardless of whether
  /// the API supplied `json`, `json_rules`, or an already-decoded `config`.
  Map<String, dynamic> _normaliseFrameRecord(Map<String, dynamic> frame) {
    final payload = jsonDecode(jsonEncode(frame)) as Map<String, dynamic>;
    dynamic rawConfig = payload['json'] ?? payload['json_rules'] ?? payload['config'];
    Map<String, dynamic>? config;

    if (rawConfig is String && rawConfig.trim().isNotEmpty) {
      try {
        final decoded = jsonDecode(rawConfig);
        if (decoded is Map) config = Map<String, dynamic>.from(decoded);
      } catch (_) {}
    } else if (rawConfig is Map) {
      config = Map<String, dynamic>.from(rawConfig);
    }

    if (config != null) {
      final fullUrl = (payload['full_url'] ?? '').toString();
      if (fullUrl.isNotEmpty) config['full_url'] = fullUrl;
      payload['config'] = config;
      payload['layers'] = config['layers'];
      payload['render_version'] ??= config['render_version'];
      payload['info'] ??= config['info'];
    }

    return payload;
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
    bool hasFrame = layers.any(
      (l) => l['_businessKey'] != null || l['_isFrameLayer'] == true,
    );
    if (hasFrame) return;

    final double cW = safeDouble(
      (templateConfig['info']?['width'] ?? templateConfig['width'] ?? 1080)
          as num,
    );
    final double cH = safeDouble(
      (templateConfig['info']?['height'] ?? templateConfig['height'] ?? 1080)
          as num,
    );

    // Default business layers (matching web editor addBusinessElements)

    // Logo
    if (homeCtrl.businessLogo.value.isNotEmpty) {
      layers.add({
        'name': '_b_logo',
        'type': 'image',
        'src': homeCtrl.businessLogo.value,
        'x': cW * 0.08,
        'y': cH * 0.08,
        'w': cW * 0.15,
        'h': cW * 0.15,
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
        'x': cW * 0.12,
        'y': cH * 0.88,
        'w': cW * 0.4,
        'h': 26,
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
        'x': cW * 0.08,
        'y': cH * 0.92,
        'w': cW * 0.5,
        'h': 26,
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
        'x': cW * 0.5,
        'y': cH * 0.88,
        'w': cW * 0.45,
        'h': 26,
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
        'x': cW * 0.5,
        'y': cH * 0.92,
        'w': cW * 0.45,
        'h': 26,
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
      'phone': 0,
      'email': 0,
      'website': 0,
      'address': 0,
    };

    bool updated = false;
    for (var layer in layers) {
      if (layer is Map<String, dynamic>) {
        final binding = _renderVersion() >= 10
            ? _parseV10BusinessBinding(layer)
            : null;
        if (binding != null) {
          layer['_businessKey'] = binding['field'];
          layer['_businessIndex'] = binding['index'];
          layer['business_field'] = binding['field'];
          layer['business_field_index'] = binding['index'];
          layer['placeholder_key'] = binding['key'];
          layer['ai_field'] = binding['key'];
        }
        if (layer['_businessKey'] == null) continue;
        final String key = layer['_businessKey'].toString();
        if (layer['type'] == 'text') {
          if (key == 'name') {
            if (layer['text'] != homeCtrl.businessName.value) {
              layer['text'] = homeCtrl.businessName.value;
              updated = true;
            }
          } else if (bizKeyCounter.containsKey(key)) {
            final int idx =
                int.tryParse(binding?['index']?.toString() ?? '') ??
                bizKeyCounter[key]!;
            bizKeyCounter[key] = idx + 1 > bizKeyCounter[key]!
                ? idx + 1
                : bizKeyCounter[key]!;
            final String targetVal = _businessValueAt(homeCtrl, key, idx);
            if (targetVal.isNotEmpty && layer['text'] != targetVal) {
              layer['text'] = targetVal;
              updated = true;
            }
          }
        } else if (layer['type'] == 'image' || layer['type'] == 'icon') {
          if (key == 'logo' && homeCtrl.businessLogo.value.isNotEmpty) {
            String logoPath = homeCtrl.businessLogo.value;
            if (!logoPath.startsWith('http')) {
              logoPath =
                  ApiService.baseUrl.replaceAll('/api', '') +
                  '/' +
                  (logoPath.startsWith('/') ? logoPath.substring(1) : logoPath);
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

            double iw = safeDouble(
              (imgLayer['w'] ?? imgLayer['width'] ?? 0) as num,
            );
            double ih = safeDouble(
              (imgLayer['h'] ?? imgLayer['height'] ?? 0) as num,
            );
            if (iw <= 0 || ih <= 0 || iw > 80 || ih > 80)
              continue; // Must be a small icon asset

            double ix = safeDouble((imgLayer['x'] ?? 0) as num);
            double iy = safeDouble((imgLayer['y'] ?? 0) as num);
            double icY = iy + ih / 2;

            Map<String, dynamic>? bestTextLayer;
            double bestDist = 999999;

            for (var txtLayer in layers) {
              if (txtLayer is Map<String, dynamic> &&
                  txtLayer['type'] == 'text') {
                String? txtBizKey = txtLayer['_businessKey']?.toString();
                if (txtBizKey == null || txtBizKey.isEmpty) {
                  final String tname =
                      (txtLayer['name'] ?? txtLayer['id'] ?? '')
                          .toString()
                          .toLowerCase();
                  if (tname.contains('phone') ||
                      tname.contains('mobile') ||
                      tname.contains('whatsapp') ||
                      tname.contains('number') ||
                      tname.contains('tel'))
                    txtBizKey = 'phone';
                  else if (tname.contains('email') || tname.contains('mail'))
                    txtBizKey = 'email';
                  else if (tname.contains('website') ||
                      tname.contains('web') ||
                      tname.contains('url'))
                    txtBizKey = 'website';
                  else if (tname.contains('address') ||
                      tname.contains('location'))
                    txtBizKey = 'address';
                }
                if (txtBizKey == null || txtBizKey.isEmpty) continue;

                double tx = safeDouble((txtLayer['x'] ?? 0) as num);
                double ty = safeDouble((txtLayer['y'] ?? 0) as num);
                double tw = safeDouble(
                  (txtLayer['w'] ?? txtLayer['width'] ?? 0) as num,
                );
                double th = safeDouble(
                  (txtLayer['h'] ?? txtLayer['height'] ?? 0) as num,
                );
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
                final String tname =
                    (bestTextLayer['name'] ?? bestTextLayer['id'] ?? '')
                        .toString()
                        .toLowerCase();
                if (tname.contains('phone') ||
                    tname.contains('mobile') ||
                    tname.contains('whatsapp') ||
                    tname.contains('number') ||
                    tname.contains('tel'))
                  bizKey = 'phone';
                else if (tname.contains('email') || tname.contains('mail'))
                  bizKey = 'email';
                else if (tname.contains('website') ||
                    tname.contains('web') ||
                    tname.contains('url'))
                  bizKey = 'website';
                else if (tname.contains('address') ||
                    tname.contains('location'))
                  bizKey = 'address';
              }
              if (bizKey.isNotEmpty) {
                imgLayer['_businessKey'] = bizKey;
                debugPrint(
                  '[ICON_AUTO_DETECT] Coupled icon "${imgLayer['name']}" to text "${bestTextLayer['name']}" (key=$bizKey, dist=${bestDist.toInt()})',
                );
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
      final String layerName = (layer['name'] ?? layer['id'] ?? '')
          .toString()
          .toLowerCase();
      final bool isBg =
          layer['is_background'] == true ||
          (layer['is_background'] == null &&
              [
                'image1',
                'main_image',
                'bg',
                'background',
                '_frame_bg',
              ].contains(layerName));

      if (!isBg &&
          (layer['type'] == 'image' ||
              layer['type'] == 'rect' ||
              layer['type'] == 'shape')) {
        bool isContactIcon = [
          'phone',
          'email',
          'website',
          'address',
          'social',
        ].any((e) => layerName.contains(e));
        if (!isContactIcon || layer['type'] != 'image') {
          double rawW = safeDouble((layer['w'] ?? layer['width'] ?? 0) as num);
          double rawH = safeDouble((layer['h'] ?? layer['height'] ?? 0) as num);
          bool isShapeMarked = layer['is_shape'] == true;
          if (layer['type'] != 'image' ||
              rawW > 200 ||
              rawH > 200 ||
              isShapeMarked) {
            if (rawW > 20 && rawH > 10) {
              shapeLayers.add({
                'name': layerName,
                'id': layer['id']?.toString() ?? '',
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

  void updateLayerBounds(
    String layerName,
    double x,
    double y,
    double w,
    double h,
    double angle,
  ) {
    final layers = templateConfig['layers'] as List<dynamic>?;
    if (layers == null) return;

    for (var layer in layers) {
      if (_matchesLayerReference(layer, layerName)) {
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

    if (_renderVersion() >= 10) {
      for (final layer in layers) {
        if (_matchesLayerReference(layer, layerName)) {
          layer['opacity'] = isVisible ? 1.0 : 0.0;
          layerUpdateTrigger.value++;
          templateConfig.refresh();
          _pushHistory();
          return;
        }
      }
      return;
    }

    final targets = layerName
        .split(',')
        .map((e) => e.trim().toLowerCase())
        .toList();

    for (var layer in layers) {
      final String rawName = (layer['name'] ?? layer['id'])
          .toString()
          .toLowerCase()
          .trim();
      final String nameWithoutSpaces = rawName.replaceAll(
        RegExp(r'[\s\-_]'),
        '',
      );

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
        final String bizKey = layer['_businessKey']
            .toString()
            .toLowerCase()
            .trim();
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

    final targets = layerName
        .split(',')
        .map((e) => e.trim().toLowerCase())
        .toList();

    for (var layer in layers) {
      final String rawName = (layer['name'] ?? layer['id'])
          .toString()
          .toLowerCase()
          .trim();
      final String nameWithoutSpaces = rawName.replaceAll(
        RegExp(r'[\s\-_]'),
        '',
      );

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
        final String bizKey = layer['_businessKey']
            .toString()
            .toLowerCase()
            .trim();
        for (String target in targets) {
          final t = target.replaceAll(RegExp(r'[\s\-_]'), '');
          if (bizKey == t || t.contains(bizKey) || bizKey.contains(t)) {
            matches = true;
            debugPrint(
              '[VISIBLE_CHECK] "$rawName" MATCHED via bizKey="$bizKey" target="$t" opacity=${layer['opacity']}',
            );
            break;
          }
        }
      }

      if (matches) {
        final opacity = layer['opacity'];
        final result = (opacity ?? 1.0) is num
            ? (opacity ?? 1.0 as num) > 0.0
            : true;
        debugPrint(
          '[VISIBLE_CHECK] "$rawName" → opacity=$opacity result=$result (targets=$targets)',
        );
        return result;
      }
    }
    debugPrint('[VISIBLE_CHECK] NO MATCH for targets=$targets');
    return false;
  }

  void updateLayerProperty(String layerName, String property, dynamic value) {
    debugPrint(
      '[DIAGNOSIS_CANVAS] --------------------------------------------',
    );
    debugPrint('[DIAGNOSIS_CANVAS] updateLayerProperty CALLED');
    debugPrint(
      '[DIAGNOSIS_CANVAS] Timestamp: ${DateTime.now().millisecondsSinceEpoch}',
    );
    debugPrint('[DIAGNOSIS_CANVAS] Layer ID: $layerName');
    debugPrint('[DIAGNOSIS_CANVAS] Property: $property');
    debugPrint('[DIAGNOSIS_CANVAS] Value: $value');
    debugPrint('[DIAGNOSIS_CANVAS] Stack Trace:');
    debugPrint(StackTrace.current.toString());
    debugPrint(
      '[DIAGNOSIS_CANVAS] --------------------------------------------',
    );

    final layers = templateConfig['layers'] as List<dynamic>?;
    if (layers == null) return;

    final beforeLayerIds = layers
        .map((l) => (l['id'] ?? l['name']).toString())
        .toList();
    debugPrint('[DIAGNOSIS_CANVAS] Before modifying templateConfig:');
    debugPrint('[DIAGNOSIS_CANVAS] Current Layer Count: ${layers.length}');
    debugPrint('[DIAGNOSIS_CANVAS] Layer IDs: $beforeLayerIds');
    debugPrint(
      '[DIAGNOSIS_CANVAS] --------------------------------------------',
    );

    if (layerName == '_frame_bg' && property == 'src') {
      // Clean up old frame layers except _frame_bg (which we are updating/adding)
      layers.removeWhere(
        (l) =>
            (l['_is_frame_layer'] == true || l['_isFrameLayer'] == true) &&
            (l['name'] ?? l['id']).toString() != '_frame_bg',
      );
    }

    bool found = false;
    for (var layer in layers) {
      if (_matchesLayerReference(layer, layerName)) {
        layer[property] = value;
        found = true;
        break;
      }
    }

    // Inject _frame_bg if missing and we are trying to update its src (simple PNG frame swap)
    if (!found && layerName == '_frame_bg' && property == 'src') {
      final double canvasW = safeDouble(
        templateConfig['info']?['width'] ?? templateConfig['width'] ?? 1080,
      );
      final double canvasH = safeDouble(
        templateConfig['info']?['height'] ?? templateConfig['height'] ?? 1080,
      );

      int maxZIndex = 0;
      for (var l in layers) {
        int z = (l['z_index'] ?? 0) is int
            ? (l['z_index'] ?? 0)
            : ((l['z_index'] ?? 0) as num).toInt();
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

    final afterLayerIds = layers
        .map((l) => (l['id'] ?? l['name']).toString())
        .toList();
    debugPrint('[DIAGNOSIS_CANVAS] After modifying templateConfig:');
    debugPrint('[DIAGNOSIS_CANVAS] Updated Layer Count: ${layers.length}');
    debugPrint('[DIAGNOSIS_CANVAS] Layer IDs: $afterLayerIds');
    debugPrint(
      '[DIAGNOSIS_CANVAS] --------------------------------------------',
    );

    templateConfig.refresh();
    _pushHistory();
  }

  void updateLayerProperties(
    String layerName,
    Map<String, dynamic> properties,
  ) {
    final layers = templateConfig['layers'] as List<dynamic>?;
    if (layers == null) return;

    for (var layer in layers) {
      if (_matchesLayerReference(layer, layerName)) {
        properties.forEach((key, value) {
          layer[key] = value;
        });
        break;
      }
    }

    templateConfig.refresh();
    _pushHistory();
  }

  /// Applies a deliberate user color selection to every color field consumed
  /// by text and icon renderers, while making it the new dynamic-color source.
  void setLayerColor(String layerName, String color) {
    updateLayerProperties(layerName, {
      'color': color,
      'font_color': color,
      'tint_color': color,
      'original_color': color,
      '_resolved_color': color,
    });
  }

  void deleteLayer(String layerName) {
    final layers = templateConfig['layers'] as List<dynamic>?;
    if (layers == null) return;

    layers.removeWhere((layer) => _matchesLayerReference(layer, layerName));
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

    if (_renderVersion() >= 10 &&
        (newLayer['id'] == null || newLayer['id'].toString().trim().isEmpty)) {
      newLayer['id'] = 'v10_user_${DateTime.now().microsecondsSinceEpoch}';
    }
    layers!.add(newLayer);
    templateConfig.refresh();
    _pushHistory();
  }

  void moveLayer(String layerName, int newIndex) {
    final layers = templateConfig['layers'] as List<dynamic>?;
    if (layers == null) return;

    int oldIndex = layers.indexWhere(
      (l) => _matchesLayerReference(l, layerName),
    );
    if (oldIndex != -1) {
      final layer = layers.removeAt(oldIndex);
      if (newIndex > layers.length) newIndex = layers.length;
      if (newIndex < 0) newIndex = 0;
      layers.insert(newIndex, layer);

      templateConfig.refresh();
      _pushHistory();
    }
  }

  int _renderVersion() {
    final value = templateConfig['render_version'];
    if (value is num) return value.toInt();
    return int.tryParse(value?.toString() ?? '') ?? 1;
  }

  @visibleForTesting
  static Map<String, dynamic>? parseV10BusinessBindingForTest(
    Map<String, dynamic> layer,
  ) {
    return _parseV10BusinessBinding(layer);
  }

  static Map<String, dynamic>? _parseV10BusinessBinding(
    Map<String, dynamic> layer,
  ) {
    const supportedFields = <String>{
      'name',
      'phone',
      'email',
      'website',
      'address',
    };
    const aliases = <String, String>{
      'mobile': 'phone',
      'mobile_number': 'phone',
      'phone_number': 'phone',
      'mail': 'email',
      'email_id': 'email',
      'web': 'website',
      'url': 'website',
      'location': 'address',
    };

    String? explicitField = layer['business_field']?.toString();
    int? explicitIndex = int.tryParse(
      layer['business_field_index']?.toString() ?? '',
    );
    dynamic placeholder = layer['placeholder'];
    if ((explicitField == null || explicitField.trim().isEmpty) &&
        placeholder is Map) {
      explicitField =
          placeholder['field']?.toString() ??
          placeholder['field_type']?.toString();
      explicitIndex ??= int.tryParse(
        placeholder['field_index']?.toString() ?? '',
      );
    }

    final candidates = <String>[
      if (explicitField != null) explicitField,
      layer['placeholder_key']?.toString() ?? '',
      layer['placeholderKey']?.toString() ?? '',
      layer['ai_field']?.toString() ?? '',
      layer['name']?.toString() ?? '',
    ];

    for (final rawCandidate in candidates) {
      String candidate = rawCandidate
          .trim()
          .toLowerCase()
          .replaceAll(RegExp(r'[\s-]+'), '_')
          .replaceFirst(RegExp(r'^business_'), '');
      if (candidate.isEmpty) continue;
      final match = RegExp(r'^(.*?)(?:_(\d+))?$').firstMatch(candidate);
      if (match == null) continue;
      String field = aliases[match.group(1)] ?? match.group(1)!;
      if (!supportedFields.contains(field)) continue;
      final int parsedIndex =
          explicitIndex ?? ((int.tryParse(match.group(2) ?? '1') ?? 1) - 1);
      final int index = field == 'name'
          ? 0
          : (parsedIndex < 0 ? 0 : (parsedIndex > 999 ? 999 : parsedIndex));
      return <String, dynamic>{
        'field': field,
        'index': index,
        'key': field == 'name' ? 'name' : '${field}_${index + 1}',
      };
    }
    return null;
  }

  List<String> _visibleBusinessValues(HomeController homeCtrl, String field) {
    String primary = '';
    List<String> extras = const <String>[];
    String hiddenKey = '';
    switch (field) {
      case 'phone':
        primary = homeCtrl.businessPhone.value;
        extras = homeCtrl.extraPhones;
        hiddenKey = 'mobile_numbers';
        break;
      case 'email':
        primary = homeCtrl.businessEmail.value;
        extras = homeCtrl.extraEmails;
        hiddenKey = 'emails';
        break;
      case 'website':
        primary = homeCtrl.businessWebsite.value;
        extras = homeCtrl.extraWebsites;
        hiddenKey = 'websites';
        break;
      case 'address':
        primary = homeCtrl.businessAddress.value;
        extras = homeCtrl.extraAddresses;
        hiddenKey = 'addresses';
        break;
    }

    final hiddenRaw = homeCtrl.hiddenFrameFields[hiddenKey];
    final hidden = hiddenRaw is List
        ? hiddenRaw.map((value) => value.toString().trim()).toSet()
        : <String>{};
    return <String>[primary, ...extras]
        .map((value) => value.trim())
        .where((value) => value.isNotEmpty && !hidden.contains(value))
        .toList(growable: false);
  }

  String _businessValueAt(HomeController homeCtrl, String field, int index) {
    if (field == 'name') return homeCtrl.businessName.value;
    final values = _visibleBusinessValues(homeCtrl, field);
    if (index < 0 || index >= values.length) return '';
    final value = values[index];
    return field == 'phone' ? value.replaceAll(' ', '\u00A0') : value;
  }

  bool _matchesLayerReference(dynamic rawLayer, String reference) {
    if (rawLayer is! Map) return false;
    // V10 assigns a UUID to every editable layer.  Names are presentation
    // labels and may repeat, so using one cannot be allowed to update/delete
    // a different icon.
    if (_renderVersion() >= 10) {
      return rawLayer['id']?.toString() == reference;
    }
    return (rawLayer['name'] ?? rawLayer['id']).toString() == reference;
  }

  void _ensureV10LayerIds(dynamic rawLayers) {
    if (_renderVersion() < 10 || rawLayers is! List) return;
    final Set<String> used = <String>{};
    for (int index = 0; index < rawLayers.length; index++) {
      final layer = rawLayers[index];
      if (layer is! Map) continue;
      final String existing = layer['id']?.toString().trim() ?? '';
      if (existing.isNotEmpty && !used.contains(existing)) {
        used.add(existing);
        continue;
      }
      final String safeName = (layer['name'] ?? 'layer').toString().replaceAll(
        RegExp(r'[^a-zA-Z0-9]+'),
        '_',
      );
      String id = 'v10_runtime_${index}_$safeName';
      int suffix = 1;
      while (used.contains(id)) {
        id = 'v10_runtime_${index}_${safeName}_$suffix';
        suffix++;
      }
      layer['id'] = id;
      used.add(id);
    }
  }

  // --- API INTEGRATIONS ---

  Future<Map<String, dynamic>?> generateAIText(
    String prompt,
    String language, {
    Map<String, dynamic>? product,
  }) async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final userId = prefs.getString('userId') ?? '';

      // Collect text layers
      final List<Map<String, dynamic>> canvasLayers = [];
      final layers = templateConfig['layers'] as List<dynamic>? ?? [];

      for (var rawLayer in layers) {
        final layer = Map<String, dynamic>.from(rawLayer);
        if (layer['type'] == 'text') {
          final String lname = (layer['name'] ?? layer['id'] ?? '')
              .toString()
              .toLowerCase();

          // Skip if hidden or contact details
          final bool isContactInfo = [
            'phone',
            'email',
            'website',
            'address',
            'call',
            'mobile',
            'contact',
            'whatsapp',
            'tel',
            'mail',
            'web',
            'url',
            'location',
            'facebook',
            'instagram',
            'twitter',
            'youtube',
            'linkedin',
          ].any((key) => lname.contains(key));

          if (!isContactInfo) {
            final String currentText = (layer['text'] ?? '').toString().trim();
            final bool isSkippable = [
              'www.',
              'http',
              '.com',
              '.in',
              '@',
              '+91',
            ].any((p) => currentText.toLowerCase().contains(p));
            final bool isAiProtected =
                layer['ai_protected'] == true ||
                layer['ai_protected'] == '1' ||
                layer['ai_protected'] == 1;

            if (!isSkippable && currentText.isNotEmpty && !isAiProtected) {
              int maxChars = (layer['ai_max_chars'] != null)
                  ? int.tryParse(layer['ai_max_chars'].toString()) ?? 0
                  : (layer['_ai_max_chars'] != null)
                  ? int.tryParse(layer['_ai_max_chars'].toString()) ?? 0
                  : 0;

              if (maxChars <= 0) {
                // Auto-calculate maximum capacity based on physical dimensions
                double w = double.tryParse(layer['w']?.toString() ?? '0') ?? 0;
                double h = double.tryParse(layer['h']?.toString() ?? '0') ?? 0;
                double size =
                    double.tryParse(layer['size']?.toString() ?? '20') ?? 20;

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
                'name':
                    layer['name'] ??
                    layer['id'] ??
                    'text_${canvasLayers.length}',
                'current_text': currentText,
                'max_chars': maxChars,
                'ai_role': layer['ai_role'] ?? layer['_ai_role'] ?? '',
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
        payload['product_name'] =
            (product['_display_name'] ??
                    product['title'] ??
                    product['name'] ??
                    '')
                .toString();
        payload['product_description'] =
            (product['description'] ?? product['short_description'] ?? '')
                .toString();
        payload['product_price'] =
            (product['_display_price'] ?? product['price'] ?? '').toString();
        payload['product_category'] =
            (product['_display_category'] ??
                    product['category_name'] ??
                    product['category'] ??
                    '')
                .toString();
        payload['product_sku'] = (product['sku'] ?? '').toString();
      }

      debugPrint('[AI Generate] Payload: $payload');

      final response = await ApiService.post(
        '/editor/ai-content/generate?userId=$userId',
        payload,
      );

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
    final int loadGeneration = ++_frameLoadGeneration;
    try {
      final int parseStartTime = DateTime.now().millisecondsSinceEpoch;
      NativeEditorController.timelineLoadStart = parseStartTime;

      final currentLayers = templateConfig['layers'] as List<dynamic>? ?? [];
      final curLayerIds = currentLayers
          .map((l) => (l['id'] ?? l['name']).toString())
          .toList();
      final incomingFrameId = (newFrameJson['id'] ?? '').toString();
      final bool isV10Frame = _frameRenderVersion(newFrameJson) >= 10;
      if (isV10Frame) {
        // V10 uses the already-rendered canvas snapshot as its double buffer.
        // A target preview can itself be a blank/partial frame, so it must not
        // replace the old canvas while assets are still decoding.
        isCanvasLoading.value = true;
        frameTransitionPreviewUrl.value = '';
      }
      int incomingLayerCount = 0;
      var incLayers =
          newFrameJson['layers'] ??
          (newFrameJson['config'] is Map
              ? newFrameJson['config']['layers']
              : null);
      if (incLayers is List) incomingLayerCount = incLayers.length;

      debugPrint(
        '[DIAGNOSIS_CANVAS] --------------------------------------------',
      );
      debugPrint('[DIAGNOSIS_CANVAS] LOAD NEW FRAME START');
      debugPrint('[DIAGNOSIS_CANVAS] Timestamp: $parseStartTime');
      debugPrint('[DIAGNOSIS_CANVAS] Incoming Frame ID: $incomingFrameId');
      debugPrint(
        '[DIAGNOSIS_CANVAS] Incoming Layer Count: $incomingLayerCount',
      );
      debugPrint(
        '[DIAGNOSIS_CANVAS] Current Template Layer Count: ${currentLayers.length}',
      );
      debugPrint('[DIAGNOSIS_CANVAS] Current Template Layer IDs: $curLayerIds');
      debugPrint(
        '[DIAGNOSIS_CANVAS] --------------------------------------------',
      );

      dynamic rawNewLayers = newFrameJson['layers'];
      if (rawNewLayers == null && newFrameJson['config'] != null) {
        if (newFrameJson['config'] is Map) {
          rawNewLayers = newFrameJson['config']['layers'];
        } else if (newFrameJson['config'] is String) {
          try {
            rawNewLayers = jsonDecode(newFrameJson['config'])['layers'];
          } catch (e) {}
        }
      }
      if (rawNewLayers == null && newFrameJson['json'] != null) {
        if (newFrameJson['json'] is Map) {
          rawNewLayers = newFrameJson['json']['layers'];
        } else if (newFrameJson['json'] is String) {
          try {
            rawNewLayers = jsonDecode(newFrameJson['json'])['layers'];
          } catch (e) {}
        }
      }

      // 3. Fallback to API if not in config and we have a zip_name
      if (rawNewLayers == null && newFrameJson['zip_name'] != null) {
        debugPrint('[DIAGNOSIS_CANVAS] Future Started (fetchTemplateJson)');
        final int fetchStart = DateTime.now().millisecondsSinceEpoch;
        final fetchedJson = await fetchTemplateJson(newFrameJson['zip_name']);
        final int fetchEnd = DateTime.now().millisecondsSinceEpoch;
        debugPrint('[DIAGNOSIS_CANVAS] Future Finished (fetchTemplateJson)');
        debugPrint('[DIAGNOSIS_CANVAS] Duration: ${fetchEnd - fetchStart}ms');

        if (fetchedJson != null) {
          if (loadGeneration != _frameLoadGeneration) return;
          newFrameJson['config'] =
              fetchedJson; // save it so we don't fetch again unnecessarily
          rawNewLayers = fetchedJson['layers'];
        }
      }

      if (rawNewLayers == null) rawNewLayers = [];

      final newLayers = jsonDecode(jsonEncode(rawNewLayers)) as List<dynamic>;
      final parsedLayerIds = newLayers
          .map((l) => (l['id'] ?? l['name']).toString())
          .toList();
      debugPrint('[DIAGNOSIS_CANVAS] Parsed New Layers');
      debugPrint('[DIAGNOSIS_CANVAS] Count: ${newLayers.length}');
      debugPrint('[DIAGNOSIS_CANVAS] Layer IDs: $parsedLayerIds');

      debugPrint(
        '🔴 [DIAGNOSIS] loadNewFrame newLayers count: ${newLayers.length}',
      );
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

      final type =
          Get.parameters['type'] ??
          templateConfig['type'] ??
          'business_custom_frame';

      final preservedLayers = currentLayers.where((l) {
        if (l['_is_frame_layer'] == true || l['_isFrameLayer'] == true) {
          return false;
        }

        final name = (l['name'] ?? l['id'] ?? '').toString().toLowerCase();
        final bool isBg =
            l['is_background'] == true ||
            (l['is_background'] == null &&
                (name == 'image1' ||
                    name == 'main_image' ||
                    name == 'bg' ||
                    name.contains('background')));
        final bool isUserAdded =
            name.startsWith('new ') ||
            name.startsWith('logo ') ||
            name.startsWith('product ') ||
            name.startsWith('sticker ') ||
            name.startsWith('text ');

        if (type == 'business_custom_frame' || type == 'custom') {
          return true; // Preserve all native layers of the custom post
        }

        return isBg || isUserAdded;
      }).toList();

      final preservedLayerIds = preservedLayers
          .map((l) => (l['id'] ?? l['name']).toString())
          .toList();
      debugPrint('[DIAGNOSIS_CANVAS] Preserved Layers');
      debugPrint('[DIAGNOSIS_CANVAS] Count: ${preservedLayers.length}');
      debugPrint('[DIAGNOSIS_CANVAS] Layer IDs: $preservedLayerIds');

      dynamic configJson = newFrameJson['config'] ?? newFrameJson['json'];
      if (configJson is String) {
        try {
          configJson = jsonDecode(configJson);
        } catch (e) {}
      }

      dynamic templateInfo = templateConfig['info'];
      if (templateInfo is String) {
        try {
          templateInfo = jsonDecode(templateInfo);
        } catch (e) {}
      }
      double canvasW = safeDouble(
        templateInfo?['width'] ?? templateConfig['width'] ?? 1080,
      );
      double canvasH = safeDouble(
        templateInfo?['height'] ?? templateConfig['height'] ?? 1080,
      );

      dynamic frameInfo = newFrameJson['info'];
      if (frameInfo is String) {
        try {
          frameInfo = jsonDecode(frameInfo);
        } catch (e) {}
      }
      dynamic configInfo = configJson?['info'];
      if (configInfo is String) {
        try {
          configInfo = jsonDecode(configInfo);
        } catch (e) {}
      }

      double frameW = safeDouble(
        newFrameJson['width'] ??
            frameInfo?['width'] ??
            configInfo?['width'] ??
            configJson?['width'] ??
            0,
      );
      double frameH = safeDouble(
        newFrameJson['height'] ??
            frameInfo?['height'] ??
            configInfo?['height'] ??
            configJson?['height'] ??
            0,
      );

      if (frameW <= 0 || frameH <= 0) {
        for (var layer in newLayers) {
          double r =
              safeDouble((layer['x'] ?? 0) as num) +
              safeDouble((layer['width'] ?? layer['w'] ?? 0) as num);
          double b =
              safeDouble((layer['y'] ?? 0) as num) +
              safeDouble((layer['height'] ?? layer['h'] ?? 0) as num);
          if (r > frameW) frameW = r;
          if (b > frameH) frameH = b;
        }
      }
      if (frameW <= 0) frameW = 1080;
      if (frameH <= 0) frameH = 1080;

      bool hasBg = newLayers.any((l) {
        String n = (l['name'] ?? l['id'] ?? '').toString().toLowerCase();
        return n == 'bg' ||
            n == '_frame_bg' ||
            n.contains('background') ||
            l['is_background'] == true;
      });

      // FIX: If the frame JSON lacks a background layer, but provides a full_url, auto-create the frame background
      // Only do this for legacy frames (V1-V3) since V4+ frames are fully constructed from component layers.
      final int renderVersion = (newFrameJson['render_version'] is int)
          ? newFrameJson['render_version']
          : safeDouble(newFrameJson['render_version'] ?? 1).toInt();

      if (renderVersion < 4 &&
          !hasBg &&
          newFrameJson['full_url'] != null &&
          newFrameJson['full_url'].toString().isNotEmpty) {
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
            if (parsedInfo['ppi'] != null)
              docPPI = safeDouble(parsedInfo['ppi'] as num);
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
        'phone': 0,
        'email': 0,
        'website': 0,
        'address': 0,
      };
      final int frameRenderVersion = _frameRenderVersion(newFrameJson);

      for (var newLayer in newLayers) {
        double rawW = safeDouble(
          (newLayer['w'] ?? newLayer['width'] ?? 0) as num,
        );
        double rawH = safeDouble(
          (newLayer['h'] ?? newLayer['height'] ?? 0) as num,
        );
        String name = (newLayer['name'] ?? newLayer['id'] ?? '').toString();
        String layerName = name.toLowerCase();

        newLayer['_is_frame_layer'] = true;

        for (String urlField in ['src', '_fallback_src']) {
          if (newLayer[urlField] != null &&
              newLayer[urlField].toString().isNotEmpty) {
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

              String zipName =
                  (newFrameJson['zip_name'] ?? newFrameJson['path'] ?? '')
                      .toString()
                      .replaceAll('/', '')
                      .trim();
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
                newLayer[urlField] =
                    '$frameBaseUrl${srcStr.replaceFirst('../', '')}';
              } else if (srcStr.startsWith('uploads/')) {
                newLayer[urlField] = '$base$srcStr';
              } else {
                newLayer[urlField] = '${frameBaseUrl}skins/$srcStr';
              }
            }

            // Server zip extraction replaces spaces with hyphens.
            // Replace proactively to avoid a 404 delay before the fallback kicks in.
            if (!newLayer[urlField].toString().startsWith('data:')) {
              newLayer[urlField] = newLayer[urlField]
                  .toString()
                  .replaceAll(' ', '-')
                  .replaceAll('%20', '-');
            }
          }
        }

        if ((layerName == '_frame_bg' ||
                layerName == '_frame' ||
                layerName == 'frame') &&
            (rawW <= 0 || rawH <= 0)) {
          rawW = frameW;
          rawH = frameH;
        }

        if (newLayer['x'] != null)
          newLayer['x'] = safeDouble(newLayer['x'] as num) * scaleX;
        if (newLayer['y'] != null)
          newLayer['y'] = safeDouble(newLayer['y'] as num) * scaleY;

        if (newLayer['w'] != null) newLayer['w'] = rawW * scaleX;
        if (newLayer['width'] != null) newLayer['width'] = rawW * scaleX;
        if (newLayer['h'] != null) newLayer['h'] = rawH * scaleY;
        if (newLayer['height'] != null) newLayer['height'] = rawH * scaleY;

        if (newLayer['fontSize'] != null)
          newLayer['fontSize'] =
              safeDouble(newLayer['fontSize'] as num) * ppiScale * scaleY;
        if (newLayer['font_size'] != null)
          newLayer['font_size'] =
              safeDouble(newLayer['font_size'] as num) * ppiScale * scaleY;
        if (newLayer['size'] != null)
          newLayer['size'] =
              safeDouble(newLayer['size'] as num) * ppiScale * scaleY;

        newLayer['_isFrameLayer'] = true;

        String bLow = layerName;
        if (newLayer['type'] == 'text') {
          final Map<String, dynamic>? v10Binding = frameRenderVersion >= 10
              ? _parseV10BusinessBinding(
                  Map<String, dynamic>.from(newLayer as Map),
                )
              : null;
          if (v10Binding != null) {
            newLayer['_businessKey'] = v10Binding['field'];
            newLayer['_businessIndex'] = v10Binding['index'];
            newLayer['business_field'] = v10Binding['field'];
            newLayer['business_field_index'] = v10Binding['index'];
            newLayer['placeholder_key'] = v10Binding['key'];
            newLayer['ai_field'] = v10Binding['key'];
          } else if (bLow.contains('name') || bLow.contains('business_name'))
            newLayer['_businessKey'] = 'name';
          else if (bLow.contains('phone') ||
              bLow.contains('mobile') ||
              bLow.contains('whatsapp') ||
              bLow.contains('number') ||
              bLow.contains('tel'))
            newLayer['_businessKey'] = 'phone';
          else if (bLow.contains('email') || bLow.contains('mail'))
            newLayer['_businessKey'] = 'email';
          else if (bLow.contains('website') ||
              bLow.contains('web') ||
              bLow.contains('url'))
            newLayer['_businessKey'] = 'website';
          else if (bLow.contains('address') || bLow.contains('location'))
            newLayer['_businessKey'] = 'address';
          bool hasValidUserText =
              userTexts.containsKey(name) &&
              userTexts[name] != null &&
              userTexts[name]!.trim().isNotEmpty;

          if (hasValidUserText &&
              v10Binding == null &&
              (name.startsWith('_b_') || newLayer['_businessKey'] != null)) {
            newLayer['text'] = userTexts[name];
          } else if (Get.isRegistered<HomeController>() &&
              newLayer['_businessKey'] != null) {
            final homeCtrl = Get.find<HomeController>();
            if (newLayer['_businessKey'] == 'name') {
              newLayer['text'] = homeCtrl.businessName.value;
            } else if (bizKeyCounter.containsKey(
              newLayer['_businessKey']?.toString(),
            )) {
              final String key = newLayer['_businessKey'].toString();
              final int idx =
                  int.tryParse(v10Binding?['index']?.toString() ?? '') ??
                  bizKeyCounter[key]!;
              bizKeyCounter[key] = idx + 1 > bizKeyCounter[key]!
                  ? idx + 1
                  : bizKeyCounter[key]!;
              final value = _businessValueAt(homeCtrl, key, idx);
              if (value.isNotEmpty) newLayer['text'] = value;
            }
          }
        } else if (newLayer['type'] == 'image') {
          if (newLayer['_businessKey'] == null) {
            if (bLow.contains('phone') ||
                bLow.contains('call') ||
                bLow.contains('mobile') ||
                bLow.contains('contact') ||
                bLow.contains('whatsapp') ||
                bLow.contains('tel') ||
                bLow.contains('ph'))
              newLayer['_businessKey'] = 'phone';
            else if (bLow.contains('email') || bLow.contains('mail'))
              newLayer['_businessKey'] = 'email';
            else if (bLow.contains('website') ||
                bLow.contains('web') ||
                bLow.contains('url'))
              newLayer['_businessKey'] = 'website';
            else if (bLow.contains('address') || bLow.contains('location'))
              newLayer['_businessKey'] = 'address';
            else if (bLow.contains('icon') ||
                bLow.contains('facebook') ||
                bLow.contains('instagram') ||
                bLow.contains('twitter') ||
                bLow.contains('youtube') ||
                bLow.contains('social') ||
                bLow.contains('linkedin'))
              newLayer['_businessKey'] = 'social';
          }
          if (bLow.contains('logo') &&
              !bLow.contains('email') &&
              !bLow.contains('call') &&
              !bLow.contains('phone') &&
              !bLow.contains('web')) {
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

        if (newLayer['type'] == 'image' ||
            newLayer['type'] == 'rect' ||
            newLayer['type'] == 'shape') {
          if (newLayer['is_background'] != true &&
              !(newLayer['is_background'] == null &&
                  [
                    'image1',
                    'main_image',
                    'bg',
                    'background',
                    '_frame_bg',
                  ].contains(layerName))) {
            if (newLayer['type'] != 'image' ||
                ![
                  'phone',
                  'email',
                  'website',
                  'address',
                  'social',
                ].any((e) => layerName.contains(e))) {
              bool isShapeMarked = newLayer['is_shape'] == true;
              if (newLayer['type'] != 'image' ||
                  rawW > 200 ||
                  rawH > 200 ||
                  isShapeMarked) {
                double px = safeDouble((newLayer['x'] ?? 0) as num);
                double py = safeDouble((newLayer['y'] ?? 0) as num);
                double pw = safeDouble(
                  (newLayer['w'] ?? newLayer['width'] ?? 0) as num,
                );
                double ph = safeDouble(
                  (newLayer['h'] ?? newLayer['height'] ?? 0) as num,
                );
                if (pw > 20 && ph > 10) {
                  shapeLayers.add({
                    'name': layerName,
                    'id': newLayer['id']?.toString() ?? '',
                    'x': px,
                    'y': py,
                    'w': pw,
                    'h': ph,
                    'fill':
                        newLayer['fill'] ??
                        newLayer['tint_color'] ??
                        newLayer['color'],
                    'src': newLayer['src'],
                    'z_index': safeDouble(
                      (newLayer['z_index'] ?? 0) as num,
                    ).toInt(),
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
        debugPrint(
          '║ src=${newLayer['src']?.toString().substring(0, (newLayer['src']?.toString().length ?? 0) > 60 ? 60 : (newLayer['src']?.toString().length ?? 0))}...',
        );
        debugPrint('╚══════════════════════════════════════════════════════');
      }

      // 1. Find the maximum z_index from native/preserved layers
      int maxNativeZIndex = 0;
      for (var l in preservedLayers) {
        int z = (l['z_index'] ?? 0) is int
            ? (l['z_index'] ?? 0)
            : ((l['z_index'] ?? 0) as num).toInt();
        if (z > maxNativeZIndex) maxNativeZIndex = z;
      }

      // 2. Boost all frame layer z_index values so they render ON TOP of native layers
      for (var newLayer in newLayers) {
        int frameZ = (newLayer['z_index'] ?? 0) is int
            ? (newLayer['z_index'] ?? 0)
            : ((newLayer['z_index'] ?? 0) as num).toInt();
        newLayer['z_index'] = maxNativeZIndex + frameZ + 1;
      }

      // `shapeLayers` is a detached runtime copy used only by the dynamic
      // colour resolver. It must receive the same z-index boost as its source
      // frame layers; otherwise a boosted text/icon is compared against an
      // unboosted shape and can choose the wrong backdrop.
      for (final shape in shapeLayers) {
        shape['z_index'] = maxNativeZIndex + _layerZIndex(shape) + 1;
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
            if (l['_is_frame_layer'] == true)
              return false; // Don't touch other frame layers
            final bk = l['_businessKey'];
            if (bk != null && frameBusinessKeys.contains(bk.toString())) {
              debugPrint(
                '[FRAME] Removing native layer with businessKey=$bk to avoid duplicate',
              );
              return true;
            }
            return false;
          });
        }
      }

      debugPrint(
        '[DIAGNOSIS_CANVAS] --------------------------------------------',
      );
      debugPrint('[DIAGNOSIS_CANVAS] MERGE START');
      debugPrint(
        '[DIAGNOSIS_CANVAS] Old Template Layer Count: ${currentLayers.length}',
      );
      debugPrint(
        '[DIAGNOSIS_CANVAS] New Frame Layer Count: ${newLayers.length}',
      );
      debugPrint(
        '[DIAGNOSIS_CANVAS] Preserved Layer Count: ${preservedLayers.length}',
      );
      debugPrint(
        '[DIAGNOSIS_CANVAS] --------------------------------------------',
      );

      // 4. Add frame layers after preserved layers
      for (var newLayer in newLayers) {
        preservedLayers.add(newLayer);
      }
      NativeEditorController.timelineMergeComplete =
          DateTime.now().millisecondsSinceEpoch;

      // 5. Deduplicate: within the same source (frame vs native), remove duplicates by name.
      //    Frame layers should NOT overwrite native layers with different purposes even if same name.
      final seenNames = <String>{};
      final uniqueLayers = <Map<String, dynamic>>[];
      for (var layer in preservedLayers.reversed) {
        final name = (layer['name'] ?? layer['id'] ?? '').toString();
        if (name.isNotEmpty) {
          String dedupeKey = layer['_is_frame_layer'] == true
              ? 'frame_$name'
              : 'native_$name';
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

      final finalLayerIds = finalLayersList
          .map((l) => (l['id'] ?? l['name']).toString())
          .toList();
      debugPrint(
        '[DIAGNOSIS_CANVAS] --------------------------------------------',
      );
      debugPrint('[DIAGNOSIS_CANVAS] FINAL LAYER LIST');
      debugPrint('[DIAGNOSIS_CANVAS] Count: ${finalLayersList.length}');
      debugPrint('[DIAGNOSIS_CANVAS] Layer IDs in exact order: $finalLayerIds');
      for (var layer in finalLayersList) {
        debugPrint(
          '[DIAGNOSIS_CANVAS] Layer: id=${layer['id'] ?? ''}, name=${layer['name'] ?? ''}, type=${layer['type'] ?? ''}',
        );
      }
      debugPrint(
        '[DIAGNOSIS_CANVAS] --------------------------------------------',
      );

      final int precacheStartTime = DateTime.now().millisecondsSinceEpoch;
      final frameLayersForPrecache = finalLayersList
          .where(
            (l) => l['_is_frame_layer'] == true || l['_isFrameLayer'] == true,
          )
          .toList();
      NativeEditorController.timelinePrecacheStart = precacheStartTime;
      debugPrint(
        '[DIAGNOSIS_CANVAS] --------------------------------------------',
      );
      debugPrint('[DIAGNOSIS_CANVAS] PRECACHE START');
      debugPrint('[DIAGNOSIS_CANVAS] Timestamp: $precacheStartTime');
      debugPrint(
        '[DIAGNOSIS_CANVAS] Frame Layer Count: ${frameLayersForPrecache.length}',
      );
      debugPrint(
        '[DIAGNOSIS_CANVAS] --------------------------------------------',
      );

      // PRECACHE ALL IMAGES BEFORE RENDERING
      debugPrint('[DIAGNOSIS_CANVAS] Future Started (_precacheFrameImages)');
      final int precacheStart = DateTime.now().millisecondsSinceEpoch;
      await _precacheFrameImages(finalLayersList);
      // A second thumbnail tap can arrive while the first frame downloads.
      // Only the latest request may replace the canvas.
      if (loadGeneration != _frameLoadGeneration) return;
      final int precacheEnd = DateTime.now().millisecondsSinceEpoch;
      debugPrint('[DIAGNOSIS_CANVAS] Future Finished (_precacheFrameImages)');
      debugPrint(
        '[DIAGNOSIS_CANVAS] Duration: ${precacheEnd - precacheStart}ms',
      );

      final int precacheEndTime = DateTime.now().millisecondsSinceEpoch;
      NativeEditorController.timelinePrecacheEnd = precacheEndTime;
      debugPrint(
        '[DIAGNOSIS_CANVAS] --------------------------------------------',
      );
      debugPrint('[DIAGNOSIS_CANVAS] PRECACHE END');
      debugPrint('[DIAGNOSIS_CANVAS] Timestamp: $precacheEndTime');
      debugPrint(
        '[DIAGNOSIS_CANVAS] Duration: ${precacheEndTime - precacheStartTime}ms',
      );
      debugPrint(
        '[DIAGNOSIS_CANVAS] --------------------------------------------',
      );

      final int oldLayersCount =
          (templateConfig['layers'] as List?)?.length ?? 0;
      debugPrint(
        '[DIAGNOSIS_CANVAS] --------------------------------------------',
      );
      debugPrint('[DIAGNOSIS_CANVAS] WRITING TEMPLATECONFIG');
      debugPrint('[DIAGNOSIS_CANVAS] Old Count: $oldLayersCount');
      debugPrint('[DIAGNOSIS_CANVAS] New Count: ${finalLayersList.length}');
      debugPrint(
        '[DIAGNOSIS_CANVAS] --------------------------------------------',
      );

      if (captureCanvasCallback != null) {
        debugPrint('[SNAPSHOT] Triggering canvas capture before refresh');
        await captureCanvasCallback!();
        // A tap can arrive between layout and paint. Retry after the current
        // frame so V10 always has an old-canvas buffer before replacing layers.
        if (isV10Frame &&
            NativeEditorController.transitionSnapshot.value == null) {
          await WidgetsBinding.instance.endOfFrame;
          await captureCanvasCallback!();
        }
      }

      if (loadGeneration != _frameLoadGeneration) return;

      templateConfig['layers'] = finalLayersList;
      NativeEditorController.timelineConfigUpdated =
          DateTime.now().millisecondsSinceEpoch;

      final currentList = templateConfig['layers'] as List? ?? [];
      final currentListIds = currentList
          .map((l) => (l['id'] ?? l['name']).toString())
          .toList();
      debugPrint(
        '[DIAGNOSIS_CANVAS] --------------------------------------------',
      );
      debugPrint('[DIAGNOSIS_CANVAS] TEMPLATECONFIG UPDATED');
      debugPrint('[DIAGNOSIS_CANVAS] Current Count: ${currentList.length}');
      debugPrint('[DIAGNOSIS_CANVAS] Current Layer IDs: $currentListIds');
      debugPrint(
        '[DIAGNOSIS_CANVAS] --------------------------------------------',
      );

      int newRenderVersion = 1;
      if (newFrameJson['render_version'] != null) {
        newRenderVersion = (newFrameJson['render_version'] is int)
            ? newFrameJson['render_version']
            : safeDouble(newFrameJson['render_version'] as num).toInt();
      } else if (configJson != null && configJson['render_version'] != null) {
        newRenderVersion = (configJson['render_version'] is int)
            ? configJson['render_version']
            : safeDouble(configJson['render_version'] as num).toInt();
      } else if (newFrameJson['info'] != null &&
          newFrameJson['info'] is Map &&
          newFrameJson['info']['render_version'] != null) {
        newRenderVersion = (newFrameJson['info']['render_version'] is int)
            ? newFrameJson['info']['render_version']
            : safeDouble(newFrameJson['info']['render_version'] as num).toInt();
      }
      if (newRenderVersion > (templateConfig['render_version'] as int? ?? 1)) {
        templateConfig['render_version'] = newRenderVersion;
      } else if (newFrameJson['render_version'] != null ||
          (configJson != null && configJson['render_version'] != null)) {
        templateConfig['render_version'] = newRenderVersion;
      }
      _ensureV10LayerIds(templateConfig['layers']);

      debugPrint(
        '[DIAGNOSIS_CANVAS] --------------------------------------------',
      );
      debugPrint('[DIAGNOSIS_CANVAS] REFRESH START');
      debugPrint(
        '[DIAGNOSIS_CANVAS] --------------------------------------------',
      );

      templateConfig.refresh();
      if (isV10Frame) {
        _pendingV10TransitionGeneration = ++frameTransitionGeneration.value;
      }
      NativeEditorController.timelineRefreshComplete =
          DateTime.now().millisecondsSinceEpoch;

      final int refreshCompleteTime = DateTime.now().millisecondsSinceEpoch;
      final int refreshLayersCount =
          (templateConfig['layers'] as List?)?.length ?? 0;
      debugPrint(
        '[DIAGNOSIS_CANVAS] --------------------------------------------',
      );
      debugPrint('[DIAGNOSIS_CANVAS] REFRESH COMPLETE');
      debugPrint('[DIAGNOSIS_CANVAS] Timestamp: $refreshCompleteTime');
      debugPrint('[DIAGNOSIS_CANVAS] Current Layer Count: $refreshLayersCount');
      debugPrint(
        '[DIAGNOSIS_CANVAS] --------------------------------------------',
      );

      WidgetsBinding.instance.addPostFrameCallback((_) {
        final int renderCompleteTime = DateTime.now().millisecondsSinceEpoch;
        final String frameIdStr = (newFrameJson['id'] ?? '').toString();
        debugPrint('[DIAGNOSIS_CANVAS] Render Complete');
        debugPrint('[DIAGNOSIS_CANVAS] Frame ID: $frameIdStr');
        debugPrint('[DIAGNOSIS_CANVAS] Timestamp: $renderCompleteTime');
        debugPrint(
          '[DIAGNOSIS_CANVAS] Total Time Since Tap: ${renderCompleteTime - parseStartTime}ms',
        );

        if (!isV10Frame) {
          Future.delayed(const Duration(milliseconds: 100), () {
            NativeEditorController.transitionSnapshot.value = null;
            debugPrint('[SNAPSHOT] transitionSnapshot cleared');
          });
        }

        final int finalCount = (templateConfig['layers'] as List?)?.length ?? 0;

        debugPrint(
          '[DIAGNOSIS_CANVAS] --------------------------------------------',
        );
        debugPrint('[DIAGNOSIS_CANVAS] FRAME SWITCH SUMMARY');
        debugPrint(
          '[DIAGNOSIS_CANVAS] Tap Time: ${NativeEditorController.timelineTapTime}',
        );
        debugPrint(
          '[DIAGNOSIS_CANVAS] loadNewFrame Start: ${NativeEditorController.timelineLoadStart}',
        );
        debugPrint(
          '[DIAGNOSIS_CANVAS] Precache Start: ${NativeEditorController.timelinePrecacheStart}',
        );
        debugPrint(
          '[DIAGNOSIS_CANVAS] Precache End: ${NativeEditorController.timelinePrecacheEnd}',
        );
        debugPrint(
          '[DIAGNOSIS_CANVAS] Merge Complete: ${NativeEditorController.timelineMergeComplete}',
        );
        debugPrint(
          '[DIAGNOSIS_CANVAS] templateConfig Updated: ${NativeEditorController.timelineConfigUpdated}',
        );
        debugPrint(
          '[DIAGNOSIS_CANVAS] Refresh Complete: ${NativeEditorController.timelineRefreshComplete}',
        );
        debugPrint(
          '[DIAGNOSIS_CANVAS] Obx Rebuild: ${NativeEditorController.timelineObxRebuild}',
        );
        debugPrint(
          '[DIAGNOSIS_CANVAS] Canvas Build: ${NativeEditorController.timelineCanvasBuild}',
        );
        debugPrint(
          '[DIAGNOSIS_CANVAS] InteractiveLayer Build: ${NativeEditorController.timelineInteractiveLayerBuild}',
        );
        debugPrint(
          '[DIAGNOSIS_CANVAS] CachedNetworkImage placeholder called?: ${NativeEditorController.timelinePlaceholderCalled}',
        );
        debugPrint(
          '[DIAGNOSIS_CANVAS] CachedNetworkImage imageBuilder called?: ${NativeEditorController.timelineImageBuilderCalled}',
        );
        debugPrint('[DIAGNOSIS_CANVAS] Final Layer Count: $finalCount');
        debugPrint(
          '[DIAGNOSIS_CANVAS] --------------------------------------------',
        );

        Future.delayed(const Duration(milliseconds: 300), () {
          loadingFrameId.value = '';
        });
      });

      _pushHistory();

      // 2. Asynchronous Brightness Check
      _asyncApplyBrightness(
        newFrameJson,
        newLayers,
        preservedLayers,
        shapeLayers,
      );
    } catch (e, stack) {
      // A failed V10 load must never leave the previous-frame overlay locked.
      if (loadGeneration == _frameLoadGeneration && isCanvasLoading.value) {
        isCanvasLoading.value = false;
        frameTransitionPreviewUrl.value = '';
        NativeEditorController.transitionSnapshot.value = null;
        loadingFrameId.value = '';
      }
      debugPrint('[LOAD_FRAME] Error: $e\n$stack');
    }
  }

  /// Called by the V10 canvas only after the replacement frame has painted.
  /// Older render versions retain their existing fixed-delay transition path.
  void completeV10FrameTransition(int generation) {
    if (!isCanvasLoading.value ||
        generation == 0 ||
        generation != _pendingV10TransitionGeneration) {
      return;
    }

    isCanvasLoading.value = false;
    frameTransitionPreviewUrl.value = '';
    NativeEditorController.transitionSnapshot.value = null;
    loadingFrameId.value = '';
    debugPrint('[SNAPSHOT] V10 transition completed after canvas paint');
  }

  int _frameRenderVersion(Map<String, dynamic> frame) {
    final dynamic direct = frame['render_version'];
    dynamic config = frame['config'] ?? frame['json'];
    if (config is String && config.trim().isNotEmpty) {
      try {
        config = jsonDecode(config);
      } catch (_) {}
    }
    final dynamic nested = config is Map ? config['render_version'] : null;
    final dynamic info = frame['info'];
    final dynamic infoVersion = info is Map ? info['render_version'] : null;
    return safeDouble(
      direct ?? nested ?? infoVersion ?? templateConfig['render_version'] ?? 1,
    ).toInt();
  }

  Future<void> _engineDecodeImage(String url) {
    final completer = Completer<void>();
    ImageProvider provider;
    if (kIsWeb) {
      provider = NetworkImage(url);
    } else {
      provider = CachedNetworkImageProvider(url);
    }

    final ImageStream stream = provider.resolve(const ImageConfiguration());
    late ImageStreamListener listener;
    listener = ImageStreamListener(
      (ImageInfo info, bool syncCall) {
        stream.removeListener(listener);
        completer.complete();
      },
      onError: (dynamic error, StackTrace? stackTrace) {
        stream.removeListener(listener);
        debugPrint('[ENGINE_DECODE] Error decoding $url: $error');
        completer.complete(); // complete on error to avoid hanging
      },
    );
    stream.addListener(listener);
    return completer.future;
  }

  Future<void> _precacheFrameImages(List<dynamic> layersList) async {
    final futures = <Future<void>>[];
    for (var layer in layersList) {
      if (layer['type'] == 'image' && layer['src'] != null) {
        String src = layer['src'].toString();
        if (!src.startsWith('http')) {
          String baseUrl = templateBaseUrl.isNotEmpty
              ? templateBaseUrl
              : AppConfig.baseUrl.replaceAll('/123456', '') + '/public';
          if (src.startsWith('../')) {
            src = '$baseUrl/${src.replaceFirst('../', '')}';
          } else if (src.startsWith('uploads/')) {
            src = '$baseUrl/$src';
          } else {
            src = '$baseUrl/skins/$src';
          }
        }

        futures.add(
          Future(() async {
            try {
              // 1. Ensure file is on disk (should be instant thanks to background pre-cacher)
              final fileInfo = await DefaultCacheManager().getFileFromCache(
                src,
              );
              if (fileInfo == null) {
                await DefaultCacheManager().downloadFile(src);
              }
              // 2. FORCE decode into RAM so when EditorCanvasWidget mounts, it paints synchronously!
              // No more white flash.
              await _engineDecodeImage(src);
            } catch (e) {
              debugPrint('[PRECACHE] Failed for $src: $e');
            }
          }),
        );
      }
    }
    if (futures.isNotEmpty) {
      await Future.wait(futures);
    }
  }

  Future<void> _asyncApplyBrightness(
    Map<String, dynamic> newFrameJson,
    List<dynamic> newLayers,
    List<dynamic> preservedLayers,
    List<Map<String, dynamic>> shapeLayers,
  ) async {
    try {
      final int renderVersion = _renderVersion();
      // Process shape images to find their actual brightness
      for (var shape in shapeLayers) {
        if (shape['fill'] == null &&
            shape['src'] != null &&
            shape['src'].toString().isNotEmpty) {
          String sUrl = shape['src'].toString();
          // Normalize URL
          if (!sUrl.startsWith('http')) {
            String baseUrl = templateBaseUrl.isNotEmpty
                ? templateBaseUrl
                : AppConfig.baseUrl.replaceAll('/123456', '') + '/public';
            if (sUrl.startsWith('../')) {
              sUrl = '$baseUrl/${sUrl.replaceFirst('../', '')}';
            } else if (sUrl.startsWith('uploads/')) {
              sUrl = '$baseUrl/$sUrl';
            } else {
              sUrl = '$baseUrl/skins/$sUrl';
            }
          }

          bool shapeIsDark = false;
          if (shape['tint_color'] != null &&
              shape['tint_color'].toString().isNotEmpty) {
            final Color tint = _parseColor(
              shape['tint_color'].toString(),
              fallback: Colors.white,
            );
            final double luminance =
                (0.299 * tint.red + 0.587 * tint.green + 0.114 * tint.blue);
            shapeIsDark = luminance < 160;
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
                    int r = data[i],
                        g = data[i + 1],
                        b = data[i + 2],
                        a = data[i + 3];
                    if (a > 30) {
                      // Only sample non-transparent pixels
                      totalLuminance += (0.299 * r + 0.587 * g + 0.114 * b);
                      sampleCount++;
                    }
                  }
                  if (sampleCount > 0) {
                    double avgBrightness = totalLuminance / sampleCount;
                    shapeIsDark = avgBrightness < 160;
                    _brightnessCache[sUrl] = shapeIsDark;
                    debugPrint(
                      '[SHAPE_BRIGHTNESS] Computed for ${shape['src']} -> avg=$avgBrightness, isDark=$shapeIsDark',
                    );
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
      if (imgUrl.isEmpty &&
          templateConfig['designUrl'] != null &&
          templateConfig['designUrl'].toString().isNotEmpty) {
        imgUrl = templateConfig['designUrl'].toString();
        debugPrint(
          '[BRIGHTNESS] Step 2 - templateConfig.designUrl = "$imgUrl"',
        );
      }

      // Priority 3: Check newFrameJson for image
      if (imgUrl.isEmpty &&
          newFrameJson['image'] != null &&
          newFrameJson['image'].toString().isNotEmpty) {
        imgUrl = newFrameJson['image'].toString();
        debugPrint('[BRIGHTNESS] Step 3 - newFrameJson.image = "$imgUrl"');
      }

      // Priority 4: Find background layer
      if (imgUrl.isEmpty) {
        dynamic bgLayer;
        try {
          bgLayer = preservedLayers.firstWhere(
            (l) =>
                l['is_background'] == true ||
                (l['is_background'] == null &&
                    (l['name'] == 'image1' ||
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
        debugPrint(
          '[BRIGHTNESS] ERROR: No image URL found for brightness detection!',
        );
        // Default to dark since most templates are dark
        templateIsDark = true;
        debugPrint('[BRIGHTNESS] Defaulting templateIsDark=true');
      }

      if (imgUrl.isNotEmpty) {
        // Make URL absolute if needed
        if (!imgUrl.startsWith('http')) {
          String baseUrl = templateBaseUrl.isNotEmpty
              ? templateBaseUrl
              : AppConfig.baseUrl.replaceAll('/123456', '') + '/public';
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
          debugPrint(
            '[BRIGHTNESS] CACHED result: templateIsDark=$templateIsDark',
          );
        } else {
          try {
            final resp = await http.get(Uri.parse(imgUrl));
            debugPrint(
              '[BRIGHTNESS] HTTP status=${resp.statusCode}, bodyLen=${resp.bodyBytes.length}',
            );
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
                  int r = data[i], g = data[i + 1], b = data[i + 2];
                  totalLuminance += (0.299 * r + 0.587 * g + 0.114 * b);
                  sampleCount++;
                }
                if (sampleCount > 0) {
                  double avgBrightness = totalLuminance / sampleCount;
                  templateIsDark = avgBrightness < 128;
                  _brightnessCache[imgUrl] = templateIsDark;
                  debugPrint(
                    '[BRIGHTNESS] COMPUTED: avgBrightness=${avgBrightness.toStringAsFixed(1)}, templateIsDark=$templateIsDark (samples=$sampleCount)',
                  );
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
      debugPrint(
        '[BRIGHTNESS] Applying colors: templateIsDark=$templateIsDark, shapeLayers=${shapeLayers.length}, textLayers=${newLayers.where((l) => l['type'] == 'text').length}',
      );
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

          double plateX = logoX - paddingX; // 10px left gap
          double plateY = logoY - paddingTop; // 20px top gap
          double plateW = logoW + (paddingX * 2); // 10px left + 10px right
          double plateH =
              logoH +
              paddingTop +
              paddingBottom; // 20px top + logo + 10px bottom

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

          debugPrint(
            '[LOGO_PLATE] ✅ Injected white plate: '
            'x=$plateX y=$plateY w=$plateW h=$plateH '
            'logoAt=($logoX,$logoY,$logoW,$logoH)',
          );
        }
      } else {
        // Light background: remove any existing plate
        final layers = templateConfig['layers'] as List;
        final removed = layers
            .where((l) => l['name'] == '_logo_bg_plate')
            .length;
        layers.removeWhere((l) => l['name'] == '_logo_bg_plate');
        if (removed > 0) {
          needsRefresh = true;
          debugPrint('[LOGO_PLATE] ❌ Removed plate (light background)');
        }
      }

      // PASS 1: Apply colors to TEXT layers first
      for (var newLayer in newLayers) {
        if (newLayer['type'] == 'text') {
          final bool changed = renderVersion >= 10
              ? _applyV10DynamicColor(newLayer, templateIsDark, shapeLayers)
              : _applyDynamicTextColor(newLayer, templateIsDark, shapeLayers);
          if (changed) {
            needsRefresh = true;
          }
        }
      }

      // PASS 2: Apply colors to ICON layers from each icon's own background.
      for (var newLayer in newLayers) {
        final String lname = (newLayer['name'] ?? newLayer['id'] ?? '')
            .toString()
            .toLowerCase();
        final bool isIcon =
            (_frameRenderVersion(newFrameJson) >= 10 &&
                _isV10IconLayer(newLayer)) ||
            newLayer['type'] == 'icon' ||
            ((newLayer['type'] == 'image') &&
                ([
                      'phone',
                      'email',
                      'website',
                      'address',
                      'social',
                    ].contains(newLayer['_businessKey']) ||
                    [
                      'phone',
                      'email',
                      'website',
                      'address',
                      'call',
                      'mobile',
                      'contact',
                      'whatsapp',
                      'tel',
                      'mail',
                      'web',
                      'url',
                      'location',
                      'icon',
                      'facebook',
                      'instagram',
                      'twitter',
                      'youtube',
                      'social',
                      'linkedin',
                    ].any((key) => lname.contains(key)) ||
                    newLayer['_originalType'] == 'icon' ||
                    (newLayer['_source_meta'] is Map &&
                        newLayer['_source_meta']['type'] == 'icon')));
        if (!isIcon) continue;
        final bool changed = renderVersion >= 10
            ? _applyV10DynamicColor(newLayer, templateIsDark, shapeLayers)
            : _applyDynamicTextColor(newLayer, templateIsDark, shapeLayers);
        if (changed) {
          needsRefresh = true;
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

  /// V10 dynamic colour is non-destructive: the saved colour is immutable and
  /// only `_resolved_color` changes for the current native preview.
  bool _applyV10DynamicColor(
    Map<String, dynamic> layer,
    bool templateIsDark,
    List<Map<String, dynamic>> shapeLayers,
  ) {
    final String type = layer['type']?.toString() ?? '';
    final bool isText = type == 'text';
    // V10+ keeps legacy raster icons as `image` layers with icon identity in
    // metadata. The resolver and renderer must use the same predicate.
    final bool isIcon = _isV10IconLayer(layer);
    if (!isText && !isIcon) return false;

    final Map<dynamic, dynamic>? sourceMeta = layer['_source_meta'] is Map
        ? layer['_source_meta'] as Map<dynamic, dynamic>
        : null;
    String original = layer['original_color']?.toString().trim() ?? '';
    if (original.isEmpty) {
      final candidates = isIcon
          ? [
              layer['tint_color'],
              layer['color'],
              sourceMeta?['original_color'],
              sourceMeta?['originalColor'],
              layer['font_color'],
            ]
          : [
              layer['color'],
              sourceMeta?['original_color'],
              sourceMeta?['originalColor'],
              layer['font_color'],
              layer['tint_color'],
            ];
      for (final candidate in candidates) {
        final value = candidate?.toString().trim() ?? '';
        if (value.isNotEmpty) {
          original = value;
          break;
        }
      }
      if (original.isEmpty) return false;
      layer['original_color'] = original;
    }

    final double x = safeDouble(layer['x'] ?? 0);
    final double y = safeDouble(layer['y'] ?? 0);
    final double w = safeDouble(layer['w'] ?? layer['width'] ?? 0);
    final double h = safeDouble(layer['h'] ?? layer['height'] ?? 0);
    final double centerX = x + w / 2;
    final double centerY = y + h / 2;
    final int zIndex = _layerZIndex(layer);

    Map<String, dynamic>? backdrop;
    for (final candidate in shapeLayers) {
      // A layer cannot be its own backdrop and a higher/equal z-index layer is
      // visually above it.  The greatest lower z-index is the physical layer
      // immediately beneath this text/icon.
      if (_layerZIndex(candidate) >= zIndex) continue;
      final double sx = safeDouble(candidate['x'] ?? 0);
      final double sy = safeDouble(candidate['y'] ?? 0);
      final double sw = safeDouble(candidate['w'] ?? candidate['width'] ?? 0);
      final double sh = safeDouble(candidate['h'] ?? candidate['height'] ?? 0);
      if (centerX < sx ||
          centerX > sx + sw ||
          centerY < sy ||
          centerY > sy + sh) {
        continue;
      }
      if (backdrop == null ||
          _layerZIndex(candidate) > _layerZIndex(backdrop)) {
        backdrop = candidate;
      }
    }

    bool backgroundIsDark = templateIsDark;
    if (backdrop != null) {
      if (backdrop['hasComputedBrightness'] == true) {
        backgroundIsDark = backdrop['shapeIsDark'] == true;
      } else {
        final String fill = backdrop['fill']?.toString().trim() ?? '';
        if (_isSafelyParsableColor(fill)) {
          final Color color = _parseColor(fill, fallback: Colors.white);
          final double brightness =
              0.299 * color.red + 0.587 * color.green + 0.114 * color.blue;
          backgroundIsDark = brightness < 160;
        }
      }
    }

    // The resolver returns the raw value unchanged when it cannot safely parse
    // it, which protects gradients/custom CSS colours from a forced black/white.
    final String resolved = DynamicColorResolver.resolve(
      originalColor: original,
      backgroundIsDark: backgroundIsDark,
    );
    if (layer['_resolved_color'] == resolved) return false;
    layer['_resolved_color'] = resolved;
    return true;
  }

  bool _isSafelyParsableColor(String value) {
    final String color = value.trim();
    return RegExp(r'^#[0-9a-fA-F]{6}([0-9a-fA-F]{2})?$').hasMatch(color) ||
        RegExp(r'^0x[0-9a-fA-F]{8}$', caseSensitive: false).hasMatch(color) ||
        RegExp(
          r'^rgb\(\s*\d{1,3}\s*,\s*\d{1,3}\s*,\s*\d{1,3}\s*\)$',
        ).hasMatch(color);
  }

  bool _applyDynamicTextColor(
    Map<String, dynamic> layer,
    bool templateIsDark,
    List<Map<String, dynamic>> shapeLayers,
  ) {
    final String diagName = (layer['name'] ?? layer['id'] ?? '').toString();
    debugPrint(
      '[COLOR_DIAG] Starting _applyDynamicTextColor for layer: "$diagName" (type: ${layer['type']})',
    );

    int renderVersion = _renderVersion();
    if (renderVersion >= 10 &&
        layer['_source_meta'] is Map &&
        layer['_source_meta']['originalColor'] != null) {
      debugPrint('[COLOR_DIAG] ❌ SKIPPED: V10+ respects originalColor');
      return false;
    }

    bool isText = layer['type'] == 'text';
    final String lname = (layer['name'] ?? layer['id'] ?? '')
        .toString()
        .toLowerCase();

    // Only target true contact/social icons. Do not broadly target all "icon" layers.
    bool isContactIcon =
        (layer['type'] == 'image' || layer['type'] == 'icon') &&
        ([
              'phone',
              'email',
              'website',
              'address',
              'social',
            ].contains(layer['_businessKey']) ||
            [
              'phone',
              'email',
              'website',
              'address',
              'call',
              'mobile',
              'contact',
              'whatsapp',
              'tel',
              'mail',
              'web',
              'url',
              'location',
              'facebook',
              'instagram',
              'twitter',
              'youtube',
              'social',
              'linkedin',
            ].any((key) => lname.contains(key)));

    // V10 also recognizes older raster icons (for example `Icon_1`) using
    // their saved identity. Before V10, the original matching rules remain
    // unchanged for backwards-compatible rendering.
    final bool isV10Frame =
        safeDouble(templateConfig['render_version'] ?? 1).toInt() >= 10;
    bool isIcon =
        layer['type'] == 'icon' ||
        isContactIcon ||
        layer['_originalType'] == 'icon' ||
        (layer['_source_meta'] is Map &&
            layer['_source_meta']['type'] == 'icon') ||
        (isV10Frame && _isV10IconLayer(layer));
    debugPrint(
      '[COLOR_DIAG] isText=$isText, isContactIcon=$isContactIcon, isIcon=$isIcon',
    );

    // DO NOT override colors for frame layers — EXCEPT for text and contact icons.
    if (layer['_is_frame_layer'] == true || layer['_isFrameLayer'] == true) {
      if (!isText && !isIcon) {
        debugPrint(
          '[COLOR_DIAG] ❌ SKIPPED: Regular frame layer that is not text or icon',
        );
        return false;
      }
    }

    if (!isText && !isIcon) {
      debugPrint('[COLOR_DIAG] ❌ SKIPPED: Not text or contact icon');
      return false;
    }

    final String layerName = (layer['name'] ?? layer['id'] ?? '').toString();
    debugPrint(
      '[COLOR_DIAG] ✅ PROCEEDING for "$layerName" (isText=$isText, isIcon=$isIcon)',
    );

    final double textX = safeDouble(layer['x'] ?? 0);
    final double textY = safeDouble(layer['y'] ?? 0);
    final double textW = safeDouble(layer['w'] ?? layer['width'] ?? 0);
    final double textH = safeDouble(layer['h'] ?? layer['height'] ?? 0);
    final double textCenterX = textX + textW / 2;
    final double textCenterY = textY + textH / 2;

    bool overlapsShape = false;
    bool shapeIsDark = false;

    // Inspect the nearest overlapping shape that is not above this layer.
    final int layerZIndex = _layerZIndex(layer);
    final List<Map<String, dynamic>> shapesToCheck = List.from(shapeLayers)
      ..sort((a, b) => _layerZIndex(b).compareTo(_layerZIndex(a)));

    for (var shape in shapesToCheck) {
      final String shapeName = (shape['name'] ?? shape['id'] ?? '')
          .toString()
          .toLowerCase();
      final String currentName = (layer['name'] ?? layer['id'] ?? '')
          .toString()
          .toLowerCase();
      if (shapeName == currentName && shapeName.isNotEmpty) {
        continue;
      }

      if (_layerZIndex(shape) >= layerZIndex) {
        continue;
      }

      final double sx = safeDouble(shape['x'] ?? 0);
      final double sy = safeDouble(shape['y'] ?? 0);
      final double sw = safeDouble(shape['w'] ?? shape['width'] ?? 0);
      final double sh = safeDouble(shape['h'] ?? shape['height'] ?? 0);

      if (textCenterX >= sx &&
          textCenterX <= (sx + sw) &&
          textCenterY >= sy &&
          textCenterY <= (sy + sh)) {
        overlapsShape = true;

        if (shape['hasComputedBrightness'] == true) {
          shapeIsDark = shape['shapeIsDark'] == true;
          debugPrint(
            '[COLOR] "$layerName" overlaps image shape with computed shapeIsDark=$shapeIsDark (src=${shape['src']})',
          );
        } else {
          // Parse the shape's fill color to determine its brightness
          final fillVal = shape['fill']?.toString() ?? '#FFFFFF';
          final Color shapeColor = _parseColor(fillVal, fallback: Colors.white);

          // Compute brightness (standard formula)
          final double luminance =
              (0.299 * shapeColor.red +
              0.587 * shapeColor.green +
              0.114 * shapeColor.blue);
          shapeIsDark = luminance < 160;

          debugPrint(
            '[COLOR] "$layerName" overlaps shape at (${sx.toInt()},${sy.toInt()},${sw.toInt()},${sh.toInt()}) with fill="$fillVal" (shapeIsDark=$shapeIsDark)',
          );
        }
        break;
      }
    }

    if (layer['original_color'] == null ||
        layer['original_color'].toString().trim().isEmpty) {
      layer['original_color'] = _canonicalOriginalColor(layer, isIcon: isIcon);
    }

    bool changed = false;
    final bool isBgDark = overlapsShape ? shapeIsDark : templateIsDark;
    final String originalColor = layer['original_color'].toString();
    final String newColor = DynamicColorResolver.resolve(
      originalColor: originalColor,
      backgroundIsDark: isBgDark,
    );
    debugPrint(
      '[COLOR_DIAG] OriginalColor: bgIsDark=$isBgDark, original=$originalColor -> result=$newColor',
    );
    if (isText) {
      debugPrint(
        '[COLOR] TEXT "$layerName" → templateIsDark=$templateIsDark overlapsShape=$overlapsShape → color=$newColor (was: ${layer['color']})',
      );
      if (layer['color'] != newColor) changed = true;
      layer['color'] = newColor;
      layer['font_color'] = newColor;
    } else if (isIcon) {
      debugPrint(
        '[COLOR] ICON "$layerName" → templateIsDark=$templateIsDark overlapsShape=$overlapsShape → tint=$newColor (was: ${layer['tint_color']})',
      );
      if (layer['tint_color'] != newColor || layer['color'] != newColor)
        changed = true;
      layer['tint_color'] = newColor;
      layer['color'] = newColor;
      // All icons use font_color in _buildIconLayer (both type=='icon' and type=='image' with icon metadata)
      layer['font_color'] = newColor;
    }
    return changed;
  }

  int _layerZIndex(Map<String, dynamic> layer) {
    final dynamic value = layer['z_index'];
    if (value is num) return value.toInt();
    return int.tryParse(value?.toString() ?? '') ?? 0;
  }

  /// V10's canonical icon predicate. Legacy icons were stored as
  /// `type: image` and therefore could miss the previous icon-only color pass.
  /// This deliberately requires explicit icon metadata or an icon-like saved
  /// name, so ordinary raster images and shapes never enter auto-color logic.
  bool _isV10IconLayer(Map<String, dynamic> layer) {
    if (layer['type'] == 'icon' || layer['_originalType'] == 'icon') {
      return true;
    }
    final dynamic sourceMeta = layer['_source_meta'];
    if (sourceMeta is Map && sourceMeta['type'] == 'icon') return true;
    if (layer['type'] != 'image') return false;

    const businessKeys = {'phone', 'email', 'website', 'address', 'social'};
    if (businessKeys.contains(layer['_businessKey']?.toString())) return true;

    final String name = (layer['name'] ?? layer['id'] ?? '')
        .toString()
        .toLowerCase();
    return const [
      'icon',
      'phone',
      'email',
      'website',
      'address',
      'call',
      'mobile',
      'contact',
      'whatsapp',
      'tel',
      'mail',
      'web',
      'url',
      'location',
      'facebook',
      'instagram',
      'twitter',
      'youtube',
      'linkedin',
    ].any(name.contains);
  }

  String _canonicalOriginalColor(
    Map<String, dynamic> layer, {
    required bool isIcon,
  }) {
    final values = isIcon
        ? [layer['tint_color'], layer['color'], layer['font_color']]
        : [layer['color'], layer['font_color'], layer['tint_color']];

    for (final value in values) {
      final color = value?.toString().trim() ?? '';
      if (color.isNotEmpty) return color;
    }

    return DynamicColorResolver.white;
  }

  Color _parseColor(
    String colorStr, {
    Color fallback = const Color(0xFF000000),
  }) {
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

    String hex = colorStr
        .replaceAll('#', '')
        .replaceAll('0x', '')
        .replaceAll('0X', '');
    if (hex.length == 6) hex = 'FF$hex';

    if (hex.length == 8) {
      int? parsed = int.tryParse(hex, radix: 16);
      if (parsed != null) return Color(parsed);
    }

    return fallback;
  }
}

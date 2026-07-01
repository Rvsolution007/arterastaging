import '../utils/safe_double.dart';
import 'package:get/get.dart';

import 'package:flutter/material.dart';

import 'dart:convert';

import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';

import 'dart:ui' as ui;
import '../config/app_config.dart';

import '../services/api_service.dart';

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

    if (editorType == 'business_custom_frame') {
      _injectDynamicBusinessFrame();
    }

    _pushHistory();
    
    // Run brightness detection on initial load too (not just on frame switch)
    _applyInitialBrightness();
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
        'text': homeCtrl.businessPhone.value,
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

  /// Applies brightness-based color theming on initial load
  Future<void> _applyInitialBrightness() async {
    final layers = templateConfig['layers'] as List<dynamic>? ?? [];
    if (layers.isEmpty) return;
    
    // Build shape layers list (same logic as loadNewFrame)
    List<Map<String, dynamic>> shapeLayers = [];
    for (var layer in layers) {
      final String layerName = (layer['name'] ?? layer['id'] ?? '').toString().toLowerCase();
      final bool isBg = layer['is_background'] == true || 
                        (layer['is_background'] == null && ['image1', 'main_image', 'bg', 'background', '_frame_bg'].contains(layerName));
      
      if (!isBg && (layer['type'] == 'image' || layer['type'] == 'rect' || layer['type'] == 'shape')) {
        bool isContactIcon = ['phone', 'email', 'website', 'address', 'social'].any((e) => layerName.contains(e));
        if (!isContactIcon || layer['type'] != 'image') {
          double pw = safeDouble((layer['w'] ?? layer['width'] ?? 0) as num);
          double ph = safeDouble((layer['h'] ?? layer['height'] ?? 0) as num);
          if (pw > 50 && ph > 10) {
            shapeLayers.add({
              'x': safeDouble((layer['x'] ?? 0) as num),
              'y': safeDouble((layer['y'] ?? 0) as num),
              'w': pw,
              'h': ph,
            });
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
    
    // Update observable map
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
      
      if (matches) {
        return (layer['opacity'] ?? 1.0) > 0.0;
      }
    }
    return false;
  }

  void updateLayerProperty(String layerName, String property, dynamic value) {
    final layers = templateConfig['layers'] as List<dynamic>?;
    if (layers == null) return;

    for (var layer in layers) {
      if ((layer['name'] ?? layer['id']).toString() == layerName) {
        layer[property] = value;
        break;
      }
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
      if (rawNewLayers == null) rawNewLayers = [];

      final newLayers = jsonDecode(jsonEncode(rawNewLayers)) as List<dynamic>;
      
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

      for (var newLayer in newLayers) {
        double rawW = safeDouble((newLayer['w'] ?? newLayer['width'] ?? 0) as num);
        double rawH = safeDouble((newLayer['h'] ?? newLayer['height'] ?? 0) as num);
        String name = (newLayer['name'] ?? newLayer['id'] ?? '').toString();
        String layerName = name.toLowerCase();

        newLayer['_is_frame_layer'] = true;

        if (newLayer['type'] == 'image' && newLayer['src'] != null) {
          String srcStr = newLayer['src'].toString();
          if (!srcStr.startsWith('http') && !srcStr.startsWith('data:')) {
            String base = ApiService.baseUrl.replaceAll('/api', '');
            if (!base.endsWith('/')) {
              base += '/';
            }
            
            String frameBaseUrl = base;
            if (newFrameJson['full_url'] != null) {
              String fullUrl = newFrameJson['full_url'].toString();
              int skinsIndex = fullUrl.indexOf('/skins/');
              if (skinsIndex != -1) {
                frameBaseUrl = fullUrl.substring(0, skinsIndex) + '/';
              }
            }

            if (srcStr.startsWith('data:') || srcStr.startsWith('http')) {
              newLayer['src'] = srcStr;
            } else if (srcStr.startsWith('../')) {
              newLayer['src'] = '$frameBaseUrl${srcStr.replaceFirst('../', '')}';
            } else if (srcStr.startsWith('uploads/')) {
              newLayer['src'] = '$base$srcStr';
            } else {
              newLayer['src'] = '${frameBaseUrl}skins/$srcStr';
            }
          }
          
          // Server zip extraction replaces spaces with hyphens.
          // Replace proactively to avoid a 404 delay before the fallback kicks in.
          // This must run even if the URL already starts with 'http' (e.g. from native_editor_screen.dart)
          if (!newLayer['src'].toString().startsWith('data:')) {
            newLayer['src'] = newLayer['src'].toString().replaceAll(' ', '-').replaceAll('%20', '-');
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
          else if (bLow.contains('phone') || bLow.contains('mobile') || bLow.contains('contact') || bLow.contains('call') || bLow.contains('whatsapp') || bLow.contains('number') || bLow.contains('tel') || bLow.contains('ph')) newLayer['_businessKey'] = 'phone';
          else if (bLow.contains('email') || bLow.contains('mail')) newLayer['_businessKey'] = 'email';
          else if (bLow.contains('website') || bLow.contains('web') || bLow.contains('url')) newLayer['_businessKey'] = 'website';
          else if (bLow.contains('address') || bLow.contains('location')) newLayer['_businessKey'] = 'address';
          
          bool hasValidUserText = userTexts.containsKey(name) && userTexts[name] != null && userTexts[name]!.trim().isNotEmpty;
          
          if (hasValidUserText && (name.startsWith('_b_') || newLayer['_businessKey'] != null)) {
            newLayer['text'] = userTexts[name]; 
          } else if (Get.isRegistered<HomeController>() && newLayer['_businessKey'] != null) {
            final homeCtrl = Get.find<HomeController>();
            if (newLayer['_businessKey'] == 'name') newLayer['text'] = homeCtrl.businessName.value;
            else if (newLayer['_businessKey'] == 'phone') newLayer['text'] = homeCtrl.businessPhone.value;
            else if (newLayer['_businessKey'] == 'email') newLayer['text'] = homeCtrl.businessEmail.value;
            else if (newLayer['_businessKey'] == 'website') newLayer['text'] = homeCtrl.businessWebsite.value;
            else if (newLayer['_businessKey'] == 'address') newLayer['text'] = homeCtrl.businessAddress.value;
          }
        } else if (newLayer['type'] == 'image') {
          if (bLow.contains('phone') || bLow.contains('call') || bLow.contains('mobile') || bLow.contains('contact') || bLow.contains('whatsapp') || bLow.contains('tel') || bLow.contains('ph')) newLayer['_businessKey'] = 'phone';
          else if (bLow.contains('email') || bLow.contains('mail')) newLayer['_businessKey'] = 'email';
          else if (bLow.contains('website') || bLow.contains('web') || bLow.contains('url')) newLayer['_businessKey'] = 'website';
          else if (bLow.contains('address') || bLow.contains('location')) newLayer['_businessKey'] = 'address';
          else if (bLow.contains('icon') || bLow.contains('facebook') || bLow.contains('instagram') || bLow.contains('twitter') || bLow.contains('youtube') || bLow.contains('social') || bLow.contains('linkedin')) newLayer['_businessKey'] = 'social';
          else if (bLow.contains('logo') && !bLow.contains('email') && !bLow.contains('call') && !bLow.contains('phone') && !bLow.contains('web')) {
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
              if (newLayer['type'] != 'image' || rawW > 200 || rawH > 200) {
               double px = safeDouble((newLayer['x'] ?? 0) as num);
               double py = safeDouble((newLayer['y'] ?? 0) as num);
               double pw = safeDouble((newLayer['w'] ?? newLayer['width'] ?? 0) as num);
               double ph = safeDouble((newLayer['h'] ?? newLayer['height'] ?? 0) as num);
               if (pw > 50 && ph > 10) {
                 shapeLayers.add({'x': px, 'y': py, 'w': pw, 'h': ph});
               }
              }
            }
          }
        }
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
          if (!seenNames.contains(dedupeKey)) {
            seenNames.add(dedupeKey);
            uniqueLayers.add(layer);
          }
        } else {
          uniqueLayers.add(layer);
        }
      }
      
      templateConfig['layers'] = uniqueLayers.reversed.toList();
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
      
      // PASS 1: Apply colors to TEXT layers first
      for (var newLayer in newLayers) {
        if (newLayer['type'] == 'text') {
          if (_applyDynamicTextColor(newLayer, templateIsDark, shapeLayers)) {
            needsRefresh = true;
          }
        }
      }
      
      // PASS 2: Apply colors to ICON layers - match paired text color (same as web editor)
      for (var newLayer in newLayers) {
        final String lname = (newLayer['name'] ?? newLayer['id'] ?? '').toString().toLowerCase();
        bool isIcon = newLayer['type'] == 'image' && (
          ['phone', 'email', 'website', 'address', 'social'].contains(newLayer['_businessKey']) ||
          ['phone', 'email', 'website', 'address', 'call', 'mobile', 'contact', 'whatsapp', 'tel',
           'mail', 'web', 'url', 'location', 'icon', 'facebook', 'instagram', 'twitter', 'youtube',
           'social', 'linkedin'].any((key) => lname.contains(key))
        );
        if (!isIcon) continue;
        
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
        String? matchedTextColor;
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
        
        String layerName = (newLayer['name'] ?? '').toString();
        if (matchedTextColor != null) {
          debugPrint('[COLOR] ICON "$layerName" → matched text color=$matchedTextColor (bizKey=$iconBizKey)');
          if (newLayer['tint_color'] != matchedTextColor) needsRefresh = true;
          newLayer['tint_color'] = matchedTextColor;
        } else {
          // No matching text - use brightness-based color (for social icons etc.)
          if (_applyDynamicTextColor(newLayer, templateIsDark, shapeLayers)) {
            needsRefresh = true;
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
  ) {
    // DO NOT override user colors for Custom templates / frames!
    final type = Get.parameters['type'] ?? templateConfig['type'] ?? 'business_custom_frame';
    final tLow = type.toString().toLowerCase();
    if (tLow.contains('custom') || tLow == 'post' || tLow == 'business_custom_frame') {
      return false; // Skip auto-color for custom templates to preserve original ZIP colors
    }

    bool isText = layer['type'] == 'text';
    final String lname = (layer['name'] ?? layer['id'] ?? '').toString().toLowerCase();
    bool isIcon = layer['type'] == 'image' && (
                  ['phone', 'email', 'website', 'address', 'social'].contains(layer['_businessKey']) ||
                  ['phone', 'email', 'website', 'address', 'call', 'mobile', 'contact', 'whatsapp', 'tel',
                   'mail', 'web', 'url', 'location', 'icon', 'facebook', 'instagram', 'twitter', 'youtube',
                   'social', 'linkedin'].any((key) => lname.contains(key))
                  );

    if (!isText && !isIcon) return false;

    final String layerName = (layer['name'] ?? layer['id'] ?? '').toString();
    final double textX = safeDouble(layer['x'] ?? 0);
    final double textY = safeDouble(layer['y'] ?? 0);
    final double textW = safeDouble(layer['w'] ?? layer['width'] ?? 0);
    final double textH = safeDouble(layer['h'] ?? layer['height'] ?? 0);
    final double textCenterX = textX + textW / 2;
    final double textCenterY = textY + textH / 2;

    bool overlapsShape = false;
    for (var shape in shapeLayers) {
      final double sx = safeDouble(shape['x'] ?? 0);
      final double sy = safeDouble(shape['y'] ?? 0);
      final double sw = safeDouble(shape['w'] ?? shape['width'] ?? 0);
      final double sh = safeDouble(shape['h'] ?? shape['height'] ?? 0);

      if (textCenterX >= sx && textCenterX <= (sx + sw) &&
          textCenterY >= sy && textCenterY <= (sy + sh)) {
        overlapsShape = true;
        debugPrint('[COLOR] "$layerName" overlaps shape at (${sx.toInt()},${sy.toInt()},${sw.toInt()},${sh.toInt()}) → keeping original color');
        break;
      }
    }

    bool changed = false;
    if (!overlapsShape) {
      String newColor = templateIsDark ? '0xFFFFFFFF' : '0xFF000000';
      if (isText) {
        debugPrint('[COLOR] TEXT "$layerName" → templateIsDark=$templateIsDark → color=$newColor (was: ${layer['color']})');
        if (layer['color'] != newColor) changed = true;
        layer['color'] = newColor;
        layer['font_color'] = newColor;
      } else if (isIcon) {
        debugPrint('[COLOR] ICON "$layerName" → templateIsDark=$templateIsDark → tint=$newColor (was: ${layer['tint_color']})');
        if (layer['tint_color'] != newColor) changed = true;
        layer['tint_color'] = newColor;
      }
    } else {
      String originalColor = layer['original_color'] ?? layer['color'] ?? '0xFFFFFFFF';
      if (isText) {
        if (layer['color'] != originalColor) changed = true;
        layer['color'] = originalColor;
        layer['font_color'] = originalColor;
      } else if (isIcon) {
        if (layer['tint_color'] != originalColor) changed = true;
        layer['tint_color'] = originalColor;
      }
    }
    return changed;
  }
}



import 'package:get/get.dart';
import 'package:flutter/material.dart';
import 'dart:convert';
import 'package:http/http.dart' as http;
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
      final response = await ApiService.get('/get-all-frames?business_category_id=$bcId');
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
  void initConfig(Map<String, dynamic> initialConfig, String tplBaseUrl, String upBaseUrl, String? baseImg) {
    templateConfig.assignAll(jsonDecode(jsonEncode(initialConfig))); // deep copy
    templateBaseUrl = tplBaseUrl;
    uploadsBaseUrl = upBaseUrl;
    baseImgUrl = baseImg;

    _pushHistory();
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
  }

  void deselectAll() {
    selectedLayerId.value = '';
  }

  void updateLayerBounds(String layerName, double x, double y, double w, double h, double angle) {
    final layers = templateConfig['layers'] as List<dynamic>?;
    if (layers == null) return;

    for (var layer in layers) {
      if (layer['name'] == layerName) {
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

    for (var layer in layers) {
      if (layer['name'] == layerName) {
        layer['opacity'] = isVisible ? 1.0 : 0.0;
        break;
      }
    }
    
    templateConfig.refresh();
    _pushHistory();
  }

  bool isLayerVisible(String layerName) {
    final layers = templateConfig['layers'] as List<dynamic>?;
    if (layers == null) return false;

    for (var layer in layers) {
      if (layer['name'] == layerName) {
        return (layer['opacity'] ?? 1.0) > 0.0;
      }
    }
    return false;
  }

  void updateLayerProperty(String layerName, String property, dynamic value) {
    final layers = templateConfig['layers'] as List<dynamic>?;
    if (layers == null) return;

    for (var layer in layers) {
      if (layer['name'] == layerName) {
        layer[property] = value;
        break;
      }
    }
    
    templateConfig.refresh();
    _pushHistory();
  }

  void deleteLayer(String layerName) {
    final layers = templateConfig['layers'] as List<dynamic>?;
    if (layers == null) return;

    layers.removeWhere((layer) => layer['name'] == layerName);
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

  Future<bool> generateAIText(String prompt, String baseUrl) async {
    try {
      final uri = Uri.parse('$baseUrl/editor/ai-content/generate');
      
      final payload = {
        'ai_instructions': prompt,
        'canvas_json': jsonEncode(templateConfig),
      };

      final response = await http.post(
        uri,
        headers: {'Content-Type': 'application/json', 'Accept': 'application/json'},
        body: jsonEncode(payload),
      );

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['success'] == true && data['updated_json'] != null) {
          templateConfig.assignAll(data['updated_json']);
          _pushHistory();
          return true;
        }
      }
      return false;
    } catch (e) {
      debugPrint('Error generating AI text: $e');
      return false;
    }
  }

  Future<void> loadNewFrame(Map<String, dynamic> newFrameJson) async {
    final currentLayers = templateConfig['layers'] as List<dynamic>? ?? [];
    final newLayers = newFrameJson['layers'] as List<dynamic>? ?? [];
    
    // Merge new frame layers into current config
    for (var newLayer in newLayers) {
      final name = newLayer['name'];
      final existingIndex = currentLayers.indexWhere((l) => l['name'] == name);
      if (existingIndex != -1) {
        // Only update layout/style properties, preserve user text if applicable
        final existingLayer = currentLayers[existingIndex];
        if (existingLayer['type'] == 'text' && existingLayer['text'] != null && name.startsWith('_b_')) {
            newLayer['text'] = existingLayer['text']; // Keep user's business detail
        }
        currentLayers[existingIndex] = newLayer;
      } else {
        currentLayers.add(newLayer);
      }
    }
    
    templateConfig['layers'] = currentLayers;
    templateConfig.refresh();
    _pushHistory();
  }
}


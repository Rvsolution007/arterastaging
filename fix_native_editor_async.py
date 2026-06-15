import re

filepath = 'brandkit_mobile/lib/controllers/native_editor_controller.dart'
with open(filepath, 'r', encoding='utf-8') as f:
    content = f.read()

start_idx = content.find('  Future<void> loadNewFrame(Map<String, dynamic> newFrameJson) async {')

new_methods = """  Future<void> loadNewFrame(Map<String, dynamic> newFrameJson) async {
    try {
      final currentLayers = templateConfig['layers'] as List<dynamic>? ?? [];
      final rawNewLayers = newFrameJson['layers'] as List<dynamic>? ?? [];
      final newLayers = jsonDecode(jsonEncode(rawNewLayers)) as List<dynamic>;
      
      Map<String, String> userTexts = {};
      for (var l in currentLayers) {
        final name = (l['name'] ?? l['id'] ?? '').toString();
        if (l['type'] == 'text' && l['text'] != null) {
          userTexts[name] = l['text'].toString();
        }
      }

      final preservedLayers = currentLayers.where((l) {
        final name = (l['name'] ?? l['id'] ?? '').toString();
        final bool isBg = l['is_background'] == true || name == 'image1' || name == 'main_image' || name == 'bg';
        final bool isUserAdded = name.startsWith('New ') || name.startsWith('Logo ') || name.startsWith('Product ') || name.startsWith('Sticker ') || name.startsWith('Text ');
        return isBg || isUserAdded;
      }).toList();
      
      double canvasW = (templateConfig['info']?['width'] ?? templateConfig['width'] ?? 1080).toDouble();
      double canvasH = (templateConfig['info']?['height'] ?? templateConfig['height'] ?? 1080).toDouble();
      
      double frameW = (newFrameJson['info']?['width'] ?? newFrameJson['width'] ?? 0).toDouble();
      double frameH = (newFrameJson['info']?['height'] ?? newFrameJson['height'] ?? 0).toDouble();
      
      if (frameW <= 0 || frameH <= 0) {
        for (var layer in newLayers) {
          double r = ((layer['x'] ?? 0) as num).toDouble() + ((layer['width'] ?? layer['w'] ?? 0) as num).toDouble();
          double b = ((layer['y'] ?? 0) as num).toDouble() + ((layer['height'] ?? layer['h'] ?? 0) as num).toDouble();
          if (r > frameW) frameW = r;
          if (b > frameH) frameH = b;
        }
      }
      if (frameW <= 0) frameW = 1080;
      if (frameH <= 0) frameH = 1080;

      double scaleX = canvasW / frameW;
      double scaleY = canvasH / frameH;

      List<Map<String, dynamic>> shapeLayers = [];
      double docPPI = 300.0;
      try {
        if (newFrameJson['info'] != null) {
          if (newFrameJson['info'] is String) {
            final parsedInfo = jsonDecode(newFrameJson['info']);
            if (parsedInfo['ppi'] != null) docPPI = (parsedInfo['ppi'] as num).toDouble();
          } else if (newFrameJson['info']['ppi'] != null) {
            docPPI = (newFrameJson['info']['ppi'] as num).toDouble();
          }
        }
      } catch (e) {
        debugPrint('[PPI] Error: $e');
      }
      double ppiScale = docPPI / 72.0;

      for (var newLayer in newLayers) {
        double rawW = ((newLayer['w'] ?? newLayer['width'] ?? 0) as num).toDouble();
        double rawH = ((newLayer['h'] ?? newLayer['height'] ?? 0) as num).toDouble();
        String name = (newLayer['name'] ?? newLayer['id'] ?? '').toString();
        String layerName = name.toLowerCase();

        if ((layerName == '_frame_bg' || layerName == '_frame' || layerName == 'frame') && (rawW <= 0 || rawH <= 0)) {
          rawW = frameW;
          rawH = frameH;
        }

        if (newLayer['x'] != null) newLayer['x'] = (newLayer['x'] as num).toDouble() * scaleX;
        if (newLayer['y'] != null) newLayer['y'] = (newLayer['y'] as num).toDouble() * scaleY;
        
        if (newLayer['w'] != null) newLayer['w'] = rawW * scaleX;
        if (newLayer['width'] != null) newLayer['width'] = rawW * scaleX;
        if (newLayer['h'] != null) newLayer['h'] = rawH * scaleY;
        if (newLayer['height'] != null) newLayer['height'] = rawH * scaleY;
        
        if (newLayer['fontSize'] != null) newLayer['fontSize'] = (newLayer['fontSize'] as num).toDouble() * ppiScale * scaleY;
        if (newLayer['font_size'] != null) newLayer['font_size'] = (newLayer['font_size'] as num).toDouble() * ppiScale * scaleY;
        if (newLayer['size'] != null) newLayer['size'] = (newLayer['size'] as num).toDouble() * ppiScale * scaleY;

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
                newLayer['src'] = homeCtrl.businessLogo.value;
              }
            }
          }
        }

        if (newLayer['type'] == 'image' || newLayer['type'] == 'rect' || newLayer['type'] == 'shape') {
          if (newLayer['type'] != 'image' || !['phone', 'email', 'website', 'address', 'social'].any((e) => layerName.contains(e))) {
            if (newLayer['type'] != 'image' || rawW > 200 || rawH > 200) {
               double px = ((newLayer['x'] ?? 0) as num).toDouble();
               double py = ((newLayer['y'] ?? 0) as num).toDouble();
               double pw = ((newLayer['w'] ?? newLayer['width'] ?? 0) as num).toDouble();
               double ph = ((newLayer['h'] ?? newLayer['height'] ?? 0) as num).toDouble();
               if (pw > 50 && ph > 10) {
                 shapeLayers.add({'x': px, 'y': py, 'w': pw, 'h': ph});
               }
            }
          }
        }
      }

      // 1. Initial Render: Add new layers instantly so the UI feels fast.
      for (var newLayer in newLayers) {
        preservedLayers.add(newLayer);
      }
      
      final seenNames = <String>{};
      final uniqueLayers = <Map<String, dynamic>>[];
      for (var layer in preservedLayers.reversed) {
        final name = (layer['name'] ?? layer['id'] ?? '').toString();
        if (name.isNotEmpty) {
          if (!seenNames.contains(name)) {
            seenNames.add(name);
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
      debugPrint('[LOAD_FRAME] Error: $e\\n$stack');
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
      String baseImgUrl = '';
      if (templateConfig['designUrl'] != null && templateConfig['designUrl'].toString().isNotEmpty) {
        baseImgUrl = templateConfig['designUrl'];
      } else if (newFrameJson['image'] != null && newFrameJson['image'].toString().isNotEmpty) {
        baseImgUrl = newFrameJson['image'];
      } else {
        final bgLayer = preservedLayers.firstWhere(
          (l) => l['name'] == 'image1' || 
                 l['name'] == 'main_image' || 
                 l['name'] == 'bg' || 
                 l['name'] == 'background' || 
                 l['is_background'] == true, 
          orElse: () => null
        );
        if (bgLayer != null && bgLayer['src'] != null) baseImgUrl = bgLayer['src'];
      }

      if (baseImgUrl.isNotEmpty) {
        if (!baseImgUrl.startsWith('http')) {
          String baseUrl = templateBaseUrl.isNotEmpty ? templateBaseUrl : AppConfig.baseUrl.replaceAll('/123456', '') + '/public';
          if (baseImgUrl.startsWith('../')) {
            baseImgUrl = '$baseUrl/${baseImgUrl.replaceFirst('../', '')}';
          } else {
            baseImgUrl = '$baseUrl/skins/$baseImgUrl';
          }
        }
        
        if (_brightnessCache.containsKey(baseImgUrl)) {
          templateIsDark = _brightnessCache[baseImgUrl]!;
        } else {
          final resp = await http.get(Uri.parse(baseImgUrl));
          if (resp.statusCode == 200) {
            final codec = await ui.instantiateImageCodec(resp.bodyBytes);
            final frameInfo = await codec.getNextFrame();
            final img = frameInfo.image;
            final data = (await img.toByteData())?.buffer.asUint8List();
            if (data != null) {
              double totalLuminance = 0;
              int sampleCount = 0;
              int startY = (img.height * 0.7).floor();
              int startIdx = startY * img.width * 4;
              for (int i = startIdx; i < data.length; i += 4 * 10) {
                int r = data[i], g = data[i+1], b = data[i+2];
                totalLuminance += (0.299 * r + 0.587 * g + 0.114 * b);
                sampleCount++;
              }
              if (sampleCount > 0) {
                templateIsDark = (totalLuminance / sampleCount) < 128;
                _brightnessCache[baseImgUrl] = templateIsDark;
              }
            }
          }
        }

        // Apply colors and refresh UI one more time if needed
        bool needsRefresh = false;
        for (var newLayer in newLayers) {
          if (_applyDynamicTextColor(newLayer, templateIsDark, shapeLayers)) {
            needsRefresh = true;
          }
        }
        if (needsRefresh) {
          templateConfig.refresh();
        }
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
    bool isText = layer['type'] == 'text';
    bool isIcon = layer['type'] == 'image' && 
                  ['phone', 'email', 'website', 'address', 'social'].contains(layer['_businessKey']);

    if (!isText && !isIcon) return false;

    final double textX = (layer['x'] ?? 0).toDouble();
    final double textY = (layer['y'] ?? 0).toDouble();
    final double textW = (layer['w'] ?? layer['width'] ?? 0).toDouble();
    final double textH = (layer['h'] ?? layer['height'] ?? 0).toDouble();
    final double textCenterX = textX + textW / 2;
    final double textCenterY = textY + textH / 2;

    bool overlapsShape = false;
    for (var shape in shapeLayers) {
      final double sx = (shape['x'] ?? 0).toDouble();
      final double sy = (shape['y'] ?? 0).toDouble();
      final double sw = (shape['w'] ?? shape['width'] ?? 0).toDouble();
      final double sh = (shape['h'] ?? shape['height'] ?? 0).toDouble();

      if (textCenterX >= sx && textCenterX <= (sx + sw) &&
          textCenterY >= sy && textCenterY <= (sy + sh)) {
        overlapsShape = true;
        break;
      }
    }

    bool changed = false;
    if (!overlapsShape) {
      String newColor = templateIsDark ? '0xFFFFFFFF' : '0xFF000000';
      if (isText) {
        if (layer['color'] != newColor) changed = true;
        layer['color'] = newColor;
        layer['font_color'] = newColor;
      } else if (isIcon) {
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
"""

new_content = content[:start_idx] + new_methods

with open(filepath, 'w', encoding='utf-8') as f:
    f.write(new_content)

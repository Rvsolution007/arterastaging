import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:get/get.dart';
import 'dart:convert';
import 'package:cached_network_image/cached_network_image.dart';
import '../config/app_config.dart';
import '../controllers/native_editor_controller.dart';
import '../services/api_service.dart';
import 'package:image_picker/image_picker.dart';
import 'package:http/http.dart' as http;
import '../utils/app_colors.dart';
import '../widgets/editor_canvas_widget.dart';
import 'package:flutter_colorpicker/flutter_colorpicker.dart';
import 'ai_chat_screen.dart';
import 'dart:ui' as ui;
import 'dart:typed_data';
import 'package:flutter/rendering.dart';
import 'package:gal/gal.dart';
import '../services/download_service.dart';
import '../controllers/subscription_controller.dart';

class NativeEditorScreen extends StatefulWidget {
  final String type;
  final int id;
  final Map<String, dynamic> frameData;
  final String designUrl;

  const NativeEditorScreen({
    super.key,
    required this.type,
    required this.id,
    required this.frameData,
    required this.designUrl,
  });

  @override
  State<NativeEditorScreen> createState() => _NativeEditorScreenState();
}

class _NativeEditorScreenState extends State<NativeEditorScreen> {
  late NativeEditorController controller;

  // --- AI State (persistent across modal open/close) ---
  final TextEditingController _aiPromptController = TextEditingController();
  final TextEditingController _aiHiringRoleController = TextEditingController();
  final TextEditingController _aiHiringReqController = TextEditingController();
  final RxString _aiSelectedLanguage = 'English'.obs;
  final RxMap<String, String> _aiUploadedImages = <String, String>{}.obs;
  Map<String, dynamic>? _aiSelectedProduct;
  final RxString _aiCurrentStep = 'menu'.obs; // 'menu', 'customPrompt', 'review', 'hiring_questionnaire'
  final RxMap<String, TextEditingController> _aiGeneratedTexts = <String, TextEditingController>{}.obs;

  final GlobalKey<ScaffoldState> _scaffoldKey = GlobalKey<ScaffoldState>();
  final GlobalKey _canvasKey = GlobalKey();

  // --- Layer interaction state for deselect-on-background-tap ---
  Offset? _lastPointerDown;
  String _selectedBeforeTap = '';

  Future<void> _exportAndSave() async {
    controller.selectedLayerId.value = '';
    await Future.delayed(const Duration(milliseconds: 100));
    try {
      final boundary = _canvasKey.currentContext?.findRenderObject() as RenderRepaintBoundary?;
      if (boundary == null) return;
      final ui.Image image = await boundary.toImage(pixelRatio: 3.0);
      final ByteData? byteData = await image.toByteData(format: ui.ImageByteFormat.png);
      if (byteData == null) return;
      final Uint8List pngBytes = byteData.buffer.asUint8List();
      final fileName = "artera_design_${DateTime.now().millisecondsSinceEpoch}";
      if (!await Gal.hasAccess(toAlbum: true)) {
        await Gal.requestAccess(toAlbum: true);
      }
      await Gal.putImageBytes(pngBytes, name: fileName);
      await DownloadService.saveDownload(pngBytes, isVideo: false, fileName: fileName);
      bool isPaid = false;
      if (widget.type == 'festival' || widget.type == 'category') {
        isPaid = widget.frameData['isPaid'] == true;
      } else {
        isPaid = true; // Custom templates are always paid
      }

      await ApiService.trackActivity(
        action: 'download_template',
        itemType: widget.type,
        itemId: widget.id.toString(),
        isPremium: isPaid,
      );
      try { await Get.find<SubscriptionController>().refreshFromApi(); } catch (_) {}
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Design saved to gallery successfully!'), backgroundColor: Colors.green));
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Failed to save design: $e'), backgroundColor: Colors.red));
      }
    }
  }

  @override
  void initState() {
    super.initState();
    controller = Get.put(NativeEditorController());

    // Extract JSON config
    Map<String, dynamic> config = {};
    if (widget.frameData['json'] != null && widget.frameData['json'].toString().trim().isNotEmpty) {
      if (widget.frameData['json'] is String) {
        try {
          config = jsonDecode(widget.frameData['json']);
        } catch (e) {
          debugPrint('Error decoding JSON: $e');
        }
      } else {
        config = widget.frameData['json'];
      }
    }
    
    // Fallback for festival / category posts that only have an image and no JSON
    if (config.isEmpty) {
      final imgUrl = widget.designUrl.isNotEmpty ? widget.designUrl : widget.frameData['image'] ?? '';
      config = {
        'info': {
          'width': widget.frameData['width'] ?? 1080,
          'height': widget.frameData['height'] ?? 1080,
        },
        'layers': [
          {
            'name': 'bg',
            'type': 'image',
            'src': imgUrl,
            'is_background': true,
            'x': 0,
            'y': 0,
            'w': widget.frameData['width'] ?? 1080,
            'h': widget.frameData['height'] ?? 1080,
            'z_index': 0,
          }
        ]
      };
    }

    // Safely extract templateBaseUrl from preview image URL if missing
    String templateBaseUrl = widget.frameData['templateBaseUrl'] ?? '';
    if (templateBaseUrl.isEmpty && widget.frameData['image'] != null) {
      final imgUrl = widget.frameData['image'] as String;
      if (imgUrl.isNotEmpty) {
        try {
          final uri = Uri.parse(imgUrl);
          final pathSegments = uri.pathSegments.toList();
          if (pathSegments.isNotEmpty) {
            pathSegments.removeLast(); // Remove preview.jpg
            templateBaseUrl = uri.replace(pathSegments: pathSegments).toString();
          }
        } catch (_) {}
      }
    }

    // Initialize controller with config
    // Use designUrl as the base template image for brightness detection
    String baseImg = widget.frameData['baseImgUrl'] ?? '';
    if (baseImg.isEmpty) baseImg = widget.designUrl;
    if (baseImg.isEmpty) baseImg = widget.frameData['image'] ?? '';
    
    // Final fallback: extract from background layer in config
    if (baseImg.isEmpty && config['layers'] != null) {
      for (var layer in config['layers']) {
        if (layer['is_background'] == true || (layer['is_background'] == null && (layer['name'] == 'bg' || layer['name'] == 'image1'))) {
          baseImg = layer['src'] ?? '';
          if (baseImg.isNotEmpty) break;
        }
      }
    }
    
    // Ensure baseImg is absolute
    if (baseImg.isNotEmpty && !baseImg.startsWith('http')) {
      String baseUrl = templateBaseUrl.isNotEmpty ? templateBaseUrl : AppConfig.baseUrl.replaceAll('/123456', '') + '/public';
      if (baseImg.startsWith('../')) {
        baseImg = '$baseUrl/${baseImg.replaceFirst('../', '')}';
      } else if (baseImg.startsWith('uploads/')) {
        baseImg = '$baseUrl/$baseImg';
      } else {
        baseImg = '$baseUrl/skins/$baseImg';
      }
    }
    
    debugPrint('╔══════════════════════════════════════════════════════');
    debugPrint('║ [INIT] widget.type="${widget.type}"');
    debugPrint('║ [INIT] designUrl="${widget.designUrl}"');
    debugPrint('║ [INIT] frameData.image="${widget.frameData['image']}"');
    debugPrint('║ [INIT] frameData.type="${widget.frameData['type']}"');
    debugPrint('║ [INIT] templateBaseUrl="$templateBaseUrl"');
    debugPrint('║ [INIT] baseImg="$baseImg"');
    debugPrint('║ [INIT] config.info=${config['info']}');
    debugPrint('║ [INIT] config.layers.length=${(config['layers'] as List?)?.length ?? 0}');
    if (config['layers'] != null) {
      for (var layer in config['layers']) {
        debugPrint('║ [INIT]   layer: name="${layer['name'] ?? layer['id']}" type="${layer['type']}" src="${layer['src'] ?? 'N/A'}"');
      }
    }
    debugPrint('╚══════════════════════════════════════════════════════');
    
    controller.initConfig(
      config,
      templateBaseUrl,
      widget.frameData['uploadsBaseUrl'] ?? '',
      baseImg,
      widget.type,
    );
  }

  @override
  void dispose() {
    Get.delete<NativeEditorController>();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      key: _scaffoldKey,
      backgroundColor: const Color(0xFFF3F4F6),
      appBar: AppBar(
        backgroundColor: Colors.white,
        elevation: 0,
        leading: IconButton(
          icon: const Icon(Icons.close, color: Colors.black87, size: 24),
          onPressed: () {
            if (controller.selectedLayerId.value.isNotEmpty) {
              controller.selectLayer('');
              controller.activeTool.value = '';
            } else {
              Navigator.pop(context);
            }
          },
        ),
        title: const Text(
          'Edit Design',
          style: TextStyle(color: Colors.black87, fontSize: 18, fontWeight: FontWeight.bold),
        ),
        centerTitle: true,
        actions: [
          IconButton(
            icon: const Icon(Icons.help_outline, color: Colors.black87),
            onPressed: () {
              showModalBottomSheet(
                context: context,
                isScrollControlled: true,
                backgroundColor: Colors.transparent,
                builder: (context) => ClipRRect(
                  borderRadius: const BorderRadius.vertical(top: Radius.circular(20)),
                  child: Container(
                    height: MediaQuery.of(context).size.height * 0.75,
                    color: Colors.white,
                    child: const AiChatScreen(source: 'editor'),
                  ),
                ),
              );
            },
          ),
        ],
      ),
      body: Column(
        children: [
          // Secondary Top Bar
          Container(
            color: Colors.white,
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
            child: Row(
              children: [

                Expanded(
                  child: Text(
                    _getTypeTitle(),
                    style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Colors.black87),
                    overflow: TextOverflow.ellipsis,
                  ),
                ),
                const SizedBox(width: 8),
                IconButton(
                  icon: const Icon(Icons.undo, color: Colors.black54, size: 18),
                  onPressed: () => controller.undo(),
                  constraints: const BoxConstraints(),
                  padding: const EdgeInsets.symmetric(horizontal: 4),
                ),
                IconButton(
                  icon: const Icon(Icons.redo, color: Colors.black54, size: 18),
                  onPressed: () => controller.redo(),
                  constraints: const BoxConstraints(),
                  padding: const EdgeInsets.symmetric(horizontal: 4),
                ),
                const SizedBox(width: 4),
                ElevatedButton(
                  style: ElevatedButton.styleFrom(
                    backgroundColor: const Color(0xFF3B28CC),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                    elevation: 0,
                    padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 0),
                    minimumSize: const Size(0, 32),
                  ),
                  onPressed: _exportAndSave,
                  child: const Text('Download', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 12)),
                ),
              ],
            ),
          ),
          Expanded(
            child: Listener(
              behavior: HitTestBehavior.translucent,
              onPointerDown: (event) {
                debugPrint('[CANVAS_LISTENER] onPointerDown at ${event.localPosition}');
                _lastPointerDown = event.localPosition;
                controller.layerWasTapped = false;
              },
              onPointerUp: (event) {
                debugPrint('[CANVAS_LISTENER] onPointerUp at ${event.localPosition}');
                final downPos = _lastPointerDown;
                if (downPos != null) {
                  final distance = (event.localPosition - downPos).distance;
                  if (distance < 10) {
                    // It was a tap (not a drag). Use post-frame callback so child 
                    // GestureDetector.onTap fires first and updates the flag.
                    WidgetsBinding.instance.addPostFrameCallback((_) {
                      if (!controller.layerWasTapped) {
                        debugPrint('[CANVAS_LISTENER] No layer tapped → deselecting');
                        controller.selectedLayerId.value = '';
                      }
                      controller.layerWasTapped = false;
                    });
                  }
                }
              },
              child: Container(
                color: const Color(0xFFE2E8F0),
                width: double.infinity,
                child: Center(
                  child: LayoutBuilder(
                    builder: (context, constraints) {
                      return Obx(() {
                        if (controller.templateConfig.isEmpty) {
                          return const Center(child: Text('No template data found.'));
                        }
                        
                        // Calculate best fit dimensions to ensure 100% visibility
                        final config = controller.templateConfig;
                        final double designW = (config['info']?['width'] ?? config['width'] ?? 1080).toDouble();
                        final double designH = (config['info']?['height'] ?? config['height'] ?? 1080).toDouble();
                        
                        final double screenW = constraints.maxWidth;
                        final double availableH = constraints.maxHeight;
                        
                        double bestWidth = screenW;
                        double calcHeight = bestWidth * (designH / designW);
                        
                        if (calcHeight > availableH) {
                          calcHeight = availableH;
                          bestWidth = calcHeight * (designW / designH);
                        }

                        return RepaintBoundary(
                          key: _canvasKey,
                          child: Container(
                            decoration: BoxDecoration(
                              color: Colors.white,
                              boxShadow: [
                                BoxShadow(
                                  color: Colors.black.withOpacity(0.1),
                                  blurRadius: 15,
                                  offset: const Offset(0, 5),
                                ),
                              ],
                            ),
                            child: EditorCanvasWidget(
                              config: Map<String, dynamic>.from(controller.templateConfig),
                              width: bestWidth,
                              uploadsBaseUrl: controller.uploadsBaseUrl,
                              templateBaseUrl: controller.templateBaseUrl,
                              baseImgUrl: controller.baseImgUrl,
                              editorType: widget.type,
                            ),
                          ),
                        );
                      });
                    }
                  ),
                ),
              ),
            ),
          ),
          // Bottom Area: Contextual OR Main tools
          Obx(() {
            final _ = controller.templateConfig.values.toList(); // trigger rebuild on config change
            if (controller.selectedLayerId.value.isNotEmpty) {
              return _buildContextualToolbar();
            }
            return _buildBottomTools();
          }),
        ],
      ),
    );
  }

  String _getTypeTitle() {
    switch (widget.type) {
      case 'custom':
        return 'Custom Post';
      case 'festival':
        return 'Festival Post';
      case 'category':
        return 'Category Post';
      case 'business_custom_frame':
      case 'business_custom':
        return 'Business Template';
      default:
        return 'Edit Design';
    }
  }

  Map<String, dynamic>? _getActiveLayer() {
    if (controller.selectedLayerId.value.isEmpty) return null;
    final layers = controller.templateConfig['layers'];
    if (layers == null) return null;
    
    for (var l in layers) {
      if ((l['name'] ?? l['id']).toString() == controller.selectedLayerId.value) {
        return l as Map<String, dynamic>;
      }
    }
    return null;
  }

  Widget _buildInlineEditPanel() {
    final layer = _getActiveLayer();
    if (layer == null) return const SizedBox.shrink();
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
      child: Row(
        children: [
          Expanded(
            child: TextFormField(
              initialValue: layer['text'] ?? '',
              onChanged: (val) {
                controller.updateLayerProperty(layer['name'] ?? layer['id'], 'text', val);
              },
              decoration: InputDecoration(
                hintText: 'Enter text',
                isDense: true,
                border: OutlineInputBorder(borderRadius: BorderRadius.circular(8)),
              ),
            ),
          ),
          IconButton(
            icon: const Icon(Icons.check_circle, color: Color(0xFF5538EE)),
            onPressed: () {
              controller.activeTool.value = '';
            },
          ),
        ],
      ),
    );
  }

  Widget _buildInlineNudgePanel() {
    final layer = _getActiveLayer();
    if (layer == null) return const SizedBox.shrink();
    return Container(
      padding: const EdgeInsets.symmetric(vertical: 8),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          IconButton(
            icon: const Icon(Icons.keyboard_arrow_left, size: 32),
            onPressed: () => controller.updateLayerProperty((layer['name'] ?? layer['id']).toString(), 'x', (layer['x'] ?? 0) - 5),
          ),
          Column(
            children: [
              IconButton(
                icon: const Icon(Icons.keyboard_arrow_up, size: 32),
                onPressed: () => controller.updateLayerProperty((layer['name'] ?? layer['id']).toString(), 'y', (layer['y'] ?? 0) - 5),
              ),
              IconButton(
                icon: const Icon(Icons.keyboard_arrow_down, size: 32),
                onPressed: () => controller.updateLayerProperty((layer['name'] ?? layer['id']).toString(), 'y', (layer['y'] ?? 0) + 5),
              ),
            ],
          ),
          IconButton(
            icon: const Icon(Icons.keyboard_arrow_right, size: 32),
            onPressed: () => controller.updateLayerProperty((layer['name'] ?? layer['id']).toString(), 'x', (layer['x'] ?? 0) + 5),
          ),
        ],
      ),
    );
  }

  Widget _buildInlineFontPanel() {
    final layer = _getActiveLayer();
    if (layer == null) return const SizedBox.shrink();
    final List<String> fonts = ['Roboto', 'Open Sans', 'Lato', 'Montserrat', 'Oswald', 'Playfair Display', 'Merriweather', 'Nunito', 'Poppins', 'Raleway'];
    return Container(
      height: 50,
      child: ListView.builder(
        scrollDirection: Axis.horizontal,
        padding: const EdgeInsets.symmetric(horizontal: 16),
        itemCount: fonts.length,
        itemBuilder: (context, index) {
          final font = fonts[index];
          final isSelected = layer['fontFamily'] == font;
          return GestureDetector(
            onTap: () => controller.updateLayerProperty((layer['name'] ?? layer['id']).toString(), 'fontFamily', font),
            child: Container(
              alignment: Alignment.center,
              padding: const EdgeInsets.symmetric(horizontal: 16),
              margin: const EdgeInsets.only(right: 8),
              decoration: BoxDecoration(
                color: isSelected ? const Color(0xFF5538EE) : Colors.grey.shade200,
                borderRadius: BorderRadius.circular(20),
              ),
              child: Text(font, style: TextStyle(color: isSelected ? Colors.white : Colors.black87, fontFamily: font)),
            ),
          );
        },
      ),
    );
  }

  Widget _buildInlineSizePanel() {
    final layer = _getActiveLayer();
    if (layer == null) return const SizedBox.shrink();
    
    double currentSize = (layer['fontSize'] ?? layer['font_size'] ?? layer['size'] ?? 48.0).toDouble();
    double minSize = 8.0;
    double maxSize = 400.0;
    
    if (currentSize < minSize) currentSize = minSize;
    if (currentSize > maxSize) currentSize = maxSize;

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
      child: Row(
        children: [
          const Icon(Icons.text_fields, size: 16),
          Expanded(
            child: Slider(
              value: currentSize,
              min: minSize,
              max: maxSize,
              activeColor: const Color(0xFF5538EE),
              onChanged: (val) {
                controller.updateLayerProperty((layer['name'] ?? layer['id']).toString(), 'fontSize', val);
              },
            ),
          ),
          const Icon(Icons.text_fields, size: 24),
        ],
      ),
    );
  }

  void _showColorPickerDialog(Map<String, dynamic> layer) {
    Color currentColor = Color(int.tryParse((layer['font_color'] ?? layer['tint_color'] ?? layer['color']?.toString() ?? '#000000').replaceFirst('#', '0xFF')) ?? 0xFF000000);
    
    showDialog(
      context: context,
      builder: (context) {
        Color pickerColor = currentColor;
        return AlertDialog(
          title: const Text('Colour picker'),
          content: SingleChildScrollView(
            child: ColorPicker(
              pickerColor: pickerColor,
              onColorChanged: (color) {
                pickerColor = color;
              },
              pickerAreaHeightPercent: 0.8,
              enableAlpha: false,
              displayThumbColor: true,
              paletteType: PaletteType.hsv,
            ),
          ),
          actions: <Widget>[
            TextButton(
              child: const Text('Cancel'),
              onPressed: () => Navigator.of(context).pop(),
            ),
            TextButton(
              child: const Text('Apply'),
              onPressed: () {
                final hex = '#${pickerColor.value.toRadixString(16).padLeft(8, '0').substring(2)}';
                controller.updateLayerProperty((layer['name'] ?? layer['id']).toString(), 'color', hex);
                controller.updateLayerProperty((layer['name'] ?? layer['id']).toString(), 'font_color', hex);
                controller.updateLayerProperty((layer['name'] ?? layer['id']).toString(), 'tint_color', hex);
                Navigator.of(context).pop();
              },
            ),
          ],
        );
      },
    );
  }

  Widget _buildInlineColorPanel() {
    final layer = _getActiveLayer();
    if (layer == null) return const SizedBox.shrink();
    final List<Color> colors = [Colors.black, Colors.white, Colors.red, Colors.pink, Colors.purple, Colors.deepPurple, Colors.indigo, Colors.blue, Colors.lightBlue, Colors.cyan, Colors.teal, Colors.green, Colors.lightGreen, Colors.lime, Colors.yellow, Colors.amber, Colors.orange, Colors.deepOrange, Colors.brown, Colors.grey];
    String colorToHex(Color color) => '#${color.value.toRadixString(16).padLeft(8, '0').substring(2)}';
    
    return Container(
      height: 40,
      child: ListView.builder(
        scrollDirection: Axis.horizontal,
        padding: const EdgeInsets.symmetric(horizontal: 16),
        itemCount: colors.length + 1,
        itemBuilder: (context, index) {
          if (index == colors.length) {
            return GestureDetector(
              onTap: () {
                _showColorPickerDialog(layer);
              },
              child: Container(
                width: 36, height: 36,
                margin: const EdgeInsets.only(right: 12),
                decoration: BoxDecoration(shape: BoxShape.circle, color: Colors.transparent, border: Border.all(color: Colors.grey)),
                child: const Icon(Icons.colorize, color: Colors.black54, size: 18),
              ),
            );
          }
          final color = colors[index];
          final hex = colorToHex(color);
          final activeColor = (layer['font_color'] ?? layer['tint_color'] ?? layer['color'])?.toString().toUpperCase() ?? '';
          final isSelected = activeColor == hex.toUpperCase();
          return GestureDetector(
            onTap: () {
              controller.updateLayerProperty((layer['name'] ?? layer['id']).toString(), 'color', hex);
              controller.updateLayerProperty((layer['name'] ?? layer['id']).toString(), 'font_color', hex);
              controller.updateLayerProperty((layer['name'] ?? layer['id']).toString(), 'tint_color', hex);
            },
            child: Container(
              width: 36,
              height: 36,
              margin: const EdgeInsets.only(right: 12),
              decoration: BoxDecoration(
                color: color,
                shape: BoxShape.circle,
                border: Border.all(color: isSelected ? const Color(0xFF5538EE) : Colors.black12, width: isSelected ? 2 : 1),
              ),
              child: isSelected ? const Icon(Icons.check, size: 18, color: Colors.white) : null,
            ),
          );
        },
      ),
    );
  }

  Widget _buildContextualToolbar() {
    final activeLayer = _getActiveLayer();
    final bool isText = activeLayer != null && (activeLayer['type'] == 'text' || activeLayer['type'] == 'i-text' || activeLayer['text'] != null);
    final bool isImage = activeLayer != null && (activeLayer['type'] == 'image' || activeLayer['type'] == 'icon' || activeLayer['src'] != null);

    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
        boxShadow: [
          BoxShadow(color: Colors.black.withOpacity(0.1), blurRadius: 10, offset: const Offset(0, -5)),
        ],
      ),
      child: SafeArea(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            SingleChildScrollView(
              scrollDirection: Axis.horizontal,
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 16),
              child: Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  if (isText) _buildToolBtn(Icons.edit_outlined, 'Edit', () {
                    final layer = _getActiveLayer();
                    if (layer != null && (layer['type'] == 'text' || layer['type'] == 'i-text' || layer['text'] != null)) {
                      controller.activeTool.value = controller.activeTool.value == 'Edit' ? '' : 'Edit';
                    }
                  }),
                  _buildToolBtn(Icons.open_with, 'Nudge', () {
                    final layer = _getActiveLayer();
                    if (layer != null) {
                      controller.activeTool.value = controller.activeTool.value == 'Nudge' ? '' : 'Nudge';
                    }
                  }),
                  if (isText) _buildToolBtn(Icons.text_fields, 'Font', () {
                    final layer = _getActiveLayer();
                    if (layer != null && (layer['type'] == 'text' || layer['type'] == 'i-text' || layer['text'] != null)) {
                      controller.activeTool.value = controller.activeTool.value == 'Font' ? '' : 'Font';
                    }
                  }),
                  if (isText) _buildToolBtn(Icons.format_size, 'Size', () {
                    final layer = _getActiveLayer();
                    if (layer != null && (layer['type'] == 'text' || layer['type'] == 'i-text' || layer['text'] != null)) {
                      controller.activeTool.value = controller.activeTool.value == 'Size' ? '' : 'Size';
                    }
                  }),
                  if (isText) _buildToolBtn(Icons.format_bold, 'Bold', () {
                    final layer = _getActiveLayer();
                    if (layer != null && (layer['type'] == 'text' || layer['type'] == 'i-text' || layer['text'] != null)) {
                      final isBold = layer['weight'] == 'bold';
                      controller.updateLayerProperty((layer['name'] ?? layer['id']).toString(), 'weight', isBold ? 'normal' : 'bold');
                    }
                  }, isSelected: _getActiveLayer()?['weight'] == 'bold'),
                  if (isText) _buildToolBtn(Icons.format_italic, 'Italic', () {
                    final layer = _getActiveLayer();
                    if (layer != null && (layer['type'] == 'text' || layer['type'] == 'i-text' || layer['text'] != null)) {
                      final isItalic = layer['style'] == 'italic';
                      controller.updateLayerProperty((layer['name'] ?? layer['id']).toString(), 'style', isItalic ? 'normal' : 'italic');
                    }
                  }, isSelected: _getActiveLayer()?['style'] == 'italic'),
                  if (isText) _buildToolBtn(Icons.palette_outlined, 'Color', () {
                    final layer = _getActiveLayer();
                    if (layer != null && (layer['type'] == 'text' || layer['type'] == 'i-text' || layer['text'] != null)) {
                      controller.activeTool.value = controller.activeTool.value == 'Color' ? '' : 'Color';
                    }
                  }),
                  
                  if (isImage) _buildToolBtn(Icons.change_circle_outlined, 'Replace', () {
                    _showReplaceOptions();
                  }),
                  if (isImage) _buildToolBtn(Icons.flip, 'Mirror', () {
                    final layer = _getActiveLayer();
                    if (layer != null) {
                      bool currentFlipX = layer['flipX'] == true || layer['flipX'] == 'true';
                      controller.updateLayerProperties(controller.selectedLayerId.value, {
                        'flipX': !currentFlipX,
                      });
                    }
                  }),

                  _buildToolBtn(Icons.delete_outline, 'Delete', () {
                    controller.deleteLayer(controller.selectedLayerId.value);
                  }, iconColor: Colors.redAccent),
                ],
              ),
            ),
            Obx(() {
               if (controller.activeTool.value == 'Edit') return _buildInlineEditPanel();
               if (controller.activeTool.value == 'Nudge') return _buildInlineNudgePanel();
               if (controller.activeTool.value == 'Font') return _buildInlineFontPanel();
               if (controller.activeTool.value == 'Size') return _buildInlineSizePanel();
               if (controller.activeTool.value == 'Color') return _buildInlineColorPanel();
               return const SizedBox.shrink();
            }),
            Obx(() => SizedBox(height: controller.activeTool.value.isNotEmpty ? 16 : 0)),
          ],
        ),
      ),
    );
  }

  Widget _buildBottomTools() {
    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
        boxShadow: [
          BoxShadow(color: Colors.black.withOpacity(0.05), blurRadius: 10, offset: const Offset(0, -5)),
        ],
      ),
      child: SafeArea(
        child: SingleChildScrollView(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              // 1. Contact info badges — show for all templates
              SingleChildScrollView(
                scrollDirection: Axis.horizontal,
                padding: const EdgeInsets.all(16),
                child: Row(
                  children: [
                    _buildFieldBadge('NAME', '_b_name,name,business_name'),
                    _buildFieldBadge('LOGO', '_b_logo,logo'),
                    _buildIconBadge(Icons.phone_android, '_b_phone,phone_icon,mobile_icon,call_icon'),
                    _buildFieldBadge('MOBILE', 'mobile,phone,phone_text,mobile_text'),
                    _buildIconBadge(Icons.mail_outline, '_b_email,email_icon'),
                    _buildFieldBadge('EMAIL', 'email,email_text'),
                    _buildIconBadge(Icons.location_on_outlined, '_b_address,address_icon,location_icon'),
                    _buildFieldBadge('ADDRESS', 'address,location,address_text,location_text'),
                    _buildIconBadge(Icons.language, '_b_website,web_icon,website_icon'),
                    _buildFieldBadge('WEBSITE', 'web,website,web_text,website_text'),
                    _buildFieldBadge('FRAME', '_frame_bg,_frame,frame,Frame_Bg,bg_frame'),
                  ],
                ),
              ),
              // 2. Select Frame section — always show
                const Padding(
                  padding: EdgeInsets.only(left: 16, right: 16, top: 4),
                  child: Text('Select Frame', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
                ),
                const SizedBox(height: 12),
                
                // Frame thumbnails
                Obx(() {
                  if (controller.isLoadingFrames.value) {
                    return const Padding(
                      padding: EdgeInsets.all(16),
                      child: Center(child: SizedBox(width: 24, height: 24, child: CircularProgressIndicator(strokeWidth: 2))),
                    );
                  }
                  if (controller.frames.isEmpty) {
                    return const Padding(
                      padding: EdgeInsets.all(8),
                      child: Center(child: Text('No frames found', style: TextStyle(color: Colors.grey, fontSize: 12))),
                    );
                  }
                  return SizedBox(
                    height: 80,
                    child: ListView.builder(
                      scrollDirection: Axis.horizontal,
                      padding: const EdgeInsets.symmetric(horizontal: 16),
                      itemCount: controller.frames.length,
                      itemBuilder: (context, index) {
                        final frame = controller.frames[index];
                        final thumbUrl = frame['thumbnail'] ?? frame['thumbnail_url'] ?? frame['full_url'] ?? '';
                        return GestureDetector(
                          onTap: () {
                            // Try JSON string first, then parsed config object, then full_url fallback
                            final jsonStr = frame['json'] ?? frame['json_rules'];
                            final configObj = frame['config'];
                            if (jsonStr != null) {
                              try {
                                final Map<String, dynamic> config = jsonStr is String ? jsonDecode(jsonStr) : Map<String, dynamic>.from(jsonStr);
                                controller.loadNewFrame(config);
                              } catch (e) {
                                debugPrint('Error parsing frame JSON: $e');
                              }
                            } else if (configObj != null) {
                              try {
                                final Map<String, dynamic> config = configObj is String ? jsonDecode(configObj) : Map<String, dynamic>.from(configObj);
                                // Resolve relative src paths using full_url as base
                                final String fullUrl = (frame['full_url'] ?? '').toString();
                                if (fullUrl.isNotEmpty && config['layers'] != null) {
                                  final skinBase = fullUrl.substring(0, fullUrl.lastIndexOf('/') + 1);
                                  final tplBase = skinBase.replaceAll(RegExp(r'skins/[^/]+/$'), '');
                                  for (var layer in (config['layers'] as List)) {
                                    if (layer['src'] != null) {
                                      String src = layer['src'].toString();
                                      if (src.startsWith('../') || src.startsWith('./') || !src.startsWith('http')) {
                                        src = src.replaceAll('../', '');
                                        layer['src'] = tplBase + src;
                                      }
                                    }
                                  }
                                }
                                controller.loadNewFrame(config);
                              } catch (e) {
                                debugPrint('Error parsing frame config: $e');
                              }
                            } else if (frame['full_url'] != null) {
                              controller.updateLayerProperty('_frame_bg', 'src', frame['full_url']);
                              controller.updateLayerProperty('_frame_bg', 'opacity', 1.0);
                            }
                          },
                          child: Container(
                            width: 80,
                            height: 80,
                            margin: const EdgeInsets.only(right: 12),
                            decoration: BoxDecoration(
                              color: Colors.grey.shade100,
                              borderRadius: BorderRadius.circular(8),
                              border: Border.all(color: Colors.grey.shade300),
                            ),
                            child: thumbUrl.isNotEmpty
                                ? ClipRRect(
                                    borderRadius: BorderRadius.circular(8),
                                    child: Image.network(thumbUrl, fit: BoxFit.cover, errorBuilder: (_, __, ___) => const Icon(Icons.broken_image, color: Colors.grey)),
                                  )
                                : const Icon(Icons.image, color: Colors.grey),
                          ),
                        );
                      },
                    ),
                  );
                }),
                const SizedBox(height: 24),
              // end of frame section
              // Editing Tools Toolbar
              _buildEditingToolsBar(),
              const SizedBox(height: 8),
            ],
          ),
        ),
      ),
    );
  }

  void _showLayersModal(BuildContext context) {
    final ScrollController scrollController = ScrollController();

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (context) => ClipRRect(
        borderRadius: const BorderRadius.vertical(top: Radius.circular(20)),
        child: Container(
          height: MediaQuery.of(context).size.height * 0.6,
          color: Colors.white,
          child: Column(
            children: [
              Container(
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(border: Border(bottom: BorderSide(color: Colors.grey.shade200))),
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    const Text('Layers', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
                    IconButton(icon: const Icon(Icons.close), onPressed: () => Navigator.pop(context)),
                  ],
                ),
              ),
              Expanded(
                child: Obx(() {
                  final _ = controller.templateConfig.length;
                  final _updateTrigger = controller.layerUpdateTrigger.value;

                  final allLayers = List<dynamic>.from(controller.templateConfig['layers'] ?? []);
                  final frameLayersCount = allLayers.where((l) => l['_is_frame_layer'] == true || l['_isFrameLayer'] == true).length;
                  final customLayers = allLayers.where((l) => l['_is_frame_layer'] != true && l['_isFrameLayer'] != true).toList();
                  final customLayersCount = customLayers.length;
                  
                  // Reverse so top layers show first
                  final reversedLayers = customLayers.reversed.toList();
                  
                  final scrollbarWidget = Scrollbar(
                    controller: scrollController,
                    thumbVisibility: true,
                    thickness: 6,
                    radius: const Radius.circular(10),
                    child: ReorderableListView.builder(
                      scrollController: scrollController,
                      physics: const BouncingScrollPhysics(parent: AlwaysScrollableScrollPhysics()),
                      itemCount: reversedLayers.length,
                      onReorder: (oldIndex, newIndex) {
                      int adjustedNewIndex = newIndex;
                      if (newIndex > oldIndex) {
                        adjustedNewIndex -= 1;
                      }
                      
                      final item = reversedLayers[oldIndex];
                      final itemId = (item['name'] ?? item['id']).toString();
                      
                      int targetCustomIndex = customLayersCount - 1 - adjustedNewIndex;
                      
                      final newAllLayers = List.of(allLayers);
                      newAllLayers.removeWhere((l) => (l['name'] ?? l['id']).toString() == itemId);
                      
                      final remainingCustomLayers = newAllLayers.where((l) => l['_is_frame_layer'] != true && l['_isFrameLayer'] != true).toList();
                      
                      int actualNew;
                      if (targetCustomIndex >= remainingCustomLayers.length) {
                        if (remainingCustomLayers.isEmpty) {
                          actualNew = newAllLayers.length;
                        } else {
                          actualNew = newAllLayers.indexOf(remainingCustomLayers.last) + 1;
                        }
                      } else if (targetCustomIndex < 0) {
                        if (remainingCustomLayers.isEmpty) {
                          actualNew = 0;
                        } else {
                          actualNew = newAllLayers.indexOf(remainingCustomLayers.first);
                        }
                      } else {
                        actualNew = newAllLayers.indexOf(remainingCustomLayers[targetCustomIndex]);
                      }
                      
                      controller.moveLayer(itemId, actualNew);
                    },
                    itemBuilder: (context, index) {
                      final layer = reversedLayers[index];
                      final isVisible = (layer['opacity'] ?? 1.0) > 0;
                      final String uniqueKey = (layer['name'] ?? layer['id'] ?? 'layer').toString() + '_$index';
                      final isText = layer['type'] == 'text' || layer['type'] == 'i-text' || layer['text'] != null;
                      final titleStr = (layer['name'] ?? layer['id'] ?? 'Unnamed Layer').toString().replaceAll(RegExp(r'[-_]'), ' ').toUpperCase();
                      debugPrint('[LAYERS_MODAL] Layer: name=${layer['name']} type=${layer['type']} src=${(layer['src'] ?? '').toString().substring(0, (layer['src'] ?? '').toString().length > 80 ? 80 : (layer['src'] ?? '').toString().length)} iconName=${layer['iconName']} _is_frame=${layer['_is_frame_layer']}');
                      Widget previewWidget;
                      if (isText) {
                        String txt = (layer['text']?.toString() ?? 'T').trim();
                        if (txt.isEmpty) txt = 'T';
                        previewWidget = Container(
                          width: 40,
                          height: 40,
                          decoration: BoxDecoration(color: Colors.grey.shade100, borderRadius: BorderRadius.circular(6), border: Border.all(color: Colors.grey.shade300)),
                          child: Center(
                            child: Text(
                              txt.characters.take(2).toString(),
                              style: const TextStyle(fontWeight: FontWeight.bold, color: Colors.black87, fontSize: 16),
                            ),
                          ),
                        );
                      } else {
                        String src = (layer['src'] ?? '').toString();
                        if (src.startsWith('data:')) {
                          try {
                            final base64String = src.split(',').last;
                            previewWidget = ClipRRect(
                              borderRadius: BorderRadius.circular(6),
                              child: Image.memory(base64Decode(base64String), width: 40, height: 40, fit: BoxFit.cover, errorBuilder: (_, __, ___) => const Icon(Icons.image, color: Colors.grey)),
                            );
                          } catch (e) {
                            previewWidget = const Icon(Icons.image, color: Colors.grey);
                          }
                        } else if (src.isNotEmpty) {
                          String finalUrl = src;
                          if (!src.startsWith('http')) {
                            String baseUrl = controller.templateBaseUrl;
                            if (src.startsWith('../')) {
                              finalUrl = '$baseUrl/${src.replaceFirst('../', '')}';
                            } else if (src.contains('/')) {
                              finalUrl = '$baseUrl/$src';
                            } else {
                              finalUrl = '$baseUrl/skins/$src';
                            }
                          }
                          if (finalUrl.startsWith('http') && finalUrl.contains(' ')) {
                            finalUrl = Uri.encodeFull(finalUrl);
                          }
                          previewWidget = ClipRRect(
                            borderRadius: BorderRadius.circular(6),
                            child: CachedNetworkImage(
                              imageUrl: finalUrl,
                              width: 40,
                              height: 40,
                              fit: BoxFit.cover,
                              errorWidget: (_, __, ___) => Container(color: Colors.grey.shade100, child: const Icon(Icons.image, color: Colors.grey)),
                              placeholder: (_, __) => Container(color: Colors.grey.shade200, width: 40, height: 40),
                            ),
                          );
                        } else if (layer['type'] == 'icon' || (layer['iconName'] != null && layer['iconName'].toString().isNotEmpty)) {
                          previewWidget = Container(
                            width: 40,
                            height: 40,
                            decoration: BoxDecoration(color: Colors.grey.shade100, borderRadius: BorderRadius.circular(6), border: Border.all(color: Colors.grey.shade300)),
                            child: const Icon(Icons.star, color: Colors.grey),
                          );
                        } else {
                          previewWidget = Container(
                            width: 40,
                            height: 40,
                            decoration: BoxDecoration(color: Colors.grey.shade100, borderRadius: BorderRadius.circular(6), border: Border.all(color: Colors.grey.shade300)),
                            child: const Icon(Icons.category, color: Colors.grey),
                          );
                        }
                      }

                      return ListTile(
                        key: ValueKey(uniqueKey),
                        contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 4),
                        leading: previewWidget,
                        title: Text(titleStr, style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w600)),
                        trailing: Row(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            IconButton(
                              icon: const Icon(Icons.edit, color: Color(0xFF5538EE), size: 22),
                              onPressed: () {
                                Navigator.pop(context);
                                controller.selectLayer((layer['name'] ?? layer['id']).toString());
                              },
                              tooltip: 'Edit Layer',
                            ),
                            IconButton(
                              icon: Icon(isVisible ? Icons.visibility : Icons.visibility_off, color: isVisible ? Colors.black54 : Colors.grey.shade400, size: 22),
                              onPressed: () => controller.toggleVisibility((layer['name'] ?? layer['id']).toString(), !isVisible),
                              tooltip: isVisible ? 'Hide' : 'Show',
                            ),
                            const SizedBox(width: 4),
                            const Icon(Icons.drag_handle, color: Colors.grey, size: 24),
                          ],
                        ),
                      );
                    },
                  ), // End ReorderableListView.builder
                  ); // End Scrollbar
                  return scrollbarWidget;
                }),
              ),
            ],
          ),
        ),
      ),
    );
  }

  void _showAiTextModal(BuildContext context) {
    final RxBool isGenerating = false.obs;
    
    // Check if purpose is Hiring
    final String catName = (widget.frameData['customCategoryName'] ?? '').toString().toLowerCase();
    final bool isHiring = catName.contains('hiring') || catName.contains('job');

    // Count text layers to see if we need requirements
    final layers = controller.templateConfig['layers'] as List<dynamic>? ?? [];
    int textLayerCount = 0;
    for (var layer in layers) {
      if (layer['type'] == 'text') textLayerCount++;
    }
    final bool askRequirements = textLayerCount > 1;

    _aiCurrentStep.value = isHiring ? 'hiring_questionnaire' : 'menu'; // Reset on open

    final List<String> languages = [
      'English', 'Hindi', 'Hinglish', 'Gujarati', 'Marathi', 'Tamil', 'Telugu', 
      'Kannada', 'Malayalam', 'Bengali', 'Punjabi', 'Urdu', 'Arabic', 'Spanish', 
      'French', 'Portuguese', 'German', 'Japanese', 'Korean', 'Chinese'
    ];

    // Detect image slots from template layers
    final List<Map<String, dynamic>> imageSlots = [];
    for (var layer in layers) {
      if (layer['type'] == 'image' && layer['is_background'] != true) {
        final name = (layer['name'] ?? layer['id'] ?? '').toString().toLowerCase();
        if ((name.contains('image') || name.contains('pic') || name.contains('photo') || name.contains('product')) &&
            !name.contains('logo') && !name.contains('bg') && !name.contains('background')) {
          imageSlots.add(Map<String, dynamic>.from(layer));
        }
      }
    }
    
    // Auto-select first product if available and none selected (skip if hiring)
    if (_aiSelectedProduct == null && !isHiring) {
      ApiService.getUserProducts().then((res) {
        if (res.statusCode == 200) {
          try {
            final data = jsonDecode(res.body);
            final List products = data['data'] ?? data['products']?['data'] ?? [];
            if (products.isNotEmpty) {
              setState(() => _aiSelectedProduct = products.first);
            }
          } catch (_) {}
        }
      });
    }

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      barrierColor: Colors.transparent, // Make background fully visible
      builder: (ctx) => Padding(
        padding: EdgeInsets.only(bottom: MediaQuery.of(ctx).viewInsets.bottom),
        child: ClipRRect(
          borderRadius: const BorderRadius.vertical(top: Radius.circular(20)),
          child: Container(
            padding: const EdgeInsets.all(16),
            color: Colors.white,
            constraints: BoxConstraints(maxHeight: MediaQuery.of(ctx).size.height * 0.40),
            child: Obx(() {
              // ==================== REVIEW STEP ====================
              if (_aiCurrentStep.value == 'review') {
                return Column(
                  mainAxisSize: MainAxisSize.min,
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        const Text('Review AI Text', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
                        IconButton(icon: const Icon(Icons.close), onPressed: () => Navigator.pop(ctx)),
                      ],
                    ),
                    const SizedBox(height: 8),
                    const Text('Edit the generated text before applying:', style: TextStyle(color: Colors.black54)),
                    const SizedBox(height: 16),
                    Expanded(
                      child: ListView(
                        shrinkWrap: true,
                        keyboardDismissBehavior: ScrollViewKeyboardDismissBehavior.onDrag,
                        physics: const BouncingScrollPhysics(),
                        children: _aiGeneratedTexts.entries.map((e) {
                          bool isCaption = e.key.toLowerCase() == 'caption';
                          return Padding(
                            padding: const EdgeInsets.only(bottom: 8),
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                if (isCaption)
                                  Row(
                                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                    children: [
                                      const Text('Social Media Caption', 
                                          style: TextStyle(fontWeight: FontWeight.w600, color: Colors.black87, fontSize: 13)),
                                      InkWell(
                                        onTap: () {
                                          Clipboard.setData(ClipboardData(text: e.value.text));
                                          Get.snackbar('Copied', 'Caption copied to clipboard!', 
                                              snackPosition: SnackPosition.BOTTOM, backgroundColor: Colors.black87, colorText: Colors.white);
                                        },
                                        child: const Row(children: [
                                          Icon(Icons.copy, size: 14, color: Color(0xFF5538EE)),
                                          SizedBox(width: 4),
                                          Text('Copy', style: TextStyle(color: Color(0xFF5538EE), fontSize: 12, fontWeight: FontWeight.bold)),
                                        ]),
                                      ),
                                    ],
                                  ),
                                if (isCaption) const SizedBox(height: 4),
                                Focus(
                                  onFocusChange: (hasFocus) {
                                    if (hasFocus && !isCaption) {
                                      controller.selectLayer(e.key);
                                    }
                                  },
                                  child: TextField(
                                    controller: e.value,
                                    minLines: isCaption ? 1 : null,
                                    maxLines: isCaption ? 5 : null,
                                    onTap: () {
                                      if (!isCaption) {
                                        controller.selectLayer(e.key);
                                      }
                                    },
                                    decoration: InputDecoration(
                                      labelText: isCaption ? null : 'Replaces: ${e.key}',
                                      labelStyle: const TextStyle(fontSize: 12, color: Colors.black54),
                                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(8)),
                                      focusedBorder: OutlineInputBorder(
                                        borderRadius: BorderRadius.circular(8),
                                        borderSide: const BorderSide(color: Color(0xFF6366F1), width: 2),
                                      ),
                                      contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                                      fillColor: isCaption ? Colors.blue.shade50 : null,
                                      filled: isCaption,
                                    ),
                                  ),
                                ),
                              ],
                            ),
                          );
                        }).toList(),
                      ),
                    ),
                    const SizedBox(height: 16),
                    Row(
                      children: [
                        Expanded(
                          child: OutlinedButton(
                            style: OutlinedButton.styleFrom(
                              padding: const EdgeInsets.symmetric(vertical: 14),
                              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                            ),
                            onPressed: () => _aiCurrentStep.value = isHiring ? 'hiring_questionnaire' : 'menu',
                            child: const Text('Back'),
                          ),
                        ),
                        const SizedBox(width: 8),
                        Expanded(
                          child: OutlinedButton(
                            style: OutlinedButton.styleFrom(
                              padding: const EdgeInsets.symmetric(vertical: 14),
                              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                            ),
                            onPressed: () {
                              if (isGenerating.value) return;
                              _aiCurrentStep.value = isHiring ? 'hiring_questionnaire' : 'customPrompt';
                            },
                            child: const Text('\u{1F504} Retry'),
                          ),
                        ),
                        const SizedBox(width: 8),
                        Expanded(
                          flex: 2,
                          child: ElevatedButton(
                            style: ElevatedButton.styleFrom(
                              backgroundColor: const Color(0xFF5538EE),
                              padding: const EdgeInsets.symmetric(vertical: 14),
                              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                            ),
                            onPressed: () {
                              _aiGeneratedTexts.forEach((key, ctrl) {
                                if (key.toLowerCase() != 'caption' && ctrl.text.trim().isNotEmpty) {
                                  controller.updateLayerProperty(key, 'text', ctrl.text.trim());
                                }
                              });
                              controller.templateConfig.refresh();
                              Navigator.pop(ctx);
                              Get.snackbar('Success', 'AI text applied successfully', backgroundColor: Colors.green, colorText: Colors.white);
                            },
                            child: const Text('Apply to Design', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
                          ),
                        ),
                      ],
                    ),
                  ],
                );
              }

              // ==================== MENU STEP ====================
              if (_aiCurrentStep.value == 'menu') {
                return SingleChildScrollView(
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          const Text('AI Content Generator', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
                          IconButton(icon: const Icon(Icons.close), onPressed: () => Navigator.pop(ctx)),
                        ],
                      ),
                      const SizedBox(height: 16),
                      // Product Selection Header (Hide if Hiring)
                      if (!isHiring) StatefulBuilder(
                        builder: (context2, setStateBuilder) => GestureDetector(
                          onTap: () async {
                            final product = await _pickProductForAI();
                            if (product != null) {
                              setStateBuilder(() => _aiSelectedProduct = product);
                              setState(() => _aiSelectedProduct = product);
                            }
                          },
                          child: Container(
                            padding: const EdgeInsets.all(12),
                            decoration: BoxDecoration(
                              border: Border.all(color: Colors.grey.shade300),
                              borderRadius: BorderRadius.circular(12),
                              color: _aiSelectedProduct != null ? Colors.white : Colors.grey.shade50,
                            ),
                            child: _aiSelectedProduct != null 
                              ? Row(
                                  children: [
                                    ClipRRect(
                                      borderRadius: BorderRadius.circular(8),
                                      child: (_aiSelectedProduct!['_display_image'] ?? '').toString().isNotEmpty
                                          ? Image.network(
                                              _aiSelectedProduct!['_display_image'].toString(),
                                              width: 48, height: 48, fit: BoxFit.cover,
                                              errorBuilder: (c,e,s) => Container(width: 48, height: 48, color: Colors.grey.shade200, child: const Icon(Icons.image, color: Colors.grey)),
                                            )
                                          : Container(width: 48, height: 48, color: Colors.grey.shade200, child: const Icon(Icons.image, color: Colors.grey)),
                                    ),
                                    const SizedBox(width: 12),
                                    Expanded(
                                      child: Column(
                                        crossAxisAlignment: CrossAxisAlignment.start,
                                        children: [
                                          Text(
                                            _aiSelectedProduct!['_display_name'] ?? _aiSelectedProduct!['title'] ?? _aiSelectedProduct!['name'] ?? 'Selected Product',
                                            style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
                                            maxLines: 1, overflow: TextOverflow.ellipsis,
                                          ),
                                          if ((_aiSelectedProduct!['_display_category'] ?? _aiSelectedProduct!['category_name'] ?? '').toString().isNotEmpty)
                                            Text(_aiSelectedProduct!['_display_category'] ?? _aiSelectedProduct!['category_name'] ?? '', 
                                              style: const TextStyle(color: Colors.grey, fontSize: 12)),
                                          if ((_aiSelectedProduct!['_display_price'] ?? _aiSelectedProduct!['price'] ?? '').toString().isNotEmpty)
                                            Text('\u20B9${_aiSelectedProduct!['_display_price'] ?? _aiSelectedProduct!['price']}', style: const TextStyle(color: Colors.green, fontWeight: FontWeight.w600, fontSize: 14)),
                                        ],
                                      ),
                                    ),
                                    const Icon(Icons.swap_horiz, color: Colors.black54),
                                  ],
                                )
                              : Row(
                                  children: [
                                    Container(
                                      width: 40, height: 40,
                                      decoration: BoxDecoration(color: Colors.grey.shade200, borderRadius: BorderRadius.circular(8)),
                                      child: const Icon(Icons.add_shopping_cart, color: Colors.black54),
                                    ),
                                    const SizedBox(width: 12),
                                    const Expanded(
                                      child: Text('Select a Product (Optional)', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 15)),
                                    ),
                                    const Icon(Icons.arrow_drop_down),
                                  ],
                                ),
                          ),
                        ),
                      ),
                      if (!isHiring) const SizedBox(height: 24),
                      // Tier 2: Enhance with AI
                      _buildAiOptionTile(
                        icon: Icons.auto_awesome,
                        title: 'Enhance with AI',
                        subtitle: 'Make text more professional (1 credit)',
                        color: const Color(0xFF5538EE),
                        onTap: () {
                          if (_aiSelectedProduct == null) {
                            Get.snackbar('Product Required', 'Please select a product first.');
                            return;
                          }
                          _performTier2Generation(isGenerating);
                        },
                      ),
                      const SizedBox(height: 12),
                      // Tier 3: Custom Prompt
                      _buildAiOptionTile(
                        icon: Icons.edit_note,
                        title: 'Custom Prompt',
                        subtitle: 'Write your own instructions (1 credit)',
                        color: Colors.orange,
                        onTap: () => _aiCurrentStep.value = 'customPrompt',
                      ),
                      if (isGenerating.value)
                        const Padding(
                          padding: EdgeInsets.only(top: 24.0),
                          child: Center(child: CircularProgressIndicator()),
                        ),
                    ],
                  ),
                );
              }

              // ==================== HIRING QUESTIONNAIRE STEP ====================
              if (_aiCurrentStep.value == 'hiring_questionnaire') {
                return SingleChildScrollView(
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          const Text('AI Hiring Post Generator', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
                          IconButton(icon: const Icon(Icons.close), onPressed: () => Navigator.pop(ctx)),
                        ],
                      ),
                      const SizedBox(height: 16),
                      const Text('आप किस Role/Post के लिए हायर कर रहे हैं?', style: TextStyle(fontSize: 14, fontWeight: FontWeight.w600, color: Colors.black87)),
                      const SizedBox(height: 8),
                      TextField(
                        controller: _aiHiringRoleController,
                        decoration: InputDecoration(
                          hintText: 'e.g., Graphic Designer, Sales Executive...',
                          border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                          contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                        ),
                      ),
                      if (askRequirements) ...[
                        const SizedBox(height: 16),
                        const Text('कोई खास Requirement या Salary?', style: TextStyle(fontSize: 14, fontWeight: FontWeight.w600, color: Colors.black87)),
                        const SizedBox(height: 8),
                        TextField(
                          controller: _aiHiringReqController,
                          decoration: InputDecoration(
                            hintText: 'e.g., 2 years experience, 25k salary...',
                            border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                            contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                          ),
                        ),
                      ],
                      const SizedBox(height: 16),
                      const Text('Language:', style: TextStyle(fontSize: 13, color: Colors.black54)),
                      const SizedBox(height: 8),
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 12),
                        decoration: BoxDecoration(
                          border: Border.all(color: Colors.grey.shade400),
                          borderRadius: BorderRadius.circular(12),
                        ),
                        child: DropdownButtonHideUnderline(
                          child: DropdownButton<String>(
                            isExpanded: true,
                            value: _aiSelectedLanguage.value,
                            items: languages.map((l) => DropdownMenuItem(value: l, child: Text(l))).toList(),
                            onChanged: (val) {
                              if (val != null) _aiSelectedLanguage.value = val;
                            },
                          ),
                        ),
                      ),
                      const SizedBox(height: 24),
                      SizedBox(
                        width: double.infinity,
                        child: ElevatedButton(
                          style: ElevatedButton.styleFrom(
                            backgroundColor: const Color(0xFF5538EE),
                            padding: const EdgeInsets.symmetric(vertical: 16),
                            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                          ),
                          onPressed: isGenerating.value ? null : () async {
                            if (_aiHiringRoleController.text.trim().isEmpty) {
                              Get.snackbar('Error', 'Please enter a role/post.');
                              return;
                            }
                            
                            // Build the prompt based on context
                            String contextPrompt = "Generate a concise and catchy hiring post text for the role of '${_aiHiringRoleController.text.trim()}'.";
                            if (askRequirements && _aiHiringReqController.text.trim().isNotEmpty) {
                              contextPrompt += " Important requirements/salary: '${_aiHiringReqController.text.trim()}'.";
                            }
                            
                            // Extract existing text from template to provide context
                            final existingTexts = layers
                              .where((l) => l['type'] == 'text' && (l['text'] ?? '').toString().trim().isNotEmpty)
                              .map((l) => l['text'])
                              .toList();
                            
                            if (existingTexts.isNotEmpty) {
                              contextPrompt += " The text must seamlessly replace or complement this existing template content: ${existingTexts.join(', ')}. Keep it short enough to fit the design constraints.";
                            }
                            
                            isGenerating.value = true;
                            final content = await controller.generateAIText(
                              contextPrompt, 
                              _aiSelectedLanguage.value,
                            );
                            isGenerating.value = false;
                            
                            if (content != null && content.isNotEmpty) {
                              _aiGeneratedTexts.clear();
                              content.forEach((k, v) {
                                _aiGeneratedTexts[k] = TextEditingController(text: v.toString());
                              });
                              _aiCurrentStep.value = 'review';
                            } else {
                              Get.snackbar('Notice', 'Could not generate text. Ensure template has text layers.', 
                                  backgroundColor: Colors.orange, colorText: Colors.white);
                            }
                          },
                          child: isGenerating.value 
                              ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                              : const Text('Generate with AI', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 15)),
                        ),
                      ),
                    ],
                  ),
                );
              }

              // ==================== CUSTOM PROMPT STEP ====================
              return SingleChildScrollView(
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        IconButton(
                          icon: const Icon(Icons.arrow_back),
                          padding: EdgeInsets.zero,
                          alignment: Alignment.centerLeft,
                          onPressed: () => _aiCurrentStep.value = isHiring ? 'hiring_questionnaire' : 'menu',
                        ),
                        const Expanded(child: Text('Custom Prompt', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold))),
                        IconButton(icon: const Icon(Icons.close), onPressed: () => Navigator.pop(ctx)),
                      ],
                    ),
                    const SizedBox(height: 16),
                    const Text('Describe what you want the AI to write:', style: TextStyle(fontSize: 13, color: Colors.black54)),
                    const SizedBox(height: 8),
                    TextField(
                      controller: _aiPromptController,
                      maxLines: 3,
                      decoration: InputDecoration(
                        hintText: 'E.g., Write a promotional text for a new coffee shop...',
                        border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                        contentPadding: const EdgeInsets.all(12),
                      ),
                    ),
                    const SizedBox(height: 16),
                    const Text('Output Language:', style: TextStyle(fontSize: 13, color: Colors.black54)),
                    const SizedBox(height: 8),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 12),
                      decoration: BoxDecoration(
                        border: Border.all(color: Colors.grey.shade400),
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: DropdownButtonHideUnderline(
                        child: DropdownButton<String>(
                          isExpanded: true,
                          value: _aiSelectedLanguage.value,
                          items: languages.map((l) => DropdownMenuItem(value: l, child: Text(l))).toList(),
                          onChanged: (val) {
                            if (val != null) _aiSelectedLanguage.value = val;
                          },
                        ),
                      ),
                    ),

                    const SizedBox(height: 24),
                    SizedBox(
                      width: double.infinity,
                      child: ElevatedButton(
                        style: ElevatedButton.styleFrom(
                          backgroundColor: const Color(0xFF5538EE),
                          padding: const EdgeInsets.symmetric(vertical: 16),
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                        ),
                        onPressed: isGenerating.value ? null : () async {
                          if (_aiPromptController.text.trim().isEmpty) {
                            Get.snackbar('Error', 'Please enter a prompt first.');
                            return;
                          }
                          isGenerating.value = true;
                          final content = await controller.generateAIText(
                            _aiPromptController.text.trim(), 
                            _aiSelectedLanguage.value,
                            product: _aiSelectedProduct,
                          );
                          isGenerating.value = false;
                          if (content != null && content.isNotEmpty) {
                            _aiGeneratedTexts.clear();
                            content.forEach((k, v) {
                              _aiGeneratedTexts[k] = TextEditingController(text: v.toString());
                            });
                            _aiCurrentStep.value = 'review';
                          } else {
                            Get.snackbar('Notice', 'Could not generate text. Ensure template has text layers.', 
                                backgroundColor: Colors.orange, colorText: Colors.white);
                          }
                        },
                        child: isGenerating.value 
                            ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                            : const Text('Generate Content', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 15)),
                      ),
                    ),
                  ],
                ),
              );
            }),
          ),
        ),
      ),
    );
  }

  // ==================== AI HELPER METHODS ====================

  Widget _buildAiOptionTile({required IconData icon, required String title, required String subtitle, required Color color, required VoidCallback onTap}) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(12),
      child: Container(
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(
          border: Border.all(color: Colors.grey.shade200),
          borderRadius: BorderRadius.circular(12),
        ),
        child: Row(
          children: [
            Container(
              padding: const EdgeInsets.all(10),
              decoration: BoxDecoration(color: color.withOpacity(0.1), shape: BoxShape.circle),
              child: Icon(icon, color: color),
            ),
            const SizedBox(width: 16),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(title, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15)),
                  const SizedBox(height: 2),
                  Text(subtitle, style: TextStyle(color: Colors.grey.shade600, fontSize: 12)),
                ],
              ),
            ),
            const Icon(Icons.chevron_right, color: Colors.grey),
          ],
        ),
      ),
    );
  }

  void _performTier1Generation() {
    if (_aiSelectedProduct == null) return;
    final product = _aiSelectedProduct!;
    final pName = (product['_display_name'] ?? product['title'] ?? product['name'] ?? '').toString();
    final pDesc = (product['description'] ?? product['short_description'] ?? '').toString();
    final pPrice = (product['_display_price'] ?? product['price'] ?? '').toString();
    final imgUrl = (product['_display_image'] ?? product['image_url'] ?? product['processed_url'] ?? product['image'] ?? '').toString();
    final fullUrl = imgUrl.startsWith('http') ? imgUrl : (imgUrl.isNotEmpty ? '${controller.uploadsBaseUrl}/$imgUrl' : '');

    final layers = controller.templateConfig['layers'] as List<dynamic>? ?? [];
    bool imageMapped = false;

    for (var layer in layers) {
      final lName = (layer['name'] ?? '').toString().toLowerCase();
      if (layer['type'] == 'text') {
        if (lName == 'title' || lName == 'heading1' || lName.startsWith('title')) {
          controller.updateLayerProperty(layer['name'], 'text', pName);
        } else if (lName == 'description' || lName == 'desc') {
          controller.updateLayerProperty(layer['name'], 'text', pDesc);
        } else if (lName == 'price' || lName.contains('price')) {
          controller.updateLayerProperty(layer['name'], 'text', pPrice);
        }
      } else if (layer['type'] == 'image' && !imageMapped) {
        if ((lName == 'image1' || lName.contains('product')) && layer['is_background'] != true) {
          controller.updateLayerProperty(layer['name'], 'src', fullUrl);
          imageMapped = true;
        }
      }
    }
    controller.templateConfig.refresh();
    Get.snackbar('Smart Generate', 'Product details applied instantly!', backgroundColor: Colors.green, colorText: Colors.white);
  }

  Future<void> _performTier2Generation(RxBool isGenerating) async {
    isGenerating.value = true;
    final product = _aiSelectedProduct!;
    final pName = (product['_display_name'] ?? product['title'] ?? product['name'] ?? '').toString();
    final pDesc = (product['description'] ?? product['short_description'] ?? '').toString();
    final autoPrompt = 'Write compelling marketing text for this product that perfectly aligns with the template\'s purpose (e.g. sale, hiring, festival, etc). Highlight the key features.';
    _aiPromptController.text = autoPrompt;

    final content = await controller.generateAIText(autoPrompt, _aiSelectedLanguage.value, product: product);
    isGenerating.value = false;
    if (content != null && content.isNotEmpty) {
      _aiGeneratedTexts.clear();
      content.forEach((k, v) {
        _aiGeneratedTexts[k] = TextEditingController(text: v.toString());
      });
      _aiCurrentStep.value = 'review';
    } else {
      Get.snackbar('Notice', 'Could not generate enhanced text.', backgroundColor: Colors.orange, colorText: Colors.white);
    }
  }

  Future<Map<String, dynamic>?> _pickProductForAI() async {
    Map<String, dynamic>? selected;
    await showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (sheetCtx) {
        return Container(
          height: MediaQuery.of(context).size.height * 0.75,
          decoration: const BoxDecoration(
            color: Color(0xFFF8FAFC),
            borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
          ),
          child: Column(
            children: [
              // Drag Handle
              Container(
                margin: const EdgeInsets.only(top: 12),
                width: 40, height: 4,
                decoration: BoxDecoration(color: Colors.grey.shade300, borderRadius: BorderRadius.circular(2)),
              ),
              // Header
              Padding(
                padding: const EdgeInsets.fromLTRB(20, 16, 20, 12),
                child: Row(
                  children: [
                    Container(
                      width: 40, height: 40,
                      decoration: BoxDecoration(
                        gradient: const LinearGradient(colors: [Color(0xFF6366F1), Color(0xFF9333EA)]),
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: const Icon(Icons.inventory_2, color: Colors.white, size: 22),
                    ),
                    const SizedBox(width: 12),
                    const Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text('Select Product', style: TextStyle(fontSize: 18, fontWeight: FontWeight.w800, color: Color(0xFF1E293B))),
                          Text('Choose a product for AI content', style: TextStyle(fontSize: 12, color: Colors.grey)),
                        ],
                      ),
                    ),
                    IconButton(icon: const Icon(Icons.close, color: Color(0xFF94A3B8)), onPressed: () => Navigator.pop(sheetCtx)),
                  ],
                ),
              ),
              const SizedBox(height: 8),
              // Product List
              Expanded(
                child: FutureBuilder<http.Response>(
                  future: ApiService.getUserProducts(),
                  builder: (context, snapshot) {
                    if (snapshot.connectionState == ConnectionState.waiting) {
                      return const Center(child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          CircularProgressIndicator(color: Color(0xFF6366F1)),
                          SizedBox(height: 16),
                          Text('Loading your products...', style: TextStyle(color: Colors.grey)),
                        ],
                      ));
                    }
                    if (!snapshot.hasData) return const Center(child: Text('Failed to load products'));
                    try {
                      final body = jsonDecode(snapshot.data!.body);
                      // Debug: print actual API response keys
                      debugPrint('[ProductPickerAI] API keys: ${body.keys.toList()}');
                      
                      // Try multiple possible data structures
                      List products = [];
                      if (body['products'] != null && body['products']['data'] != null) {
                        products = body['products']['data'];
                      } else if (body['data'] != null && body['data'] is List) {
                        products = body['data'];
                      } else if (body['data'] != null && body['data']['data'] != null) {
                        products = body['data']['data'];
                      }
                      
                      debugPrint('[ProductPickerAI] Found ${products.length} products');
                      if (products.isNotEmpty) {
                        debugPrint('[ProductPickerAI] First product keys: ${(products.first as Map).keys.toList()}');
                        debugPrint('[ProductPickerAI] First product: ${products.first}');
                      }
                      
                      if (products.isEmpty) {
                        return Center(child: Column(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Icon(Icons.inventory_2_outlined, size: 56, color: Colors.grey.shade300),
                            const SizedBox(height: 12),
                            const Text('No products found', style: TextStyle(color: Colors.grey, fontWeight: FontWeight.w600)),
                            const SizedBox(height: 4),
                            const Text('Add products from My Business section', style: TextStyle(color: Colors.grey, fontSize: 12)),
                          ],
                        ));
                      }
                      
                      return ListView.builder(
                        padding: const EdgeInsets.symmetric(horizontal: 16),
                        itemCount: products.length,
                        itemBuilder: (context, index) {
                          final product = products[index];
                          
                          // Try all possible field names for image
                          final imgUrl = (product['image_url'] ?? product['processed_url'] ?? product['image'] ?? '').toString();
                          final fullUrl = imgUrl.isEmpty ? '' : (imgUrl.startsWith('http') ? imgUrl : '${controller.uploadsBaseUrl}/$imgUrl');
                          
                          // Try all possible field names for title/name
                          final productName = (product['title'] ?? product['name'] ?? product['sku'] ?? 'Product ${index + 1}').toString();
                          final categoryName = (product['category_name'] ?? product['category'] ?? '').toString();
                          final price = product['price']?.toString() ?? '';
                          
                          return GestureDetector(
                            onTap: () {
                              selected = Map<String, dynamic>.from(product);
                              // Ensure we normalize the keys for downstream usage
                              selected!['_display_name'] = productName;
                              selected!['_display_image'] = fullUrl;
                              selected!['_display_category'] = categoryName;
                              selected!['_display_price'] = price;
                              Navigator.pop(sheetCtx);
                            },
                            child: Container(
                              margin: const EdgeInsets.only(bottom: 10),
                              padding: const EdgeInsets.all(14),
                              decoration: BoxDecoration(
                                color: Colors.white,
                                borderRadius: BorderRadius.circular(16),
                                border: Border.all(color: const Color(0xFFF1F5F9)),
                                boxShadow: const [BoxShadow(color: Color(0x08000000), blurRadius: 4, offset: Offset(0, 1))],
                              ),
                              child: Row(
                                children: [
                                  // Product Image
                                  Container(
                                    width: 56, height: 56,
                                    decoration: BoxDecoration(
                                      borderRadius: BorderRadius.circular(14),
                                      color: const Color(0xFFF8FAFC),
                                      border: Border.all(color: const Color(0xFFE2E8F0)),
                                    ),
                                    child: ClipRRect(
                                      borderRadius: BorderRadius.circular(14),
                                      child: fullUrl.isNotEmpty
                                          ? Image.network(fullUrl, fit: BoxFit.cover,
                                              errorBuilder: (c,e,s) => const Icon(Icons.inventory_2_outlined, color: Color(0xFF94A3B8), size: 24))
                                          : const Icon(Icons.inventory_2_outlined, color: Color(0xFF94A3B8), size: 24),
                                    ),
                                  ),
                                  const SizedBox(width: 14),
                                  // Product Info
                                  Expanded(
                                    child: Column(
                                      crossAxisAlignment: CrossAxisAlignment.start,
                                      children: [
                                        Text(productName,
                                          style: const TextStyle(fontSize: 15, fontWeight: FontWeight.w700, color: Color(0xFF1E293B)),
                                          maxLines: 1, overflow: TextOverflow.ellipsis),
                                        if (categoryName.isNotEmpty) ...[
                                          const SizedBox(height: 3),
                                          Text(categoryName,
                                            style: const TextStyle(fontSize: 12, color: Color(0xFF94A3B8), fontWeight: FontWeight.w500)),
                                        ],
                                        if (price.isNotEmpty) ...[
                                          const SizedBox(height: 2),
                                          Text('\u20B9$price',
                                            style: const TextStyle(fontSize: 13, color: Colors.green, fontWeight: FontWeight.w600)),
                                        ],
                                      ],
                                    ),
                                  ),
                                  // Arrow
                                  Container(
                                    width: 32, height: 32,
                                    decoration: BoxDecoration(
                                      color: const Color(0xFFF8FAFC), shape: BoxShape.circle,
                                      border: Border.all(color: const Color(0xFFE2E8F0)),
                                    ),
                                    child: const Icon(Icons.arrow_forward_ios, size: 12, color: Color(0xFF94A3B8)),
                                  ),
                                ],
                              ),
                            ),
                          );
                        },
                      );
                    } catch (e) {
                      debugPrint('[ProductPickerAI] Error: $e');
                      return Center(child: Text('Error loading products: $e'));
                    }
                  },
                ),
              ),
            ],
          ),
        );
      },
    );
    return selected;

  }

  void _showProductSelectionForSlot(String slotName) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (sheetCtx) {
        return Container(
          height: MediaQuery.of(context).size.height * 0.6,
          padding: const EdgeInsets.all(16),
          decoration: const BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text('Select Product for $slotName', style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
              const SizedBox(height: 12),
              Expanded(
                child: FutureBuilder<http.Response>(
                  future: ApiService.getUserProducts(),
                  builder: (context, snapshot) {
                    if (snapshot.connectionState == ConnectionState.waiting) return const Center(child: CircularProgressIndicator());
                    if (!snapshot.hasData) return const Center(child: Text('Failed'));
                    try {
                      final body = jsonDecode(snapshot.data!.body);
                      final List products = body['data'] ?? body['products']?['data'] ?? [];
                      if (products.isEmpty) return const Center(child: Text('No products found'));
                      return ListView.builder(
                        itemCount: products.length,
                        itemBuilder: (context, index) {
                          final product = products[index];
                          final imgUrl = (product['processed_url'] ?? product['image'] ?? '').toString();
                          final fullUrl = imgUrl.startsWith('http') ? imgUrl : '${controller.uploadsBaseUrl}/$imgUrl';
                          return ListTile(
                            leading: ClipRRect(
                              borderRadius: BorderRadius.circular(4),
                              child: Image.network(fullUrl, width: 50, height: 50, fit: BoxFit.cover,
                                  errorBuilder: (c,e,s) => const Icon(Icons.image)),
                            ),
                            title: Text(product['name'] ?? ''),
                            onTap: () {
                              _aiUploadedImages[slotName] = fullUrl;
                              controller.updateLayerProperty(slotName, 'src', fullUrl);
                              controller.templateConfig.refresh();
                              Navigator.pop(sheetCtx);
                            },
                          );
                        },
                      );
                    } catch (e) {
                      return const Center(child: Text('Error'));
                    }
                  },
                ),
              ),
            ],
          ),
        );
      },
    );
  }



  Widget _buildFieldBadge(String text, String layerName, {bool alwaysBlue = false}) {
    if (alwaysBlue) {
      return GestureDetector(
        onTap: () {},
        child: Container(
          margin: const EdgeInsets.only(right: 8),
          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
          decoration: BoxDecoration(
            color: const Color(0xFF5538EE),
            borderRadius: BorderRadius.circular(8),
          ),
          child: Text(
            text,
            style: const TextStyle(
              color: Colors.white,
              fontWeight: FontWeight.bold,
              fontSize: 11,
            ),
          ),
        ),
      );
    }
    
    return Obx(() {
      final _layerUpdate = controller.layerUpdateTrigger.value;
      final _ = controller.templateConfig.length; // Ensure GetX tracks this scope
      final isVisible = controller.isLayerVisible(layerName);
      return GestureDetector(
        onTap: () {
          controller.toggleVisibility(layerName, !isVisible);
        },
        child: Container(
          margin: const EdgeInsets.only(right: 8),
          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
          decoration: BoxDecoration(
            color: isVisible ? const Color(0xFF5538EE) : const Color(0xFFF3F4F6),
            borderRadius: BorderRadius.circular(8),
          ),
          child: Text(
            text,
            style: TextStyle(
              color: isVisible ? Colors.white : const Color(0xFF6B7280),
              fontWeight: FontWeight.bold,
              fontSize: 11,
            ),
          ),
        ),
      );
    });
  }

  Widget _buildIconBadge(IconData icon, String layerName) {
    return Obx(() {
      final _layerUpdate = controller.layerUpdateTrigger.value;
      final _ = controller.templateConfig.length; // Ensure GetX tracks this scope
      final isVisible = controller.isLayerVisible(layerName);
      return GestureDetector(
        onTap: () {
          controller.toggleVisibility(layerName, !isVisible);
        },
        child: Container(
          margin: const EdgeInsets.only(right: 6),
          padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 4),
          decoration: BoxDecoration(
            color: isVisible ? const Color(0xFF5538EE) : const Color(0xFFF3F4F6),
            borderRadius: BorderRadius.circular(6),
          ),
          child: Icon(icon, color: isVisible ? Colors.white : const Color(0xFF6B7280), size: 14),
        ),
      );
    });
  }

  void _showAddTextModal() {
    final textController = TextEditingController();
    double selectedSize = 48.0;

    showDialog(
      context: context,
      builder: (context) => StatefulBuilder(
        builder: (context, setState) => AlertDialog(
          title: const Text('Add Text'),
          content: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              TextField(
                controller: textController,
                autofocus: true,
                decoration: const InputDecoration(hintText: 'Enter text here'),
              ),
              const SizedBox(height: 20),
              Text('Font Size: ${selectedSize.toInt()}'),
              Slider(
                value: selectedSize,
                min: 12,
                max: 120,
                divisions: 108,
                label: selectedSize.round().toString(),
                onChanged: (value) {
                  setState(() {
                    selectedSize = value;
                  });
                },
              ),
            ],
          ),
          actions: [
            TextButton(onPressed: () => Navigator.pop(context), child: const Text('Cancel')),
            TextButton(
              onPressed: () {
                if (textController.text.trim().isNotEmpty) {
                  controller.addLayer({
                    'name': 'New Text ${DateTime.now().millisecondsSinceEpoch}',
                    'type': 'text',
                    'text': textController.text.trim(),
                    'size': selectedSize,
                    'color': '#000000',
                    'x': 540,
                    'y': 540,
                    'width': 400,
                    'height': 100,
                    'opacity': 1.0,
                    'z_index': 999,
                  });
                }
                Navigator.pop(context);
              },
              child: const Text('Add'),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildEditingToolsBar() {
    final bool isCustom = widget.type.toLowerCase().contains('custom');
    
    return Container(
      height: 75,
      margin: const EdgeInsets.only(bottom: 12),
      child: ListView(
        scrollDirection: Axis.horizontal,
        padding: const EdgeInsets.symmetric(horizontal: 16),
        children: [
          if (isCustom) _buildToolBtn(Icons.image, 'Logo', _pickAndAddImage),
          _buildToolBtn(Icons.text_fields, 'Text', _showAddTextModal),
          if (isCustom) _buildToolBtn(Icons.shopping_bag_outlined, 'Products', _showProductsModal),
          if (isCustom) _buildToolBtn(Icons.auto_awesome, 'AI Text', () => _showAiTextModal(context)),
          _buildToolBtn(Icons.emoji_emotions_outlined, 'Sticker', _showStickerModal),
          if (isCustom) _buildToolBtn(Icons.layers, 'Layers', () => _showLayersModal(context)),
        ],
      ),
    );
  }

  Widget _buildToolBtn(IconData icon, String label, VoidCallback onTap, {Color? iconColor, bool isSelected = false}) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        margin: const EdgeInsets.only(right: 20),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Container(
              padding: const EdgeInsets.all(14),
              decoration: BoxDecoration(
                color: isSelected ? const Color(0xFF5538EE) : Colors.white,
                shape: BoxShape.circle,
                boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.06), blurRadius: 12, spreadRadius: 2)],
              ),
              child: Icon(icon, color: isSelected ? Colors.white : (iconColor ?? const Color(0xFF5538EE)), size: 24),
            ),
            const SizedBox(height: 6),
            Text(label, style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w600, color: Colors.black87)),
          ],
        ),
      ),
    );
  }

  Future<void> _pickAndAddImage() async {
    final picker = ImagePicker();
    final pickedFile = await picker.pickImage(source: ImageSource.gallery);
    if (pickedFile != null) {
      controller.addLayer({
        'name': 'Logo ${DateTime.now().millisecondsSinceEpoch}',
        'type': 'image',
        'src': pickedFile.path,
        'isLocal': true,
        'x': 540,
        'y': 540,
        'width': 250,
        'height': 250,
        'opacity': 1.0,
        'z_index': 999,
        'isUserAdded': true,
      });
    }
  }

  void _showReplaceOptions() {
    final layerId = controller.selectedLayerId.value;
    if (layerId.isEmpty) return;

    showModalBottomSheet(
      context: context,
      shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(20))),
      builder: (context) => SafeArea(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Padding(
              padding: EdgeInsets.all(16.0),
              child: Text('Replace Image', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
            ),
            ListTile(
              leading: const Icon(Icons.photo_library),
              title: const Text('Choose from Gallery'),
              onTap: () async {
                Navigator.pop(context);
                final picker = ImagePicker();
                final pickedFile = await picker.pickImage(source: ImageSource.gallery);
                if (pickedFile != null) {
                  controller.updateLayerProperties(layerId, {
                    'src': pickedFile.path,
                    'isLocal': true,
                  });
                }
              },
            ),
            ListTile(
              leading: const Icon(Icons.auto_awesome_mosaic),
              title: const Text('Choose from Stickers'),
              onTap: () {
                Navigator.pop(context);
                _showStickerModal(replaceLayerId: layerId);
              },
            ),
            const SizedBox(height: 16),
          ],
        ),
      ),
    );
  }

  void _detachImage() {
    if (controller.selectedLayerId.value.isNotEmpty) {
      controller.updateLayerProperties(controller.selectedLayerId.value, {
        'src': '',
        'isLocal': false,
      });
    }
  }

  void _showProductsModal() {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (context) {
        return Container(
          height: MediaQuery.of(context).size.height * 0.7,
          padding: const EdgeInsets.all(16),
          decoration: const BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const Text('My Products', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
              const SizedBox(height: 16),
              Expanded(
                child: FutureBuilder<http.Response>(
                  future: ApiService.getUserProducts(),
                  builder: (context, snapshot) {
                    if (snapshot.connectionState == ConnectionState.waiting) {
                      return const Center(child: CircularProgressIndicator());
                    }
                    if (snapshot.hasError || !snapshot.hasData) {
                      return const Center(child: Text('Failed to load products'));
                    }
                    try {
                      final body = jsonDecode(snapshot.data!.body);
                      final List products = body['data'] ?? [];
                      if (products.isEmpty) {
                        return const Center(child: Text('No products found'));
                      }
                      return GridView.builder(
                        gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                          crossAxisCount: 3,
                          crossAxisSpacing: 10,
                          mainAxisSpacing: 10,
                        ),
                        itemCount: products.length,
                        itemBuilder: (context, index) {
                          final product = products[index];
                          final imgUrl = product['processed_url'] ?? product['image'];
                          final fullUrl = imgUrl.toString().startsWith('http') ? imgUrl : '${controller.uploadsBaseUrl}/$imgUrl';
                          return GestureDetector(
                            onTap: () {
                              controller.addLayer({
                                'name': 'Product ${product['id']}',
                                'type': 'image',
                                'src': fullUrl,
                                'x': 540,
                                'y': 540,
                                'width': 300,
                                'height': 300,
                                'opacity': 1.0,
                                'z_index': 999,
                                'isUserAdded': true,
                              });
                              Navigator.pop(context);
                            },
                            child: ClipRRect(
                              borderRadius: BorderRadius.circular(8),
                              child: Image.network(fullUrl, fit: BoxFit.cover),
                            ),
                          );
                        },
                      );
                    } catch (e) {
                      return const Center(child: Text('Error loading products'));
                    }
                  },
                ),
              ),
            ],
          ),
        );
      },
    );
  }



  void _showStickerModal({String? replaceLayerId}) {
    String searchQuery = '';
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (context) {
        return StatefulBuilder(
          builder: (context, setState) {
            return Container(
              height: MediaQuery.of(context).size.height * 0.7,
              padding: const EdgeInsets.all(16),
              decoration: const BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(replaceLayerId != null ? 'Choose Placeholder' : 'Stickers', style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
                  const SizedBox(height: 12),
                  TextField(
                    decoration: InputDecoration(
                      hintText: 'Search...',
                      prefixIcon: const Icon(Icons.search),
                      contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 0),
                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                    ),
                    onSubmitted: (val) {
                      setState(() {
                        searchQuery = val;
                      });
                    },
                  ),
                  const SizedBox(height: 16),
                  Expanded(
                    child: FutureBuilder<http.Response>(
                      future: searchQuery.isEmpty 
                          ? http.get(Uri.parse('${AppConfig.baseUrl}/get-sticker'))
                          : http.post(Uri.parse('${AppConfig.baseUrl}/search-sticker'), body: {'keyword': searchQuery}),
                      builder: (context, snapshot) {
                        if (snapshot.connectionState == ConnectionState.waiting) {
                          return const Center(child: CircularProgressIndicator());
                        }
                        if (snapshot.hasError || !snapshot.hasData) {
                          return const Center(child: Text('Failed to load stickers'));
                        }
                        try {
                          final body = jsonDecode(snapshot.data!.body);
                          List stickers = [];
                          
                          if (body['data'] != null && body['data'] is List) {
                            final dataList = body['data'] as List;
                            if (dataList.isNotEmpty && dataList[0] is Map && dataList[0].containsKey('stickerCategoryName')) {
                              for (var category in dataList) {
                                if (category['sticker'] != null && category['sticker'] is List) {
                                  stickers.addAll(category['sticker']);
                                }
                              }
                            } else {
                              stickers = dataList;
                            }
                          } else if (body['stickers'] != null && body['stickers'] is List) {
                            stickers = body['stickers'];
                          } else if (body is List) {
                            stickers = body;
                          } else if (body['data'] != null && body['data'] is Map && body['data']['StickerCategory'] != null) {
                            // Native app get-sticker format
                            for(var category in body['data']['StickerCategory']) {
                               if (category['sticker'] != null) {
                                  stickers.addAll(category['sticker']);
                               }
                            }
                          }

                          if (stickers.isEmpty) {
                            return const Center(child: Text('No stickers found'));
                          }
                          return GridView.builder(
                            gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                              crossAxisCount: 4,
                              crossAxisSpacing: 10,
                              mainAxisSpacing: 10,
                            ),
                            itemCount: stickers.length,
                            itemBuilder: (context, index) {
                              final sticker = stickers[index];
                              final imgUrl = sticker['stickerImage'] ?? sticker['image'] ?? sticker['url'] ?? '';
                              if (imgUrl.isEmpty) return const SizedBox.shrink();
                              
                              final fullUrl = imgUrl.toString().startsWith('http') ? imgUrl : '${controller.uploadsBaseUrl}/$imgUrl';
                              return GestureDetector(
                                onTap: () {
                                  if (replaceLayerId != null) {
                                    controller.updateLayerProperties(replaceLayerId, {
                                      'src': fullUrl,
                                      'isLocal': false,
                                    });
                                  } else {
                                    controller.addLayer({
                                      'name': 'Sticker ${DateTime.now().millisecondsSinceEpoch}',
                                      'type': 'image',
                                      'src': fullUrl,
                                      'x': 540,
                                      'y': 540,
                                      'width': 200,
                                      'height': 200,
                                      'opacity': 1.0,
                                      'z_index': 999,
                                      'isUserAdded': true,
                                    });
                                  }
                                  Navigator.pop(context);
                                },
                                child: ClipRRect(
                                  borderRadius: BorderRadius.circular(8),
                                  child: Image.network(
                                    fullUrl, 
                                    fit: BoxFit.contain,
                                    loadingBuilder: (BuildContext context, Widget child, ImageChunkEvent? loadingProgress) {
                                      if (loadingProgress == null) return child;
                                      return Center(
                                        child: CircularProgressIndicator(
                                          value: loadingProgress.expectedTotalBytes != null
                                              ? loadingProgress.cumulativeBytesLoaded / loadingProgress.expectedTotalBytes!
                                              : null,
                                        ),
                                      );
                                    },
                                    errorBuilder: (context, error, stackTrace) => const Icon(Icons.broken_image, color: Colors.grey),
                                  ),
                                ),
                              );
                            },
                          );
                        } catch (e) {
                          return const Center(child: Text('Error parsing stickers'));
                        }
                      },
                    ),
                  ),
                ],
              ),
            );
          }
        );
      },
    );
  }
}

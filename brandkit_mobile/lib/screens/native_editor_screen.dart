import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'dart:convert';
import '../controllers/native_editor_controller.dart';
import '../utils/app_colors.dart';
import '../widgets/editor_canvas_widget.dart';
import 'ai_chat_screen.dart';

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
    controller.initConfig(
      config,
      templateBaseUrl,
      widget.frameData['uploadsBaseUrl'] ?? '',
      widget.frameData['baseImgUrl'] ?? '',
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
      backgroundColor: const Color(0xFFF3F4F6),
      appBar: AppBar(
        backgroundColor: Colors.white,
        elevation: 0,
        leading: IconButton(
          icon: const Icon(Icons.close, color: Colors.black87, size: 24),
          onPressed: () => Navigator.pop(context),
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
                GestureDetector(
                  onTap: () => Navigator.pop(context),
                  child: const Icon(Icons.arrow_back_ios_new, size: 16, color: Colors.black87),
                ),
                const SizedBox(width: 8),
                Flexible(
                  child: Text(
                    _getTypeTitle(),
                    style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Colors.black87),
                    overflow: TextOverflow.ellipsis,
                  ),
                ),
                const SizedBox(width: 12),
                Container(
                  decoration: BoxDecoration(
                    color: const Color(0xFF3B28CC),
                    borderRadius: BorderRadius.circular(6),
                  ),
                  child: IconButton(
                    icon: const Icon(Icons.grid_view, color: Colors.white, size: 18),
                    onPressed: () => Navigator.pop(context),
                    constraints: const BoxConstraints(),
                    padding: const EdgeInsets.all(6),
                  ),
                ),
                const Spacer(),
                IconButton(
                  icon: const Icon(Icons.undo, color: Colors.black54, size: 22),
                  onPressed: () => controller.undo(),
                  constraints: const BoxConstraints(),
                  padding: const EdgeInsets.symmetric(horizontal: 8),
                ),
                IconButton(
                  icon: const Icon(Icons.redo, color: Colors.black54, size: 22),
                  onPressed: () => controller.redo(),
                  constraints: const BoxConstraints(),
                  padding: const EdgeInsets.symmetric(horizontal: 8),
                ),
                const SizedBox(width: 8),
                ElevatedButton(
                  style: ElevatedButton.styleFrom(
                    backgroundColor: const Color(0xFF3B28CC),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                    elevation: 0,
                    padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 0),
                    minimumSize: const Size(0, 36),
                  ),
                  onPressed: () {},
                  child: const Text('Download', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 13)),
                ),
              ],
            ),
          ),
          Expanded(
            child: GestureDetector(
              onTap: () {
                // Deselect layers when tapping outside
                controller.selectedLayerId.value = '';
              },
              child: Container(
                color: const Color(0xFFE2E8F0), // Match web canvas area background
                width: double.infinity,
                child: Center(
                  child: Padding(
                    padding: const EdgeInsets.all(20.0),
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
                      child: Obx(() {
                        if (controller.templateConfig.isEmpty) {
                          return const Center(child: Text('No template data found.'));
                        }
                        
                        // Calculate optimal width based on screen size minus padding
                        final maxWidth = MediaQuery.of(context).size.width - 40;

                        return EditorCanvasWidget(
                          config: Map<String, dynamic>.from(controller.templateConfig),
                          width: maxWidth,
                          uploadsBaseUrl: controller.uploadsBaseUrl,
                          templateBaseUrl: controller.templateBaseUrl,
                          baseImgUrl: controller.baseImgUrl,
                        );
                      }),
                    ),
                  ),
                ),
              ),
            ),
          ),
          // Bottom Area: Contextual OR Main tools
          Obx(() {
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

  Widget _buildContextualToolbar() {
    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
        boxShadow: [
          BoxShadow(color: Colors.black.withOpacity(0.1), blurRadius: 10, offset: const Offset(0, -5)),
        ],
      ),
      child: SafeArea(
        child: SingleChildScrollView(
          scrollDirection: Axis.horizontal,
          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 16),
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              _buildToolItem(Icons.edit_outlined, 'Edit', () {
                final layer = controller.templateConfig['layers'].firstWhere(
                    (l) => l['name'] == controller.selectedLayerId.value,
                    orElse: () => null);
                if (layer != null && layer['type'] == 'text') {
                  _showEditModal(layer);
                }
              }),
              _buildToolItem(Icons.open_with, 'Nudge', () {
                final layer = controller.templateConfig['layers'].firstWhere(
                    (l) => l['name'] == controller.selectedLayerId.value,
                    orElse: () => null);
                if (layer != null) {
                  _showNudgeModal(layer);
                }
              }),
              _buildToolItem(Icons.text_fields, 'Font', () {
                final layer = controller.templateConfig['layers'].firstWhere(
                    (l) => l['name'] == controller.selectedLayerId.value,
                    orElse: () => null);
                if (layer != null && layer['type'] == 'text') {
                  _showFontModal(layer);
                }
              }),
              _buildToolItem(Icons.format_size, 'Size', () {
                final layer = controller.templateConfig['layers'].firstWhere(
                    (l) => l['name'] == controller.selectedLayerId.value,
                    orElse: () => null);
                if (layer != null && layer['type'] == 'text') {
                  _showSizeModal(layer);
                }
              }),
              _buildToolItem(Icons.format_bold, 'Bold', () {
                final layer = controller.templateConfig['layers'].firstWhere(
                    (l) => l['name'] == controller.selectedLayerId.value,
                    orElse: () => null);
                if (layer != null && layer['type'] == 'text') {
                  final isBold = layer['weight'] == 'bold';
                  controller.updateLayerProperty(layer['name'], 'weight', isBold ? 'normal' : 'bold');
                }
              }),
              _buildToolItem(Icons.format_italic, 'Italic', () {
                final layer = controller.templateConfig['layers'].firstWhere(
                    (l) => l['name'] == controller.selectedLayerId.value,
                    orElse: () => null);
                if (layer != null && layer['type'] == 'text') {
                  final isItalic = layer['style'] == 'italic';
                  controller.updateLayerProperty(layer['name'], 'style', isItalic ? 'normal' : 'italic');
                }
              }),
              _buildToolItem(Icons.palette_outlined, 'Color', () {
                final layer = controller.templateConfig['layers'].firstWhere(
                    (l) => l['name'] == controller.selectedLayerId.value,
                    orElse: () => null);
                if (layer != null && layer['type'] == 'text') {
                  _showColorPickerModal(layer);
                }
              }, iconColor: const Color(0xFF7D2AE8)),
              _buildToolItem(Icons.layers_outlined, 'Layers', () {
                _showLayersModal(context);
              }),
              _buildToolItem(Icons.delete_outline, 'Delete', () {
                controller.deleteLayer(controller.selectedLayerId.value);
              }, iconColor: Colors.red),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildBottomTools() {
    final isCustomType = widget.type == 'custom' || widget.type == 'business_custom' || widget.type == 'business_custom_frame';
    
    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
        boxShadow: [
          BoxShadow(color: Colors.black.withOpacity(0.05), blurRadius: 10, offset: const Offset(0, -5)),
        ],
      ),
      child: SafeArea(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            // 1. Contact info badges — show for all templates
            SingleChildScrollView(
              scrollDirection: Axis.horizontal,
              padding: const EdgeInsets.all(16),
              child: Row(
                children: [
                  _buildFieldBadge('NAME', '_b_name'),
                  _buildFieldBadge('LOGO', '_b_logo'),
                  _buildIconBadge(Icons.phone_android, '_b_phone'),
                  _buildIconBadge(Icons.mail_outline, '_b_email'),
                  _buildIconBadge(Icons.location_on_outlined, '_b_address'),
                  _buildIconBadge(Icons.language, '_b_website'),
                  _buildFieldBadge('FRAME', '_frame_bg'),
                  _buildFieldBadge('FRAME COLOR', '_frame_color', alwaysBlue: true),
                ],
              ),
            ),
            
            // 2. Select Frame section — show for all templates
            Padding(
                padding: const EdgeInsets.symmetric(horizontal: 16),
                child: Row(
                  children: [
                    const Text('Select Frame', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
                    const SizedBox(width: 8),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 4),
                      decoration: BoxDecoration(
                        border: Border.all(color: Colors.grey.shade300),
                        borderRadius: BorderRadius.circular(8),
                      ),
                      child: const Row(
                        children: [
                          Text('All Themes', style: TextStyle(fontSize: 12)),
                          Icon(Icons.arrow_drop_down, size: 20),
                        ],
                      ),
                    ),
                  ],
                ),
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
                          final jsonStr = frame['json'] ?? frame['json_rules'];
                          if (jsonStr != null) {
                            try {
                              final Map<String, dynamic> config = jsonStr is String ? jsonDecode(jsonStr) : jsonStr;
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
                            borderRadius: BorderRadius.circular(12),
                            border: Border.all(color: Colors.grey.shade300),
                            image: thumbUrl.isNotEmpty
                                ? DecorationImage(image: NetworkImage(thumbUrl), fit: BoxFit.cover)
                                : null,
                          ),
                          child: thumbUrl.isEmpty ? const Center(child: Icon(Icons.image, color: Colors.grey)) : null,
                        ),
                      );
                    },
                  ),
                );
              }),
              const SizedBox(height: 12),
              
              // Toggle visibility button
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 16),
                child: GestureDetector(
                  onTap: () {
                    controller.toggleVisibility('_frame_bg', !controller.isLayerVisible('_frame_bg'));
                  },
                  child: Container(
                    width: double.infinity,
                    padding: const EdgeInsets.symmetric(vertical: 10),
                    decoration: BoxDecoration(
                      color: const Color(0xFFF5F3FF),
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: const Center(
                      child: Text(
                        'TOGGLE FRAME VISIBILITY',
                        style: TextStyle(color: Color(0xFF5538EE), fontWeight: FontWeight.bold, fontSize: 12),
                      ),
                    ),
                  ),
                ),
              ),
              const SizedBox(height: 12),
            

            // 3. Bottom Toolbar — match web toolbox styling
            SingleChildScrollView(
              scrollDirection: Axis.horizontal,
              padding: const EdgeInsets.only(left: 16, right: 16, bottom: 16, top: 8),
              child: Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  _buildToolItem(Icons.image_outlined, 'Add Logo', () {
                    controller.addLayer({
                      'name': 'New Logo ${DateTime.now().millisecondsSinceEpoch}',
                      'type': 'image',
                      'src': 'https://via.placeholder.com/150',
                      'x': 540,
                      'y': 540,
                      'width': 200,
                      'height': 200,
                      'opacity': 1.0,
                    });
                    Get.snackbar('Success', 'Added new logo layer');
                  }),
                  _buildToolItem(Icons.text_fields, 'Add Text', () {
                    _showAddTextModal();
                  }),
                  _buildToolItem(Icons.shopping_bag_outlined, 'Products', () {
                    Get.snackbar('Coming Soon', 'Product picker will be implemented here');
                  }),
                  _buildToolItem(Icons.auto_awesome, 'AI Text', () {
                    _showAiTextModal(context);
                  }, iconColor: Colors.green),
                  _buildToolItem(Icons.emoji_emotions_outlined, 'Sticker', () {
                    Get.snackbar('Coming Soon', 'Sticker picker will be implemented here');
                  }),
                  _buildToolItem(Icons.layers_outlined, 'Layers', () {
                    _showLayersModal(context);
                  }),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  void _showLayersModal(BuildContext context) {
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
                  final layers = List<dynamic>.from(controller.templateConfig['layers'] ?? []);
                  // Reverse so top layers show first
                  final reversedLayers = layers.reversed.toList();
                  return ReorderableListView.builder(
                    itemCount: reversedLayers.length,
                    onReorder: (oldIndex, newIndex) {
                      // Adjust indices because we reversed the list for display
                      final actualOld = layers.length - 1 - oldIndex;
                      var actualNew = layers.length - 1 - newIndex;
                      if (newIndex > oldIndex) {
                        actualNew += 1;
                      }
                      controller.moveLayer(reversedLayers[oldIndex]['name'], actualNew);
                    },
                    itemBuilder: (context, index) {
                      final layer = reversedLayers[index];
                      final isVisible = (layer['opacity'] ?? 1.0) > 0;
                      return ListTile(
                        key: ValueKey(layer['name']),
                        leading: Icon(layer['type'] == 'text' ? Icons.text_fields : Icons.image, color: Colors.grey),
                        title: Text(layer['name'] ?? 'Unnamed Layer'),
                        trailing: Row(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            IconButton(
                              icon: Icon(isVisible ? Icons.visibility : Icons.visibility_off, color: isVisible ? Colors.blue : Colors.grey),
                              onPressed: () => controller.toggleVisibility(layer['name'], !isVisible),
                            ),
                            const Icon(Icons.drag_handle, color: Colors.grey),
                          ],
                        ),
                      );
                    },
                  );
                }),
              ),
            ],
          ),
        ),
      ),
    );
  }

  void _showAiTextModal(BuildContext context) {
    final TextEditingController promptController = TextEditingController();
    final RxBool isGenerating = false.obs;

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (context) => Padding(
        padding: EdgeInsets.only(bottom: MediaQuery.of(context).viewInsets.bottom),
        child: ClipRRect(
          borderRadius: const BorderRadius.vertical(top: Radius.circular(20)),
          child: Container(
            padding: const EdgeInsets.all(24),
            color: Colors.white,
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text('AI Text Generator', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
                const SizedBox(height: 16),
                TextField(
                  controller: promptController,
                  maxLines: 3,
                  decoration: InputDecoration(
                    hintText: 'E.g., Write a promotional text for a new coffee shop...',
                    border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                  ),
                ),
                const SizedBox(height: 16),
                SizedBox(
                  width: double.infinity,
                  child: Obx(() => ElevatedButton(
                    style: ElevatedButton.styleFrom(
                      backgroundColor: const Color(0xFF5538EE),
                      padding: const EdgeInsets.symmetric(vertical: 16),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                    ),
                    onPressed: isGenerating.value ? null : () async {
                      if (promptController.text.trim().isEmpty) return;
                      isGenerating.value = true;
                      
                      // Using the emulator localhost URL
                      final success = await controller.generateAIText(
                        promptController.text.trim(), 
                        'http://10.0.2.2/Artera'
                      );
                      
                      isGenerating.value = false;
                      if (success) {
                        Navigator.pop(context);
                      } else {
                        Get.snackbar('Error', 'Failed to generate text', backgroundColor: Colors.red, colorText: Colors.white);
                      }
                    },
                    child: isGenerating.value 
                        ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                        : const Text('Generate & Apply', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
                  )),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildFieldBadge(String text, String layerName, {bool alwaysBlue = false}) {
    return Obx(() {
      final isVisible = alwaysBlue || controller.isLayerVisible(layerName);
      return GestureDetector(
        onTap: () {
          if (!alwaysBlue) {
            controller.toggleVisibility(layerName, !isVisible);
          }
        },
        child: Container(
          margin: const EdgeInsets.only(right: 8),
          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
          decoration: BoxDecoration(
            color: isVisible ? const Color(0xFF5538EE) : const Color(0xFFF3F4F6),
            borderRadius: BorderRadius.circular(12),
          ),
          child: Text(
            text,
            style: TextStyle(
              color: isVisible ? Colors.white : const Color(0xFF6B7280),
              fontWeight: FontWeight.bold,
              fontSize: 12,
            ),
          ),
        ),
      );
    });
  }

  Widget _buildIconBadge(IconData icon, String layerName) {
    return Obx(() {
      final isVisible = controller.isLayerVisible(layerName);
      return GestureDetector(
        onTap: () {
          controller.toggleVisibility(layerName, !isVisible);
        },
        child: Container(
          margin: const EdgeInsets.only(right: 8),
          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
          decoration: BoxDecoration(
            color: isVisible ? const Color(0xFF5538EE) : const Color(0xFFF3F4F6),
            borderRadius: BorderRadius.circular(12),
          ),
          child: Icon(icon, color: isVisible ? Colors.white : const Color(0xFF6B7280), size: 20),
        ),
      );
    });
  }

  Widget _buildToolItem(IconData icon, String label, VoidCallback onTap, {Color? iconColor}) {
    return InkWell(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 4),
        constraints: const BoxConstraints(minWidth: 64),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: const Color(0xFFF1F5F9), // Slate 100
                borderRadius: BorderRadius.circular(12),
              ),
              child: Icon(icon, color: const Color(0xFF0F172A), size: 24),
            ),
            const SizedBox(height: 6),
            Text(label, style: const TextStyle(fontSize: 10, color: Color(0xFF4B5563), fontWeight: FontWeight.w600)),
          ],
        ),
      ),
    );
  }

  void _showAddTextModal() {
    final TextEditingController textController = TextEditingController();
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Add Text'),
        content: TextField(
          controller: textController,
          autofocus: true,
          decoration: const InputDecoration(hintText: 'Enter text here'),
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
                  'size': 48,
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
    );
  }

  void _showEditModal(Map<String, dynamic> layer) {
    final TextEditingController textController = TextEditingController(text: layer['text'] ?? '');
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Edit Text'),
        content: TextField(
          controller: textController,
          autofocus: true,
          maxLines: 3,
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(context), child: const Text('Cancel')),
          TextButton(
            onPressed: () {
              controller.updateLayerProperty(layer['name'], 'text', textController.text);
              Navigator.pop(context);
            },
            child: const Text('Save'),
          ),
        ],
      ),
    );
  }

  void _showFontModal(Map<String, dynamic> layer) {
    // Basic list of available fonts
    final List<String> fonts = [
      'Roboto', 'Open Sans', 'Lato', 'Montserrat', 'Oswald',
      'Playfair Display', 'Merriweather', 'Nunito', 'Poppins', 'Raleway'
    ];
    
    showModalBottomSheet(
      context: context,
      builder: (context) {
        return Container(
          padding: const EdgeInsets.all(16),
          height: 300,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const Text('Select Font', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
              const SizedBox(height: 16),
              Expanded(
                child: ListView.builder(
                  itemCount: fonts.length,
                  itemBuilder: (context, index) {
                    final font = fonts[index];
                    return ListTile(
                      title: Text(font, style: TextStyle(fontFamily: font)),
                      trailing: layer['fontFamily'] == font ? const Icon(Icons.check, color: Colors.blue) : null,
                      onTap: () {
                        controller.updateLayerProperty(layer['name'], 'fontFamily', font);
                        Navigator.pop(context);
                      },
                    );
                  },
                ),
              ),
            ],
          ),
        );
      },
    );
  }

  void _showSizeModal(Map<String, dynamic> layer) {
    double currentSize = (layer['size'] ?? 48.0).toDouble();
    
    showModalBottomSheet(
      context: context,
      builder: (context) {
        return StatefulBuilder(
          builder: (context, setState) {
            return Container(
              padding: const EdgeInsets.all(24),
              height: 200,
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text('Text Size: ${currentSize.toInt()}', style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
                  const SizedBox(height: 24),
                  Row(
                    children: [
                      const Icon(Icons.text_fields, size: 16),
                      Expanded(
                        child: Slider(
                          value: currentSize,
                          min: 8.0,
                          max: 150.0,
                          onChanged: (val) {
                            setState(() => currentSize = val);
                            controller.updateLayerProperty(layer['name'], 'size', val);
                          },
                        ),
                      ),
                      const Icon(Icons.text_fields, size: 32),
                    ],
                  ),
                ],
              ),
            );
          }
        );
      },
    );
  }

  void _showColorPickerModal(Map<String, dynamic> layer) {
    final List<Color> colors = [
      Colors.black, Colors.white, Colors.red, Colors.pink, Colors.purple,
      Colors.deepPurple, Colors.indigo, Colors.blue, Colors.lightBlue, Colors.cyan,
      Colors.teal, Colors.green, Colors.lightGreen, Colors.lime, Colors.yellow,
      Colors.amber, Colors.orange, Colors.deepOrange, Colors.brown, Colors.grey,
    ];
    
    // helper to format color to hex
    String colorToHex(Color color) {
      return '#${color.value.toRadixString(16).padLeft(8, '0').substring(2)}';
    }

    showModalBottomSheet(
      context: context,
      builder: (context) {
        return Container(
          padding: const EdgeInsets.all(16),
          height: 350,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const Text('Select Color', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
              const SizedBox(height: 16),
              Expanded(
                child: GridView.builder(
                  gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                    crossAxisCount: 5,
                    crossAxisSpacing: 10,
                    mainAxisSpacing: 10,
                  ),
                  itemCount: colors.length,
                  itemBuilder: (context, index) {
                    final color = colors[index];
                    final hex = colorToHex(color);
                    return GestureDetector(
                      onTap: () {
                        controller.updateLayerProperty(layer['name'], 'color', hex);
                        Navigator.pop(context);
                      },
                      child: Container(
                        decoration: BoxDecoration(
                          color: color,
                          shape: BoxShape.circle,
                          border: Border.all(color: Colors.black12, width: 1),
                        ),
                        child: layer['color']?.toString().toUpperCase() == hex.toUpperCase() 
                          ? Icon(Icons.check, color: color == Colors.white ? Colors.black : Colors.white) 
                          : null,
                      ),
                    );
                  },
                ),
              ),
            ],
          ),
        );
      },
    );
  }

  void _showNudgeModal(Map<String, dynamic> layer) {
    showModalBottomSheet(
      context: context,
      barrierColor: Colors.transparent, // Allow seeing the canvas
      builder: (context) {
        return Container(
          padding: const EdgeInsets.all(16),
          height: 250,
          decoration: BoxDecoration(
            color: Colors.white,
            boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.1), blurRadius: 10)],
            borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
          ),
          child: Column(
            children: [
              const Text('Nudge', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
              const SizedBox(height: 16),
              Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Column(
                    children: [
                      IconButton(
                        icon: const Icon(Icons.arrow_drop_up, size: 48),
                        onPressed: () {
                          controller.updateLayerProperty(layer['name'], 'y', (layer['y'] ?? 0) - 5);
                        },
                      ),
                      Row(
                        children: [
                          IconButton(
                            icon: const Icon(Icons.arrow_left, size: 48),
                            onPressed: () {
                              controller.updateLayerProperty(layer['name'], 'x', (layer['x'] ?? 0) - 5);
                            },
                          ),
                          const SizedBox(width: 32),
                          IconButton(
                            icon: const Icon(Icons.arrow_right, size: 48),
                            onPressed: () {
                              controller.updateLayerProperty(layer['name'], 'x', (layer['x'] ?? 0) + 5);
                            },
                          ),
                        ],
                      ),
                      IconButton(
                        icon: const Icon(Icons.arrow_drop_down, size: 48),
                        onPressed: () {
                          controller.updateLayerProperty(layer['name'], 'y', (layer['y'] ?? 0) + 5);
                        },
                      ),
                    ],
                  ),
                ],
              ),
            ],
          ),
        );
      },
    );
  }
}

import '../utils/safe_double.dart';
import 'package:flutter/material.dart';

import 'package:get/get.dart';

import '../controllers/native_editor_controller.dart';

import 'dart:math' as math;

class InteractiveLayer extends StatefulWidget {
  final String layerName;
  final Widget child;
  final double scale;
  final Map<String, dynamic> layerConfig;

  const InteractiveLayer({
    super.key,
    required this.layerName,
    required this.child,
    required this.scale,
    required this.layerConfig,
  });

  @override
  State<InteractiveLayer> createState() => _InteractiveLayerState();
}

class _InteractiveLayerState extends State<InteractiveLayer> {
  // Local drag offset — accumulated during pan, applied to visual position.
  // Committed to controller only on pan end.
  double _dragDx = 0.0;
  double _dragDy = 0.0;
  bool _isDragging = false;

  @override
  Widget build(BuildContext context) {
    final controller = Get.find<NativeEditorController>();

    return Obx(() {
      final _layerUpdate = controller.layerUpdateTrigger.value;
      final _ = controller.templateConfig.values.toList();
      final isSelected = controller.selectedLayerId.value == widget.layerName;

      final double x = safeDouble(widget.layerConfig['x'] ?? 0) * widget.scale;
      double y = safeDouble(widget.layerConfig['y'] ?? 0) * widget.scale;

      // RC-6: Point Text Y-offset correction
      if (widget.layerConfig['type'] == 'text' && widget.layerConfig['kind']?.toString().toLowerCase() == 'point') {
        final double rawSize = safeDouble(widget.layerConfig['fontSize'] ?? widget.layerConfig['font_size'] ?? widget.layerConfig['size'] ?? 16);
        final double docPPI = safeDouble(controller.templateConfig['info']?['ppi'] ?? 72);
        final double ppiScale = docPPI / 72.0;
        final double layerScaleYForFont = safeDouble(widget.layerConfig['scaleY'] ?? widget.layerConfig['scaleX'] ?? 1.0);
        final double effectiveFontSize = rawSize * ppiScale * layerScaleYForFont * widget.scale;
        y -= (effectiveFontSize * 0.12);
      }
      double rawW = safeDouble(widget.layerConfig['w'] ?? widget.layerConfig['width'] ?? 0);
      double rawH = safeDouble(widget.layerConfig['h'] ?? widget.layerConfig['height'] ?? 0);

      // For frames lacking explicit dimensions, force them to 100% canvas size
      if ((widget.layerName == '_frame_bg' || widget.layerName == '_frame' || widget.layerName == 'frame') && (rawW <= 0 || rawH <= 0)) {
        rawW = safeDouble(controller.templateConfig['info']?['width'] ?? controller.templateConfig['width'] ?? 1080);
        rawH = safeDouble(controller.templateConfig['info']?['height'] ?? controller.templateConfig['height'] ?? 1080);
      }

      final double opacity = safeDouble(widget.layerConfig['opacity'] ?? 1.0);
      if (opacity <= 0.0) return const SizedBox.shrink();

      final double layerScaleX = safeDouble(widget.layerConfig['scaleX'] ?? 1.0);
      final double layerScaleY = safeDouble(widget.layerConfig['scaleY'] ?? 1.0);

      final double w = rawW * layerScaleX * widget.scale;
      final double h = rawH * layerScaleY * widget.scale;
      final double angle = safeDouble(widget.layerConfig['angle'] ?? 0);

      final bool isText = widget.layerConfig['type'] == 'text';
      final double? posW = w > 0 ? w : null;
      final double? posH = (h > 0 && !isText) ? h : null;

      final bool isFrameLayer = widget.layerConfig['_is_frame_layer'] == true || widget.layerConfig['_isFrameLayer'] == true;

      // Only block interaction on STRUCTURAL frame layers (bg, overlay, frame border).
      // Content layers from frames (text, logo, icons, contact info) should be interactive.
      final String _lName = widget.layerName.toLowerCase();
      final bool isFrameStructural = isFrameLayer && (
        widget.layerConfig['is_background'] == true ||
        _lName == '_frame_bg' ||
        _lName == '_frame' ||
        _lName == 'frame' ||
        _lName == 'background' ||
        _lName == 'bg' ||
        _lName.contains('background')
      );
      final bool canInteract = !isFrameStructural;

      // Apply local drag offset for smooth dragging
      final double visualX = x + (_isDragging ? _dragDx : 0.0);
      final double visualY = y + (_isDragging ? _dragDy : 0.0);

      Widget layerContent = Transform.rotate(
        angle: angle * math.pi / 180,
        child: canInteract ? GestureDetector(
          behavior: HitTestBehavior.opaque,
          onTap: () {
            debugPrint('[LAYER_TAP] ✅ onTap FIRED for "${widget.layerName}"');
            controller.layerWasTapped = true;
            controller.selectLayer(widget.layerName);
          },
          onPanStart: (details) {
            debugPrint('[LAYER_PAN] ✅ onPanStart for "${widget.layerName}"');
            controller.layerWasTapped = true;
            if (controller.selectedLayerId.value != widget.layerName) {
              controller.selectLayer(widget.layerName);
            }
            setState(() {
              _isDragging = true;
              _dragDx = 0.0;
              _dragDy = 0.0;
            });
          },
          onPanUpdate: (details) {
            if (controller.selectedLayerId.value != widget.layerName) return;
            // Accumulate drag offset locally for instant visual feedback
            setState(() {
              _dragDx += details.delta.dx;
              _dragDy += details.delta.dy;
            });
          },
          onPanEnd: (_) {
            debugPrint('[LAYER_PAN] onPanEnd for "${widget.layerName}" dx=$_dragDx dy=$_dragDy');
            if (controller.selectedLayerId.value == widget.layerName) {
              // Commit the accumulated drag to the controller
              final layers = controller.templateConfig['layers'] as List<dynamic>?;
              final currentLayer = layers?.firstWhere(
                (l) => (l['name'] ?? l['id']).toString() == widget.layerName,
                orElse: () => null,
              );
              if (currentLayer != null) {
                final currentX = safeDouble(currentLayer['x'] ?? 0);
                final currentY = safeDouble(currentLayer['y'] ?? 0);
                controller.updateLayerBounds(
                  widget.layerName,
                  currentX + _dragDx / widget.scale,
                  currentY + _dragDy / widget.scale,
                  safeDouble(currentLayer['w'] ?? currentLayer['width'] ?? 0),
                  safeDouble(currentLayer['h'] ?? currentLayer['height'] ?? 0),
                  angle,
                );
              }
              controller.commitLayerChange();
            }
            setState(() {
              _isDragging = false;
              _dragDx = 0.0;
              _dragDy = 0.0;
            });
          },
          child: Stack(
            clipBehavior: Clip.none,
            children: [
              SizedBox(
                width: posW,
                height: posH,
                child: widget.child,
              ),
              if (isSelected)
                Positioned.fill(
                  child: Container(
                    decoration: BoxDecoration(
                      border: Border.all(color: const Color(0xFF6366F1), width: 2),
                    ),
                  ),
                ),
              if (isSelected)
                ..._buildHandles(),
            ],
          ),
        ) : widget.child,
      );

      final String just = (widget.layerConfig['justification']?.toString().toLowerCase().trim()) ?? 'left';
      double? finalLeft;
      double? finalRight;

      if (posW != null) {
        finalLeft = visualX;
      } else {
        if (just == 'right') {
          final double canvasW = safeDouble(controller.templateConfig['info']?['width'] ?? controller.templateConfig['width'] ?? 1080) * widget.scale;
          finalRight = canvasW - visualX;
        } else if (just == 'center') {
          finalLeft = visualX;
        } else {
          finalLeft = visualX;
        }
      }

      return Positioned(
        left: finalLeft,
        right: finalRight,
        top: visualY,
        width: posW,
        height: posH,
        child: layerContent,
      );
    });
  }

  List<Widget> _buildHandles() {
    return [
      _buildHandle(-5, -5), // Top-left
      _buildHandle(null, -5, right: -5), // Top-right
      _buildHandle(-5, null, bottom: -5), // Bottom-left
      _buildHandle(null, null, right: -5, bottom: -5), // Bottom-right
    ];
  }

  Widget _buildHandle(double? left, double? top, {double? right, double? bottom}) {
    return Positioned(
      left: left,
      top: top,
      right: right,
      bottom: bottom,
      child: Container(
        width: 10,
        height: 10,
        decoration: BoxDecoration(
          color: Colors.white,
          border: Border.all(color: const Color(0xFF6366F1), width: 2),
          shape: BoxShape.circle,
        ),
      ),
    );
  }
}

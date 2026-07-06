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

  // Local scale offset
  double _scaleFactor = 1.0;
  bool _isScaling = false;
  double _scaleDx = 0.0;
  double _scaleDy = 0.0;
  bool _activeHandleIsLeft = false;
  bool _activeHandleIsTop = false;

  @override
  Widget build(BuildContext context) {
    final controller = Get.find<NativeEditorController>();

    return Obx(() {
      final _layerUpdate = controller.layerUpdateTrigger.value;
      final _ = controller.templateConfig.values.toList();
      final isSelected = controller.selectedLayerId.value == widget.layerName;

      final double x = safeDouble(widget.layerConfig['x'] ?? 0) * widget.scale;
      double y = safeDouble(widget.layerConfig['y'] ?? 0) * widget.scale;

      // The Y-offset correction is now handled universally at the JSON exporter level.
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
      final bool isSingleLine = isText && widget.layerConfig['_is_single_line'] == true;
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

      // Block interaction on the main template background image (Festival post main image etc)
      final bool isMainBackground = !isFrameLayer && (
        widget.layerConfig['is_background'] == true ||
        widget.layerConfig['is_background'] == 1 ||
        _lName == '_bg_image' ||
        _lName == 'background' ||
        _lName == 'bg'
      );

      final bool canInteract = !isFrameStructural && !isMainBackground;

      // Apply local drag offset for smooth dragging
      final double visualX = x + (_isDragging ? _dragDx : 0.0);
      final double visualY = y + (_isDragging ? _dragDy : 0.0);

      Widget gestureChild = Stack(
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
      );

      if (_isScaling) {
        Alignment scaleAlignment = Alignment.topLeft;
        if (_activeHandleIsLeft && _activeHandleIsTop) scaleAlignment = Alignment.bottomRight;
        else if (_activeHandleIsLeft && !_activeHandleIsTop) scaleAlignment = Alignment.topRight;
        else if (!_activeHandleIsLeft && _activeHandleIsTop) scaleAlignment = Alignment.bottomLeft;
        else scaleAlignment = Alignment.topLeft;

        gestureChild = Transform.scale(
          scale: _scaleFactor,
          alignment: scaleAlignment,
          child: gestureChild,
        );
      }

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
              Map<String, dynamic>? currentLayer;
              if (layers != null) {
                for (var l in layers) {
                  if ((l['name'] ?? l['id']).toString() == widget.layerName) {
                    currentLayer = l as Map<String, dynamic>;
                    break;
                  }
                }
              }
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
          child: gestureChild,
        ) : widget.child,
      );

      final dynamic fontObj = widget.layerConfig['font'];
      final String just = (widget.layerConfig['justification'] ?? 
                          (fontObj is Map ? fontObj['justification'] : null) ?? 
                          widget.layerConfig['textAlign'] ?? 'left').toString().toLowerCase().trim();
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
      _buildHandle(left: 0, top: 0, isLeft: true, isTop: true), // Top-left
      _buildHandle(right: 0, top: 0, isLeft: false, isTop: true), // Top-right
      _buildHandle(left: 0, bottom: 0, isLeft: true, isTop: false), // Bottom-left
      _buildHandle(right: 0, bottom: 0, isLeft: false, isTop: false), // Bottom-right
    ];
  }

  Widget _buildHandle({double? left, double? top, double? right, double? bottom, required bool isLeft, required bool isTop}) {
    double? posLeft = left != null ? left - 12 : null;
    double? posTop = top != null ? top - 12 : null;
    double? posRight = right != null ? right - 12 : null;
    double? posBottom = bottom != null ? bottom - 12 : null;

    final double angle = safeDouble(widget.layerConfig['angle'] ?? 0);
    final double angleRad = angle * math.pi / 180;

    return Positioned(
      left: posLeft,
      top: posTop,
      right: posRight,
      bottom: posBottom,
      child: GestureDetector(
        behavior: HitTestBehavior.opaque,
        onPanStart: (details) {
          final controller = Get.find<NativeEditorController>();
          controller.layerWasTapped = true;
          if (controller.selectedLayerId.value != widget.layerName) {
            controller.selectLayer(widget.layerName);
          }
          setState(() {
            _isScaling = true;
            _scaleFactor = 1.0;
            _scaleDx = 0.0;
            _scaleDy = 0.0;
            _activeHandleIsLeft = isLeft;
            _activeHandleIsTop = isTop;
          });
        },
        onPanUpdate: (details) {
          setState(() {
            double rotatedDx = details.delta.dx * math.cos(-angleRad) - details.delta.dy * math.sin(-angleRad);
            double rotatedDy = details.delta.dx * math.sin(-angleRad) + details.delta.dy * math.cos(-angleRad);
            
            _scaleDx += rotatedDx;
            _scaleDy += rotatedDy;
            
            double dx = _scaleDx;
            if (isLeft) dx = -dx;
            
            double initialW = safeDouble(widget.layerConfig['w'] ?? widget.layerConfig['width'] ?? 0) * safeDouble(widget.layerConfig['scaleX'] ?? 1.0) * widget.scale;
            if (initialW <= 0 && context.size != null) {
              initialW = context.size!.width;
            }
            if (initialW < 20) initialW = 20;

            _scaleFactor = 1.0 + (dx / initialW);
            if (_scaleFactor < 0.1) _scaleFactor = 0.1;
            debugPrint('[LAYER_RESIZE] onPanUpdate dx: $dx, initialW: $initialW, scaleFactor: $_scaleFactor');
          });
        },
        onPanEnd: (_) {
          final controller = Get.find<NativeEditorController>();
          final layers = controller.templateConfig['layers'] as List<dynamic>?;
          Map<String, dynamic>? currentLayer;
          if (layers != null) {
            for (var l in layers) {
              if ((l['name'] ?? l['id']).toString() == widget.layerName) {
                currentLayer = l as Map<String, dynamic>;
                break;
              }
            }
          }
          if (currentLayer != null) {
            final double currentX = safeDouble(currentLayer['x'] ?? 0);
            final double currentY = safeDouble(currentLayer['y'] ?? 0);
            final double currentScaleX = safeDouble(currentLayer['scaleX'] ?? 1.0);
            final double currentScaleY = safeDouble(currentLayer['scaleY'] ?? 1.0);
            
            double initialW = safeDouble(currentLayer['w'] ?? currentLayer['width'] ?? 0) * currentScaleX;
            double initialH = safeDouble(currentLayer['h'] ?? currentLayer['height'] ?? 0) * currentScaleY;
            
            if (initialW <= 0 && context.size != null) {
              initialW = context.size!.width / widget.scale;
            }
            if (initialH <= 0 && context.size != null) {
              initialH = context.size!.height / widget.scale;
            }
            
            double newW = initialW * _scaleFactor;
            double newH = initialH * _scaleFactor;
            
            double finalX = currentX;
            double finalY = currentY;
            
            if (isLeft) {
              finalX = currentX - (newW - initialW);
            }
            if (isTop) {
              finalY = currentY - (newH - initialH);
            }
            
            controller.updateLayerProperties(widget.layerName, {
              'x': finalX,
              'y': finalY,
              'scaleX': currentScaleX * _scaleFactor,
              'scaleY': currentScaleY * _scaleFactor,
            });
            controller.commitLayerChange();
          }
          setState(() {
            _isScaling = false;
            _scaleFactor = 1.0;
            _scaleDx = 0.0;
            _scaleDy = 0.0;
          });
        },
        child: Container(
          width: 24,
          height: 24,
          color: Colors.transparent,
          alignment: Alignment.center,
          child: Container(
            width: 10,
            height: 10,
            decoration: BoxDecoration(
              color: Colors.white,
              border: Border.all(color: const Color(0xFF6366F1), width: 2),
              shape: BoxShape.circle,
            ),
          ),
        ),
      ),
    );
  }
}

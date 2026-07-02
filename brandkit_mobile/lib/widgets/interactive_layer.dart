import '../utils/safe_double.dart';
import 'package:flutter/material.dart';

import 'package:get/get.dart';

import '../controllers/native_editor_controller.dart';

import 'dart:math' as math;

class InteractiveLayer extends StatelessWidget {
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
  Widget build(BuildContext context) {
    final controller = Get.find<NativeEditorController>();


    return Obx(() {
      final _layerUpdate = controller.layerUpdateTrigger.value;
      final _ = controller.templateConfig.values.toList(); // Force GetX to track config changes like opacity
      final isSelected = controller.selectedLayerId.value == layerName;
      
      final double x = safeDouble(layerConfig['x'] ?? 0) * scale;
      double y = safeDouble(layerConfig['y'] ?? 0) * scale;
      
      // RC-6: Point Text Y-offset correction (matches web editor's Fabric.js em-box adjustment)
      // Web editor: for Point text, renders at y - fontSize*0.12 (Fabric em-box vs visual bounds).
      // On export, adds the offset back. So JSON Y = original PSD Y.
      // Native must also subtract the offset for Point text to match web positioning.
      if (layerConfig['type'] == 'text' && layerConfig['kind']?.toString().toLowerCase() == 'point') {
        final double rawSize = safeDouble(layerConfig['fontSize'] ?? layerConfig['font_size'] ?? layerConfig['size'] ?? 16);
        final double docPPI = safeDouble(controller.templateConfig['info']?['ppi'] ?? 72);
        final double ppiScale = docPPI / 72.0;
        final double layerScaleYForFont = safeDouble(layerConfig['scaleY'] ?? layerConfig['scaleX'] ?? 1.0);
        final double effectiveFontSize = rawSize * ppiScale * layerScaleYForFont * scale;
        y -= (effectiveFontSize * 0.12);
      }
      double rawW = safeDouble(layerConfig['w'] ?? layerConfig['width'] ?? 0);
      double rawH = safeDouble(layerConfig['h'] ?? layerConfig['height'] ?? 0);

      // For frames lacking explicit dimensions, force them to 100% canvas size
      if ((layerName == '_frame_bg' || layerName == '_frame' || layerName == 'frame') && (rawW <= 0 || rawH <= 0)) {
        rawW = safeDouble(controller.templateConfig['info']?['width'] ?? controller.templateConfig['width'] ?? 1080);
        rawH = safeDouble(controller.templateConfig['info']?['height'] ?? controller.templateConfig['height'] ?? 1080);
      }

      final double opacity = safeDouble(layerConfig['opacity'] ?? 1.0);
      if (opacity <= 0.0) return const SizedBox.shrink();

      final double layerScaleX = safeDouble(layerConfig['scaleX'] ?? 1.0);
      final double layerScaleY = safeDouble(layerConfig['scaleY'] ?? 1.0);

      final double w = rawW * layerScaleX * scale;
      final double h = rawH * layerScaleY * scale;
      final double angle = safeDouble(layerConfig['angle'] ?? 0);

      // For layers with zero/missing dimensions, use the child's intrinsic size
      // but cap it to prevent unbounded overflow
      final bool isText = layerConfig['type'] == 'text';
      final double? posW = w > 0 ? w : null;
      final double? posH = (h > 0 && !isText) ? h : null;

      final bool isFrameLayer = layerConfig['_is_frame_layer'] == true || layerConfig['_isFrameLayer'] == true;
      final bool canInteract = !isFrameLayer;

      // Diagnostics moved to onTap/onPanStart to avoid running on every rebuild

      Widget layerContent = Transform.rotate(
        angle: angle * math.pi / 180,
        child: canInteract ? GestureDetector(
          behavior: HitTestBehavior.opaque,
          onTap: () {
            debugPrint('[LAYER_TAP] ✅ onTap FIRED for "$layerName" type=${layerConfig['type']} isFrame=$isFrameLayer canInteract=$canInteract posW=$posW posH=$posH');
            controller.layerWasTapped = true;
            controller.selectLayer(layerName);
          },
          onPanStart: (_) {
            debugPrint('[LAYER_PAN] ✅ onPanStart FIRED for "$layerName" type=${layerConfig['type']}');
            controller.layerWasTapped = true;
            if (controller.selectedLayerId.value != layerName) {
              controller.selectLayer(layerName);
            }
          },
          onPanUpdate: (details) {
            // Only allow drag if this layer is the selected one
            if (controller.selectedLayerId.value != layerName) return;
            
            final layers = controller.templateConfig['layers'] as List<dynamic>?;
            final currentLayer = layers?.firstWhere((l) => (l['name'] ?? l['id']).toString() == layerName, orElse: () => null);
            if (currentLayer == null) return;

            // Update position using synchronous state to prevent drag jitter
            final dx = details.delta.dx / scale;
            final dy = details.delta.dy / scale;
            final currentX = safeDouble(currentLayer['x'] ?? 0);
            final currentY = safeDouble(currentLayer['y'] ?? 0);
            
            controller.updateLayerBounds(
              layerName, 
              currentX + dx, 
              currentY + dy, 
              safeDouble(currentLayer['w'] ?? currentLayer['width'] ?? 0), 
              safeDouble(currentLayer['h'] ?? currentLayer['height'] ?? 0), 
              angle
            );
          },
          onPanEnd: (_) {
            if (controller.selectedLayerId.value == layerName) {
              controller.commitLayerChange();
            }
          },
          child: Stack(
            clipBehavior: Clip.none,
            children: [
              SizedBox(
                width: posW,
                height: posH,
                child: child,
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
        ) : child,
      );

      final String just = (layerConfig['justification']?.toString().toLowerCase().trim()) ?? 'left';
      double? finalLeft;
      double? finalRight;
      
      if (posW != null) {
        // If width is known, always use left: x and width: posW
        // The container inside will handle the right alignment
        finalLeft = x;
      } else {
        // If width is unknown, alignment determines origin
        if (just == 'right') {
          // Web editor: originX = right, tLeft = x. Right edge is at x!
          final double canvasW = safeDouble(controller.templateConfig['info']?['width'] ?? controller.templateConfig['width'] ?? 1080) * scale;
          finalRight = canvasW - x;
        } else if (just == 'center') {
          // Fallback, not strictly perfect without width
          finalLeft = x; 
        } else {
          finalLeft = x;
        }
      }

      return Positioned(
        left: finalLeft,
        right: finalRight,
        top: y,
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



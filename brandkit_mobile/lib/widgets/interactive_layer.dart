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
    final isBackground = layerConfig['is_background'] == true ||
        layerName.toLowerCase() == 'bg' ||
        layerName.toLowerCase() == 'background';

    // If background, just return the child without gestures (can't drag background)
    if (isBackground) return child;

    return Obx(() {
      final isSelected = controller.selectedLayerId.value == layerName;
      
      final double x = (layerConfig['x'] ?? 0).toDouble() * scale;
      final double y = (layerConfig['y'] ?? 0).toDouble() * scale;
      double rawW = (layerConfig['w'] ?? layerConfig['width'] ?? 0).toDouble();
      double rawH = (layerConfig['h'] ?? layerConfig['height'] ?? 0).toDouble();

      // For frames lacking explicit dimensions, force them to 100% canvas size
      if ((layerName == '_frame_bg' || layerName == '_frame' || layerName == 'frame') && (rawW <= 0 || rawH <= 0)) {
        rawW = (controller.templateConfig['info']?['width'] ?? controller.templateConfig['width'] ?? 1080).toDouble();
        rawH = (controller.templateConfig['info']?['height'] ?? controller.templateConfig['height'] ?? 1080).toDouble();
      }

      final double opacity = (layerConfig['opacity'] ?? 1.0).toDouble();
      if (opacity <= 0.0) return const SizedBox.shrink();

      final double layerScaleX = (layerConfig['scaleX'] ?? 1.0).toDouble();
      final double layerScaleY = (layerConfig['scaleY'] ?? 1.0).toDouble();

      final double w = rawW * layerScaleX * scale;
      final double h = rawH * layerScaleY * scale;
      final double angle = (layerConfig['angle'] ?? 0).toDouble();

      // For layers with zero/missing dimensions, use the child's intrinsic size
      // but cap it to prevent unbounded overflow
      final bool isText = layerConfig['type'] == 'text';
      final double? posW = w > 0 ? w : null;
      final double? posH = (h > 0 && !isText) ? h : null;

      final bool canInteract = true;

      Widget layerContent = Transform.rotate(
        angle: angle * math.pi / 180,
        child: canInteract ? GestureDetector(
          onTap: () {
            controller.selectLayer(layerName);
          },
          onPanUpdate: isSelected ? (details) {
            final layers = controller.templateConfig['layers'] as List<dynamic>?;
            final currentLayer = layers?.firstWhere((l) => (l['name'] ?? l['id']).toString() == layerName, orElse: () => null);
            if (currentLayer == null) return;

            // Update position using synchronous state to prevent drag jitter
            final dx = details.delta.dx / scale;
            final dy = details.delta.dy / scale;
            final currentX = (currentLayer['x'] ?? 0).toDouble();
            final currentY = (currentLayer['y'] ?? 0).toDouble();
            
            controller.updateLayerBounds(
              layerName, 
              currentX + dx, 
              currentY + dy, 
              (currentLayer['w'] ?? currentLayer['width'] ?? 0).toDouble(), 
              (currentLayer['h'] ?? currentLayer['height'] ?? 0).toDouble(), 
              angle
            );
          } : null,
          onPanEnd: isSelected ? (_) => controller.commitLayerChange() : null,
          child: Stack(
            clipBehavior: Clip.none,
            children: [
              child,
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
          final double canvasW = (controller.templateConfig['info']?['width'] ?? controller.templateConfig['width'] ?? 1080).toDouble() * scale;
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

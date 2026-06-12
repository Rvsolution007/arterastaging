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
      final double w = (layerConfig['w'] ?? layerConfig['width'] ?? 0).toDouble() * scale;
      final double h = (layerConfig['h'] ?? layerConfig['height'] ?? 0).toDouble() * scale;
      final double angle = (layerConfig['angle'] ?? 0).toDouble();

      return Positioned(
        left: x,
        top: y,
        width: w > 0 ? w : null,
        height: h > 0 ? h : null,
        child: Transform.rotate(
          angle: angle * math.pi / 180,
          child: GestureDetector(
            onTap: () {
              controller.selectLayer(layerName);
            },
            onPanUpdate: isSelected ? (details) {
              // Update position
              final dx = details.delta.dx / scale;
              final dy = details.delta.dy / scale;
              final newX = (layerConfig['x'] ?? 0).toDouble() + dx;
              final newY = (layerConfig['y'] ?? 0).toDouble() + dy;
              
              controller.updateLayerBounds(
                layerName, 
                newX, 
                newY, 
                (layerConfig['w'] ?? layerConfig['width'] ?? 0).toDouble(), 
                (layerConfig['h'] ?? layerConfig['height'] ?? 0).toDouble(), 
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
          ),
        ),
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

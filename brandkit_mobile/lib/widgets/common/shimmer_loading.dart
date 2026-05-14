import 'package:flutter/material.dart';
import '../../utils/app_colors.dart';

/// Shimmer loading placeholder — matches web preview's shimmer animation
class ShimmerLoading extends StatefulWidget {
  final double width;
  final double height;
  final double borderRadius;

  const ShimmerLoading({
    super.key,
    required this.width,
    required this.height,
    this.borderRadius = 20,
  });

  @override
  State<ShimmerLoading> createState() => _ShimmerLoadingState();
}

class _ShimmerLoadingState extends State<ShimmerLoading>
    with SingleTickerProviderStateMixin {
  late AnimationController _controller;
  late Animation<double> _animation;

  @override
  void initState() {
    super.initState();
    _controller = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 1500),
    )..repeat();
    _animation = Tween<double>(begin: -1.0, end: 2.0).animate(
      CurvedAnimation(parent: _controller, curve: Curves.easeInOut),
    );
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return AnimatedBuilder(
      animation: _animation,
      builder: (context, child) {
        return Container(
          width: widget.width,
          height: widget.height,
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(widget.borderRadius),
            gradient: LinearGradient(
              begin: Alignment(_animation.value - 1, 0),
              end: Alignment(_animation.value, 0),
              colors: const [
                AppColors.slate100,
                AppColors.slate200,
                AppColors.slate100,
              ],
            ),
          ),
        );
      },
    );
  }
}

/// Shimmer loading with spinner overlay — matches web custom post shimmer
class ShimmerWithSpinner extends StatelessWidget {
  final double width;
  final double height;
  final double borderRadius;
  final String? label;

  const ShimmerWithSpinner({
    super.key,
    required this.width,
    required this.height,
    this.borderRadius = 20,
    this.label,
  });

  @override
  Widget build(BuildContext context) {
    return Stack(
      children: [
        ShimmerLoading(
          width: width,
          height: height,
          borderRadius: borderRadius,
        ),
        Positioned.fill(
          child: Center(
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                SizedBox(
                  width: 32,
                  height: 32,
                  child: CircularProgressIndicator(
                    strokeWidth: 2,
                    color: AppColors.indigo500,
                  ),
                ),
                if (label != null) ...[
                  const SizedBox(height: 8),
                  Text(
                    label!,
                    style: TextStyle(
                      fontSize: 10,
                      fontWeight: FontWeight.w600,
                      color: AppColors.slate400,
                    ),
                  ),
                ],
              ],
            ),
          ),
        ),
      ],
    );
  }
}

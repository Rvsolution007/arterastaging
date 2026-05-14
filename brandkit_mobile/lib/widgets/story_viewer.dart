import 'dart:async';
import 'dart:io';
import 'package:flutter/material.dart';
import 'package:cached_network_image/cached_network_image.dart';
import '../utils/app_colors.dart';

/// Full-screen story viewer — pixel-perfect match with web preview's story overlay
/// Features: segmented progress bar, left/right tap zones, auto-advance, external link
class StoryViewer extends StatefulWidget {
  final List<String> images;
  final String? linkTitle;
  final String? linkUrl;
  final String? businessName;
  final String? businessLogo;
  final bool isLocalFile;

  const StoryViewer({
    super.key,
    required this.images,
    this.linkTitle,
    this.linkUrl,
    this.businessName,
    this.businessLogo,
    this.isLocalFile = false,
  });

  @override
  State<StoryViewer> createState() => _StoryViewerState();
}

class _StoryViewerState extends State<StoryViewer>
    with SingleTickerProviderStateMixin {
  int _currentIndex = 0;
  late AnimationController _progressController;
  Timer? _autoAdvanceTimer;
  static const _slideDuration = Duration(seconds: 5);

  @override
  void initState() {
    super.initState();
    _progressController = AnimationController(
      vsync: this,
      duration: _slideDuration,
    );
    _showSlide(0);
  }

  @override
  void dispose() {
    _progressController.dispose();
    _autoAdvanceTimer?.cancel();
    super.dispose();
  }

  void _showSlide(int index) {
    if (index < 0 || index >= widget.images.length) {
      Navigator.of(context).pop();
      return;
    }
    setState(() => _currentIndex = index);
    _progressController.reset();
    _progressController.forward();
    _autoAdvanceTimer?.cancel();
    _autoAdvanceTimer = Timer(_slideDuration, () {
      if (_currentIndex < widget.images.length - 1) {
        _showSlide(_currentIndex + 1);
      } else {
        Navigator.of(context).pop();
      }
    });
  }

  void _nextSlide() {
    if (_currentIndex < widget.images.length - 1) {
      _showSlide(_currentIndex + 1);
    } else {
      Navigator.of(context).pop();
    }
  }

  void _prevSlide() {
    if (_currentIndex > 0) {
      _showSlide(_currentIndex - 1);
    } else {
      _showSlide(0);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.black,
      body: Stack(
        children: [
          // Story Image
          Center(
            child: widget.isLocalFile
              ? Image.file(
                  File(widget.images[_currentIndex]),
                  fit: BoxFit.contain,
                  width: double.infinity,
                  height: double.infinity,
                  errorBuilder: (_, __, ___) => const Center(
                    child: Icon(Icons.broken_image, color: Colors.white54, size: 48),
                  ),
                )
              : CachedNetworkImage(
                  imageUrl: widget.images[_currentIndex],
                  fit: BoxFit.contain,
                  width: double.infinity,
                  height: double.infinity,
                  placeholder: (_, __) => const Center(
                    child: CircularProgressIndicator(color: Colors.white),
                  ),
                  errorWidget: (_, __, ___) => const Center(
                    child: Icon(Icons.broken_image, color: Colors.white54, size: 48),
                  ),
                ),
          ),

          // Segmented Progress Bar
          Positioned(
            top: MediaQuery.of(context).padding.top + 8,
            left: 8,
            right: 8,
            child: Row(
              children: List.generate(widget.images.length, (i) {
                return Expanded(
                  child: Container(
                    margin: EdgeInsets.symmetric(horizontal: widget.images.length > 1 ? 2 : 0),
                    height: 2,
                    decoration: BoxDecoration(
                      borderRadius: BorderRadius.circular(2),
                      color: Colors.white.withValues(alpha: 0.3),
                    ),
                    child: ClipRRect(
                      borderRadius: BorderRadius.circular(2),
                      child: i < _currentIndex
                          ? Container(color: Colors.white)
                          : i == _currentIndex
                              ? AnimatedBuilder(
                                  animation: _progressController,
                                  builder: (_, __) => FractionallySizedBox(
                                    alignment: Alignment.centerLeft,
                                    widthFactor: _progressController.value,
                                    child: Container(color: Colors.white),
                                  ),
                                )
                              : const SizedBox(),
                    ),
                  ),
                );
              }),
            ),
          ),

          // Header: Avatar + Name
          Positioned(
            top: MediaQuery.of(context).padding.top + 20,
            left: 16,
            child: Row(
              children: [
                Container(
                  width: 36,
                  height: 36,
                  decoration: BoxDecoration(
                    shape: BoxShape.circle,
                    border: Border.all(color: Colors.white, width: 2),
                    color: AppColors.gray200,
                  ),
                  clipBehavior: Clip.antiAlias,
                  child: widget.businessLogo != null && widget.businessLogo!.isNotEmpty
                      ? CachedNetworkImage(
                          imageUrl: widget.businessLogo!,
                          fit: BoxFit.cover,
                          errorWidget: (_, __, ___) =>
                              const Icon(Icons.person, color: Colors.grey, size: 20),
                        )
                      : const Icon(Icons.person, color: Colors.grey, size: 20),
                ),
                const SizedBox(width: 8),
                Text(
                  widget.businessName ?? 'Business',
                  style: const TextStyle(
                    color: Colors.white,
                    fontSize: 14,
                    fontWeight: FontWeight.w600,
                    shadows: [Shadow(blurRadius: 4, color: Colors.black54)],
                  ),
                ),
                const SizedBox(width: 6),
                Text(
                  '1h',
                  style: TextStyle(
                    color: Colors.white.withValues(alpha: 0.7),
                    fontSize: 12,
                    fontWeight: FontWeight.w500,
                  ),
                ),
              ],
            ),
          ),

          // Close Button
          Positioned(
            top: MediaQuery.of(context).padding.top + 20,
            right: 16,
            child: GestureDetector(
              onTap: () => Navigator.of(context).pop(),
              child: Container(
                padding: const EdgeInsets.all(4),
                decoration: BoxDecoration(
                  shape: BoxShape.circle,
                  color: Colors.black.withValues(alpha: 0.2),
                ),
                child: const Icon(Icons.close, color: Colors.white, size: 24),
              ),
            ),
          ),

          // Left tap zone (go back)
          Positioned(
            left: 0,
            top: 0,
            bottom: 0,
            width: MediaQuery.of(context).size.width * 0.4,
            child: GestureDetector(onTap: _prevSlide),
          ),

          // Right tap zone (go next)
          Positioned(
            right: 0,
            top: 0,
            bottom: 0,
            width: MediaQuery.of(context).size.width * 0.6,
            child: GestureDetector(onTap: _nextSlide),
          ),

          // External Link Button
          if (widget.linkUrl != null && widget.linkUrl!.isNotEmpty)
            Positioned(
              bottom: 48,
              left: 0,
              right: 0,
              child: Center(
                child: GestureDetector(
                  onTap: () {
                    // Could launch URL
                  },
                  child: Container(
                    padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 10),
                    decoration: BoxDecoration(
                      color: Colors.white.withValues(alpha: 0.1),
                      borderRadius: BorderRadius.circular(999),
                      border: Border.all(
                        color: Colors.white.withValues(alpha: 0.3),
                      ),
                    ),
                    child: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Text(
                          widget.linkTitle ?? 'View More',
                          style: const TextStyle(
                            color: Colors.white,
                            fontSize: 14,
                            fontWeight: FontWeight.w700,
                          ),
                        ),
                        const SizedBox(width: 4),
                        const Icon(Icons.keyboard_arrow_up,
                            color: Colors.white, size: 16),
                      ],
                    ),
                  ),
                ),
              ),
            ),
        ],
      ),
    );
  }
}

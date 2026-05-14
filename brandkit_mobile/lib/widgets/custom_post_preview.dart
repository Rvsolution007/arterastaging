import 'package:flutter/material.dart';
import 'package:cached_network_image/cached_network_image.dart';

/// Renders a JSON-based AI Post configuration into native Flutter widgets.
/// This matches the exact behavior of the `renderAdvancedAiPost` JS script.
///
/// ARCHITECTURE NOTES (for future maintainers):
/// 1. All positioning uses absolute coordinates from the JSON (x, y, w, h)
///    scaled by [scale] = previewWidth / designWidth.
/// 2. Images are loaded via `Image.network` (NOT CachedNetworkImage) for
///    small decorator assets (< 50px native) to avoid Flutter Web's silent
///    failure with tiny cached images.
/// 3. URLs with spaces in filenames are encoded via Uri.encodeFull().
/// 4. A minimum rendered size of 8×8 logical pixels is enforced so that
///    sub-pixel images (e.g. 13×13 @ 0.3x scale ≈ 4px) remain visible.
class CustomPostPreview extends StatelessWidget {
  final Map<String, dynamic> config;
  final Map<String, dynamic>? aiData;
  final double width;
  final String uploadsBaseUrl;
  final String templateBaseUrl;
  final String? baseImgUrl;

  const CustomPostPreview({
    super.key,
    required this.config,
    this.aiData,
    required this.width,
    required this.uploadsBaseUrl,
    required this.templateBaseUrl,
    this.baseImgUrl,
  });

  @override
  Widget build(BuildContext context) {
    // Original design resolution (default 1024)
    final double designW = (config['info']?['width'] ?? 1024).toDouble();
    final double designH = (config['info']?['height'] ?? 1024).toDouble();

    final double scale = width / designW;
    final double height = designH * scale;

    final List<dynamic> layers = config['layers'] ?? [];

    // ══ DIAGNOSTICS ══
    debugPrint('╔══════════════════════════════════════════════');
    debugPrint('║ CustomPostPreview: ${layers.length} layers, design=${designW}x${designH}, preview=$width, scale=${scale.toStringAsFixed(3)}');
    debugPrint('║ templateBaseUrl: $templateBaseUrl');
    debugPrint('╚══════════════════════════════════════════════');

    // Sort layers by z_index (ascending) so lowest z_index renders first (bottom)
    // and highest z_index renders last (top of Stack).
    // This handles JSONs where layers may not be in visual order.
    final List<dynamic> sortedLayers = List.from(layers);
    sortedLayers.sort((a, b) {
      final int zA = (a['z_index'] ?? 0) as int;
      final int zB = (b['z_index'] ?? 0) as int;
      return zA.compareTo(zB);
    });

    return Container(
      width: width,
      height: height,
      clipBehavior: Clip.antiAlias,
      decoration: const BoxDecoration(
        color: Colors.white,
      ),
      child: Stack(
        children: [
          // Render Layers sorted by z_index (lowest = bottom, highest = top)
          ...sortedLayers.map((layer) {
            return _buildLayer(layer, scale);
          }),
        ],
      ),
    );
  }

  Widget _buildLayer(Map<String, dynamic> layer, double scale) {
    final String name = (layer['name'] ?? '').toString().toLowerCase();
    final String type = layer['type'] ?? '';
    final bool isBackground = layer['is_background'] == true ||
        name == 'bg' ||
        name == 'background';

    // ══ DIAGNOSTICS ══
    final double rawW = (layer['w'] ?? layer['width'] ?? 0).toDouble();
    final double rawH = (layer['h'] ?? layer['height'] ?? 0).toDouble();
    debugPrint('[LAYER] name="$name" type=$type bg=$isBackground native=${rawW}x${rawH} scaled=${(rawW*scale).toStringAsFixed(1)}x${(rawH*scale).toStringAsFixed(1)}');

    // Handle Background Layer
    if (isBackground) {
      if (type == 'image') {
        String bgSrc = layer['src'] ?? '';
        String finalSrc = _resolveAssetUrl(bgSrc);
        return Positioned.fill(
          child: _buildImage(finalSrc, BoxFit.cover, null, isSmall: false),
        );
      }
      return const SizedBox.shrink();
    }

    // Standard absolute positioning parameters
    final double x = (layer['x'] ?? 0).toDouble() * scale;
    final double y = (layer['y'] ?? 0).toDouble() * scale;
    double w = (layer['w'] ?? layer['width'] ?? 0).toDouble() * scale;
    double h = (layer['h'] ?? layer['height'] ?? 0).toDouble() * scale;
    final double angle = (layer['angle'] ?? 0).toDouble();

    // ── MINIMUM SIZE ENFORCEMENT ──
    // Small decorator images (bullets, icons, diamonds) can scale down to
    // 3–4 logical pixels which Flutter Web may silently skip or render as
    // invisible. We enforce a minimum of 8×8 px and shift the origin so the
    // center stays at the same visual position.
    final double nativeW = (layer['w'] ?? layer['width'] ?? 0).toDouble();
    final double nativeH = (layer['h'] ?? layer['height'] ?? 0).toDouble();
    const double minRenderSize = 8.0;

    double xOffset = 0;
    double yOffset = 0;
    if (type == 'image' && !isBackground && w > 0 && h > 0) {
      if (w < minRenderSize) {
        xOffset = (w - minRenderSize) / 2; // negative shift to keep center
        w = minRenderSize;
      }
      if (h < minRenderSize) {
        yOffset = (h - minRenderSize) / 2;
        h = minRenderSize;
      }
    }

    Widget content = const SizedBox.shrink();

    if (type == 'text') {
      content = _buildText(layer, scale);
    } else if (type == 'image') {
      content = _buildImageLayer(layer, name, scale, nativeW, nativeH);
    }

    if (angle != 0) {
      content = Transform.rotate(
        angle: angle * 3.1415926535897932 / 180,
        child: content,
      );
    }

    // Positioning Logic
    if (type == 'text') {
      final double fontSize = (layer['size'] ?? 16).toDouble() * scale;
      final bool isMultiLine = h > (fontSize * 1.5);
      final String just = layer['justification'] ?? 'left';

      if (isMultiLine) {
        // Multi-line paragraph text: constrain to fixed width & height so it wraps
        return Positioned(
          left: x,
          top: y,
          width: w > 0 ? w : null,
          height: h > 0 ? h : null,
          child: content,
        );
      } else {
        // Single-line point text: do not constrain width, anchor based on justification
        if (just == 'right') {
          return Positioned(
            right: width - (x + w),
            top: y,
            child: content,
          );
        } else if (just == 'center') {
          return Positioned(
            left: x + (w / 2),
            top: y,
            child: FractionalTranslation(
              translation: const Offset(-0.5, 0),
              child: content,
            ),
          );
        } else {
          // Left aligned (default)
          return Positioned(
            left: x,
            top: y,
            child: content,
          );
        }
      }
    }

    // Default positioning for images and other layers
    if (w > 0 || h > 0) {
      return Positioned(
        left: x + xOffset,
        top: y + yOffset,
        width: w > 0 ? w : null,
        height: h > 0 ? h : null,
        child: content,
      );
    }

    // Fallback position if no w/h provided
    return Positioned(
      left: x,
      top: y,
      child: content,
    );
  }

  Widget _buildText(Map<String, dynamic> layer, double scale) {
    final String layerName = layer['name'] ?? '';
    String textValue = layer['text'] ?? '';
    
    // Override with AI Data if available
    if (aiData != null && aiData![layerName] != null) {
      textValue = aiData![layerName].toString();
    }

    textValue = textValue.replaceAll(r'\\n', '\n');

    if (layer['uppercase'] == true) {
      textValue = textValue.toUpperCase();
    }

    // Color conversion (handles hex and 0x formats)
    String colorStr = layer['color'] ?? '#000000';
    colorStr = colorStr.replaceAll('0x', '#');
    if (colorStr.length == 7) colorStr = colorStr.replaceFirst('#', '0xFF');
    Color fontColor = Color(int.tryParse(colorStr) ?? 0xFF000000);

    // Font size
    final double fontSize = (layer['size'] ?? 16).toDouble() * scale;

    // Font weight
    final String weightStr = (layer['weight'] ?? '').toString();
    final String fontName = (layer['font'] ?? '').toString().toLowerCase();
    FontWeight fontWeight = FontWeight.normal;
    if (weightStr == 'bold' || fontName.contains('bold')) {
      fontWeight = FontWeight.bold;
    }

    // Character spacing
    double? letterSpacing;
    if (layer['char_spacing'] != null) {
      final double charSpacing = (layer['char_spacing']).toDouble();
      letterSpacing = (charSpacing / 1000) * fontSize;
    }

    // Shadow
    List<Shadow>? shadows;
    if (layer['shadow'] != null) {
      final Map<String, dynamic> shadowMap = layer['shadow'];
      final double ox = (shadowMap['offsetX'] ?? 0).toDouble() * scale;
      final double oy = (shadowMap['offsetY'] ?? 0).toDouble() * scale;
      final double bl = (shadowMap['blur'] ?? 0).toDouble() * scale;
      String sColorStr = shadowMap['color'] ?? 'rgba(0,0,0,0.5)';
      
      // Simple RGBA parser for Flutter
      Color sColor = Colors.black54;
      if (sColorStr.startsWith('rgba')) {
        try {
          final parts = sColorStr.replaceAll(RegExp(r'[a-zA-Z\(\)]'), '').split(',');
          if (parts.length >= 4) {
            sColor = Color.fromARGB(
              (double.parse(parts[3]) * 255).round(),
              int.parse(parts[0]),
              int.parse(parts[1]),
              int.parse(parts[2]),
            );
          }
        } catch (_) {}
      } else if (sColorStr.startsWith('#')) {
         sColorStr = sColorStr.replaceFirst('#', '0xFF');
         sColor = Color(int.tryParse(sColorStr) ?? 0xFF000000);
      }
      
      shadows = [Shadow(offset: Offset(ox, oy), blurRadius: bl, color: sColor)];
    }

    // Justification
    final String just = layer['justification'] ?? 'left';
    TextAlign textAlign = TextAlign.left;
    if (just == 'center') textAlign = TextAlign.center;
    if (just == 'right') textAlign = TextAlign.right;

    return Text(
      textValue,
      textAlign: textAlign,
      style: TextStyle(
        fontFamily: fontName.isNotEmpty ? fontName : null,
        color: fontColor,
        fontSize: fontSize,
        fontWeight: fontWeight,
        letterSpacing: letterSpacing,
        shadows: shadows,
        height: layer['line_height']?.toDouble() ?? 1.1,
      ),
    );
  }

  /// Resolves a relative `src` path from the JSON into a full HTTP URL.
  /// Handles `../skins/...` paths and bare filenames.
  /// Encodes spaces and special characters via Uri.encodeFull().
  String _resolveAssetUrl(String src) {
    if (src.isEmpty) return '';
    if (src.startsWith('http')) return src;

    String resolved;
    if (src.startsWith('../')) {
      resolved = '$templateBaseUrl/${src.replaceFirst('../', '')}';
    } else {
      resolved = '$templateBaseUrl/skins/$src';
    }

    // Encode spaces and special characters in the URL path
    // Split into base + path, encode only the path portion
    return Uri.encodeFull(resolved);
  }

  Widget _buildImageLayer(
      Map<String, dynamic> layer, String lname, double scale,
      double nativeW, double nativeH) {
    String src = layer['src'] ?? '';
    String? mappedImg;

    // Map AI injected images
    if (aiData != null && aiData!['_image_mappings'] != null) {
      final Map<String, dynamic> mappings = aiData!['_image_mappings'];
      final String cleanLName = lname.replaceAll(RegExp(r'[\s\-_]'), '');
      
      if (mappings[lname] != null) {
        mappedImg = mappings[lname];
      } else {
        for (var key in mappings.keys) {
          if (cleanLName == key.replaceAll(RegExp(r'[\s\-_]'), '').toLowerCase()) {
            mappedImg = mappings[key];
            break;
          }
        }
      }
      
      if (mappedImg == null && (cleanLName == 'image1' || cleanLName == 'mainimage')) {
        mappedImg = mappings['image1'] ?? mappings['main_image'] ?? mappings['image 1'];
      }
    }

    String finalUrl = '';
    BoxFit fit = BoxFit.cover;
    String pathType = 'unknown';

    if (mappedImg != null) {
      finalUrl = mappedImg;
      pathType = 'AI_MAPPED';
      if (!finalUrl.startsWith('http') && !finalUrl.startsWith('/') && !finalUrl.startsWith('data:')) {
        finalUrl = '$uploadsBaseUrl/$finalUrl';
      }
    } else if (lname == 'image1' || lname == 'main_image' || lname.startsWith('image')) {
      finalUrl = baseImgUrl ?? '';
      pathType = 'BASE_IMG';
    } else {
      // Template component image (icon, shape, overlay, etc.)
      finalUrl = _resolveAssetUrl(src);
      fit = BoxFit.contain; // Icons/overlays should contain
      pathType = 'TEMPLATE_ASSET';
    }

    // ══ DIAGNOSTICS ══
    debugPrint('[IMG_LAYER] name="$lname" pathType=$pathType native=${nativeW}x$nativeH isSmall=${(nativeW > 0 && nativeW < 50) || (nativeH > 0 && nativeH < 50)}');
    debugPrint('[IMG_LAYER]   src="$src"');
    debugPrint('[IMG_LAYER]   finalUrl="$finalUrl"');

    // Radius
    double? radius;
    if (lname.startsWith('image')) {
      radius = (layer['radius'] ?? 40).toDouble() * scale;
    }

    // Determine if this is a "small" decorator image that needs special handling
    final bool isSmallAsset = (nativeW > 0 && nativeW < 50) || (nativeH > 0 && nativeH < 50);

    return _buildImage(finalUrl, fit, radius, isSmall: isSmallAsset);
  }

  /// Builds an image widget.
  ///
  /// [isSmall] — When true, uses `Image.network` with `FilterQuality.high`
  /// instead of `CachedNetworkImage`. This is critical for Flutter Web where
  /// CachedNetworkImage silently fails to render images below ~10 logical px
  /// due to the HTML image element's caching/sizing quirks.
  Widget _buildImage(String url, BoxFit fit, double? radius,
      {required bool isSmall}) {
    if (url.isEmpty) {
      debugPrint('[IMG_BUILD] SKIP: empty URL');
      return const SizedBox.shrink();
    }

    // Ensure URL is properly encoded (handles spaces in filenames)
    if (url.startsWith('http') && url.contains(' ')) {
      final encoded = Uri.encodeFull(url);
      debugPrint('[IMG_BUILD] URL encoded: spaces found');
      url = encoded;
    }

    Widget imgWidget;

    if (!url.startsWith('http')) {
      debugPrint('[IMG_BUILD] SKIP: non-http URL: $url');
      return const SizedBox.shrink();
    }

    debugPrint('[IMG_BUILD] LOADING: isSmall=$isSmall renderer=${isSmall ? "Image.network" : "CachedNetworkImage"} url=$url');

    if (isSmall) {
      // ── SMALL ASSET PATH ──
      // Use raw Image.network with high filter quality.
      // CachedNetworkImage on Flutter Web uses an HTML <img> tag internally.
      // When the rendered size is tiny (< 10px), the browser may optimize it
      // away or the canvas renderer may skip it. Image.network with the
      // Skia/CanvasKit renderer handles this more reliably.
      imgWidget = Image.network(
        url,
        fit: fit,
        filterQuality: FilterQuality.high,
        errorBuilder: (_, error, __) {
          debugPrint('IMAGE LOAD ERROR ($url): $error');
          return const SizedBox.shrink();
        },
      );
    } else {
      // ── LARGE ASSET PATH ──
      // Use CachedNetworkImage for performance (caching, fade-in, etc.)
      imgWidget = CachedNetworkImage(
        imageUrl: url,
        fit: fit,
        filterQuality: FilterQuality.high,
        errorListener: (err) {
          debugPrint('IMAGE LOAD ERROR: $url');
        },
        errorWidget: (_, __, ___) => const SizedBox.shrink(),
      );
    }

    if (radius != null && radius > 0) {
      return ClipRRect(
        borderRadius: BorderRadius.circular(radius),
        child: imgWidget,
      );
    }
    return imgWidget;
  }
}

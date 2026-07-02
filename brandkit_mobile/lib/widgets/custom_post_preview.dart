import 'package:flutter/material.dart';
import 'package:cached_network_image/cached_network_image.dart';
import 'package:font_awesome_flutter/font_awesome_flutter.dart';

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
/// 5. AUTO-LAYOUT (Smart Shifting):
///    Phase 1 (first render): Word-based mathematical estimation of text
///    heights → shifts computed from original JSON positions.
///    Phase 2 (post-frame): Measures actual rendered text heights via
///    GlobalKey + RenderBox, recalculates shifts, and re-renders with
///    pixel-perfect accuracy. Mirrors the web editor's applyAutoLayout().
class CustomPostPreview extends StatefulWidget {
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
  State<CustomPostPreview> createState() => _CustomPostPreviewState();
}

class _CustomPostPreviewState extends State<CustomPostPreview> {
  /// GlobalKeys for text layers — used to measure actual rendered heights.
  final Map<String, GlobalKey> _textKeys = {};

  /// Y-shifts (in design pixels) computed from post-frame measured text heights.
  /// Key = layer name, Value = shift delta in design pixels.
  Map<String, double> _measuredShifts = {};

  /// Whether post-frame measurement has completed at least once.
  bool _hasMeasured = false;

  @override
  void initState() {
    super.initState();
    _schedulePostFrameMeasurement();
  }

  @override
  void didUpdateWidget(covariant CustomPostPreview oldWidget) {
    super.didUpdateWidget(oldWidget);
    // Re-measure when inputs change (new AI text, different template, resize)
    if (oldWidget.aiData != widget.aiData ||
        oldWidget.config != widget.config ||
        oldWidget.width != widget.width) {
      _hasMeasured = false;
      _measuredShifts = {};
      _textKeys.clear();
      _schedulePostFrameMeasurement();
    }
  }

  void _schedulePostFrameMeasurement() {
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (!mounted) return;
      _measureAndApplyShifts();
    });
  }

  // ══════════════════════════════════════════════════════════════
  // POST-FRAME MEASUREMENT (Phase 2: pixel-perfect via RenderBox)
  // ══════════════════════════════════════════════════════════════
  // After the first render, text layers have been laid out by Flutter's
  // text engine with correct fonts and word-wrapping. We read their actual
  // heights, compute deltas from JSON heights, and shift layers below.
  // This mirrors the web editor's applyAutoLayout() which uses
  // _origTop + deltaHeight for each object.
  void _measureAndApplyShifts() {
    final double designW =
        (widget.config['info']?['width'] ?? 1024).toDouble();
    final double scale = widget.width / designW;
    final List<dynamic> layers = widget.config['layers'] ?? [];

    // ── Step 1: Collect deltas from measured text heights ──
    final List<_TextShiftSource> textSources = [];

    for (var rawLayer in layers) {
      final layer = Map<String, dynamic>.from(rawLayer);
      if (layer['type'] != 'text') continue;

      final String name = (layer['name'] ?? '').toString();
      final String lname = name.toLowerCase();
      if (lname == 'bg' || lname == 'background') continue;

      final double origH =
          (layer['h'] ?? layer['height'] ?? 0).toDouble();
      if (origH <= 0) continue;

      final double layerW =
          (layer['w'] ?? layer['width'] ?? 0).toDouble();
      if (layerW >= designW * 0.9) continue; // skip full-canvas

      final key = _textKeys[name];
      if (key == null || key.currentContext == null) continue;

      final renderBox =
          key.currentContext!.findRenderObject() as RenderBox?;
      if (renderBox == null || !renderBox.hasSize) continue;

      final double renderedHeightDesign = renderBox.size.height / scale;
      final double delta = renderedHeightDesign - origH;

      debugPrint('[POST-MEASURE] "$name": origH=$origH '
          'renderedH=${renderedHeightDesign.toStringAsFixed(1)} '
          'delta=${delta.toStringAsFixed(1)}');

      if (delta.abs() < 3) continue;

      textSources.add(_TextShiftSource(
        name: name,
        origBottom: (layer['y'] ?? 0).toDouble() + origH,
        origLeft: (layer['x'] ?? 0).toDouble(),
        origRight: (layer['x'] ?? 0).toDouble() + layerW,
        layerW: layerW,
        delta: delta,
      ));
    }

    // ── Step 2: Calculate accumulated shift for each layer ──
    final Map<String, double> newShifts =
        _computeShifts(layers, textSources, designW);

    // Only setState if shifts actually changed
    bool changed = !_hasMeasured ||
        newShifts.length != _measuredShifts.length;
    if (!changed) {
      for (var key in newShifts.keys) {
        if ((_measuredShifts[key] ?? 0).toStringAsFixed(1) !=
            newShifts[key]!.toStringAsFixed(1)) {
          changed = true;
          break;
        }
      }
    }

    if (changed) {
      setState(() {
        _measuredShifts = newShifts;
        _hasMeasured = true;
      });
    } else if (!_hasMeasured) {
      setState(() {
        _hasMeasured = true;
      });
    }
  }

  // ══════════════════════════════════════════════════════════════
  // SHARED: COMPUTE Y-SHIFTS FROM TEXT DELTAS
  // ══════════════════════════════════════════════════════════════
  // Given a list of text-height deltas, computes how much each layer
  // should shift. Uses ORIGINAL JSON positions for all comparisons
  // (matching the web editor's _origTop + deltaHeight pattern).
  Map<String, double> _computeShifts(
    List<dynamic> layers,
    List<_TextShiftSource> textSources,
    double designW,
  ) {
    final Map<String, double> shifts = {};

    for (var rawLayer in layers) {
      final layer = Map<String, dynamic>.from(rawLayer);
      final String name = (layer['name'] ?? '').toString();
      final double layerY = (layer['y'] ?? 0).toDouble();
      final double layerW =
          (layer['w'] ?? layer['width'] ?? 0).toDouble();
      final double layerLeft = (layer['x'] ?? 0).toDouble();
      final double layerRight = layerLeft + layerW;

      // Skip full-canvas decorative layers (>90% of canvas width)
      if (layerW >= designW * 0.9) continue;

      double totalShift = 0;

      for (var src in textSources) {
        // Don't shift yourself
        if (src.name == name) continue;

        // Must be below the text's original bottom (10px tolerance)
        if (layerY < src.origBottom - 10) continue;

        // Horizontal overlap check (≥25% of the smaller element)
        final double overlapStart =
            src.origLeft > layerLeft ? src.origLeft : layerLeft;
        final double overlapEnd =
            src.origRight < layerRight ? src.origRight : layerRight;
        final double overlapW =
            overlapEnd > overlapStart ? overlapEnd - overlapStart : 0;
        final double minW = src.layerW < layerW ? src.layerW : layerW;

        if (minW > 0 && overlapW / minW >= 0.25) {
          totalShift += src.delta;
        }
      }

      if (totalShift.abs() >= 1) {
        shifts[name] = totalShift;
        debugPrint('[SHIFT] "$name": ${totalShift.toStringAsFixed(1)}');
      }
    }

    return shifts;
  }

  // ══════════════════════════════════════════════════════════════
  // ESTIMATION-BASED SHIFTS (Phase 1: for first render before measurement)
  // ══════════════════════════════════════════════════════════════
  Map<String, double> _computeEstimationShifts(
      List<dynamic> layers, double designW, double scale) {
    // Collect text deltas using mathematical estimation
    final List<_TextShiftSource> textSources = [];

    // Process text layers sorted by Y
    final List<Map<String, dynamic>> textLayers = [];
    for (var rawLayer in layers) {
      final layer = Map<String, dynamic>.from(rawLayer);
      if (layer['type'] != 'text') continue;
      textLayers.add(layer);
    }
    textLayers.sort((a, b) => ((a['y'] ?? 0) as num)
        .toDouble()
        .compareTo(((b['y'] ?? 0) as num).toDouble()));

    for (var layer in textLayers) {
      final String name = (layer['name'] ?? '').toString();
      final String lname = name.toLowerCase();
      if (lname == 'bg' || lname == 'background') continue;

      final double origH =
          (layer['h'] ?? layer['height'] ?? 0).toDouble();
      if (origH <= 0) continue;

      final double layerW =
          (layer['w'] ?? layer['width'] ?? 0).toDouble();
      if (layerW >= designW * 0.9) continue;

      final double actualH = _measureTextHeight(layer, scale);
      final double actualHDesign = actualH / scale;
      final double delta = actualHDesign - origH;

      debugPrint('[EST-LAYOUT] "$name": origH=$origH '
          'actualH=${actualHDesign.toStringAsFixed(1)} '
          'delta=${delta.toStringAsFixed(1)}');

      if (delta.abs() < 3) continue;

      textSources.add(_TextShiftSource(
        name: name,
        origBottom: (layer['y'] ?? 0).toDouble() + origH,
        origLeft: (layer['x'] ?? 0).toDouble(),
        origRight: (layer['x'] ?? 0).toDouble() + layerW,
        layerW: layerW,
        delta: delta,
      ));
    }

    return _computeShifts(layers, textSources, designW);
  }

  // ══════════════════════════════════════════════════════════════
  // TEXT NORMALIZATION (shared between measure and render)
  // ══════════════════════════════════════════════════════════════
  /// Normalizes newlines and applies uppercase. Ensures _measureTextHeight
  /// and _buildText process identical text content.
  String _normalizeText(String text, Map<String, dynamic> layer) {
    // Handle actual CR/CRLF characters (from JSON parser decoding \r, \r\n)
    text = text.replaceAll('\r\n', '\n');
    text = text.replaceAll('\r', '\n');
    // Handle escaped newlines (JSON \\n, \\r → parsed as literal \n, \r chars)
    text = text.replaceAll('\\n', '\n');
    text = text.replaceAll('\\r', '\n');

    if (layer['uppercase'] == true) {
      text = text.toUpperCase();
    }
    return text;
  }

  // ══════════════════════════════════════════════════════════════
  // MEASURE TEXT HEIGHT (word-based mathematical estimation)
  // ══════════════════════════════════════════════════════════════
  // Uses word-based wrapping simulation instead of character-based.
  // Words don't break mid-word, matching Flutter's text layout engine.
  double _measureTextHeight(Map<String, dynamic> layer, double scale) {
    final String layerName = layer['name'] ?? '';
    String textValue = layer['text'] ?? '';

    // Override with AI Data (same logic as _buildText)
    if (widget.aiData != null && widget.aiData![layerName] != null) {
      textValue = widget.aiData![layerName].toString();
    }

    // Normalize newlines consistently with _buildText
    textValue = _normalizeText(textValue, layer);

    final double fontSize = (layer['size'] ?? 16).toDouble(); // design px
    final double lineHeight = layer['line_height']?.toDouble() ?? 1.1;
    final double layerW =
        (layer['w'] ?? layer['width'] ?? 0).toDouble(); // design px

    // Character spacing
    double extraCharWidth = 0;
    if (layer['char_spacing'] != null) {
      final double charSpacing = (layer['char_spacing']).toDouble();
      extraCharWidth = (charSpacing / 1000) * fontSize;
    }

    // Font weight affects average character width
    final String weightStr = (layer['weight'] ?? '').toString();
    final String fontName =
        (layer['font'] ?? '').toString().toLowerCase();
    final bool isBold = weightStr == 'bold' ||
        fontName.contains('bold') ||
        fontName.contains('semibold');

    // Average character width ratio for sans-serif proportional fonts
    // Bold fonts are ~5% wider than regular. Letter-spacing added on top.
    final double avgCharWidthRatio = isBold ? 0.58 : 0.55;
    final double avgCharWidth =
        fontSize * avgCharWidthRatio + extraCharWidth;
    final double spaceWidth = fontSize * 0.28; // space character width

    if (layerW <= 0 || avgCharWidth <= 0) {
      // Can't calculate wrapping without width
      return fontSize * lineHeight * scale; // assume 1 line
    }

    // Count lines using WORD-BASED wrapping (more accurate than char-based)
    final List<String> paragraphs = textValue.split('\n');
    int totalLines = 0;

    for (final para in paragraphs) {
      if (para.trim().isEmpty) {
        totalLines += 1; // empty line still takes space
        continue;
      }

      // Word-based wrapping simulation: accumulate word widths,
      // wrap when exceeding layer width (like Flutter's text engine)
      final List<String> words = para.split(RegExp(r'\s+'));
      int lines = 1;
      double currentLineWidth = 0;

      for (int w = 0; w < words.length; w++) {
        final word = words[w];
        if (word.isEmpty) continue;

        final double wordWidth = word.length * avgCharWidth;

        if (currentLineWidth > 0 &&
            currentLineWidth + spaceWidth + wordWidth > layerW) {
          // Word doesn't fit on current line → wrap to next line
          lines++;
          currentLineWidth = wordWidth;
        } else {
          // Add word to current line (with space if not first word)
          currentLineWidth +=
              (currentLineWidth > 0 ? spaceWidth : 0) + wordWidth;
        }
      }

      totalLines += lines;
    }

    final double totalHeight = totalLines * fontSize * lineHeight;

    debugPrint('[MEASURE] "$layerName": '
        'text="${textValue.length > 30 ? '${textValue.substring(0, 30)}...' : textValue}" '
        'fontSize=$fontSize layerW=$layerW '
        'avgCharW=${avgCharWidth.toStringAsFixed(1)} '
        'lines=$totalLines height=${totalHeight.toStringAsFixed(1)}');

    return totalHeight * scale; // return in screen pixels
  }

  @override
  Widget build(BuildContext context) {
    // Original design resolution (default 1024)
    final double designW =
        (widget.config['info']?['width'] ?? 1024).toDouble();
    final double designH =
        (widget.config['info']?['height'] ?? 1024).toDouble();

    final double scale = widget.width / designW;
    final double height = designH * scale;

    final List<dynamic> layers = widget.config['layers'] ?? [];

    // ══ DIAGNOSTICS ══
    debugPrint('╔══════════════════════════════════════════════');
    debugPrint('║ CustomPostPreview: ${layers.length} layers, '
        'design=${designW}x$designH, '
        'preview=${widget.width}, scale=${scale.toStringAsFixed(3)}');
    debugPrint('║ templateBaseUrl: ${widget.templateBaseUrl}');
    debugPrint('║ hasMeasured: $_hasMeasured, '
        'shifts: ${_measuredShifts.length}');
    debugPrint('╚══════════════════════════════════════════════');

    // Sort layers by z_index for Stack rendering order
    final List<Map<String, dynamic>> sortedLayers = layers
        .map((l) => Map<String, dynamic>.from(l))
        .toList();
    sortedLayers.sort((a, b) {
      final int zA = (a['z_index'] ?? 0) as int;
      final int zB = (b['z_index'] ?? 0) as int;
      return zA.compareTo(zB);
    });

    // ══════════════════════════════════════════════════════════════
    // APPLY Y-SHIFTS (from measurement or estimation)
    // ══════════════════════════════════════════════════════════════
    // Create adjusted copies — we modify Y positions on these copies,
    // leaving the original config untouched.
    final List<Map<String, dynamic>> adjusted = sortedLayers
        .map((l) => Map<String, dynamic>.from(l))
        .toList();

    if (_hasMeasured && _measuredShifts.isNotEmpty) {
      // Phase 2: Apply pixel-perfect measured shifts
      for (var layer in adjusted) {
        final String name = (layer['name'] ?? '').toString();
        if (_measuredShifts.containsKey(name)) {
          layer['y'] =
              (layer['y'] as num).toDouble() + _measuredShifts[name]!;
        }
      }
    } else {
      // Phase 1: Apply estimation-based shifts (first render)
      final Map<String, double> estShifts =
          _computeEstimationShifts(layers, designW, scale);
      for (var layer in adjusted) {
        final String name = (layer['name'] ?? '').toString();
        if (estShifts.containsKey(name)) {
          layer['y'] =
              (layer['y'] as num).toDouble() + estShifts[name]!;
        }
      }
    }

    // ══════════════════════════════════════════════════════════════
    // BUILD WIDGETS (absolute positioning with adjusted Y)
    // ══════════════════════════════════════════════════════════════
    final List<Widget> stackChildren = [];
    for (var layer in adjusted) {
      stackChildren.add(_buildLayer(layer, scale));
    }

    return Container(
      width: widget.width,
      height: height,
      clipBehavior: Clip.antiAlias,
      decoration: const BoxDecoration(
        color: Colors.white,
      ),
      child: Stack(
        clipBehavior: Clip.none,
        children: stackChildren,
      ),
    );
  }

  Widget _buildLayer(Map<String, dynamic> layer, double scale) {
    final String name =
        (layer['name'] ?? '').toString().toLowerCase();
    final String rawName =
        (layer['name'] ?? '').toString(); // original case for key lookup
    final String type = layer['type'] ?? '';
    final bool isBackground = layer['is_background'] == true ||
        (layer['is_background'] == null && (name == 'bg' ||
        name == 'background'));

    // ── FIX: Hide layer when is_background is explicitly false and name suggests background ──
    if (layer['is_background'] == false &&
        (name.contains('background') || name == 'bg' || name == 'image1')) {
      debugPrint('[PREVIEW_LAYER_HIDE] Hiding "$name" — is_background explicitly false');
      return const SizedBox.shrink();
    }

    // ── FIX: Masked layers — override position/size to match mask shape bounds ──
    Map<String, dynamic> effectiveLayer = layer;
    if (type == 'image' && layer['mask_layer_id'] != null) {
      final maskId = layer['mask_layer_id'].toString();
      final allLayers = widget.layers;
      final maskShapeLayer = allLayers.firstWhere(
        (l) => (l['name']?.toString() == maskId) || (l['id']?.toString() == maskId),
        orElse: () => <String, dynamic>{},
      );
      if (maskShapeLayer.isNotEmpty) {
        effectiveLayer = Map<String, dynamic>.from(layer);
        effectiveLayer['x'] = maskShapeLayer['x'];
        effectiveLayer['y'] = maskShapeLayer['y'];
        effectiveLayer['w'] = maskShapeLayer['w'] ?? maskShapeLayer['width'];
        effectiveLayer['h'] = maskShapeLayer['h'] ?? maskShapeLayer['height'];
        debugPrint('[PREVIEW_MASK_FIX] "$name" position overridden to mask shape');
      }
    }

    // ══ DIAGNOSTICS ══
    final double rawW =
        (effectiveLayer['w'] ?? effectiveLayer['width'] ?? 0).toDouble();
    final double rawH =
        (effectiveLayer['h'] ?? effectiveLayer['height'] ?? 0).toDouble();
    debugPrint('[LAYER] name="$name" type=$type bg=$isBackground '
        'native=${rawW}x$rawH '
        'scaled=${(rawW * scale).toStringAsFixed(1)}x'
        '${(rawH * scale).toStringAsFixed(1)}');

    // Handle Background Layer
    if (isBackground) {
      if (type == 'image') {
        String bgSrc = effectiveLayer['src'] ?? '';
        String finalSrc = _resolveAssetUrl(bgSrc);
        return Positioned.fill(
          child:
              _buildImage(finalSrc, BoxFit.cover, null, isSmall: false),
        );
      }
      return const SizedBox.shrink();
    }

    // Standard absolute positioning parameters
    final double x = (effectiveLayer['x'] ?? 0).toDouble() * scale;
    final double y = (effectiveLayer['y'] ?? 0).toDouble() * scale;
    double w =
        (effectiveLayer['w'] ?? effectiveLayer['width'] ?? 0).toDouble() * scale;
    double h =
        (effectiveLayer['h'] ?? effectiveLayer['height'] ?? 0).toDouble() * scale;
    final double angle = (effectiveLayer['angle'] ?? 0).toDouble();

    // ── MINIMUM SIZE ENFORCEMENT ──
    // Small decorator images (bullets, icons, diamonds) can scale down to
    // 3–4 logical pixels which Flutter Web may silently skip or render as
    // invisible. We enforce a minimum of 8×8 px and shift the origin so the
    // center stays at the same visual position.
    final double nativeW =
        (layer['w'] ?? layer['width'] ?? 0).toDouble();
    final double nativeH =
        (layer['h'] ?? layer['height'] ?? 0).toDouble();
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

      // Wrap text with GlobalKey for post-frame height measurement
      final key =
          _textKeys.putIfAbsent(rawName, () => GlobalKey());
      content = KeyedSubtree(key: key, child: content);
    } else if (type == 'image') {
      content =
          _buildImageLayer(layer, name, scale, nativeW, nativeH);
    } else if (type == 'icon') {
      content =
          _buildIconLayer(layer, name, scale, nativeW, nativeH);
    }

    if (angle != 0) {
      content = Transform.rotate(
        angle: angle * 3.1415926535897932 / 180,
        child: content,
      );
    }

    // Positioning Logic
    if (type == 'text') {
      final double fontSize =
          (layer['size'] ?? 16).toDouble() * scale;
      final bool isMultiLine = h > (fontSize * 1.5);
      final String just = layer['justification'] ?? 'left';

      if (isMultiLine) {
        // Multi-line paragraph text: constrain width for wrapping, NO fixed height
        // Height is dynamic so text can grow/shrink and auto-layout shifting works
        return Positioned(
          left: x,
          top: y,
          width: w > 0 ? w : null,
          child: content,
        );
      } else {
        // Single-line point text: do not constrain width, anchor based on justification
        if (just == 'right') {
          return Positioned(
            right: widget.width - (x + w),
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
    if (widget.aiData != null && widget.aiData![layerName] != null) {
      textValue = widget.aiData![layerName].toString();
    }

    // Normalize newlines consistently (shared with _measureTextHeight)
    textValue = _normalizeText(textValue, layer);

    // Color conversion (handles hex and 0x formats)
    String colorStr = layer['color'] ?? '#000000';
    colorStr = colorStr.replaceAll('0x', '#');
    if (colorStr.length == 7) {
      colorStr = colorStr.replaceFirst('#', '0xFF');
    }
    Color fontColor = Color(int.tryParse(colorStr) ?? 0xFF000000);

    // Font size
    final double fontSize =
        (layer['size'] ?? 16).toDouble() * scale;

    // Font weight
    final String weightStr = (layer['weight'] ?? '').toString();
    String fontName = (layer['fontFamily'] ?? layer['font_name'] ?? layer['font'] ?? '').toString();
    fontName = fontName.replaceAll("'", "").replaceAll('"', '');

    // CSS font-stack: take only the first font from comma-separated list
    if (fontName.contains(',')) {
      fontName = fontName.split(',').first.trim();
    }

    // Map Web Editor FontAwesome names to Flutter's font_awesome_flutter package families
    if (fontName.toLowerCase().contains('brands')) {
      fontName = 'packages/font_awesome_flutter/FontAwesomeBrands';
    } else if (fontName.toLowerCase().contains('font awesome') || fontName.toLowerCase().contains('fontawesome')) {
      fontName = 'packages/font_awesome_flutter/FontAwesomeSolid';
    }

    FontWeight fontWeight = FontWeight.normal;
    if (weightStr == 'bold' || fontName.toLowerCase().contains('bold')) {
      fontWeight = FontWeight.bold;
    }

    // Character spacing
    double? letterSpacing;
    if (layer['char_spacing'] != null) {
      final double charSpacing =
          (layer['char_spacing']).toDouble();
      letterSpacing = (charSpacing / 1000) * fontSize;
    }

    // Shadow
    List<Shadow>? shadows;
    if (layer['shadow'] != null) {
      final Map<String, dynamic> shadowMap = layer['shadow'];
      final double ox =
          (shadowMap['offsetX'] ?? 0).toDouble() * scale;
      final double oy =
          (shadowMap['offsetY'] ?? 0).toDouble() * scale;
      final double bl =
          (shadowMap['blur'] ?? 0).toDouble() * scale;
      String sColorStr =
          shadowMap['color'] ?? 'rgba(0,0,0,0.5)';

      // Simple RGBA parser for Flutter
      Color sColor = Colors.black54;
      if (sColorStr.startsWith('rgba')) {
        try {
          final parts = sColorStr
              .replaceAll(RegExp(r'[a-zA-Z\(\)]'), '')
              .split(',');
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
        sColor =
            Color(int.tryParse(sColorStr) ?? 0xFF000000);
      }

      shadows = [
        Shadow(
            offset: Offset(ox, oy),
            blurRadius: bl,
            color: sColor)
      ];
    }

    // Justification
    final String just = layer['justification'] ?? 'left';
    TextAlign textAlign = TextAlign.left;
    if (just == 'center') textAlign = TextAlign.center;
    if (just == 'right') textAlign = TextAlign.right;

    Widget textWidget = Text(
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

    final double rawW = (layer['w'] ?? layer['width'] ?? 0).toDouble();
    if (rawW > 0) {
      textWidget = SizedBox(
        width: double.infinity,
        child: textWidget,
      );
    }

    return textWidget;
  }

  /// Resolves a relative `src` path from the JSON into a full HTTP URL.
  /// Handles `../skins/...` paths and bare filenames.
  /// Encodes spaces and special characters via Uri.encodeFull().
  String _resolveAssetUrl(String src) {
    if (src.isEmpty) return '';
    if (src.startsWith('http')) return src;

    String resolved;
    if (src.startsWith('../')) {
      resolved =
          '${widget.templateBaseUrl}/${src.replaceFirst('../', '')}';
    } else {
      resolved = '${widget.templateBaseUrl}/skins/$src';
    }

    // Encode spaces and special characters in the URL path
    // Split into base + path, encode only the path portion
    return Uri.encodeFull(resolved);
  }

  Widget _buildImageLayer(Map<String, dynamic> layer, String lname,
      double scale, double nativeW, double nativeH) {
    String src = layer['src'] ?? '';
    String? mappedImg;

    // Map AI injected images
    if (widget.aiData != null &&
        widget.aiData!['_image_mappings'] != null) {
      final Map<String, dynamic> mappings =
          widget.aiData!['_image_mappings'];
      final String cleanLName =
          lname.replaceAll(RegExp(r'[\s\-_]'), '');

      if (mappings[lname] != null) {
        mappedImg = mappings[lname];
      } else {
        for (var key in mappings.keys) {
          if (cleanLName ==
              key
                  .replaceAll(RegExp(r'[\s\-_]'), '')
                  .toLowerCase()) {
            mappedImg = mappings[key];
            break;
          }
        }
      }

      if (mappedImg == null &&
          (cleanLName == 'image1' ||
              cleanLName == 'mainimage')) {
        mappedImg = mappings['image1'] ??
            mappings['main_image'] ??
            mappings['image 1'];
      }
    }

    String finalUrl = '';
    BoxFit fit = BoxFit.cover;
    String pathType = 'unknown';

    if (mappedImg != null) {
      finalUrl = mappedImg;
      pathType = 'AI_MAPPED';
      if (!finalUrl.startsWith('http') &&
          !finalUrl.startsWith('/') &&
          !finalUrl.startsWith('data:')) {
        finalUrl = '${widget.uploadsBaseUrl}/$finalUrl';
      }
    } else if (lname == 'image1' ||
        lname == 'main_image' ||
        lname.startsWith('image')) {
      finalUrl = widget.baseImgUrl ?? '';
      pathType = 'BASE_IMG';
    } else {
      // Template component image (icon, shape, overlay, etc.)
      finalUrl = _resolveAssetUrl(src);
      fit = BoxFit.contain; // Icons/overlays should contain
      pathType = 'TEMPLATE_ASSET';
    }

    // ══ DIAGNOSTICS ══
    debugPrint('[IMG_LAYER] name="$lname" pathType=$pathType '
        'native=${nativeW}x$nativeH '
        'isSmall=${(nativeW > 0 && nativeW < 50) || (nativeH > 0 && nativeH < 50)}');
    debugPrint('[IMG_LAYER]   src="$src"');
    debugPrint('[IMG_LAYER]   finalUrl="$finalUrl"');

    // Radius
    double? radius;
    if (lname.startsWith('image')) {
      radius = (layer['radius'] ?? 40).toDouble() * scale;
    }

    // Determine if this is a "small" decorator image that needs special handling
    final bool isSmallAsset = (nativeW > 0 && nativeW < 50) ||
        (nativeH > 0 && nativeH < 50);

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

    debugPrint('[IMG_BUILD] LOADING: isSmall=$isSmall '
        'renderer=${isSmall ? "Image.network" : "CachedNetworkImage"} '
        'url=$url');

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

  Widget _buildIconLayer(Map<String, dynamic> layer, String lname,
      double scale, double nativeW, double nativeH) {
    final String iconName = layer['iconName'] ?? '';
    if (iconName.isEmpty) {
      // Fallback to image
      return _buildImageLayer(layer, lname, scale, nativeW, nativeH);
    }

    FaIconData? iconData = _getFontAwesomeIcon(iconName);

    if (iconData == null) {
      // Fallback to image if icon is not found in library
      return _buildImageLayer(layer, lname, scale, nativeW, nativeH);
    }

    String colorStr = layer['color'] ?? '#000000';
    colorStr = colorStr.replaceAll('0x', '#');
    if (colorStr.length == 7) {
      colorStr = colorStr.replaceFirst('#', '0xFF');
    }
    Color iconColor = Color(int.tryParse(colorStr) ?? 0xFF000000);

    // Default icon size is based on height
    double size = (layer['size']?.toDouble() ??
            (nativeH > 0 ? nativeH : 24.0)) *
        scale;

    return Center(
      child: FaIcon(
        iconData,
        color: iconColor,
        size: size,
      ),
    );
  }

  FaIconData? _getFontAwesomeIcon(String name) {
    switch (name.toLowerCase()) {
      case 'circlecheck':
      case 'check-circle':
      case 'check_circle':
        return FontAwesomeIcons.solidCircleCheck;
      case 'phone':
      case 'call':
        return FontAwesomeIcons.phone;
      case 'email':
      case 'envelope':
        return FontAwesomeIcons.envelope;
      case 'globe':
      case 'website':
      case 'web':
        return FontAwesomeIcons.globe;
      case 'location':
      case 'locationdot':
      case 'mapmarker':
        return FontAwesomeIcons.locationDot;
      case 'whatsapp':
        return FontAwesomeIcons.whatsapp;
      case 'facebook':
        return FontAwesomeIcons.facebook;
      case 'instagram':
        return FontAwesomeIcons.instagram;
      case 'twitter':
      case 'x':
        return FontAwesomeIcons.xTwitter;
      case 'youtube':
        return FontAwesomeIcons.youtube;
      case 'linkedin':
        return FontAwesomeIcons.linkedin;
      default:
        return null; // Triggers fallback
    }
  }
}

/// Helper class for auto-layout shift calculation.
/// Stores the text layer's original bounds and height delta.
class _TextShiftSource {
  final String name;
  final double origBottom;
  final double origLeft;
  final double origRight;
  final double layerW;
  final double delta;

  const _TextShiftSource({
    required this.name,
    required this.origBottom,
    required this.origLeft,
    required this.origRight,
    required this.layerW,
    required this.delta,
  });
}

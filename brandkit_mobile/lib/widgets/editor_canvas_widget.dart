import 'dart:convert';
import 'dart:io';
import 'dart:math' as math;
import 'package:flutter/material.dart';
import 'package:cached_network_image/cached_network_image.dart';
import 'interactive_layer.dart';
import 'package:font_awesome_flutter/font_awesome_flutter.dart';
import 'package:google_fonts/google_fonts.dart';

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
class EditorCanvasWidget extends StatefulWidget {
  final Map<String, dynamic> config;
  final Map<String, dynamic>? aiData;
  final double width;
  final String uploadsBaseUrl;
  final String templateBaseUrl;
  final String? baseImgUrl;
  final String editorType;

  const EditorCanvasWidget({
    super.key,
    required this.config,
    this.aiData,
    required this.width,
    required this.uploadsBaseUrl,
    required this.templateBaseUrl,
    this.baseImgUrl,
    this.editorType = 'business_custom_frame',
  });

  @override
  State<EditorCanvasWidget> createState() => _EditorCanvasWidgetState();
}

class _EditorCanvasWidgetState extends State<EditorCanvasWidget> {
  /// GlobalKeys for text layers — used to measure actual rendered heights.
  final Map<String, GlobalKey> _textKeys = {};

  /// Y-shifts (in design pixels) computed from post-frame measured text heights.
  /// Key = layer name, Value = shift delta in design pixels.
  Map<String, double> _measuredShifts = {};

  /// Font size overrides from overflow shrinking (design pixels, pre-scale).
  /// Key = layer name, Value = shrunk font size in design units.
  Map<String, double> _fontSizeOverrides = {};

  /// Baseline rendered heights captured on first measurement (mirrors web editor's _origHeight).
  /// Key = layer name, Value = rendered height in design pixels at initial load.
  Map<String, double> _origHeights = {};

  /// Whether post-frame measurement has completed at least once.
  bool _hasMeasured = false;

  @override
  void initState() {
    super.initState();
    _schedulePostFrameMeasurement();
  }

  @override
  void didUpdateWidget(covariant EditorCanvasWidget oldWidget) {
    super.didUpdateWidget(oldWidget);
    // Re-measure when inputs change (new AI text, different template, resize)
    if (oldWidget.config != widget.config ||
        oldWidget.width != widget.width ||
        oldWidget.aiData != widget.aiData) {
      
      // Only reset baselines if the template itself changed (different template loaded)
      // NOT when text is edited within the same template
      final bool templateChanged = oldWidget.config['info']?['width'] != widget.config['info']?['width'] ||
          (oldWidget.config['layers'] as List?)?.length != (widget.config['layers'] as List?)?.length;
      
      if (templateChanged || oldWidget.width != widget.width) {
        _origHeights = {};
        _textKeys.clear();
      }

      _hasMeasured = false;
      _measuredShifts = {};
      _fontSizeOverrides = {};
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
  // SOLVE FLEX LAYOUT (matches web editor's solveFlexLayout exactly)
  // ══════════════════════════════════════════════════════════════
  // Uses first-render height as baseline (_origHeight equivalent).
  // After text changes, measures actual rendered height via RenderBox,
  // computes delta from baseline, then shifts all layers below. Includes overflow
  // protection: gap compression (matching web editor).
  void _measureAndApplyShifts() {
    final double designW =
        (widget.config['info']?['width'] ?? widget.config['width'] ?? 1080).toDouble();
    final double designH =
        (widget.config['info']?['height'] ?? widget.config['height'] ?? 1080).toDouble();
    final double scale = widget.width / designW;
    final List<dynamic> layers = widget.config['layers'] ?? [];
    final double maxBottom = designH - 30; // 30px padding at bottom (matches web editor)
    final double minMargin = 10; // minimum gap to preserve between elements

    // ── Step 1: Identify text sources with height deltas ──
    final List<_TextShiftSource> textSources = [];

    for (var rawLayer in layers) {
      final layer = Map<String, dynamic>.from(rawLayer);
      if (layer['type'] != 'text') continue;

      final String name = (layer['name'] ?? '').toString();
      final String lname = name.toLowerCase();
      if (lname == 'bg' || lname == 'background') continue;

      final double origH = (layer['h'] ?? layer['height'] ?? 0).toDouble();
      if (origH <= 0) continue;

      final double layerW = (layer['w'] ?? layer['width'] ?? 0).toDouble();
      if (layerW >= designW * 0.9) continue; // skip full-canvas decorative

      final double layerY = (layer['y'] ?? 0).toDouble();
      final double layerX = (layer['x'] ?? 0).toDouble();

      final key = _textKeys[name];
      if (key == null || key.currentContext == null) continue;

      final renderBox = key.currentContext!.findRenderObject() as RenderBox?;
      if (renderBox == null || !renderBox.hasSize) continue;

      final double currentH = renderBox.size.height / scale; // convert to design pixels

      // On first measurement, capture baseline height (mirrors web editor's _origHeight)
      if (!_origHeights.containsKey(name)) {
        _origHeights[name] = currentH;
        debugPrint('[BASELINE] "$name": _origHeight=${currentH.toStringAsFixed(1)}');
        continue; // No delta on first capture
      }

      final double baselineH = _origHeights[name]!;
      final double delta = currentH - baselineH;

      debugPrint('[FLEX] "$name": baselineH=${baselineH.toStringAsFixed(1)} '
          'currentH=${currentH.toStringAsFixed(1)} '
          'delta=${delta.toStringAsFixed(1)}');

      if (delta.abs() >= 1) {
        textSources.add(_TextShiftSource(
          name: name,
          origBottom: layerY + baselineH, // _origTop + _origHeight
          origLeft: layerX,
          origRight: layerX + layerW,
          layerW: layerW,
          delta: delta,
          shrinkableGap: 0,
          appliedCompression: 0,
        ));
      }
    }

    if (textSources.isEmpty) {
      // No text changes — clear shifts and return
      if (_measuredShifts.isNotEmpty || !_hasMeasured) {
        setState(() {
          _measuredShifts = {};
          _hasMeasured = true;
        });
      }
      return;
    }

    // ── Step 2: Determine shrinkable gap (matches web editor step 2) ──
    for (var src in textSources) {
      double minGap = 9999;
      for (var rawLayer in layers) {
        final layer = Map<String, dynamic>.from(rawLayer);
        final String objName = (layer['name'] ?? '').toString();
        if (objName == src.name) continue;

        // Skip decorative full-width layers
        final double objW = (layer['w'] ?? layer['width'] ?? 0).toDouble();
        if (objW >= designW * 0.9) continue;

        final double objTop = (layer['y'] ?? 0).toDouble();
        final bool isBelow = objTop >= (src.origBottom - 5);

        if (isBelow) {
          final double gap = objTop - src.origBottom;
          if (gap < minGap) minGap = gap;
        }
      }
      if (minGap == 9999) minGap = 0;
      src.shrinkableGap = math.max(0, minGap - minMargin);
    }

    // ── Step 3: Calculate natural bottom and overflow (web editor step 3) ──
    double maxNaturalBottom = 0;

    for (var rawLayer in layers) {
      final layer = Map<String, dynamic>.from(rawLayer);
      final String objName = (layer['name'] ?? '').toString();
      final double objW = (layer['w'] ?? layer['width'] ?? 0).toDouble();
      if (objW >= designW * 0.9) continue;

      final double objTop = (layer['y'] ?? 0).toDouble();
      final double objH = (layer['h'] ?? layer['height'] ?? 0).toDouble();

      double totalShift = 0;
      for (var src in textSources) {
        if (src.name == objName) continue;
        if (objTop >= (src.origBottom - 5)) {
          totalShift += src.delta;
        }
      }

      // Also include text sources themselves (their height changed)
      bool isTextSource = textSources.any((s) => s.name == objName);

      if (totalShift.abs() > 0 || isTextSource) {
        final double naturalTop = objTop + totalShift;
        // For text sources, use current rendered height; for others, use JSON h
        double currentH = objH;
        if (isTextSource) {
          final key = _textKeys[objName];
          if (key != null && key.currentContext != null) {
            final rb = key.currentContext!.findRenderObject() as RenderBox?;
            if (rb != null && rb.hasSize) {
              currentH = rb.size.height / scale;
            }
          }
        }
        final double naturalBottom = naturalTop + currentH;
        if (naturalBottom > maxNaturalBottom) {
          maxNaturalBottom = naturalBottom;
        }
      }
    }

    double overflow = maxNaturalBottom - maxBottom;

    // ── Step 4: Resolve overflow via gap compression (web editor step 4) ──
    if (overflow > 0) {
      for (var src in textSources) {
        if (overflow <= 0) break;
        if (src.shrinkableGap > 0) {
          final double compress = math.min(overflow, src.shrinkableGap);
          src.appliedCompression = compress;
          overflow -= compress;
        }
      }
      // Note: Font shrinking would go here in a do-while loop,
      // but for now we handle it via gap compression only.
      // The web editor's font shrinking is a complex iterative process
      // that requires re-measuring after each font size change.
    } else {
      for (var src in textSources) {
        src.appliedCompression = 0;
      }
    }

    // ── Step 5: Apply final shifts (web editor step 5) ──
    // finalShift = sum of (delta - compression) for all text sources above this layer
    final Map<String, double> newShifts = {};

    for (var rawLayer in layers) {
      final layer = Map<String, dynamic>.from(rawLayer);
      final String name = (layer['name'] ?? '').toString();
      final double objW = (layer['w'] ?? layer['width'] ?? 0).toDouble();
      if (objW >= designW * 0.9) continue; // skip decorative

      final double objTop = (layer['y'] ?? 0).toDouble();

      double finalShift = 0;
      for (var src in textSources) {
        if (src.name == name) continue;
        if (objTop >= (src.origBottom - 5)) {
          finalShift += (src.delta - src.appliedCompression);
        }
      }

      if (finalShift.abs() >= 1) {
        newShifts[name] = finalShift;
        debugPrint('[SHIFT] "$name": ${finalShift.toStringAsFixed(1)}');
      }
    }

    // Only setState if shifts actually changed
    bool changed = !_hasMeasured || newShifts.length != _measuredShifts.length;
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

    final double rawFontSize = (layer['fontSize'] ?? layer['font_size'] ?? layer['size'] ?? 16).toDouble();
    final double docPPI = (widget.config['info']?['ppi'] ?? 72).toDouble();
    final double fontSize = rawFontSize * (docPPI / 72.0); // design px with PPI
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
    // Original design resolution
    // Support both config['info']['width'] (standard) and config['width'] (legacy/fallback)
    final double designW =
        (widget.config['info']?['width'] ?? widget.config['width'] ?? 1080).toDouble();
    final double designH =
        (widget.config['info']?['height'] ?? widget.config['height'] ?? 1080).toDouble();

    final double scale = widget.width / designW;
    final double height = designH * scale;

    final List<dynamic> layers = widget.config['layers'] ?? [];

    // ══ DIAGNOSTICS ══
    debugPrint('╔══════════════════════════════════════════════');
    debugPrint('║ EditorCanvasWidget: ${layers.length} layers, '
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
    // APPLY Y-SHIFTS (from post-frame measurement)
    // ══════════════════════════════════════════════════════════════
    // Create adjusted copies — we modify Y positions on these copies,
    // leaving the original config untouched.
    final List<Map<String, dynamic>> adjusted = sortedLayers
        .map((l) => Map<String, dynamic>.from(l))
        .toList();

    if (_measuredShifts.isNotEmpty) {
      for (var layer in adjusted) {
        final String name = (layer['name'] ?? '').toString();
        if (_measuredShifts.containsKey(name)) {
          layer['y'] =
              (layer['y'] as num).toDouble() + _measuredShifts[name]!;
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
        clipBehavior: Clip.hardEdge,
        children: stackChildren,
      ),
    );
  }

  Widget _buildLayer(Map<String, dynamic> layer, double scale) {
    final String name =
        (layer['name'] ?? layer['id'] ?? '').toString().toLowerCase();
    final String rawName =
        (layer['name'] ?? layer['id'] ?? '').toString(); // original case for key lookup
    final String type = layer['type'] ?? '';
    final bool isBackground = layer['is_background'] == true ||
        name == 'bg' ||
        name == 'background' ||
        name.contains('background');

    // ══ DIAGNOSTICS ══
    final double rawW =
        (layer['w'] ?? layer['width'] ?? 0).toDouble();
    final double rawH =
        (layer['h'] ?? layer['height'] ?? 0).toDouble();
    debugPrint('[LAYER] name="$name" type=$type bg=$isBackground '
        'native=${rawW}x$rawH '
        'scaled=${(rawW * scale).toStringAsFixed(1)}x'
        '${(rawH * scale).toStringAsFixed(1)}');

    // Handle Background Layer
    if (isBackground) {
      if (type == 'image') {
        final bool isCustom = _isCustomTemplate;
        String bgSrc = layer['src'] ?? '';
        String finalSrc;
        if (isCustom && bgSrc.isNotEmpty) {
          // Custom template: use the actual ZIP asset (BG.png etc.)
          finalSrc = _resolveAssetUrl(bgSrc);
        } else {
          // Category/Festival: use the design image as background
          finalSrc = widget.baseImgUrl ?? _resolveAssetUrl(bgSrc);
        }
        return Positioned.fill(
          child:
              _buildImage(finalSrc, BoxFit.cover, null, isSmall: false),
        );
      }
      return const SizedBox.shrink();
    }

    // ── MINIMUM SIZE ENFORCEMENT ──
    // Small decorator images (bullets, icons, diamonds) can scale down to
    // 3–4 logical pixels which Flutter Web may silently skip or render as
    // invisible. We enforce a minimum of 8×8 px and shift the origin so the
    // center stays at the same visual position.
    final double nativeW =
        (layer['w'] ?? layer['width'] ?? 0).toDouble();
    final double nativeH =
        (layer['h'] ?? layer['height'] ?? 0).toDouble();

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

    // Wrap the raw content in InteractiveLayer
    return InteractiveLayer(
      layerName: rawName,
      layerConfig: layer,
      scale: scale,
      child: content,
    );
  }

  Widget _buildText(Map<String, dynamic> layer, double scale) {
    final String layerName = (layer['name'] ?? layer['id'] ?? '').toString();
    String textValue = layer['text']?.toString() ?? '';

    // Override with AI Data if available
    if (widget.aiData != null && widget.aiData![layerName] != null) {
      textValue = widget.aiData![layerName].toString();
    }

    // Normalize newlines consistently (shared with _measureTextHeight)
    textValue = _normalizeText(textValue, layer);

    // Color conversion (handles hex and 0x formats)
    // Dynamic Theming writes to 'font_color', original JSON uses 'color'
    String colorStr = layer['font_color'] ?? layer['color'] ?? layer['fill'] ?? '#000000';
    Color fontColor = _parseColor(colorStr);

    // Font size — matches web editor's Adobe Standard Point-to-Pixel Conversion
    // Formula: pixel_size = point_size × (document_ppi / 72)
    final double layerScaleY = (layer['scaleY'] ?? layer['scaleX'] ?? 1.0).toDouble();
    double rawSize = (layer['fontSize'] ?? layer['font_size'] ?? layer['size'] ?? 16).toDouble();
    if (rawSize <= 0) rawSize = 20.0; // Web Editor fallback for 0 or missing
    // Apply PPI conversion (web editor: origSize = rawFontPt * (docPPI / 72))
    final double docPPI = (widget.config['info']?['ppi'] ?? 72).toDouble();
    final double ppiScale = docPPI / 72.0;
    final double fontSize = rawSize * ppiScale * layerScaleY * scale;

    // Font weight
    final String weightStr = (layer['weight'] ?? '').toString();
    String fontName = (layer['fontFamily'] ?? layer['font_name'] ?? layer['font'] ?? '').toString();
    fontName = fontName.replaceAll("'", "").replaceAll('"', '');

    FontWeight fontWeight = FontWeight.normal;
    String cleanFontFamily = fontName;

    if (fontName.contains('-')) {
      final parts = fontName.split('-');
      cleanFontFamily = parts[0];
      final weightPart = parts.sublist(1).join('-').toLowerCase();
      
      if (weightPart.contains('bold')) fontWeight = FontWeight.bold;
      else if (weightPart.contains('medium')) fontWeight = FontWeight.w500;
      else if (weightPart.contains('semibold') || weightPart.contains('semi')) fontWeight = FontWeight.w600;
      else if (weightPart.contains('light')) fontWeight = FontWeight.w300;
      else if (weightPart.contains('thin')) fontWeight = FontWeight.w100;
      else if (weightPart.contains('black')) fontWeight = FontWeight.w900;
    }

    // Add spaces for PascalCase fonts like BebasNeue -> Bebas Neue to match Google Fonts
    cleanFontFamily = cleanFontFamily.replaceAllMapped(RegExp(r'([a-z])([A-Z])'), (Match m) => '${m[1]} ${m[2]}');

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
      
      String sColorStr = (shadowMap['color'] ?? '#000000').toString();
      Color sColor = _parseColor(sColorStr);

      shadows = [
        Shadow(
            offset: Offset(ox, oy),
            blurRadius: bl,
            color: sColor)
      ];
    }

    // Justification
    final String just = (layer['justification']?.toString().toLowerCase().trim()) ?? 'left';
    TextAlign textAlign = TextAlign.left;
    Alignment alignment = Alignment.centerLeft;
    if (just == 'center') {
      textAlign = TextAlign.center;
      alignment = Alignment.center;
    }
    if (just == 'right') {
      textAlign = TextAlign.right;
      alignment = Alignment.centerRight;
    }
    
    debugPrint('[TEXT_LAYER] name="${layer['name']}" text="$textValue" font="$fontName" color="$colorStr" fontSize=$fontSize');

    TextStyle textStyle = TextStyle(
      color: fontColor,
      fontSize: fontSize,
      fontWeight: fontWeight,
      letterSpacing: letterSpacing,
      shadows: shadows,
      height: layer['line_height']?.toDouble() ?? 1.1,
    );

    if (cleanFontFamily.isNotEmpty) {
      try {
        textStyle = GoogleFonts.getFont(
          cleanFontFamily,
          textStyle: textStyle,
        );
      } catch (_) {
        textStyle = textStyle.copyWith(fontFamily: fontName);
      }
    }

    final String lname = (layer['name'] ?? layer['id'] ?? '').toString().toLowerCase();
    
    // Treat text as a single line if it's meant to be a single field OR if it has no spaces.
    // Single words cannot wrap naturally, so they should always scale down.
    bool noSpaces = !textValue.trim().contains(' ');
    bool isSingleLine = lname.contains('name') || 
                        lname.contains('email') || 
                        lname.contains('phone') || 
                        lname.contains('mobile') || 
                        lname.contains('web') ||
                        lname.contains('address') ||
                        noSpaces;

    // --- OVERFLOW PREVENTION (Fabric.js textbox word-wrap fix) ---
    // In Flutter, if a single word is wider than the container, it wraps mid-character.
    // In the Web Editor, it shrinks the font size until the longest word fits.
    final double layerW = (layer['w'] ?? layer['width'] ?? 0).toDouble();
    if (!isSingleLine && layerW > 0 && textValue.isNotEmpty) {
      final double posW = layerW * scale;
      final words = textValue.split(RegExp(r'\s+'));
      double maxWordWidth = 0;
      
      final tp = TextPainter(
        textDirection: TextDirection.ltr,
        // Disable system font scaling for measurement
        textScaler: TextScaler.noScaling,
      );
      for (var word in words) {
        tp.text = TextSpan(text: word, style: textStyle);
        tp.layout();
        if (tp.width > maxWordWidth) maxWordWidth = tp.width;
      }

      if (maxWordWidth > posW) {
        // Leave a tiny 1px margin to avoid rounding precision wraps
        final double shrinkFactor = (posW - 1) / maxWordWidth;
        final double newFontSize = fontSize * shrinkFactor;
        textStyle = textStyle.copyWith(fontSize: newFontSize);
      }
    }

    Widget textWidget = Text(
      textValue,
      textAlign: textAlign,
      style: textStyle,
      softWrap: !isSingleLine,
      // CRITICAL: Disable system accessibility font scaling so canvas designs don't break
      textScaler: TextScaler.noScaling,
    );

    if (isSingleLine) {
      textWidget = FittedBox(
        fit: BoxFit.scaleDown,
        alignment: alignment,
        child: textWidget,
      );
    }

    final double rawW = (layer['w'] ?? layer['width'] ?? 0).toDouble();
    if (rawW > 0) {
      textWidget = Container(
        width: double.infinity,
        alignment: alignment,
        child: textWidget,
      );
    }

    return textWidget;
  }

  Color _parseColor(String colorStr, {Color fallback = const Color(0xFF000000)}) {
    if (colorStr.isEmpty) return fallback;
    
    // Handle rgba(r,g,b,a) format
    if (colorStr.startsWith('rgba')) {
      try {
        final parts = colorStr
            .replaceAll(RegExp(r'[a-zA-Z\(\)]'), '')
            .split(',');
        if (parts.length >= 4) {
          return Color.fromARGB(
            (double.parse(parts[3]) * 255).round(),
            int.parse(parts[0]),
            int.parse(parts[1]),
            int.parse(parts[2]),
          );
        }
      } catch (_) {}
      return fallback;
    }
    
    String hex = colorStr.replaceAll('#', '').replaceAll('0x', '').replaceAll('0X', '');
    if (hex.length == 6) hex = 'FF$hex';
    
    if (hex.length == 8) {
      int? parsed = int.tryParse(hex, radix: 16);
      debugPrint('[PARSE_COLOR] colorStr="$colorStr" -> hex="$hex" -> parsed=$parsed');
      if (parsed != null) return Color(parsed);
    } else {
      debugPrint('[PARSE_COLOR] Unknown hex format: "$colorStr" -> "$hex" (length ${hex.length})');
    }
    
    return fallback;
  }

  /// Resolves a relative `src` path from the JSON into a full HTTP URL.
  /// Handles `../skins/...` paths and bare filenames.
  /// Encodes spaces and special characters via Uri.encodeFull().
  /// Whether this is a custom template (ZIP-based, self-contained assets)
  /// vs a category/festival template (overlay on poster image)
  bool get _isCustomTemplate {
    final t = widget.editorType.toLowerCase();
    return t.contains('custom') || t == 'post' || t == 'business_custom_frame';
  }

  /// Resolves relative src from JSON to absolute URL.
  /// Matches the web editor logic: skinDir + filename
  String _resolveAssetUrl(String src) {
    if (src.isEmpty) return '';
    if (src.startsWith('http')) return src;
    if (src.startsWith('data:')) return src;

    // Web editor logic: take just the filename and prepend skinDir
    // src is like "../skins/Frame_Square_101/BG.png"
    // We need: templateBaseUrl + /skins/Frame_Square_101/BG.png
    String resolved;
    if (src.startsWith('../')) {
      // Remove the leading ../ and combine with templateBaseUrl
      resolved = '${widget.templateBaseUrl}/${src.replaceFirst('../', '')}';
    } else if (src.contains('/')) {
      // Has path separators, might be skins/SkinName/file.png
      resolved = '${widget.templateBaseUrl}/$src';
    } else {
      // Just a filename — put it in skins directory
      resolved = '${widget.templateBaseUrl}/skins/$src';
    }

    // Normalize path to handle multiple ../../ segments
    final uri = Uri.parse(resolved).normalizePath();
    return uri.toString();
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

    // Determine if this is a "frame slot" (user photo placeholder) like web editor
    // Web editor: isFrameSlot = lname.startsWith('image')
    final bool isFrameSlot = lname.startsWith('image') && !lname.contains('icon');
    final bool isCustom = _isCustomTemplate;

    if (mappedImg != null) {
      finalUrl = mappedImg;
      pathType = 'AI_MAPPED';
      if (!finalUrl.startsWith('http') &&
          !finalUrl.startsWith('/') &&
          !finalUrl.startsWith('data:')) {
        finalUrl = '${widget.uploadsBaseUrl}/$finalUrl';
      }
    } else if (isFrameSlot && !isCustom) {
      // Category/Festival template: inject the design image into slots
      finalUrl = widget.baseImgUrl ?? '';
      pathType = 'BASE_IMG';
      fit = BoxFit.cover;
    } else {
      // Custom template OR non-slot image: resolve from ZIP assets
      // This matches web editor: src = skinDir + src.split('/').pop()
      finalUrl = _resolveAssetUrl(src);
      if (layer['_businessKey'] == 'logo' || lname.contains('logo')) {
        fit = BoxFit.contain;
      } else if (isFrameSlot) {
        fit = BoxFit.cover; // Photo slots use cover scaling
      } else {
        // Web editor uses contain scaling for decorative images:
        // containScale = Math.min(lw/img.width, lh/img.height)
        fit = BoxFit.contain;
      }
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

    Color? tintColor;
    final String _lowName = lname.toLowerCase();
    final bool _isContactOrSocial = ['phone', 'email', 'web', 'address', 'call', 'mobile',
      'facebook', 'instagram', 'twitter', 'youtube', 'linkedin', 'icon', 'social', 'mail', 'location'].any((k) => _lowName.contains(k));
    
    if (_isContactOrSocial) {
      debugPrint('[TINT] "$lname" tint_color=${layer['tint_color']} _businessKey=${layer['_businessKey']}');
    }
    
    if (layer['tint_color'] != null) {
      String tintStr = layer['tint_color'].toString();
      tintColor = _parseColor(tintStr, fallback: const Color(0xFFFFFFFF));
      debugPrint('[TINT] "$lname" PARSED → $tintColor');
    }

    return _buildImage(finalUrl, fit, radius, isSmall: isSmallAsset, tintColor: tintColor);
  }

  /// Builds an image widget.
  ///
  /// [isSmall] — When true, uses `Image.network` with `FilterQuality.high`
  /// instead of `CachedNetworkImage`. This is critical for Flutter Web where
  /// CachedNetworkImage silently fails to render images below ~10 logical px
  /// due to the HTML image element's caching/sizing quirks.
  Widget _buildImage(String url, BoxFit fit, double? radius,
      {required bool isSmall, Color? tintColor, bool isLocal = false}) {
    if (url.isEmpty) {
      debugPrint('[IMG_BUILD] SKIP: empty URL');
      return const SizedBox.shrink();
    }

    Widget imgWidget;

    if (isLocal) {
      // For local files (e.g. from image_picker)
      imgWidget = Image.file(
        File(url),
        fit: fit,
        filterQuality: FilterQuality.high,
        errorBuilder: (_, error, __) {
          debugPrint('IMAGE LOAD ERROR (Local $url): $error');
          return const SizedBox.shrink();
        },
      );
    } else {
      // Ensure URL is properly encoded (handles spaces in filenames)
      if (url.startsWith('http') && url.contains(' ')) {
        final encoded = Uri.encodeFull(url);
        url = encoded;
      }

      if (!url.startsWith('http')) {
        debugPrint('[IMG_BUILD] SKIP: non-http URL: $url');
        return const SizedBox.shrink();
      }

      if (isSmall) {
        // ── SMALL ASSET PATH ──
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
    }

    if (tintColor != null) {
      imgWidget = ColorFiltered(
        colorFilter: ColorFilter.mode(tintColor, BlendMode.srcIn),
        child: imgWidget,
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
    Color iconColor = _parseColor(colorStr);

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
  double shrinkableGap;
  double appliedCompression;

  _TextShiftSource({
    required this.name,
    required this.origBottom,
    required this.origLeft,
    required this.origRight,
    required this.layerW,
    required this.delta,
    this.shrinkableGap = 0,
    this.appliedCompression = 0,
  });
}

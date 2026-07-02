import '../utils/safe_double.dart';
import 'dart:convert';

import 'dart:io';
import 'dart:async';
import 'dart:ui' as ui;
import 'package:flutter/services.dart';

import 'dart:math' as math;
import 'package:flutter/material.dart';

import 'package:cached_network_image/cached_network_image.dart';

import 'interactive_layer.dart';

import 'package:font_awesome_flutter/font_awesome_flutter.dart';

import 'package:google_fonts/google_fonts.dart';

import 'package:get/get.dart';

import '../controllers/native_editor_controller.dart';


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
  bool _isReadyToRender = false;
  Timer? _renderTimer;

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
    _startRevealTimer();
  }

  void _startRevealTimer() {
    _isReadyToRender = false;
    _renderTimer?.cancel();
    _renderTimer = Timer(const Duration(milliseconds: 400), () {
      if (mounted) {
        setState(() {
          _isReadyToRender = true;
        });
      }
    });
  }

  @override
  void dispose() {
    _renderTimer?.cancel();
    super.dispose();
  }

  @override
  void didUpdateWidget(EditorCanvasWidget oldWidget) {
    super.didUpdateWidget(oldWidget);
    
    // Always schedule a measurement on update because widget.config is a mutable Map
    // and manual edits (like font size changes) won't trigger oldWidget.config != widget.config
    _schedulePostFrameMeasurement();
    
    // Only reset baselines if the template itself changed (different template loaded)
    final bool templateChanged = oldWidget.config['info']?['width'] != widget.config['info']?['width'] ||
        (oldWidget.config['layers'] as List?)?.length != (widget.config['layers'] as List?)?.length;
    
    if (templateChanged || oldWidget.width != widget.width) {
      _origHeights = {};
      _textKeys.clear();
      _hasMeasured = false;
      _measuredShifts = {};
      _fontSizeOverrides = {};
      _startRevealTimer();
    } else if (oldWidget.aiData != widget.aiData) {
      _hasMeasured = false;
      _measuredShifts = {};
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
        safeDouble(widget.config['info']?['width'] ?? widget.config['width'] ?? 1080);
    final double designH =
        safeDouble(widget.config['info']?['height'] ?? widget.config['height'] ?? 1080);
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

      final double origH = safeDouble(layer['h'] ?? layer['height'] ?? 0);
      if (origH <= 0) continue;

      final double layerW = safeDouble(layer['w'] ?? layer['width'] ?? 0);
      if (layerW >= designW * 0.9) continue; // skip full-canvas decorative

      final double layerY = safeDouble(layer['y'] ?? 0);
      final double layerX = safeDouble(layer['x'] ?? 0);

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
        final double objW = safeDouble(layer['w'] ?? layer['width'] ?? 0);
        if (objW >= designW * 0.9) continue;

        final double objTop = safeDouble(layer['y'] ?? 0);
        final double objLeft = safeDouble(layer['x'] ?? 0);
        final double objRight = objLeft + objW;

        final bool isBelow = objTop >= (src.origBottom - 5);
        final bool overlapsX = (objLeft < src.origRight + 20) && (objRight > src.origLeft - 20);

        if (isBelow && overlapsX) {
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
      final double objW = safeDouble(layer['w'] ?? layer['width'] ?? 0);
      if (objW >= designW * 0.9) continue;

      final double objTop = safeDouble(layer['y'] ?? 0);
      final double objLeft = safeDouble(layer['x'] ?? 0);
      final double objRight = objLeft + objW;
      final double objH = safeDouble(layer['h'] ?? layer['height'] ?? 0);

      double totalShift = 0;
      for (var src in textSources) {
        if (src.name == objName) continue;
        final bool isBelow = objTop >= (src.origBottom - 5);
        final bool overlapsX = (objLeft < src.origRight + 20) && (objRight > src.origLeft - 20);
        if (isBelow && overlapsX) {
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
        // Only compress gaps for elements that actually expanded
        if (src.delta > 0 && src.shrinkableGap > 0) {
          // Limit compression to the amount the text grew, to prevent pulling
          // elements up higher than their original designed positions.
          final double maxCompress = math.min(src.delta, src.shrinkableGap);
          final double compress = math.min(overflow, maxCompress);
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
      final double objW = safeDouble(layer['w'] ?? layer['width'] ?? 0);
      if (objW >= designW * 0.9) continue; // skip decorative

      final double objTop = safeDouble(layer['y'] ?? 0);
      final double objLeft = safeDouble(layer['x'] ?? 0);
      final double objRight = objLeft + objW;

      double finalShift = 0;
      for (var src in textSources) {
        if (src.name == name) continue;
        final bool isBelow = objTop >= (src.origBottom - 5);
        final bool overlapsX = (objLeft < src.origRight + 20) && (objRight > src.origLeft - 20);
        if (isBelow && overlapsX) {
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

    final double rawFontSize = safeDouble(layer['fontSize'] ?? layer['font_size'] ?? layer['size'] ?? 16);
    final double docPPI = safeDouble(widget.config['info']?['ppi'] ?? 72);
    final double fontSize = rawFontSize * (docPPI / 72.0); // design px with PPI
    final double lineHeight = safeDouble(layer['lineHeight'] ?? layer['line_height']) ?? 1.16;
    final double layerW =
        safeDouble(layer['w'] ?? layer['width'] ?? 0); // design px

    // Character spacing — read both camelCase and underscore forms
    double extraCharWidth = 0;
    final dynamic rawCS = layer['letterSpacing'] ?? layer['char_spacing'];
    if (rawCS != null) {
      final double charSpacing = safeDouble(rawCS);
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
        safeDouble(widget.config['info']?['width'] ?? widget.config['width'] ?? 1080);
    final double designH =
        safeDouble(widget.config['info']?['height'] ?? widget.config['height'] ?? 1080);

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
      final int zA = (a['z_index'] ?? 0) is int ? (a['z_index'] ?? 0) : ((a['z_index'] ?? 0) as num).toInt();
      final int zB = (b['z_index'] ?? 0) is int ? (b['z_index'] ?? 0) : ((b['z_index'] ?? 0) as num).toInt();
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
              safeDouble(layer['y'] as num) + _measuredShifts[name]!;
        }
      }
    }

    // ══════════════════════════════════════════════════════════════
    // PRE-PASS: AUTO-DETECT PSD CLIPPING MASKS
    // ══════════════════════════════════════════════════════════════
    // Must run BEFORE rendering so that:
    //   1. mask_layer_id is set on image layers that need masking
    //   2. mask shape layers are marked with _is_used_as_mask for hiding
    for (int idx = 0; idx < adjusted.length; idx++) {
      final layer = adjusted[idx];
      final String ltype = layer['type'] ?? '';
      final bool isShape = layer['is_shape'] == true || layer['is_shape'] == 1;
      
      if (ltype == 'image' && layer['mask_layer_id'] == null && !isShape) {
        final double myX = safeDouble(layer['x'] ?? 0);
        final double myY = safeDouble(layer['y'] ?? 0);
        final double myW = safeDouble(layer['w'] ?? layer['width'] ?? 0);
        final double myH = safeDouble(layer['h'] ?? layer['height'] ?? 0);
        
        debugPrint('[MASK_PREPASS] Checking "${layer['name']}" (idx=$idx) pos=($myX,$myY,$myW,$myH)');
        
        for (int i = idx - 1; i >= 0; i--) {
          final candidate = adjusted[i];
          final bool candIsShape = candidate['is_shape'] == true || candidate['is_shape'] == 1;
          if (!candIsShape) continue;
          
          final double cx = safeDouble(candidate['x'] ?? 0);
          final double cy = safeDouble(candidate['y'] ?? 0);
          final double cw = safeDouble(candidate['w'] ?? candidate['width'] ?? 0);
          final double ch = safeDouble(candidate['h'] ?? candidate['height'] ?? 0);
          
          if ((myX - cx).abs() < 2 && (myY - cy).abs() < 2 && 
              (myW - cw).abs() < 2 && (myH - ch).abs() < 2) {
            debugPrint('[MASK_PREPASS] ✅ MATCH! "${layer['name']}" clips to "${candidate['name']}"');
            layer['mask_layer_id'] = candidate['name'];
            // Mark the mask shape so _buildLayer hides it
            candidate['_is_used_as_mask'] = true;
            debugPrint('[MASK_PREPASS] 🔒 Marked "${candidate['name']}" as _is_used_as_mask=true');
            break;
          }
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

    return AnimatedOpacity(
      opacity: _isReadyToRender ? 1.0 : 0.0,
      duration: const Duration(milliseconds: 300),
      curve: Curves.easeIn,
      child: Container(
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
      ),
    );
  }

  Widget _buildLayer(Map<String, dynamic> layer, double scale) {
    final String name =
        (layer['name'] ?? layer['id'] ?? '').toString().toLowerCase();
    final String rawName =
        (layer['name'] ?? layer['id'] ?? '').toString(); // original case for key lookup
    final String type = layer['type'] ?? '';
    final bool isFrameLayer = layer['_is_frame_layer'] == true || layer['_isFrameLayer'] == true;
    final bool isBackground = !isFrameLayer && (layer['is_background'] == true ||
        (layer['is_background'] == null && (name == 'bg' ||
        name == 'background' ||
        name.contains('background'))));

    // ── FIX: Hide layer when is_background is explicitly false and name suggests background ──
    if (!isFrameLayer && layer['is_background'] == false &&
        (name.contains('background') || name == 'bg' || name == 'image1')) {
      debugPrint('[LAYER_HIDE] Hiding "$name" — is_background explicitly false');
      return const SizedBox.shrink();
    }

    // ── FIX: Hide mask shape layers that are used as clipPath by another layer ──
    // If any other layer references this layer's name/id as mask_layer_id,
    // this layer serves as a clip path and should NOT render independently.
    final allLayers = widget.config['layers'] as List;
    final bool isUsedAsMask = layer['_is_used_as_mask'] == true || allLayers.any((otherLayer) {
      final otherMaskId = otherLayer['mask_layer_id'];
      if (otherMaskId == null) return false;
      final maskIdStr = otherMaskId.toString();
      return maskIdStr == rawName || maskIdStr == (layer['id']?.toString() ?? '');
    });
    if (isUsedAsMask) {
      debugPrint('[LAYER_HIDE] Hiding "$name" — used as mask shape by another layer');
      return const SizedBox.shrink();
    }

    // ══ FULL LAYER DIAGNOSTICS ══
    final double rawW =
        safeDouble(layer['w'] ?? layer['width'] ?? 0);
    final double rawH =
        safeDouble(layer['h'] ?? layer['height'] ?? 0);
    debugPrint('╔══════════════════════════════════════════');
    debugPrint('║ [LAYER_DIAG] name="$name" type=$type');
    debugPrint('║   is_background_raw=${layer['is_background']} (${layer['is_background'].runtimeType})');
    debugPrint('║   is_background_resolved=$isBackground');
    debugPrint('║   is_placeholder=${layer['is_placeholder']}');
    debugPrint('║   mask_layer_id=${layer['mask_layer_id']}');
    debugPrint('║   src=${layer['src']}');
    debugPrint('║   native=${rawW}x$rawH scaled=${(rawW * scale).toStringAsFixed(1)}x${(rawH * scale).toStringAsFixed(1)}');
    debugPrint('║   opacity=${layer['opacity']} visible=${layer['visible']}');
    debugPrint('╚══════════════════════════════════════════');

    // Check visibility / opacity (Do this FIRST so even bg layers can be hidden)
    final double opacity = safeDouble(layer['opacity'] ?? 1.0);
    if (opacity <= 0.0) {
      return const SizedBox.shrink();
    }

    // ── FIX: Masked layers — override position/size to match mask shape bounds ──
    // NOTE: mask_layer_id is set by the PRE-PASS in the build() method above.
    Map<String, dynamic> effectiveLayer = layer;
    
    if (type == 'image' && layer['mask_layer_id'] != null) {
      final maskId = layer['mask_layer_id'].toString();
      final allLayers = widget.config['layers'] as List;
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
        effectiveLayer['scaleX'] = 1.0;
        effectiveLayer['scaleY'] = 1.0;
        effectiveLayer['angle'] = maskShapeLayer['angle'] ?? 0.0;
        debugPrint('[MASK_FIX] "$name" position overridden to mask shape "${maskShapeLayer['name']}": '
            'x=${effectiveLayer['x']} y=${effectiveLayer['y']} '
            'w=${effectiveLayer['w']} h=${effectiveLayer['h']} '
            'mask_src=${maskShapeLayer['src']}');
      } else {
        debugPrint('[MASK_FIX] ⚠️ "$name" mask_layer_id="$maskId" but NO matching layer found!');
      }
    }

    // ── MINIMUM SIZE ENFORCEMENT ──
    // Small decorator images (bullets, icons, diamonds) can scale down to
    // 3–4 logical pixels which Flutter Web may silently skip or render as
    // invisible. We enforce a minimum of 8×8 px and shift the origin so the
    // center stays at the same visual position.
    final double nativeW =
        safeDouble(effectiveLayer['w'] ?? effectiveLayer['width'] ?? 0);
    final double nativeH =
        safeDouble(effectiveLayer['h'] ?? effectiveLayer['height'] ?? 0);

    Widget content = const SizedBox.shrink();

    if (type == 'text') {
      if (effectiveLayer == layer) {
        effectiveLayer = Map<String, dynamic>.from(layer);
      }
      
      final double rawSize = safeDouble(effectiveLayer['fontSize'] ?? effectiveLayer['font_size'] ?? effectiveLayer['size'] ?? 16);
      final double docPPI = safeDouble(widget.config['info']?['ppi'] ?? 72);
      final double ppiScale = docPPI / 72.0;
      final double fontSize = rawSize * ppiScale;
      
      final double currentY = safeDouble(effectiveLayer['y'] ?? 0);
      final String textKind = (effectiveLayer['kind'] ?? '').toString().toLowerCase();
      
      // The Web Editor exports JSON with an artificial +0.12 * fontSize Y-offset for ALL text (Point & Paragraph).
      // Flutter's Text widget with height: 1.16 introduces internal leading (~0.08 * fontSize visual padding at the top).
      // InteractiveLayer ALREADY subtracts 0.12 for 'point' text (due to a locked legacy rule).
      // So for 'point', we only need to compensate the remaining 0.08 (Flutter padding).
      // For 'paragraph', we must subtract the full 0.20 (0.12 JSON + 0.08 padding).
      final double offsetMultiplier = (textKind == 'point') ? 0.08 : 0.20;
      final double yAdjustment = fontSize * offsetMultiplier;
      effectiveLayer['y'] = currentY - yAdjustment;

      // ══ COMPREHENSIVE TEXT DIAGNOSTICS ══
      debugPrint('┌─────────────────────────────────────────────────────────');
      debugPrint('│ [TEXT_DIAG] name="${effectiveLayer['name']}"');
      debugPrint('│   text="${effectiveLayer['text']}"');
      debugPrint('│   ── RAW JSON VALUES ──');
      debugPrint('│   json.x=${layer['x']}  json.y=${layer['y']}');
      debugPrint('│   json.w=${layer['w']}  json.h=${layer['h']}');
      debugPrint('│   json.size=${layer['size']}  json.font_size=${layer['font_size']}  json.fontSize=${layer['fontSize']}');
      debugPrint('│   json.kind=${layer['kind']}  json.weight=${layer['weight']}  json.style=${layer['style']}');
      debugPrint('│   json.font=${layer['font']}  json.font_name=${layer['font_name']}  json.fontFamily=${layer['fontFamily']}');
      debugPrint('│   json.color=${layer['color']}  json.fill=${layer['fill']}');
      debugPrint('│   json.justification=${layer['justification']}  json.textAlign=${layer['textAlign']}');
      debugPrint('│   json.lineHeight=${layer['lineHeight']}  json.line_height=${layer['line_height']}');
      debugPrint('│   json.letterSpacing=${layer['letterSpacing']}  json.char_spacing=${layer['char_spacing']}');
      debugPrint('│   json.scaleX=${layer['scaleX']}  json.scaleY=${layer['scaleY']}');
      debugPrint('│   ── COMPUTED VALUES ──');
      debugPrint('│   rawSize=$rawSize  docPPI=$docPPI  ppiScale=$ppiScale  fontSize(design)=$fontSize');
      debugPrint('│   textKind=$textKind  offsetMultiplier=$offsetMultiplier');
      debugPrint('│   yAdjustment=$yAdjustment');
      debugPrint('│   original_Y=$currentY  adjusted_Y=${effectiveLayer['y']}');
      debugPrint('│   ── INTERACTIVE_LAYER WILL COMPUTE ──');
      debugPrint('│   scale=$scale');
      debugPrint('│   finalX_scaled=${safeDouble(effectiveLayer['x'] ?? 0) * scale}');
      debugPrint('│   finalY_scaled_before_point_fix=${safeDouble(effectiveLayer['y'] ?? 0) * scale}');
      if (textKind == 'point') {
        final double layerScaleYForFont = safeDouble(effectiveLayer['scaleY'] ?? effectiveLayer['scaleX'] ?? 1.0);
        final double effectiveFontSizeScaled = rawSize * ppiScale * layerScaleYForFont * scale;
        final double pointFix = effectiveFontSizeScaled * 0.12;
        debugPrint('│   POINT_TEXT_FIX: effectiveFontSizeScaled=$effectiveFontSizeScaled  pointFix(0.12)=$pointFix');
        debugPrint('│   finalY_after_all_fixes=${safeDouble(effectiveLayer['y'] ?? 0) * scale - pointFix}');
      } else {
        debugPrint('│   (not point text, no InteractiveLayer point fix)');
        debugPrint('│   finalY_after_all_fixes=${safeDouble(effectiveLayer['y'] ?? 0) * scale}');
      }
      debugPrint('│   finalW_scaled=${safeDouble(effectiveLayer['w'] ?? effectiveLayer['width'] ?? 0) * scale}');
      debugPrint('│   finalH_scaled=(text uses intrinsic height)');
      debugPrint('└─────────────────────────────────────────────────────────');

      content = _buildText(effectiveLayer, scale);

      // Wrap text with GlobalKey for post-frame height measurement
      final key =
          _textKeys.putIfAbsent(rawName, () => GlobalKey());
      content = KeyedSubtree(key: key, child: content);
    } else if (type == 'image' || type == 'shape' || type == 'rect') {
      // ══ IMAGE/SHAPE DIAGNOSTICS ══
      debugPrint('┌─────────────────────────────────────────────────────────');
      debugPrint('│ [IMG_DIAG] name="${effectiveLayer['name']}"  type=$type');
      debugPrint('│   json.x=${effectiveLayer['x']}  json.y=${effectiveLayer['y']}');
      debugPrint('│   json.w=${effectiveLayer['w']}  json.h=${effectiveLayer['h']}');
      debugPrint('│   json.src=${effectiveLayer['src']}');
      debugPrint('│   json.is_background=${effectiveLayer['is_background']}  json.is_shape=${effectiveLayer['is_shape']}');
      debugPrint('│   json.is_placeholder=${effectiveLayer['is_placeholder']}  json.is_slot=${effectiveLayer['is_slot']}');
      debugPrint('│   scale=$scale');
      debugPrint('│   finalX=${safeDouble(effectiveLayer['x'] ?? 0) * scale}  finalY=${safeDouble(effectiveLayer['y'] ?? 0) * scale}');
      debugPrint('│   finalW=${nativeW * scale}  finalH=${nativeH * scale}');
      debugPrint('└─────────────────────────────────────────────────────────');
      content =
          _buildImageLayer(effectiveLayer, name, scale, nativeW, nativeH);
    } else if (type == 'icon') {
      content =
          _buildIconLayer(effectiveLayer, name, scale, nativeW, nativeH);
    }

    // Wrap the raw content in InteractiveLayer
    return InteractiveLayer(
      layerName: rawName,
      layerConfig: effectiveLayer,
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
    List<Color>? gradientColors = _parseGradient(colorStr);

    // Font size — matches web editor's Adobe Standard Point-to-Pixel Conversion
    // Formula: pixel_size = point_size × (document_ppi / 72)
    final double layerScaleY = safeDouble(layer['scaleY'] ?? layer['scaleX'] ?? 1.0);
    double rawSize = safeDouble(layer['fontSize'] ?? layer['font_size'] ?? layer['size'] ?? 16);
    if (rawSize <= 0) rawSize = 20.0; // Web Editor fallback for 0 or missing
    // Apply PPI conversion (web editor: origSize = rawFontPt * (docPPI / 72))
    final double docPPI = safeDouble(widget.config['info']?['ppi'] ?? 72);
    final double ppiScale = docPPI / 72.0;
    final double fontSize = rawSize * ppiScale * layerScaleY * scale;

    // Font weight and style
    final String weightStr = (layer['weight'] ?? '').toString();
    final String styleStr = (layer['style'] ?? '').toString();
    String fontName = (layer['fontFamily'] ?? layer['font_name'] ?? layer['font'] ?? '').toString();
    fontName = fontName.replaceAll("'", "").replaceAll('"', '');

    // CSS font-stack: take only the first font from comma-separated list
    // e.g. "Font Awesome 6 Brands, Font Awesome 5 Brands" → "Font Awesome 6 Brands"
    if (fontName.contains(',')) {
      fontName = fontName.split(',').first.trim();
    }

    // Map Web Editor FontAwesome names to Flutter's font_awesome_flutter package families
    bool isPackageFont = false;
    String? fontPackage;
    if (fontName.toLowerCase().contains('brands')) {
      fontName = 'FontAwesomeBrands';
      fontPackage = 'font_awesome_flutter';
      isPackageFont = true;
    } else if (fontName.toLowerCase().contains('font awesome') || fontName.toLowerCase().contains('fontawesome')) {
      fontName = 'FontAwesomeSolid';
      fontPackage = 'font_awesome_flutter';
      isPackageFont = true;
    }

    FontWeight fontWeight = FontWeight.normal;
    FontStyle fontStyle = (styleStr == 'italic' || fontName.toLowerCase().contains('italic')) 
        ? FontStyle.italic 
        : FontStyle.normal;
        
    String cleanFontFamily = fontName;

    if (!fontName.startsWith('packages/') && fontName.contains('-')) {
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
    if (!cleanFontFamily.startsWith('packages/')) {
      cleanFontFamily = cleanFontFamily.replaceAllMapped(RegExp(r'([a-z])([A-Z])'), (Match m) => '${m[1]} ${m[2]}');
    }

    if (weightStr == 'bold' || fontName.toLowerCase().contains('bold')) {
      fontWeight = FontWeight.bold;
    }
    if (weightStr == 'normal' && !fontName.toLowerCase().contains('bold')) {
      fontWeight = FontWeight.normal;
    }
    // RC-8: Handle numeric weight strings from web editor (e.g., "500", "600", "300")
    final int? numericWeight = int.tryParse(weightStr);
    if (numericWeight != null) {
      const Map<int, FontWeight> weightMap = {
        100: FontWeight.w100, 200: FontWeight.w200, 300: FontWeight.w300,
        400: FontWeight.w400, 500: FontWeight.w500, 600: FontWeight.w600,
        700: FontWeight.w700, 800: FontWeight.w800, 900: FontWeight.w900,
      };
      fontWeight = weightMap[numericWeight] ?? FontWeight.w400;
    }

    // Character spacing — web editor exports as 'letterSpacing' (camelCase, Fabric 1/1000 em),
    // PSD ZIP exports as 'char_spacing'. Read both. (RC-3)
    double? letterSpacing;
    final dynamic rawCharSpacing = layer['letterSpacing'] ?? layer['char_spacing'];
    if (rawCharSpacing != null) {
      final double charSpacing = safeDouble(rawCharSpacing);
      letterSpacing = (charSpacing / 1000) * fontSize;
    }

    // Shadow
    List<Shadow>? shadows;
    if (layer['shadow'] != null) {
      final Map<String, dynamic> shadowMap = layer['shadow'];
      final double ox =
          safeDouble(shadowMap['offsetX'] ?? 0) * scale;
      final double oy =
          safeDouble(shadowMap['offsetY'] ?? 0) * scale;
      final double bl =
          safeDouble(shadowMap['blur'] ?? 0) * scale;
      
      String sColorStr = (shadowMap['color'] ?? '#000000').toString();
      Color sColor = _parseColor(sColorStr);

      shadows = [
        Shadow(
            offset: Offset(ox, oy),
            blurRadius: bl,
            color: sColor)
      ];
    }

    // Justification — web legacy JSON doesn't export this at top level,
    // Artera Schema puts it in font.justification. Also check textAlign. (RC-7)
    final String just = (layer['justification'] ?? layer['textAlign'] ?? 'left').toString().toLowerCase().trim();
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
    
    debugPrint('[TEXT_LAYER] name="${layer['name']}" text="$textValue" font="$fontName" color="$colorStr" fontSize=$fontSize fontWeight=$fontWeight fontStyle=$fontStyle');

    double? strokeWidth;
    if (layer['stroke_width'] != null) {
      strokeWidth = safeDouble(layer['stroke_width']) * scale;
    } else if (layer['border_width'] != null) {
      strokeWidth = safeDouble(layer['border_width']) * scale;
    }

    Color? strokeColor;
    bool outlineOnly = layer['outline_only'] == true || layer['transparent_fill'] == true;

    if (strokeWidth != null && strokeWidth > 0) {
      String strokeStr = layer['stroke_color'] ?? layer['border_color'] ?? '#000000';
      strokeColor = _parseColor(strokeStr);
      
      // If the text is outline only, apply the gradient to the outline
      if (outlineOnly) {
        List<Color>? borderGradient = _parseGradient(strokeStr);
        if (borderGradient != null) {
          gradientColors = borderGradient;
        }
      }
    }

    TextStyle textStyle = TextStyle(
      color: outlineOnly ? null : fontColor,
      foreground: (outlineOnly && strokeWidth != null && strokeWidth > 0) 
          ? (Paint()
              ..style = PaintingStyle.stroke
              ..strokeWidth = strokeWidth
              ..color = strokeColor!) 
          : null,
      fontSize: fontSize,
      fontWeight: fontWeight,
      fontStyle: fontStyle,
      letterSpacing: letterSpacing,
      shadows: shadows,
      package: fontPackage,
      fontFamily: isPackageFont ? fontName : null,
      // RC-1: Web exports 'lineHeight' (camelCase), PSD ZIP uses 'line_height'. Default 1.16 matches Fabric.js.
      // Guard: corrupted lineHeight values (e.g. 0.0387 from legacy export dividing multiplier by fontSize)
      // are clamped to default 1.16. Valid Fabric multipliers range ~0.5–10.
      height: () {
        final double? raw = safeDouble(layer['lineHeight'] ?? layer['line_height']);
        if (raw == null || raw < 0.5 || raw > 10.0) return 1.16;
        return raw;
      }(),
    );

    if (!isPackageFont && cleanFontFamily.isNotEmpty) {
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
    
    // ── RC-2: Point vs Paragraph text detection (matches web editor) ──
    debugPrint('[TEXT_DIAG_CODEPOINTS] name="$lname" text="$textValue" codePoints="${textValue.runes.map((r) => r.toRadixString(16)).join(',')}"');
    
    // Web editor exports 'kind': 'Point' (fabric.Text, no wrap) or 'Paragraph' (fabric.Textbox, wraps within w).
    // Use kind from JSON first, fall back to heuristic for old templates.
    final String? textKind = layer['kind']?.toString().toLowerCase();
    
    bool noSpaces = !textValue.trim().contains(' ');
    
    // Check height to font-size ratio. If the bounding box can only hold ~1 line, it's a single line.
    final double layerH = safeDouble(layer['h'] ?? layer['height'] ?? 0);
    final double ratio = layerH > 0 && rawSize > 0 ? (layerH / rawSize) : 2.0;
    final bool hasExplicitNewlines = textValue.contains('\n') || textValue.contains('\r');
    
    bool isSingleLine;
    if (textKind == 'point') {
      // Explicitly marked as Point Text by web editor — never wraps, scales down
      isSingleLine = true;
    } else if (textKind == 'paragraph') {
      // Explicitly marked as Paragraph Text by web editor — wraps within layer width
      isSingleLine = false;
    } else {
      // Fallback heuristic for old templates without kind field
      isSingleLine = lname.contains('name') || 
                      lname.contains('email') || 
                      lname.contains('phone') || 
                      lname.contains('mobile') || 
                      lname.contains('web') ||
                      lname.contains('address') ||
                      noSpaces;
                      
      if (!hasExplicitNewlines && ratio <= 1.6) {
        isSingleLine = true;
      }
    }

    // --- OVERFLOW PREVENTION (Fabric.js textbox word-wrap fix) ---
    // In Flutter, if a single word is wider than the container, it wraps mid-character.
    // In the Web Editor, it shrinks the font size until the longest word fits.
    final double layerW = safeDouble(layer['w'] ?? layer['width'] ?? 0);
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
        // Removed font shrinking logic as per user request to never shrink multi-line text font sizes
        // and let the height auto-adjust instead.
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

    final double rawW = safeDouble(layer['w'] ?? layer['width'] ?? 0);
    if (rawW > 0) {
      textWidget = Container(
        width: double.infinity,
        alignment: alignment,
        child: textWidget,
      );
    }

    if (gradientColors != null && gradientColors.length >= 2) {
      String gradientDir = (layer['gradient_direction'] ?? 'vertical').toString().toLowerCase();
      Alignment begin = Alignment.topCenter;
      Alignment end = Alignment.bottomCenter;
      if (gradientDir == 'horizontal' || gradientDir == 'left_to_right' || gradientDir == 'left') {
        begin = Alignment.centerLeft;
        end = Alignment.centerRight;
      }

      textWidget = ShaderMask(
        blendMode: BlendMode.srcIn,
        shaderCallback: (bounds) {
          return LinearGradient(
            begin: begin,
            end: end,
            colors: gradientColors!,
          ).createShader(bounds);
        },
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

  List<Color>? _parseGradient(String colorStr) {
    if (!colorStr.startsWith('rgba') && colorStr.contains(',')) {
      final parts = colorStr.split(',').map((s) => s.trim()).where((s) => s.isNotEmpty).toList();
      if (parts.length >= 2) {
        return parts.map((c) => _parseColor(c)).toList();
      }
    }
    return null;
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

    bool isLocal = layer['isLocal'] == true || src.startsWith('/data/') || src.startsWith('file://') || src.startsWith('/storage/');

    if (isLocal) {
      finalUrl = src;
      pathType = 'LOCAL_FILE';
      fit = BoxFit.cover;
    } else if (mappedImg != null) {
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
      final bool isBg = (layer['is_background'] == true || layer['is_background'] == 1);
      final bool isPlaceholder = (layer['is_placeholder'] == true || layer['is_placeholder'] == 1);
      
      // If API already injected a full URL (http/https) into src, use it directly.
      // This happens when injectDynamicBackgroundImageArray replaces src with admin category image.
      final bool srcIsFullUrl = src.startsWith('http://') || src.startsWith('https://');
      
      if (srcIsFullUrl && (isBg || isPlaceholder)) {
        // API-injected admin background image — use the URL from src as-is
        finalUrl = src;
        pathType = 'API_INJECTED';
      } else if (isCustom && isBg && widget.baseImgUrl != null && widget.baseImgUrl!.isNotEmpty && !srcIsFullUrl) {
        // Custom template background without API injection — fallback to post preview
        finalUrl = widget.baseImgUrl!;
        pathType = 'BASE_IMG';
      } else {
        finalUrl = _resolveAssetUrl(src);
      }
      
      final bool isShape = layer['is_shape'] == true;
      final bool isFlatBg = isBg && !isShape;
      // Shaped backgrounds (circle/ellipse) that were dynamically replaced by API
      final bool isShapeBg = isBg && isShape;

      if (layer['_businessKey'] == 'logo' || lname.contains('logo')) {
        fit = BoxFit.contain;
      } else if (isFrameSlot) {
        fit = BoxFit.cover; // Photo slots use cover scaling
      } else if (isFlatBg || isShapeBg || isPlaceholder) {
        fit = BoxFit.cover; // All backgrounds (flat or shaped) and placeholders should fill the area
      } else if (lname == '_frame_bg' || lname == '_frame' || lname == 'frame' || layer['_is_frame_layer'] == true) {
        fit = BoxFit.fill; // Stretch frame components to exactly match computed scales and overlap 100%
      } else {
        // RC-4 + RC-5: Web editor uses scaleX: w/img.width, scaleY: h/img.height
        // which stretches each axis to exact bounding box = BoxFit.fill.
        // Previously BoxFit.contain left gaps in shapes and decorative images.
        fit = BoxFit.fill;
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
      radius = safeDouble(layer['radius'] ?? 40) * scale;
    }

    // Determine if this is a "small" decorator image that needs special handling
    final bool isSmallAsset = (nativeW > 0 && nativeW < 50) ||
        (nativeH > 0 && nativeH < 50);

    Color? tintColor;
    List<Color>? gradientColors;
    final String _lowName = lname.toLowerCase();
    final bool _isContactOrSocial = ['phone', 'email', 'web', 'address', 'call', 'mobile',
      'facebook', 'instagram', 'twitter', 'youtube', 'linkedin', 'icon', 'social', 'mail', 'location'].any((k) => _lowName.contains(k));
    
    if (_isContactOrSocial) {
      debugPrint('[TINT] "$lname" tint_color=${layer['tint_color']} _businessKey=${layer['_businessKey']}');
    }
    
    if (layer['tint_color'] != null) {
      String tintStr = layer['tint_color'].toString();
      tintColor = _parseColor(tintStr, fallback: const Color(0xFFFFFFFF));
      gradientColors = _parseGradient(tintStr);
      debugPrint('[TINT] "$lname" PARSED → $tintColor');
    }

    String gradientDir = (layer['gradient_direction'] ?? 'vertical').toString();
    bool flipX = layer['flipX'] == true || layer['flipX'] == 'true';
    
    // Check if layer implies an oval/circular shape for masking Admin backgrounds.
    // IMPORTANT: Only apply clipOval when an admin/user image has been injected into
    // an ellipse-shaped layer (API_INJECTED, BASE_IMG, AI_MAPPED). Template asset PNGs
    // (TEMPLATE_ASSET) already have their shape baked into the alpha channel —
    // clipping them with ClipOval would distort/cut them in half.
    final bool isShapeLayer = layer['is_shape'] == true || layer['is_shape'] == 1;
    final bool hasInjectedImage = (pathType == 'API_INJECTED' || pathType == 'BASE_IMG' || pathType == 'AI_MAPPED');
    bool clipOval = !isShapeLayer && hasInjectedImage && (_lowName.contains('ellipse') || _lowName.contains('circle') || _lowName.contains('round'));
    
    Widget imgWidget = _buildImage(finalUrl, fit, radius, isSmall: isSmallAsset, tintColor: tintColor, gradientColors: gradientColors, gradientDir: gradientDir, isLocal: isLocal, flipX: flipX, clipOval: clipOval);
    
    // -- Custom Image Mask Logic --
    // When a layer has mask_layer_id (either from JSON or auto-detected in pre-pass):
    //   1. _buildLayer overrides position/size to match the mask shape's bounds
    //   2. Mask shape layer is hidden (returns SizedBox.shrink)
    // Here we rebuild the image with BoxFit.cover and wrap it with CustomImageMaskWidget
    // which uses the shape PNG's alpha channel for pixel-perfect clipping.
    if (layer['mask_layer_id'] != null) {
      final maskId = layer['mask_layer_id'].toString();
      final allLayers = widget.config['layers'] as List;
      final maskShapeLayer = allLayers.firstWhere(
          (l) => l['name']?.toString() == maskId || l['id']?.toString() == maskId,
          orElse: () => <String, dynamic>{});
      
      debugPrint('[MASK_DIAG] ═══════════════════════════════════════');
      debugPrint('[MASK_DIAG] Layer "$lname" has mask_layer_id="$maskId"');
      debugPrint('[MASK_DIAG] maskShapeLayer found=${maskShapeLayer.isNotEmpty}');
      
      imgWidget = _buildImage(finalUrl, BoxFit.cover, null, 
        isSmall: false, tintColor: tintColor, 
        gradientColors: gradientColors, gradientDir: gradientDir, 
        isLocal: isLocal, flipX: flipX, clipOval: false);
      
      if (maskShapeLayer.isNotEmpty && maskShapeLayer['src'] != null) {
        final String maskSrc = maskShapeLayer['src'] ?? '';
        final String maskUrl = _resolveAssetUrl(maskSrc);
        
        debugPrint('[MASK_DIAG] maskSrc="$maskSrc" → maskUrl="$maskUrl"');
        
        imgWidget = CustomImageMaskWidget(
          imageUrl: maskUrl,
          child: imgWidget,
        );
        debugPrint('[MASK_DIAG] ✅ CustomImageMaskWidget applied for "$lname"');
      } else {
        debugPrint('[MASK_DIAG] ⚠️ Mask shape has no src — cannot apply alpha mask');
      }
      debugPrint('[MASK_DIAG] ═══════════════════════════════════════');
    }
    
    return imgWidget;
  }

  /// Builds an image widget.
  ///
  /// [isSmall] — When true, uses `Image.network` with `FilterQuality.high`
  /// instead of `CachedNetworkImage`. This is critical for Flutter Web where
  /// CachedNetworkImage silently fails to render images below ~10 logical px
  /// due to the HTML image element's caching/sizing quirks.
  Widget _buildImage(String url, BoxFit fit, double? radius,
      {required bool isSmall, Color? tintColor, List<Color>? gradientColors, String gradientDir = 'vertical', bool isLocal = false, bool flipX = false, bool clipOval = false}) {
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

      if (url.startsWith('data:image')) {
        final base64String = url.split(',').last;
        try {
          final bytes = base64Decode(base64String);
          imgWidget = Image.memory(
            bytes,
            fit: fit,
            filterQuality: FilterQuality.high,
            errorBuilder: (_, error, __) {
              debugPrint('IMAGE LOAD ERROR (Base64): $error');
              return const SizedBox.shrink();
            },
          );
        } catch (e) {
          debugPrint('IMAGE LOAD ERROR (Base64 decode failed): $e');
          return const SizedBox.shrink();
        }
      } else {
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
            errorBuilder: (context, error, stackTrace) {
              debugPrint('IMAGE LOAD ERROR (Small): $error');
              if (url.contains('%20')) {
                final fallbackUrl = url.replaceAll('%20', '-');
                debugPrint('Retrying small image with fallback URL: $fallbackUrl');
                return Image.network(
                  fallbackUrl,
                  fit: fit,
                  filterQuality: FilterQuality.high,
                  errorBuilder: (_, __, ___) => const SizedBox.shrink(),
                );
              }
              return const SizedBox.shrink();
            },
          );
        } else {
          // ── LARGE ASSET PATH ──
          imgWidget = CachedNetworkImage(
            imageUrl: url,
            fit: fit,
            filterQuality: FilterQuality.high,
            fadeInDuration: Duration.zero,
            fadeOutDuration: Duration.zero,
            placeholderFadeInDuration: Duration.zero,
            errorListener: (err) {
              debugPrint('IMAGE LOAD ERROR: $url');
            },
            errorWidget: (context, errorUrl, error) {
              if (url.contains('%20')) {
                final fallbackUrl = url.replaceAll('%20', '-');
                debugPrint('Retrying image with fallback URL: $fallbackUrl');
                return CachedNetworkImage(
                  imageUrl: fallbackUrl,
                  fit: fit,
                  filterQuality: FilterQuality.high,
                  fadeInDuration: Duration.zero,
                  fadeOutDuration: Duration.zero,
                  placeholderFadeInDuration: Duration.zero,
                  errorWidget: (_, __, ___) => const SizedBox.shrink(),
                );
              }
              return const SizedBox.shrink();
            },
          );
        }
      }
    }

    if (gradientColors != null && gradientColors.length >= 2) {
      String dir = gradientDir.toLowerCase();
      Alignment begin = Alignment.topCenter;
      Alignment end = Alignment.bottomCenter;
      if (dir == 'horizontal' || dir == 'left_to_right' || dir == 'left') {
        begin = Alignment.centerLeft;
        end = Alignment.centerRight;
      }

      imgWidget = ShaderMask(
        blendMode: BlendMode.srcIn,
        shaderCallback: (bounds) {
          return LinearGradient(
            begin: begin,
            end: end,
            colors: gradientColors,
          ).createShader(bounds);
        },
        child: imgWidget,
      );
    } else if (tintColor != null) {
      imgWidget = ColorFiltered(
        colorFilter: ColorFilter.mode(tintColor, BlendMode.srcIn),
        child: imgWidget,
      );
    }

    if (radius != null && radius > 0) {
      imgWidget = ClipRRect(
        borderRadius: BorderRadius.circular(radius),
        child: imgWidget,
      );
    }
    
    if (clipOval) {
      imgWidget = ClipOval(
        child: imgWidget,
      );
    }
    
    if (flipX) {
      imgWidget = Transform(
        alignment: Alignment.center,
        transform: Matrix4.rotationY(3.1415926535897932), // pi
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
    List<Color>? gradientColors = _parseGradient(colorStr);

    // Default icon size is based on height
    double size = (safeDouble(layer['size']) ??
            (nativeH > 0 ? nativeH : 24.0)) *
        scale;

    Widget iconWidget = FaIcon(
      iconData,
      color: iconColor,
      size: size,
    );

    if (gradientColors != null && gradientColors.length >= 2) {
      String gradientDir = (layer['gradient_direction'] ?? 'vertical').toString().toLowerCase();
      Alignment begin = Alignment.topCenter;
      Alignment end = Alignment.bottomCenter;
      if (gradientDir == 'horizontal' || gradientDir == 'left_to_right' || gradientDir == 'left') {
        begin = Alignment.centerLeft;
        end = Alignment.centerRight;
      }

      iconWidget = ShaderMask(
        blendMode: BlendMode.srcIn,
        shaderCallback: (bounds) {
          return LinearGradient(
            begin: begin,
            end: end,
            colors: gradientColors,
          ).createShader(bounds);
        },
        child: iconWidget,
      );
    }

    if (layer['opacity'] != null) {
      iconWidget = Opacity(opacity: safeDouble(layer['opacity']), child: iconWidget);
    }

    return Center(child: iconWidget);
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

class CustomImageMaskWidget extends StatefulWidget {
  final String imageUrl;
  final Widget child;
  // Shape position on the full canvas (mask PNG is full-canvas size)
  final double shapeX;
  final double shapeY;
  final double shapeW;
  final double shapeH;
  final double canvasW;
  final double canvasH;

  const CustomImageMaskWidget({
    Key? key,
    required this.imageUrl,
    required this.child,
    this.shapeX = 0,
    this.shapeY = 0,
    this.shapeW = 0,
    this.shapeH = 0,
    this.canvasW = 2000,
    this.canvasH = 2000,
  }) : super(key: key);

  @override
  _CustomImageMaskWidgetState createState() => _CustomImageMaskWidgetState();
}

class _CustomImageMaskWidgetState extends State<CustomImageMaskWidget> {
  ui.Image? _maskImage;
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    debugPrint('[MASK_WIDGET] initState — loading mask from: ${widget.imageUrl}');
    _loadMaskImage();
  }
  
  @override
  void didUpdateWidget(CustomImageMaskWidget oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (oldWidget.imageUrl != widget.imageUrl) {
      _loadMaskImage();
    }
  }

  Future<void> _loadMaskImage() async {
    try {
      setState(() { _isLoading = true; });
      debugPrint('[MASK_WIDGET] Loading mask image: ${widget.imageUrl}');
      Uint8List bytes;
      if (widget.imageUrl.startsWith('data:image')) {
        bytes = base64Decode(widget.imageUrl.split(',').last);
      } else if (widget.imageUrl.startsWith('http')) {
        final data = await NetworkAssetBundle(Uri.parse(widget.imageUrl)).load('');
        bytes = data.buffer.asUint8List();
      } else {
        // Local file path fallback
        final file = File(widget.imageUrl);
        bytes = await file.readAsBytes();
      }
      
      debugPrint('[MASK_WIDGET] Downloaded ${bytes.length} bytes, decoding...');
      final completer = Completer<ui.Image>();
      ui.decodeImageFromList(bytes, (img) => completer.complete(img));
      final img = await completer.future;
      debugPrint('[MASK_WIDGET] ✅ Mask decoded: ${img.width}x${img.height}');
      
      if (mounted) {
        setState(() {
          _maskImage = img;
          _isLoading = false;
        });
      }
    } catch (e) {
      debugPrint('[MASK_WIDGET] ❌ Error loading mask image: $e');
      if (mounted) {
        setState(() { _isLoading = false; });
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_isLoading) {
      return widget.child;
    }
    if (_maskImage == null) {
      debugPrint('[MASK_WIDGET] ⚠️ Mask image null — showing unmasked');
      return widget.child;
    }

    final int maskW = _maskImage!.width;
    final int maskH = _maskImage!.height;

    return ShaderMask(
      blendMode: BlendMode.dstIn,
      shaderCallback: (Rect bounds) {
        // The mask PNG is the shape rendered at its exact bounds (e.g. 824x1804).
        // NOT a full-canvas mask. So we simply scale the mask image
        // to fill the widget bounds — the shape's alpha channel does the clipping.
        // ImageShader matrix scales the IMAGE. So to fit a maskW image into bounds.width,
        // we scale by (bounds.width / maskW).

        final double scaleX = maskW > 0 ? bounds.width / maskW : 1.0;
        final double scaleY = maskH > 0 ? bounds.height / maskH : 1.0;

        debugPrint('[MASK_WIDGET] shader: maskSize=${maskW}x$maskH bounds=$bounds scaleX=$scaleX scaleY=$scaleY');

        final Matrix4 matrix = Matrix4.identity()
          ..scale(scaleX, scaleY, 1.0);

        return ImageShader(
          _maskImage!,
          TileMode.clamp,
          TileMode.clamp,
          matrix.storage,
        );
      },
      child: widget.child,
    );
  }
}

  }

  Widget _buildText(Map<String, dynamic> layer, double scale) {
    int renderVersion = (widget.config['render_version'] is int)
        ? widget.config['render_version']
        : int.tryParse(widget.config['render_version']?.toString() ?? '1') ?? 1;

    if (renderVersion >= 10) {
      return _buildTextV10(layer, scale, renderVersion);
    }

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
    
    bool isBrand = fontName.toLowerCase().contains('brands');
    if (!isBrand && (fontName.toLowerCase().contains('font awesome') || fontName.toLowerCase().contains('fontawesome'))) {
        // Fallback for PSDs that exported as "Font Awesome 5 Free" but contain brand icons
        final List<int> brandRunes = [
          0xf09a, 0xf39e, 0xf082, 0xf16d, 0xfe66, 0xf167, 0xf431, 
          0xf099, 0xf081, 0xe61b, 0xf232, 0xf08c, 0xf0e1, 0xe07b, 
          0xf2c6, 0xf2ab, 0xf0d2, 0xf231
        ];
        for (var rune in textValue.runes) {
          if (brandRunes.contains(rune)) {
            isBrand = true;
            break;
          }
        }
    }

    if (isBrand) {
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
    final dynamic fontObj = layer['font'];
    final String just = (layer['justification'] ?? 
                        (fontObj is Map ? fontObj['justification'] : null) ?? 
                        layer['textAlign'] ?? 'left').toString().toLowerCase().trim();
    TextAlign textAlign = TextAlign.left;
    Alignment alignment = Alignment.centerLeft;
    if (just == 'center') {
      textAlign = TextAlign.center;
      alignment = Alignment.center;
    } else if (just == 'right') {
      textAlign = TextAlign.right;
      alignment = Alignment.centerRight;
    } else if (just == 'justify' || just == 'full') {
      textAlign = TextAlign.justify;
      alignment = Alignment.centerLeft;
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
    
    final String aiRole = (layer['ai_role'] ?? layer['ai_field'] ?? layer['_businessKey'] ?? '').toString().toLowerCase();
    final bool isFrameLayer = layer['_is_frame_layer'] == true || layer['_isFrameLayer'] == true;
    final bool isKnownSingleLineField = lname.contains('name') || 
                                        lname.contains('email') || 
                                        lname.contains('phone') || 
                                        lname.contains('mobile') || 
                                        lname.contains('web') ||
                                        lname.contains('address') ||
                                        aiRole.contains('name') ||
                                        aiRole.contains('email') ||
                                        aiRole.contains('phone') ||
                                        aiRole.contains('mobile') ||
                                        aiRole.contains('web') ||
                                        aiRole.contains('address') ||
                                        (isFrameLayer && !hasExplicitNewlines) ||
                                        noSpaces;

    bool fitsOnSingleLine = false;
    final double tpW = safeDouble(layer['w'] ?? layer['width'] ?? 0);
    if (tpW > 0 && textValue.isNotEmpty) {
      final tp = TextPainter(
        textDirection: TextDirection.ltr,
        textScaler: TextScaler.noScaling,
        maxLines: 1,
      );
      tp.text = TextSpan(text: textValue, style: textStyle);
      tp.layout();
      if (tp.width <= tpW * 1.1) {
        fitsOnSingleLine = true;
      }
    }

    bool isSingleLine;
    if (textKind == 'point') {
      // Explicitly marked as Point Text by web editor — never wraps, scales down
      isSingleLine = true;
    } else if (isKnownSingleLineField || (!hasExplicitNewlines && (ratio <= 2.2 || fitsOnSingleLine))) {
      // Even if marked as 'paragraph' (Textbox for alignment), fields like email/phone/web/name,
      // frame layers, or boxes without explicit newlines must NEVER wrap to multiple lines; they must scale down via FittedBox!
      isSingleLine = true;
    } else if (textKind == 'paragraph') {
      // Explicitly marked as Paragraph Text by web editor — wraps within layer width
      isSingleLine = false;
    } else {
      isSingleLine = false;
    }

    // Inject single-line flag so InteractiveLayer knows not to constrain width
    layer['_is_single_line'] = isSingleLine;

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
    if (rawW > 0 && !isSingleLine) {
      textWidget = Container(
        width: double.infinity,
        alignment: alignment,
        child: textWidget,
      );
    } else if (isSingleLine && rawW > 0) {
      // Point text (single-line) needs a fixed-width container so alignment (right/center/left)
      // takes effect, and FittedBox(scaleDown) ensures long text (like email) shrinks without overlapping.
      textWidget = SizedBox(
        width: rawW * scale,
        child: FittedBox(
          fit: BoxFit.scaleDown,
          alignment: alignment,
          child: textWidget,
        ),
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

  Color _parseColor(dynamic colorVal, {Color fallback = const Color(0xFF000000)}) {
    if (colorVal == null) return fallback;
    if (colorVal is int) return Color(colorVal);
    
    String colorStr = colorVal.toString().trim();
    if (colorStr.isEmpty) return fallback;
    
    // Handle stringified integers (e.g. "4278190080" from 0xFF000000)
    int? intParsed = int.tryParse(colorStr);
    if (intParsed != null && !colorStr.startsWith('#') && !colorStr.startsWith('0x') && colorStr.length > 7) {
      return Color(intParsed);
    }
    
    // Handle rgb(r,g,b) format
    if (colorStr.startsWith('rgb') && !colorStr.startsWith('rgba')) {
      try {
        final parts = colorStr
            .replaceAll(RegExp(r'[a-zA-Z\(\)]'), '')
            .split(',');
        if (parts.length >= 3) {
          return Color.fromARGB(
            255,
            int.parse(parts[0].trim()),
            int.parse(parts[1].trim()),
            int.parse(parts[2].trim()),
          );
        }
      } catch (_) {}
      return fallback;
    }
    
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
    if (src.startsWith('data:')) return src;

    String baseUrl = widget.templateBaseUrl;
    if ((baseUrl.isEmpty || !baseUrl.contains('/uploads/template/')) && Get.isRegistered<NativeEditorController>()) {
      final ctrl = Get.find<NativeEditorController>();
      if (ctrl.templateBaseUrl.contains('/uploads/template/')) {
        baseUrl = ctrl.templateBaseUrl;
      }
    }
    if (!baseUrl.endsWith('/')) {
      baseUrl += '/';
    }

    String zipName = (widget.config['zip_name'] ?? widget.config['path'] ?? '').toString().replaceAll('/', '').trim();
    if (zipName.isEmpty) {
      final reg = RegExp(r'(?:\.\./)?skins/([^/]+)/');
      final match = reg.firstMatch(src);
      if (match != null && match.group(1) != null) {
        zipName = match.group(1)!.trim();
      } else if (widget.config['layers'] is List) {
        for (var l in (widget.config['layers'] as List)) {
          if (l is Map) {
            for (var f in ['src', '_fallback_src']) {
              if (l[f] != null) {
                final m = reg.firstMatch(l[f].toString());
                if (m != null && m.group(1) != null) {
                  zipName = m.group(1)!.trim();
                  break;
                }
              }
            }
          }
          if (zipName.isNotEmpty) break;
        }
      }
    }

    if (zipName.isNotEmpty && !baseUrl.contains('/uploads/template/$zipName')) {
      final rootUrl = ApiService.baseUrl.replaceAll('/123456', '');
      baseUrl = '$rootUrl/uploads/template/$zipName/';
    }

    if (src.startsWith('http')) {
      if (src.contains('/skins/skins/')) {
        src = src.replaceAll('/skins/skins/', '/skins/');
      }
      if (zipName.isNotEmpty && src.contains('/uploads/skins/$zipName/')) {
        src = src.replaceAll('/uploads/skins/$zipName/', '/uploads/template/$zipName/skins/$zipName/');
      }
      return src;
    }

    String resolved;
    if (src.startsWith('../')) {
      resolved = '$baseUrl${src.replaceFirst('../', '')}';
    } else if (src.startsWith('skins/')) {
      resolved = '$baseUrl$src';
    } else if (src.startsWith('uploads/')) {
      final rootUrl = ApiService.baseUrl.replaceAll('/123456', '');
      resolved = '$rootUrl/$src';
    } else if (src.startsWith('/')) {
      final rootUrl = ApiService.baseUrl.replaceAll('/123456', '');
      resolved = '$rootUrl${src.substring(1)}';
    } else if (src.contains('/')) {
      resolved = '$baseUrl$src';
    } else {
      if (zipName.isNotEmpty) {
        resolved = '${baseUrl}skins/$zipName/$src';
      } else {
        resolved = '${baseUrl}skins/$src';
      }
    }

    final uri = Uri.parse(resolved).normalizePath();

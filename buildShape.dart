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

  /// ── RENDER V4: Renders shapes natively without PNG ──
  /// Supports: rect, ellipse/circle, triangle, line, icon
  /// Fallback: For complex shapes (polygon, path), uses _fallback_src PNG
  Widget _buildVectorShape(Map<String, dynamic> layer, String lname,
      double scale, double nativeW, double nativeH, int renderVersion) {
    
    final String shapeType = (layer['shapeType'] ?? 'rect').toString().toLowerCase();
    debugPrint('🔴 [DIAGNOSIS-SHAPE] name="$lname" shapeType="$shapeType" fallback=${layer['_fallback_src'] != null}');
    
    // If it's an icon shape, delegate to _buildIconLayer
    final bool hasMetaIcon = layer['_source_meta'] is Map && layer['_source_meta']['iconName'] != null;
    if (shapeType == 'icon' || layer['iconName'] != null || hasMetaIcon) {
      return _buildIconLayer(layer, lname, scale, nativeW, nativeH, renderVersion);
    }

    final double w = nativeW * scale;
    final double h = nativeH * scale;
    final double opacity = safeDouble(layer['opacity'] ?? 1.0);
    final double rotation = safeDouble(layer['rotation'] ?? 0);
    
    // ── Parse Fill Color ──
    Color fillColor = Colors.transparent;
    List<Color>? gradientColors;
    Gradient? gradient;
    
    final dynamic fillVal = layer['fill'];
    if (fillVal is String && fillVal.isNotEmpty && fillVal != 'none') {
      fillColor = _parseColor(fillVal, fallback: Colors.transparent);
      gradientColors = _parseGradient(fillVal);
    } else if (fillVal is Map) {
      // Fabric.js gradient object
      gradient = _fabricGradientToFlutter(fillVal, w, h);
    }
    
    // ── Parse Stroke ──
    Color strokeColor = Colors.transparent;
    double strokeWidth = 0;
    if (layer['stroke'] != null && layer['stroke'].toString() != 'null' && layer['stroke'].toString() != 'none') {
      strokeColor = _parseColor(layer['stroke'].toString());
      strokeWidth = safeDouble(layer['strokeWidth'] ?? 0) * scale;
    }
    
    // ── Parse Border Radius ──
    double rx = safeDouble(layer['rx'] ?? 0) * scale;
    double ry = safeDouble(layer['ry'] ?? 0) * scale;
    
    Widget shapeWidget;
    
    // ═══════════════════════════════════════════════════════
    // RECT / ROUNDED RECT
    // ═══════════════════════════════════════════════════════
    if (shapeType == 'rect' || shapeType == 'rectangle') {
      shapeWidget = Container(
        width: w,
        height: h,
        decoration: BoxDecoration(
          color: gradient == null ? fillColor : null,
          gradient: gradient,
          borderRadius: (rx > 0 || ry > 0)
              ? BorderRadius.all(Radius.elliptical(rx, ry))
              : null,
          border: strokeWidth > 0
              ? Border.all(color: strokeColor, width: strokeWidth)
              : null,
        ),
      );
    }
    // ═══════════════════════════════════════════════════════
    // ELLIPSE / CIRCLE
    // ═══════════════════════════════════════════════════════
    else if (shapeType == 'ellipse' || shapeType == 'circle') {
      shapeWidget = Container(
        width: w,
        height: h,
        decoration: BoxDecoration(
          color: gradient == null ? fillColor : null,
          gradient: gradient,
          shape: (w == h) ? BoxShape.circle : BoxShape.rectangle,
          borderRadius: (w != h)
              ? BorderRadius.all(Radius.elliptical(w / 2, h / 2))
              : null,
          border: strokeWidth > 0
              ? Border.all(color: strokeColor, width: strokeWidth)
              : null,
        ),
      );
    }
    // ═══════════════════════════════════════════════════════
    // TRIANGLE
    // ═══════════════════════════════════════════════════════
    else if (shapeType == 'triangle') {
      shapeWidget = SizedBox(
        width: w,
        height: h,
        child: CustomPaint(
          painter: _TrianglePainter(
            fillColor: fillColor,
            strokeColor: strokeColor,
            strokeWidth: strokeWidth,
            gradient: gradient,
          ),
        ),
      );
    }
    // ═══════════════════════════════════════════════════════
    // LINE
    // ═══════════════════════════════════════════════════════
    else if (shapeType == 'line') {
      shapeWidget = SizedBox(
        width: w,
        height: h.clamp(1.0, double.infinity),
        child: CustomPaint(
          painter: _LinePainter(
            color: strokeColor != Colors.transparent ? strokeColor : fillColor,
            strokeWidth: strokeWidth > 0 ? strokeWidth : 1.0 * scale,
          ),
        ),
      );
    }
    // ═══════════════════════════════════════════════════════
    // COMPLEX SHAPES (polygon, path) — use fallback PNG
    // ═══════════════════════════════════════════════════════
    else {
      final String fallbackSrc = layer['_fallback_src'] ?? layer['src'] ?? '';
      if (fallbackSrc.isNotEmpty) {
        return _buildImageLayer(layer, lname, scale, nativeW, nativeH);
      }
      shapeWidget = Container(width: w, height: h, color: fillColor);
    }
    
    // ── Apply Gradient via ShaderMask if fill is string gradient ──
    if (gradientColors != null && gradientColors.length >= 2 && gradient == null) {
      String gradientDir = (layer['gradient_direction'] ?? 'vertical').toString();
      Alignment begin = Alignment.topCenter;
      Alignment end = Alignment.bottomCenter;
      if (gradientDir.contains('horizontal') || gradientDir.contains('left')) {
        begin = Alignment.centerLeft;
        end = Alignment.centerRight;
      }
      shapeWidget = ShaderMask(
        blendMode: BlendMode.srcIn,
        shaderCallback: (bounds) => LinearGradient(
          begin: begin, end: end, colors: gradientColors!,
        ).createShader(bounds),
        child: shapeWidget,
      );
    }
    
    // ── Apply Opacity ──
    if (opacity < 1.0 && opacity >= 0.0) {
      shapeWidget = Opacity(opacity: opacity, child: shapeWidget);
    }
    
    // ── Apply Rotation ──

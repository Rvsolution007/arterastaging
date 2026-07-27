import re

with open('c:\\xampp\\htdocs\\artera\\brandkit_mobile\\lib\\widgets\\editor_canvas_widget.dart', 'r', encoding='utf-8') as f:
    content = f.read()

# Find _buildText and _buildVectorShape
text_match = re.search(r'(  Widget _buildText\(Map<String, dynamic> layer, double scale\).*?return textWidget;\n  \})', content, flags=re.DOTALL)
shape_match = re.search(r'(  Widget _buildVectorShape\(Map<String, dynamic> layer, String lname,\s*double scale, double nativeW, double nativeH, int renderVersion\).*?return shapeWidget;\n  \})', content, flags=re.DOTALL)

if not text_match or not shape_match:
    print("Could not find base functions")
    exit(1)

base_text = text_match.group(1)
base_shape = shape_match.group(1)

# Generate _buildTextV10
v10_text = base_text.replace(
    'Widget _buildText(Map<String, dynamic> layer, double scale) {',
    'Widget _buildTextV10(Map<String, dynamic> layer, double scale, int renderVersion) {'
)
v10_text = re.sub(r"int renderVersion = \(widget\.config\['render_version'\].*?return _buildTextV10\(layer, scale, renderVersion\);\s*\}\s*", "", v10_text, flags=re.DOTALL)

letter_spacing_replacement = """
    // V10 text spacing enhancements
    double? letterSpacing;
    final dynamic rawCharSpacing = layer['letterSpacing'] ?? layer['letter_spacing'] ?? layer['char_spacing'];
    if (rawCharSpacing != null) {
      final double charSpacing = safeDouble(rawCharSpacing);
      letterSpacing = (charSpacing / 1000) * fontSize;
    }
    
    double? wordSpacing;
    final dynamic rawWordSpacing = layer['wordSpacing'] ?? layer['word_spacing'];
    if (rawWordSpacing != null) {
      wordSpacing = safeDouble(rawWordSpacing);
    }
"""
v10_text = re.sub(r"// Character spacing — web editor.*?letterSpacing = \(charSpacing / 1000\) \* fontSize;\s*\}", letter_spacing_replacement, v10_text, flags=re.DOTALL)
v10_text = v10_text.replace(
    'letterSpacing: letterSpacing,',
    'letterSpacing: letterSpacing,\n      wordSpacing: wordSpacing,'
)

flip_rot_logic = """
    bool flipX = layer['flip_x'] == true || layer['flipX'] == 'true' || layer['flipX'] == true;
    bool flipY = layer['flip_y'] == true || layer['flipY'] == 'true' || layer['flipY'] == true;
    double rotation = safeDouble(layer['rotation'] ?? layer['angle'] ?? 0);

    if (flipX) {
      textWidget = Transform(
        alignment: Alignment.center,
        transform: Matrix4.rotationY(3.1415926535897932), // pi
        child: textWidget,
      );
    }
    if (flipY) {
      textWidget = Transform(
        alignment: Alignment.center,
        transform: Matrix4.rotationX(3.1415926535897932), // pi
        child: textWidget,
      );
    }
    if (rotation != 0) {
      textWidget = Transform.rotate(
        angle: rotation * 3.1415926535897932 / 180,
        child: textWidget,
      );
    }

    return textWidget;
"""
v10_text = v10_text.replace('return textWidget;', flip_rot_logic)

# Generate _buildVectorShapeV10
v10_shape = base_shape.replace(
    'Widget _buildVectorShape(Map<String, dynamic> layer, String lname,\n      double scale, double nativeW, double nativeH, int renderVersion) {',
    'Widget _buildVectorShapeV10(Map<String, dynamic> layer, String lname,\n      double scale, double nativeW, double nativeH, int renderVersion) {'
)
v10_shape = re.sub(r"if \(renderVersion >= 10\) \{.*?return _buildVectorShapeV10.*?\}\s*", "", v10_shape, flags=re.DOTALL)

shape_fill_replace = """
    // V10 Stroke and Fill parsing
    final dynamic fillVal = layer['fill_color'] ?? layer['fill'];
    if (fillVal is String && fillVal.isNotEmpty && fillVal != 'none') {
      fillColor = _parseColor(fillVal, fallback: Colors.transparent);
      gradientColors = _parseGradient(fillVal);
    } else if (fillVal is Map) {
      gradient = _fabricGradientToFlutter(fillVal, w, h);
    }
    
    // Parse Stroke
    Color strokeColor = Colors.transparent;
    double strokeWidth = 0;
    final dynamic strokeVal = layer['stroke_color'] ?? layer['stroke'];
    if (strokeVal != null && strokeVal.toString() != 'null' && strokeVal.toString() != 'none') {
      strokeColor = _parseColor(strokeVal.toString());
      strokeWidth = safeDouble(layer['stroke_width'] ?? layer['strokeWidth'] ?? 0) * scale;
    }
    
    // Parse Border Radius
    double cornerRadius = safeDouble(layer['corner_radius'] ?? 0) * scale;
    double rx = cornerRadius > 0 ? cornerRadius : safeDouble(layer['rx'] ?? 0) * scale;
    double ry = cornerRadius > 0 ? cornerRadius : safeDouble(layer['ry'] ?? 0) * scale;
"""
v10_shape = re.sub(
    r"final dynamic fillVal = layer\['fill'\];.*?double ry = safeDouble\(layer\['ry'\] \?\? 0\) \* scale;",
    shape_fill_replace,
    v10_shape,
    flags=re.DOTALL
)

# Now remove the badly injected code at the bottom of the file
# It starts with `  Widget _buildTextV10` and ends before `class _TextShiftSource` or similar
# The safest way is to search for `  Widget _buildTextV10` up to EOF and remove it.
bad_injection_match = re.search(r'(  Widget _buildTextV10\(.*)', content, flags=re.DOTALL)
if bad_injection_match:
    content = content.replace(bad_injection_match.group(1), '')

# Also remove `Widget _buildVectorShapeV10` if it's there
bad_injection_shape = re.search(r'(Widget _buildVectorShapeV10\(.*)', content, flags=re.DOTALL)
if bad_injection_shape:
    content = content.replace(bad_injection_shape.group(1), '')

# Clean trailing whitespace before EOF
content = content.rstrip() + "\n"

# Now inject both V10 functions right before `  Widget _buildIconLayer`
inject_target = r'(  Widget _buildIconLayer\(Map<String, dynamic> layer, String lname, double scale, double nativeW, double nativeH, int renderVersion\) \{)'
content = re.sub(inject_target, lambda m: v10_text + "\n\n" + v10_shape + "\n\n" + m.group(1), content)

with open('c:\\xampp\\htdocs\\artera\\brandkit_mobile\\lib\\widgets\\editor_canvas_widget.dart', 'w', encoding='utf-8') as f:
    f.write(content)

print("Restoration and injection complete!")

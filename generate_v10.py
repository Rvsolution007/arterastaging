import os
import re

with open('c:\\xampp\\htdocs\\artera\\buildText.dart', 'r', encoding='utf-8') as f:
    text_content = f.read()

# buildText has `Widget _buildText(Map<String, dynamic> layer, double scale) {`
# We'll adapt it into `Widget _buildTextV10(Map<String, dynamic> layer, double scale, int renderVersion) {`
v10_text = text_content.replace(
    'Widget _buildText(Map<String, dynamic> layer, double scale) {',
    'Widget _buildTextV10(Map<String, dynamic> layer, double scale, int renderVersion) {'
)

# Remove the recursive dispatch logic we added to `_buildText` inside the extracted copy
v10_text = re.sub(r"int renderVersion = \(widget\.config\['render_version'\].*?return _buildTextV10\(layer, scale, renderVersion\);\s*\}", "", v10_text, flags=re.DOTALL)

# Inject word_spacing, flip_x, flip_y, rotation
# Find where letterSpacing is parsed
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

# Add wordSpacing to TextStyle
v10_text = v10_text.replace(
    'letterSpacing: letterSpacing,',
    'letterSpacing: letterSpacing,\n      wordSpacing: wordSpacing,'
)

# Apply flip and rotation at the very end before returning textWidget
flip_rot_logic = """
    bool flipX = layer['flip_x'] == true || layer['flipX'] == 'true' || layer['flipX'] == true;
    bool flipY = layer['flip_y'] == true || layer['flipY'] == 'true' || layer['flipY'] == true;
    double rotation = safeDouble(layer['rotation'] ?? 0);

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

with open('c:\\xampp\\htdocs\\artera\\buildShape.dart', 'r', encoding='utf-8') as f:
    shape_content = f.read()

v10_shape = shape_content.replace(
    'Widget _buildVectorShape(Map<String, dynamic> layer, String lname,\n      double scale, double nativeW, double nativeH, int renderVersion) {',
    'Widget _buildVectorShapeV10(Map<String, dynamic> layer, String lname,\n      double scale, double nativeW, double nativeH, int renderVersion) {'
)

# Extract only the _buildVectorShape definition
v10_shape = v10_shape[v10_shape.find('Widget _buildVectorShapeV10'):]

# Replace fill and stroke parsing
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


with open('c:\\xampp\\htdocs\\artera\\brandkit_mobile\\lib\\widgets\\editor_canvas_widget.dart', 'r', encoding='utf-8') as f:
    main_code = f.readlines()

insert_idx = -1
for i, line in enumerate(main_code):
    if "class _TextShiftSource {" in line:
        insert_idx = i - 1
        break

if insert_idx != -1:
    main_code.insert(insert_idx, v10_text + "\n" + v10_shape + "\n")
    with open('c:\\xampp\\htdocs\\artera\\brandkit_mobile\\lib\\widgets\\editor_canvas_widget.dart', 'w', encoding='utf-8') as f:
        f.writelines(main_code)
    print("Injected successfully!")
else:
    print("Could not find injection point")

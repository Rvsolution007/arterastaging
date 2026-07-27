import re

with open('c:\\xampp\\htdocs\\artera\\brandkit_mobile\\lib\\widgets\\editor_canvas_widget.dart', 'r', encoding='utf-8') as f:
    content = f.read()

match_text = re.search(r'(\s*\}\s*Widget _buildTextV10.*?return textWidget;\s*\})', content, flags=re.DOTALL)
match_shape = re.search(r'(Widget _buildVectorShapeV10.*?return shapeWidget;\s*\})', content, flags=re.DOTALL)

if match_text and match_shape:
    text_code = match_text.group(1)
    shape_code = match_shape.group(1)
    
    # Remove from content
    content = content.replace(text_code, '')
    content = content.replace(shape_code, '')
    
    # Clean up text_code
    text_code = text_code.strip()
    if text_code.startswith('}'):
        text_code = text_code[1:].strip()
    
    # Re-inject at the end of _EditorCanvasWidgetState
    target_pattern = r'    // 3\. Unrecognized icon fallback\s*return null;\s*\}\s*\}'
    
    def replacer(m):
        original = m.group(0)
        return original[:-1] + "\n\n" + text_code + "\n\n" + shape_code + "\n}\n"
    
    content = re.sub(target_pattern, replacer, content)
    
    with open('c:\\xampp\\htdocs\\artera\\brandkit_mobile\\lib\\widgets\\editor_canvas_widget.dart', 'w', encoding='utf-8') as f:
        f.write(content)
    print("Fixed injection!")
else:
    print("Could not find functions")

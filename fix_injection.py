import re

with open('c:\\xampp\\htdocs\\artera\\brandkit_mobile\\lib\\widgets\\editor_canvas_widget.dart', 'r', encoding='utf-8') as f:
    content = f.read()

# The injected block starts with `  }\n\n  Widget _buildTextV10` and ends right before `/// Helper class for auto-layout shift calculation.`
# Wait, let's extract it carefully.

match = re.search(r'(  \}\n\n  Widget _buildTextV10.*?)/// Helper class', content, flags=re.DOTALL)
if match:
    injected_code = match.group(1)
    
    # Remove it from its current position
    content = content.replace(injected_code, '')
    
    # Strip the leading `  }\n\n` from the injected code
    injected_code = injected_code.strip()
    if injected_code.startswith('}'):
        injected_code = injected_code[1:].strip()
    
    # Now find the end of _EditorCanvasWidgetState
    # It ends with:
    #     // 3. Unrecognized icon fallback
    #     return null;
    #   }
    # }
    
    target_pattern = r'    // 3\. Unrecognized icon fallback\s*return null;\s*\}\s*\}'
    
    # We want to replace the last `}` with the injected code + `\n}`
    def replacer(m):
        original = m.group(0)
        # return everything except the last `}`
        return original[:-1] + "\n\n" + injected_code + "\n}\n"
    
    content = re.sub(target_pattern, replacer, content)
    
    with open('c:\\xampp\\htdocs\\artera\\brandkit_mobile\\lib\\widgets\\editor_canvas_widget.dart', 'w', encoding='utf-8') as f:
        f.write(content)
    print("Fixed injection!")
else:
    print("Could not find injected code.")

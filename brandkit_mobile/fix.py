import re
import os

files = [
    'c:/xampp/htdocs/Artera/brandkit_mobile/lib/widgets/interactive_layer.dart',
    'c:/xampp/htdocs/Artera/brandkit_mobile/lib/widgets/editor_canvas_widget.dart',
    'c:/xampp/htdocs/Artera/brandkit_mobile/lib/controllers/native_editor_controller.dart'
]

def replace_toSafeDouble(text):
    # We want to replace EXPRESSION.toSafeDouble() with safeDouble(EXPRESSION)
    # Since regex can't easily match nested parens generically, we can loop
    
    # Simple cases first: varName.toSafeDouble()
    text = re.sub(r'([\w\d_]+(?:\[.*?\])?(?:\?\[.*?\])?(?:\[.*?\])?)\.toSafeDouble\(\)', r'safeDouble(\1)', text)
    
    # Case: (expr).toSafeDouble()
    # We will find .toSafeDouble(), then walk backwards to find the matching open parenthesis.
    while True:
        idx = text.find('.toSafeDouble()')
        if idx == -1:
            break
            
        # We know it's a parenthesis right before it
        if text[idx-1] == ')':
            paren_count = 1
            start = idx - 2
            while start >= 0:
                if text[start] == ')':
                    paren_count += 1
                elif text[start] == '(':
                    paren_count -= 1
                    if paren_count == 0:
                        break
                start -= 1
                
            if paren_count == 0:
                expr = text[start+1:idx-1]
                # Replace it!
                text = text[:start] + f"safeDouble({expr})" + text[idx+15:]
            else:
                # Malformed? Just break to avoid infinite loop
                break
        else:
            # It's like rawCS?.toSafeDouble()
            # Already handled by regex mostly, but if not:
            text = text[:idx] + ".REPLACEME_SAFE()" + text[idx+15:] # prevent infinite loop
            
    return text.replace('.REPLACEME_SAFE()', '.toSafeDouble()')

for filepath in files:
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()
        
    new_content = replace_toSafeDouble(content)
    
    with open(filepath, 'w', encoding='utf-8') as f:
        f.write(new_content)
        
print("Done")

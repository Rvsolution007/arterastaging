import 'package:flutter/material.dart'; // Ignore
import 'dart:io';

void main() {
  final files = [
    'c:/xampp/htdocs/Artera/brandkit_mobile/lib/widgets/interactive_layer.dart',
    'c:/xampp/htdocs/Artera/brandkit_mobile/lib/widgets/editor_canvas_widget.dart',
    'c:/xampp/htdocs/Artera/brandkit_mobile/lib/controllers/native_editor_controller.dart'
  ];

  for (var file in files) {
    var f = File(file);
    var content = f.readAsStringSync();
    
    // Replace (expr).toSafeDouble() with safeDouble(expr)
    // Handle matched parentheses up to 3 levels deep
    var r1 = RegExp(r'\(([^()]*\([^()]*\([^()]*\)[^()]*\)[^()]*)\)\.toSafeDouble\(\)');
    content = content.replaceAllMapped(r1, (m) => 'safeDouble(${m[1]})');

    var r2 = RegExp(r'\(([^()]*\([^()]*\)[^()]*)\)\.toSafeDouble\(\)');
    content = content.replaceAllMapped(r2, (m) => 'safeDouble(${m[1]})');

    var r3 = RegExp(r'\(([^()]*)\)\.toSafeDouble\(\)');
    content = content.replaceAllMapped(r3, (m) => 'safeDouble(${m[1]})');
    
    // Replace var.toSafeDouble() with safeDouble(var)
    var r4 = RegExp(r'([\w\d_\[\]\?]+)\.toSafeDouble\(\)');
    content = content.replaceAllMapped(r4, (m) => 'safeDouble(${m[1]})');
    
    // Replace remaining .toSafeDouble() just in case (this might be syntax error but we'll see)
    // Actually no, let's not blindly replace.
    
    f.writeAsStringSync(content);
  }
}

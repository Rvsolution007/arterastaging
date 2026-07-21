import 'package:brandkit_mobile/controllers/native_editor_controller.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  test('manual color selection becomes the canonical original color', () {
    final controller = NativeEditorController();
    controller.templateConfig['layers'] = [
      <String, dynamic>{'name': 'headline', 'type': 'text', 'color': '#0D47A1'},
    ];

    controller.setLayerColor('headline', '#FFCC00');

    final layer = (controller.templateConfig['layers'] as List).single as Map;
    expect(layer['color'], '#FFCC00');
    expect(layer['font_color'], '#FFCC00');
    expect(layer['tint_color'], '#FFCC00');
    expect(layer['original_color'], '#FFCC00');
  });
}

import 'package:brandkit_mobile/controllers/native_editor_controller.dart';
import 'dart:typed_data';
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

  test('opening a new template clears stale frame transition state', () {
    final controller = NativeEditorController();
    controller.selectedLayerId.value = 'old-layer';
    controller.activeTool.value = 'font';
    controller.loadingFrameId.value = 'old-frame';
    controller.isCanvasLoading.value = true;
    controller.frameTransitionPreviewUrl.value =
        'https://old.example/frame.png';
    NativeEditorController.transitionSnapshot.value = Uint8List.fromList([1]);

    controller.initConfig(
      {
        'render_version': 10,
        'info': {'width': 1080, 'height': 1080},
        'layers': [
          {
            'id': 'new-headline',
            'name': 'new-headline',
            'type': 'text',
            'text': 'New template',
          },
        ],
      },
      '',
      '',
      null,
      'custom',
    );

    expect(controller.selectedLayerId.value, isEmpty);
    expect(controller.activeTool.value, isEmpty);
    expect(controller.loadingFrameId.value, isEmpty);
    expect(controller.isCanvasLoading.value, isFalse);
    expect(controller.frameTransitionPreviewUrl.value, isEmpty);
    expect(NativeEditorController.transitionSnapshot.value, isNull);
    expect(controller.historyStack, hasLength(1));
    expect(controller.editorSessionGeneration.value, 1);
  });
}

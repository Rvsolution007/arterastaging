import 'package:brandkit_mobile/controllers/native_editor_controller.dart';
import 'package:brandkit_mobile/controllers/home_controller.dart';
import 'dart:typed_data';
import 'package:flutter_test/flutter_test.dart';
import 'package:get/get.dart';

class _TestHomeController extends HomeController {
  @override
  void onInit() {}
}

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

  test('V10 business placeholders preserve their explicit field index', () {
    expect(
      NativeEditorController.parseV10BusinessBindingForTest({
        'name': 'phone_2',
        'type': 'text',
      }),
      {'field': 'phone', 'index': 1, 'key': 'phone_2'},
    );
    expect(
      NativeEditorController.parseV10BusinessBindingForTest({
        'name': 'arbitrary-label',
        'type': 'text',
        'business_field': 'email',
        'business_field_index': 1,
      }),
      {'field': 'email', 'index': 1, 'key': 'email_2'},
    );
    expect(
      NativeEditorController.parseV10BusinessBindingForTest({
        'name': 'address',
        'type': 'text',
      }),
      {'field': 'address', 'index': 0, 'key': 'address_1'},
    );
  });

  test('V10 resolves every indexed business placeholder independently', () {
    Get.testMode = true;
    final home = Get.put<HomeController>(_TestHomeController());
    home.businessPhone.value = '1111111111';
    home.extraPhones.assignAll(['2222222222']);
    home.businessEmail.value = 'first@example.com';
    home.extraEmails.assignAll(['second@example.com']);
    home.businessWebsite.value = 'first.example';
    home.extraWebsites.assignAll(['second.example']);
    home.businessAddress.value = 'First address';
    home.extraAddresses.assignAll(['Second address']);

    final controller = NativeEditorController();
    controller.templateConfig.assignAll({
      'render_version': 10,
      'layers': [
        for (final entry in [
          ('phone', 0),
          ('phone', 1),
          ('email', 0),
          ('email', 1),
          ('website', 0),
          ('website', 1),
          ('address', 0),
          ('address', 1),
        ])
          <String, dynamic>{
            'name': '${entry.$1}_${entry.$2 + 1}',
            'type': 'text',
            'text': 'authored placeholder',
            'business_field': entry.$1,
            'business_field_index': entry.$2,
          },
      ],
    });

    controller.reapplyBusinessProfile();

    final layers = controller.templateConfig['layers'] as List;
    expect(layers.map((layer) => layer['text']).toList(), [
      '1111111111',
      '2222222222',
      'first@example.com',
      'second@example.com',
      'first.example',
      'second.example',
      'First address',
      'Second address',
    ]);
    Get.reset();
  });

  test('V10 repairs duplicate staging placeholder indexes by layer order', () {
    Get.testMode = true;
    final home = Get.put<HomeController>(_TestHomeController());
    home.businessPhone.value = '1111111111';
    home.extraPhones.assignAll(['2222222222']);
    home.businessEmail.value = 'first@example.com';
    home.extraEmails.assignAll(['second@example.com']);
    home.businessWebsite.value = 'first.example';
    home.extraWebsites.assignAll(['second.example']);
    home.businessAddress.value = 'First address';
    home.extraAddresses.assignAll(['Second address']);

    final controller = NativeEditorController();
    controller.templateConfig.assignAll({
      'render_version': 10,
      'layers': [
        for (final field in ['phone', 'email', 'website', 'address'])
          for (int occurrence = 0; occurrence < 2; occurrence++)
            <String, dynamic>{
              'name': '${field}_1',
              'type': 'text',
              'text': 'authored placeholder',
              'business_field': field,
              'business_field_index': 0,
              'placeholder_key': '${field}_1',
            },
      ],
    });

    controller.reapplyBusinessProfile();

    final layers = controller.templateConfig['layers'] as List;
    expect(layers.map((layer) => layer['text']).toList(), [
      '1111111111',
      '2222222222',
      'first@example.com',
      'second@example.com',
      'first.example',
      'second.example',
      'First address',
      'Second address',
    ]);
    expect(layers.map((layer) => layer['business_field_index']).toList(), [
      0,
      1,
      0,
      1,
      0,
      1,
      0,
      1,
    ]);
    Get.reset();
  });
}

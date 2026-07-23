import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:brandkit_mobile/widgets/editor_canvas_widget.dart';
import 'package:brandkit_mobile/controllers/native_editor_controller.dart';
import 'package:font_awesome_flutter/font_awesome_flutter.dart';
import 'package:get/get.dart';

void main() {
  setUp(() {
    Get.testMode = true;
    Get.put(NativeEditorController());
  });

  tearDown(Get.reset);

  testWidgets('V10+ text renders the non-destructive resolved colour', (
    WidgetTester tester,
  ) async {
    final Map<String, dynamic> config = {
      'render_version': 11,
      'info': {'width': 1080, 'height': 1080},
      'layers': [
        {
          'id': 'headline',
          'name': 'headline',
          'type': 'text',
          'text': 'Contrast headline',
          'x': 10,
          'y': 10,
          'w': 500,
          'h': 80,
          'size': 30,
          'color': '#123A9E',
          'font_color': '#123A9E',
          '_resolved_color': '#FFFFFF',
          'z_index': 1,
        },
      ],
    };

    await tester.pumpWidget(
      MaterialApp(
        home: Scaffold(
          body: SizedBox(
            width: 1080,
            height: 1080,
            child: EditorCanvasWidget(
              config: config,
              width: 1080,
              uploadsBaseUrl: '',
              templateBaseUrl: '',
            ),
          ),
        ),
      ),
    );

    final text = tester.widget<Text>(find.text('Contrast headline'));
    expect(text.style?.color, Colors.white);
    expect(config['layers'][0]['color'], '#123A9E');
  });

  testWidgets('acknowledges a V10 frame after its replacement paint', (
    WidgetTester tester,
  ) async {
    final paintedGenerations = <int>[];
    final config = <String, dynamic>{
      'render_version': 10,
      'info': {'width': 1080, 'height': 1080},
      'layers': <dynamic>[],
    };

    Widget buildCanvas(int generation) => MaterialApp(
      home: SizedBox(
        width: 1080,
        height: 1080,
        child: EditorCanvasWidget(
          config: config,
          width: 1080,
          uploadsBaseUrl: '',
          templateBaseUrl: '',
          frameTransitionGeneration: generation,
          onFramePainted: paintedGenerations.add,
        ),
      ),
    );

    await tester.pumpWidget(buildCanvas(0));
    await tester.pump();
    expect(paintedGenerations, isEmpty);

    await tester.pumpWidget(buildCanvas(1));
    await tester.pump();
    expect(paintedGenerations, [1]);

    await tester.pump();
    expect(paintedGenerations, [1]);
  });

  testWidgets('Test 3 icons rendering at different positions', (
    WidgetTester tester,
  ) async {
    final Map<String, dynamic> fakeJson = {
      'info': {'width': 1080, 'height': 1080},
      'layers': [
        {
          'name': 'Icon',
          'type': 'icon',
          'iconName': 'facebook',
          'color': '#1877F2',
          'x': 100,
          'y': 100,
          'w': 100,
          'h': 100,
          'size': 100,
          'z_index': 1,
        },
        {
          'name': 'Icon',
          'type': 'icon',
          'iconName': 'instagram',
          'color': '#E1306C',
          'x': 300,
          'y': 100,
          'w': 100,
          'h': 100,
          'size': 100,
          'z_index': 2,
        },
        {
          'name': 'Icon',
          'type': 'icon',
          'iconName': 'twitter',
          'color': '#1DA1F2',
          'x': 500,
          'y': 100,
          'w': 100,
          'h': 100,
          'size': 100,
          'z_index': 3,
        },
      ],
    };

    await tester.pumpWidget(
      MaterialApp(
        home: Scaffold(
          body: Center(
            child: SizedBox(
              width: 1080,
              height: 1080,
              child: EditorCanvasWidget(
                config: fakeJson,
                width: 1080,
                uploadsBaseUrl: '',
                templateBaseUrl: '',
              ),
            ),
          ),
        ),
      ),
    );

    await tester.pumpAndSettle();

    expect(find.byType(FaIcon), findsNWidgets(3));
  });
}

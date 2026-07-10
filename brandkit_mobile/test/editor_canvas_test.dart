import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:brandkit_mobile/widgets/editor_canvas_widget.dart';
import 'package:font_awesome_flutter/font_awesome_flutter.dart';

void main() {
  testWidgets('Test 3 icons rendering at different positions', (WidgetTester tester) async {
    final Map<String, dynamic> fakeJson = {
      'info': {'width': 1080, 'height': 1080},
      'layers': [
        {
          'name': 'Icon', 'type': 'icon', 'iconName': 'facebook', 'color': '#1877F2',
          'x': 100, 'y': 100, 'w': 100, 'h': 100, 'size': 100, 'z_index': 1
        },
        {
          'name': 'Icon', 'type': 'icon', 'iconName': 'instagram', 'color': '#E1306C',
          'x': 300, 'y': 100, 'w': 100, 'h': 100, 'size': 100, 'z_index': 2
        },
        {
          'name': 'Icon', 'type': 'icon', 'iconName': 'twitter', 'color': '#1DA1F2',
          'x': 500, 'y': 100, 'w': 100, 'h': 100, 'size': 100, 'z_index': 3
        }
      ]
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
              ),
            ),
          ),
        ),
      ),
    );

    await tester.pumpAndSettle();

    expect(find.byType(FaIcon), findsNWidgets(3));
    print('TEST PASSED: Found 3 icons rendered by EditorCanvasWidget!');
  });
}

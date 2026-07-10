import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  testWidgets('Multiple identical rawName text keys', (WidgetTester tester) async {
    final Map<String, GlobalKey> _textKeys = {};
    
    Widget buildLayer(String name, String text, int index) {
      String rawName = name;
      String uniqueKeyName = rawName;
      int dupeIndex = 1;
      while (_textKeys.containsKey(uniqueKeyName) && _textKeys[uniqueKeyName] != null) {
        uniqueKeyName = rawName + '_dup' + dupeIndex.toString();
        dupeIndex++;
      }
      final key = _textKeys.putIfAbsent(uniqueKeyName, () => GlobalKey());
      
      return KeyedSubtree(
        key: key,
        child: Text(text),
      );
    }

    await tester.pumpWidget(
      MaterialApp(
        home: Column(
          children: [
            buildLayer('Icon', 'A', 0),
            buildLayer('Icon', 'B', 1),
            buildLayer('Icon', 'C', 2),
          ],
        ),
      ),
    );

    expect(find.text('A'), findsOneWidget);
    expect(find.text('B'), findsOneWidget);
    expect(find.text('C'), findsOneWidget);
    print('TEST PASSED! All 3 widgets rendered!');
  });
}

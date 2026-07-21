import 'package:brandkit_mobile/utils/dynamic_color_resolver.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  group('DynamicColorResolver', () {
    test('keeps a dark blue web color on a light background for V1-V9', () {
      for (var renderVersion = 1; renderVersion <= 9; renderVersion++) {
        expect(
          DynamicColorResolver.resolve(
            originalColor: '#0D47A1',
            backgroundIsDark: false,
          ),
          '#0D47A1',
          reason: 'render version $renderVersion',
        );
      }
    });

    test('keeps a light web color on a dark background', () {
      expect(
        DynamicColorResolver.resolve(
          originalColor: '#F3E5F5',
          backgroundIsDark: true,
        ),
        '#F3E5F5',
      );
    });

    test('uses white for dark-on-dark and black for light-on-light', () {
      expect(
        DynamicColorResolver.resolve(
          originalColor: '#000000',
          backgroundIsDark: true,
        ),
        DynamicColorResolver.white,
      );
      expect(
        DynamicColorResolver.resolve(
          originalColor: '#FFFFFF',
          backgroundIsDark: false,
        ),
        DynamicColorResolver.black,
      );
    });

    test('keeps an unparseable saved color instead of applying a fallback', () {
      const gradient = 'linear-gradient(#123456, #abcdef)';
      expect(
        DynamicColorResolver.resolve(
          originalColor: gradient,
          backgroundIsDark: false,
        ),
        gradient,
      );
    });
  });
}

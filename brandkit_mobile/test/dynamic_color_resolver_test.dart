import 'package:brandkit_mobile/utils/dynamic_color_resolver.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  group('V10 dynamic colour truth table', () {
    test('keeps an exact dark blue on a light background', () {
      expect(
        DynamicColorResolver.resolve(
          originalColor: '#123A9E',
          backgroundIsDark: false,
        ),
        '#123A9E',
      );
    });

    test('keeps an exact light colour on a dark background', () {
      expect(
        DynamicColorResolver.resolve(
          originalColor: '#FFF2B2',
          backgroundIsDark: true,
        ),
        '#FFF2B2',
      );
    });

    test('changes dark on dark to white and light on light to black', () {
      expect(
        DynamicColorResolver.resolve(
          originalColor: '#123A9E',
          backgroundIsDark: true,
        ),
        DynamicColorResolver.white,
      );
      expect(
        DynamicColorResolver.resolve(
          originalColor: '#FFF2B2',
          backgroundIsDark: false,
        ),
        DynamicColorResolver.black,
      );
    });

    test('never substitutes an unparseable authored colour', () {
      const rawGradient = 'linear-gradient(#123A9E,#FFF2B2)';
      expect(
        DynamicColorResolver.resolve(
          originalColor: rawGradient,
          backgroundIsDark: true,
        ),
        rawGradient,
      );
    });
  });
}

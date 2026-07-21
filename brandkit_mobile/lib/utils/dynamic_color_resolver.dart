/// Resolves the display color for a text or icon layer against its local
/// background without discarding the color chosen in the web editor.
class DynamicColorResolver {
  const DynamicColorResolver._();

  static const String black = '0xFF000000';
  static const String white = '0xFFFFFFFF';

  /// Keeps the exact original color whenever it already contrasts by tone.
  ///
  /// An unparseable value (for example, a gradient) is returned untouched so
  /// the native editor never replaces a saved design color with a fallback.
  static String resolve({
    required String originalColor,
    required bool backgroundIsDark,
  }) {
    final originalArgb = _tryParseArgb(originalColor);
    if (originalArgb == null) return originalColor;

    final bool originalIsDark = _brightness(originalArgb) < 128;
    if (backgroundIsDark) {
      return originalIsDark ? white : originalColor;
    }

    return originalIsDark ? originalColor : black;
  }

  static int? _tryParseArgb(String rawColor) {
    final colorStr = rawColor.trim();
    if (colorStr.isEmpty) return null;

    if (colorStr.startsWith('rgb(') && colorStr.endsWith(')')) {
      final parts = colorStr
          .substring(4, colorStr.length - 1)
          .split(',')
          .map((part) => int.tryParse(part.trim()))
          .toList();
      if (parts.length == 3 && parts.every((part) => part != null)) {
        final int red = parts[0]!;
        final int green = parts[1]!;
        final int blue = parts[2]!;
        if (red > 255 ||
            green > 255 ||
            blue > 255 ||
            red < 0 ||
            green < 0 ||
            blue < 0) {
          return null;
        }
        return 0xFF000000 | (red << 16) | (green << 8) | blue;
      }
      return null;
    }

    var hex = colorStr.replaceFirst('#', '').replaceFirst('0x', '');
    if (hex.length == 6) hex = 'FF$hex';
    if (hex.length != 8) return null;

    final value = int.tryParse(hex, radix: 16);
    return value;
  }

  static double _brightness(int argb) {
    final int red = (argb >> 16) & 0xFF;
    final int green = (argb >> 8) & 0xFF;
    final int blue = argb & 0xFF;
    return 0.299 * red + 0.587 * green + 0.114 * blue;
  }
}

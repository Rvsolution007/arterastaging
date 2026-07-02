import 'package:brandkit_mobile/utils/string_extensions.dart';
import 'package:get/get.dart';

extension StringFormatting on String {
  String get trFormat {
    String translated = this.tr;
    if (translated == this) {
      String spaced = replaceAll('_', ' ');
      if (spaced.isEmpty) return spaced;
      return spaced[0].toUpperCase() + spaced.substring(1).toLowerCase();
    }
    return translated;
  }
}

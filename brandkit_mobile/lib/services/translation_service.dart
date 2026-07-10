import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'api_service.dart';

class TranslationService extends Translations {
  static const String _langKey = 'selected_app_language';
  static Map<String, Map<String, String>> appKeys = {};
  
  static String? savedLangCode;
  
  // Expose active languages to UI (e.g. settings)
  static List<Map<String, dynamic>> availableLanguages = [];

  @override
  Map<String, Map<String, String>> get keys => TranslationService.appKeys;

  /// Fetch translations from API and initialize
  static Future<void> init() async {
    final prefs = await SharedPreferences.getInstance();
    savedLangCode = prefs.getString(_langKey) ?? 'en'; // default English
    
    // Attempt to load from API
    try {
      final response = await ApiService.get('/app-translations');
      if (response.statusCode == 200) {
        final List<dynamic> data = jsonDecode(response.body);
        availableLanguages.clear();
        appKeys.clear();
        
        for (var lang in data) {
          String code = lang['language_code'] ?? 'en';
          String title = lang['title'] ?? 'Unknown';
          
          availableLanguages.add({
            'code': code,
            'title': title,
          });
          
          if (lang['translations'] != null) {
            Map<String, dynamic> rawTrans = lang['translations'];
            Map<String, String> stringTrans = {};
            rawTrans.forEach((k, v) {
              stringTrans[k] = v.toString();
            });
            appKeys[code] = stringTrans;
          }
        }

        // ── CRITICAL FIX: Inject translations into GetX's internal map ──
        // GetMaterialApp reads `keys` only once at startup (when appKeys is empty).
        // We must manually push loaded translations into GetX so .tr works.
        if (appKeys.isNotEmpty) {
          Get.addTranslations(appKeys);
          debugPrint('[TranslationService] Injected ${appKeys.length} languages into GetX');
          
          // Re-apply saved locale so GetX picks up the correct language
          if (savedLangCode != null && savedLangCode != 'en') {
            Get.updateLocale(Locale(savedLangCode!));
          }
        }
      }
    } catch (e) {
      debugPrint("Failed to load translations from API: $e");
    }
  }

  static void changeLanguage(String langCode) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_langKey, langCode);
    savedLangCode = langCode;
    
    // Re-inject translations to be safe (ensures GetX has them)
    if (appKeys.isNotEmpty) {
      Get.addTranslations(appKeys);
    }
    
    // Apply changes in GetX globally
    Get.updateLocale(Locale(langCode));
  }
}

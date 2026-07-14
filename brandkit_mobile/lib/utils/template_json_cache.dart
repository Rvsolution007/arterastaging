import 'package:shared_preferences/shared_preferences.dart';

/// Cache template JSON locally with last_updated timestamp for 304 sync.
class TemplateJsonCache {
    static const _prefix = 'tpl_cache_';
    static const _tsPrefix = 'tpl_ts_';

    /// Get cached JSON for a template, or null if not cached.
    static Future<String?> getCached(String zipName) async {
        final prefs = await SharedPreferences.getInstance();
        return prefs.getString('$_prefix$zipName');
    }

    /// Get the last_updated timestamp for a cached template.
    static Future<String?> getTimestamp(String zipName) async {
        final prefs = await SharedPreferences.getInstance();
        return prefs.getString('$_tsPrefix$zipName');
    }

    /// Save template JSON and its timestamp to local cache.
    static Future<void> save(String zipName, String json, String? updatedAt) async {
        final prefs = await SharedPreferences.getInstance();
        await prefs.setString('$_prefix$zipName', json);
        if (updatedAt != null) {
            await prefs.setString('$_tsPrefix$zipName', updatedAt);
        }
    }
}

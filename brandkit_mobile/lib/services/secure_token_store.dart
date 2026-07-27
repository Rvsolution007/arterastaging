import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:shared_preferences/shared_preferences.dart';

/// Stores the mobile bearer token in the operating system's protected keychain
/// instead of the app's ordinary preferences file.
class SecureTokenStore {
  static const _tokenKey = 'auth_token';

  static const FlutterSecureStorage _storage = FlutterSecureStorage(
    aOptions: AndroidOptions(),
    iOptions: IOSOptions(
      accessibility: KeychainAccessibility.first_unlock_this_device,
    ),
  );

  static Future<String?> read() async {
    final secureToken = await _storage.read(key: _tokenKey);
    if (secureToken != null && secureToken.isNotEmpty) {
      return secureToken;
    }

    // One-time migration for existing app users. The legacy value is removed
    // immediately after a successful secure-store write.
    final preferences = await SharedPreferences.getInstance();
    final legacyToken = preferences.getString(_tokenKey);
    if (legacyToken != null && legacyToken.isNotEmpty) {
      await _storage.write(key: _tokenKey, value: legacyToken);
      await preferences.remove(_tokenKey);
      return legacyToken;
    }

    return null;
  }

  static Future<void> write(String token) {
    return _storage.write(key: _tokenKey, value: token);
  }

  static Future<void> clear() async {
    await _storage.delete(key: _tokenKey);
    final preferences = await SharedPreferences.getInstance();
    await preferences.remove(_tokenKey);
  }
}

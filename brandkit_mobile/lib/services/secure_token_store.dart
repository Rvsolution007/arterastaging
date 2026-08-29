import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../config/app_config.dart';

/// Stores the mobile bearer token in the operating system's protected keychain
/// instead of the app's ordinary preferences file.
class SecureTokenStore {
  static const _tokenKey = 'auth_token';
  static const _environmentKey = 'auth_token_environment';
  static const _environmentResetPendingKey = 'auth_environment_reset_pending';

  static const FlutterSecureStorage _storage = FlutterSecureStorage(
    aOptions: AndroidOptions(),
    iOptions: IOSOptions(
      accessibility: KeychainAccessibility.first_unlock_this_device,
    ),
  );

  static Future<String?> read() async {
    // A token belongs only to the server that issued it. A debug/local build
    // and a production build share Android storage, so never reuse a token
    // after the configured environment changes.
    if (await prepareCurrentEnvironment()) {
      return null;
    }

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

  static Future<void> write(String token) async {
    await _storage.write(key: _tokenKey, value: token);
    final preferences = await SharedPreferences.getInstance();
    await preferences.setString(_environmentKey, _currentEnvironment);
  }

  static Future<void> clear() async {
    await _storage.delete(key: _tokenKey);
    final preferences = await SharedPreferences.getInstance();
    await preferences.remove(_tokenKey);
  }

  /// Clears a prior server's token before it can be sent to the current
  /// server. Returns true only when an existing session was invalidated.
  static Future<bool> prepareCurrentEnvironment() async {
    final preferences = await SharedPreferences.getInstance();
    final savedEnvironment = preferences.getString(_environmentKey);
    final hasStoredToken =
        await _storage.containsKey(key: _tokenKey) ||
        preferences.containsKey(_tokenKey);
    final hasSession =
        hasStoredToken ||
        (preferences.getString('userId')?.isNotEmpty ?? false) ||
        (preferences.getBool('isGuest') ?? false);

    if (savedEnvironment == _currentEnvironment) {
      return preferences.getBool(_environmentResetPendingKey) ?? false;
    }

    await _storage.delete(key: _tokenKey);
    await preferences.remove(_tokenKey);
    await preferences.setString(_environmentKey, _currentEnvironment);
    if (hasSession) {
      await preferences.setBool(_environmentResetPendingKey, true);
    }

    // A legacy installation has no environment marker. Fail closed instead
    // of guessing which server issued its token.
    return hasSession;
  }

  static Future<void> acknowledgeEnvironmentReset() async {
    final preferences = await SharedPreferences.getInstance();
    await preferences.remove(_environmentResetPendingKey);
  }

  static String get _currentEnvironment => AppConfig.currentEnv.name;
}

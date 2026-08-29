import 'package:flutter_secure_storage/flutter_secure_storage.dart';

/// AdLive receives its own scoped token after a server-to-server Artera SSO
/// exchange. It is intentionally separate from Artera's mobile token.
class AdLiveTokenStore {
  static const _tokenKey = 'adlive_auth_token';

  static const FlutterSecureStorage _storage = FlutterSecureStorage(
    aOptions: AndroidOptions(),
    iOptions: IOSOptions(
      accessibility: KeychainAccessibility.first_unlock_this_device,
    ),
  );

  static Future<String?> read() async {
    final token = await _storage.read(key: _tokenKey);
    return token != null && token.isNotEmpty ? token : null;
  }

  static Future<void> write(String token) =>
      _storage.write(key: _tokenKey, value: token);

  static Future<void> clear() => _storage.delete(key: _tokenKey);
}

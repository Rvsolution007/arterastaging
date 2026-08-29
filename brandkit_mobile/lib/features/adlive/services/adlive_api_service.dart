import 'dart:convert';

import 'package:http/http.dart' as http;

import '../../../config/app_config.dart';
import 'adlive_token_store.dart';

class AdLiveApiException implements Exception {
  const AdLiveApiException(this.message, {this.statusCode});

  final String message;
  final int? statusCode;

  @override
  String toString() => message;
}

/// API client for the AdLive server only. It never sends the Artera token to
/// AdLive; the SSO ticket is exchanged once and then stored separately.
class AdLiveApiService {
  static String get baseUrl => AppConfig.adLiveApiBaseUrl;

  static Future<Map<String, dynamic>> exchangeArteraTicket(String ticket) {
    return _post('/auth/artera/exchange', {'ticket': ticket});
  }

  static Future<Map<String, dynamic>> bootstrap() => _get('/mobile/bootstrap');

  static Future<Map<String, dynamic>> metaConnectionStatus() =>
      _get('/mobile/meta/status');

  static Future<Map<String, dynamic>> facebookSdkConfiguration() =>
      _get('/mobile/meta/sdk-configuration');

  static Future<Map<String, dynamic>> startMetaConnection() =>
      _post('/mobile/meta/connect', const {});

  static Future<Map<String, dynamic>> launchConnectedAccounts() =>
      _post('/mobile/connected-accounts/launch', const {});

  static Future<Map<String, dynamic>> completeNativeMetaConnection(
    String token,
  ) => _post('/mobile/meta/native/complete', {'access_token': token});

  static Future<Map<String, dynamic>> _get(String endpoint) async {
    final response = await http
        .get(Uri.parse('$baseUrl$endpoint'), headers: await _headers())
        .timeout(const Duration(seconds: 20));

    return _decode(response);
  }

  static Future<Map<String, dynamic>> _post(
    String endpoint,
    Map<String, dynamic> body,
  ) async {
    final response = await http
        .post(
          Uri.parse('$baseUrl$endpoint'),
          headers: await _headers(),
          body: jsonEncode(body),
        )
        .timeout(const Duration(seconds: 20));

    return _decode(response);
  }

  static Future<Map<String, String>> _headers() async {
    final token = await AdLiveTokenStore.read();

    return {
      'Accept': 'application/json',
      'Content-Type': 'application/json',
      if (token != null) 'Authorization': 'Bearer $token',
    };
  }

  static Map<String, dynamic> _decode(http.Response response) {
    Map<String, dynamic> data = {};
    try {
      final decoded = jsonDecode(response.body);
      if (decoded is Map<String, dynamic>) data = decoded;
    } catch (_) {
      // A generic error is safer than rendering an upstream HTML error page.
    }

    if (response.statusCode < 200 || response.statusCode >= 300) {
      throw AdLiveApiException(
        data['message']?.toString() ??
            'AdLive could not complete this request.',
        statusCode: response.statusCode,
      );
    }

    return data;
  }
}

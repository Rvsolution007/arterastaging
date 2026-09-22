import 'dart:convert';
import 'dart:math';
import 'package:http/http.dart' as http;
import 'package:http_parser/http_parser.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:flutter/foundation.dart';
import '../config/app_config.dart';
import 'secure_token_store.dart';

class ApiService {
  static const _pendingSessionActivityKey =
      'pending_session_activity_events_v1';
  static const _maxPendingSessionActivities = 50;
  static const _sessionActivityMaxAge = Duration(days: 7);
  static Future<void> _sessionActivitySequence = Future.value();

  // Base URL is now controlled by AppConfig (staging vs production)
  static String get baseUrl => AppConfig.baseUrl;

  static Future<Map<String, String>> _getHeaders() async {
    final token = await SecureTokenStore.read();
    return {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      if (token != null) 'Authorization': 'Bearer $token',
    };
  }

  static Future<http.Response> get(String endpoint) async {
    // Add cache busting timestamp for Flutter Web compatibility
    final timestamp = DateTime.now().millisecondsSinceEpoch;
    final separator = endpoint.contains('?') ? '&' : '?';
    final url = Uri.parse('$baseUrl$endpoint${separator}t=$timestamp');
    debugPrint('[ApiService] GET: $url');

    final headers = await _getHeaders();
    try {
      final response = await http.get(url, headers: headers).timeout(const Duration(seconds: 15));
      if (response.statusCode != 200) {
        debugPrint('[ApiService] GET Failed (${response.statusCode}): ${response.body}');
      }
      return response;
    } catch (e) {
      debugPrint('[ApiService] GET Exception: $e');
      rethrow;
    }
  }

  static Future<http.Response> post(String endpoint, Map<String, dynamic> body) async {
    final url = Uri.parse('$baseUrl$endpoint');
    debugPrint('[ApiService] POST: $url');
    final headers = await _getHeaders();
    try {
      final response = await http.post(url, headers: headers, body: jsonEncode(body)).timeout(const Duration(seconds: 60));
      if (response.statusCode != 200) {
        debugPrint('[ApiService] POST Failed (${response.statusCode}): ${response.body}');
      }
      return response;
    } catch (e) {
      debugPrint('[ApiService] POST Exception: $e');
      rethrow;
    }
  }



  static Future<http.Response> uploadSetupWizardSource(String endpoint, Map<String, String> fields, {String? filePath, List<int>? fileBytes, String? fileName}) async {
    final url = Uri.parse('$baseUrl$endpoint');
    final token = await SecureTokenStore.read();

    var request = http.MultipartRequest('POST', url);
    request.headers.addAll({
      'Accept': 'application/json',
      if (token != null) 'Authorization': 'Bearer $token',
    });

    request.fields.addAll(fields);
    if (filePath != null && filePath.isNotEmpty) {
      request.files.add(await http.MultipartFile.fromPath('catalogue_pdf', filePath));
    } else if (fileBytes != null && fileBytes.isNotEmpty && fileName != null) {
      request.files.add(http.MultipartFile.fromBytes(
        'catalogue_pdf', 
        fileBytes, 
        filename: fileName,
        contentType: MediaType('application', 'pdf'),
      ));
    }

    final streamedResponse = await request.send().timeout(const Duration(minutes: 5));
    return await http.Response.fromStream(streamedResponse);
  }

  static Future<http.Response> multipartPost(String endpoint, Map<String, String> fields, {String? fileKey, String? filePath, List<int>? fileBytes, String? fileName}) async {
    final url = Uri.parse('$baseUrl$endpoint');
    final token = await SecureTokenStore.read();

    var request = http.MultipartRequest('POST', url);
    request.headers.addAll({
      'Accept': 'application/json',
      if (token != null) 'Authorization': 'Bearer $token',
    });

    request.fields.addAll(fields);

    if (fileKey != null) {
      if (filePath != null && filePath.isNotEmpty) {
        request.files.add(await http.MultipartFile.fromPath(fileKey, filePath));
      } else if (fileBytes != null && fileName != null) {
        // Detect content type from file extension for proper server-side handling
        MediaType? contentType;
        final ext = fileName.split('.').last.toLowerCase();
        final mimeMap = {'jpg': 'image/jpeg', 'jpeg': 'image/jpeg', 'png': 'image/png', 'gif': 'image/gif', 'webp': 'image/webp', 'jfif': 'image/jpeg', 'bmp': 'image/bmp'};
        if (mimeMap.containsKey(ext)) {
          final parts = mimeMap[ext]!.split('/');
          contentType = MediaType(parts[0], parts[1]);
        }
        request.files.add(http.MultipartFile.fromBytes(fileKey, fileBytes, filename: fileName, contentType: contentType ?? MediaType('application', 'octet-stream')));
      }
    }

    final streamedResponse = await request.send();
    return await http.Response.fromStream(streamedResponse);
  }

  static Future<http.Response> getPaymentDetails() async {
    return await get('/payment-details');
  }

  static Future<http.Response> applyCoupon(String userId, String code, String planId) async {
    return await post('/coupon-code-validation', {
      'userId': userId,
      'code': code,
      'planId': planId,
    });
  }

  static Future<Map<String, dynamic>> updateBusiness(Map<String, dynamic> data) async {
    final response = await post('/update-business', data);
    return jsonDecode(response.body);
  }

  static Future<Map<String, dynamic>> getPaymentHistory(int userId) async {
    final response = await get('/payment-history?userId=$userId');
    return jsonDecode(response.body);
  }

  static Future<http.Response> createPayment(Map<String, dynamic> body) async {
    return await post('/create-payment', body);
  }

  static Future<void> trackActivity({
    required String action,
    String? itemType,
    String? itemId,
    bool? isPremium,
  }) async {
    // Downloads remain on the legacy endpoint because it atomically records
    // usage against plan/ad-reward entitlement. Session-only actions use the
    // idempotent endpoint below, which is safe to retry after connectivity or
    // token-refresh failures.
    if (action == 'download_template') {
      await _trackDownloadActivity(
        action: action,
        itemType: itemType,
        itemId: itemId,
        isPremium: isPremium,
      );
      return;
    }

    final next = _sessionActivitySequence.then(
      (_) => _trackSessionActivity(
        action: action,
        itemType: itemType,
        itemId: itemId,
      ),
      onError: (_) => _trackSessionActivity(
        action: action,
        itemType: itemType,
        itemId: itemId,
      ),
    );
    _sessionActivitySequence = next;
    await next;
  }

  static Future<void> _trackDownloadActivity({
    required String action,
    String? itemType,
    String? itemId,
    bool? isPremium,
  }) async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final userId = prefs.getString('userId');
      if (userId == null || userId.isEmpty) return;

      final payload = <String, dynamic>{
        'userId': userId,
        'action': action,
        'item_type': itemType,
        'item_id': itemId,
      };
      if (isPremium != null) {
        payload['is_premium'] = isPremium;
      }
      await post('/track-activity', payload);
    } catch (e) {
      // Download completion remains successful even if telemetry fails.
      debugPrint('Tracking error: $e');
    }
  }

  static Future<void> _trackSessionActivity({
    required String action,
    String? itemType,
    String? itemId,
  }) async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final userId = prefs.getString('userId');
      if (userId == null || userId.isEmpty) return;

      final events = _readPendingSessionActivities(prefs);
      events.add({
        'event_id': _newTrackingEventId(),
        'user_id': userId,
        'action': action,
        'item_type': itemType,
        'item_id': itemId,
        'platform': kIsWeb ? 'Web' : 'Mobile',
        'occurred_at': DateTime.now().toUtc().toIso8601String(),
      });
      await _savePendingSessionActivities(prefs, events);
      await _flushPendingSessionActivities(prefs, userId);
    } catch (e) {
      // The event has already been placed in local storage when possible.
      // Never interrupt navigation for optional analytics telemetry.
      debugPrint('Session tracking queued: $e');
    }
  }

  static Future<void> _flushPendingSessionActivities(
    SharedPreferences prefs,
    String currentUserId,
  ) async {
    final now = DateTime.now().toUtc();
    final pending = _readPendingSessionActivities(
      prefs,
    ).where((event) => _isFreshSessionActivity(event, now)).toList();
    final remaining = <Map<String, dynamic>>[];

    for (var index = 0; index < pending.length; index++) {
      final event = pending[index];
      if (event['user_id'] != currentUserId) {
        remaining.add(event);
        continue;
      }

      if (await _sendSessionActivity(event)) {
        continue;
      }

      // Preserve this and later events in their original order. A later
      // request is unlikely to succeed while the current request is failing.
      remaining.addAll(pending.sublist(index));
      break;
    }

    await _savePendingSessionActivities(prefs, remaining);
  }

  static Future<bool> _sendSessionActivity(Map<String, dynamic> event) async {
    final payload = <String, dynamic>{
      'event_id': event['event_id'],
      'action': event['action'],
      'platform': event['platform'],
    };
    if (event['item_type'] != null) {
      payload['item_type'] = event['item_type'];
    }
    if (event['item_id'] != null) {
      payload['item_id'] = event['item_id'];
    }

    final response = await post('/track-session-activity', payload);
    final delivered = response.statusCode >= 200 && response.statusCode < 300;
    if (!delivered) {
      debugPrint('Session tracking pending (${response.statusCode}).');
    }
    return delivered;
  }

  static List<Map<String, dynamic>> _readPendingSessionActivities(
    SharedPreferences prefs,
  ) {
    final raw = prefs.getString(_pendingSessionActivityKey);
    if (raw == null || raw.isEmpty) return [];

    try {
      final decoded = jsonDecode(raw);
      if (decoded is! List) return [];
      return decoded
          .whereType<Map>()
          .map((event) => Map<String, dynamic>.from(event))
          .where(_isValidSessionActivity)
          .toList();
    } catch (_) {
      return [];
    }
  }

  static Future<void> _savePendingSessionActivities(
    SharedPreferences prefs,
    List<Map<String, dynamic>> events,
  ) async {
    final trimmed = events.length <= _maxPendingSessionActivities
        ? events
        : events.sublist(events.length - _maxPendingSessionActivities);
    if (trimmed.isEmpty) {
      await prefs.remove(_pendingSessionActivityKey);
      return;
    }
    await prefs.setString(_pendingSessionActivityKey, jsonEncode(trimmed));
  }

  static bool _isValidSessionActivity(Map<String, dynamic> event) {
    final action = event['action'];
    return event['event_id'] is String &&
        event['user_id'] is String &&
        (action == 'home_visit' || action == 'select_template') &&
        event['occurred_at'] is String;
  }

  static bool _isFreshSessionActivity(
    Map<String, dynamic> event,
    DateTime now,
  ) {
    final occurredAt = DateTime.tryParse(
      event['occurred_at'] as String,
    )?.toUtc();
    return occurredAt != null &&
        now.difference(occurredAt) <= _sessionActivityMaxAge;
  }

  static String _newTrackingEventId() {
    final random = Random.secure();
    final bytes = List<int>.generate(16, (_) => random.nextInt(256));
    bytes[6] = (bytes[6] & 0x0f) | 0x40;
    bytes[8] = (bytes[8] & 0x3f) | 0x80;
    final hex = bytes
        .map((byte) => byte.toRadixString(16).padLeft(2, '0'))
        .join();
    return '${hex.substring(0, 8)}-${hex.substring(8, 12)}-${hex.substring(12, 16)}-${hex.substring(16, 20)}-${hex.substring(20)}';
  }

  static Future<String> getWebEditorUrl({required String type, required String id, String designUrl = ''}) async {
    // Construct the direct web editor URL with SSO login
    final prefs = await SharedPreferences.getInstance();
    final userId = prefs.getString('userId') ?? '';

    final cleanBaseUrl = baseUrl.split('/').take(baseUrl.split('/').length - 1).join('/');
    final targetUrl = '/edit/$type/$id?from_app=1&design=${Uri.encodeComponent(designUrl)}';
    return '$cleanBaseUrl/webview-login?user_id=$userId&redirect=${Uri.encodeComponent(targetUrl)}';
  }

  /// Swap the product for a custom frame template.
  /// Returns AI-generated content for the specified product, with per-product caching.
  static Future<http.Response> swapProduct({
    required String frameId,
    required String productId,
  }) async {
    final prefs = await SharedPreferences.getInstance();
    final userId = prefs.getString('userId') ?? '';
    return await post('/custom-frame/swap-product', {
      'userId': userId,
      'frameId': frameId,
      'productId': productId,
    });
  }

  /// Get user's product list for the product picker.
  static Future<http.Response> getUserProducts() async {
    final prefs = await SharedPreferences.getInstance();
    final userId = prefs.getString('userId') ?? '';
    return await post('/products/list', {
      'userId': userId,
    });
  }
}

import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:http_parser/http_parser.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:flutter/foundation.dart';
import '../config/app_config.dart';

class ApiService {
  // Base URL is now controlled by AppConfig (staging vs production)
  static String get baseUrl => AppConfig.baseUrl;

  static Future<Map<String, String>> _getHeaders() async {
    final prefs = await SharedPreferences.getInstance();
    final token = prefs.getString('auth_token');
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
      final response = await http.get(url, headers: headers);
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
      final response = await http.post(url, headers: headers, body: jsonEncode(body));
      if (response.statusCode != 200) {
        debugPrint('[ApiService] POST Failed (${response.statusCode}): ${response.body}');
      }
      return response;
    } catch (e) {
      debugPrint('[ApiService] POST Exception: $e');
      rethrow;
    }
  }

  static Future<http.Response> uploadMagicClonerImage(String endpoint, String imagePath) async {
    final url = Uri.parse('$baseUrl$endpoint');
    final prefs = await SharedPreferences.getInstance();
    final token = prefs.getString('auth_token');
    
    var request = http.MultipartRequest('POST', url);
    request.headers.addAll({
      'Accept': 'application/json',
      if (token != null) 'Authorization': 'Bearer $token',
    });
    
    request.files.add(await http.MultipartFile.fromPath('inspiration_image', imagePath));
    
    final streamedResponse = await request.send();
    return await http.Response.fromStream(streamedResponse);
  }

  static Future<http.Response> uploadSetupWizardSource(String endpoint, Map<String, String> fields, {String? filePath, List<int>? fileBytes, String? fileName}) async {
    final url = Uri.parse('$baseUrl$endpoint');
    final prefs = await SharedPreferences.getInstance();
    final token = prefs.getString('auth_token');
    
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
    final prefs = await SharedPreferences.getInstance();
    final token = prefs.getString('auth_token');
    
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

  static Future<http.Response> applyCoupon(String userId, String code) async {
    return await post('/coupon-code-validation', {
      'userId': userId,
      'code': code,
    });
  }

  static Future<http.Response> createPayment(Map<String, dynamic> body) async {
    return await post('/create-payment', body);
  }

  static Future<void> trackActivity({
    required String action,
    String? itemType,
    String? itemId,
  }) async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final userId = prefs.getString('userId');
      
      if (userId == null) return;

      await post('/track-activity', {
        'userId': userId,
        'action': action,
        'item_type': itemType,
        'item_id': itemId,
      });
    } catch (e) {
      // Fail silently for tracking to avoid interrupting user experience
      debugPrint('Tracking error: $e');
    }
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

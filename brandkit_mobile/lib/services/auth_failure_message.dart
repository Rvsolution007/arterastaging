import 'dart:async';
import 'dart:convert';
import 'dart:io';
import 'package:http/http.dart' as http;

/// Keep transport details and server response dumps out of sign-in messages.
String authFailureMessage(Object error) {
  if (error is TimeoutException ||
      error is SocketException ||
      error is http.ClientException) {
    return 'Could not reach Artera. Check your connection and try again.';
  }
  return 'Could not complete sign-in. Please try again.';
}

String authResponseMessage(int statusCode, String body) {
  if (statusCode == 429) {
    return 'Too many attempts. Please wait a moment and try again.';
  }
  if (statusCode >= 500) {
    return 'Artera is temporarily unavailable. Please try again shortly.';
  }
  try {
    final data = jsonDecode(body);
    if (data is Map<String, dynamic>) {
      final message = data['message'];
      if (message is String && message.isNotEmpty) return message;
      if (message is List) {
        final messages = message.whereType<String>().toList();
        if (messages.isNotEmpty) return messages.join('\n');
      }
    }
  } catch (_) {
    // An HTML gateway response is not a useful authentication error.
  }
  return 'Please check your account details and try again.';
}

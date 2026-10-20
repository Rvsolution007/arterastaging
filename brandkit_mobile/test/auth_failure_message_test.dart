import 'dart:async';
import 'package:brandkit_mobile/services/auth_failure_message.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart' as http;

void main() {
  test('connection errors never display private server details', () {
    for (final error in [
      http.ClientException('internal-address/login'),
      TimeoutException('internal-address/login'),
      FormatException('private response'),
    ]) {
      expect(authFailureMessage(error), isNot(contains('internal-address')));
      expect(authFailureMessage(error), isNot(contains('private response')));
    }
  });
  test('registration validation arrays remain readable', () {
    expect(authResponseMessage(422, '{"message":["Already registered. Please log in."]}'),
        'Already registered. Please log in.');
    expect(authResponseMessage(503, '{"message":"private response"}'),
        isNot(contains('private response')));
    expect(authResponseMessage(502, '<html>Bad gateway</html>'),
        contains('temporarily unavailable'));
  });
}

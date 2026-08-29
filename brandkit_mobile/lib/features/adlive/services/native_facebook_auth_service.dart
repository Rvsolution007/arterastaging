import 'package:flutter_facebook_auth/flutter_facebook_auth.dart';
import 'package:flutter/services.dart';

class NativeFacebookLoginException implements Exception {
  const NativeFacebookLoginException(this.message);

  final String message;

  @override
  String toString() => message;
}

/// Opens the Facebook Android app for the signed-in person's consent. It is
/// intentionally native-only: Artera must not quietly fall back to the phone's
/// browser, which can have a different Facebook session than the Facebook app.
class NativeFacebookAuthService {
  static const _sdkChannel = MethodChannel('com.arterapixel.pro/facebook_sdk');

  static const _permissions = <String>[
    'pages_show_list',
    'pages_read_engagement',
    'pages_manage_ads',
    'pages_manage_metadata',
    'ads_read',
    'ads_management',
    'instagram_basic',
    'instagram_business_basic',
    'business_management',
    'whatsapp_business_management',
    'whatsapp_business_messaging',
  ];

  static Future<String> connect({required String clientToken}) async {
    final normalizedClientToken = clientToken.trim();
    if (normalizedClientToken.isEmpty) {
      throw const NativeFacebookLoginException(
        'Facebook app login is not configured yet. Ask the platform administrator to save the Facebook Client Token.',
      );
    }

    try {
      await _sdkChannel.invokeMethod<void>('setClientToken', {
        'clientToken': normalizedClientToken,
      });
    } on PlatformException catch (error) {
      throw NativeFacebookLoginException(
        error.message ?? 'Facebook app login could not be configured.',
      );
    }

    final result = await FacebookAuth.instance.login(
      permissions: _permissions,
      loginBehavior: LoginBehavior.nativeOnly,
      // AdLive needs a classic user token so its server can validate the
      // selected Page and save the connected assets. Limited login does not
      // return a token that can be used for this Meta Marketing workflow.
      loginTracking: LoginTracking.enabled,
    );
    final accessToken = result.accessToken?.tokenString;
    if (result.status == LoginStatus.success &&
        accessToken != null &&
        accessToken.isNotEmpty) {
      return accessToken;
    }

    final message = result.message?.trim();
    throw NativeFacebookLoginException(
      message == null || message.isEmpty
          ? 'Facebook app login was cancelled or could not be completed.'
          : message,
    );
  }
}

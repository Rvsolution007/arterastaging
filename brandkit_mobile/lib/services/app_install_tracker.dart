import 'dart:io';
import 'dart:math';

import 'package:device_info_plus/device_info_plus.dart';
import 'package:flutter/foundation.dart';
import 'package:package_info_plus/package_info_plus.dart';
import 'package:shared_preferences/shared_preferences.dart';

import 'api_service.dart';

/// Sends first-party install telemetry to Artera. No Play Store API is used.
class AppInstallTracker {
  static const _installIdKey = 'app_install_id';
  static const _fallbackDeviceIdKey = 'analytics_fallback_device_id';

  static Future<void> trackInstall({String? userId}) async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final installId = await _installId(prefs);
      final device = await _deviceDetails(prefs);
      final packageInfo = await PackageInfo.fromPlatform();

      await ApiService.post('/analytics/install', {
        'device_id': device.id,
        'install_id': installId,
        if (int.tryParse(userId ?? '') != null) 'userId': int.parse(userId!),
        'platform': device.platform,
        'app_version': '${packageInfo.version}+${packageInfo.buildNumber}',
        'device_model': device.model,
        'os_version': device.osVersion,
      });
    } catch (error) {
      // Analytics must never block startup or login.
      debugPrint('[InstallTracker] Unable to record app install: $error');
    }
  }

  static Future<String> deviceId() async {
    final prefs = await SharedPreferences.getInstance();
    return (await _deviceDetails(prefs)).id;
  }

  static Future<String> _installId(SharedPreferences prefs) async {
    final existing = prefs.getString(_installIdKey);
    if (existing != null && existing.isNotEmpty) return existing;

    final value = _randomId();
    await prefs.setString(_installIdKey, value);
    return value;
  }

  static Future<_DeviceDetails> _deviceDetails(SharedPreferences prefs) async {
    if (kIsWeb) {
      return _DeviceDetails(
        id: 'web-${await _fallbackDeviceId(prefs)}',
        platform: 'web',
        model: 'web',
        osVersion: 'web',
      );
    }

    final deviceInfo = DeviceInfoPlugin();
    if (Platform.isAndroid) {
      final info = await deviceInfo.androidInfo;
      return _DeviceDetails(
        id: 'android-${info.id}',
        platform: 'android',
        model: info.model,
        osVersion: info.version.release,
      );
    }

    if (Platform.isIOS) {
      final info = await deviceInfo.iosInfo;
      return _DeviceDetails(
        id: 'ios-${info.identifierForVendor ?? await _fallbackDeviceId(prefs)}',
        platform: 'ios',
        model: info.utsname.machine,
        osVersion: info.systemVersion,
      );
    }

    return _DeviceDetails(
      id: 'other-${await _fallbackDeviceId(prefs)}',
      platform: Platform.operatingSystem,
      model: Platform.localHostname,
      osVersion: Platform.operatingSystemVersion,
    );
  }

  static Future<String> _fallbackDeviceId(SharedPreferences prefs) async {
    final existing = prefs.getString(_fallbackDeviceIdKey);
    if (existing != null && existing.isNotEmpty) return existing;

    final value = _randomId();
    await prefs.setString(_fallbackDeviceIdKey, value);
    return value;
  }

  static String _randomId() {
    final random = Random.secure();
    final values = List<int>.generate(32, (_) => random.nextInt(256));
    return values
        .map((value) => value.toRadixString(16).padLeft(2, '0'))
        .join();
  }
}

class _DeviceDetails {
  const _DeviceDetails({
    required this.id,
    required this.platform,
    required this.model,
    required this.osVersion,
  });

  final String id;
  final String platform;
  final String model;
  final String osVersion;
}

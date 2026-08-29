import 'package:flutter/foundation.dart';

/// App Environment Configuration
class AppConfig {
  // An explicit dart-define always wins. Without one, debug builds use XAMPP
  // and release builds use the production servers so a local address can
  // never be shipped accidentally.
  static const String _rawEnv = String.fromEnvironment(
    'ENV',
    defaultValue: 'production',
  );
  static const String _localBaseUrl = String.fromEnvironment(
    'LOCAL_API_BASE_URL',
    // Never use 127.0.0.1 here: on Android that is the phone itself, not the
    // XAMPP machine. The local runner can still override this when the PC's
    // LAN address changes.
    defaultValue: 'http://192.168.1.66/Artera/123456',
  );
  static const bool _localUsesIndexFrontController = bool.fromEnvironment(
    'LOCAL_USE_INDEX_FRONT_CONTROLLER',
    defaultValue: true,
  );
  static const String _localAdLiveApiBaseUrl = String.fromEnvironment(
    'ADLIVE_LOCAL_API_BASE_URL',
    defaultValue: 'http://192.168.1.66/adlive/public/api/v1',
  );
  static const String _stagingAdLiveApiBaseUrl = String.fromEnvironment(
    'ADLIVE_STAGING_API_BASE_URL',
    defaultValue: 'https://staging.arteraadlive.com/api/v1',
  );
  static const String _productionAdLiveApiBaseUrl = String.fromEnvironment(
    'ADLIVE_PRODUCTION_API_BASE_URL',
    defaultValue: 'https://arteraadlive.com/api/v1',
  );

  static AppEnvironment get currentEnv {
    switch (_rawEnv) {
      case 'staging':
        return AppEnvironment.staging;
      case 'production':
        return AppEnvironment.production;
      case 'local':
        return AppEnvironment.local;
      case 'auto':
      default:
        return kReleaseMode ? AppEnvironment.production : AppEnvironment.local;
    }
  }

  static String get baseUrl {
    switch (currentEnv) {
      case AppEnvironment.local:
        return _localUsesIndexFrontController
            ? _withIndexFrontController(_localBaseUrl)
            : _localBaseUrl;
      case AppEnvironment.staging:
        return 'https://stagingartera.arterapixel.com/123456';
      case AppEnvironment.production:
        return 'https://arterapixel.com/123456';
    }
  }

  static String get appName {
    switch (currentEnv) {
      case AppEnvironment.local:
        return 'Artera (Local)';
      case AppEnvironment.staging:
        return 'Artera (Staging)';
      case AppEnvironment.production:
        return 'Artera Pixel';
    }
  }

  /// Public AdLive API address. Secrets are configured only on the two
  /// servers; Flutter uses this URL plus the scoped AdLive session token.
  static String get adLiveApiBaseUrl {
    switch (currentEnv) {
      case AppEnvironment.local:
        return _localAdLiveApiBaseUrl;
      case AppEnvironment.staging:
        return _stagingAdLiveApiBaseUrl;
      case AppEnvironment.production:
        return _productionAdLiveApiBaseUrl;
    }
  }

  static bool get isLocal => currentEnv == AppEnvironment.local;
  static bool get isStaging => currentEnv == AppEnvironment.staging;
  static bool get isProduction => currentEnv == AppEnvironment.production;

  /// XAMPP commonly mounts Laravel below a directory (for example /artera).
  /// In that setup Apache may not expose pretty API routes to a LAN device,
  /// while index.php/route is reliable. Staging and production never use
  /// this local-only normalization.
  static String _withIndexFrontController(String value) {
    final uri = Uri.parse(value);
    final segments = List<String>.from(uri.pathSegments);

    if (segments.contains('index.php') || segments.isEmpty) {
      return value;
    }

    // The API prefix is the last segment of the configured local base URL.
    segments.insert(segments.length - 1, 'index.php');

    return uri.replace(pathSegments: segments).toString();
  }
}

enum AppEnvironment { local, staging, production }

import 'package:flutter/foundation.dart' show kIsWeb;

/// App Environment Configuration
class AppConfig {
  // Read target environment from build-time dart-define, defaulting to local
  static const String _rawEnv = String.fromEnvironment('ENV', defaultValue: 'local');

  static AppEnvironment get currentEnv {
    switch (_rawEnv) {
      case 'staging':
        return AppEnvironment.staging;
      case 'production':
        return AppEnvironment.production;
      case 'local':
      default:
        return AppEnvironment.local;
    }
  }

  static String get baseUrl {
    switch (currentEnv) {
      case AppEnvironment.local:
        return 'http://192.168.1.34/Artera/123456';
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

  static bool get isLocal => currentEnv == AppEnvironment.local;
  static bool get isStaging => currentEnv == AppEnvironment.staging;
  static bool get isProduction => currentEnv == AppEnvironment.production;
}

enum AppEnvironment {
  local,
  staging,
  production,
}

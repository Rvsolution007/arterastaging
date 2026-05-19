/// App Environment Configuration
/// Change [currentEnv] to switch between staging and production.
class AppConfig {
  // ──────────────────────────────────────────────
  // Change this ONE line to switch environments:
  static const AppEnvironment currentEnv = AppEnvironment.production;
  // ──────────────────────────────────────────────

  static String get baseUrl {
    switch (currentEnv) {
      case AppEnvironment.staging:
        return 'https://staging.arterapixel.com/123456';
      case AppEnvironment.production:
        return 'https://arterapixel.com/123456';
    }
  }

  static String get appName {
    switch (currentEnv) {
      case AppEnvironment.staging:
        return 'Artera (Staging)';
      case AppEnvironment.production:
        return 'Artera Pixel';
    }
  }

  static bool get isProduction => currentEnv == AppEnvironment.production;
  static bool get isStaging => currentEnv == AppEnvironment.staging;
}

enum AppEnvironment {
  staging,
  production,
}

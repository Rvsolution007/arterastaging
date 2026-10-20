import 'package:brandkit_mobile/config/app_config.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  test(
    'local API URL uses the front controller for a subdirectory install',
    () {
      expect(AppConfig.currentEnv, AppEnvironment.local);
      expect(AppConfig.baseUrl, 'http://127.0.0.1:8080/Artera/index.php/123456');
      expect(
        AppConfig.adLiveApiBaseUrl,
        'http://127.0.0.1:8080/adlive/public/api/v1',
      );
    },
  );
}

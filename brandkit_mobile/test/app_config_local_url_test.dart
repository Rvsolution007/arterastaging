import 'package:brandkit_mobile/config/app_config.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  test(
    'local API URL uses the front controller for a subdirectory install',
    () {
      expect(AppConfig.currentEnv, AppEnvironment.local);
      expect(
        AppConfig.baseUrl,
        'http://192.168.1.40/artera/index.php/your_api_route_prefix',
      );
    },
  );
}

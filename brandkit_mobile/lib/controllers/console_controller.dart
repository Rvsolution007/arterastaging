import 'package:get/get.dart';

class ConsoleController extends GetxController {
  final logs = <String>[].obs;

  static ConsoleController get to => Get.find<ConsoleController>();

  void addLog(String log) {
    Future.microtask(() {
      // Keep max 500 logs to prevent memory issues
      if (logs.length > 500) {
        logs.removeAt(0);
      }
      final timestamp = DateTime.now().toIso8601String().split('T').last.substring(0, 8);
      logs.add('[$timestamp] $log');
    });
  }

  void clearLogs() {
    logs.clear();
  }
}

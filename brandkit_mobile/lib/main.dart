import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'utils/app_colors.dart';
import 'utils/app_theme.dart';
import 'screens/login_screen.dart';
import 'screens/dashboard_screen.dart';
import 'services/ad_service.dart';
import 'controllers/ad_controller.dart';
import 'controllers/auth_controller.dart';
import 'controllers/subscription_controller.dart';
import 'services/api_service.dart';
import 'services/notification_service.dart';
import 'package:firebase_core/firebase_core.dart';
import 'dart:convert';
import 'package:flutter/foundation.dart' show kIsWeb;
import 'screens/notifications_screen.dart';
import 'screens/editor_screen.dart';
import 'screens/detail_list_screen.dart';
import 'controllers/home_controller.dart';

import 'package:flutter/foundation.dart';
import 'widgets/error_submission_dialog.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  
  // Initialize Firebase and Notifications only on mobile platforms
  if (!kIsWeb) {
    await Firebase.initializeApp();
    await NotificationService().initialize();
    await AdService().initialize();
  }
  
  // Register controllers globally so all screens can access them
  Get.put(AuthController(), permanent: true);
  Get.put(AdController(), permanent: true);
  Get.put(SubscriptionController(), permanent: true);
  Get.put(HomeController(), permanent: true);

  // Global Error Handler
  FlutterError.onError = (FlutterErrorDetails details) {
    FlutterError.presentError(details);
    debugPrint('Global Flutter Error: ${details.exceptionAsString()}');

    // Show a non-intrusive snackbar instead of an instant dialog
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (Get.context != null && !Get.isSnackbarOpen) {
        Get.snackbar(
          "Something went wrong",
          "A small issue was detected. Tap here to report it to our team.",
          mainButton: TextButton(
            onPressed: () {
              Get.back(); // close snackbar
              Get.dialog(
                ErrorSubmissionDialog(
                  errorCode: 'ERR_FLUTTER',
                  errorMessage: details.exceptionAsString(),
                ),
              );
            },
            child: const Text("REPORT", style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
          ),
          backgroundColor: Colors.black87,
          colorText: Colors.white,
          duration: const Duration(seconds: 5),
          snackPosition: SnackPosition.BOTTOM,
          margin: const EdgeInsets.all(12),
          borderRadius: 16,
        );
      }
    });
  };

  PlatformDispatcher.instance.onError = (error, stack) {
    debugPrint('Global Platform Error: $error');
    // Same for platform errors
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (Get.context != null && !Get.isSnackbarOpen) {
        Get.snackbar(
          "App Encountered an Issue",
          "Would you like to report this problem to our admin?",
          mainButton: TextButton(
            onPressed: () {
              Get.back();
              Get.dialog(
                ErrorSubmissionDialog(
                  errorCode: 'ERR_PLATFORM',
                  errorMessage: error.toString(),
                ),
              );
            },
            child: const Text("REPORT", style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
          ),
          backgroundColor: Colors.black87,
          colorText: Colors.white,
          duration: const Duration(seconds: 5),
          snackPosition: SnackPosition.BOTTOM,
          margin: const EdgeInsets.all(12),
          borderRadius: 16,
        );
      }
    });
    return true;
  };

  runApp(const BrandkitApp());
}

class BrandkitApp extends StatelessWidget {
  const BrandkitApp({super.key});

  @override
  Widget build(BuildContext context) {
    return GetMaterialApp(
      title: 'Artera Pixel',
      debugShowCheckedModeBanner: false,
      theme: AppTheme.lightTheme,
      home: const SplashGate(),
      getPages: [
        GetPage(name: '/', page: () => const SplashGate()),
        GetPage(name: '/LoginScreen', page: () => const LoginScreen()),
        GetPage(name: '/DashboardScreen', page: () => const DashboardScreen()),
        GetPage(name: '/notifications', page: () => const NotificationsScreen()),
        GetPage(name: '/editor', page: () {
          final args = Get.arguments as Map<String, dynamic>? ?? {};
          final params = Get.parameters;
          
          // Helper to get value from args or params
          dynamic getValue(String key) => args[key] ?? params[key];
          
          final idRaw = getValue('id');
          int id = 0;
          if (idRaw != null) {
            if (idRaw is int) id = idRaw;
            else if (idRaw is String) id = int.tryParse(idRaw) ?? 0;
          }

          return EditorScreen(
            type: getValue('type') ?? 'business_custom_frame',
            id: id,
            designUrl: getValue('designUrl') ?? '',
            frameData: args['frameData'] ?? {},
            aiAnalysisData: args['aiAnalysisData'],
            mappingRules: args['mappingRules'],
          );
        }),
        GetPage(name: '/details', page: () {
          final args = Get.arguments as Map<String, dynamic>? ?? {};
          return DetailListScreen(
            type: args['type'] ?? 'category',
            id: args['id'] ?? 0,
            title: args['title'] ?? 'Details',
          );
        }),
      ],
    );
  }
}

/// Checks if user is already logged in and redirects accordingly.
/// This prevents the app from asking for login every time it's reopened.
class SplashGate extends StatefulWidget {
  const SplashGate({super.key});

  @override
  State<SplashGate> createState() => _SplashGateState();
}

class _SplashGateState extends State<SplashGate> {
  @override
  void initState() {
    super.initState();
    _checkLoginStatus();
  }

  Future<void> _checkLoginStatus() async {
    final prefs = await SharedPreferences.getInstance();
    final userId = prefs.getString('userId');

    // Small delay for splash feel
    await Future.delayed(const Duration(milliseconds: 300));

    if (userId != null && userId.isNotEmpty) {
      try {
        // Fetch latest user data to sync ad limits and subscription state
        final response = await ApiService.post('/user_data', {'id': userId});
        if (response.statusCode == 200) {
          final data = jsonDecode(response.body);
          if (data['adConfig'] != null) {
            Get.find<AdController>().updateAdConfig(data['adConfig']);
          }
          // Sync subscription info for header badge & profile card
          final prefs2 = await SharedPreferences.getInstance();
          await prefs2.setString('planName', data['planName'] ?? '');
          await prefs2.setString('planDuration', data['planDuration'] ?? '');
          await prefs2.setString('planStartDate', data['planStartDate'] ?? '');
          await prefs2.setString('planEndDate', data['planEndDate'] ?? '');
          await prefs2.setBool('isSubscribe', data['isSubscribe'] ?? false);
          Get.find<SubscriptionController>().loadFromPrefs();
        }
      } catch (e) {
        debugPrint('Failed to sync user data on splash: $e');
      }

      // User is already logged in — go directly to Dashboard
      Get.offAll(() => const DashboardScreen());
    } else {
      // Not logged in — show login screen
      Get.offAll(() => const LoginScreen());
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      body: Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            const Icon(Icons.rocket_launch, size: 80, color: AppColors.primary),
            const SizedBox(height: 20),
            CircularProgressIndicator(color: AppColors.primary),
          ],
        ),
      ),
    );
  }
}

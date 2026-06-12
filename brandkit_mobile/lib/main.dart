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
import 'screens/native_editor_screen.dart';
import 'screens/detail_list_screen.dart';
import 'controllers/home_controller.dart';
import 'services/translation_service.dart';

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

  // Initialize Translations
  await TranslationService.init();

  // Global Error Handler
  FlutterError.onError = (FlutterErrorDetails details) {
    FlutterError.presentError(details);
    debugPrint('Global Flutter Error: ${details.exceptionAsString()}');
  };

  PlatformDispatcher.instance.onError = (error, stack) {
    debugPrint('Global Platform Error: $error');
    return true;
  };

  runApp(const ArteraApp());
}

class ArteraApp extends StatelessWidget {
  const ArteraApp({super.key});

  @override
  Widget build(BuildContext context) {
    return GetMaterialApp(
      title: 'Festival Post Maker',
      theme: AppTheme.lightTheme,
      debugShowCheckedModeBanner: false,
      translations: TranslationService(),
      locale: Locale(TranslationService.savedLangCode ?? 'en'),
      fallbackLocale: const Locale('en'),
      defaultTransition: Transition.cupertino,
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

          return NativeEditorScreen(
            type: getValue('type') ?? 'business_custom_frame',
            id: id,
            designUrl: getValue('designUrl') ?? '',
            frameData: args['frameData'] ?? <String, dynamic>{},
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

      // User is already logged in
      // On Web, read the actual browser URL to detect deep links (e.g. /#/editor?type=...)
      String? deepLinkFragment;

      if (kIsWeb) {
        final fragment = Uri.base.fragment; // e.g. "/editor?type=festival&id=5&designUrl=..."
        if (fragment.isNotEmpty) {
          final path = Uri.parse(fragment).path;
          // Only treat as deep link if it's NOT a root/dashboard/login route
          if (path != '/' &&
              path != '/DashboardScreen' &&
              path != '/LoginScreen' &&
              path != '/SplashGate' &&
              path.isNotEmpty) {
            deepLinkFragment = fragment; // Store the FULL fragment with query params
            debugPrint('[SplashGate] Deep link detected: $fragment');
          }
        }
      }

      Get.offAllNamed('/DashboardScreen');

      // If we detected a deep link (e.g. /editor?type=...&id=...), navigate there
      if (deepLinkFragment != null) {
        await Future.delayed(const Duration(milliseconds: 200)); // Let Dashboard settle
        Get.toNamed(deepLinkFragment!);
      }
    } else {
      // Not logged in — show login screen
      Get.offAllNamed('/LoginScreen');
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

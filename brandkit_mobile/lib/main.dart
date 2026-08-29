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
import 'package:firebase_messaging/firebase_messaging.dart';
import 'dart:convert';
import 'package:flutter/foundation.dart' show kIsWeb;
import 'screens/notifications_screen.dart';
import 'screens/native_editor_screen.dart';
import 'screens/detail_list_screen.dart';
import 'controllers/home_controller.dart';
import 'controllers/festival_ai_job_controller.dart';
import 'services/translation_service.dart';
import 'services/app_install_tracker.dart';

import 'package:flutter/foundation.dart';
import 'widgets/error_submission_dialog.dart';
import 'controllers/console_controller.dart';
import 'features/adlive/services/adlive_token_store.dart';
import 'services/secure_token_store.dart';

// Background message handler — must be top-level function (not inside a class)
// This runs in a separate isolate when app is in background/terminated
@pragma('vm:entry-point')
Future<void> _firebaseMessagingBackgroundHandler(RemoteMessage message) async {
  await Firebase.initializeApp();
  // FCM automatically shows notification with image in background
  // when the 'notification' block has an 'image' field.
  // No need to manually show — just ensure Firebase is initialized.
  debugPrint("Background FCM: ${message.notification?.title}");
}

void main() {
  WidgetsFlutterBinding.ensureInitialized();

  // ── PERF: Register controllers immediately (no async, no delay) ──
  Get.put(AuthController(), permanent: true);
  Get.put(AdController(), permanent: true);
  Get.put(SubscriptionController(), permanent: true);
  Get.put(HomeController(), permanent: true);
  Get.put(FestivalAiJobController(), permanent: true);

  // Initialize ConsoleController for in-app debugging
  final consoleController = Get.put(ConsoleController(), permanent: true);

  // Intercept all debugPrint calls
  final originalDebugPrint = debugPrint;
  debugPrint = (String? message, {int? wrapWidth}) {
    if (message != null) {
      consoleController.addLog(message);
    }
    originalDebugPrint(message, wrapWidth: wrapWidth);
  };

  // Global Error Handler
  FlutterError.onError = (FlutterErrorDetails details) {
    FlutterError.presentError(details);
    final errorMsg =
        'Global Flutter Error: ${details.exceptionAsString()}\nStacktrace: ${details.stack}';
    debugPrint(errorMsg);
    _sendErrorToLaravel(errorMsg);
  };

  PlatformDispatcher.instance.onError = (error, stack) {
    final errorMsg = 'Global Platform Error: $error\nStacktrace: $stack';
    debugPrint(errorMsg);
    _sendErrorToLaravel(errorMsg);
    return true;
  };

  // ── PERF: Call runApp() IMMEDIATELY ──
  // No awaits before this! Logo shows within ~500ms instead of 6s blank.
  // All heavy init (Firebase, Notifications, Ads, Translations) moves to SplashGate.
  runApp(const ArteraApp());
}

Future<void> _sendErrorToLaravel(String message) async {
  try {
    // Use the existing authenticated endpoint. Do not send an unauthenticated
    // debug endpoint or any access token in the error payload.
    await ApiService.post('/report-error', {
      'error_code': 'flutter_client_error',
      'error_message': message.length > 4000
          ? message.substring(0, 4000)
          : message,
      'device_info': 'flutter_app',
    }).timeout(const Duration(seconds: 5));
  } catch (_) {
    // Error reporting must never interrupt the app or create a retry loop.
  }
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
        GetPage(
          name: '/notifications',
          page: () => const NotificationsScreen(),
        ),
        GetPage(
          name: '/editor',
          page: () {
            final args = Get.arguments as Map<String, dynamic>? ?? {};
            final params = Get.parameters;

            // Helper to get value from args or params
            dynamic getValue(String key) => args[key] ?? params[key];

            final idRaw = getValue('id');
            int id = 0;
            if (idRaw != null) {
              if (idRaw is int)
                id = idRaw;
              else if (idRaw is String)
                id = int.tryParse(idRaw) ?? 0;
            }

            return NativeEditorScreen(
              type: getValue('type') ?? 'business_custom_frame',
              id: id,
              designUrl: getValue('designUrl') ?? '',
              frameData: args['frameData'] ?? <String, dynamic>{},
            );
          },
        ),
        GetPage(
          name: '/details',
          page: () {
            final args = Get.arguments as Map<String, dynamic>? ?? {};
            return DetailListScreen(
              type: args['type'] ?? 'category',
              id: args['id'] ?? 0,
              title: args['title'] ?? 'Details',
            );
          },
        ),
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
    _navigateFast();
  }

  /// ── ULTRA-FAST STARTUP ──
  /// 1. Native Splash shows `app_icon` instantly.
  /// 2. We navigate to Dashboard/Login immediately (NO delay).
  /// 3. Fire-and-forget ALL heavy init in background.
  Future<void> _navigateFast() async {
    // Read login status (SharedPreferences is instant, ~5ms)
    final prefs = await SharedPreferences.getInstance();
    final hadSessionFromAnotherEnvironment =
        await SecureTokenStore.prepareCurrentEnvironment();
    if (hadSessionFromAnotherEnvironment) {
      await AdLiveTokenStore.clear();
      await Future.wait([
        prefs.remove('userId'),
        prefs.remove('isGuest'),
        prefs.remove('userName'),
        prefs.remove('emailId'),
        prefs.remove('phoneNumber'),
        prefs.remove('profileImage'),
        prefs.remove('planName'),
        prefs.remove('planDuration'),
        prefs.remove('planStartDate'),
        prefs.remove('planEndDate'),
        prefs.remove('isSubscribe'),
        prefs.remove('isPartner'),
      ]);
      await SecureTokenStore.acknowledgeEnvironmentReset();
      Get.find<HomeController>().clearData();
      debugPrint(
        '[SplashGate] Cleared a session from another server environment.',
      );
    }
    final userId = prefs.getString('userId');
    final isGuest = prefs.getBool('isGuest') ?? false;

    // A local install ID is created once per app installation. On reinstallation
    // the app generates a new total-install event, while the backend deduplicates
    // unique installs with the physical mobile-device identifier.
    Future(() => AppInstallTracker.trackInstall(userId: userId));

    if ((userId != null && userId.isNotEmpty) || isGuest) {
      // Navigate to dashboard FIRST
      Get.offAllNamed('/DashboardScreen');

      // ── PERF: ALL heavy init runs AFTER dashboard is visible ──
      _doHeavyInitInBackground();
      _syncUserDataInBackground(userId);

      // Handle deep links (Web only)
      if (kIsWeb) {
        final fragment = Uri.base.fragment;
        if (fragment.isNotEmpty) {
          final path = Uri.parse(fragment).path;
          if (path != '/' &&
              path != '/DashboardScreen' &&
              path != '/LoginScreen' &&
              path != '/SplashGate' &&
              path.isNotEmpty) {
            await Future.delayed(const Duration(milliseconds: 200));
            Get.toNamed(fragment);
          }
        }
      }
    } else {
      // Not logged in — show login screen
      Get.offAllNamed('/LoginScreen');
      // Still init Firebase etc for push notifications
      _doHeavyInitInBackground();
    }
  }

  /// ── PERF: Fire-and-forget heavy initialization ──
  void _doHeavyInitInBackground() {
    Future(() async {
      try {
        if (!kIsWeb) {
          await Firebase.initializeApp();
          FirebaseMessaging.onBackgroundMessage(
            _firebaseMessagingBackgroundHandler,
          );
        }
        await Future.wait([
          if (!kIsWeb) NotificationService().initialize(),
          if (!kIsWeb) AdService().initialize(),
          TranslationService.init(),
        ]);
        debugPrint('[SplashGate] Background init complete ✓');
      } catch (e) {
        debugPrint('Background init error (non-fatal): $e');
      }
    });
  }

  /// ── PERF: Background sync of user data ──
  void _syncUserDataInBackground(String? userId) {
    Future(() async {
      try {
        final response = await ApiService.post('/user_data', {'id': userId});
        if (response.statusCode == 200) {
          final data = jsonDecode(response.body);
          if (data['adConfig'] != null) {
            Get.find<AdController>().updateAdConfig(data['adConfig']);
          }
          final prefs2 = await SharedPreferences.getInstance();
          await prefs2.setString('planName', data['planName'] ?? '');
          await prefs2.setString('planDuration', data['planDuration'] ?? '');
          await prefs2.setString('planStartDate', data['planStartDate'] ?? '');
          await prefs2.setString('planEndDate', data['planEndDate'] ?? '');
          await prefs2.setBool('isSubscribe', data['isSubscribe'] ?? false);
          Get.find<SubscriptionController>().loadFromPrefs();
        }
      } catch (e) {
        debugPrint('Background user data sync failed: $e');
      }
    });
  }

  @override
  Widget build(BuildContext context) {
    // Empty scaffold while navigating (Flutter removes native splash upon first render)
    return const Scaffold(backgroundColor: Colors.white);
  }
}

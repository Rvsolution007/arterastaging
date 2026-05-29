import 'dart:convert';
import 'dart:io';
import 'dart:typed_data';
import 'package:http/http.dart' as http;
import 'package:path_provider/path_provider.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter_local_notifications/flutter_local_notifications.dart';
import 'package:flutter/foundation.dart';
import 'package:get/get.dart';
import 'package:url_launcher/url_launcher.dart';
import '../screens/detail_list_screen.dart';

class NotificationService {
  static final NotificationService _instance = NotificationService._internal();
  factory NotificationService() => _instance;
  NotificationService._internal();

  final FirebaseMessaging _fcm = FirebaseMessaging.instance;
  final FlutterLocalNotificationsPlugin _localNotifications =
      FlutterLocalNotificationsPlugin();

  Future<void> initialize() async {
    try {
      debugPrint("=== NotificationService.initialize() START ===");

      // 1. Request permissions
      NotificationSettings settings = await _fcm.requestPermission(
        alert: true,
        badge: true,
        sound: true,
      );
      debugPrint('Notification permission: ${settings.authorizationStatus}');

      // 2. Get FCM token
      String? token = await _fcm.getToken();
      debugPrint("FCM Token: $token");

      // 3. Initialize flutter_local_notifications
      const AndroidInitializationSettings androidSettings =
          AndroidInitializationSettings('@mipmap/ic_launcher');
      const DarwinInitializationSettings iosSettings =
          DarwinInitializationSettings();
      const InitializationSettings initSettings = InitializationSettings(
        android: androidSettings,
        iOS: iosSettings,
      );

      await _localNotifications.initialize(
        initSettings,
        onDidReceiveNotificationResponse: (NotificationResponse response) {
          debugPrint("Notification tapped, payload: ${response.payload}");
          _handleNotificationTap(response.payload);
        },
      );

      // 4. Create high importance notification channel AND request permission
      if (Platform.isAndroid) {
        const AndroidNotificationChannel channel = AndroidNotificationChannel(
          'high_importance_channel',
          'High Importance Notifications',
          description: 'This channel is used for important notifications.',
          importance: Importance.max,
          playSound: true,
          enableVibration: true,
          showBadge: true,
        );

        final androidPlugin = _localNotifications
            .resolvePlatformSpecificImplementation<
                AndroidFlutterLocalNotificationsPlugin>();

        if (androidPlugin != null) {
          await androidPlugin.createNotificationChannel(channel);
          debugPrint("Notification channel created: high_importance_channel");

          // CRITICAL: Request POST_NOTIFICATIONS permission for Android 13+ (API 33+)
          // Without this, notifications silently fail - they don't show in shutter
          final bool? permissionGranted = await androidPlugin.requestNotificationsPermission();
          debugPrint("Android notification permission granted: $permissionGranted");
          
          if (permissionGranted != true) {
            debugPrint("WARNING: Notification permission NOT granted! Notifications will NOT show in shutter.");
          }
        }
      }

      // 5. Subscribe to 'all' topic
      await _fcm.subscribeToTopic('all');
      debugPrint("Subscribed to topic: all");

      // 6. Set foreground notification presentation (iOS mainly, but good practice)
      await _fcm.setForegroundNotificationPresentationOptions(
        alert: true,
        badge: true,
        sound: true,
      );

      // 7. Listen for foreground messages - THIS is the key handler for shutter notifications
      FirebaseMessaging.onMessage.listen((RemoteMessage message) {
        debugPrint("=== FOREGROUND MESSAGE RECEIVED ===");
        debugPrint("Title: ${message.notification?.title}");
        debugPrint("Body: ${message.notification?.body}");
        debugPrint("Data: ${message.data}");
        debugPrint("Image (android): ${message.notification?.android?.imageUrl}");
        debugPrint("Image (data): ${message.data['image']}");

        // Show visible snackbar so user knows FCM message arrived
        try {
          Get.snackbar(
            '📩 FCM Received',
            '${message.notification?.title ?? "No title"} - showing in shutter now...',
            snackPosition: SnackPosition.TOP,
            duration: const Duration(seconds: 3),
          );
        } catch (_) {}

        _showLocalNotification(message);
      });

      // 8. Handle notification tap when app is opened from background
      FirebaseMessaging.onMessageOpenedApp.listen((RemoteMessage message) {
        debugPrint("App opened from background notification: ${message.data}");
        _handleNotificationTap(jsonEncode(message.data));
      });

      // 9. Handle notification tap when app was terminated
      RemoteMessage? initialMessage = await _fcm.getInitialMessage();
      if (initialMessage != null) {
        debugPrint("App opened from terminated notification: ${initialMessage.data}");
        // Delay to let app fully initialize
        Future.delayed(const Duration(seconds: 1), () {
          _handleNotificationTap(jsonEncode(initialMessage.data));
        });
      }

      debugPrint("=== NotificationService.initialize() COMPLETE ===");
    } catch (e, stack) {
      debugPrint("FCM Initialization Error: $e");
      debugPrint("Stack: $stack");
    }
  }

  /// Show a local notification in the phone's shutter/status bar
  Future<void> _showLocalNotification(RemoteMessage message) async {
    try {
      RemoteNotification? notification = message.notification;
      if (notification == null) {
        debugPrint("No notification block in message, skipping local notification");
        return;
      }

      // Get image URL from multiple possible sources
      String? imageUrl = message.notification?.android?.imageUrl
          ?? message.notification?.apple?.imageUrl
          ?? message.data['image'];

      debugPrint("Will show local notification: title=${notification.title}, imageUrl=$imageUrl");

      // Build Android notification details
      AndroidNotificationDetails androidDetails;

      if (imageUrl != null && imageUrl.isNotEmpty) {
        // Try to download image for BigPicture style
        try {
          final http.Response imgResponse = await http.get(Uri.parse(imageUrl))
              .timeout(const Duration(seconds: 10));

          if (imgResponse.statusCode == 200 && imgResponse.bodyBytes.isNotEmpty) {
            final String imgPath = await _saveFile(
              imgResponse.bodyBytes,
              'notif_${DateTime.now().millisecondsSinceEpoch}.jpg',
            );
            debugPrint("Image downloaded successfully: $imgPath");

            androidDetails = AndroidNotificationDetails(
              'high_importance_channel',
              'High Importance Notifications',
              channelDescription: 'This channel is used for important notifications.',
              importance: Importance.max,
              priority: Priority.high,
              icon: '@mipmap/ic_launcher',
              largeIcon: FilePathAndroidBitmap(imgPath),
              styleInformation: BigPictureStyleInformation(
                FilePathAndroidBitmap(imgPath),
                largeIcon: FilePathAndroidBitmap(imgPath),
                contentTitle: notification.title,
                summaryText: notification.body,
                hideExpandedLargeIcon: false,
              ),
            );
          } else {
            debugPrint("Image download failed: status=${imgResponse.statusCode}");
            androidDetails = _buildSimpleAndroidDetails();
          }
        } catch (imgError) {
          debugPrint("Image download error: $imgError");
          androidDetails = _buildSimpleAndroidDetails();
        }
      } else {
        androidDetails = _buildSimpleAndroidDetails();
      }

      // Generate unique notification ID
      final int notifId = DateTime.now().millisecondsSinceEpoch.remainder(100000);

      // Encode payload as JSON string
      final String payload = jsonEncode(message.data);
      debugPrint("Showing notification id=$notifId with payload=$payload");

      await _localNotifications.show(
        notifId,
        notification.title,
        notification.body,
        NotificationDetails(
          android: androidDetails,
          iOS: const DarwinNotificationDetails(
            presentAlert: true,
            presentBadge: true,
            presentSound: true,
          ),
        ),
        payload: payload,
      );

      debugPrint("=== LOCAL NOTIFICATION SHOWN SUCCESSFULLY ===");
    } catch (e, stack) {
      debugPrint("ERROR in _showLocalNotification: $e");
      debugPrint("Stack: $stack");

      // Ultimate fallback - show basic notification no matter what
      try {
        await _localNotifications.show(
          DateTime.now().millisecondsSinceEpoch.remainder(100000),
          message.notification?.title ?? 'Notification',
          message.notification?.body ?? '',
          const NotificationDetails(
            android: AndroidNotificationDetails(
              'high_importance_channel',
              'High Importance Notifications',
              importance: Importance.max,
              priority: Priority.high,
            ),
          ),
          payload: jsonEncode(message.data),
        );
        debugPrint("Fallback notification shown");
      } catch (fallbackError) {
        debugPrint("Even fallback notification failed: $fallbackError");
      }
    }
  }

  /// Simple Android notification details without image
  AndroidNotificationDetails _buildSimpleAndroidDetails() {
    return const AndroidNotificationDetails(
      'high_importance_channel',
      'High Importance Notifications',
      channelDescription: 'This channel is used for important notifications.',
      importance: Importance.max,
      priority: Priority.high,
      icon: '@mipmap/ic_launcher',
    );
  }

  /// Save bytes to a temp file and return the path
  Future<String> _saveFile(Uint8List bytes, String fileName) async {
    final directory = await getTemporaryDirectory();
    final file = File('${directory.path}/$fileName');
    await file.writeAsBytes(bytes);
    return file.path;
  }

  /// Handle notification tap - navigate to the correct screen
  void _handleNotificationTap(String? payload) {
    if (payload == null || payload.isEmpty) return;
    debugPrint("=== NOTIFICATION TAP ===");
    debugPrint("Raw payload: $payload");

    try {
      Map<String, dynamic> data = {};

      // Parse payload (could be JSON string or Map.toString() format)
      if (payload.startsWith('{')) {
        try {
          data = Map<String, dynamic>.from(jsonDecode(payload));
        } catch (_) {
          // Fallback: parse {key: value, key2: value2} format
          final content = payload.substring(1, payload.length - 1);
          for (var part in content.split(', ')) {
            final colonIdx = part.indexOf(':');
            if (colonIdx > 0) {
              data[part.substring(0, colonIdx).trim()] =
                  part.substring(colonIdx + 1).trim();
            }
          }
        }
      }

      debugPrint("Parsed data: $data");

      final String type = data['type']?.toString() ?? '';
      final int id = int.tryParse(data['id']?.toString() ?? '') ?? 0;
      final String title = data['name']?.toString()
          ?? data['festival']?.toString()
          ?? data['custom']?.toString()
          ?? data['subscriptionPlan']?.toString()
          ?? 'Details';

      debugPrint("Navigation: type=$type, id=$id, title=$title");

      // External link - open in browser
      if (type == 'externalLink') {
        final link = data['externalLink']?.toString() ?? '';
        if (link.isNotEmpty) {
          Future.delayed(const Duration(milliseconds: 300), () {
            launchUrl(Uri.parse(link), mode: LaunchMode.externalApplication);
          });
          return;
        }
      }

      // Category, Festival, Custom, SubscriptionPlan - navigate to detail screen
      if (id > 0 && (type == 'category' || type == 'festival' || type == 'custom' || type == 'subscriptionPlan')) {
        Future.delayed(const Duration(milliseconds: 300), () {
          Get.to(() => DetailListScreen(
            type: type == 'subscriptionPlan' ? 'category' : type,
            id: id,
            title: title,
          ));
        });
        return;
      }

      // Fallback - go to notifications list
      Future.delayed(const Duration(milliseconds: 300), () {
        Get.toNamed('/notifications');
      });
    } catch (e) {
      debugPrint("Error handling notification tap: $e");
      Future.delayed(const Duration(milliseconds: 300), () {
        Get.toNamed('/notifications');
      });
    }
  }

  /// Get FCM token
  Future<String?> getToken() async {
    return await _fcm.getToken();
  }

  /// TEST: Fire a local notification directly (no FCM involved)
  /// Call this from a button to verify flutter_local_notifications works
  Future<void> testLocalNotification() async {
    try {
      debugPrint("=== TEST LOCAL NOTIFICATION ===");
      final int notifId = DateTime.now().millisecondsSinceEpoch.remainder(100000);
      
      await _localNotifications.show(
        notifId,
        'Test Local Notification 🔔',
        'If you see this in shutter, local notifications work!',
        const NotificationDetails(
          android: AndroidNotificationDetails(
            'high_importance_channel',
            'High Importance Notifications',
            channelDescription: 'This channel is used for important notifications.',
            importance: Importance.max,
            priority: Priority.high,
            icon: '@mipmap/ic_launcher',
          ),
          iOS: DarwinNotificationDetails(
            presentAlert: true,
            presentBadge: true,
            presentSound: true,
          ),
        ),
        payload: '{"type":"test"}',
      );
      debugPrint("=== TEST LOCAL NOTIFICATION FIRED ===");
    } catch (e, stack) {
      debugPrint("TEST NOTIFICATION ERROR: $e");
      debugPrint("Stack: $stack");
    }
  }
}

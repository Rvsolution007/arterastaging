import 'dart:convert';
import 'dart:io';
import 'dart:typed_data';
import 'package:http/http.dart' as http;
import 'package:path_provider/path_provider.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter_local_notifications/flutter_local_notifications.dart';
import 'package:flutter/foundation.dart';
import 'package:get/get.dart';
import '../screens/detail_list_screen.dart';

class NotificationService {
  static final NotificationService _instance = NotificationService._internal();
  factory NotificationService() => _instance;
  NotificationService._internal();

  final FirebaseMessaging _fcm = FirebaseMessaging.instance;
  final FlutterLocalNotificationsPlugin _localNotifications = FlutterLocalNotificationsPlugin();

  Future<void> initialize() async {
    try {
      debugPrint("Initializing Firebase Messaging...");
      
      // Request permissions (especially for iOS)
      NotificationSettings settings = await _fcm.requestPermission(
        alert: true,
        badge: true,
        sound: true,
      );

      if (settings.authorizationStatus == AuthorizationStatus.authorized) {
        debugPrint('User granted notification permissions');
      }

      // Get token for debugging
      String? token = await _fcm.getToken();
      debugPrint("FCM Token: $token");

      // Initialize local notifications for foreground display
    const AndroidInitializationSettings initializationSettingsAndroid =
        AndroidInitializationSettings('ic_launcher');
    const DarwinInitializationSettings initializationSettingsIOS = DarwinInitializationSettings();
    const InitializationSettings initializationSettings = InitializationSettings(
      android: initializationSettingsAndroid,
      iOS: initializationSettingsIOS,
    );

    await _localNotifications.initialize(
      initializationSettings,
      onDidReceiveNotificationResponse: (NotificationResponse response) {
        // Handle notification tap
        _handleNotificationTap(response.payload);
      },
    );

    // Create High Importance Channel for Android
    if (Platform.isAndroid) {
      const AndroidNotificationChannel channel = AndroidNotificationChannel(
        'high_importance_channel', // id
        'High Importance Notifications', // title
        description: 'This channel is used for important notifications.', // description
        importance: Importance.max,
      );

      final FlutterLocalNotificationsPlugin flutterLocalNotificationsPlugin =
          FlutterLocalNotificationsPlugin();

      await flutterLocalNotificationsPlugin
          .resolvePlatformSpecificImplementation<
              AndroidFlutterLocalNotificationsPlugin>()
          ?.createNotificationChannel(channel);
    }

    // Subscribe to 'all' topic (matching the backend)
    await _fcm.subscribeToTopic('all');

    // Handle foreground messages
    FirebaseMessaging.onMessage.listen((RemoteMessage message) {
      debugPrint("Foreground message received: ${message.notification?.title}");
      _showLocalNotification(message);
    });

    // Handle app opening from background/terminated state via notification
    FirebaseMessaging.onMessageOpenedApp.listen((RemoteMessage message) {
      debugPrint("App opened via notification: ${message.notification?.title}");
      debugPrint("Notification data: ${message.data}");
      _handleNotificationTap(jsonEncode(message.data));
    });
    
    // Check if app was opened from terminated state
    RemoteMessage? initialMessage = await _fcm.getInitialMessage();
    if (initialMessage != null) {
      debugPrint("App opened from terminated state with data: ${initialMessage.data}");
      _handleNotificationTap(jsonEncode(initialMessage.data));
    }
    } catch (e) {
      debugPrint("FCM Initialization Error: $e");
    }
  }

  Future<void> _showLocalNotification(RemoteMessage message) async {
    RemoteNotification? notification = message.notification;
    AndroidNotification? android = message.notification?.android;
    // Try multiple sources for image URL
    String? imageUrl = notification?.android?.imageUrl 
        ?? notification?.apple?.imageUrl
        ?? message.data['image'];

    debugPrint("Notification image URL: $imageUrl");
    debugPrint("Notification data payload: ${message.data}");

    if (notification != null) {
      StyleInformation? styleInformation;
      String? largeIconPath;
      
      if (imageUrl != null && imageUrl.isNotEmpty) {
        try {
          // Download image for BigPictureStyle
          final http.Response response = await http.get(Uri.parse(imageUrl));
          if (response.statusCode == 200 && response.bodyBytes.isNotEmpty) {
            final bigPicturePath = await _saveFile(response.bodyBytes, 'notification_img_${DateTime.now().millisecondsSinceEpoch}.jpg');
            largeIconPath = bigPicturePath;
            
            styleInformation = BigPictureStyleInformation(
              FilePathAndroidBitmap(bigPicturePath),
              largeIcon: FilePathAndroidBitmap(bigPicturePath),
              contentTitle: notification.title,
              summaryText: notification.body,
              hideExpandedLargeIcon: false,
            );
            debugPrint("BigPictureStyle notification created successfully");
          } else {
            debugPrint("Image download failed with status: ${response.statusCode}");
          }
        } catch (e) {
          debugPrint("Error downloading notification image: $e");
        }
      }

      await _localNotifications.show(
        DateTime.now().millisecondsSinceEpoch ~/ 1000,
        notification.title,
        notification.body,
        NotificationDetails(
          android: AndroidNotificationDetails(
            'high_importance_channel',
            'High Importance Notifications',
            channelDescription: 'This channel is used for important notifications.',
            importance: Importance.max,
            priority: Priority.high,
            icon: android?.smallIcon ?? 'ic_launcher',
            largeIcon: largeIconPath != null ? FilePathAndroidBitmap(largeIconPath) : null,
            styleInformation: styleInformation,
          ),
          iOS: const DarwinNotificationDetails(
            presentAlert: true,
            presentBadge: true,
            presentSound: true,
          ),
        ),
        payload: jsonEncode(message.data),
      );
    }
  }

  Future<String> _saveFile(Uint8List bytes, String fileName) async {
    final directory = await getTemporaryDirectory();
    final file = File('${directory.path}/$fileName');
    await file.writeAsBytes(bytes);
    return file.path;
  }

  void _handleNotificationTap(String? payload) {
    if (payload == null || payload.isEmpty) return;
    debugPrint("Notification Tapped with payload: $payload");
    
    try {
      Map<String, dynamic> data = _parsePayload(payload);
      debugPrint("Parsed notification data: $data");
      
      String type = data['type']?.toString() ?? '';
      int id = int.tryParse(data['id']?.toString() ?? '0') ?? 0;
      String title = data['name']?.toString() 
          ?? data['festival']?.toString() 
          ?? data['custom']?.toString() 
          ?? data['subscriptionPlan']?.toString()
          ?? 'Details';

      debugPrint("Notification tap - type: $type, id: $id, title: $title");

      if (id > 0 && type.isNotEmpty) {
        if (type == 'category' || type == 'festival' || type == 'custom' || type == 'subscriptionPlan') {
          // Small delay to ensure navigation context is ready
          Future.delayed(const Duration(milliseconds: 300), () {
            Get.to(() => DetailListScreen(
              type: type == 'subscriptionPlan' ? 'category' : type,
              id: id,
              title: title,
            ));
          });
          return;
        }
        if (type == 'externalLink') {
          // Handle external link type
          final link = data['externalLink']?.toString() ?? '';
          if (link.isNotEmpty) {
            // Navigate to external link or in-app browser
            debugPrint("Opening external link: $link");
          }
          return;
        }
      }
      
      // Fallback - go to notifications page
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

  Map<String, dynamic> _parsePayload(String payload) {
    if (payload.startsWith('{') && payload.endsWith('}')) {
      try {
        // Try JSON first (this is the primary format now)
        return Map<String, dynamic>.from(jsonDecode(payload));
      } catch (e) {
        debugPrint("JSON parse failed, trying manual parse: $e");
        // Fallback for Map.toString() format: {key: value, key2: value2}
        final content = payload.substring(1, payload.length - 1);
        final parts = content.split(', ');
        final map = <String, dynamic>{};
        for (var part in parts) {
          final colonIndex = part.indexOf(':');
          if (colonIndex > 0) {
            final key = part.substring(0, colonIndex).trim();
            final value = part.substring(colonIndex + 1).trim();
            map[key] = value;
          }
        }
        return map;
      }
    }
    return {};
  }

  Future<String?> getToken() async {
    return await _fcm.getToken();
  }
}

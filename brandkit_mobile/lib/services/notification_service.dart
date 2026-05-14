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
      _handleNotificationTap(message.data.toString());
    });
    
    // Check if app was opened from terminated state
    RemoteMessage? initialMessage = await _fcm.getInitialMessage();
    if (initialMessage != null) {
      _handleNotificationTap(initialMessage.data.toString());
    }
    } catch (e) {
      debugPrint("FCM Initialization Error: $e");
    }
  }

  Future<void> _showLocalNotification(RemoteMessage message) async {
    RemoteNotification? notification = message.notification;
    AndroidNotification? android = message.notification?.android;
    String? imageUrl = android?.imageUrl ?? message.data['image'];

    if (notification != null) {
      StyleInformation? styleInformation;
      
      if (imageUrl != null && imageUrl.isNotEmpty) {
        try {
          // Download image for BigPictureStyle
          final http.Response response = await http.get(Uri.parse(imageUrl));
          final bigPicturePath = await _saveFile(response.bodyBytes, 'notification_img.jpg');
          
          styleInformation = BigPictureStyleInformation(
            FilePathAndroidBitmap(bigPicturePath),
            largeIcon: FilePathAndroidBitmap(bigPicturePath),
            contentTitle: notification.title,
            summaryText: notification.body,
          );
        } catch (e) {
          debugPrint("Error downloading notification image: $e");
        }
      }

      await _localNotifications.show(
        notification.hashCode,
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
    if (payload == null) return;
    debugPrint("Notification Tapped with payload: $payload");
    
    try {
      // The payload is often a string representation of a map like "{type: category, id: 1}"
      // We convert it to a real Map. 
      // Note: In a real app, you might want to use jsonDecode if you passed a JSON string.
      Map<String, dynamic> data = _parsePayload(payload);
      
      String type = data['type'] ?? '';
      int id = int.tryParse(data['id']?.toString() ?? '0') ?? 0;
      String title = data['name'] ?? data['festival'] ?? data['custom'] ?? 'Details';

      if (id > 0) {
        if (type == 'category' || type == 'festival' || type == 'custom' || type == 'subscriptionPlan') {
          Get.to(() => DetailListScreen(
            type: type == 'subscriptionPlan' ? 'category' : type, // Handle mapping if needed
            id: id,
            title: title,
          ));
          return;
        }
      }
      
      // Fallback
      Get.toNamed('/notifications');
    } catch (e) {
      debugPrint("Error handling notification tap: $e");
      Get.toNamed('/notifications');
    }
  }

  Map<String, dynamic> _parsePayload(String payload) {
    // Basic parser for Map.toString() format or JSON
    if (payload.startsWith('{') && payload.endsWith('}')) {
      try {
        // Try JSON first
        return Map<String, dynamic>.from(jsonDecode(payload));
      } catch (e) {
        // Fallback for Map.toString() format: {key: value, key2: value2}
        final content = payload.substring(1, payload.length - 1);
        final parts = content.split(',');
        final map = <String, dynamic>{};
        for (var part in parts) {
          final kv = part.split(':');
          if (kv.length == 2) {
            map[kv[0].trim()] = kv[1].trim();
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

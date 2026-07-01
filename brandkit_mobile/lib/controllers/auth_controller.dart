import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../services/api_service.dart';
import '../screens/dashboard_screen.dart'; // We will create this next
import '../screens/login_screen.dart';
import '../controllers/ad_controller.dart';
import '../controllers/home_controller.dart';
import '../services/notification_service.dart';

class AuthController extends GetxController {
  var isLoading = false.obs;

  Future<void> login(String email, String password) async {
    try {
      isLoading.value = true;
      final response = await ApiService.post('/login', {
        'email': email,
        'password': password,
      });

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        // Save user session
        final prefs = await SharedPreferences.getInstance();
        await prefs.setString('userId', data['userId'].toString());
        await prefs.setString('userName', data['userName']);
        await prefs.setString('emailId', data['emailId']);
        
        // Save subscription info for Profile & Header
        await prefs.setString('planName', data['planName'] ?? '');
        await prefs.setString('planDuration', data['planDuration'] ?? '');
        await prefs.setString('planStartDate', data['planStartDate'] ?? '');
        await prefs.setString('planEndDate', data['planEndDate'] ?? '');
        await prefs.setBool('isSubscribe', data['isSubscribe'] ?? false);
        await prefs.setBool('isPartner', data['isPartner'] ?? false);
        
        // Save Gamification Stats
        await prefs.setInt('currentStreak', int.tryParse(data['currentStreak']?.toString() ?? '0') ?? 0);
        await prefs.setInt('maxStreak', int.tryParse(data['maxStreak']?.toString() ?? '0') ?? 0);
        
        if (data['adConfig'] != null) {
          try {
            Get.find<AdController>().updateAdConfig(data['adConfig']);
          } catch (e) {
            debugPrint('Failed to update adConfig: $e');
          }
        }
        
        if (Get.isRegistered<HomeController>()) {
          Get.find<HomeController>().loadBusinessInfo();
          Get.find<HomeController>().fetchHomeData();
        }
        
        await _registerFcmToken(data['userId'].toString());
        
        Get.snackbar('Success', 'Login Successful!', backgroundColor: Colors.green, colorText: Colors.white);
        
        // Navigate to Dashboard
        Get.offAll(() => const DashboardScreen());
      } else {
        try {
          final errorData = jsonDecode(response.body);
          Get.snackbar('Error', errorData['message'] ?? 'Invalid login credentials', backgroundColor: Colors.redAccent, colorText: Colors.white);
        } catch (_) {
          Get.snackbar('Error', 'Server Error (${response.statusCode}). Please check staging config.', backgroundColor: Colors.redAccent, colorText: Colors.white);
        }
      }
    } catch (e) {
      Get.snackbar('Error', 'Failed to connect to the server. $e', backgroundColor: Colors.redAccent, colorText: Colors.white);
    } finally {
      isLoading.value = false;
    }
  }

  Future<void> register({
    required String name,
    required String email,
    required String phone,
    required String password,
    String referralCode = '',
    String? businessName,
    String? businessCategoryId,
    List<String>? businessSubCategoryIds,
    List<String>? businessTypeIds,
    List<String>? productIds,
    String? businessWebsite,
    String? businessAddress,
  }) async {
    try {
      isLoading.value = true;
      final payload = {
        'name': name,
        'email': email,
        'mobile_no': phone,
        'password': password,
        'country': '91', // Defaulting to India code for now based on previous context
        'referralCode': referralCode,
      };

      if (businessName != null && businessName.isNotEmpty) {
        payload['bussinessName'] = businessName;
        if (businessCategoryId != null) payload['businessCategoryId'] = businessCategoryId;
        if (businessSubCategoryIds != null) payload['businessSubCategoryIds'] = businessSubCategoryIds.join(',');
        if (businessTypeIds != null) payload['businessTypeIds'] = businessTypeIds.join(',');
        if (productIds != null) payload['product_ids'] = productIds.join(',');
        if (businessWebsite != null) payload['bussinessWebsite'] = businessWebsite;
        if (businessAddress != null) payload['bussinessAddress'] = businessAddress;
      }

      final response = await ApiService.post('/registration', payload);

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        
        // Save user session
        final prefs = await SharedPreferences.getInstance();
        await prefs.setString('userId', data['userId'].toString());
        await prefs.setString('userName', data['userName']);
        await prefs.setString('emailId', data['emailId']);
        
        // Save subscription info for Profile & Header
        await prefs.setString('planName', data['planName'] ?? '');
        await prefs.setString('planDuration', data['planDuration'] ?? '');
        await prefs.setString('planStartDate', data['planStartDate'] ?? '');
        await prefs.setString('planEndDate', data['planEndDate'] ?? '');
        await prefs.setBool('isSubscribe', data['isSubscribe'] ?? false);
        await prefs.setBool('isPartner', data['isPartner'] ?? false);
        
        // Save Gamification Stats
        await prefs.setInt('currentStreak', int.tryParse(data['currentStreak']?.toString() ?? '0') ?? 0);
        await prefs.setInt('maxStreak', int.tryParse(data['maxStreak']?.toString() ?? '0') ?? 0);
        
        if (data['adConfig'] != null) {
          try {
            Get.find<AdController>().updateAdConfig(data['adConfig']);
          } catch (e) {
            debugPrint('Failed to update adConfig: $e');
          }
        }
        
        if (Get.isRegistered<HomeController>()) {
          Get.find<HomeController>().loadBusinessInfo();
          Get.find<HomeController>().fetchHomeData();
        }

        await _registerFcmToken(data['userId'].toString());

        Get.snackbar('Success', 'Registration successful! Welcome aboard.', backgroundColor: Colors.green, colorText: Colors.white);
        
        // Auto-login and navigate to Dashboard
        Get.offAll(() => const DashboardScreen());
      } else {
        try {
          final errorData = jsonDecode(response.body);
          Get.snackbar('Error', errorData['message'].toString(), backgroundColor: Colors.redAccent, colorText: Colors.white);
        } catch (_) {
          Get.snackbar('Error', 'Server Error (${response.statusCode}). Please check staging config.', backgroundColor: Colors.redAccent, colorText: Colors.white);
        }
      }
    } catch (e) {
      Get.snackbar('Error', 'Failed to register. $e', backgroundColor: Colors.redAccent, colorText: Colors.white);
    } finally {
      isLoading.value = false;
    }
  }

  Future<void> logout() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.clear();
    if (Get.isRegistered<HomeController>()) {
      Get.find<HomeController>().clearData();
    }
    Get.offAll(() => const LoginScreen());
  }

  Future<void> forgotPassword(String email) async {
    try {
      isLoading.value = true;
      final response = await ApiService.post('/forgot-password', {
        'email': email,
      });

      if (response.statusCode == 200) {
        Get.snackbar('Success', 'Password reset email sent!', backgroundColor: Colors.green, colorText: Colors.white);
        Get.back();
      } else {
        try {
          final errorData = jsonDecode(response.body);
          Get.snackbar('Error', errorData['message'].toString(), backgroundColor: Colors.redAccent, colorText: Colors.white);
        } catch (_) {
          Get.snackbar('Error', 'Server Error (${response.statusCode}).', backgroundColor: Colors.redAccent, colorText: Colors.white);
        }
      }
    } catch (e) {
      Get.snackbar('Error', 'Failed to send reset email. $e', backgroundColor: Colors.redAccent, colorText: Colors.white);
    } finally {
      isLoading.value = false;
    }
  }

  Future<void> _registerFcmToken(String userId) async {
    try {
      final token = await NotificationService().getToken();
      if (token != null) {
        debugPrint("Registering FCM Token: $token");
        await ApiService.post('/register-fcm', {
          'userId': userId,
          'fcmToken': token,
          'deviceId': 'flutter_device', // Since deviceId isn't explicitly extracted in this app, we'll send a fallback
        });
      }
    } catch (e) {
      debugPrint("Failed to register FCM Token: $e");
    }
  }
}

import 'dart:io';
import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../services/api_service.dart';
import '../screens/dashboard_screen.dart'; // We will create this next
import '../screens/login_screen.dart';
import '../screens/business_profile_screen.dart';
import '../widgets/guest_login_prompt_sheet.dart';
import '../widgets/create_business_prompt_sheet.dart';
import '../controllers/ad_controller.dart';
import '../controllers/home_controller.dart';
import '../services/notification_service.dart';

class AuthController extends GetxController {
  var isLoading = false.obs;

  Future<void> login(String email, String password, {String? redirectRoute, dynamic redirectArguments}) async {
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
        await prefs.setBool('isGuest', false);
        await prefs.setString('userName', data['userName']?.toString() ?? '');
        await prefs.setString('emailId', data['emailId']?.toString() ?? '');
        await prefs.setString('phoneNumber', data['phoneNumber']?.toString() ?? '');
        await prefs.setString('profileImage', data['profileImage']?.toString() ?? '');
        
        // Save subscription info for Profile & Header
        await prefs.setString('planName', data['planName']?.toString() ?? '');
        await prefs.setString('planDuration', data['planDuration']?.toString() ?? '');
        await prefs.setString('planStartDate', data['planStartDate']?.toString() ?? '');
        await prefs.setString('planEndDate', data['planEndDate']?.toString() ?? '');
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
        
        _registerFcmToken(data['userId'].toString());
        
        Get.snackbar('Success', 'Login Successful!', backgroundColor: Colors.green, colorText: Colors.white);
        
        // Navigate to Dashboard or Redirect Route
        if (redirectRoute != null) {
          Get.offAll(() => const DashboardScreen());
          Get.toNamed(redirectRoute, arguments: redirectArguments);
        } else {
          Get.offAll(() => const DashboardScreen());
        }
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
    File? profileImage,
    String? redirectRoute,
    dynamic redirectArguments,
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

      var response;
      if (profileImage != null) {
        response = await ApiService.multipartPost(
          '/registration',
          payload,
          fileKey: 'image',
          filePath: profileImage.path,
        );
      } else {
        response = await ApiService.post('/registration', payload);
      }

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        
        // Save user session
        final prefs = await SharedPreferences.getInstance();
        await prefs.setString('userId', data['userId'].toString());
        await prefs.setBool('isGuest', false);
        await prefs.setString('userName', data['userName']?.toString() ?? '');
        await prefs.setString('emailId', data['emailId']?.toString() ?? '');
        await prefs.setString('phoneNumber', data['phoneNumber']?.toString() ?? '');
        await prefs.setString('profileImage', data['profileImage']?.toString() ?? '');
        
        // Save subscription info for Profile & Header
        await prefs.setString('planName', data['planName']?.toString() ?? '');
        await prefs.setString('planDuration', data['planDuration']?.toString() ?? '');
        await prefs.setString('planStartDate', data['planStartDate']?.toString() ?? '');
        await prefs.setString('planEndDate', data['planEndDate']?.toString() ?? '');
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

        _registerFcmToken(data['userId'].toString());

        Get.snackbar('Success', 'Registration successful! Welcome aboard.', backgroundColor: Colors.green, colorText: Colors.white);
        
        // Auto-login and navigate to Dashboard or Redirect Route
        if (redirectRoute != null) {
          Get.offAll(() => const DashboardScreen());
          Get.toNamed(redirectRoute, arguments: redirectArguments);
        } else {
          Get.offAll(() => const DashboardScreen());
        }
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

  Future<bool> forgotPassword(String email) async {
    try {
      isLoading.value = true;
      final response = await ApiService.post('/forgot-password', {
        'email': email,
      });

      if (response.statusCode == 200) {
        Get.snackbar('Success', 'OTP sent to your email!', backgroundColor: Colors.green, colorText: Colors.white);
        return true;
      } else {
        try {
          final errorData = jsonDecode(response.body);
          Get.snackbar('Error', errorData['message'].toString(), backgroundColor: Colors.redAccent, colorText: Colors.white);
        } catch (_) {
          Get.snackbar('Error', 'Server Error (${response.statusCode}).', backgroundColor: Colors.redAccent, colorText: Colors.white);
        }
        return false;
      }
    } catch (e) {
      Get.snackbar('Error', 'Failed to send OTP. $e', backgroundColor: Colors.redAccent, colorText: Colors.white);
      return false;
    } finally {
      isLoading.value = false;
    }
  }

  Future<bool> verifyOtp(String email, String otp) async {
    try {
      isLoading.value = true;
      final response = await ApiService.post('/forgot-password/verify-otp', {
        'email': email,
        'otp': otp,
      });

      if (response.statusCode == 200) {
        return true;
      } else {
        try {
          final errorData = jsonDecode(response.body);
          Get.snackbar('Error', errorData['message'].toString(), backgroundColor: Colors.redAccent, colorText: Colors.white);
        } catch (_) {
          Get.snackbar('Error', 'Wrong Code or Server Error.', backgroundColor: Colors.redAccent, colorText: Colors.white);
        }
        return false;
      }
    } catch (e) {
      Get.snackbar('Error', 'Verification failed. $e', backgroundColor: Colors.redAccent, colorText: Colors.white);
      return false;
    } finally {
      isLoading.value = false;
    }
  }

  Future<bool> updatePassword(String email, String otp, String newPassword) async {
    try {
      isLoading.value = true;
      final response = await ApiService.post('/forgot-password/update', {
        'email': email,
        'otp': otp,
        'new_password': newPassword,
      });

      if (response.statusCode == 200) {
        Get.snackbar('Success', 'Password updated successfully!', backgroundColor: Colors.green, colorText: Colors.white);
        return true;
      } else {
        try {
          final errorData = jsonDecode(response.body);
          Get.snackbar('Error', errorData['message'].toString(), backgroundColor: Colors.redAccent, colorText: Colors.white);
        } catch (_) {
          Get.snackbar('Error', 'Failed to update password.', backgroundColor: Colors.redAccent, colorText: Colors.white);
        }
        return false;
      }
    } catch (e) {
      Get.snackbar('Error', 'Update failed. $e', backgroundColor: Colors.redAccent, colorText: Colors.white);
      return false;
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

  Future<void> checkAndNavigateToEditor(String route, {dynamic arguments}) async {
    final prefs = await SharedPreferences.getInstance();
    final isGuest = prefs.getBool('isGuest') ?? false;
    final userId = prefs.getString('userId');
    
    if (userId == null || userId.isEmpty || isGuest) {
      Get.bottomSheet(
        GuestLoginPromptSheet(
          redirectRoute: route,
          redirectArguments: arguments,
        ),
        isScrollControlled: true,
        backgroundColor: Colors.transparent,
      );
    } else {
      // Check if business profile is valid (has at least a name, and either phone or email)
      bool hasValidBusiness = true;
      if (Get.isRegistered<HomeController>()) {
        final hc = Get.find<HomeController>();
        if (hc.businessId.value.isEmpty || (hc.businessPhone.value.isEmpty && hc.businessEmail.value.isEmpty)) {
          hasValidBusiness = false;
        }
      } else {
        final cachedBizId = prefs.getString('cached_biz_id') ?? '';
        final cachedBizPhone = prefs.getString('cached_biz_phone') ?? '';
        final cachedBizEmail = prefs.getString('cached_biz_email') ?? '';
        if (cachedBizId.isEmpty || (cachedBizPhone.isEmpty && cachedBizEmail.isEmpty)) {
          hasValidBusiness = false;
        }
      }

      if (!hasValidBusiness) {
        Get.bottomSheet(
          CreateBusinessPromptSheet(
            redirectRoute: route,
            redirectArguments: arguments,
          ),
          isScrollControlled: true,
          backgroundColor: Colors.transparent,
        );
        return;
      }

      Get.toNamed(route, arguments: arguments);
    }
  }
}

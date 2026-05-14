import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'dart:convert';
import '../services/api_service.dart';
import '../models/ad_config.dart';
import 'ad_controller.dart';

/// Central GetX controller for subscription state management.
/// Provides plan info, feature usage data, and helper methods
/// for the Profile card, Header badge, and Plans screen.
class SubscriptionController extends GetxController {
  // ── Plan Info ──
  final planName = ''.obs;
  final planDuration = ''.obs;
  final planStartDate = ''.obs;
  final planEndDate = ''.obs;
  final isSubscribe = false.obs;
  final isLoading = false.obs;
  final businessLimit = 1.obs;

  // ── Computed ──
  int get daysRemaining {
    if (planEndDate.value.isEmpty) return 0;
    try {
      final end = DateTime.parse(planEndDate.value);
      final diff = end.difference(DateTime.now()).inDays;
      return diff > 0 ? diff : 0;
    } catch (_) {
      return 0;
    }
  }

  bool get isExpiringSoon => daysRemaining > 0 && daysRemaining <= 3;
  bool get isExpired => isSubscribe.value && daysRemaining == 0;
  bool get hasActivePlan => isSubscribe.value && daysRemaining > 0;

  @override
  void onInit() {
    super.onInit();
    loadFromPrefs();
  }

  /// Load subscription info from SharedPreferences (saved on login)
  Future<void> loadFromPrefs() async {
    final prefs = await SharedPreferences.getInstance();
    planName.value = prefs.getString('planName') ?? '';
    planDuration.value = prefs.getString('planDuration') ?? '';
    planStartDate.value = prefs.getString('planStartDate') ?? '';
    planEndDate.value = prefs.getString('planEndDate') ?? '';
    isSubscribe.value = prefs.getBool('isSubscribe') ?? false;
    businessLimit.value = prefs.getInt('businessLimit') ?? 1;
  }

  /// Refresh subscription data from backend /user API
  Future<void> refreshFromApi() async {
    final prefs = await SharedPreferences.getInstance();
    final userId = prefs.getString('userId') ?? '';
    if (userId.isEmpty) return;

    isLoading.value = true;
    try {
      final response = await ApiService.post('/user_data', {'id': userId});
      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        planName.value = data['planName'] ?? '';
        planDuration.value = data['planDuration'] ?? '';
        planStartDate.value = data['planStartDate'] ?? '';
        planEndDate.value = data['planEndDate'] ?? '';
        isSubscribe.value = data['isSubscribe'] ?? false;
        businessLimit.value = data['businessLimit'] ?? 1;
        bool isPartner = data['isPartner'] ?? false;

        // Persist locally
        await prefs.setString('planName', planName.value);
        await prefs.setString('planDuration', planDuration.value);
        await prefs.setString('planStartDate', planStartDate.value);
        await prefs.setString('planEndDate', planEndDate.value);
        await prefs.setBool('isSubscribe', isSubscribe.value);
        await prefs.setInt('businessLimit', businessLimit.value);
        await prefs.setBool('isPartner', isPartner);

        // Update ad config too if present
        if (data['adConfig'] != null) {
          try {
            Get.find<AdController>().updateAdConfig(data['adConfig']);
          } catch (_) {}
        }
      }
    } catch (e) {
      debugPrint('SubscriptionController.refreshFromApi error: $e');
    } finally {
      isLoading.value = false;
    }
  }

  /// Get structured feature usage list for UI rendering
  /// Reads from AdController's adConfig which has base_limit, used, etc.
  List<FeatureUsageInfo> getFeatureUsageList() {
    final adController = Get.find<AdController>();
    final config = adController.adConfig.value;
    if (config == null) return [];

    final features = <FeatureUsageInfo>[];
    final featureMap = {
      'custom_post': {'name': 'Custom Posts', 'icon': Icons.edit_outlined},
      'festival_post': {'name': 'Festival Posts', 'icon': Icons.celebration_outlined},
      'business_category_post': {'name': 'Category Posts', 'icon': Icons.category_outlined},
      'magic_cloner': {'name': 'Magic Cloner', 'icon': Icons.auto_awesome_outlined},
      'daily_drip': {'name': 'Daily Drip', 'icon': Icons.water_drop_outlined},
    };

    featureMap.forEach((key, meta) {
      final fc = config.features[key];
      if (fc != null) {
        final used = fc.used;
        final limit = fc.baseLimit;
        final percentage = limit > 0 ? (used / limit).clamp(0.0, 1.0) : 0.0;
        final adRemaining = (fc.maxAdUses - fc.adUsed).clamp(0, fc.maxAdUses);

        features.add(FeatureUsageInfo(
          key: key,
          name: meta['name'] as String,
          icon: meta['icon'] as IconData,
          used: used,
          limit: limit,
          percentage: percentage,
          color: percentage < 0.6
              ? const Color(0xFF10B981) // Green
              : percentage < 0.85
                  ? const Color(0xFFF59E0B) // Orange
                  : const Color(0xFFEF4444), // Red
          adRewardsRemaining: adRemaining,
          state: fc.state,
        ));
      }
    });

    return features;
  }
}

/// Data class for feature usage info
class FeatureUsageInfo {
  final String key;
  final String name;
  final IconData icon;
  final int used;
  final int limit;
  final double percentage;
  final Color color;
  final int adRewardsRemaining;
  final String state;

  FeatureUsageInfo({
    required this.key,
    required this.name,
    required this.icon,
    required this.used,
    required this.limit,
    required this.percentage,
    required this.color,
    required this.adRewardsRemaining,
    required this.state,
  });

  bool get isLocked => state == 'locked';
  bool get hasAdRewards => adRewardsRemaining > 0;
}

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
class SubscriptionController extends GetxController with WidgetsBindingObserver {
  // ── Plan Info ──
  final planName = ''.obs;
  final planDuration = ''.obs;
  final planStartDate = ''.obs;
  final planEndDate = ''.obs;
  final isSubscribe = false.obs;
  final isLoading = false.obs;
  final businessLimit = 1.obs;
  final rewardPoints = 0.obs;

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
  bool get isFreePlan => planName.value.toLowerCase().contains('starting') || planName.value.toLowerCase().contains('free');
  bool get hasPaidActivePlan => hasActivePlan && !isFreePlan;

  @override
  void onInit() {
    super.onInit();
    WidgetsBinding.instance.addObserver(this);
    loadFromPrefs();
  }

  @override
  void onClose() {
    WidgetsBinding.instance.removeObserver(this);
    super.onClose();
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.resumed) {
      _refreshSilently();
    }
  }

  Future<void> _refreshSilently() async {
    try {
      await refreshFromApi();
    } catch (e) {
      debugPrint('Silent subscription refresh failed: $e');
    }
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
    rewardPoints.value = prefs.getInt('rewardPoints') ?? 0;
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
        rewardPoints.value = data['rewardPoints'] ?? 0;
        bool isPartner = data['isPartner'] ?? false;

        // Persist locally
        await prefs.setString('planName', planName.value);
        await prefs.setString('planDuration', planDuration.value);
        await prefs.setString('planStartDate', planStartDate.value);
        await prefs.setString('planEndDate', planEndDate.value);
        await prefs.setBool('isSubscribe', isSubscribe.value);
        await prefs.setInt('businessLimit', businessLimit.value);
        await prefs.setInt('rewardPoints', rewardPoints.value);
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
      'custom_post': {'name': 'PRO Custom Posts', 'icon': Icons.edit_outlined},
      'festival_post': {'name': 'PRO Festival Posts', 'icon': Icons.celebration_outlined},
      'category_post': {'name': 'PRO Category Posts', 'icon': Icons.category_outlined},

    };

    featureMap.forEach((key, meta) {
      final fc = config.features[key];
      if (fc != null) {
        final used = fc.used;
        final limit = fc.baseLimit;
        final percentage = limit > 0 ? (used / limit).clamp(0.0, 1.0) : (fc.maxAdUses > 0 ? (fc.adUsed / fc.maxAdUses).clamp(0.0, 1.0) : 0.0);
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
          adUsed: fc.adUsed,
          maxAdUses: fc.maxAdUses,
        ));
      }
    });

    return features;
  }

  /// Use 1 Reward Credit to unlock a feature bypass
  Future<bool> useRewardCredit(String featureKey) async {
    if (rewardPoints.value < 1) return false;
    
    final prefs = await SharedPreferences.getInstance();
    final userId = prefs.getString('userId') ?? '';
    
    try {
      final response = await ApiService.post('/use-reward-credit', {
        'user_id': userId,
        'feature_key': featureKey
      });
      
      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['status'] == 'success') {
          rewardPoints.value = data['rewardPoints'] ?? (rewardPoints.value - 1);
          await prefs.setInt('rewardPoints', rewardPoints.value);
          return true;
        }
      }
    } catch (e) {
      debugPrint('Error using reward credit: $e');
    }
    return false;
  }

  /// Get the upgrade preview details from the backend
  Future<Map<String, dynamic>?> getUpgradePreview(String newPlanId) async {
    final prefs = await SharedPreferences.getInstance();
    final userId = prefs.getString('userId') ?? '';
    if (userId.isEmpty) return null;

    try {
      final response = await ApiService.get(
        '/subscription-upgrade-preview?userId=$userId&newPlanId=$newPlanId',
      );
      if (response.statusCode == 200) {
        return jsonDecode(response.body);
      }
    } catch (e) {
      debugPrint('Error getting upgrade preview: $e');
    }
    return null;
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
  final int adUsed;
  final int maxAdUses;

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
    required this.adUsed,
    required this.maxAdUses,
  });

  bool get isLocked => state == 'locked';
  bool get hasAdRewards => adRewardsRemaining > 0;
}

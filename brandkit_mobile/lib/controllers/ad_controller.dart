import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:flutter/foundation.dart';
import 'package:get/get.dart';
import 'package:google_mobile_ads/google_mobile_ads.dart';
import '../services/ad_service.dart';
import '../models/ad_config.dart';
import '../utils/app_spacing.dart';
import '../widgets/limit_reached_sheet.dart';
import '../controllers/subscription_controller.dart';
import '../screens/subscription_plans_screen.dart';

/// GetX Controller for managing reactive ad state across the app.
/// Handles banner ads per tab, interstitial triggers on download,
/// and rewarded ad flow for premium features based on subscription limits.
class AdController extends GetxController {
  final AdService _adService = AdService();

  // ── Ad Config from API ──
  final Rx<AdConfig?> adConfig = Rx<AdConfig?>(null);

  // ── Banner Ad State (one per tab that needs it) ──
  final Rx<BannerAd?> homeBannerAd = Rx<BannerAd?>(null);
  final Rx<BannerAd?> customBannerAd = Rx<BannerAd?>(null);
  final Rx<BannerAd?> aiTrendsBannerAd = Rx<BannerAd?>(null);
  final Rx<BannerAd?> moreBannerAd = Rx<BannerAd?>(null);

  final RxBool isHomeBannerLoaded = false.obs;
  final RxBool isCustomBannerLoaded = false.obs;
  final RxBool isAiTrendsBannerLoaded = false.obs;
  final RxBool isMoreBannerLoaded = false.obs;

  // ── Rewarded Ad State ──
  final RxBool isRewardedReady = false.obs;

  @override
  void onInit() {
    super.onInit();
    // Periodically check rewarded ad readiness
    ever(isRewardedReady, (_) {});
    
    // Listen for ad config changes
    ever(adConfig, (config) {
      if (config != null) {
        if (config.showGlobalAds) {
          _loadAllBanners();
        } else {
          _disposeAllBanners();
        }
      }
    });
  }

  /// Update the ad config when user logs in or fetches profile
  void updateAdConfig(Map<String, dynamic>? configData) {
    if (configData != null) {
      final newConfig = AdConfig.fromJson(configData);
      if (newConfig.admob != null) {
        AdService.updateIds(
          bannerId: newConfig.admob!.bannerAdsId,
          interstitialId: newConfig.admob!.interstitialAdsId,
          rewardedId: newConfig.admob!.rewardedAdsId,
          nativeId: newConfig.admob!.nativeAdsId,
        );
      }
      adConfig.value = newConfig;
    }
  }

  /// Check if global banners should be shown based on ad config
  bool get showGlobalBanners => adConfig.value?.showGlobalAds ?? false;

  /// Load banner ads for all tabs if allowed by config
  void _loadAllBanners() {
    if (kIsWeb) return;
    if (!showGlobalBanners) return;

    // Home Tab Banner
    if (homeBannerAd.value == null) {
      homeBannerAd.value = _adService.createBannerAd(
        onAdLoaded: (_) => isHomeBannerLoaded.value = true,
        onAdFailedToLoad: (_, __) => isHomeBannerLoaded.value = false,
      )..load();
    }

    // Custom Tab Banner
    if (customBannerAd.value == null) {
      customBannerAd.value = _adService.createBannerAd(
        onAdLoaded: (_) => isCustomBannerLoaded.value = true,
        onAdFailedToLoad: (_, __) => isCustomBannerLoaded.value = false,
      )..load();
    }

    // AI Trends Tab Banner
    if (aiTrendsBannerAd.value == null) {
      aiTrendsBannerAd.value = _adService.createBannerAd(
        onAdLoaded: (_) => isAiTrendsBannerLoaded.value = true,
        onAdFailedToLoad: (_, __) => isAiTrendsBannerLoaded.value = false,
      )..load();
    }

    // More Tab Banner
    if (moreBannerAd.value == null) {
      moreBannerAd.value = _adService.createBannerAd(
        onAdLoaded: (_) => isMoreBannerLoaded.value = true,
        onAdFailedToLoad: (_, __) => isMoreBannerLoaded.value = false,
      )..load();
    }
  }

  /// Dispose all banners when subscription upgrades
  void _disposeAllBanners() {
    homeBannerAd.value?.dispose();
    customBannerAd.value?.dispose();
    aiTrendsBannerAd.value?.dispose();
    moreBannerAd.value?.dispose();
    
    homeBannerAd.value = null;
    customBannerAd.value = null;
    aiTrendsBannerAd.value = null;
    moreBannerAd.value = null;

    isHomeBannerLoaded.value = false;
    isCustomBannerLoaded.value = false;
    isAiTrendsBannerLoaded.value = false;
    isMoreBannerLoaded.value = false;
  }

  /// Get the feature ad state from the config
  FeatureAdConfig? _getFeatureConfig(String feature) {
    return adConfig.value?.features[feature];
  }

  /// Handle feature access logic for standard features (custom post, daily drip, etc.)
  Future<bool> handleFeatureAccess({
    required BuildContext context,
    required String feature,
    required VoidCallback onAccessGranted,
  }) async {
    final prefs = await SharedPreferences.getInstance();
    final isGuest = prefs.getBool('isGuest') ?? false;

    // Guests should bypass limit checks and hit the auth interceptor
    if (isGuest) {
      onAccessGranted();
      return true;
    }

    final config = _getFeatureConfig(feature);
    
    if (config == null || config.isLocked) {
      _showLockedDialog(context, feature);
      return false;
    }

    if (config.isNoAds) {
      onAccessGranted();
      return true;
    }

    // For all_ads or rewarded_interstitial on standard features, 
    // we use a rewarded ad if available, fallback to interstitial
    if (_adService.isRewardedReady) {
      bool rewardEarned = false;
      _adService.showRewardedAd(
        onRewarded: (reward) {
          rewardEarned = true;
          onAccessGranted();
        },
      );
      // Fallback if ad is dismissed without earning (handled externally usually)
      return true;
    } else {
      // Fallback to interstitial if rewarded is not ready
      _adService.showInterstitialAd();
      onAccessGranted();
      return true;
    }
  }

  Future<bool> handlePostAccess({
    required BuildContext context,
    required String feature, // 'festival_post' or 'category_post'
    required bool isPaid,
    required VoidCallback onAccessGranted,
  }) async {
    final config = _getFeatureConfig(feature);
    
    final prefs = await SharedPreferences.getInstance();
    final isGuest = prefs.getBool('isGuest') ?? false;

    // Guests should bypass limit checks and hit the auth interceptor
    if (isGuest) {
      onAccessGranted();
      return true;
    }

    // --- FREE TEMPLATE RULES ---
    if (!isPaid) {
      // Free templates do not count towards usage limits, so they are never locked at access.
      onAccessGranted();
      return true;
    }

    // --- PAID (PRO) TEMPLATE RULES ---
    if (config == null) {
      _showLockedDialog(context, feature);
      return false;
    }

    // Check if the user has truly exhausted both base limit AND ad limits
    bool isTrulyLocked = (config.used >= config.baseLimit) && (config.adUsed >= config.maxAdUses);
    
    if (isTrulyLocked) {
      _showLockedDialog(context, feature);
      return false;
    }

    // If within base limit, grant access immediately with no ads
    if (config.used < config.baseLimit) {
      onAccessGranted();
      return true;
    }

    // Base limit reached, but ad limit is available.
    final flow = config.postAdFlowPaid ?? 'rewarded_then_interstitial';

    if (flow == 'no_ads') {
      onAccessGranted();
      return true;
    } else {
      // Show rewarded ad if available
      if (_adService.isRewardedReady) {
        _adService.showRewardedAd(
          onRewarded: (reward) {
            onAccessGranted();
          },
        );
      } else {
        // Fallback to interstitial if rewarded not ready
        _adService.showInterstitialAd();
        onAccessGranted();
      }
      return true;
    }
  }

  /// Show an Interstitial Ad during download for templates
  ///
  /// Returns true if the download should proceed.
  bool handlePremiumDownloadAd({
    required String feature,
    required bool isPaid,
  }) {
    final config = _getFeatureConfig(feature);
    if (config == null) return true;

    // --- FREE TEMPLATE RULES ---
    if (!isPaid) {
      if (config.baseLimit > 0) {
        // If package base limit > 0, NO ads for free templates
        return true; 
      } else {
        // If package base limit == 0, show ad at download time
        _adService.showInterstitialAd();
        return true;
      }
    }

    // --- PAID (PRO) TEMPLATE RULES ---
    if (config.used < config.baseLimit) {
      return true; // Within base limit, no ads needed
    }

    // Base limit reached -> show interstitial on download for paid templates
    _adService.showInterstitialAd();
    
    return true;
  }

  /// Show premium bottom sheet informing the user they have reached their limit.
  /// Provides "Watch Ad" option (if available) and "Upgrade" button.
  void _showLockedDialog(BuildContext context, String feature) {
    final sc = Get.find<SubscriptionController>();
    final usageList = sc.getFeatureUsageList();
    final featureInfo = usageList.where((f) => f.key == feature).firstOrNull;

    if (featureInfo != null) {
      LimitReachedSheet.show(
        context: context,
        feature: featureInfo,
        onWatchAd: featureInfo.hasAdRewards
            ? () {
                // Trigger existing rewarded ad flow
                _showRewardedAd(context, feature);
              }
            : null,
        onUpgrade: () {
          Navigator.push(
            context,
            MaterialPageRoute(builder: (_) => const SubscriptionPlansScreen()),
          );
        },
      );
    } else {
      // Fallback for unknown features
      String featureName = feature.replaceAll('_', ' ').capitalizeFirst ?? 'Feature';
      LimitReachedSheet.show(
        context: context,
        feature: FeatureUsageInfo(
          key: feature,
          name: featureName,
          icon: Icons.lock_outline,
          used: 0,
          limit: 0,
          percentage: 1.0,
          color: const Color(0xFFEF4444),
          adRewardsRemaining: 0,
          state: 'locked',
          adUsed: 0,
          maxAdUses: 0,
        ),
        onUpgrade: () {
          Navigator.push(
            context,
            MaterialPageRoute(builder: (_) => const SubscriptionPlansScreen()),
          );
        },
      );
    }
  }

  /// Show rewarded ad for the feature and grant +1 use on completion
  void _showRewardedAd(BuildContext context, String feature) {
    AdService().showRewardedAd(
      onRewarded: (reward) {
        // The consumeFeature API with ad_rewarded flag will be called
        // by the existing feature-specific handlers
        Get.snackbar(
          'Unlocked!',
          '+1 ${feature.replaceAll('_', ' ')} unlocked via ad reward',
          snackPosition: SnackPosition.BOTTOM,
          backgroundColor: const Color(0xFF10B981),
          colorText: Colors.white,
          margin: const EdgeInsets.all(16),
        );
      },
    );
  }

  /// Get the appropriate BannerAd widget for a specific tab index.
  /// Tab indices: 0=Home, 1=Custom, 3=AI Trends, 4=More
  /// Tab index 2 (My Business) does NOT get a banner.
  Widget? getBannerWidget(int tabIndex) {
    if (!showGlobalBanners) return null;

    BannerAd? ad;
    bool isLoaded = false;

    switch (tabIndex) {
      case 0:
        ad = homeBannerAd.value;
        isLoaded = isHomeBannerLoaded.value;
        break;
      case 1:
        ad = customBannerAd.value;
        isLoaded = isCustomBannerLoaded.value;
        break;
      case 3:
        ad = aiTrendsBannerAd.value;
        isLoaded = isAiTrendsBannerLoaded.value;
        break;
      case 4:
        ad = moreBannerAd.value;
        isLoaded = isMoreBannerLoaded.value;
        break;
      default:
        return null; // No banner for My Business (index 2)
    }

    if (ad != null && isLoaded) {
      return Container(
        width: ad.size.width.toDouble(),
        height: ad.size.height.toDouble(),
        alignment: Alignment.center,
        color: Colors.transparent,
        child: AdWidget(ad: ad),
      );
    }
    return null;
  }

  @override
  void onClose() {
    _disposeAllBanners();
    super.onClose();
  }
}

import 'dart:io';
import 'package:flutter/foundation.dart';
import 'package:google_mobile_ads/google_mobile_ads.dart';

/// Central AdMob Service — Handles initialization, loading, caching, and
/// displaying all 4 ad formats: Banner, Interstitial, Rewarded, Native.
///
/// Uses TEST ad unit IDs by default. Replace with production IDs from your
/// AdMob dashboard before release.
class AdService {
  // ── Singleton ──
  static final AdService _instance = AdService._internal();
  factory AdService() => _instance;
  AdService._internal();

  // ── TEST Ad Unit IDs (Replace with production IDs before release) ──
  static String get bannerAdUnitId {
    if (kIsWeb) return '';
    if (Platform.isAndroid) {
      return 'ca-app-pub-3940256099942544/6300978111'; // Android Test Banner
    } else if (Platform.isIOS) {
      return 'ca-app-pub-3940256099942544/2934735716'; // iOS Test Banner
    }
    return '';
  }

  static String get interstitialAdUnitId {
    if (kIsWeb) return '';
    if (Platform.isAndroid) {
      return 'ca-app-pub-3940256099942544/1033173712'; // Android Test Interstitial
    } else if (Platform.isIOS) {
      return 'ca-app-pub-3940256099942544/4411468910'; // iOS Test Interstitial
    }
    return '';
  }

  static String get rewardedAdUnitId {
    if (kIsWeb) return '';
    if (Platform.isAndroid) {
      return 'ca-app-pub-3940256099942544/5224354917'; // Android Test Rewarded
    } else if (Platform.isIOS) {
      return 'ca-app-pub-3940256099942544/1712485313'; // iOS Test Rewarded
    }
    return '';
  }

  static String get nativeAdUnitId {
    if (kIsWeb) return '';
    if (Platform.isAndroid) {
      return 'ca-app-pub-3940256099942544/2247696110'; // Android Test Native
    } else if (Platform.isIOS) {
      return 'ca-app-pub-3940256099942544/3986624511'; // iOS Test Native
    }
    return '';
  }

  // ── State ──
  InterstitialAd? _interstitialAd;
  RewardedAd? _rewardedAd;
  bool _isInterstitialReady = false;
  bool _isRewardedReady = false;
  DateTime? _lastInterstitialShown;

  /// Frequency cap: minimum 10 minutes between interstitial ads.
  static const int interstitialCooldownMinutes = 10;

  // ── Initialize the SDK ──
  Future<void> initialize() async {
    await MobileAds.instance.initialize();
    debugPrint('[AdService] MobileAds SDK initialized.');
    // Pre-load ads immediately so they're ready when needed
    loadInterstitialAd();
    loadRewardedAd();
  }

  // ─────────────────── BANNER ADS ───────────────────
  /// Creates a BannerAd. The caller is responsible for calling `load()`.
  BannerAd createBannerAd({
    AdSize size = AdSize.banner,
    Function(Ad)? onAdLoaded,
    Function(Ad, LoadAdError)? onAdFailedToLoad,
  }) {
    return BannerAd(
      adUnitId: bannerAdUnitId,
      size: size,
      request: const AdRequest(),
      listener: BannerAdListener(
        onAdLoaded: (ad) {
          debugPrint('[AdService] Banner ad loaded.');
          onAdLoaded?.call(ad);
        },
        onAdFailedToLoad: (ad, error) {
          debugPrint('[AdService] Banner ad failed to load: $error');
          ad.dispose();
          onAdFailedToLoad?.call(ad, error);
        },
      ),
    );
  }

  // ─────────────────── INTERSTITIAL ADS ───────────────────
  void loadInterstitialAd() {
    InterstitialAd.load(
      adUnitId: interstitialAdUnitId,
      request: const AdRequest(),
      adLoadCallback: InterstitialAdLoadCallback(
        onAdLoaded: (ad) {
          _interstitialAd = ad;
          _isInterstitialReady = true;
          debugPrint('[AdService] Interstitial ad loaded & cached.');

          ad.fullScreenContentCallback = FullScreenContentCallback(
            onAdDismissedFullScreenContent: (ad) {
              ad.dispose();
              _isInterstitialReady = false;
              _interstitialAd = null;
              // Immediately pre-load the next one
              loadInterstitialAd();
            },
            onAdFailedToShowFullScreenContent: (ad, error) {
              debugPrint('[AdService] Interstitial failed to show: $error');
              ad.dispose();
              _isInterstitialReady = false;
              _interstitialAd = null;
              loadInterstitialAd();
            },
          );
        },
        onAdFailedToLoad: (error) {
          debugPrint('[AdService] Interstitial failed to load: $error');
          _isInterstitialReady = false;
        },
      ),
    );
  }

  /// Shows a cached interstitial ad, respecting the frequency cap.
  /// Returns true if the ad was shown, false otherwise.
  bool showInterstitialAd() {
    // Frequency cap check
    if (_lastInterstitialShown != null) {
      final diff = DateTime.now().difference(_lastInterstitialShown!).inMinutes;
      if (diff < interstitialCooldownMinutes) {
        debugPrint('[AdService] Interstitial skipped (cooldown: ${interstitialCooldownMinutes - diff} min left).');
        return false;
      }
    }

    if (_isInterstitialReady && _interstitialAd != null) {
      _lastInterstitialShown = DateTime.now();
      _interstitialAd!.show();
      return true;
    } else {
      debugPrint('[AdService] Interstitial not ready yet. Loading...');
      loadInterstitialAd();
      return false;
    }
  }

  // ─────────────────── REWARDED ADS ───────────────────
  void loadRewardedAd() {
    RewardedAd.load(
      adUnitId: rewardedAdUnitId,
      request: const AdRequest(),
      rewardedAdLoadCallback: RewardedAdLoadCallback(
        onAdLoaded: (ad) {
          _rewardedAd = ad;
          _isRewardedReady = true;
          debugPrint('[AdService] Rewarded ad loaded & cached.');
        },
        onAdFailedToLoad: (error) {
          debugPrint('[AdService] Rewarded ad failed to load: $error');
          _isRewardedReady = false;
        },
      ),
    );
  }

  /// Shows a rewarded ad. The [onRewarded] callback fires when the user
  /// earns the reward (i.e., watched the full video).
  void showRewardedAd({required Function(RewardItem reward) onRewarded}) {
    if (_isRewardedReady && _rewardedAd != null) {
      _rewardedAd!.fullScreenContentCallback = FullScreenContentCallback(
        onAdDismissedFullScreenContent: (ad) {
          ad.dispose();
          _isRewardedReady = false;
          _rewardedAd = null;
          loadRewardedAd(); // Pre-load next
        },
        onAdFailedToShowFullScreenContent: (ad, error) {
          debugPrint('[AdService] Rewarded ad failed to show: $error');
          ad.dispose();
          _isRewardedReady = false;
          _rewardedAd = null;
          loadRewardedAd();
        },
      );
      _rewardedAd!.show(onUserEarnedReward: (ad, reward) {
        debugPrint('[AdService] User earned reward: ${reward.amount} ${reward.type}');
        onRewarded(reward);
      });
    } else {
      debugPrint('[AdService] Rewarded ad not ready. Loading...');
      loadRewardedAd();
    }
  }

  bool get isRewardedReady => _isRewardedReady;
  bool get isInterstitialReady => _isInterstitialReady;

  // ─────────────────── CLEANUP ───────────────────
  void dispose() {
    _interstitialAd?.dispose();
    _rewardedAd?.dispose();
  }
}

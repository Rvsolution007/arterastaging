class AdConfig {
  final bool showGlobalAds;
  final Map<String, FeatureAdConfig> features;

  AdConfig({
    required this.showGlobalAds,
    required this.features,
  });

  factory AdConfig.fromJson(Map<String, dynamic> json) {
    Map<String, FeatureAdConfig> featuresMap = {};
    
    if (json['features'] != null) {
      json['features'].forEach((key, value) {
        featuresMap[key] = FeatureAdConfig.fromJson(value);
      });
    }

    return AdConfig(
      showGlobalAds: json['show_global_ads'] ?? false,
      features: featuresMap,
    );
  }
}

class FeatureAdConfig {
  final int baseLimit;
  final int used;
  final int maxAdUses;
  final int adUsed;
  final String state; // 'no_ads', 'all_ads', 'rewarded_interstitial', 'locked'
  final String? postAdFlowPaid; // 'no_ads', 'rewarded_then_interstitial', 'interstitial_only', 'locked'
  final String? postAdFlowFree; 

  FeatureAdConfig({
    required this.baseLimit,
    required this.used,
    required this.maxAdUses,
    required this.adUsed,
    required this.state,
    this.postAdFlowPaid,
    this.postAdFlowFree,
  });

  factory FeatureAdConfig.fromJson(Map<String, dynamic> json) {
    return FeatureAdConfig(
      baseLimit: json['base_limit'] ?? 0,
      used: json['used'] ?? 0,
      maxAdUses: json['max_ad_uses'] ?? 0,
      adUsed: json['ad_used'] ?? 0,
      state: json['state'] ?? 'locked',
      postAdFlowPaid: json['post_ad_flow_paid'],
      postAdFlowFree: json['post_ad_flow_free'],
    );
  }

  bool get isLocked => state == 'locked';
  bool get isNoAds => state == 'no_ads';
  bool get isAllAds => state == 'all_ads';
  bool get isRewardedInterstitial => state == 'rewarded_interstitial';
}

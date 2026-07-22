/// Normalizes the premium flags returned by admin APIs. Laravel/MySQL payloads
/// may represent the same flag as `true`, `1`, or a string such as `"1"`.
bool isPremiumTemplate(Map<dynamic, dynamic>? template) {
  if (template == null) return false;
  final dynamic value =
      template['isPaid'] ?? template['is_paid'] ?? template['premium'];
  if (value is bool) return value;
  if (value is num) return value != 0;
  return const {
    '1',
    'true',
    'yes',
    'paid',
    'premium',
  }.contains(value?.toString().trim().toLowerCase());
}

/// Uses the same feature names as the subscription API for every entry point.
String subscriptionFeatureForTemplateType(String type) {
  switch (type.toLowerCase()) {
    case 'category':
      return 'category_post';
    case 'custom':
    case 'business_custom':
    case 'business_custom_frame':
    case 'business_frame':
      return 'custom_post';
    default:
      return 'festival_post';
  }
}

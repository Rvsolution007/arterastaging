import 'dart:convert';
import 'dart:io';

import 'package:http/http.dart' as http;

import '../../../config/app_config.dart';
import 'adlive_token_store.dart';

class AdLiveApiException implements Exception {
  const AdLiveApiException(this.message, {this.statusCode});

  final String message;
  final int? statusCode;

  @override
  String toString() => message;
}

/// API client for the AdLive server only. It never sends the Artera token to
/// AdLive; the SSO ticket is exchanged once and then stored separately.
class AdLiveApiService {
  static String get baseUrl => AppConfig.adLiveApiBaseUrl;

  static Future<Map<String, dynamic>> exchangeArteraTicket(String ticket) {
    return _post('/auth/artera/exchange', {'ticket': ticket});
  }

  static Future<Map<String, dynamic>> bootstrap() => _get('/mobile/bootstrap');

  static Future<Map<String, dynamic>> dashboard({String range = '7d'}) =>
      _get('/mobile/dashboard?range=${Uri.encodeQueryComponent(range)}');

  static Future<Map<String, dynamic>> campaigns() =>
      _get('/mobile/campaigns?per_page=50');

  static Future<Map<String, dynamic>> campaign(String id) =>
      _get('/mobile/campaigns/${Uri.encodeComponent(id)}');

  static Future<Map<String, dynamic>> campaignDrafts() =>
      _get('/mobile/campaign-drafts');

  static Future<Map<String, dynamic>> campaignDraft(String id) =>
      _get('/mobile/campaign-workflow/drafts/${Uri.encodeComponent(id)}');

  static Future<Map<String, dynamic>> deleteCampaignDraft(String id) =>
      _delete('/mobile/campaign-drafts/${Uri.encodeComponent(id)}');

  static Future<Map<String, dynamic>> requestCampaignUnpublish(
    String campaignId,
  ) => _post(
    '/mobile/campaigns/${Uri.encodeComponent(campaignId)}/unpublish-request',
    const {},
  );

  static Future<Map<String, dynamic>> billing() => _get('/billing');

  static Future<Map<String, dynamic>> metaConnectionStatus() =>
      _get('/mobile/meta/status');

  static Future<Map<String, dynamic>> facebookSdkConfiguration() =>
      _get('/mobile/meta/sdk-configuration');

  static Future<Map<String, dynamic>> startMetaConnection() =>
      _post('/mobile/meta/connect', const {});

  static Future<Map<String, dynamic>> launchConnectedAccounts() =>
      _post('/mobile/connected-accounts/launch', const {});

  /// Exchanges the AdLive-only mobile token for a short-lived, opaque browser
  /// handoff. The returned URL contains no bearer token or Pixel credential.
  static Future<String> launchBrowserApp({String? businessId}) async {
    final response = await _post('/mobile/app/launch', {
      if (businessId != null && businessId.isNotEmpty)
        'artera_business_id': businessId,
    });
    final url = response['url']?.toString() ?? '';
    final uri = Uri.tryParse(url);
    if (uri == null || !uri.hasScheme || uri.host.isEmpty) {
      throw const AdLiveApiException(
        'AdLive did not create a secure mobile session.',
      );
    }
    return uri.toString();
  }

  static Future<Map<String, dynamic>> completeNativeMetaConnection(
    String token,
  ) => _post('/mobile/meta/native/complete', {'access_token': token});

  static Future<Map<String, dynamic>> campaignWorkflowBootstrap() =>
      _get('/mobile/campaign-workflow/bootstrap');

  static Future<Map<String, dynamic>> ecommerceStores() =>
      _get('/mobile/ecommerce/stores');

  static Future<Map<String, dynamic>> connectEcommerceStore({
    required String platform,
    required String storeName,
    required String storeUrl,
    String? accessToken,
    String? apiKey,
    String? apiSecret,
    String? webhookSecret,
  }) => _post('/mobile/ecommerce/stores', {
    'platform': platform,
    'store_name': storeName,
    'store_url': storeUrl,
    if (accessToken != null && accessToken.isNotEmpty)
      'access_token': accessToken,
    if (apiKey != null && apiKey.isNotEmpty) 'api_key': apiKey,
    if (apiSecret != null && apiSecret.isNotEmpty) 'api_secret': apiSecret,
    if (webhookSecret != null && webhookSecret.isNotEmpty)
      'webhook_secret': webhookSecret,
  });

  static Future<Map<String, dynamic>> verifyEcommerceStore(String storeId) =>
      _post(
        '/mobile/ecommerce/stores/${Uri.encodeComponent(storeId)}/verify',
        const {},
      );

  static Future<Map<String, dynamic>> disconnectEcommerceStore(
    String storeId,
  ) => _post(
    '/mobile/ecommerce/stores/${Uri.encodeComponent(storeId)}/disconnect',
    const {},
  );

  static Future<Map<String, dynamic>> campaignCatalogueOptions({
    required String kind,
    required Map<String, dynamic> catalogue,
    String search = '',
  }) => _post('/mobile/campaign-workflow/catalogue-options', {
    'kind': kind,
    'catalogue': catalogue,
    'search': search,
  });

  static Future<Map<String, dynamic>> previewCampaignAudience(
    Map<String, dynamic> audienceProfile,
  ) => _post('/mobile/campaign-workflow/audience-preview', {
    'audience_profile': audienceProfile,
  });

  static Future<Map<String, dynamic>> searchCampaignLocations(String query) =>
      _post('/mobile/campaign-workflow/location-search', {'q': query});

  static Future<Map<String, dynamic>> saveCampaignDraft({
    String? draftId,
    required int currentStep,
    required Map<String, dynamic> payload,
    bool finalize = false,
  }) => _post('/mobile/campaign-workflow/drafts', {
    if (draftId != null && draftId.isNotEmpty) 'draft_id': draftId,
    'current_step': currentStep,
    'finalize': finalize,
    'payload': payload,
  });

  static Future<Map<String, dynamic>> prepareCampaignCheckout(
    String draftId,
  ) => _post(
    '/mobile/campaign-workflow/drafts/${Uri.encodeComponent(draftId)}/checkout',
    const {},
  );

  static Future<Map<String, dynamic>> campaignCreative(
    String campaignId,
  ) => _get(
    '/mobile/campaign-workflow/campaigns/${Uri.encodeComponent(campaignId)}/creative',
  );

  static Future<Map<String, dynamic>> uploadCampaignCreative({
    required String campaignId,
    required Map<String, String> fields,
    Map<String, String> files = const {},
  }) => _postMultipart(
    '/mobile/campaign-workflow/campaigns/${Uri.encodeComponent(campaignId)}/creative',
    fields,
    files.map((key, value) => MapEntry(key, [value])),
  );

  static Future<Map<String, dynamic>> submitCampaignRequirements({
    required String campaignId,
    required Map<String, String> fields,
    Map<String, List<String>> files = const {},
  }) => _postMultipart(
    '/mobile/campaign-workflow/campaigns/${Uri.encodeComponent(campaignId)}/requirements',
    fields,
    files,
  );

  static Future<Map<String, dynamic>> createCampaignPaymentOrder(
    String campaignId,
  ) => _post(
    '/mobile/campaign-workflow/campaigns/${Uri.encodeComponent(campaignId)}/payment/order',
    const {},
  );

  static Future<Map<String, dynamic>> verifyCampaignPayment({
    required String campaignId,
    required String orderId,
    required String paymentId,
    required String signature,
  }) => _post(
    '/mobile/campaign-workflow/campaigns/${Uri.encodeComponent(campaignId)}/payment/verify',
    {
      'razorpay_order_id': orderId,
      'razorpay_payment_id': paymentId,
      'razorpay_signature': signature,
    },
  );

  static Future<Map<String, dynamic>> campaignMetaSetup(String campaignId) =>
      _get('/mobile/campaigns/${Uri.encodeComponent(campaignId)}/meta-setup');

  static Future<Map<String, dynamic>> metaAssets(String accountMode) => _get(
    '/mobile/meta/assets?account_mode=${Uri.encodeQueryComponent(accountMode)}',
  );

  static Future<Map<String, dynamic>> selectManagedMetaPage({
    required String pageId,
    required String pageName,
  }) => _post('/mobile/meta/assets/select-page', {
    'page_id': pageId,
    'page_name': pageName,
  });

  static Future<Map<String, dynamic>> selectClientMetaAssets({
    required String adAccountId,
    required String pageId,
    String? whatsappPhoneNumberId,
  }) => _post('/mobile/meta/assets/select-client', {
    'client_meta_ad_account_id': adAccountId,
    'client_meta_page_id': pageId,
    if (whatsappPhoneNumberId != null && whatsappPhoneNumberId.isNotEmpty)
      'whatsapp_phone_number_id': whatsappPhoneNumberId,
  });

  static Future<Map<String, dynamic>> refreshMetaAssets({
    required String accountMode,
    bool force = false,
  }) => _post('/mobile/meta/assets/refresh', {
    'account_mode': accountMode,
    'force': force,
  });

  static Future<Map<String, dynamic>> bridgeClientMetaConnection() =>
      _post('/mobile/meta/assets/bridge-client', const {});

  static Future<Map<String, dynamic>> confirmCampaignMetaSetup({
    required String campaignId,
    String? whatsappPhoneNumberId,
    String? clientMetaDatasetId,
    String? metaDatasetId,
  }) => _post(
    '/mobile/campaigns/${Uri.encodeComponent(campaignId)}/meta-setup/confirm',
    {
      if (whatsappPhoneNumberId != null && whatsappPhoneNumberId.isNotEmpty)
        'whatsapp_phone_number_id': whatsappPhoneNumberId,
      if (clientMetaDatasetId != null && clientMetaDatasetId.isNotEmpty)
        'client_meta_dataset_id': clientMetaDatasetId,
      if (metaDatasetId != null && metaDatasetId.isNotEmpty)
        'meta_dataset_id': metaDatasetId,
    },
  );

  static Future<Map<String, dynamic>> profile() => _get('/profile');

  static Future<Map<String, dynamic>> updateProfile(
    Map<String, dynamic> data,
  ) => _put('/profile', data);

  static Future<Map<String, dynamic>> _get(String endpoint) async {
    final response = await http
        .get(Uri.parse('$baseUrl$endpoint'), headers: await _headers())
        .timeout(const Duration(seconds: 20));

    return _decode(response);
  }

  static Future<Map<String, dynamic>> _post(
    String endpoint,
    Map<String, dynamic> body,
  ) async {
    final response = await http
        .post(
          Uri.parse('$baseUrl$endpoint'),
          headers: await _headers(),
          body: jsonEncode(body),
        )
        .timeout(const Duration(seconds: 20));

    return _decode(response);
  }

  static Future<Map<String, dynamic>> _put(
    String endpoint,
    Map<String, dynamic> body,
  ) async {
    final response = await http
        .put(
          Uri.parse('$baseUrl$endpoint'),
          headers: await _headers(),
          body: jsonEncode(body),
        )
        .timeout(const Duration(seconds: 20));

    return _decode(response);
  }

  static Future<Map<String, dynamic>> _delete(String endpoint) async {
    final response = await http
        .delete(Uri.parse('$baseUrl$endpoint'), headers: await _headers())
        .timeout(const Duration(seconds: 20));

    return _decode(response);
  }

  static Future<Map<String, dynamic>> _postMultipart(
    String endpoint,
    Map<String, String> fields,
    Map<String, List<String>> files,
  ) async {
    final request = http.MultipartRequest(
      'POST',
      Uri.parse('$baseUrl$endpoint'),
    );
    request.headers.addAll(await _headers(json: false));
    request.fields.addAll(fields);
    for (final entry in files.entries) {
      for (final path in entry.value) {
        if (path.isEmpty || !await File(path).exists()) continue;
        request.files.add(await http.MultipartFile.fromPath(entry.key, path));
      }
    }
    final streamed = await request.send().timeout(const Duration(seconds: 45));
    return _decode(await http.Response.fromStream(streamed));
  }

  static Future<Map<String, String>> _headers({bool json = true}) async {
    final token = await AdLiveTokenStore.read();

    return {
      'Accept': 'application/json',
      if (json) 'Content-Type': 'application/json',
      if (token != null) 'Authorization': 'Bearer $token',
    };
  }

  static Map<String, dynamic> _decode(http.Response response) {
    Map<String, dynamic> data = {};
    try {
      final decoded = jsonDecode(response.body);
      if (decoded is Map<String, dynamic>) data = decoded;
    } catch (_) {
      // A generic error is safer than rendering an upstream HTML error page.
    }

    if (response.statusCode < 200 || response.statusCode >= 300) {
      throw AdLiveApiException(
        data['message']?.toString() ??
            'AdLive could not complete this request.',
        statusCode: response.statusCode,
      );
    }

    return data;
  }
}

import 'dart:convert';

import 'package:get/get.dart';

import '../../../services/api_service.dart';
import '../services/adlive_api_service.dart';
import '../services/adlive_token_store.dart';

class AdLiveSessionController extends GetxController {
  final isOpening = false.obs;
  final bootstrapData = Rxn<Map<String, dynamic>>();

  /// Opens AdLive without transferring the Artera bearer token or password.
  Future<Map<String, dynamic>> openForBusiness(
    Map<String, dynamic> business,
  ) async {
    final businessId = business['id']?.toString() ?? '';
    if (businessId.isEmpty) {
      throw const AdLiveApiException(
        'Select an Artera business before opening AdLive.',
      );
    }

    isOpening.value = true;
    try {
      final ticketResponse = await ApiService.post('/adlive/sso-ticket', {
        'business_id': businessId,
        'consent_version': 'adlive-mobile-v1',
      });

      if (ticketResponse.statusCode < 200 || ticketResponse.statusCode >= 300) {
        throw AdLiveApiException(_arteraError(ticketResponse.body));
      }

      final ticketData = _decodeArtera(ticketResponse.body);
      final ticket = ticketData['ticket']?.toString() ?? '';
      if (ticket.isEmpty) {
        throw const AdLiveApiException(
          'Artera did not create a secure AdLive access request.',
        );
      }

      final exchange = await AdLiveApiService.exchangeArteraTicket(ticket);
      final adLiveToken = exchange['access_token']?.toString() ?? '';
      if (adLiveToken.isEmpty) {
        throw const AdLiveApiException(
          'AdLive did not return a secure session.',
        );
      }

      await AdLiveTokenStore.write(adLiveToken);
      final bootstrap = await AdLiveApiService.bootstrap();
      bootstrapData.value = bootstrap;
      return bootstrap;
    } on AdLiveApiException {
      rethrow;
    } catch (_) {
      throw const AdLiveApiException(
        'Could not open AdLive. Check your connection and try again.',
      );
    } finally {
      isOpening.value = false;
    }
  }

  Future<void> clearLocalSession() async {
    bootstrapData.value = null;
    await AdLiveTokenStore.clear();
  }

  Map<String, dynamic> _decodeArtera(String responseBody) {
    try {
      final decoded = jsonDecode(responseBody);
      if (decoded is Map<String, dynamic>) return decoded;
    } catch (_) {
      // The calling method supplies a safe error below.
    }
    throw const AdLiveApiException(
      'Artera returned an invalid sign-in response.',
    );
  }

  String _arteraError(String responseBody) {
    try {
      final decoded = jsonDecode(responseBody);
      if (decoded is Map && decoded['message'] != null) {
        return decoded['message'].toString();
      }
    } catch (_) {
      // Do not surface response bodies that may contain HTML or internals.
    }
    return 'Artera could not start AdLive. Please try again.';
  }
}

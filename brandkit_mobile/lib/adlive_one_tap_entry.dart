import 'package:brandkit_mobile/controllers/home_controller.dart';
import 'package:brandkit_mobile/features/adlive/controllers/adlive_session_controller.dart';
import 'package:brandkit_mobile/features/adlive/screens/adlive_shell_screen.dart';
import 'package:brandkit_mobile/features/adlive/services/adlive_api_service.dart';
import 'package:brandkit_mobile/features/adlive/services/adlive_token_store.dart';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:shared_preferences/shared_preferences.dart';

/// Pixel-owned selection metadata for AdLive's separately scoped token.
/// The token remains in [AdLiveTokenStore]; no Pixel bearer token is copied.
class AdLiveOneTapEntry {
  const AdLiveOneTapEntry._();

  static const _userKey = 'adlive_scoped_artera_user_id';
  static const _businessKey = 'adlive_scoped_business_id';

  static Future<void> open(BuildContext context) async {
    final home = Get.isRegistered<HomeController>()
        ? Get.find<HomeController>()
        : Get.put(HomeController());
    final prefs = await SharedPreferences.getInstance();
    final userId = prefs.getString('userId') ?? '';
    final selectedId = prefs.getString(_businessKey) ?? '';
    final sameAccount = prefs.getString(_userKey) == userId;
    final selectedBusiness = sameAccount
        ? _businessForId(home, selectedId)
        : null;

    final hasToken = await AdLiveTokenStore.read() != null;
    if (!AdLiveScopedSelection.canOpen(
      currentUserId: userId,
      storedUserId: prefs.getString(_userKey),
      selectedBusinessId: selectedId,
      businessAvailable: selectedBusiness != null,
      hasToken: hasToken,
    )) {
      await _clearScope(prefs);
      if (context.mounted) {
        await showModalBottomSheet<void>(
          context: context,
          isScrollControlled: true,
          backgroundColor: Colors.transparent,
          builder: (_) => _AdLiveActivation(
            home: home,
            userId: userId,
            onActivated: (business, bootstrap) => _showShell(context, business, bootstrap),
          ),
        );
      }
      return;
    }

    try {
      final bootstrap = await AdLiveApiService.bootstrap();
      if (context.mounted) _showShell(context, selectedBusiness, bootstrap);
    } on AdLiveApiException catch (error) {
      // Only an expired/invalid AdLive token is refreshed, through Pixel's
      // authenticated one-time ticket, in the same user tap.
      if (error.statusCode != 401 && error.statusCode != 403) {
        _showError(error.message);
        return;
      }
      await _issueScopedSession(context, home, userId, selectedBusiness);
    }
  }

  static Future<void> switchBusiness(BuildContext context) async {
    final prefs = await SharedPreferences.getInstance();
    await _clearScope(prefs);
    if (context.mounted) await open(context);
  }

  static Map<String, dynamic>? _businessForId(HomeController home, String id) {
    if (id.isEmpty) return null;
    for (final raw in home.businesses) {
      if (raw is Map && raw['id']?.toString() == id) {
        return Map<String, dynamic>.from(raw);
      }
    }
    return null;
  }

  static Future<void> _issueScopedSession(
    BuildContext context,
    HomeController home,
    String userId,
    Map<String, dynamic> business,
  ) async {
    try {
      final session = Get.isRegistered<AdLiveSessionController>()
          ? Get.find<AdLiveSessionController>()
          : Get.put(AdLiveSessionController());
      final bootstrap = await session.openForBusiness(business);
      final prefs = await SharedPreferences.getInstance();
      await _saveScope(prefs, userId, business['id']?.toString() ?? '');
      if (context.mounted) _showShell(context, business, bootstrap);
    } on AdLiveApiException catch (error) {
      _showError(error.message);
    }
  }

  static Future<void> _saveScope(
    SharedPreferences prefs,
    String userId,
    String businessId,
  ) async {
    await prefs.setString(_userKey, userId);
    await prefs.setString(_businessKey, businessId);
  }

  static Future<void> _clearScope(SharedPreferences prefs) async {
    await AdLiveTokenStore.clear();
    await prefs.remove(_userKey);
    await prefs.remove(_businessKey);
  }

  static void _showShell(
    BuildContext context,
    Map<String, dynamic> selectedBusiness,
    Map<String, dynamic> bootstrap,
  ) {
    final serverBusiness = bootstrap['business'];
    Get.to(
      () => AdLiveShellScreen(
        initialBusiness: serverBusiness is Map
            ? Map<String, dynamic>.from(serverBusiness)
            : selectedBusiness,
        bootstrap: bootstrap,
      ),
    );
  }

  static void _showError(String message) {
    Get.snackbar(
      'Could not open AdLive',
      message,
      backgroundColor: const Color(0xFFFEF3C7),
      colorText: const Color(0xFF92400E),
    );
  }
}

/// Pure guard used by the entry point and widget-level tests. Keeping account
/// scope explicit prevents a separately scoped AdLive token being used by a
/// later Pixel login on the same device.
class AdLiveScopedSelection {
  const AdLiveScopedSelection._();

  static bool canOpen({
    required String currentUserId,
    required String? storedUserId,
    required String selectedBusinessId,
    required bool businessAvailable,
    required bool hasToken,
  }) {
    return currentUserId.isNotEmpty &&
        storedUserId == currentUserId &&
        selectedBusinessId.isNotEmpty &&
        businessAvailable &&
        hasToken;
  }
}

class AdLiveOneTapEntryCard extends StatelessWidget {
  const AdLiveOneTapEntryCard({super.key});

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.fromLTRB(16, 0, 16, 20),
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(24),
        gradient: const LinearGradient(colors: [Color(0xFF047857), Color(0xFF10B981)]),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text('ADLIVE', style: TextStyle(color: Colors.white, fontWeight: FontWeight.w800, letterSpacing: 1.1)),
          const SizedBox(height: 12),
          const Text('Grow your business\nwith Meta ads', style: TextStyle(color: Colors.white, fontSize: 23, fontWeight: FontWeight.w800)),
          const SizedBox(height: 8),
          const Text('Launch, track and improve campaigns with AdLive.', style: TextStyle(color: Color(0xFFD1FAE5))),
          const SizedBox(height: 16),
          FilledButton.icon(
            onPressed: () => AdLiveOneTapEntry.open(context),
            style: FilledButton.styleFrom(backgroundColor: Colors.white, foregroundColor: const Color(0xFF047857)),
            icon: const Icon(Icons.campaign_outlined),
            label: const Text('Open AdLive'),
          ),
        ],
      ),
    );
  }
}

class AdLiveOneTapEntryTile extends StatelessWidget {
  const AdLiveOneTapEntryTile({super.key});

  @override
  Widget build(BuildContext context) {
    return ListTile(
      leading: const Icon(Icons.campaign_outlined, color: Color(0xFF047857)),
      title: const Text('AdLive'),
      subtitle: const Text('Campaigns, leads and ad wallet'),
      trailing: const Icon(Icons.chevron_right),
      onTap: () => AdLiveOneTapEntry.open(context),
    );
  }
}

class _AdLiveActivation extends StatefulWidget {
  const _AdLiveActivation({required this.home, required this.userId, required this.onActivated});

  final HomeController home;
  final String userId;
  final void Function(Map<String, dynamic>, Map<String, dynamic>) onActivated;

  @override
  State<_AdLiveActivation> createState() => _AdLiveActivationState();
}

class _AdLiveActivationState extends State<_AdLiveActivation> {
  Map<String, dynamic>? _selected;
  var _opening = false;

  @override
  void initState() {
    super.initState();
    final currentId = widget.home.businessId.value;
    _selected = AdLiveOneTapEntry._businessForId(widget.home, currentId);
  }

  @override
  Widget build(BuildContext context) {
    final businesses = widget.home.businesses
        .whereType<Map>()
        .map((business) => Map<String, dynamic>.from(business))
        .toList();
    return SafeArea(
      top: false,
      child: Container(
        margin: const EdgeInsets.only(top: 80),
        padding: const EdgeInsets.fromLTRB(20, 16, 20, 28),
        decoration: const BoxDecoration(color: Colors.white, borderRadius: BorderRadius.vertical(top: Radius.circular(28))),
        child: Column(mainAxisSize: MainAxisSize.min, children: [
          const Text('Choose a business for AdLive', style: TextStyle(fontSize: 20, fontWeight: FontWeight.w800)),
          const SizedBox(height: 8),
          const Text('Confirm once. Later taps open this business directly.', textAlign: TextAlign.center),
          const SizedBox(height: 16),
          ConstrainedBox(
            constraints: const BoxConstraints(maxHeight: 260),
            child: ListView.builder(
              shrinkWrap: true,
              itemCount: businesses.length,
              itemBuilder: (context, index) {
                final business = businesses[index];
                final selected = business['id']?.toString() == _selected?['id']?.toString();
                return RadioListTile<String>(
                  value: business['id']?.toString() ?? '',
                  groupValue: _selected?['id']?.toString(),
                  title: Text(business['name']?.toString() ?? 'Business'),
                  selected: selected,
                  onChanged: (_) => setState(() => _selected = business),
                );
              },
            ),
          ),
          const SizedBox(height: 12),
          SizedBox(
            width: double.infinity,
            child: FilledButton(
              onPressed: _opening || _selected == null || widget.userId.isEmpty ? null : _confirm,
              child: _opening ? const CircularProgressIndicator() : const Text('Open AdLive'),
            ),
          ),
        ]),
      ),
    );
  }

  Future<void> _confirm() async {
    setState(() => _opening = true);
    try {
      final session = Get.isRegistered<AdLiveSessionController>()
          ? Get.find<AdLiveSessionController>()
          : Get.put(AdLiveSessionController());
      final business = _selected!;
      final bootstrap = await session.openForBusiness(business);
      final prefs = await SharedPreferences.getInstance();
      await AdLiveOneTapEntry._saveScope(prefs, widget.userId, business['id']?.toString() ?? '');
      if (mounted) Navigator.of(context).pop();
      widget.onActivated(business, bootstrap);
    } on AdLiveApiException catch (error) {
      AdLiveOneTapEntry._showError(error.message);
    } finally {
      if (mounted) setState(() => _opening = false);
    }
  }
}

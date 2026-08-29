import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:url_launcher/url_launcher.dart';

import '../services/adlive_api_service.dart';
import '../services/native_facebook_auth_service.dart';

/// The first in-app AdLive surface. It deliberately uses setup states until
/// the secure AdLive SSO ticket and mobile API are connected.
class AdLiveShellScreen extends StatefulWidget {
  const AdLiveShellScreen({
    super.key,
    required this.initialBusiness,
    this.bootstrap,
  });

  final Map<String, dynamic> initialBusiness;
  final Map<String, dynamic>? bootstrap;

  @override
  State<AdLiveShellScreen> createState() => _AdLiveShellScreenState();
}

class _AdLiveShellScreenState extends State<AdLiveShellScreen>
    with WidgetsBindingObserver {
  static const _green = Color(0xFF047857);
  static const _mint = Color(0xFFECFDF5);

  late Map<String, dynamic> _business;
  var _currentIndex = 0;
  var _isOpeningConnectedAccounts = false;
  var _isConnectingFacebook = false;
  var _metaConnected = false;
  var _whatsAppConnected = false;
  String? _metaPageName;

  bool get _hasMetaConnection => _metaConnected;

  @override
  void initState() {
    super.initState();
    _business = Map<String, dynamic>.from(widget.initialBusiness);
    WidgetsBinding.instance.addObserver(this);
    _refreshMetaStatus();
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    super.didChangeAppLifecycleState(state);
    if (state == AppLifecycleState.resumed) {
      _refreshMetaStatus();
    }
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    super.dispose();
  }

  String get _businessName {
    final name = _business['name']?.toString().trim() ?? '';
    return name.isEmpty ? 'Your business' : name;
  }

  Map<String, dynamic> get _wallet {
    final wallet = widget.bootstrap?['wallet'];
    return wallet is Map ? Map<String, dynamic>.from(wallet) : const {};
  }

  double get _availableWalletBalance {
    final value = _wallet['available'];
    if (value is num) return value.toDouble();
    return double.tryParse(value?.toString() ?? '') ?? 0;
  }

  String get _walletBalanceLabel =>
      '₹${_availableWalletBalance.toStringAsFixed(2)}';

  Future<void> _refreshMetaStatus() async {
    try {
      final response = await AdLiveApiService.metaConnectionStatus();
      if (!mounted) return;

      final pageName = response['page_name']?.toString().trim();
      setState(() {
        _metaConnected = response['connected'] == true;
        _metaPageName = pageName == null || pageName.isEmpty ? null : pageName;
        _whatsAppConnected = response['whatsapp_connected'] == true;
      });
    } catch (_) {
      // Status is refreshed again when the user returns from the browser.
    }
  }

  Future<void> _connectFacebook() async {
    if (_isConnectingFacebook) return;
    setState(() => _isConnectingFacebook = true);

    try {
      final sdkConfiguration = await AdLiveApiService.facebookSdkConfiguration();
      final facebookAccessToken = await NativeFacebookAuthService.connect(
        clientToken: sdkConfiguration['facebook_client_token']?.toString() ?? '',
      );
      final response = await AdLiveApiService.completeNativeMetaConnection(
        facebookAccessToken,
      );
      await _refreshMetaStatus();
      if (mounted) {
        Get.snackbar(
          'Facebook connected',
          response['page_name']?.toString().trim().isNotEmpty == true
              ? 'Connected to ${response['page_name']}.'
              : 'Your Facebook Page is ready for AdLive.',
          snackPosition: SnackPosition.BOTTOM,
          backgroundColor: const Color(0xFFECFDF5),
          colorText: const Color(0xFF065F46),
          margin: const EdgeInsets.all(16),
        );
      }
    } on NativeFacebookLoginException catch (error) {
      if (mounted) {
        _showConnectionError(error.message, title: 'Could not connect Facebook');
      }
    } on AdLiveApiException catch (error) {
      if (mounted) {
        _showConnectionError(error.message, title: 'Could not connect Facebook');
      }
    } catch (_) {
      if (mounted) {
        _showConnectionError(
          'Facebook app login could not be completed. Please try again.',
          title: 'Could not connect Facebook',
        );
      }
    } finally {
      if (mounted) setState(() => _isConnectingFacebook = false);
    }
  }

  Future<void> _openWhatsAppSetup() async {
    if (!_hasMetaConnection) {
      Get.snackbar(
        'Connect Facebook first',
        'Use the Facebook app to connect your Page before WhatsApp setup.',
        snackPosition: SnackPosition.BOTTOM,
        backgroundColor: const Color(0xFFFEF3C7),
        colorText: const Color(0xFF92400E),
        margin: const EdgeInsets.all(16),
      );
      return;
    }

    await _openConnectedAccounts();
  }

  Future<void> _openConnectedAccounts() async {
    if (_isOpeningConnectedAccounts) return;
    setState(() => _isOpeningConnectedAccounts = true);

    try {
      final response = await AdLiveApiService.launchConnectedAccounts();
      final url = response['url']?.toString();
      final uri = url == null ? null : Uri.tryParse(url);
      if (uri == null) {
        throw const AdLiveApiException(
          'Connected Accounts link could not be created.',
        );
      }

      final opened = await launchUrl(uri, mode: LaunchMode.externalApplication);
      if (!opened) {
        throw const AdLiveApiException(
          'Could not open Connected Accounts in the browser.',
        );
      }

      if (mounted) {
        Get.snackbar(
          'Open WhatsApp setup in browser',
          'Complete Meta WhatsApp onboarding, then return to Artera.',
          snackPosition: SnackPosition.BOTTOM,
          backgroundColor: const Color(0xFFECFDF5),
          colorText: const Color(0xFF065F46),
          margin: const EdgeInsets.all(16),
        );
      }
    } on AdLiveApiException catch (error) {
      if (mounted) {
        _showConnectionError(error.message, title: 'Could not open Connected Accounts');
      }
    } catch (_) {
      if (mounted) {
        _showConnectionError(
          'Connected Accounts could not be opened. Please try again.',
        );
      }
    } finally {
      if (mounted) setState(() => _isOpeningConnectedAccounts = false);
    }
  }

  void _showConnectionError(String message, {String title = 'Connection error'}) {
    Get.snackbar(
      title,
      message,
      snackPosition: SnackPosition.BOTTOM,
      backgroundColor: const Color(0xFFFEF3C7),
      colorText: const Color(0xFF92400E),
      margin: const EdgeInsets.all(16),
    );
  }

  void _comingNext(String feature) {
    Get.snackbar(
      'AdLive setup',
      '$feature will be available after your secure AdLive account connection is enabled.',
      snackPosition: SnackPosition.BOTTOM,
      backgroundColor: const Color(0xFFECFDF5),
      colorText: const Color(0xFF065F46),
      margin: const EdgeInsets.all(16),
    );
  }

  Future<void> _changeBusiness() async {
    final name = await showModalBottomSheet<String>(
      context: context,
      backgroundColor: Colors.transparent,
      builder: (context) => SafeArea(
        top: false,
        child: Container(
          padding: const EdgeInsets.fromLTRB(20, 12, 20, 24),
          decoration: const BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.vertical(top: Radius.circular(28)),
          ),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Container(
                width: 40,
                height: 4,
                decoration: BoxDecoration(
                  color: const Color(0xFFE2E8F0),
                  borderRadius: BorderRadius.circular(99),
                ),
              ),
              const SizedBox(height: 18),
              const Align(
                alignment: Alignment.centerLeft,
                child: Text(
                  'AdLive business',
                  style: TextStyle(
                    color: Color(0xFF0F172A),
                    fontSize: 18,
                    fontWeight: FontWeight.w800,
                  ),
                ),
              ),
              const SizedBox(height: 4),
              const Align(
                alignment: Alignment.centerLeft,
                child: Text(
                  'This first version keeps your selected business in view.',
                  style: TextStyle(color: Color(0xFF64748B), fontSize: 12),
                ),
              ),
              const SizedBox(height: 16),
              ListTile(
                contentPadding: const EdgeInsets.symmetric(horizontal: 4),
                leading: const CircleAvatar(
                  backgroundColor: Color(0xFFD1FAE5),
                  child: Icon(Icons.storefront_outlined, color: _green),
                ),
                title: Text(
                  _businessName,
                  style: const TextStyle(fontWeight: FontWeight.w800),
                ),
                subtitle: const Text('Selected from Artera Pixel'),
                trailing: const Icon(
                  Icons.check_circle,
                  color: Color(0xFF059669),
                ),
                onTap: () => Navigator.of(context).pop(_businessName),
              ),
            ],
          ),
        ),
      ),
    );

    if (name != null) {
      _comingNext('Choosing another Artera business');
    }
  }

  @override
  Widget build(BuildContext context) {
    final pages = [_overview(), _campaigns(), _insights(), _more()];

    return Scaffold(
      backgroundColor: const Color(0xFFF8FAFC),
      appBar: AppBar(
        backgroundColor: Colors.white,
        elevation: 0,
        surfaceTintColor: Colors.white,
        leading: IconButton(
          onPressed: () => Navigator.of(context).pop(),
          icon: const Icon(Icons.arrow_back, color: Color(0xFF0F172A)),
        ),
        titleSpacing: 0,
        title: InkWell(
          onTap: _changeBusiness,
          borderRadius: BorderRadius.circular(12),
          child: Padding(
            padding: const EdgeInsets.symmetric(vertical: 6, horizontal: 4),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text(
                  'AdLive',
                  style: TextStyle(
                    color: _green,
                    fontSize: 19,
                    fontWeight: FontWeight.w900,
                  ),
                ),
                Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Flexible(
                      child: Text(
                        _businessName,
                        overflow: TextOverflow.ellipsis,
                        style: const TextStyle(
                          color: Color(0xFF64748B),
                          fontSize: 11,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                    ),
                    const Icon(
                      Icons.expand_more,
                      size: 15,
                      color: Color(0xFF64748B),
                    ),
                  ],
                ),
              ],
            ),
          ),
        ),
        actions: [
          IconButton(
            onPressed: () => _comingNext('Notifications'),
            icon: const Badge(
              child: Icon(Icons.notifications_none, color: Color(0xFF334155)),
            ),
          ),
          const SizedBox(width: 4),
        ],
      ),
      body: SafeArea(top: false, child: pages[_currentIndex]),
      floatingActionButton: _currentIndex == 1
          ? FloatingActionButton.extended(
              onPressed: () => _comingNext('Create campaign'),
              backgroundColor: _green,
              foregroundColor: Colors.white,
              icon: const Icon(Icons.add),
              label: const Text(
                'Create campaign',
                style: TextStyle(fontWeight: FontWeight.w800),
              ),
            )
          : null,
      bottomNavigationBar: NavigationBar(
        height: 72,
        selectedIndex: _currentIndex,
        onDestinationSelected: (value) => setState(() => _currentIndex = value),
        indicatorColor: const Color(0xFFD1FAE5),
        backgroundColor: Colors.white,
        labelTextStyle: WidgetStateProperty.resolveWith(
          (states) => TextStyle(
            color: states.contains(WidgetState.selected)
                ? _green
                : const Color(0xFF64748B),
            fontWeight: states.contains(WidgetState.selected)
                ? FontWeight.w800
                : FontWeight.w600,
            fontSize: 11,
          ),
        ),
        destinations: const [
          NavigationDestination(
            icon: Icon(Icons.grid_view_rounded),
            label: 'Overview',
          ),
          NavigationDestination(
            icon: Icon(Icons.campaign_outlined),
            label: 'Campaigns',
          ),
          NavigationDestination(
            icon: Icon(Icons.insights_outlined),
            label: 'Insights',
          ),
          NavigationDestination(icon: Icon(Icons.more_horiz), label: 'More'),
        ],
      ),
    );
  }

  Widget _overview() {
    return ListView(
      padding: const EdgeInsets.fromLTRB(16, 20, 16, 112),
      children: [
        Text(
          'Good to see you',
          style: TextStyle(
            color: Colors.blueGrey.shade600,
            fontSize: 14,
            fontWeight: FontWeight.w600,
          ),
        ),
        const SizedBox(height: 2),
        Text(
          _businessName,
          style: const TextStyle(
            color: Color(0xFF0F172A),
            fontSize: 25,
            fontWeight: FontWeight.w900,
          ),
        ),
        const SizedBox(height: 18),
        _walletCard(),
        const SizedBox(height: 20),
        const Text(
          'Get ready to launch',
          style: TextStyle(
            color: Color(0xFF0F172A),
            fontSize: 17,
            fontWeight: FontWeight.w800,
          ),
        ),
        const SizedBox(height: 10),
        _actionCard(
          icon: _hasMetaConnection
              ? Icons.verified_rounded
              : Icons.link_rounded,
          iconBackground: const Color(0xFFE0F2FE),
          iconColor: const Color(0xFF0369A1),
          title: _hasMetaConnection ? 'Facebook connected' : 'Connect Facebook',
          subtitle: _hasMetaConnection
              ? (_metaPageName == null
                    ? 'Your Facebook and Meta ad account are ready.'
                    : 'Connected to $_metaPageName.')
              : (_isConnectingFacebook
                    ? 'Opening the Facebook app…'
                    : 'Use the Facebook app installed on this phone.'),
          onTap: _isConnectingFacebook ? null : _connectFacebook,
        ),
        const SizedBox(height: 10),
        _actionCard(
          icon: _whatsAppConnected
              ? Icons.verified_rounded
              : Icons.chat_outlined,
          iconBackground: const Color(0xFFD1FAE5),
          iconColor: _green,
          title: _whatsAppConnected
              ? 'WhatsApp Business connected'
              : 'Connect WhatsApp Business',
          subtitle: _whatsAppConnected
              ? 'Your WhatsApp Business account is ready.'
              : (_isOpeningConnectedAccounts
                    ? 'Opening secure WhatsApp setup...'
                    : 'Set up WhatsApp Business and link it to your Page.'),
          onTap: _isOpeningConnectedAccounts ? null : _openWhatsAppSetup,
        ),
        const SizedBox(height: 10),
        _actionCard(
          icon: Icons.add_chart_rounded,
          iconBackground: const Color(0xFFD1FAE5),
          iconColor: _green,
          title: 'Create your first campaign',
          subtitle: 'Choose audience, creative, budget and delivery hours.',
          onTap: () => _comingNext('Create campaign'),
        ),
        const SizedBox(height: 24),
        const Text(
          'Campaign performance',
          style: TextStyle(
            color: Color(0xFF0F172A),
            fontSize: 17,
            fontWeight: FontWeight.w800,
          ),
        ),
        const SizedBox(height: 10),
        _emptyPerformanceCard(),
      ],
    );
  }

  Widget _walletCard() {
    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(22),
        gradient: const LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [_green, Color(0xFF059669), Color(0xFF10B981)],
        ),
        boxShadow: const [
          BoxShadow(
            color: Color(0x33047857),
            blurRadius: 18,
            offset: Offset(0, 8),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Icon(
                Icons.account_balance_wallet_outlined,
                color: Colors.white,
                size: 21,
              ),
              SizedBox(width: 8),
              Text(
                'AdLive ad wallet',
                style: TextStyle(
                  color: Colors.white,
                  fontSize: 15,
                  fontWeight: FontWeight.w800,
                ),
              ),
              Spacer(),
              _Pill(
                label: widget.bootstrap == null ? 'Setup needed' : 'Connected',
              ),
            ],
          ),
          const SizedBox(height: 20),
          Text(
            _walletBalanceLabel,
            style: TextStyle(
              color: Colors.white,
              fontSize: 32,
              fontWeight: FontWeight.w900,
            ),
          ),
          const SizedBox(height: 3),
          Text(
            widget.bootstrap == null
                ? 'Available for Meta ad spend after wallet setup'
                : 'Available for Meta ad spend',
            style: TextStyle(
              color: Color(0xFFD1FAE5),
              fontSize: 12.5,
              fontWeight: FontWeight.w600,
            ),
          ),
          const SizedBox(height: 17),
          OutlinedButton.icon(
            onPressed: () => _comingNext('Ad wallet setup'),
            icon: const Icon(Icons.add_circle_outline, size: 18),
            label: Text(
              widget.bootstrap == null ? 'Set up ad wallet' : 'View ad wallet',
              style: TextStyle(fontWeight: FontWeight.w800),
            ),
            style: OutlinedButton.styleFrom(
              foregroundColor: _green,
              backgroundColor: Colors.white,
              side: BorderSide.none,
              padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(12),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _campaigns() {
    return _emptyPage(
      icon: Icons.campaign_outlined,
      title: 'No campaigns yet',
      description:
          'Create and manage Meta campaigns for $_businessName here. Campaign changes and results will be saved in their history.',
      actionLabel: 'Create first campaign',
      onAction: () => _comingNext('Create campaign'),
    );
  }

  Widget _insights() {
    return _emptyPage(
      icon: Icons.query_stats_outlined,
      title: 'Insights will appear here',
      description:
          'After Meta sync starts, you will see leads, spend, cost per lead and the best delivery hours for $_businessName.',
      actionLabel: 'Connect Facebook & WhatsApp',
      onAction: _hasMetaConnection ? _openWhatsAppSetup : _connectFacebook,
    );
  }

  Widget _more() {
    return ListView(
      padding: const EdgeInsets.fromLTRB(16, 20, 16, 36),
      children: [
        const Text(
          'AdLive settings',
          style: TextStyle(
            color: Color(0xFF0F172A),
            fontSize: 22,
            fontWeight: FontWeight.w900,
          ),
        ),
        const SizedBox(height: 16),
        _settingsCard(
          Icons.account_balance_wallet_outlined,
          'Ad wallet & billing',
          'Top up and view campaign spending',
        ),
        _settingsCard(
          Icons.account_tree_outlined,
          'Facebook & WhatsApp',
          'Manage Connected Accounts',
          onTap: _openConnectedAccounts,
        ),
        _settingsCard(
          Icons.history_rounded,
          'Campaign change history',
          'Saved campaign versions and results',
        ),
        _settingsCard(
          Icons.help_outline_rounded,
          'AdLive help',
          'Get help with your campaigns',
        ),
        const SizedBox(height: 18),
        Container(
          padding: const EdgeInsets.all(16),
          decoration: BoxDecoration(
            color: _mint,
            borderRadius: BorderRadius.circular(16),
            border: Border.all(color: const Color(0xFFA7F3D0)),
          ),
          child: const Row(
            children: [
              Icon(Icons.shield_outlined, color: _green),
              SizedBox(width: 12),
              Expanded(
                child: Text(
                  'Your Artera login stays secure. AdLive uses it only to open your connected ad account.',
                  style: TextStyle(
                    color: Color(0xFF065F46),
                    fontSize: 12.5,
                    height: 1.35,
                    fontWeight: FontWeight.w600,
                  ),
                ),
              ),
            ],
          ),
        ),
      ],
    );
  }

  Widget _settingsCard(
    IconData icon,
    String title,
    String subtitle, {
    VoidCallback? onTap,
  }) {
    return Card(
      elevation: 0,
      margin: const EdgeInsets.only(bottom: 10),
      color: Colors.white,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(16),
        side: const BorderSide(color: Color(0xFFE2E8F0)),
      ),
      child: ListTile(
        onTap: onTap ?? () => _comingNext(title),
        leading: Container(
          width: 42,
          height: 42,
          decoration: BoxDecoration(
            color: _mint,
            borderRadius: BorderRadius.circular(12),
          ),
          child: Icon(icon, color: _green),
        ),
        title: Text(
          title,
          style: const TextStyle(
            color: Color(0xFF1E293B),
            fontWeight: FontWeight.w800,
          ),
        ),
        subtitle: Text(
          subtitle,
          style: const TextStyle(color: Color(0xFF64748B), fontSize: 12),
        ),
        trailing: const Icon(Icons.chevron_right, color: Color(0xFF94A3B8)),
      ),
    );
  }

  Widget _emptyPage({
    required IconData icon,
    required String title,
    required String description,
    required String actionLabel,
    required VoidCallback onAction,
  }) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(28),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Container(
              width: 78,
              height: 78,
              decoration: const BoxDecoration(
                color: Color(0xFFD1FAE5),
                shape: BoxShape.circle,
              ),
              child: Icon(icon, color: _green, size: 36),
            ),
            const SizedBox(height: 20),
            Text(
              title,
              textAlign: TextAlign.center,
              style: const TextStyle(
                color: Color(0xFF0F172A),
                fontSize: 21,
                fontWeight: FontWeight.w900,
              ),
            ),
            const SizedBox(height: 9),
            Text(
              description,
              textAlign: TextAlign.center,
              style: const TextStyle(
                color: Color(0xFF64748B),
                fontSize: 13.5,
                height: 1.45,
              ),
            ),
            const SizedBox(height: 22),
            ElevatedButton(
              onPressed: onAction,
              style: ElevatedButton.styleFrom(
                backgroundColor: _green,
                foregroundColor: Colors.white,
                elevation: 0,
                padding: const EdgeInsets.symmetric(
                  horizontal: 20,
                  vertical: 14,
                ),
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(14),
                ),
              ),
              child: Text(
                actionLabel,
                style: const TextStyle(fontWeight: FontWeight.w800),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _actionCard({
    required IconData icon,
    required Color iconBackground,
    required Color iconColor,
    required String title,
    required String subtitle,
    required VoidCallback? onTap,
  }) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(18),
      child: Container(
        padding: const EdgeInsets.all(15),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(18),
          border: Border.all(color: const Color(0xFFE2E8F0)),
        ),
        child: Row(
          children: [
            Container(
              width: 46,
              height: 46,
              decoration: BoxDecoration(
                color: iconBackground,
                borderRadius: BorderRadius.circular(14),
              ),
              child: Icon(icon, color: iconColor),
            ),
            const SizedBox(width: 13),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    title,
                    style: const TextStyle(
                      color: Color(0xFF1E293B),
                      fontSize: 14.5,
                      fontWeight: FontWeight.w800,
                    ),
                  ),
                  const SizedBox(height: 3),
                  Text(
                    subtitle,
                    style: const TextStyle(
                      color: Color(0xFF64748B),
                      fontSize: 12,
                      height: 1.3,
                    ),
                  ),
                ],
              ),
            ),
            const Icon(Icons.chevron_right, color: Color(0xFF94A3B8)),
          ],
        ),
      ),
    );
  }

  Widget _emptyPerformanceCard() {
    return Container(
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: const Color(0xFFE2E8F0)),
      ),
      child: const Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Icon(Icons.bar_chart_rounded, color: Color(0xFF059669)),
              SizedBox(width: 9),
              Text(
                'No reporting data yet',
                style: TextStyle(
                  color: Color(0xFF1E293B),
                  fontWeight: FontWeight.w800,
                ),
              ),
            ],
          ),
          SizedBox(height: 9),
          Text(
            'Once a campaign is live, AdLive will show its 24-hour lead and cost trend, best delivery hours, and every change against the result it produced.',
            style: TextStyle(
              color: Color(0xFF64748B),
              fontSize: 12.5,
              height: 1.42,
            ),
          ),
        ],
      ),
    );
  }
}

class _Pill extends StatelessWidget {
  const _Pill({required this.label});

  final String label;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 5),
      decoration: BoxDecoration(
        color: Colors.white.withValues(alpha: 0.18),
        borderRadius: BorderRadius.circular(99),
        border: Border.all(color: Colors.white.withValues(alpha: 0.35)),
      ),
      child: Text(
        label,
        style: const TextStyle(
          color: Colors.white,
          fontSize: 10.5,
          fontWeight: FontWeight.w800,
        ),
      ),
    );
  }
}

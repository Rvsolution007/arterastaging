import 'package:flutter/material.dart';

import '../services/adlive_api_service.dart';
import 'adlive_campaign_flow_screen.dart';
import 'adlive_connections_screen.dart';
import 'adlive_design.dart';

Map<String, dynamic> _map(dynamic value) =>
    value is Map ? Map<String, dynamic>.from(value) : <String, dynamic>{};

List<Map<String, dynamic>> _maps(dynamic value) => value is List
    ? value
          .whereType<Map>()
          .map((item) => Map<String, dynamic>.from(item))
          .toList()
    : <Map<String, dynamic>>[];

double _amount(dynamic value) => value is num
    ? value.toDouble()
    : double.tryParse(value?.toString() ?? '') ?? 0;

String _money(dynamic value) => '₹${_amount(value).toStringAsFixed(2)}';

String _date(dynamic value) {
  final parsed = DateTime.tryParse(value?.toString() ?? '');
  if (parsed == null) return '—';
  const months = [
    'Jan',
    'Feb',
    'Mar',
    'Apr',
    'May',
    'Jun',
    'Jul',
    'Aug',
    'Sep',
    'Oct',
    'Nov',
    'Dec',
  ];
  return '${parsed.day} ${months[parsed.month - 1]} ${parsed.year}';
}

/// Native AdLive workspace. Every mutating action is sent through the
/// business-scoped AdLive API, which retains payment, approval and Meta gates.
class AdLiveNativeScreen extends StatefulWidget {
  const AdLiveNativeScreen({super.key, required this.business, this.bootstrap});

  final Map<String, dynamic> business;
  final Map<String, dynamic>? bootstrap;

  @override
  State<AdLiveNativeScreen> createState() => _AdLiveNativeScreenState();
}

class _AdLiveNativeScreenState extends State<AdLiveNativeScreen> {
  int _tab = 0;
  bool _loading = true;
  String? _error;
  String _search = '';
  String _status = 'All';
  Map<String, dynamic> _dashboard = {};
  Map<String, dynamic> _campaignPage = {};
  Map<String, dynamic> _billing = {};

  String get _businessName {
    final name = widget.business['name']?.toString().trim() ?? '';
    return name.isEmpty ? 'Your business' : name;
  }

  dynamic get _availableWallet {
    final liveWallet = _map(_billing['wallet']);
    if (liveWallet.containsKey('available')) return liveWallet['available'];
    return _map(widget.bootstrap?['wallet'])['available'];
  }

  @override
  void initState() {
    super.initState();
    _refresh();
  }

  Future<void> _refresh() async {
    if (mounted) {
      setState(() {
        _loading = true;
        _error = null;
      });
    }
    Future<Map<String, dynamic>> safe(
      Future<Map<String, dynamic>> value,
    ) async {
      try {
        return await value;
      } catch (_) {
        return {};
      }
    }

    final results = await Future.wait([
      safe(AdLiveApiService.dashboard()),
      safe(AdLiveApiService.campaigns()),
      safe(AdLiveApiService.billing()),
    ]);
    if (!mounted) return;
    setState(() {
      _dashboard = results[0];
      _campaignPage = results[1];
      _billing = results[2];
      _loading = false;
      if (results.every((result) => result.isEmpty)) {
        _error = 'AdLive data could not load. Check your connection and retry.';
      }
    });
  }

  Future<void> _openCampaignFlow() async {
    await Navigator.of(context).push(
      MaterialPageRoute<void>(builder: (_) => const AdLiveCampaignFlowScreen()),
    );
    if (mounted) _refresh();
  }

  void _openCampaign(Map<String, dynamic> campaign) {
    final id = campaign['id']?.toString() ?? '';
    if (id.isEmpty) return;
    Navigator.of(context).push(
      MaterialPageRoute<void>(
        builder: (_) =>
            _CampaignDetailScreen(id: id, business: widget.business),
      ),
    );
  }

  void _showBusiness() {
    showModalBottomSheet<void>(
      context: context,
      backgroundColor: Colors.transparent,
      builder: (sheetContext) => SafeArea(
        top: false,
        child: Container(
          padding: const EdgeInsets.fromLTRB(20, 14, 20, 24),
          decoration: const BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.vertical(top: Radius.circular(26)),
          ),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Center(
                child: Container(
                  width: 36,
                  height: 4,
                  decoration: BoxDecoration(
                    color: AdLiveDesign.line,
                    borderRadius: BorderRadius.circular(8),
                  ),
                ),
              ),
              const SizedBox(height: 22),
              Text('AdLive business', style: AdLiveDesign.heading(20)),
              const SizedBox(height: 14),
              _surface(
                child: Row(
                  children: [
                    const Icon(
                      Icons.storefront_rounded,
                      color: AdLiveDesign.green,
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Text(
                        _businessName,
                        style: AdLiveDesign.heading(15),
                      ),
                    ),
                    const Icon(
                      Icons.check_circle_rounded,
                      color: AdLiveDesign.green,
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 12),
              Text(
                'To use another business, select it in Pixel and open AdLive again.',
                style: AdLiveDesign.body(),
              ),
              const SizedBox(height: 18),
              SizedBox(
                width: double.infinity,
                child: _primaryButton(
                  'Back to Pixel',
                  Icons.swap_horiz_rounded,
                  () {
                    Navigator.of(sheetContext).pop();
                    Navigator.of(context).pop();
                  },
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _walletHeader() => Tooltip(
    message: 'Available AdLive wallet balance',
    child: InkWell(
      onTap: _loading ? null : _refresh,
      borderRadius: BorderRadius.circular(13),
      child: Container(
        constraints: const BoxConstraints(minWidth: 92, maxWidth: 116),
        margin: const EdgeInsets.symmetric(vertical: 8),
        padding: const EdgeInsets.symmetric(horizontal: 9, vertical: 5),
        decoration: BoxDecoration(
          color: AdLiveDesign.mint,
          borderRadius: BorderRadius.circular(13),
          border: Border.all(color: const Color(0xFFBDEBDA)),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Icon(
              Icons.account_balance_wallet_outlined,
              size: 16,
              color: AdLiveDesign.green,
            ),
            const SizedBox(width: 5),
            Flexible(
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    'Available',
                    style: AdLiveDesign.body(
                      size: 8.5,
                      color: AdLiveDesign.muted,
                    ),
                  ),
                  Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Flexible(
                        child: Text(
                          _availableWallet == null
                              ? '—'
                              : _money(_availableWallet),
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: AdLiveDesign.heading(
                            11.5,
                          ).copyWith(color: AdLiveDesign.deepGreen),
                        ),
                      ),
                      if (_loading) ...[
                        const SizedBox(width: 4),
                        const SizedBox(
                          width: 8,
                          height: 8,
                          child: CircularProgressIndicator(
                            strokeWidth: 1.3,
                            color: AdLiveDesign.green,
                          ),
                        ),
                      ],
                    ],
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    ),
  );

  @override
  Widget build(BuildContext context) {
    return Theme(
      data: Theme.of(context).copyWith(
        colorScheme: ColorScheme.fromSeed(
          seedColor: AdLiveDesign.green,
          surface: Colors.white,
        ),
      ),
      child: Scaffold(
        backgroundColor: AdLiveDesign.canvas,
        appBar: AppBar(
          backgroundColor: Colors.white,
          surfaceTintColor: Colors.white,
          elevation: 0,
          centerTitle: false,
          leading: IconButton(
            tooltip: 'Back to Pixel',
            icon: const Icon(Icons.arrow_back_rounded, color: AdLiveDesign.ink),
            onPressed: () => Navigator.of(context).pop(),
          ),
          titleSpacing: 0,
          title: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                'AdLive',
                style: AdLiveDesign.heading(
                  19,
                ).copyWith(color: AdLiveDesign.deepGreen),
              ),
              Text(
                _businessName,
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: AdLiveDesign.body(size: 11),
              ),
            ],
          ),
          actions: [
            _walletHeader(),
            IconButton(
              tooltip: 'Switch business',
              onPressed: _showBusiness,
              icon: const Icon(
                Icons.keyboard_arrow_down_rounded,
                color: AdLiveDesign.deepGreen,
              ),
            ),
            const SizedBox(width: 4),
          ],
        ),
        body:
            _loading &&
                _dashboard.isEmpty &&
                _campaignPage.isEmpty &&
                _billing.isEmpty
            ? const Center(
                child: CircularProgressIndicator(color: AdLiveDesign.green),
              )
            : _error != null
            ? _errorState(_error!, _refresh)
            : IndexedStack(
                index: _tab,
                children: [_home(), _campaigns(), _billingPage(), _more()],
              ),
        bottomNavigationBar: NavigationBar(
          selectedIndex: _tab,
          onDestinationSelected: (index) => setState(() => _tab = index),
          height: 68,
          backgroundColor: Colors.white,
          surfaceTintColor: Colors.white,
          indicatorColor: AdLiveDesign.mint,
          destinations: const [
            NavigationDestination(
              icon: Icon(Icons.space_dashboard_outlined),
              selectedIcon: Icon(Icons.space_dashboard_rounded),
              label: 'Home',
            ),
            NavigationDestination(
              icon: Icon(Icons.campaign_outlined),
              selectedIcon: Icon(Icons.campaign_rounded),
              label: 'Campaigns',
            ),
            NavigationDestination(
              icon: Icon(Icons.account_balance_wallet_outlined),
              selectedIcon: Icon(Icons.account_balance_wallet_rounded),
              label: 'Billing',
            ),
            NavigationDestination(
              icon: Icon(Icons.grid_view_outlined),
              selectedIcon: Icon(Icons.grid_view_rounded),
              label: 'More',
            ),
          ],
        ),
      ),
    );
  }

  Widget _home() {
    if (_dashboard.isEmpty) {
      return _errorState('Dashboard data could not load.', _refresh);
    }
    final overview = _map(_dashboard['overview']);
    final recent = _maps(_dashboard['recent_campaigns']);
    return RefreshIndicator(
      color: AdLiveDesign.green,
      onRefresh: _refresh,
      child: ListView(
        padding: const EdgeInsets.fromLTRB(18, 22, 18, 28),
        children: [
          Text(
            'YOUR WORKSPACE',
            style: AdLiveDesign.body(
              size: 11,
              color: AdLiveDesign.green,
            ).copyWith(fontWeight: FontWeight.w800, letterSpacing: 1.3),
          ),
          const SizedBox(height: 5),
          Text('Grow with confidence', style: AdLiveDesign.heading(26)),
          const SizedBox(height: 16),
          Container(
            padding: const EdgeInsets.all(20),
            decoration: BoxDecoration(
              gradient: const LinearGradient(
                colors: [AdLiveDesign.deepGreen, AdLiveDesign.green],
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
              ),
              borderRadius: BorderRadius.circular(24),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Icon(
                  Icons.auto_awesome_rounded,
                  color: Color(0xFFD9FFF0),
                  size: 23,
                ),
                const SizedBox(height: 13),
                Text(
                  _businessName,
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                  style: AdLiveDesign.heading(23).copyWith(color: Colors.white),
                ),
                const SizedBox(height: 6),
                Text(
                  '${overview['active_campaigns'] ?? 0} active campaigns',
                  style: AdLiveDesign.body(
                    size: 13,
                    color: const Color(0xFFE0F7ED),
                  ),
                ),
                const SizedBox(height: 19),
                SizedBox(
                  width: double.infinity,
                  child: FilledButton.icon(
                    onPressed: _openCampaignFlow,
                    icon: const Icon(Icons.add_rounded, size: 19),
                    label: const Text('Create campaign'),
                    style: FilledButton.styleFrom(
                      backgroundColor: Colors.white,
                      foregroundColor: AdLiveDesign.deepGreen,
                      padding: const EdgeInsets.symmetric(vertical: 13),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(13),
                      ),
                    ),
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 22),
          _sectionTitle('At a glance'),
          const SizedBox(height: 10),
          LayoutBuilder(
            builder: (context, constraints) {
              final width = (constraints.maxWidth - 10) / 2;
              return Wrap(
                spacing: 10,
                runSpacing: 10,
                children: [
                  _metric(
                    'Campaigns',
                    '${overview['campaigns'] ?? 0}',
                    Icons.campaign_outlined,
                    width,
                  ),
                  _metric(
                    'Active',
                    '${overview['active_campaigns'] ?? 0}',
                    Icons.bolt_rounded,
                    width,
                  ),
                  _metric(
                    'Spend',
                    _money(overview['spend']),
                    Icons.trending_up_rounded,
                    width,
                  ),
                  _metric(
                    'Results',
                    '${overview['primary_results'] ?? 0}',
                    Icons.ads_click_rounded,
                    width,
                  ),
                ],
              );
            },
          ),
          const SizedBox(height: 22),
          Row(
            children: [
              Expanded(child: _sectionTitle('Recent campaigns')),
              TextButton(
                onPressed: () => setState(() => _tab = 1),
                child: const Text(
                  'View all',
                  style: TextStyle(
                    color: AdLiveDesign.green,
                    fontWeight: FontWeight.w700,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 5),
          if (recent.isEmpty)
            _emptyCard('Campaigns will appear here after you create one.'),
          ...recent.map(
            (item) => Padding(
              padding: const EdgeInsets.only(bottom: 9),
              child: _campaignCard(item),
            ),
          ),
        ],
      ),
    );
  }

  Widget _campaigns() {
    if (_campaignPage.isEmpty) {
      return _errorState('Campaigns could not load.', _refresh);
    }
    final all = _maps(_campaignPage['data']);
    final filtered = all.where((item) {
      final matchText = (item['name']?.toString() ?? '').toLowerCase().contains(
        _search.toLowerCase(),
      );
      final matchStatus =
          _status == 'All' ||
          (item['status_label']?.toString() ?? item['status']?.toString() ?? '')
              .toLowerCase()
              .contains(_status.toLowerCase());
      return matchText && matchStatus;
    }).toList();
    return RefreshIndicator(
      color: AdLiveDesign.green,
      onRefresh: _refresh,
      child: ListView(
        padding: const EdgeInsets.fromLTRB(18, 20, 18, 30),
        children: [
          Row(
            children: [
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text('Campaigns', style: AdLiveDesign.heading(26)),
                    Text(
                      '${_campaignPage['total'] ?? all.length} campaigns',
                      style: AdLiveDesign.body(),
                    ),
                  ],
                ),
              ),
              IconButton.filled(
                tooltip: 'New campaign',
                onPressed: _openCampaignFlow,
                style: IconButton.styleFrom(
                  backgroundColor: AdLiveDesign.green,
                ),
                icon: const Icon(Icons.add_rounded),
              ),
            ],
          ),
          const SizedBox(height: 17),
          TextField(
            onChanged: (value) => setState(() => _search = value),
            decoration: InputDecoration(
              hintText: 'Search campaigns',
              prefixIcon: const Icon(Icons.search_rounded, size: 20),
              filled: true,
              fillColor: Colors.white,
              contentPadding: const EdgeInsets.symmetric(
                vertical: 12,
                horizontal: 14,
              ),
              enabledBorder: OutlineInputBorder(
                borderRadius: BorderRadius.circular(14),
                borderSide: const BorderSide(color: AdLiveDesign.line),
              ),
              focusedBorder: OutlineInputBorder(
                borderRadius: BorderRadius.circular(14),
                borderSide: const BorderSide(color: AdLiveDesign.green),
              ),
            ),
          ),
          const SizedBox(height: 13),
          SingleChildScrollView(
            scrollDirection: Axis.horizontal,
            child: Row(
              children: [
                for (final status in ['All', 'Active', 'Pending', 'Paused'])
                  Padding(
                    padding: const EdgeInsets.only(right: 8),
                    child: ChoiceChip(
                      label: Text(status),
                      selected: _status == status,
                      onSelected: (_) => setState(() => _status = status),
                      selectedColor: AdLiveDesign.mint,
                      backgroundColor: Colors.white,
                      side: BorderSide(
                        color: _status == status
                            ? const Color(0xFF8ED8B8)
                            : AdLiveDesign.line,
                      ),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(11),
                      ),
                      labelStyle: TextStyle(
                        color: _status == status
                            ? AdLiveDesign.deepGreen
                            : AdLiveDesign.muted,
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                  ),
              ],
            ),
          ),
          const SizedBox(height: 16),
          if (filtered.isEmpty)
            _emptyCard(
              all.isEmpty
                  ? 'No campaigns yet. Start your first campaign.'
                  : 'No campaigns match these filters.',
            ),
          ...filtered.map(
            (item) => Padding(
              padding: const EdgeInsets.only(bottom: 10),
              child: _campaignCard(item),
            ),
          ),
        ],
      ),
    );
  }

  Widget _billingPage() {
    if (_billing.isEmpty) {
      return _errorState('Billing data could not load.', _refresh);
    }
    final wallet = _map(_billing['wallet']);
    final history = _maps(_billing['wallet_history']);
    final available =
        wallet['available'] ?? _map(widget.bootstrap?['wallet'])['available'];
    return RefreshIndicator(
      color: AdLiveDesign.green,
      onRefresh: _refresh,
      child: ListView(
        padding: const EdgeInsets.fromLTRB(18, 20, 18, 32),
        children: [
          Text('Billing', style: AdLiveDesign.heading(26)),
          const SizedBox(height: 4),
          Text(
            'AdLive wallet and campaign payments',
            style: AdLiveDesign.body(),
          ),
          const SizedBox(height: 18),
          Container(
            padding: const EdgeInsets.all(20),
            decoration: BoxDecoration(
              color: AdLiveDesign.deepGreen,
              borderRadius: BorderRadius.circular(23),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Icon(
                  Icons.account_balance_wallet_outlined,
                  color: Color(0xFFD9FFF0),
                ),
                const SizedBox(height: 16),
                const Text(
                  'Available balance',
                  style: TextStyle(
                    color: Color(0xFFD9FFF0),
                    fontSize: 13,
                    fontWeight: FontWeight.w600,
                  ),
                ),
                const SizedBox(height: 3),
                Text(
                  _money(available),
                  style: const TextStyle(
                    color: Colors.white,
                    fontSize: 30,
                    fontWeight: FontWeight.w800,
                    letterSpacing: -0.8,
                  ),
                ),
                const SizedBox(height: 14),
                Text(
                  'Separate from your Pixel wallet',
                  style: AdLiveDesign.body(
                    size: 12,
                    color: const Color(0xFFD9FFF0),
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 15),
          LayoutBuilder(
            builder: (context, constraints) {
              final width = (constraints.maxWidth - 10) / 2;
              return Wrap(
                spacing: 10,
                runSpacing: 10,
                children: [
                  _metric(
                    'Pending',
                    _money(wallet['pending']),
                    Icons.schedule_rounded,
                    width,
                  ),
                  _metric(
                    'Locked',
                    _money(wallet['locked']),
                    Icons.lock_outline_rounded,
                    width,
                  ),
                  _metric(
                    'Completed',
                    _money(wallet['completed']),
                    Icons.check_circle_outline_rounded,
                    width,
                  ),
                  _metric(
                    'Wallet total',
                    _money(wallet['total']),
                    Icons.wallet_outlined,
                    width,
                  ),
                ],
              );
            },
          ),
          const SizedBox(height: 15),
          SizedBox(
            width: double.infinity,
            child: _primaryButton(
              'Create a campaign',
              Icons.add_rounded,
              _openCampaignFlow,
            ),
          ),
          const SizedBox(height: 23),
          _sectionTitle('Balance history'),
          const SizedBox(height: 10),
          if (history.isEmpty) _emptyCard('No AdLive wallet activity yet.'),
          ...history
              .take(30)
              .map(
                (entry) => Padding(
                  padding: const EdgeInsets.only(bottom: 9),
                  child: _surface(
                    child: Row(
                      children: [
                        Container(
                          width: 36,
                          height: 36,
                          decoration: BoxDecoration(
                            color: AdLiveDesign.mint,
                            borderRadius: BorderRadius.circular(11),
                          ),
                          child: const Icon(
                            Icons.swap_horiz_rounded,
                            size: 19,
                            color: AdLiveDesign.deepGreen,
                          ),
                        ),
                        const SizedBox(width: 11),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                entry['campaign']?.toString() ??
                                    entry['event']?.toString() ??
                                    'Wallet activity',
                                maxLines: 1,
                                overflow: TextOverflow.ellipsis,
                                style: AdLiveDesign.heading(13),
                              ),
                              Text(
                                _date(entry['created_at']),
                                style: AdLiveDesign.body(size: 11),
                              ),
                            ],
                          ),
                        ),
                        const SizedBox(width: 8),
                        Text(
                          _money(_amount(entry['amount_minor']) / 100),
                          style: AdLiveDesign.heading(13),
                        ),
                      ],
                    ),
                  ),
                ),
              ),
        ],
      ),
    );
  }

  Widget _more() => ListView(
    padding: const EdgeInsets.fromLTRB(18, 20, 18, 30),
    children: [
      Text('More', style: AdLiveDesign.heading(26)),
      const SizedBox(height: 5),
      Text(
        'Everything else in your AdLive workspace',
        style: AdLiveDesign.body(),
      ),
      const SizedBox(height: 19),
      _menuItem(
        Icons.tune_rounded,
        'Manage connections',
        'Facebook, Instagram & WhatsApp',
        () => Navigator.of(context).push(
          MaterialPageRoute<void>(
            builder: (_) => const AdLiveConnectionsScreen(),
          ),
        ),
      ),
      _menuItem(
        Icons.insights_rounded,
        'Analytics',
        'Campaign performance and results',
        () => Navigator.of(context).push(
          MaterialPageRoute<void>(builder: (_) => const _InsightsScreen()),
        ),
      ),
      _menuItem(
        Icons.receipt_long_outlined,
        'Invoices',
        'Payments and documents',
        () => Navigator.of(context).push(
          MaterialPageRoute<void>(builder: (_) => const _InvoicesScreen()),
        ),
      ),
      _menuItem(
        Icons.account_circle_outlined,
        'Profile',
        'Your AdLive account',
        () => Navigator.of(
          context,
        ).push(MaterialPageRoute<void>(builder: (_) => const _ProfileScreen())),
      ),
      const SizedBox(height: 16),
      _surface(
        color: AdLiveDesign.mint,
        child: Text(
          'Your AdLive wallet and campaign billing stay separate from Pixel.',
          style: AdLiveDesign.body(color: AdLiveDesign.deepGreen),
        ),
      ),
    ],
  );

  Widget _menuItem(
    IconData icon,
    String title,
    String subtitle,
    VoidCallback onTap,
  ) => Padding(
    padding: const EdgeInsets.only(bottom: 9),
    child: Material(
      color: Colors.white,
      borderRadius: BorderRadius.circular(17),
      child: InkWell(
        borderRadius: BorderRadius.circular(17),
        onTap: onTap,
        child: Container(
          padding: const EdgeInsets.all(13),
          decoration: AdLiveDesign.surface(),
          child: Row(
            children: [
              Container(
                width: 40,
                height: 40,
                decoration: BoxDecoration(
                  color: AdLiveDesign.mint,
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Icon(icon, color: AdLiveDesign.deepGreen, size: 20),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(title, style: AdLiveDesign.heading(14)),
                    Text(subtitle, style: AdLiveDesign.body(size: 11)),
                  ],
                ),
              ),
              const Icon(
                Icons.chevron_right_rounded,
                color: AdLiveDesign.muted,
              ),
            ],
          ),
        ),
      ),
    ),
  );

  Widget _campaignCard(Map<String, dynamic> item) => Material(
    color: Colors.white,
    borderRadius: BorderRadius.circular(18),
    child: InkWell(
      borderRadius: BorderRadius.circular(18),
      onTap: () => _openCampaign(item),
      child: Container(
        padding: const EdgeInsets.all(15),
        decoration: AdLiveDesign.surface(),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Expanded(
                  child: Text(
                    item['name']?.toString() ?? 'Campaign',
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                    style: AdLiveDesign.heading(15),
                  ),
                ),
                const SizedBox(width: 8),
                const Icon(
                  Icons.arrow_outward_rounded,
                  size: 18,
                  color: AdLiveDesign.muted,
                ),
              ],
            ),
            const SizedBox(height: 10),
            Wrap(
              spacing: 8,
              runSpacing: 6,
              crossAxisAlignment: WrapCrossAlignment.center,
              children: [
                Container(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 9,
                    vertical: 5,
                  ),
                  decoration: BoxDecoration(
                    color: AdLiveDesign.mint,
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Text(
                    item['status_label']?.toString() ??
                        item['status']?.toString() ??
                        'Pending',
                    style: const TextStyle(
                      color: AdLiveDesign.deepGreen,
                      fontSize: 10,
                      fontWeight: FontWeight.w700,
                    ),
                  ),
                ),
                if (item['daily_budget'] != null)
                  Text(
                    'Daily ${_money(item['daily_budget'])}',
                    style: AdLiveDesign.body(size: 11),
                  ),
                Text(
                  _date(item['created_at']),
                  style: AdLiveDesign.body(size: 11),
                ),
              ],
            ),
          ],
        ),
      ),
    ),
  );

  Widget _metric(String label, String value, IconData icon, double width) =>
      SizedBox(
        width: width,
        child: _surface(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Icon(icon, color: AdLiveDesign.green, size: 19),
              const SizedBox(height: 10),
              Text(label, style: AdLiveDesign.body(size: 11)),
              const SizedBox(height: 3),
              Text(
                value,
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: AdLiveDesign.heading(18),
              ),
            ],
          ),
        ),
      );
}

class _InsightsScreen extends StatefulWidget {
  const _InsightsScreen();

  @override
  State<_InsightsScreen> createState() => _InsightsScreenState();
}

class _InsightsScreenState extends State<_InsightsScreen> {
  String _range = '7d';
  late Future<Map<String, dynamic>> _future = AdLiveApiService.dashboard(
    range: _range,
  );

  void _load(String range) {
    setState(() {
      _range = range;
      _future = AdLiveApiService.dashboard(range: range);
    });
  }

  @override
  Widget build(BuildContext context) => Scaffold(
    backgroundColor: AdLiveDesign.canvas,
    appBar: AppBar(
      title: const Text('Analytics'),
      backgroundColor: Colors.white,
      surfaceTintColor: Colors.white,
    ),
    body: FutureBuilder<Map<String, dynamic>>(
      future: _future,
      builder: (context, snapshot) {
        if (!snapshot.hasData && !snapshot.hasError) {
          return const Center(
            child: CircularProgressIndicator(color: AdLiveDesign.green),
          );
        }
        if (snapshot.hasError) {
          return _errorState('Analytics could not load.', () => _load(_range));
        }
        final overview = _map(snapshot.data?['overview']);
        final trend = _maps(snapshot.data?['trend']);
        final trendTotal = trend.fold<double>(
          0,
          (total, day) => total + _amount(day['results']),
        );
        final maxResults = trend.fold<double>(
          1,
          (max, day) =>
              _amount(day['results']) > max ? _amount(day['results']) : max,
        );
        return ListView(
          padding: const EdgeInsets.all(18),
          children: [
            Text('Performance', style: AdLiveDesign.heading(25)),
            const SizedBox(height: 5),
            Text(
              'Results for your selected AdLive business',
              style: AdLiveDesign.body(),
            ),
            const SizedBox(height: 18),
            Wrap(
              spacing: 8,
              children: [
                for (final option in const [
                  ('7d', '7 days'),
                  ('30d', '30 days'),
                ])
                  ChoiceChip(
                    label: Text(option.$2),
                    selected: _range == option.$1,
                    onSelected: (_) => _load(option.$1),
                    selectedColor: AdLiveDesign.mint,
                    backgroundColor: Colors.white,
                    checkmarkColor: AdLiveDesign.deepGreen,
                    labelStyle: TextStyle(
                      color: _range == option.$1
                          ? AdLiveDesign.deepGreen
                          : AdLiveDesign.muted,
                      fontWeight: FontWeight.w700,
                    ),
                    side: BorderSide(
                      color: _range == option.$1
                          ? const Color(0xFF8ED8B8)
                          : AdLiveDesign.line,
                    ),
                  ),
              ],
            ),
            const SizedBox(height: 18),
            LayoutBuilder(
              builder: (context, constraints) {
                final width = (constraints.maxWidth - 10) / 2;
                return Wrap(
                  spacing: 10,
                  runSpacing: 10,
                  children: [
                    SizedBox(
                      width: width,
                      child: _surface(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text('Results', style: AdLiveDesign.body(size: 11)),
                            const SizedBox(height: 7),
                            Text(
                              '${overview['primary_results'] ?? 0}',
                              style: AdLiveDesign.heading(21),
                            ),
                          ],
                        ),
                      ),
                    ),
                    SizedBox(
                      width: width,
                      child: _surface(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              'Campaign spend',
                              style: AdLiveDesign.body(size: 11),
                            ),
                            const SizedBox(height: 7),
                            Text(
                              _money(overview['spend']),
                              style: AdLiveDesign.heading(21),
                            ),
                          ],
                        ),
                      ),
                    ),
                  ],
                );
              },
            ),
            const SizedBox(height: 20),
            _surface(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text('Daily results', style: AdLiveDesign.heading(17)),
                  const SizedBox(height: 5),
                  if (trendTotal == 0)
                    Padding(
                      padding: const EdgeInsets.only(top: 9),
                      child: Text(
                        'Daily breakdown is not available for this period.',
                        style: AdLiveDesign.body(),
                      ),
                    )
                  else ...[
                    Text(
                      'Swipe to see every day',
                      style: AdLiveDesign.body(size: 11),
                    ),
                    const SizedBox(height: 19),
                    SizedBox(
                      height: 172,
                      child: SingleChildScrollView(
                        scrollDirection: Axis.horizontal,
                        child: Row(
                          crossAxisAlignment: CrossAxisAlignment.end,
                          children: trend.map((day) {
                            final results = _amount(day['results']);
                            final height = 8 + (results / maxResults * 125);
                            final date = DateTime.tryParse(
                              day['date']?.toString() ?? '',
                            );
                            return SizedBox(
                              width: 38,
                              child: Column(
                                mainAxisAlignment: MainAxisAlignment.end,
                                children: [
                                  Text(
                                    results.toInt().toString(),
                                    style: AdLiveDesign.body(size: 10),
                                  ),
                                  const SizedBox(height: 5),
                                  Container(
                                    width: 19,
                                    height: height,
                                    decoration: BoxDecoration(
                                      color: results > 0
                                          ? AdLiveDesign.green
                                          : AdLiveDesign.line,
                                      borderRadius: BorderRadius.circular(7),
                                    ),
                                  ),
                                  const SizedBox(height: 6),
                                  Text(
                                    date == null ? '—' : '${date.day}',
                                    style: AdLiveDesign.body(size: 10),
                                  ),
                                ],
                              ),
                            );
                          }).toList(),
                        ),
                      ),
                    ),
                  ],
                ],
              ),
            ),
          ],
        );
      },
    ),
  );
}

class _CampaignDetailScreen extends StatefulWidget {
  const _CampaignDetailScreen({required this.id, required this.business});
  final String id;
  final Map<String, dynamic> business;

  @override
  State<_CampaignDetailScreen> createState() => _CampaignDetailScreenState();
}

class _CampaignDetailScreenState extends State<_CampaignDetailScreen> {
  late Future<Map<String, dynamic>> _future = AdLiveApiService.campaign(
    widget.id,
  );

  void _reload() =>
      setState(() => _future = AdLiveApiService.campaign(widget.id));

  @override
  Widget build(BuildContext context) => Scaffold(
    backgroundColor: AdLiveDesign.canvas,
    appBar: AppBar(
      title: const Text('Campaign details'),
      backgroundColor: Colors.white,
      surfaceTintColor: Colors.white,
    ),
    body: FutureBuilder<Map<String, dynamic>>(
      future: _future,
      builder: (context, snapshot) {
        if (!snapshot.hasData && !snapshot.hasError) {
          return const Center(
            child: CircularProgressIndicator(color: AdLiveDesign.green),
          );
        }
        if (snapshot.hasError) {
          return _errorState('Campaign details could not load.', _reload);
        }
        final campaign = _map(snapshot.data?['campaign']);
        final budget = _map(campaign['budget']);
        final performance = _map(campaign['performance']);
        final totals = _map(performance['totals']);
        return ListView(
          padding: const EdgeInsets.all(18),
          children: [
            Text(
              campaign['name']?.toString() ?? 'Campaign',
              style: AdLiveDesign.heading(25),
            ),
            const SizedBox(height: 7),
            Text(
              campaign['status_label']?.toString() ??
                  campaign['status']?.toString() ??
                  'Pending',
              style: AdLiveDesign.body(color: AdLiveDesign.deepGreen),
            ),
            const SizedBox(height: 18),
            _surface(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text('Performance', style: AdLiveDesign.heading(17)),
                  const SizedBox(height: 16),
                  _detailRow('Spend', _money(totals['spend'])),
                  _detailRow('Results', '${totals['primary_results'] ?? 0}'),
                  _detailRow(
                    'Daily budget',
                    _money(budget['daily'] ?? campaign['daily_budget']),
                  ),
                  _detailRow('Created', _date(campaign['created_at'])),
                ],
              ),
            ),
            const SizedBox(height: 15),
            _surface(
              color: AdLiveDesign.mint,
              child: Text(
                'Campaign changes remain subject to AdLive review. Create a new campaign from the native Campaigns tab when you need a new plan.',
                style: AdLiveDesign.body(color: AdLiveDesign.deepGreen),
              ),
            ),
          ],
        );
      },
    ),
  );
}

class _InvoicesScreen extends StatefulWidget {
  const _InvoicesScreen();

  @override
  State<_InvoicesScreen> createState() => _InvoicesScreenState();
}

class _InvoicesScreenState extends State<_InvoicesScreen> {
  late Future<Map<String, dynamic>> _future = AdLiveApiService.billing();

  void _reload() => setState(() => _future = AdLiveApiService.billing());

  @override
  Widget build(BuildContext context) => Scaffold(
    backgroundColor: AdLiveDesign.canvas,
    appBar: AppBar(
      title: const Text('Invoices & payments'),
      backgroundColor: Colors.white,
      surfaceTintColor: Colors.white,
    ),
    body: FutureBuilder<Map<String, dynamic>>(
      future: _future,
      builder: (context, snapshot) {
        if (!snapshot.hasData && !snapshot.hasError) {
          return const Center(
            child: CircularProgressIndicator(color: AdLiveDesign.green),
          );
        }
        if (snapshot.hasError) {
          return _errorState('Invoice history could not load.', _reload);
        }
        final history = _maps(snapshot.data?['wallet_history']);
        final payments = _maps(snapshot.data?['payments']);
        final entries = [...payments, ...history].take(50).toList();
        return RefreshIndicator(
          color: AdLiveDesign.green,
          onRefresh: () async => _reload(),
          child: ListView(
            padding: const EdgeInsets.all(18),
            children: [
              Text('Payment history', style: AdLiveDesign.heading(24)),
              const SizedBox(height: 5),
              Text(
                'AdLive billing remains separate from Pixel.',
                style: AdLiveDesign.body(),
              ),
              const SizedBox(height: 16),
              if (entries.isEmpty)
                _emptyCard('No payment records are available yet.'),
              ...entries.map(
                (entry) => Padding(
                  padding: const EdgeInsets.only(bottom: 9),
                  child: _surface(
                    child: Row(
                      children: [
                        Container(
                          width: 40,
                          height: 40,
                          decoration: BoxDecoration(
                            color: AdLiveDesign.mint,
                            borderRadius: BorderRadius.circular(12),
                          ),
                          child: const Icon(
                            Icons.receipt_long_outlined,
                            color: AdLiveDesign.deepGreen,
                          ),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                entry['campaign']?.toString() ??
                                    entry['description']?.toString() ??
                                    entry['event']?.toString() ??
                                    'AdLive payment',
                                maxLines: 1,
                                overflow: TextOverflow.ellipsis,
                                style: AdLiveDesign.heading(13),
                              ),
                              Text(
                                _date(entry['created_at']),
                                style: AdLiveDesign.body(size: 11),
                              ),
                            ],
                          ),
                        ),
                        Text(
                          _money(
                            entry['amount'] ??
                                (_amount(entry['amount_minor']) / 100),
                          ),
                          style: AdLiveDesign.heading(13),
                        ),
                      ],
                    ),
                  ),
                ),
              ),
            ],
          ),
        );
      },
    ),
  );
}

class _ProfileScreen extends StatefulWidget {
  const _ProfileScreen();

  @override
  State<_ProfileScreen> createState() => _ProfileScreenState();
}

class _ProfileScreenState extends State<_ProfileScreen> {
  final _name = TextEditingController();
  final _email = TextEditingController();
  final _phone = TextEditingController();
  bool _loading = true;
  bool _saving = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    _load();
  }

  @override
  void dispose() {
    _name.dispose();
    _email.dispose();
    _phone.dispose();
    super.dispose();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final response = await AdLiveApiService.profile();
      final user = _map(response['data']).isEmpty
          ? _map(response['user'])
          : _map(response['data']);
      if (!mounted) return;
      setState(() {
        _name.text = user['name']?.toString() ?? '';
        _email.text = user['email']?.toString() ?? '';
        _phone.text = user['phone']?.toString() ?? '';
      });
    } on AdLiveApiException catch (error) {
      if (mounted) setState(() => _error = error.message);
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _save() async {
    setState(() => _saving = true);
    try {
      await AdLiveApiService.updateProfile({
        'name': _name.text.trim(),
        'email': _email.text.trim(),
        'phone': _phone.text.trim(),
      });
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Profile saved.'),
            backgroundColor: AdLiveDesign.deepGreen,
          ),
        );
      }
    } on AdLiveApiException catch (error) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(error.message), backgroundColor: Colors.red),
        );
      }
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  @override
  Widget build(BuildContext context) => Scaffold(
    backgroundColor: AdLiveDesign.canvas,
    appBar: AppBar(
      title: const Text('Profile'),
      backgroundColor: Colors.white,
      surfaceTintColor: Colors.white,
    ),
    body: _loading
        ? const Center(
            child: CircularProgressIndicator(color: AdLiveDesign.green),
          )
        : _error != null
        ? _errorState(_error!, _load)
        : ListView(
            padding: const EdgeInsets.all(18),
            children: [
              Text('Your AdLive profile', style: AdLiveDesign.heading(24)),
              const SizedBox(height: 5),
              Text(
                'Profile changes synchronize with your Pixel account.',
                style: AdLiveDesign.body(),
              ),
              const SizedBox(height: 18),
              _surface(
                child: Column(
                  children: [
                    TextField(
                      controller: _name,
                      decoration: _profileInput('Full name'),
                    ),
                    const SizedBox(height: 12),
                    TextField(
                      controller: _email,
                      keyboardType: TextInputType.emailAddress,
                      decoration: _profileInput('Email address'),
                    ),
                    const SizedBox(height: 12),
                    TextField(
                      controller: _phone,
                      keyboardType: TextInputType.phone,
                      decoration: _profileInput('Phone (optional)'),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 17),
              _primaryButton(
                _saving ? 'Saving…' : 'Save profile',
                Icons.save_outlined,
                _saving ? () {} : _save,
              ),
            ],
          ),
  );

  InputDecoration _profileInput(String label) => InputDecoration(
    labelText: label,
    filled: true,
    fillColor: Colors.white,
    enabledBorder: OutlineInputBorder(
      borderRadius: BorderRadius.circular(14),
      borderSide: const BorderSide(color: AdLiveDesign.line),
    ),
    focusedBorder: OutlineInputBorder(
      borderRadius: BorderRadius.circular(14),
      borderSide: const BorderSide(color: AdLiveDesign.green),
    ),
  );
}

Widget _surface({required Widget child, Color color = Colors.white}) =>
    Container(
      padding: const EdgeInsets.all(16),
      decoration: AdLiveDesign.surface(color: color),
      child: child,
    );

Widget _sectionTitle(String title) =>
    Text(title, style: AdLiveDesign.heading(17));

Widget _emptyCard(String message) => _surface(
  child: Row(
    children: [
      const Icon(Icons.info_outline_rounded, color: AdLiveDesign.green),
      const SizedBox(width: 11),
      Expanded(child: Text(message, style: AdLiveDesign.body())),
    ],
  ),
);

Widget _primaryButton(String label, IconData icon, VoidCallback onPressed) =>
    FilledButton.icon(
      onPressed: onPressed,
      icon: Icon(icon, size: 18),
      label: Text(label),
      style: FilledButton.styleFrom(
        backgroundColor: AdLiveDesign.green,
        foregroundColor: Colors.white,
        padding: const EdgeInsets.symmetric(vertical: 14, horizontal: 16),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
      ),
    );

Widget _detailRow(String label, String value) => Padding(
  padding: const EdgeInsets.only(bottom: 12),
  child: Row(
    children: [
      Expanded(child: Text(label, style: AdLiveDesign.body())),
      Text(value, style: AdLiveDesign.heading(13)),
    ],
  ),
);

Widget _errorState(String message, VoidCallback retry) => Center(
  child: Padding(
    padding: const EdgeInsets.all(24),
    child: Column(
      mainAxisSize: MainAxisSize.min,
      children: [
        const Icon(
          Icons.cloud_off_outlined,
          size: 38,
          color: AdLiveDesign.muted,
        ),
        const SizedBox(height: 11),
        Text(message, textAlign: TextAlign.center, style: AdLiveDesign.body()),
        const SizedBox(height: 15),
        _primaryButton('Try again', Icons.refresh_rounded, retry),
      ],
    ),
  ),
);

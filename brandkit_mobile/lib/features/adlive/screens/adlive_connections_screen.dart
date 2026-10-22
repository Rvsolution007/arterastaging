import 'package:flutter/material.dart';

import '../services/adlive_api_service.dart';
import '../services/native_facebook_auth_service.dart';
import 'adlive_design.dart';

Map<String, dynamic> _connectionMap(dynamic value) =>
    value is Map ? Map<String, dynamic>.from(value) : <String, dynamic>{};

List<Map<String, dynamic>> _connectionMaps(dynamic value) => value is List
    ? value
          .whereType<Map>()
          .map((item) => Map<String, dynamic>.from(item))
          .toList()
    : <Map<String, dynamic>>[];

/// Native, business-scoped Meta account screen. Facebook tokens go straight
/// from the installed Facebook app to AdLive's existing verifier and are never
/// rendered, stored here, or written to logs.
class AdLiveConnectionsScreen extends StatefulWidget {
  const AdLiveConnectionsScreen({
    super.key,
    this.accountMode = 'adlive_managed',
    this.title = 'Connected accounts',
  });

  final String accountMode;
  final String title;

  @override
  State<AdLiveConnectionsScreen> createState() =>
      _AdLiveConnectionsScreenState();
}

class _AdLiveConnectionsScreenState extends State<AdLiveConnectionsScreen> {
  bool _loading = true;
  bool _working = false;
  String? _error;
  Map<String, dynamic> _assets = {};
  String? _selectedAccount;
  String? _selectedPage;
  String? _selectedPhone;

  bool get _clientOwned => widget.accountMode == 'client_owned';

  bool get _connected {
    if (_clientOwned) {
      final status = _connectionMap(
        _assets['connection'],
      )['status']?.toString();
      return status == 'connected' ||
          _connectionMaps(_assets['ad_accounts']).isNotEmpty;
    }
    return _connectionMaps(_assets['pages']).isNotEmpty ||
        (_assets['selected_page_id']?.toString().isNotEmpty ?? false);
  }

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final assets = await AdLiveApiService.metaAssets(widget.accountMode);
      if (!mounted) return;
      setState(() {
        _assets = assets;
        final selection = _connectionMap(assets['selection']);
        _selectedAccount ??= selection['ad_account_id']?.toString();
        _selectedPage ??=
            selection['page_id']?.toString() ??
            assets['selected_page_id']?.toString();
        _selectedPhone ??= selection['whatsapp_phone_number_id']?.toString();
      });
    } on AdLiveApiException catch (error) {
      if (mounted) setState(() => _error = error.message);
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _connect() async {
    setState(() => _working = true);
    try {
      final config = await AdLiveApiService.facebookSdkConfiguration();
      final token = await NativeFacebookAuthService.connect(
        clientToken: config['facebook_client_token']?.toString() ?? '',
      );
      await AdLiveApiService.completeNativeMetaConnection(token);
      if (_clientOwned) {
        try {
          await AdLiveApiService.bridgeClientMetaConnection();
        } on AdLiveApiException {
          // Asset selection below shows the server's actionable state.
        }
      }
      await _load();
      _notice('Facebook connection updated.');
    } catch (error) {
      _notice(error.toString(), failed: true);
    } finally {
      if (mounted) setState(() => _working = false);
    }
  }

  Future<void> _refreshAssets() async {
    setState(() => _working = true);
    try {
      await AdLiveApiService.refreshMetaAssets(
        accountMode: widget.accountMode,
        force: true,
      );
      await _load();
    } catch (error) {
      _notice(error.toString(), failed: true);
    } finally {
      if (mounted) setState(() => _working = false);
    }
  }

  Future<void> _selectManagedPage(Map<String, dynamic> page) async {
    setState(() => _working = true);
    try {
      await AdLiveApiService.selectManagedMetaPage(
        pageId: page['id']?.toString() ?? '',
        pageName: page['name']?.toString() ?? '',
      );
      await _load();
      _notice('Page selected.');
    } catch (error) {
      _notice(error.toString(), failed: true);
    } finally {
      if (mounted) setState(() => _working = false);
    }
  }

  Future<void> _saveClientAssets() async {
    if ((_selectedAccount ?? '').isEmpty || (_selectedPage ?? '').isEmpty) {
      _notice(
        'Choose both an ad account and Facebook Page first.',
        failed: true,
      );
      return;
    }
    setState(() => _working = true);
    try {
      await AdLiveApiService.selectClientMetaAssets(
        adAccountId: _selectedAccount!,
        pageId: _selectedPage!,
        whatsappPhoneNumberId: _selectedPhone,
      );
      await _load();
      _notice('Connected assets saved.');
    } catch (error) {
      _notice(error.toString(), failed: true);
    } finally {
      if (mounted) setState(() => _working = false);
    }
  }

  void _notice(String message, {bool failed = false}) {
    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(message),
        backgroundColor: failed ? Colors.red.shade700 : AdLiveDesign.deepGreen,
      ),
    );
  }

  @override
  Widget build(BuildContext context) => Scaffold(
    backgroundColor: AdLiveDesign.canvas,
    appBar: AppBar(
      title: Text(widget.title),
      backgroundColor: Colors.white,
      surfaceTintColor: Colors.white,
      actions: [
        IconButton(
          onPressed: _working ? null : _refreshAssets,
          icon: const Icon(Icons.refresh_rounded),
        ),
      ],
    ),
    body: _loading
        ? const Center(
            child: CircularProgressIndicator(color: AdLiveDesign.green),
          )
        : RefreshIndicator(
            color: AdLiveDesign.green,
            onRefresh: _load,
            child: ListView(
              padding: const EdgeInsets.fromLTRB(18, 18, 18, 32),
              children: [
                Text(
                  _clientOwned ? 'Your Meta assets' : 'AdLive managed assets',
                  style: AdLiveDesign.heading(24),
                ),
                const SizedBox(height: 6),
                Text(
                  _clientOwned
                      ? 'Choose only the account and Page that this business can use.'
                      : 'Connect Facebook, then choose the Page for this business.',
                  style: AdLiveDesign.body(),
                ),
                const SizedBox(height: 16),
                _connectionCard(
                  icon: Icons.facebook_rounded,
                  title: 'Facebook',
                  detail: _connected
                      ? 'Connected securely'
                      : 'Connect with the Facebook app',
                  active: _connected,
                  action: _working ? null : _connect,
                  actionText: _connected ? 'Reconnect' : 'Connect Facebook',
                ),
                if (_error != null) ...[
                  const SizedBox(height: 12),
                  _message(_error!, error: true),
                ],
                const SizedBox(height: 12),
                if (_connected) ...[
                  if (_clientOwned)
                    _clientAssetSelectors()
                  else
                    _managedPages(),
                ] else
                  _message(
                    'Connect Facebook to load the Pages, Instagram account and WhatsApp numbers available to you.',
                  ),
              ],
            ),
          ),
  );

  Widget _managedPages() {
    final pages = _connectionMaps(_assets['pages']);
    final selected = _assets['selected_page_id']?.toString() ?? '';
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text('Facebook Page', style: AdLiveDesign.heading(17)),
        const SizedBox(height: 9),
        if (pages.isEmpty)
          _message(
            'No eligible Pages are available for this Facebook connection.',
          )
        else
          ...pages.map((page) {
            final chosen = selected == page['id']?.toString();
            return Padding(
              padding: const EdgeInsets.only(bottom: 9),
              child: InkWell(
                borderRadius: BorderRadius.circular(18),
                onTap: _working ? null : () => _selectManagedPage(page),
                child: Container(
                  padding: const EdgeInsets.all(15),
                  decoration: AdLiveDesign.surface(
                    color: chosen ? AdLiveDesign.mint : Colors.white,
                  ),
                  child: Row(
                    children: [
                      Icon(
                        chosen
                            ? Icons.check_circle_rounded
                            : Icons.pages_outlined,
                        color: chosen ? AdLiveDesign.green : AdLiveDesign.muted,
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: Text(
                          page['name']?.toString() ?? 'Facebook Page',
                          style: AdLiveDesign.heading(14),
                        ),
                      ),
                      if (chosen)
                        const Text(
                          'Selected',
                          style: TextStyle(
                            color: AdLiveDesign.deepGreen,
                            fontWeight: FontWeight.w800,
                            fontSize: 12,
                          ),
                        ),
                    ],
                  ),
                ),
              ),
            );
          }),
        const SizedBox(height: 10),
        _statusAsset(
          'Instagram',
          _flowPageValue(pages, selected, 'instagram_account_name'),
          Icons.camera_alt_outlined,
        ),
        _statusAsset(
          'Business WhatsApp',
          _flowPageValue(pages, selected, 'whatsapp_phone'),
          Icons.chat_bubble_outline_rounded,
        ),
      ],
    );
  }

  Widget _clientAssetSelectors() {
    final accounts = _connectionMaps(_assets['ad_accounts']);
    final pages = _connectionMaps(_assets['pages']);
    final phones = _connectionMaps(_assets['whatsapp_numbers']);
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        _selector(
          'Ad account',
          accounts,
          _selectedAccount,
          (value) => setState(() => _selectedAccount = value),
        ),
        const SizedBox(height: 11),
        _selector(
          'Facebook Page',
          pages,
          _selectedPage,
          (value) => setState(() => _selectedPage = value),
        ),
        const SizedBox(height: 11),
        _selector(
          'Business WhatsApp (optional)',
          phones,
          _selectedPhone,
          (value) => setState(() => _selectedPhone = value),
          optional: true,
        ),
        const SizedBox(height: 16),
        SizedBox(
          width: double.infinity,
          child: FilledButton.icon(
            onPressed: _working ? null : _saveClientAssets,
            icon: const Icon(Icons.check_rounded),
            label: const Text('Save connected assets'),
            style: FilledButton.styleFrom(
              backgroundColor: AdLiveDesign.green,
              padding: const EdgeInsets.symmetric(vertical: 14),
            ),
          ),
        ),
      ],
    );
  }

  Widget _selector(
    String label,
    List<Map<String, dynamic>> items,
    String? value,
    ValueChanged<String?> changed, {
    bool optional = false,
  }) {
    final values = items
        .map((item) => item['id']?.toString() ?? '')
        .where((id) => id.isNotEmpty)
        .toSet();
    final selected = values.contains(value) ? value : null;
    return Container(
      padding: const EdgeInsets.fromLTRB(14, 7, 14, 7),
      decoration: AdLiveDesign.surface(),
      child: DropdownButtonFormField<String>(
        key: ValueKey('$label-${selected ?? ''}'),
        initialValue: selected,
        isExpanded: true,
        decoration: InputDecoration(labelText: label, border: InputBorder.none),
        items: [
          if (optional)
            const DropdownMenuItem(value: '', child: Text('Not selected')),
          ...items.map(
            (item) => DropdownMenuItem(
              value: item['id']?.toString(),
              child: Text(
                item['name']?.toString() ??
                    item['label']?.toString() ??
                    item['id']?.toString() ??
                    'Asset',
                overflow: TextOverflow.ellipsis,
              ),
            ),
          ),
        ],
        onChanged: items.isEmpty || _working ? null : changed,
      ),
    );
  }

  Widget _connectionCard({
    required IconData icon,
    required String title,
    required String detail,
    required bool active,
    required VoidCallback? action,
    required String actionText,
  }) => Container(
    padding: const EdgeInsets.all(16),
    decoration: AdLiveDesign.surface(),
    child: Row(
      children: [
        Container(
          width: 44,
          height: 44,
          decoration: BoxDecoration(
            color: active ? AdLiveDesign.mint : const Color(0xFFF1F4F3),
            borderRadius: BorderRadius.circular(14),
          ),
          child: Icon(
            icon,
            color: active ? AdLiveDesign.deepGreen : AdLiveDesign.muted,
          ),
        ),
        const SizedBox(width: 12),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(title, style: AdLiveDesign.heading(15)),
              Text(
                detail,
                style: AdLiveDesign.body(
                  size: 11,
                  color: active ? AdLiveDesign.deepGreen : AdLiveDesign.muted,
                ),
              ),
            ],
          ),
        ),
        TextButton(onPressed: action, child: Text(actionText)),
      ],
    ),
  );

  Widget _statusAsset(String title, String? value, IconData icon) => Padding(
    padding: const EdgeInsets.only(top: 9),
    child: Container(
      padding: const EdgeInsets.all(14),
      decoration: AdLiveDesign.surface(),
      child: Row(
        children: [
          Icon(icon, color: AdLiveDesign.deepGreen, size: 20),
          const SizedBox(width: 11),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(title, style: AdLiveDesign.heading(13)),
                Text(
                  value == null || value.isEmpty ? 'Not connected' : value,
                  style: AdLiveDesign.body(size: 11),
                ),
              ],
            ),
          ),
        ],
      ),
    ),
  );

  Widget _message(String text, {bool error = false}) => Container(
    padding: const EdgeInsets.all(14),
    decoration: BoxDecoration(
      color: error ? const Color(0xFFFFF1F1) : AdLiveDesign.mint,
      borderRadius: BorderRadius.circular(16),
      border: Border.all(
        color: error ? const Color(0xFFFFCCCC) : const Color(0xFFBCEBD5),
      ),
    ),
    child: Text(
      text,
      style: AdLiveDesign.body(
        color: error ? Colors.red.shade800 : AdLiveDesign.deepGreen,
      ),
    ),
  );
}

String? _flowPageValue(
  List<Map<String, dynamic>> pages,
  String selectedId,
  String key,
) {
  final page = pages
      .where((item) => item['id']?.toString() == selectedId)
      .firstOrNull;
  return page?[key]?.toString();
}

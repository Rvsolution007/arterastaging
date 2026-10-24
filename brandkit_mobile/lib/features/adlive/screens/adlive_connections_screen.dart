import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

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
  List<Map<String, dynamic>> _stores = [];
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
      final results = await Future.wait([
        AdLiveApiService.metaAssets(widget.accountMode),
        AdLiveApiService.ecommerceStores(),
      ]);
      final assets = results[0];
      final stores = results[1];
      if (!mounted) return;
      setState(() {
        _assets = assets;
        _stores = _connectionMaps(stores['stores']);
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
                const SizedBox(height: 22),
                _ecommerceStores(),
              ],
            ),
          ),
  );

  Widget _ecommerceStores() => Column(
    crossAxisAlignment: CrossAxisAlignment.start,
    children: [
      Row(
        children: [
          Expanded(
            child: Text('Ecommerce stores', style: AdLiveDesign.heading(17)),
          ),
          TextButton.icon(
            onPressed: _working ? null : _connectStore,
            icon: const Icon(Icons.add_rounded, size: 18),
            label: const Text('Add store'),
          ),
        ],
      ),
      const SizedBox(height: 5),
      Text(
        'Use a verified Shopify, WooCommerce, or custom store for Purchase campaigns.',
        style: AdLiveDesign.body(size: 11),
      ),
      const SizedBox(height: 10),
      if (_stores.isEmpty)
        _message('No ecommerce store is connected for this business.')
      else
        ..._stores.map((store) {
          final connected = store['status']?.toString() == 'connected';
          return Padding(
            padding: const EdgeInsets.only(bottom: 9),
            child: Container(
              padding: const EdgeInsets.all(14),
              decoration: AdLiveDesign.surface(),
              child: Row(
                children: [
                  Container(
                    width: 40,
                    height: 40,
                    decoration: BoxDecoration(
                      color: connected
                          ? AdLiveDesign.mint
                          : const Color(0xFFFFF5E8),
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: Icon(
                      Icons.storefront_outlined,
                      color: connected
                          ? AdLiveDesign.deepGreen
                          : Colors.orange.shade800,
                    ),
                  ),
                  const SizedBox(width: 11),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          store['name']?.toString().isNotEmpty == true
                              ? store['name'].toString()
                              : store['url']?.toString() ?? 'Store',
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: AdLiveDesign.heading(13),
                        ),
                        Text(
                          '${store['platform'] ?? 'custom'} • ${connected ? 'Verified' : 'Needs attention'}',
                          style: AdLiveDesign.body(
                            size: 10.5,
                            color: connected
                                ? AdLiveDesign.deepGreen
                                : Colors.orange.shade900,
                          ),
                        ),
                      ],
                    ),
                  ),
                  PopupMenuButton<String>(
                    enabled: !_working,
                    onSelected: (action) => action == 'verify'
                        ? _verifyStore(store)
                        : _disconnectStore(store),
                    itemBuilder: (_) => [
                      const PopupMenuItem(
                        value: 'verify',
                        child: Text('Verify again'),
                      ),
                      if (connected)
                        const PopupMenuItem(
                          value: 'disconnect',
                          child: Text('Disconnect'),
                        ),
                    ],
                  ),
                ],
              ),
            ),
          );
        }),
    ],
  );

  Future<void> _verifyStore(Map<String, dynamic> store) async {
    setState(() => _working = true);
    try {
      await AdLiveApiService.verifyEcommerceStore(
        store['id']?.toString() ?? '',
      );
      await _load();
      _notice('Store verification refreshed.');
    } on AdLiveApiException catch (error) {
      _notice(error.message, failed: true);
    } finally {
      if (mounted) setState(() => _working = false);
    }
  }

  Future<void> _disconnectStore(Map<String, dynamic> store) async {
    setState(() => _working = true);
    try {
      await AdLiveApiService.disconnectEcommerceStore(
        store['id']?.toString() ?? '',
      );
      await _load();
      _notice('Store disconnected. Existing campaign records were kept.');
    } on AdLiveApiException catch (error) {
      _notice(error.message, failed: true);
    } finally {
      if (mounted) setState(() => _working = false);
    }
  }

  Future<void> _connectStore() async {
    final name = TextEditingController();
    final url = TextEditingController();
    final token = TextEditingController();
    final webhookSecret = TextEditingController();
    final key = TextEditingController();
    final secret = TextEditingController();
    var platform = 'shopify';
    var saving = false;
    String? errorText;
    final response = await showModalBottomSheet<Map<String, dynamic>>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.white,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      builder: (sheetContext) => StatefulBuilder(
        builder: (context, setSheetState) => SafeArea(
          child: SingleChildScrollView(
            padding: EdgeInsets.fromLTRB(
              18,
              16,
              18,
              18 + MediaQuery.viewInsetsOf(context).bottom,
            ),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                Text('Connect store', style: AdLiveDesign.heading(20)),
                const SizedBox(height: 5),
                Text(
                  'Credentials are encrypted by AdLive and never shown after verification.',
                  style: AdLiveDesign.body(size: 11),
                ),
                const SizedBox(height: 13),
                DropdownButtonFormField<String>(
                  initialValue: platform,
                  isExpanded: true,
                  decoration: _storeInput('Platform'),
                  items: const [
                    DropdownMenuItem(value: 'shopify', child: Text('Shopify')),
                    DropdownMenuItem(
                      value: 'woocommerce',
                      child: Text('WooCommerce / WordPress'),
                    ),
                    DropdownMenuItem(
                      value: 'custom',
                      child: Text('Custom website'),
                    ),
                  ],
                  onChanged: saving
                      ? null
                      : (value) =>
                            setSheetState(() => platform = value ?? 'shopify'),
                ),
                const SizedBox(height: 9),
                TextField(
                  controller: name,
                  decoration: _storeInput('Store name'),
                ),
                const SizedBox(height: 9),
                TextField(
                  controller: url,
                  keyboardType: TextInputType.url,
                  decoration: _storeInput('HTTPS store URL'),
                ),
                if (platform == 'shopify') ...[
                  const SizedBox(height: 9),
                  TextField(
                    controller: token,
                    obscureText: true,
                    enableSuggestions: false,
                    autocorrect: false,
                    decoration: _storeInput('Shopify Admin API token'),
                  ),
                  const SizedBox(height: 9),
                  TextField(
                    controller: webhookSecret,
                    obscureText: true,
                    enableSuggestions: false,
                    autocorrect: false,
                    decoration: _storeInput('Shopify app API secret'),
                  ),
                ],
                if (platform == 'woocommerce') ...[
                  const SizedBox(height: 9),
                  TextField(
                    controller: key,
                    obscureText: true,
                    enableSuggestions: false,
                    autocorrect: false,
                    decoration: _storeInput('Consumer key'),
                  ),
                  const SizedBox(height: 9),
                  TextField(
                    controller: secret,
                    obscureText: true,
                    enableSuggestions: false,
                    autocorrect: false,
                    decoration: _storeInput('Consumer secret'),
                  ),
                ],
                if (errorText != null) ...[
                  const SizedBox(height: 9),
                  Text(
                    errorText!,
                    style: AdLiveDesign.body(
                      size: 11,
                      color: Colors.red.shade700,
                    ),
                  ),
                ],
                const SizedBox(height: 14),
                FilledButton.icon(
                  onPressed: saving
                      ? null
                      : () async {
                          setSheetState(() {
                            saving = true;
                            errorText = null;
                          });
                          try {
                            final response =
                                await AdLiveApiService.connectEcommerceStore(
                                  platform: platform,
                                  storeName: name.text.trim(),
                                  storeUrl: url.text.trim(),
                                  accessToken: token.text.trim(),
                                  apiKey: key.text.trim(),
                                  apiSecret: secret.text.trim(),
                                  webhookSecret: webhookSecret.text.trim(),
                                );
                            if (sheetContext.mounted) {
                              Navigator.of(sheetContext).pop(response);
                            }
                          } on AdLiveApiException catch (error) {
                            if (sheetContext.mounted) {
                              setSheetState(() {
                                errorText = error.message;
                                saving = false;
                              });
                            }
                          }
                        },
                  icon: const Icon(Icons.verified_user_outlined),
                  label: Text(saving ? 'Verifying…' : 'Connect & verify'),
                  style: FilledButton.styleFrom(
                    backgroundColor: AdLiveDesign.green,
                    padding: const EdgeInsets.symmetric(vertical: 14),
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
    name.dispose();
    url.dispose();
    token.dispose();
    webhookSecret.dispose();
    key.dispose();
    secret.dispose();
    if (response != null) {
      await _load();
      await _showStoreIntegration(_connectionMap(response['integration']));
      _notice('Store connected and verified.');
    }
  }

  Future<void> _showStoreIntegration(Map<String, dynamic> integration) async {
    final webhookUrl = integration['webhook_url']?.toString() ?? '';
    final signingSecret = integration['webhook_secret']?.toString() ?? '';
    if (!mounted || webhookUrl.isEmpty || signingSecret.isEmpty) return;
    await showDialog<void>(
      context: context,
      builder: (dialogContext) => AlertDialog(
        title: const Text('Purchase webhook'),
        content: SingleChildScrollView(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                'Configure an order-paid webhook. This signing secret is shown for this setup.',
                style: AdLiveDesign.body(size: 11),
              ),
              const SizedBox(height: 12),
              Text('Webhook URL', style: AdLiveDesign.heading(12)),
              SelectableText(webhookUrl, style: AdLiveDesign.body(size: 11)),
              TextButton.icon(
                onPressed: () =>
                    Clipboard.setData(ClipboardData(text: webhookUrl)),
                icon: const Icon(Icons.copy_rounded, size: 16),
                label: const Text('Copy URL'),
              ),
              const SizedBox(height: 8),
              Text('Signing secret', style: AdLiveDesign.heading(12)),
              SelectableText(signingSecret, style: AdLiveDesign.body(size: 11)),
              TextButton.icon(
                onPressed: () =>
                    Clipboard.setData(ClipboardData(text: signingSecret)),
                icon: const Icon(Icons.copy_rounded, size: 16),
                label: const Text('Copy secret'),
              ),
            ],
          ),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(dialogContext).pop(),
            child: const Text('Done'),
          ),
        ],
      ),
    );
  }

  InputDecoration _storeInput(String label) => InputDecoration(
    labelText: label,
    isDense: true,
    filled: true,
    fillColor: Colors.white,
    border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
    enabledBorder: OutlineInputBorder(
      borderRadius: BorderRadius.circular(12),
      borderSide: const BorderSide(color: AdLiveDesign.line),
    ),
    focusedBorder: OutlineInputBorder(
      borderRadius: BorderRadius.circular(12),
      borderSide: const BorderSide(color: AdLiveDesign.green),
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

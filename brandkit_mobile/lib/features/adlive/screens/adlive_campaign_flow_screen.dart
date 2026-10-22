import 'dart:async';

import 'package:file_picker/file_picker.dart';
import 'package:flutter/material.dart';
import 'package:razorpay_flutter/razorpay_flutter.dart';

import '../services/adlive_api_service.dart';
import 'adlive_connections_screen.dart';
import 'adlive_design.dart';

Map<String, dynamic> _flowMap(dynamic value) =>
    value is Map ? Map<String, dynamic>.from(value) : <String, dynamic>{};

List<Map<String, dynamic>> _flowMaps(dynamic value) => value is List
    ? value
          .whereType<Map>()
          .map((item) => Map<String, dynamic>.from(item))
          .toList()
    : <Map<String, dynamic>>[];

String _flowText(dynamic value) => value?.toString().trim() ?? '';

double _flowAmount(dynamic value) => value is num
    ? value.toDouble()
    : double.tryParse(value?.toString() ?? '') ?? 0;

/// Three native campaign screens. The server still normalizes all planning
/// data, quotes the plan, validates payment, stores creative files and gates
/// Meta activation. This screen never creates a Meta campaign directly.
class AdLiveCampaignFlowScreen extends StatefulWidget {
  const AdLiveCampaignFlowScreen({super.key});

  @override
  State<AdLiveCampaignFlowScreen> createState() =>
      _AdLiveCampaignFlowScreenState();
}

class _AdLiveCampaignFlowScreenState extends State<AdLiveCampaignFlowScreen> {
  final _campaignName = TextEditingController();
  final _businessName = TextEditingController();
  final _product = TextEditingController();
  final _details = TextEditingController();
  final _website = TextEditingController();
  final _budget = TextEditingController(text: '500');
  final _creativeBody = TextEditingController();
  final _headline = TextEditingController();
  final _description = TextEditingController();
  final _locationQuery = TextEditingController();
  final Razorpay _razorpay = Razorpay();

  bool _loading = true;
  bool _working = false;
  int _step = 0;
  String? _error;
  String? _draftId;
  String? _campaignId;
  String _goal = 'whatsapp';
  String _creativeMode = 'self_upload';
  String _format = 'single_image';
  String? _packageId;
  String? _customerType;
  String? _industry;
  String? _serviceCode;
  int _creativeQuantity = 1;
  String? _mediaPath;
  String? _whatsAppPhoneId;
  Map<String, dynamic> _bootstrap = {};
  Map<String, dynamic> _quote = {};
  Map<String, dynamic> _audience = {};
  Map<String, dynamic> _creative = {};
  Map<String, dynamic> _meta = {};
  List<Map<String, dynamic>> _whatsAppNumbers = [];
  final Map<String, TextEditingController> _requirementControllers = {};
  final Map<String, List<String>> _requirementFiles = {};
  final List<_CarouselCard> _carouselCards = [_CarouselCard(), _CarouselCard()];
  List<Map<String, dynamic>> _industries = [];
  List<Map<String, dynamic>> _locations = [];
  Map<String, dynamic>? _selectedLocation;
  Timer? _locationDebounce;

  @override
  void initState() {
    super.initState();
    _razorpay.on(Razorpay.EVENT_PAYMENT_SUCCESS, _onPaymentSuccess);
    _razorpay.on(Razorpay.EVENT_PAYMENT_ERROR, _onPaymentError);
    _load();
  }

  @override
  void dispose() {
    _razorpay.clear();
    _locationDebounce?.cancel();
    for (final controller in [
      _campaignName,
      _businessName,
      _product,
      _details,
      _website,
      _budget,
      _creativeBody,
      _headline,
      _description,
      _locationQuery,
    ]) {
      controller.dispose();
    }
    for (final controller in _requirementControllers.values) {
      controller.dispose();
    }
    for (final card in _carouselCards) {
      card.dispose();
    }
    super.dispose();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final data = await AdLiveApiService.campaignWorkflowBootstrap();
      final defaults = _flowMap(data['business_defaults']);
      final packages = _flowMaps(data['packages']);
      final services = _flowMaps(data['creative_services']);
      if (!mounted) return;
      setState(() {
        _bootstrap = data;
        _packageId ??= packages.isEmpty
            ? null
            : _flowText(packages.first['id']);
        _serviceCode ??= services.isEmpty
            ? null
            : _flowText(services.first['code']);
        _businessName.text = _businessName.text.isNotEmpty
            ? _businessName.text
            : _flowText(defaults['business_name'] ?? data['business']?['name']);
        _product.text = _product.text.isNotEmpty
            ? _product.text
            : _flowText(defaults['product_name']);
        _details.text = _details.text.isNotEmpty
            ? _details.text
            : _flowText(defaults['product_summary']);
        _website.text = _website.text.isNotEmpty
            ? _website.text
            : _flowText(defaults['website_url']);
      });
    } on AdLiveApiException catch (error) {
      if (mounted) setState(() => _error = error.message);
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _loadIndustries(String? type) async {
    setState(() {
      _customerType = type;
      _industry = null;
      _industries = [];
    });
    if (type == null || type.isEmpty) return;
    try {
      final data = await AdLiveApiService.campaignCatalogueOptions(
        kind: 'industry',
        catalogue: _catalogue(),
      );
      if (mounted) setState(() => _industries = _flowMaps(data['options']));
    } on AdLiveApiException catch (error) {
      _show(error.message, error: true);
    }
  }

  Map<String, dynamic> _catalogue() => {
    'schema_version': 1,
    'selections': {
      'customer_type': _customerType == null ? <String>[] : [_customerType],
      'industry': _industry == null ? <String>[] : [_industry],
      'role': <String>[],
      'subcategory': <String>[],
      'product': <String>[],
    },
    'states': {
      'customer_type': _customerType == null ? 'unselected' : 'selected',
      'industry': _industry == null ? 'unselected' : 'selected',
      'role': 'skipped',
      'subcategory': 'skipped',
      'product': 'skipped',
    },
  };

  Map<String, dynamic> _payload() {
    final selected = _selectedLocation;
    final geo = <String, dynamic>{};
    if (selected != null) {
      final type = _flowText(selected['type']).toLowerCase();
      const buckets = {
        'country': 'countries',
        'region': 'regions',
        'city': 'cities',
        'zip': 'zips',
        'geo_market': 'geo_markets',
        'neighborhood': 'neighborhoods',
        'subcity': 'subcities',
        'subneighborhood': 'subneighborhoods',
        'place': 'places',
        'political_district': 'political_districts',
        'electoral_district': 'electoral_districts',
        'large_geo_area': 'large_geo_areas',
        'medium_geo_area': 'medium_geo_areas',
        'small_geo_area': 'small_geo_areas',
      };
      final bucket = buckets[type];
      if (bucket == 'countries') {
        geo[bucket!] = [
          _flowText(selected['country_code']).isEmpty
              ? _flowText(selected['key'])
              : _flowText(selected['country_code']).toUpperCase(),
        ];
      } else if (bucket != null) {
        geo[bucket] = [
          {
            'key': _flowText(selected['key']),
            'name': _flowText(selected['name']),
          },
        ];
      }
    }
    final label = selected == null ? <String>[] : [_flowText(selected['name'])];
    return {
      'flow_version': 2,
      'package_id': _packageId,
      'campaign_name': _campaignName.text.trim(),
      'business': {
        'business_name': _businessName.text.trim(),
        'product_name': _product.text.trim(),
        'product_summary': _details.text.trim(),
        'website_url': _website.text.trim(),
      },
      'goal': _goal,
      'audience': {
        'target_customer': _customerType ?? '',
        'locations': label,
        'industry': _industry ?? '',
        'buyer_type': _customerType ?? '',
        'smart_profile': {
          'business_name': _businessName.text.trim(),
          'product_name': _product.text.trim(),
          'product_details': _details.text.trim(),
          'business_model': 'b2b',
          'buyer_type': _customerType == null ? <String>[] : [_customerType],
          'target_industry': _industry == null ? <String>[] : [_industry],
          'catalogue': _catalogue(),
        },
        'admin_match': _audience.isEmpty
            ? null
            : {
                'matched_category_id': _audience['matched_category_id'],
                'name': _flowMap(
                  _audience['audience_match'],
                )['matched_category'],
                'confidence': _flowMap(
                  _audience['audience_match'],
                )['confidence'],
              },
      },
      'budget': {
        'type': 'daily',
        'daily_budget': _budget.text.trim(),
        'lifetime_budget': '',
        'start_date': DateTime.now().toIso8601String().substring(0, 10),
        'end_date': '',
      },
      'creative': {
        'mode': _creativeMode,
        'service_code': _serviceCode,
        'quantity': _creativeQuantity,
      },
      'targeting_overrides': {
        'location': selected == null
            ? {'mode': 'inherit'}
            : {'mode': 'custom', 'value': geo, 'labels': label},
        'age': {'mode': 'inherit'},
        'gender': {'mode': 'inherit'},
        'languages': {'mode': 'inherit'},
      },
      'planner_state': {
        'step_key': [
          'package_audience',
          'creative_payment',
          'creative_meta',
        ][_step],
        'meta_locations': selected == null ? [] : [selected],
        'audience_match': _audience,
      },
    };
  }

  Future<void> _previewAudience() async {
    setState(() => _working = true);
    try {
      final data = await AdLiveApiService.previewCampaignAudience(
        _flowMap(_payload()['audience'])['smart_profile'],
      );
      if (mounted) setState(() => _audience = data);
      if (data['is_unmatched'] == true) {
        _show(_flowText(data['error']), error: true);
      }
    } on AdLiveApiException catch (error) {
      _show(error.message, error: true);
    } finally {
      if (mounted) setState(() => _working = false);
    }
  }

  Future<void> _searchLocations(String value) async {
    _locationDebounce?.cancel();
    if (value.trim().length < 2) {
      setState(() => _locations = []);
      return;
    }
    _locationDebounce = Timer(const Duration(milliseconds: 450), () async {
      try {
        final data = await AdLiveApiService.searchCampaignLocations(
          value.trim(),
        );
        if (mounted && _locationQuery.text.trim() == value.trim()) {
          setState(() => _locations = _flowMaps(data['data']));
        }
      } on AdLiveApiException catch (error) {
        if (mounted) _show(error.message, error: true);
      }
    });
  }

  Future<void> _continueFromPlan() async {
    setState(() => _working = true);
    try {
      final data = await AdLiveApiService.saveCampaignDraft(
        draftId: _draftId,
        currentStep: 1,
        payload: _payload(),
        finalize: true,
      );
      if (!mounted) return;
      setState(() {
        _draftId = _flowText(data['id']);
        _quote = _flowMap(data['quote']);
        _step = 1;
      });
    } on AdLiveApiException catch (error) {
      _show(error.message, error: true);
    } finally {
      if (mounted) setState(() => _working = false);
    }
  }

  Future<void> _pay() async {
    setState(() => _working = true);
    try {
      final draft = await AdLiveApiService.saveCampaignDraft(
        draftId: _draftId,
        currentStep: 2,
        payload: _payload(),
        finalize: true,
      );
      _draftId = _flowText(draft['id']);
      final checkout = await AdLiveApiService.prepareCampaignCheckout(
        _draftId!,
      );
      _campaignId = _flowText(checkout['campaign_id']);
      if (checkout['paid'] == true ||
          _flowAmount(checkout['amount_due']) <= 0) {
        await _afterPaid();
        return;
      }
      final order = await AdLiveApiService.createCampaignPaymentOrder(
        _campaignId!,
      );
      if (order['settled'] == true) {
        await _afterPaid();
        return;
      }
      final key = _flowText(order['key']);
      final orderId = _flowText(order['order_id']);
      if (key.isEmpty || orderId.isEmpty) {
        throw const AdLiveApiException(
          'Payment gateway is not ready. Please try again.',
        );
      }
      _razorpay.open({
        'key': key,
        'amount': order['amount'],
        'order_id': orderId,
        'name': 'Artera AdLive',
        'description': _campaignName.text.trim(),
      });
    } on AdLiveApiException catch (error) {
      _show(error.message, error: true);
    } finally {
      if (mounted) setState(() => _working = false);
    }
  }

  Future<void> _onPaymentSuccess(PaymentSuccessResponse payment) async {
    if ((_campaignId ?? '').isEmpty) return;
    setState(() => _working = true);
    try {
      await AdLiveApiService.verifyCampaignPayment(
        campaignId: _campaignId!,
        orderId: payment.orderId ?? '',
        paymentId: payment.paymentId ?? '',
        signature: payment.signature ?? '',
      );
      await _afterPaid();
    } on AdLiveApiException catch (error) {
      _show(error.message, error: true);
    } finally {
      if (mounted) setState(() => _working = false);
    }
  }

  void _onPaymentError(PaymentFailureResponse payment) =>
      _show(payment.message ?? 'Payment was cancelled.', error: true);

  Future<void> _afterPaid() async {
    if ((_campaignId ?? '').isEmpty) return;
    final creative = await AdLiveApiService.campaignCreative(_campaignId!);
    final meta = await AdLiveApiService.campaignMetaSetup(_campaignId!);
    if (!mounted) return;
    setState(() {
      _creative = creative;
      _meta = meta;
      _step = 2;
    });
    _prepareRequirementControllers();
    await _loadWhatsAppNumbers();
    _show('Payment confirmed. Complete creative and account setup.');
  }

  void _prepareRequirementControllers() {
    final brief = _flowMap(_creative['creative_brief']);
    final quantity = (brief['quantity'] as num?)?.toInt() ?? 1;
    for (var item = 0; item < quantity; item++) {
      for (final field in _flowMaps(brief['requirement_schema'])) {
        final key = _flowText(field['key']);
        if (key.isNotEmpty && _flowText(field['type']) != 'file') {
          _requirementControllers.putIfAbsent(
            '$item.$key',
            TextEditingController.new,
          );
        }
      }
    }
  }

  Future<void> _loadWhatsAppNumbers() async {
    if (_flowText(_meta['connection_mode']) != 'adlive_managed') return;
    try {
      final assets = await AdLiveApiService.metaAssets('adlive_managed');
      final selectedId = _flowText(assets['selected_page_id']);
      Map<String, dynamic>? page;
      for (final item in _flowMaps(assets['pages'])) {
        if (_flowText(item['id']) == selectedId) {
          page = item;
          break;
        }
      }
      final numbers = _flowMaps(page?['whatsapp_numbers']);
      if (mounted) {
        setState(() {
          _whatsAppNumbers = numbers;
          if (!_whatsAppNumbers.any(
            (item) => _flowText(item['id']) == _whatsAppPhoneId,
          )) {
            _whatsAppPhoneId = null;
          }
        });
      }
    } on AdLiveApiException {
      // The confirmation endpoint remains authoritative and shows a safe
      // message if current assets cannot be loaded.
    }
  }

  Future<void> _pickMedia() async {
    final result = await FilePicker.platform.pickFiles(
      type: FileType.media,
      withData: false,
    );
    final path = result?.files.single.path;
    if (path != null && mounted) setState(() => _mediaPath = path);
  }

  Future<void> _saveCreative() async {
    if ((_campaignId ?? '').isEmpty) return;
    setState(() => _working = true);
    try {
      final fields = <String, String>{
        'ad_format': _format,
        'placements[0]': 'facebook',
        'placements[1]': 'instagram',
        'ad_body': _creativeBody.text.trim(),
        'ad_headline': _headline.text.trim(),
        'ad_description': _description.text.trim(),
        'ad_link_url': _goal == 'website' ? _website.text.trim() : '',
        'cta_type': _goal == 'whatsapp' ? 'WHATSAPP_MESSAGE' : 'LEARN_MORE',
      };
      if (_format == 'carousel') {
        for (var index = 0; index < _carouselCards.length; index++) {
          final card = _carouselCards[index];
          fields['carousel_cards[$index][headline]'] = card.headline.text
              .trim();
          fields['carousel_cards[$index][description]'] = card.description.text
              .trim();
        }
      }
      final files = <String, String>{
        if (_format != 'carousel' && _mediaPath != null)
          'creative_file': _mediaPath!,
        if (_format == 'carousel')
          for (var index = 0; index < _carouselCards.length; index++)
            if (_carouselCards[index].imagePath != null)
              'carousel_cards[$index][image]': _carouselCards[index].imagePath!,
      };
      await AdLiveApiService.uploadCampaignCreative(
        campaignId: _campaignId!,
        fields: fields,
        files: files,
      );
      await _afterPaid();
      _show('Creative saved.');
    } on AdLiveApiException catch (error) {
      _show(error.message, error: true);
    } finally {
      if (mounted) setState(() => _working = false);
    }
  }

  Future<void> _pickCarouselImage(int index) async {
    final result = await FilePicker.platform.pickFiles(
      type: FileType.image,
      withData: false,
    );
    final path = result?.files.single.path;
    if (path != null && mounted) {
      setState(() => _carouselCards[index].imagePath = path);
    }
  }

  Future<void> _pickRequirementFile(
    int item,
    Map<String, dynamic> field,
  ) async {
    final key = _flowText(field['key']);
    if (key.isEmpty) return;
    final result = await FilePicker.platform.pickFiles(
      type: FileType.custom,
      allowedExtensions: const ['jpg', 'jpeg', 'png', 'webp', 'pdf'],
      allowMultiple: field['multiple'] == true,
      withData: false,
    );
    final paths = result?.files
        .map((file) => file.path)
        .whereType<String>()
        .toList();
    if (paths != null && paths.isNotEmpty && mounted) {
      setState(() => _requirementFiles['$item.$key'] = paths);
    }
  }

  Future<void> _submitRequirements() async {
    if ((_campaignId ?? '').isEmpty) return;
    final brief = _flowMap(_creative['creative_brief']);
    final schema = _flowMaps(brief['requirement_schema']);
    final quantity = (brief['quantity'] as num?)?.toInt() ?? 1;
    final fields = <String, String>{};
    final files = <String, List<String>>{};
    for (var item = 0; item < quantity; item++) {
      for (final field in schema) {
        final key = _flowText(field['key']);
        if (key.isEmpty) continue;
        final input = 'requirements[$item][$key]';
        if (_flowText(field['type']) == 'file') {
          final selected = _requirementFiles['$item.$key'];
          if (selected != null) files[input] = selected;
        } else {
          fields[input] =
              _requirementControllers['$item.$key']?.text.trim() ?? '';
        }
      }
    }
    setState(() => _working = true);
    try {
      await AdLiveApiService.submitCampaignRequirements(
        campaignId: _campaignId!,
        fields: fields,
        files: files,
      );
      await _afterPaid();
      _show('Design requirements sent to the AdLive team.');
    } on AdLiveApiException catch (error) {
      _show(error.message, error: true);
    } finally {
      if (mounted) setState(() => _working = false);
    }
  }

  Future<void> _openConnections() async {
    final mode = _flowText(_meta['account_mode']);
    await Navigator.of(context).push(
      MaterialPageRoute<void>(
        builder: (_) => AdLiveConnectionsScreen(
          accountMode: mode == 'client_owned'
              ? 'client_owned'
              : 'adlive_managed',
          title: 'Meta setup',
        ),
      ),
    );
    if ((_campaignId ?? '').isNotEmpty) {
      try {
        final data = await AdLiveApiService.campaignMetaSetup(_campaignId!);
        if (mounted) setState(() => _meta = data);
        await _loadWhatsAppNumbers();
      } on AdLiveApiException catch (error) {
        _show(error.message, error: true);
      }
    }
  }

  Future<void> _confirmMeta() async {
    if ((_campaignId ?? '').isEmpty) return;
    setState(() => _working = true);
    try {
      await AdLiveApiService.confirmCampaignMetaSetup(
        campaignId: _campaignId!,
        whatsappPhoneNumberId: _whatsAppPhoneId,
      );
      final data = await AdLiveApiService.campaignMetaSetup(_campaignId!);
      if (mounted) setState(() => _meta = data);
      _show('Meta setup saved for the campaign review queue.');
    } on AdLiveApiException catch (error) {
      _show(error.message, error: true);
    } finally {
      if (mounted) setState(() => _working = false);
    }
  }

  void _show(String message, {bool error = false}) {
    if (!mounted || message.isEmpty) return;
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(message),
        backgroundColor: error ? Colors.red.shade700 : AdLiveDesign.deepGreen,
      ),
    );
  }

  @override
  Widget build(BuildContext context) => Scaffold(
    backgroundColor: AdLiveDesign.canvas,
    appBar: AppBar(
      title: const Text('New campaign'),
      backgroundColor: Colors.white,
      surfaceTintColor: Colors.white,
    ),
    body: _loading
        ? const Center(
            child: CircularProgressIndicator(color: AdLiveDesign.green),
          )
        : _error != null
        ? _retry()
        : Column(
            children: [
              _steps(),
              Expanded(
                child: AnimatedSwitcher(
                  duration: const Duration(milliseconds: 180),
                  child: KeyedSubtree(
                    key: ValueKey(_step),
                    child: _step == 0
                        ? _plan()
                        : _step == 1
                        ? _payment()
                        : _finalize(),
                  ),
                ),
              ),
            ],
          ),
  );

  Widget _steps() => Container(
    color: Colors.white,
    padding: const EdgeInsets.fromLTRB(18, 14, 18, 13),
    child: Row(
      children: List.generate(
        3,
        (index) => Expanded(
          child: Row(
            children: [
              Expanded(
                child: Column(
                  children: [
                    CircleAvatar(
                      radius: 13,
                      backgroundColor: index <= _step
                          ? AdLiveDesign.green
                          : AdLiveDesign.line,
                      child: Text(
                        '${index + 1}',
                        style: TextStyle(
                          color: index <= _step
                              ? Colors.white
                              : AdLiveDesign.muted,
                          fontSize: 11,
                          fontWeight: FontWeight.w800,
                        ),
                      ),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      ['Plan', 'Payment', 'Creative & Meta'][index],
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: AdLiveDesign.body(
                        size: 10,
                        color: index == _step
                            ? AdLiveDesign.deepGreen
                            : AdLiveDesign.muted,
                      ),
                    ),
                  ],
                ),
              ),
              if (index < 2)
                Container(
                  height: 2,
                  width: 18,
                  color: index < _step ? AdLiveDesign.green : AdLiveDesign.line,
                ),
            ],
          ),
        ),
      ),
    ),
  );

  Widget _plan() {
    final packages = _flowMaps(_bootstrap['packages']);
    final customerTypes = _flowMaps(
      _flowMap(_bootstrap['catalogue'])['customer_types'],
    );
    return ListView(
      padding: const EdgeInsets.fromLTRB(18, 18, 18, 32),
      children: [
        Text('Package & audience', style: AdLiveDesign.heading(24)),
        const SizedBox(height: 5),
        Text(
          'Set your campaign essentials and smart audience in one place.',
          style: AdLiveDesign.body(),
        ),
        const SizedBox(height: 17),
        _label('Campaign package'),
        Wrap(
          spacing: 8,
          runSpacing: 8,
          children: packages
              .map(
                (item) => _choice(
                  _flowText(item['name']),
                  _packageId == _flowText(item['id']),
                  () => setState(() => _packageId = _flowText(item['id'])),
                ),
              )
              .toList(),
        ),
        const SizedBox(height: 15),
        _field(_campaignName, 'Campaign name'),
        const SizedBox(height: 10),
        _goalPicker(),
        const SizedBox(height: 15),
        _section('Business & audience', [
          _field(_businessName, 'Business name'),
          const SizedBox(height: 10),
          _field(_product, 'Product or service'),
          const SizedBox(height: 10),
          _field(_details, 'Offer details (optional)', maxLines: 2),
          if (_goal == 'website') ...[
            const SizedBox(height: 10),
            _field(_website, 'Website URL'),
          ],
          const SizedBox(height: 14),
          _label('Target customer'),
          _dropdown(
            'Choose customer type',
            customerTypes,
            _customerType,
            (value) => _loadIndustries(value),
          ),
          const SizedBox(height: 10),
          _label('Target industry'),
          _dropdown(
            'Choose industry',
            _industries,
            _industry,
            (value) => setState(() => _industry = value),
          ),
          const SizedBox(height: 12),
          OutlinedButton.icon(
            onPressed: _working ? null : _previewAudience,
            icon: const Icon(Icons.auto_awesome_rounded),
            label: const Text('Generate Smart Audience'),
          ),
          if (_audience.isNotEmpty)
            Padding(
              padding: const EdgeInsets.only(top: 10),
              child: _note(
                _flowText(_audience['matched_description']).isEmpty
                    ? 'Smart audience is ready.'
                    : _flowText(_audience['matched_description']),
              ),
            ),
        ]),
        const SizedBox(height: 14),
        _section('Location & targeting', [
          TextField(
            controller: _locationQuery,
            onChanged: _searchLocations,
            decoration: _input(
              'Search Meta location',
              icon: Icons.search_rounded,
            ),
          ),
          if (_selectedLocation != null)
            Padding(
              padding: const EdgeInsets.only(top: 9),
              child: Chip(
                label: Text(_flowText(_selectedLocation!['name'])),
                onDeleted: () => setState(() => _selectedLocation = null),
                backgroundColor: AdLiveDesign.mint,
              ),
            ),
          ..._locations
              .take(5)
              .map(
                (place) => ListTile(
                  contentPadding: EdgeInsets.zero,
                  title: Text(_flowText(place['name'])),
                  subtitle: Text(_flowText(place['type'])),
                  trailing: const Icon(Icons.add_circle_outline_rounded),
                  onTap: () => setState(() {
                    _selectedLocation = place;
                    _locations = [];
                    _locationQuery.clear();
                  }),
                ),
              ),
          const SizedBox(height: 8),
          Text(
            'Age, gender and language stay on the approved Smart Audience unless changed during review.',
            style: AdLiveDesign.body(size: 11),
          ),
        ]),
        const SizedBox(height: 15),
        _label('Daily Meta ads budget'),
        _field(_budget, 'Amount in ₹', keyboard: TextInputType.number),
        const SizedBox(height: 20),
        _mainButton(
          'Continue to creative & payment',
          Icons.arrow_forward_rounded,
          _working ? null : _continueFromPlan,
        ),
      ],
    );
  }

  Widget _payment() {
    final services = _flowMaps(_bootstrap['creative_services']);
    final total = _flowAmount(
      _quote['total'] ?? _quote['service_charge'] ?? _quote['payable'],
    );
    return ListView(
      padding: const EdgeInsets.fromLTRB(18, 18, 18, 32),
      children: [
        Text('Creative & payment', style: AdLiveDesign.heading(24)),
        const SizedBox(height: 5),
        Text(
          'Your selection updates the verified campaign estimate.',
          style: AdLiveDesign.body(),
        ),
        const SizedBox(height: 16),
        _section('Creative preference', [
          _radioChoice(
            'I will upload my own creative',
            _creativeMode == 'self_upload',
            () => setState(() => _creativeMode = 'self_upload'),
          ),
          const SizedBox(height: 8),
          _radioChoice(
            'Ask AdLive to design it',
            _creativeMode == 'adlive_design',
            () => setState(() => _creativeMode = 'adlive_design'),
          ),
          if (_creativeMode == 'adlive_design') ...[
            const SizedBox(height: 12),
            _dropdown(
              'Creative type',
              services,
              _serviceCode,
              (value) => setState(() => _serviceCode = value),
            ),
            const SizedBox(height: 10),
            DropdownButtonFormField<int>(
              key: ValueKey(_creativeQuantity),
              initialValue: _creativeQuantity,
              decoration: _input('Number of creatives'),
              items: List.generate(
                10,
                (i) => DropdownMenuItem(
                  value: i + 1,
                  child: Text('${i + 1} creative${i == 0 ? '' : 's'}'),
                ),
              ),
              onChanged: (value) =>
                  setState(() => _creativeQuantity = value ?? 1),
            ),
          ],
        ]),
        const SizedBox(height: 15),
        _section('Review & payment', [
          _summaryRow(
            'Campaign',
            _campaignName.text.isEmpty ? 'Untitled' : _campaignName.text,
          ),
          _summaryRow('Daily media budget', '₹${_budget.text.trim()}'),
          _summaryRow(
            'Creative',
            _creativeMode == 'self_upload'
                ? 'Own upload'
                : 'AdLive design × $_creativeQuantity',
          ),
          const Divider(height: 24),
          _summaryRow(
            'Estimated service payment',
            total > 0
                ? '₹${total.toStringAsFixed(2)}'
                : 'Calculated securely at checkout',
            strong: true,
          ),
          const SizedBox(height: 8),
          Text(
            'Wallet settlement and payment verification happen on AdLive before this campaign opens the next step.',
            style: AdLiveDesign.body(size: 11),
          ),
        ]),
        const SizedBox(height: 20),
        _mainButton(
          'Continue to secure payment',
          Icons.lock_outline_rounded,
          _working ? null : _pay,
        ),
      ],
    );
  }

  Widget _finalize() {
    final brief = _flowMap(_creative['creative_brief']);
    final metaReady =
        _flowMap(_meta['campaign'])['meta_setup_completed'] == true;
    final own = _flowText(brief['mode']) == 'self_upload';
    final requiresWhatsApp = _meta['requires_whatsapp'] == true;
    return ListView(
      padding: const EdgeInsets.fromLTRB(18, 18, 18, 32),
      children: [
        Text('Creative & Meta setup', style: AdLiveDesign.heading(24)),
        const SizedBox(height: 5),
        Text(
          'Creative and Meta setup are saved separately, so you can complete both in parallel.',
          style: AdLiveDesign.body(),
        ),
        const SizedBox(height: 15),
        if (own) _creativeUpload() else _designRequirements(brief),
        const SizedBox(height: 15),
        _section('Meta setup', [
          Text(
            metaReady
                ? 'Meta setup is complete and remains subject to campaign review.'
                : 'Connect the Facebook assets for this campaign. Creative and Meta setup have separate save actions.',
            style: AdLiveDesign.body(),
          ),
          const SizedBox(height: 13),
          _mainButton(
            'Open Meta setup',
            Icons.tune_rounded,
            _working ? null : _openConnections,
          ),
          if (requiresWhatsApp &&
              _flowText(_meta['connection_mode']) == 'adlive_managed') ...[
            const SizedBox(height: 12),
            _assetDropdown(
              'Business WhatsApp number',
              _whatsAppNumbers,
              _whatsAppPhoneId,
              (value) => setState(() => _whatsAppPhoneId = value),
            ),
          ],
          const SizedBox(height: 9),
          OutlinedButton.icon(
            onPressed: _working ? null : _confirmMeta,
            icon: const Icon(Icons.check_circle_outline_rounded),
            label: Text(metaReady ? 'Meta setup confirmed' : 'Save Meta setup'),
          ),
        ]),
      ],
    );
  }

  Widget _creativeUpload() => _section('Upload your creative', [
    Text(
      'Choose the format, save the creative, then complete Meta setup below.',
      style: AdLiveDesign.body(),
    ),
    const SizedBox(height: 12),
    Wrap(
      spacing: 8,
      runSpacing: 8,
      children: [
        for (final choice in const [
          ('single_image', 'Single image'),
          ('single_video', 'Single video'),
          ('carousel', 'Carousel'),
        ])
          _choice(
            choice.$2,
            _format == choice.$1,
            () => setState(() => _format = choice.$1),
          ),
      ],
    ),
    if (_format == 'carousel') ...[
      const SizedBox(height: 12),
      ...List.generate(_carouselCards.length, (index) {
        final card = _carouselCards[index];
        return Padding(
          padding: const EdgeInsets.only(bottom: 10),
          child: Container(
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(
              color: const Color(0xFFF9FBFA),
              borderRadius: BorderRadius.circular(14),
              border: Border.all(color: AdLiveDesign.line),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Text('Card ${index + 1}', style: AdLiveDesign.heading(13)),
                    const Spacer(),
                    if (_carouselCards.length > 2)
                      IconButton(
                        tooltip: 'Remove card',
                        onPressed: () => setState(() {
                          final removed = _carouselCards.removeAt(index);
                          removed.dispose();
                        }),
                        icon: const Icon(Icons.close_rounded, size: 19),
                      ),
                  ],
                ),
                OutlinedButton.icon(
                  onPressed: _working ? null : () => _pickCarouselImage(index),
                  icon: const Icon(Icons.image_outlined),
                  label: Text(
                    card.imagePath == null
                        ? 'Choose card image'
                        : 'Image selected',
                  ),
                ),
                const SizedBox(height: 9),
                _field(card.headline, 'Card headline'),
                const SizedBox(height: 9),
                _field(card.description, 'Card description (optional)'),
              ],
            ),
          ),
        );
      }),
      if (_carouselCards.length < 10)
        OutlinedButton.icon(
          onPressed: _working
              ? null
              : () => setState(() => _carouselCards.add(_CarouselCard())),
          icon: const Icon(Icons.add_rounded),
          label: const Text('Add card'),
        ),
    ] else ...[
      const SizedBox(height: 12),
      OutlinedButton.icon(
        onPressed: _working ? null : _pickMedia,
        icon: const Icon(Icons.upload_file_rounded),
        label: Text(_mediaPath == null ? 'Choose media' : 'Media selected'),
      ),
      const SizedBox(height: 10),
      _field(_headline, 'Headline'),
      const SizedBox(height: 10),
      _field(_description, 'Description (optional)'),
    ],
    const SizedBox(height: 10),
    _field(_creativeBody, 'Primary text', maxLines: 3),
    const SizedBox(height: 15),
    _mainButton(
      'Save creative',
      Icons.save_outlined,
      _working ? null : _saveCreative,
    ),
  ]);

  Widget _designRequirements(Map<String, dynamic> brief) {
    final submitted = brief['requirements_submitted'] == true;
    final schema = _flowMaps(brief['requirement_schema']);
    final quantity = (brief['quantity'] as num?)?.toInt() ?? 1;
    return _section('Design requirements', [
      Text(
        submitted
            ? 'Design requirements have been sent to the AdLive team.'
            : 'Tell the design team what they should create. You can still complete Meta setup below.',
        style: AdLiveDesign.body(),
      ),
      if (!submitted && schema.isEmpty) ...[
        const SizedBox(height: 12),
        _note(
          'The design brief is still being prepared. You can complete Meta setup while AdLive adds the required fields.',
        ),
      ],
      if (!submitted) ...[
        for (var item = 0; item < quantity; item++) ...[
          if (quantity > 1)
            Padding(
              padding: const EdgeInsets.only(top: 14),
              child: Text(
                'Creative ${item + 1}',
                style: AdLiveDesign.heading(14),
              ),
            ),
          for (final field in schema) ...[
            const SizedBox(height: 10),
            _requirementField(item, field),
          ],
        ],
        if (schema.isNotEmpty) ...[
          const SizedBox(height: 15),
          _mainButton(
            'Send design requirements',
            Icons.send_rounded,
            _working ? null : _submitRequirements,
          ),
        ],
      ],
    ]);
  }

  Widget _goalPicker() => _section('Where should leads go?', [
    Wrap(
      spacing: 8,
      runSpacing: 8,
      children: [
        for (final item in const [
          ('whatsapp', 'WhatsApp'),
          ('website', 'Website'),
          ('instant_form', 'Instant form'),
        ])
          _choice(
            item.$2,
            _goal == item.$1,
            () => setState(() => _goal = item.$1),
          ),
      ],
    ),
  ]);

  Widget _requirementField(int item, Map<String, dynamic> field) {
    final key = _flowText(field['key']);
    final label = _flowText(field['label']).isEmpty
        ? key
        : _flowText(field['label']);
    final required = field['required'] == true ? ' *' : '';
    if (_flowText(field['type']) == 'file') {
      final selected = _requirementFiles['$item.$key'] ?? const <String>[];
      return Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text('$label$required', style: AdLiveDesign.heading(13)),
          const SizedBox(height: 7),
          OutlinedButton.icon(
            onPressed: _working
                ? null
                : () => _pickRequirementFile(item, field),
            icon: const Icon(Icons.attach_file_rounded),
            label: Text(
              selected.isEmpty
                  ? 'Attach file'
                  : '${selected.length} file${selected.length == 1 ? '' : 's'} selected',
            ),
          ),
        ],
      );
    }
    final controller = _requirementControllers.putIfAbsent(
      '$item.$key',
      TextEditingController.new,
    );
    return _field(
      controller,
      '$label$required',
      maxLines: _flowText(field['type']) == 'textarea' ? 4 : 1,
    );
  }

  Widget _assetDropdown(
    String label,
    List<Map<String, dynamic>> options,
    String? value,
    ValueChanged<String?> changed,
  ) {
    final valid = options.any((option) => _flowText(option['id']) == value);
    return DropdownButtonFormField<String>(
      key: ValueKey('asset-$label-${valid ? value : ''}'),
      initialValue: valid ? value : null,
      isExpanded: true,
      decoration: _input(label),
      items: options
          .map(
            (option) => DropdownMenuItem(
              value: _flowText(option['id']),
              child: Text(
                _flowText(
                  option['display_phone_number'] ??
                      option['name'] ??
                      option['id'],
                ),
                overflow: TextOverflow.ellipsis,
              ),
            ),
          )
          .toList(),
      onChanged: options.isEmpty || _working ? null : changed,
    );
  }

  Widget _retry() => Center(
    child: Padding(
      padding: const EdgeInsets.all(24),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Text(
            _error!,
            textAlign: TextAlign.center,
            style: AdLiveDesign.body(),
          ),
          const SizedBox(height: 14),
          _mainButton('Try again', Icons.refresh_rounded, _load),
        ],
      ),
    ),
  );
  Widget _field(
    TextEditingController controller,
    String label, {
    int maxLines = 1,
    TextInputType? keyboard,
  }) => TextField(
    controller: controller,
    maxLines: maxLines,
    keyboardType: keyboard,
    decoration: _input(label),
  );
  InputDecoration _input(String label, {IconData? icon}) => InputDecoration(
    labelText: label,
    prefixIcon: icon == null ? null : Icon(icon),
    filled: true,
    fillColor: Colors.white,
    contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 13),
    enabledBorder: OutlineInputBorder(
      borderRadius: BorderRadius.circular(14),
      borderSide: const BorderSide(color: AdLiveDesign.line),
    ),
    focusedBorder: OutlineInputBorder(
      borderRadius: BorderRadius.circular(14),
      borderSide: const BorderSide(color: AdLiveDesign.green),
    ),
  );
  Widget _label(String text) => Padding(
    padding: const EdgeInsets.only(bottom: 7),
    child: Text(text, style: AdLiveDesign.heading(14)),
  );
  Widget _section(String title, List<Widget> children) => Container(
    padding: const EdgeInsets.all(16),
    decoration: AdLiveDesign.surface(),
    child: Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(title, style: AdLiveDesign.heading(17)),
        const SizedBox(height: 12),
        ...children,
      ],
    ),
  );
  Widget _choice(String label, bool selected, VoidCallback onTap) => ChoiceChip(
    label: Text(label),
    selected: selected,
    onSelected: (_) => onTap(),
    selectedColor: AdLiveDesign.mint,
    backgroundColor: Colors.white,
    side: const BorderSide(color: AdLiveDesign.line),
    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(11)),
    labelStyle: TextStyle(
      color: selected ? AdLiveDesign.deepGreen : AdLiveDesign.ink,
      fontWeight: FontWeight.w700,
    ),
  );
  Widget _radioChoice(String label, bool selected, VoidCallback tap) => InkWell(
    onTap: tap,
    borderRadius: BorderRadius.circular(14),
    child: Container(
      padding: const EdgeInsets.all(13),
      decoration: BoxDecoration(
        color: selected ? AdLiveDesign.mint : Colors.white,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(
          color: selected ? const Color(0xFF8ED8B8) : AdLiveDesign.line,
        ),
      ),
      child: Row(
        children: [
          Icon(
            selected
                ? Icons.radio_button_checked_rounded
                : Icons.radio_button_off_rounded,
            color: selected ? AdLiveDesign.green : AdLiveDesign.muted,
          ),
          const SizedBox(width: 9),
          Expanded(child: Text(label, style: AdLiveDesign.heading(13))),
        ],
      ),
    ),
  );
  Widget _dropdown(
    String label,
    List<Map<String, dynamic>> options,
    String? value,
    ValueChanged<String?> changed,
  ) {
    final valid = options.any(
      (option) =>
          _flowText(option['id'] ?? option['value'] ?? option['key']) == value,
    );
    return DropdownButtonFormField<String>(
      key: ValueKey('catalogue-$label-${valid ? value : ''}'),
      initialValue: valid ? value : null,
      isExpanded: true,
      decoration: _input(label),
      items: options.map((option) {
        final id = _flowText(option['id'] ?? option['value'] ?? option['key']);
        return DropdownMenuItem(
          value: id,
          child: Text(
            _flowText(
              option['name'] ?? option['label'] ?? option['title'] ?? id,
            ),
            overflow: TextOverflow.ellipsis,
          ),
        );
      }).toList(),
      onChanged: options.isEmpty ? null : changed,
    );
  }

  Widget _note(String message) => Container(
    padding: const EdgeInsets.all(12),
    decoration: BoxDecoration(
      color: AdLiveDesign.mint,
      borderRadius: BorderRadius.circular(13),
    ),
    child: Text(
      message,
      style: AdLiveDesign.body(size: 11, color: AdLiveDesign.deepGreen),
    ),
  );
  Widget _mainButton(String label, IconData icon, VoidCallback? pressed) =>
      SizedBox(
        width: double.infinity,
        child: FilledButton.icon(
          onPressed: pressed,
          icon: Icon(icon),
          label: Text(label),
          style: FilledButton.styleFrom(
            backgroundColor: AdLiveDesign.green,
            foregroundColor: Colors.white,
            padding: const EdgeInsets.symmetric(vertical: 14),
            shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(14),
            ),
          ),
        ),
      );
  Widget _summaryRow(String label, String value, {bool strong = false}) =>
      Padding(
        padding: const EdgeInsets.only(bottom: 9),
        child: Row(
          children: [
            Expanded(child: Text(label, style: AdLiveDesign.body(size: 12))),
            Text(
              value,
              style:
                  (strong
                          ? AdLiveDesign.heading(14)
                          : AdLiveDesign.body(size: 12))
                      .copyWith(color: strong ? AdLiveDesign.deepGreen : null),
            ),
          ],
        ),
      );
}

class _CarouselCard {
  final headline = TextEditingController();
  final description = TextEditingController();
  String? imagePath;

  void dispose() {
    headline.dispose();
    description.dispose();
  }
}

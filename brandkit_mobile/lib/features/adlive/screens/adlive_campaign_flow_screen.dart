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

  Widget _steps() => LayoutBuilder(
    builder: (context, constraints) {
      final compact = constraints.maxWidth < 390;
      const labels = ['Plan', 'Creative & payment', 'Creative & Meta'];
      return Container(
        color: Colors.white,
        padding: EdgeInsets.fromLTRB(
          compact ? 12 : 20,
          9,
          compact ? 12 : 20,
          8,
        ),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            for (var index = 0; index < labels.length; index++) ...[
              Expanded(
                child: Column(
                  children: [
                    AnimatedContainer(
                      duration: const Duration(milliseconds: 180),
                      width: 25,
                      height: 25,
                      alignment: Alignment.center,
                      decoration: BoxDecoration(
                        color: index <= _step
                            ? AdLiveDesign.green
                            : const Color(0xFFF0F5F2),
                        shape: BoxShape.circle,
                      ),
                      child: index < _step
                          ? const Icon(
                              Icons.check_rounded,
                              size: 15,
                              color: Colors.white,
                            )
                          : Text(
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
                    SizedBox(
                      height: compact ? 27 : 16,
                      child: Text(
                        labels[index],
                        maxLines: compact ? 2 : 1,
                        overflow: TextOverflow.ellipsis,
                        textAlign: TextAlign.center,
                        style:
                            AdLiveDesign.body(
                              size: compact ? 9 : 10,
                              color: index == _step
                                  ? AdLiveDesign.deepGreen
                                  : AdLiveDesign.muted,
                            ).copyWith(
                              height: 1.15,
                              fontWeight: index == _step
                                  ? FontWeight.w800
                                  : FontWeight.w600,
                            ),
                      ),
                    ),
                  ],
                ),
              ),
              if (index < labels.length - 1)
                Container(
                  width: compact ? 12 : 28,
                  height: 2,
                  margin: const EdgeInsets.only(top: 12),
                  decoration: BoxDecoration(
                    color: index < _step
                        ? AdLiveDesign.green
                        : AdLiveDesign.line,
                    borderRadius: BorderRadius.circular(99),
                  ),
                ),
            ],
          ],
        ),
      );
    },
  );

  Widget _plan() {
    final packages = _flowMaps(_bootstrap['packages']);
    final customerTypes = _flowMaps(
      _flowMap(_bootstrap['catalogue'])['customer_types'],
    );
    return _flowPage([
      _pageIntro(
        'Package & audience',
        'Set campaign essentials and build the audience.',
      ),
      const SizedBox(height: 12),
      _section('Campaign basics', [
        _label('Package'),
        _choiceGrid(
          packages
              .map((item) => (_flowText(item['id']), _flowText(item['name'])))
              .toList(),
          _packageId,
          (value) => setState(() => _packageId = value),
          preferredColumns: 2,
        ),
        const SizedBox(height: 10),
        _responsivePair(
          _field(_campaignName, 'Campaign name'),
          _field(_budget, 'Daily budget (₹)', keyboard: TextInputType.number),
          compactTwoColumn: true,
        ),
        const SizedBox(height: 10),
        _label('Lead destination'),
        _choiceGrid(
          const [
            ('whatsapp', 'WhatsApp'),
            ('website', 'Website'),
            ('instant_form', 'Instant form'),
          ],
          _goal,
          (value) => setState(() => _goal = value),
          preferredColumns: 3,
        ),
      ]),
      const SizedBox(height: 12),
      _section('Business & audience', [
        _responsivePair(
          _field(_businessName, 'Business name'),
          _field(_product, 'Product or service'),
          compactTwoColumn: true,
        ),
        const SizedBox(height: 10),
        _field(_details, 'Offer details (optional)', maxLines: 2),
        if (_goal == 'website') ...[
          const SizedBox(height: 10),
          _field(_website, 'Website URL'),
        ],
        const SizedBox(height: 10),
        _responsivePair(
          _dropdown(
            'Customer type',
            customerTypes,
            _customerType,
            _loadIndustries,
          ),
          _dropdown(
            'Industry',
            _industries,
            _industry,
            (value) => setState(() => _industry = value),
          ),
          compactTwoColumn: true,
        ),
        const SizedBox(height: 10),
        _outlineButton(
          'Generate Smart Audience',
          Icons.auto_awesome_rounded,
          _working ? null : _previewAudience,
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
      const SizedBox(height: 12),
      _section('Location & targeting', [
        TextField(
          controller: _locationQuery,
          onChanged: _searchLocations,
          style: AdLiveDesign.body(size: 12, color: AdLiveDesign.ink),
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
                dense: true,
                visualDensity: VisualDensity.compact,
                contentPadding: const EdgeInsets.symmetric(horizontal: 2),
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
        const SizedBox(height: 6),
        Text(
          'Age, gender and language stay on the approved Smart Audience unless changed during review.',
          style: AdLiveDesign.body(size: 11),
        ),
      ]),
      const SizedBox(height: 14),
      _mainButton(
        'Continue to creative & payment',
        Icons.arrow_forward_rounded,
        _working ? null : _continueFromPlan,
      ),
    ]);
  }

  Widget _payment() {
    final services = _flowMaps(_bootstrap['creative_services']);
    final total = _flowAmount(
      _quote['total'] ?? _quote['service_charge'] ?? _quote['payable'],
    );
    return _flowPage([
      _pageIntro(
        'Creative & payment',
        'Your choices update the verified estimate.',
      ),
      const SizedBox(height: 12),
      _section('Creative preference', [
        _responsivePair(
          _radioChoice(
            'Upload my creative',
            _creativeMode == 'self_upload',
            () => setState(() => _creativeMode = 'self_upload'),
          ),
          _radioChoice(
            'AdLive designs it',
            _creativeMode == 'adlive_design',
            () => setState(() => _creativeMode = 'adlive_design'),
          ),
          compactTwoColumn: true,
        ),
        if (_creativeMode == 'adlive_design') ...[
          const SizedBox(height: 10),
          _responsivePair(
            _dropdown(
              'Creative type',
              services,
              _serviceCode,
              (value) => setState(() => _serviceCode = value),
            ),
            DropdownButtonFormField<int>(
              key: ValueKey(_creativeQuantity),
              initialValue: _creativeQuantity,
              isExpanded: true,
              isDense: true,
              menuMaxHeight: 280,
              borderRadius: BorderRadius.circular(14),
              style: AdLiveDesign.body(size: 12, color: AdLiveDesign.ink),
              decoration: _input('Quantity'),
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
            compactTwoColumn: true,
          ),
        ],
      ]),
      const SizedBox(height: 12),
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
        const Divider(height: 18),
        _summaryRow(
          'Estimated service payment',
          total > 0
              ? '₹${total.toStringAsFixed(2)}'
              : 'Calculated securely at checkout',
          strong: true,
        ),
        const SizedBox(height: 5),
        Text(
          'Wallet settlement and payment verification happen on AdLive before this campaign opens the next step.',
          style: AdLiveDesign.body(size: 11),
        ),
      ]),
      const SizedBox(height: 14),
      _mainButton(
        'Continue to secure payment',
        Icons.lock_outline_rounded,
        _working ? null : _pay,
      ),
    ]);
  }

  Widget _finalize() {
    final brief = _flowMap(_creative['creative_brief']);
    final metaReady =
        _flowMap(_meta['campaign'])['meta_setup_completed'] == true;
    final own = _flowText(brief['mode']) == 'self_upload';
    final requiresWhatsApp = _meta['requires_whatsapp'] == true;
    return _flowPage([
      _pageIntro(
        'Creative & Meta setup',
        'Save creative and Meta setup separately.',
      ),
      const SizedBox(height: 12),
      if (own) _creativeUpload() else _designRequirements(brief),
      const SizedBox(height: 12),
      _section('Meta setup', [
        Text(
          metaReady
              ? 'Meta setup is complete and remains subject to campaign review.'
              : 'Connect the Facebook assets for this campaign. Creative and Meta setup have separate save actions.',
          style: AdLiveDesign.body(),
        ),
        const SizedBox(height: 10),
        if (requiresWhatsApp &&
            _flowText(_meta['connection_mode']) == 'adlive_managed') ...[
          _assetDropdown(
            'Business WhatsApp number',
            _whatsAppNumbers,
            _whatsAppPhoneId,
            (value) => setState(() => _whatsAppPhoneId = value),
          ),
          const SizedBox(height: 10),
        ],
        _responsiveActions(
          _outlineButton(
            'Connect assets',
            Icons.tune_rounded,
            _working ? null : _openConnections,
          ),
          _mainButton(
            metaReady ? 'Confirmed' : 'Save Meta setup',
            Icons.check_circle_outline_rounded,
            _working ? null : _confirmMeta,
          ),
        ),
      ]),
    ]);
  }

  Widget _creativeUpload() => _section('Upload your creative', [
    Text(
      'Choose the format, save the creative, then complete Meta setup below.',
      style: AdLiveDesign.body(),
    ),
    const SizedBox(height: 12),
    _choiceGrid(
      const [
        ('single_image', 'Single image'),
        ('single_video', 'Single video'),
        ('carousel', 'Carousel'),
      ],
      _format,
      (value) => setState(() => _format = value),
      preferredColumns: 3,
    ),
    if (_format == 'carousel') ...[
      const SizedBox(height: 12),
      ...List.generate(_carouselCards.length, (index) {
        final card = _carouselCards[index];
        return Padding(
          padding: const EdgeInsets.only(bottom: 10),
          child: Container(
            padding: const EdgeInsets.all(11),
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
                        visualDensity: VisualDensity.compact,
                        constraints: const BoxConstraints(
                          minWidth: 34,
                          minHeight: 34,
                        ),
                      ),
                  ],
                ),
                _outlineButton(
                  card.imagePath == null
                      ? 'Choose card image'
                      : 'Image selected',
                  Icons.image_outlined,
                  _working ? null : () => _pickCarouselImage(index),
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
        _outlineButton(
          'Add card',
          Icons.add_rounded,
          _working
              ? null
              : () => setState(() => _carouselCards.add(_CarouselCard())),
        ),
    ] else ...[
      const SizedBox(height: 12),
      _outlineButton(
        _mediaPath == null ? 'Choose media' : 'Media selected',
        Icons.upload_file_rounded,
        _working ? null : _pickMedia,
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
          _outlineButton(
            selected.isEmpty
                ? 'Attach file'
                : '${selected.length} file${selected.length == 1 ? '' : 's'} selected',
            Icons.attach_file_rounded,
            _working ? null : () => _pickRequirementFile(item, field),
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
      isDense: true,
      menuMaxHeight: 280,
      borderRadius: BorderRadius.circular(14),
      icon: const Icon(Icons.keyboard_arrow_down_rounded, size: 20),
      style: AdLiveDesign.body(size: 12, color: AdLiveDesign.ink),
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

  Widget _flowPage(List<Widget> children) => LayoutBuilder(
    builder: (context, constraints) {
      final horizontal = constraints.maxWidth < 390 ? 12.0 : 18.0;
      return ListView(
        keyboardDismissBehavior: ScrollViewKeyboardDismissBehavior.onDrag,
        padding: EdgeInsets.fromLTRB(horizontal, 12, horizontal, 28),
        children: [
          Center(
            child: ConstrainedBox(
              constraints: const BoxConstraints(maxWidth: 820),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: children,
              ),
            ),
          ),
        ],
      );
    },
  );

  Widget _pageIntro(String title, String subtitle) => Row(
    crossAxisAlignment: CrossAxisAlignment.start,
    children: [
      Container(
        width: 32,
        height: 32,
        alignment: Alignment.center,
        decoration: const BoxDecoration(
          color: AdLiveDesign.mint,
          shape: BoxShape.circle,
        ),
        child: Text(
          '${_step + 1}',
          style: AdLiveDesign.heading(13).copyWith(color: AdLiveDesign.green),
        ),
      ),
      const SizedBox(width: 10),
      Expanded(
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(title, style: AdLiveDesign.heading(21)),
            const SizedBox(height: 2),
            Text(subtitle, style: AdLiveDesign.body(size: 12)),
          ],
        ),
      ),
    ],
  );

  Widget _responsivePair(
    Widget first,
    Widget second, {
    bool compactTwoColumn = false,
  }) => LayoutBuilder(
    builder: (context, constraints) {
      final twoColumns = constraints.maxWidth >= (compactTwoColumn ? 300 : 560);
      if (!twoColumns) {
        return Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [first, const SizedBox(height: 9), second],
        );
      }
      return Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Expanded(child: first),
          const SizedBox(width: 9),
          Expanded(child: second),
        ],
      );
    },
  );

  Widget _responsiveActions(Widget first, Widget second) => LayoutBuilder(
    builder: (context, constraints) {
      if (constraints.maxWidth < 300) {
        return Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [first, const SizedBox(height: 8), second],
        );
      }
      return Row(
        children: [
          Expanded(child: first),
          const SizedBox(width: 8),
          Expanded(child: second),
        ],
      );
    },
  );

  Widget _choiceGrid(
    List<(String, String)> items,
    String? selected,
    ValueChanged<String> changed, {
    required int preferredColumns,
  }) {
    if (items.isEmpty) return const SizedBox.shrink();
    return LayoutBuilder(
      builder: (context, constraints) {
        var columns = items.length < preferredColumns
            ? items.length
            : preferredColumns;
        if (constraints.maxWidth < 300 && columns > 2) columns = 2;
        const gap = 7.0;
        final width = (constraints.maxWidth - (gap * (columns - 1))) / columns;
        return Wrap(
          spacing: gap,
          runSpacing: gap,
          children: [
            for (final item in items)
              SizedBox(
                width: width,
                child: _choice(
                  item.$2,
                  selected == item.$1,
                  () => changed(item.$1),
                ),
              ),
          ],
        );
      },
    );
  }

  Widget _field(
    TextEditingController controller,
    String label, {
    int maxLines = 1,
    TextInputType? keyboard,
  }) => TextField(
    controller: controller,
    maxLines: maxLines,
    keyboardType: keyboard,
    style: AdLiveDesign.body(size: 12, color: AdLiveDesign.ink),
    decoration: _input(label, alignLabelWithHint: maxLines > 1),
  );
  InputDecoration _input(
    String label, {
    IconData? icon,
    bool alignLabelWithHint = false,
  }) => InputDecoration(
    labelText: label,
    alignLabelWithHint: alignLabelWithHint,
    isDense: true,
    labelStyle: AdLiveDesign.body(size: 12, color: AdLiveDesign.ink),
    floatingLabelStyle: AdLiveDesign.body(
      size: 10.5,
      color: AdLiveDesign.deepGreen,
    ).copyWith(fontWeight: FontWeight.w600),
    hintStyle: AdLiveDesign.body(size: 12),
    prefixIcon: icon == null ? null : Icon(icon, size: 19),
    prefixIconConstraints: icon == null
        ? null
        : const BoxConstraints(minWidth: 40, minHeight: 40),
    filled: true,
    fillColor: Colors.white,
    contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 11),
    enabledBorder: OutlineInputBorder(
      borderRadius: BorderRadius.circular(12),
      borderSide: const BorderSide(color: AdLiveDesign.line),
    ),
    focusedBorder: OutlineInputBorder(
      borderRadius: BorderRadius.circular(12),
      borderSide: const BorderSide(color: AdLiveDesign.green),
    ),
    disabledBorder: OutlineInputBorder(
      borderRadius: BorderRadius.circular(12),
      borderSide: const BorderSide(color: AdLiveDesign.line),
    ),
  );
  Widget _label(String text) => Padding(
    padding: const EdgeInsets.only(bottom: 6),
    child: Text(text, style: AdLiveDesign.heading(13)),
  );
  Widget _section(String title, List<Widget> children) {
    final compact = MediaQuery.sizeOf(context).width < 390;
    return Container(
      padding: EdgeInsets.all(compact ? 12 : 15),
      decoration: AdLiveDesign.surface(),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Text(title, style: AdLiveDesign.heading(compact ? 15 : 17)),
          SizedBox(height: compact ? 9 : 11),
          ...children,
        ],
      ),
    );
  }

  Widget _choice(String label, bool selected, VoidCallback onTap) => Material(
    color: Colors.transparent,
    child: InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(11),
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 160),
        constraints: const BoxConstraints(minHeight: 42),
        padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 8),
        alignment: Alignment.center,
        decoration: BoxDecoration(
          color: selected ? AdLiveDesign.mint : Colors.white,
          borderRadius: BorderRadius.circular(11),
          border: Border.all(
            color: selected ? const Color(0xFF82D4B1) : AdLiveDesign.line,
          ),
        ),
        child: Text(
          label,
          maxLines: 2,
          overflow: TextOverflow.ellipsis,
          textAlign: TextAlign.center,
          style: TextStyle(
            color: selected ? AdLiveDesign.deepGreen : AdLiveDesign.ink,
            fontSize: 11.5,
            height: 1.15,
            fontWeight: FontWeight.w700,
          ),
        ),
      ),
    ),
  );
  Widget _radioChoice(String label, bool selected, VoidCallback tap) => InkWell(
    onTap: tap,
    borderRadius: BorderRadius.circular(12),
    child: Container(
      constraints: const BoxConstraints(minHeight: 44),
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 9),
      decoration: BoxDecoration(
        color: selected ? AdLiveDesign.mint : Colors.white,
        borderRadius: BorderRadius.circular(12),
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
            size: 19,
          ),
          const SizedBox(width: 7),
          Expanded(
            child: Text(
              label,
              maxLines: 2,
              overflow: TextOverflow.ellipsis,
              style: AdLiveDesign.heading(12),
            ),
          ),
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
      isDense: true,
      menuMaxHeight: 280,
      borderRadius: BorderRadius.circular(14),
      icon: const Icon(Icons.keyboard_arrow_down_rounded, size: 20),
      style: AdLiveDesign.body(size: 12, color: AdLiveDesign.ink),
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
    padding: const EdgeInsets.all(10),
    decoration: BoxDecoration(
      color: AdLiveDesign.mint,
      borderRadius: BorderRadius.circular(13),
    ),
    child: Text(
      message,
      style: AdLiveDesign.body(size: 11, color: AdLiveDesign.deepGreen),
    ),
  );
  Widget _outlineButton(
    String label,
    IconData icon,
    VoidCallback? pressed,
  ) => SizedBox(
    width: double.infinity,
    height: 44,
    child: OutlinedButton.icon(
      onPressed: pressed,
      icon: Icon(icon, size: 18),
      label: Text(label, maxLines: 1, overflow: TextOverflow.ellipsis),
      style: OutlinedButton.styleFrom(
        foregroundColor: AdLiveDesign.deepGreen,
        side: const BorderSide(color: AdLiveDesign.line),
        padding: const EdgeInsets.symmetric(horizontal: 10),
        textStyle: const TextStyle(fontSize: 12, fontWeight: FontWeight.w700),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      ),
    ),
  );

  Widget _mainButton(
    String label,
    IconData icon,
    VoidCallback? pressed,
  ) => SizedBox(
    width: double.infinity,
    height: 46,
    child: FilledButton.icon(
      onPressed: pressed,
      icon: Icon(icon, size: 18),
      label: Text(label, maxLines: 1, overflow: TextOverflow.ellipsis),
      style: FilledButton.styleFrom(
        backgroundColor: AdLiveDesign.green,
        foregroundColor: Colors.white,
        padding: const EdgeInsets.symmetric(horizontal: 12),
        textStyle: const TextStyle(fontSize: 12.5, fontWeight: FontWeight.w800),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      ),
    ),
  );
  Widget _summaryRow(String label, String value, {bool strong = false}) =>
      Padding(
        padding: const EdgeInsets.only(bottom: 9),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Expanded(
              flex: 4,
              child: Text(label, style: AdLiveDesign.body(size: 12)),
            ),
            const SizedBox(width: 10),
            Expanded(
              flex: 5,
              child: Text(
                value,
                textAlign: TextAlign.end,
                maxLines: 3,
                overflow: TextOverflow.ellipsis,
                style:
                    (strong
                            ? AdLiveDesign.heading(14)
                            : AdLiveDesign.body(size: 12))
                        .copyWith(
                          color: strong ? AdLiveDesign.deepGreen : null,
                        ),
              ),
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

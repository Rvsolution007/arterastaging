import 'dart:async';
import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:image_picker/image_picker.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../controllers/home_controller.dart';
import '../features/ai_editable_v1/screens/ai_editable_editor_screen.dart';
import '../services/api_service.dart';
import 'business_profile_screen.dart';

/// Custom Post's standalone Business AI journey. It does not create or load a
/// frame; the generated design opens the isolated editable-document editor.
class BusinessAiPostScreen extends StatefulWidget {
  const BusinessAiPostScreen({super.key});

  @override
  State<BusinessAiPostScreen> createState() => _BusinessAiPostScreenState();
}

class _BusinessAiPostScreenState extends State<BusinessAiPostScreen> {
  final _visualInstruction = TextEditingController();
  final _extraDetail = TextEditingController();
  final _reviewHeadline = TextEditingController();
  final _reviewContent = TextEditingController();
  final _reviewCta = TextEditingController();
  final Map<String, TextEditingController> _brief = {};
  final Set<int> _productIds = <int>{};
  final List<Map<String, dynamic>> _directReferenceUploads = [];

  Timer? _poller;
  bool _loading = true;
  bool _submitting = false;
  bool _previewLoading = false;
  bool _savingDraft = false;
  String? _error;
  String? _previewNotice;
  int _step = 0;
  Map<String, dynamic>? _purpose;
  Map<String, dynamic>? _scope;
  Map<String, dynamic>? _style;
  Map<String, dynamic>? _model;
  String? _quality;
  String? _sizeKey;
  Map<String, dynamic>? _quota;
  int _generationCost = 1;
  List<dynamic> _purposes = [];
  List<dynamic> _styles = [];
  List<dynamic> _models = [];
  List<dynamic> _languages = [];
  List<dynamic> _products = [];
  List<dynamic> _availableScopes = [];
  Map<String, dynamic>? _business;
  Map<String, dynamic>? _contentPreview;
  Map<String, dynamic>? _job;
  String _paletteMode = 'style_colors';
  String _generationKind = 'initial';

  @override
  void initState() {
    super.initState();
    _loadOptions();
  }

  @override
  void dispose() {
    _poller?.cancel();
    _visualInstruction.dispose();
    _extraDetail.dispose();
    _reviewHeadline.dispose();
    _reviewContent.dispose();
    _reviewCta.dispose();
    for (final controller in _brief.values) {
      controller.dispose();
    }
    super.dispose();
  }

  Future<void> _loadOptions() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final response = await ApiService.get('/business-ai/options');
      final data = jsonDecode(response.body);
      if (response.statusCode != 200 ||
          data is! Map ||
          data['success'] != true) {
        throw Exception(
          data is Map
              ? data['message']
              : 'Business Post AI could not be loaded.',
        );
      }
      final models = List<dynamic>.from(data['models'] ?? []);
      Map<String, dynamic>? model;
      String? quality;
      for (final raw in models) {
        final candidate = Map<String, dynamic>.from(raw as Map);
        final variants = List<dynamic>.from(
          candidate['quality_variants'] ?? [],
        );
        final available = variants.cast<Map?>().firstWhere(
          (item) => item?['is_available'] == true,
          orElse: () => null,
        );
        if (available != null) {
          model = candidate;
          quality = available['key']?.toString();
          break;
        }
      }
      setState(() {
        _quota = Map<String, dynamic>.from(data['quota'] ?? {});
        _generationCost = int.tryParse('${data['generation_cost'] ?? 1}') ?? 1;
        _purposes = List<dynamic>.from(
          data['custom_post_cards'] ?? data['purposes'] ?? [],
        );
        _styles = List<dynamic>.from(data['styles'] ?? []);
        _models = models;
        _languages = List<dynamic>.from(data['languages'] ?? []);
        _availableScopes = List<dynamic>.from(
          data['scopes'] ??
              data['category_scopes'] ??
              data['business_scopes'] ??
              [],
        );
        _purpose = null;
        _scope = null;
        _business = data['active_business'] is Map
            ? Map<String, dynamic>.from(data['active_business'] as Map)
            : _defaultBusiness(_asMapList(data['businesses']));
        _paletteMode = 'style_colors';
        _style = _styles.isEmpty
            ? null
            : Map<String, dynamic>.from(_styles.first as Map);
        _model = model;
        _quality = quality;
        _sizeKey = _availableSizes.isEmpty
            ? null
            : _availableSizes.first['key']?.toString();
        _syncBriefFields();
        _loading = false;
      });
    } catch (error) {
      if (!mounted) return;
      setState(() {
        _loading = false;
        _error = error.toString().replaceFirst('Exception: ', '');
      });
    }
  }

  List<Map<String, dynamic>> get _availableSizes => List<dynamic>.from(
    _model?['sizes'] ?? [],
  ).map((item) => Map<String, dynamic>.from(item as Map)).toList();

  int get _referenceLimit {
    final limits = <int>[
      int.tryParse('${_purpose?['max_product_references'] ?? ''}') ?? 0,
      int.tryParse('${_model?['max_product_images'] ?? ''}') ?? 0,
      4,
    ].where((value) => value > 0).toList();
    return limits.reduce(
      (smallest, value) => value < smallest ? value : smallest,
    );
  }

  int get _referenceCount =>
      _productIds.length + _directReferenceUploads.length;

  List<Map<String, dynamic>> get _purposeFields {
    // A selected category/subcategory scope is the only source of Brief
    // fields. Parent Type fields were legacy fallbacks and must not leak into
    // a different subcategory's flow.
    if (_scope != null) {
      return _asMapList(_scope?['brief_fields'] ?? _scope?['fields']);
    }
    return <Map<String, dynamic>>[];
  }

  List<Map<String, dynamic>> _asMapList(dynamic source) {
    if (source is! List) return <Map<String, dynamic>>[];
    return source
        .whereType<Map>()
        .map((item) => Map<String, dynamic>.from(item))
        .toList();
  }

  Map<String, dynamic>? _defaultBusiness(
    List<Map<String, dynamic>> businesses,
  ) {
    if (businesses.isEmpty) return null;
    return businesses.firstWhere(
      (business) =>
          business['is_default'] == true ||
          business['isDefault'] == true ||
          business['is_default'] == 1 ||
          business['isDefault'] == 1,
      orElse: () => businesses.first,
    );
  }

  String _businessLabel(Map<String, dynamic> business) {
    for (final key in const [
      'business_name',
      'name',
      'title',
      'display_name',
    ]) {
      final value = business[key]?.toString().trim();
      if (value != null && value.isNotEmpty) return value;
    }
    return 'My Business';
  }

  List<Map<String, dynamic>> get _scopesForPurpose {
    final directScopes = _asMapList(
      _purpose?['scopes'] ??
          _purpose?['category_scopes'] ??
          _purpose?['business_scopes'],
    );
    if (directScopes.isNotEmpty) {
      return directScopes.where(_scopeMatchesBusiness).toList();
    }

    final purposeKey = _purpose?['key']?.toString();
    if (purposeKey == null || purposeKey.isEmpty)
      return <Map<String, dynamic>>[];
    return _asMapList(_availableScopes)
        .where((scope) {
          final directKey =
              scope['purpose_key'] ??
              scope['custom_post_type_key'] ??
              scope['business_ai_purpose_key'];
          if (directKey?.toString() == purposeKey) return true;
          final keys = scope['purpose_keys'];
          return keys is List &&
              keys.map((item) => item.toString()).contains(purposeKey);
        })
        .where(_scopeMatchesBusiness)
        .toList();
  }

  List<Map<String, dynamic>> get _availableStyles {
    final scopedStyles = _asMapList(_scope?['styles']);
    if (scopedStyles.isNotEmpty) return scopedStyles;
    final purposeStyles = _asMapList(_purpose?['styles']);
    if (purposeStyles.isNotEmpty) return purposeStyles;
    return _asMapList(_styles);
  }

  void _syncSelectedStyle() {
    final styles = _availableStyles;
    if (styles.isEmpty) {
      _style = null;
      return;
    }
    final selectedKey = _style?['key']?.toString();
    _style = styles.firstWhere(
      (style) => style['key']?.toString() == selectedKey,
      orElse: () => styles.first,
    );
  }

  bool _scopeMatchesBusiness(Map<String, dynamic> scope) {
    final matchingIds = scope['matching_business_ids'];
    if (matchingIds is! List || matchingIds.isEmpty) return true;
    final businessId = _business?['id']?.toString();
    return businessId != null &&
        matchingIds.map((id) => id.toString()).contains(businessId);
  }

  String _scopeLabel(Map<String, dynamic> scope) {
    for (final key in const ['display_name', 'label', 'title', 'name']) {
      final value = scope[key]?.toString().trim();
      if (value != null && value.isNotEmpty) return value;
    }
    final categoryObject = scope['category'];
    final subcategoryObject = scope['subcategory'] ?? scope['sub_category'];
    final category =
        scope['category_name'] ??
        scope['business_category_name'] ??
        (categoryObject is Map ? categoryObject['name'] : null);
    final subcategory =
        scope['subcategory_name'] ??
        scope['sub_category_name'] ??
        scope['business_subcategory_name'] ??
        (subcategoryObject is Map ? subcategoryObject['name'] : null);
    final values = [category, subcategory]
        .map((item) => item?.toString().trim() ?? '')
        .where((item) => item.isNotEmpty)
        .toList();
    return values.isEmpty ? 'Business category' : values.join(' / ');
  }

  Map<String, dynamic> _scopePayload() {
    final scope = _scope;
    if (scope == null) return <String, dynamic>{};
    final payload = <String, dynamic>{};
    final scopeId = scope['scope_id'] ?? scope['id'];
    final category = scope['category'];
    final subcategory = scope['subcategory'] ?? scope['sub_category'];
    final categoryId =
        scope['category_id'] ??
        scope['business_category_id'] ??
        (category is Map ? category['id'] : null);
    final subcategoryId =
        scope['subcategory_id'] ??
        scope['sub_category_id'] ??
        scope['business_subcategory_id'] ??
        (subcategory is Map ? subcategory['id'] : null);
    if (scopeId != null) payload['scope_id'] = scopeId;
    if (categoryId != null) payload['category_id'] = categoryId;
    if (subcategoryId != null) payload['subcategory_id'] = subcategoryId;
    return payload;
  }

  int get _remaining => int.tryParse('${_quota?['remaining'] ?? 0}') ?? 0;
  bool get _canUseBusinessTheme {
    final theme = _business?['brand_theme'];
    if (theme is! Map) return false;
    final primary = theme['primary_color']?.toString() ?? '';
    final secondary = theme['secondary_color']?.toString() ?? '';
    return RegExp(r'^#[A-Fa-f0-9]{6}$').hasMatch(primary) &&
        RegExp(r'^#[A-Fa-f0-9]{6}$').hasMatch(secondary);
  }

  bool get _canGenerate =>
      _purpose != null &&
      _style != null &&
      _model != null &&
      _quality != null &&
      _sizeKey != null;

  void _syncBriefFields() {
    final validKeys = _purposeFields
        .map((item) => item['key'].toString())
        .toSet();
    for (final key
        in _brief.keys.where((key) => !validKeys.contains(key)).toList()) {
      _brief.remove(key)?.dispose();
    }
    for (final field in _purposeFields) {
      _brief.putIfAbsent(field['key'].toString(), TextEditingController.new);
    }
  }

  void _selectPurpose(Map<String, dynamic> purpose) {
    setState(() {
      _purpose = purpose;
      final scopes = _scopesForPurpose;
      _scope = scopes.length == 1 ? scopes.first : null;
      _paletteMode = 'style_colors';
      _generationKind = 'initial';
      _contentPreview = null;
      _previewNotice = null;
      _syncSelectedStyle();
      _syncBriefFields();
    });
  }

  void _startBrief() {
    if (_purpose == null) {
      _showError('Please choose a Custom Post Type.');
      return;
    }
    if (_scope == null) {
      _showError(
        _scopesForPurpose.isEmpty
            ? 'No Custom Post Type is available for your active business yet.'
            : 'This Custom Post Type is not available for your active business.',
      );
      return;
    }
    setState(() => _step = 1);
  }

  Future<void> _loadProducts() async {
    if (_products.isNotEmpty) return;
    final prefs = await SharedPreferences.getInstance();
    final userId = prefs.getString('userId') ?? '';
    final response = await ApiService.post('/products/list', {
      'userId': userId,
    });
    final data = jsonDecode(response.body);
    if (response.statusCode != 200 || data is! Map || data['success'] != true) {
      throw Exception(
        data is Map ? data['message'] : 'Products could not be loaded.',
      );
    }
    _products = List<dynamic>.from(data['products']?['data'] ?? []);
  }

  Future<void> _chooseProducts() async {
    try {
      await _loadProducts();
      if (!mounted) return;
      final selected = Set<int>.from(_productIds);
      final referenceLimit = _referenceLimit;
      await showModalBottomSheet<void>(
        context: context,
        isScrollControlled: true,
        builder: (sheetContext) => StatefulBuilder(
          builder: (_, setSheetState) => SafeArea(
            child: SizedBox(
              height: MediaQuery.of(context).size.height * .72,
              child: Column(
                children: [
                  ListTile(
                    title: Text(
                      'Add product photos',
                      style: TextStyle(fontWeight: FontWeight.w800),
                    ),
                    subtitle: Text(
                      'Up to $referenceLimit reference photos go into one AI artwork generation.',
                    ),
                  ),
                  Expanded(
                    child: ListView.builder(
                      itemCount: _products.length,
                      itemBuilder: (_, index) {
                        final product = Map<String, dynamic>.from(
                          _products[index] as Map,
                        );
                        final id = int.tryParse('${product['id']}') ?? 0;
                        final checked = selected.contains(id);
                        return CheckboxListTile(
                          value: checked,
                          title: Text(
                            '${product['display_name'] ?? product['name'] ?? 'Product'}',
                          ),
                          subtitle: Text(
                            checked ? 'Selected' : 'Tap to select',
                          ),
                          onChanged: (value) {
                            if (value == true &&
                                selected.length +
                                        _directReferenceUploads.length >=
                                    referenceLimit &&
                                !checked) {
                              ScaffoldMessenger.of(context).showSnackBar(
                                SnackBar(
                                  content: Text(
                                    'This Custom Post supports up to $referenceLimit reference photos.',
                                  ),
                                ),
                              );
                              return;
                            }
                            setSheetState(
                              () => value == true
                                  ? selected.add(id)
                                  : selected.remove(id),
                            );
                          },
                        );
                      },
                    ),
                  ),
                  Padding(
                    padding: const EdgeInsets.all(16),
                    child: FilledButton(
                      onPressed: () {
                        setState(
                          () => _productIds
                            ..clear()
                            ..addAll(selected),
                        );
                        Navigator.pop(sheetContext);
                      },
                      style: _primaryButtonStyle,
                      child: Text(
                        'Use ${selected.length} photo${selected.length == 1 ? '' : 's'}',
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ),
        ),
      );
    } catch (error) {
      _showError(error.toString().replaceFirst('Exception: ', ''));
    }
  }

  Future<void> _uploadDeviceReferences() async {
    final remaining = _referenceLimit - _referenceCount;
    if (remaining <= 0) {
      _showError(
        'This Custom Post already has the maximum $_referenceLimit reference photos.',
      );
      return;
    }

    try {
      final selected = await ImagePicker().pickMultiImage(imageQuality: 92);
      if (selected.isEmpty) return;
      if (selected.length > remaining) {
        _showError(
          'Only $remaining more reference photo${remaining == 1 ? '' : 's'} can be added.',
        );
      }

      final uploads = <Map<String, dynamic>>[];
      for (final file in selected.take(remaining)) {
        final response = await ApiService.multipartPost(
          '/business-ai/reference-uploads',
          const <String, String>{},
          fileKey: 'image',
          filePath: file.path,
        );
        final data = jsonDecode(response.body);
        if (response.statusCode != 201 ||
            data is! Map ||
            data['success'] != true ||
            data['upload'] is! Map) {
          throw Exception(
            data is Map
                ? data['message']
                : 'The selected image could not be uploaded.',
          );
        }
        uploads.add(Map<String, dynamic>.from(data['upload'] as Map));
      }

      if (!mounted || uploads.isEmpty) return;
      setState(() => _directReferenceUploads.addAll(uploads));
    } catch (error) {
      _showError(error.toString().replaceFirst('Exception: ', ''));
    }
  }

  bool _validateBrief() {
    for (final field in _purposeFields) {
      if (field['required'] == true &&
          (_brief[field['key'].toString()]?.text.trim().isEmpty ?? true)) {
        _showError('${field['label']} is required.');
        return false;
      }
    }
    return true;
  }

  Map<String, String> _briefPayload() {
    final brief = <String, String>{
      for (final item in _brief.entries)
        if (item.value.text.trim().isNotEmpty) item.key: item.value.text.trim(),
    };
    if (_extraDetail.text.trim().isNotEmpty) {
      brief['extra_detail'] = _extraDetail.text.trim();
    }
    return brief;
  }

  Map<String, String> _approvedContentPayload() => <String, String>{
    'headline': _reviewHeadline.text.trim(),
    'content': _reviewContent.text.trim(),
    'cta': _reviewCta.text.trim(),
  };

  String _generationInstruction({String? versionInstruction}) {
    final pieces = <String>[];
    final requestedVisualChange =
        versionInstruction ?? _visualInstruction.text.trim();
    if (requestedVisualChange.isNotEmpty) {
      pieces.add(requestedVisualChange);
    }

    final approved = _approvedContentPayload();
    if (approved.values.any((value) => value.isNotEmpty)) {
      pieces.add(
        'Use this user-approved post content. Keep it accurate and readable. '
        'Headline: ${approved['headline'] ?? ''}\n'
        'Content: ${approved['content'] ?? ''}\n'
        'CTA: ${approved['cta'] ?? ''}',
      );
    }

    final instruction = pieces.join('\n\n').trim();
    return instruction.length <= 1000
        ? instruction
        : instruction.substring(0, 1000);
  }

  Future<void> _generate({
    String? versionInstruction,
    String? generationKind,
  }) async {
    if (!_validateBrief()) return;
    if (_scope == null) {
      _showError('Please choose a business category before generating.');
      return;
    }
    if (_contentPreview == null) {
      _showError(
        'Please review the post content before generating the design.',
      );
      return;
    }
    if (!_canGenerate || _remaining < _generationCost) {
      _showError(
        _remaining < _generationCost
            ? 'Not enough AI credits available.'
            : 'Choose a model, quality and size.',
      );
      return;
    }
    final brief = _briefPayload();
    final kind = generationKind ?? _generationKind;
    final parentGenerationId = _job?['id'];
    setState(() => _submitting = true);
    try {
      final response = await ApiService.post('/business-ai/generations', {
        'purpose_key': _purpose!['key'],
        ..._scopePayload(),
        'style_key': _style!['key'],
        'palette_mode': _paletteMode,
        'model_id': _model!['id'],
        'quality': _quality,
        'size_key': _sizeKey,
        'language_id': _languages.isEmpty ? null : _languages.first['id'],
        'brief': brief,
        'content_preview': _approvedContentPayload(),
        'user_instruction': _generationInstruction(
          versionInstruction: versionInstruction,
        ),
        'product_ids': _productIds.toList(),
        'reference_upload_ids': _directReferenceUploads
            .map((upload) => upload['id'])
            .toList(),
        'generation_kind': kind,
        if (kind != 'initial' && parentGenerationId != null)
          'parent_generation_id': parentGenerationId,
      });
      final data = jsonDecode(response.body);
      if (response.statusCode != 202 ||
          data is! Map ||
          data['success'] != true) {
        throw Exception(
          data is Map ? data['message'] : 'Generation could not be started.',
        );
      }
      setState(() {
        _job = Map<String, dynamic>.from(data['job'] as Map);
        _quota = {
          ...?_quota,
          'remaining': (_remaining - _generationCost).clamp(0, 999999),
        };
        _step = 4;
      });
      _startPolling();
    } catch (error) {
      _showError(error.toString().replaceFirst('Exception: ', ''));
    } finally {
      if (mounted) setState(() => _submitting = false);
    }
  }

  void _startPolling() {
    _poller?.cancel();
    _poller = Timer.periodic(const Duration(seconds: 3), (_) => _refreshJob());
    _refreshJob();
  }

  Future<void> _refreshJob() async {
    final id = _job?['id'];
    if (id == null) return;
    try {
      final response = await ApiService.get('/business-ai/generations/$id');
      final data = jsonDecode(response.body);
      if (response.statusCode == 200 &&
          data is Map &&
          data['success'] == true &&
          data['job'] is Map) {
        final job = Map<String, dynamic>.from(data['job'] as Map);
        if (mounted) setState(() => _job = job);
        if (job['status'] == 'completed' || job['status'] == 'failed')
          _poller?.cancel();
      }
    } catch (_) {}
  }

  void _showError(String message) {
    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(message),
        backgroundColor: const Color(0xFFDC2626),
      ),
    );
  }

  Future<void> _saveDraft() async {
    final id = _job?['id'];
    if (id == null || _savingDraft) return;
    setState(() => _savingDraft = true);
    try {
      final response = await ApiService.post(
        '/business-ai/generations/$id/draft',
        const <String, dynamic>{},
      );
      final data = jsonDecode(response.body);
      if (response.statusCode != 200 ||
          data is! Map ||
          data['success'] != true) {
        throw Exception(
          data is Map ? data['message'] : 'Draft could not be saved.',
        );
      }
      if (!mounted) return;
      if (data['draft'] is Map) {
        setState(() => _job = Map<String, dynamic>.from(data['draft'] as Map));
      }
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Saved in My Designs. You can restore it anytime.'),
        ),
      );
    } catch (error) {
      _showError(error.toString().replaceFirst('Exception: ', ''));
    } finally {
      if (mounted) setState(() => _savingDraft = false);
    }
  }

  PreferredSizeWidget _buildAppBar() {
    String title = '';
    Widget? leading;
    List<Widget>? actions;

    switch (_step) {
      case 0:
        title = 'Artera Create';
        leading = IconButton(
          icon: const Icon(Icons.menu, color: Color(0xFF172033)),
          onPressed: () => Navigator.pop(context),
        );
        actions = [
          IconButton(
            icon: const Icon(
              Icons.notifications_outlined,
              color: Color(0xFF172033),
            ),
            onPressed: () {},
          ),
        ];
        break;
      case 1:
        title = 'Add Post Brief';
        leading = IconButton(
          icon: const Icon(Icons.arrow_back, color: Color(0xFF172033)),
          onPressed: () => setState(() => _step = 0),
        );
        actions = [
          IconButton(
            icon: const Icon(Icons.help_outline, color: Color(0xFF172033)),
            onPressed: () {},
          ),
        ];
        break;
      case 2:
        title = 'Review Post Content';
        leading = IconButton(
          icon: const Icon(Icons.arrow_back, color: Color(0xFF172033)),
          onPressed: () => setState(() => _step = 1),
        );
        actions = [
          IconButton(
            icon: const Icon(Icons.help_outline, color: Color(0xFF172033)),
            onPressed: () {},
          ),
        ];
        break;
      case 3:
        title = 'Select Custom Post Style';
        leading = IconButton(
          icon: const Icon(Icons.arrow_back, color: Color(0xFF172033)),
          onPressed: () => setState(() => _step = 2),
        );
        actions = [
          IconButton(
            icon: const Icon(Icons.help_outline, color: Color(0xFF172033)),
            onPressed: () {},
          ),
        ];
        break;
      case 4:
        title = 'AI Preview - Editable Design';
        leading = IconButton(
          icon: const Icon(Icons.arrow_back, color: Color(0xFF172033)),
          onPressed: () => setState(() => _step = 3),
        );
        actions = [
          IconButton(
            icon: const Icon(
              Icons.file_download_outlined,
              color: Color(0xFF172033),
            ),
            onPressed: () {},
          ),
        ];
        break;
    }

    return AppBar(
      backgroundColor: const Color(0xFFF8FAFC),
      elevation: 0,
      centerTitle: true,
      leading: leading,
      title: Text(
        title,
        style: const TextStyle(
          color: Color(0xFF172033),
          fontWeight: FontWeight.w800,
          fontSize: 16,
        ),
      ),
      actions: actions,
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF8FAFC),
      appBar: _buildAppBar(),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : _error != null
          ? _errorState()
          : SafeArea(
              top: false,
              child: Column(
                children: [
                  _stepper(),
                  Expanded(
                    child: AnimatedSwitcher(
                      duration: const Duration(milliseconds: 220),
                      child: _currentStep(),
                    ),
                  ),
                ],
              ),
            ),
    );
  }

  Widget _currentStep() {
    return switch (_step) {
      0 => _choosePurpose(),
      1 => _addBrief(),
      2 => _reviewPostContent(),
      3 => _selectStyle(),
      _ => _generatedArtworkPreview(),
    };
  }

  Widget _stepper() {
    return Container(
      color: const Color(0xFFF8FAFC),
      padding: const EdgeInsets.only(top: 10, bottom: 20),
      child: Column(
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: List.generate(9, (index) {
              if (index.isOdd) {
                return Container(
                  width: 32,
                  height: 1.5,
                  color: const Color(0xFFE2E8F0),
                );
              }
              final stepIndex = index ~/ 2;
              final isCompleted = stepIndex < _step;
              final isActive = stepIndex == _step;
              final color = isCompleted || isActive
                  ? const Color(0xFF6434E8)
                  : const Color(0xFFCBD5E1);

              return Container(
                width: 28,
                height: 28,
                alignment: Alignment.center,
                decoration: BoxDecoration(
                  shape: BoxShape.circle,
                  color: isActive ? color : Colors.white,
                  border: Border.all(color: color, width: 1.5),
                ),
                child: Text(
                  '${stepIndex + 1}',
                  style: TextStyle(
                    color: isActive ? Colors.white : color,
                    fontWeight: FontWeight.bold,
                    fontSize: 13,
                  ),
                ),
              );
            }),
          ),
          const SizedBox(height: 12),
          Text(
            'Step ${_step + 1} of 5',
            style: const TextStyle(
              fontWeight: FontWeight.bold,
              fontSize: 13,
              color: Color(0xFF475569),
            ),
          ),
        ],
      ),
    );
  }

  Widget _choosePurpose() => ListView(
    key: const ValueKey('purpose'),
    padding: const EdgeInsets.fromLTRB(20, 16, 20, 36),
    children: [
      InkWell(
        onTap: _openBusinessContactDetails,
        borderRadius: BorderRadius.circular(16),
        child: Ink(
          padding: const EdgeInsets.all(16),
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(16),
            boxShadow: [
              BoxShadow(
                color: Colors.black.withOpacity(0.04),
                blurRadius: 10,
                offset: const Offset(0, 4),
              ),
            ],
          ),
          child: Row(
            children: [
              Container(
                width: 40,
                height: 40,
                decoration: BoxDecoration(
                  color: const Color(0xFFF3F4F6),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: const Center(
                  child: Text(
                    'A',
                    style: TextStyle(
                      color: Color(0xFFE11D48),
                      fontSize: 24,
                      fontWeight: FontWeight.w900,
                    ),
                  ),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      _business == null
                          ? 'Choose an active business'
                          : _businessLabel(_business!),
                      style: const TextStyle(
                        fontWeight: FontWeight.bold,
                        fontSize: 16,
                      ),
                    ),
                    Text(
                      'Logo • Contacts • My Business',
                      style: TextStyle(color: Colors.grey, fontSize: 12),
                    ),
                  ],
                ),
              ),
              const Icon(Icons.chevron_right, color: Colors.grey),
            ],
          ),
        ),
      ),
      const SizedBox(height: 24),
      const Text(
        'What will you create?',
        style: TextStyle(
          fontSize: 20,
          fontWeight: FontWeight.w900,
          color: Color(0xFF172033),
        ),
      ),
      const SizedBox(height: 16),
      GridView.builder(
        shrinkWrap: true,
        physics: const NeverScrollableScrollPhysics(),
        itemCount: _purposes.length,
        gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
          crossAxisCount: 2,
          mainAxisSpacing: 16,
          crossAxisSpacing: 16,
          mainAxisExtent: 172,
        ),
        itemBuilder: (_, index) {
          final purpose = Map<String, dynamic>.from(_purposes[index] as Map);
          final isSelected = purpose['key'] == _purpose?['key'];
          return InkWell(
            onTap: () => _selectPurpose(purpose),
            borderRadius: BorderRadius.circular(20),
            child: Ink(
              decoration: BoxDecoration(
                color: isSelected ? const Color(0xFF6434E8) : Colors.white,
                borderRadius: BorderRadius.circular(20),
                border: Border.all(
                  color: isSelected
                      ? const Color(0xFF6434E8)
                      : const Color(0xFFF1F5F9),
                  width: 1.5,
                ),
                boxShadow: isSelected
                    ? [
                        BoxShadow(
                          color: const Color(0xFF6434E8).withOpacity(0.3),
                          blurRadius: 12,
                          offset: const Offset(0, 6),
                        ),
                      ]
                    : [
                        BoxShadow(
                          color: Colors.black.withOpacity(0.03),
                          blurRadius: 8,
                          offset: const Offset(0, 4),
                        ),
                      ],
              ),
              padding: const EdgeInsets.all(14),
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Container(
                    width: 54,
                    height: 54,
                    decoration: BoxDecoration(
                      color: isSelected
                          ? Colors.white.withOpacity(0.18)
                          : _purposeAccent(
                              '${purpose['icon']}',
                            ).withOpacity(0.12),
                      borderRadius: BorderRadius.circular(18),
                    ),
                    child: Icon(
                      _purposeIcon('${purpose['icon']}'),
                      color: isSelected
                          ? Colors.white
                          : _purposeAccent('${purpose['icon']}'),
                      size: 28,
                    ),
                  ),
                  const SizedBox(height: 10),
                  Text(
                    '${purpose['title']}',
                    textAlign: TextAlign.center,
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                    style: TextStyle(
                      fontWeight: FontWeight.w800,
                      fontSize: 13.5,
                      height: 1.12,
                      color: isSelected
                          ? Colors.white
                          : const Color(0xFF172033),
                    ),
                  ),
                  const SizedBox(height: 4),
                  Builder(
                    builder: (context) {
                      final text =
                          (purpose['subtitle'] ?? purpose['description'])
                              ?.toString();
                      if (text == null || text.trim().isEmpty || text == 'null')
                        return const SizedBox.shrink();
                      return Text(
                        text,
                        textAlign: TextAlign.center,
                        maxLines: 2,
                        overflow: TextOverflow.ellipsis,
                        style: TextStyle(
                          fontSize: 10,
                          color: isSelected
                              ? Colors.white70
                              : const Color(0xFF64748B),
                          height: 1.2,
                        ),
                      );
                    },
                  ),
                ],
              ),
            ),
          );
        },
      ),
      if (_purpose != null) ...[
        const SizedBox(height: 24),
        Container(
          padding: const EdgeInsets.all(16),
          decoration: BoxDecoration(
            color: const Color(0xFFF7F5FF),
            borderRadius: BorderRadius.circular(16),
            border: Border.all(color: const Color(0xFFE2D9FF)),
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                'Selected: ${_purpose?['title']}',
                style: const TextStyle(
                  fontWeight: FontWeight.w800,
                  color: Color(0xFF3D237C),
                ),
              ),
              const SizedBox(height: 12),
              if (_scope != null) ...[
                const Text(
                  'Available for active business',
                  style: TextStyle(
                    fontWeight: FontWeight.w700,
                    fontSize: 13,
                    color: Color(0xFF334155),
                  ),
                ),
                const SizedBox(height: 6),
                Text(
                  _scopeLabel(_scope!),
                  style: const TextStyle(
                    color: Color(0xFF475569),
                    fontSize: 13,
                  ),
                ),
              ] else
                const Row(
                  children: [
                    Icon(
                      Icons.info_outline,
                      color: Color(0xFFB45309),
                      size: 18,
                    ),
                    SizedBox(width: 8),
                    Expanded(
                      child: Text(
                        'No Custom Post data is available for your active business. Update My Business or ask Admin to add this subcategory data.',
                        style: TextStyle(
                          color: Color(0xFF475569),
                          fontSize: 12,
                        ),
                      ),
                    ),
                  ],
                ),
            ],
          ),
        ),
        const SizedBox(height: 18),
        FilledButton.icon(
          onPressed: _startBrief,
          style: _primaryButtonStyle,
          icon: const Icon(Icons.arrow_forward),
          label: const Text('Continue to Brief'),
        ),
      ],
    ],
  );

  Future<void> _openBusinessContactDetails() async {
    final homeController = Get.find<HomeController>();
    await homeController.loadBusinessInfo();
    if (!mounted) return;

    if (homeController.businesses.isEmpty) {
      await Get.to<dynamic>(() => const BusinessProfileScreen(isNew: true));
      return;
    }

    final selected = homeController.businesses.firstWhere(
      (item) =>
          item is Map &&
          (item['isDefault'] == true ||
              item['is_default'] == true ||
              item['is_default'] == 1 ||
              item['is_default'] == '1'),
      orElse: () => homeController.businesses.first,
    );
    if (selected is! Map) return;

    await Get.to<dynamic>(
      () =>
          BusinessProfileScreen(business: Map<String, dynamic>.from(selected)),
    );
    if (mounted) {
      await homeController.loadBusinessInfo();
      await _loadOptions();
    }
  }

  InputDecoration _inputDecoration(String hint) => InputDecoration(
    hintText: hint,
    hintStyle: const TextStyle(color: Color(0xFF94A3B8), fontSize: 14),
    contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
    border: OutlineInputBorder(
      borderRadius: BorderRadius.circular(12),
      borderSide: const BorderSide(color: Color(0xFFCBD5E1)),
    ),
    enabledBorder: OutlineInputBorder(
      borderRadius: BorderRadius.circular(12),
      borderSide: const BorderSide(color: Color(0xFFCBD5E1)),
    ),
    focusedBorder: OutlineInputBorder(
      borderRadius: BorderRadius.circular(12),
      borderSide: const BorderSide(color: Color(0xFF6434E8), width: 1.5),
    ),
    filled: true,
    fillColor: Colors.white,
  );

  Widget _addBrief() => ListView(
    key: const ValueKey('brief'),
    padding: const EdgeInsets.fromLTRB(20, 0, 20, 32),
    children: [
      Text(
        '${_purpose?['title'] ?? 'Custom Post'} Brief',
        style: const TextStyle(fontSize: 22, fontWeight: FontWeight.w900),
      ),
      const SizedBox(height: 6),
      Text(
        _scope == null
            ? 'Add details for your post.'
            : 'For ${_scopeLabel(_scope!)}',
        style: const TextStyle(color: Color(0xFF64748B)),
      ),
      const SizedBox(height: 16),
      for (final field in _purposeFields)
        Padding(
          padding: const EdgeInsets.only(bottom: 16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                '${field['label']}${field['required'] == true ? ' *' : ''}',
                style: const TextStyle(
                  fontWeight: FontWeight.w600,
                  fontSize: 13,
                  color: Color(0xFF334155),
                ),
              ),
              const SizedBox(height: 6),
              TextField(
                controller: _brief[field['key'].toString()],
                maxLines: field['multiline'] == true ? 3 : 1,
                decoration: _inputDecoration('${field['hint'] ?? ''}'),
              ),
            ],
          ),
        ),
      const Text(
        'Extra Detail (Optional)',
        style: TextStyle(
          fontWeight: FontWeight.w600,
          fontSize: 13,
          color: Color(0xFF334155),
        ),
      ),
      const SizedBox(height: 6),
      TextField(
        controller: _extraDetail,
        maxLines: 3,
        decoration: _inputDecoration(
          'Example: Weekend appointments are available.',
        ),
      ),
      const SizedBox(height: 20),
      const Text(
        'Design instruction (Optional)',
        style: TextStyle(
          fontWeight: FontWeight.w600,
          fontSize: 13,
          color: Color(0xFF334155),
        ),
      ),
      const SizedBox(height: 6),
      TextField(
        controller: _visualInstruction,
        maxLines: 3,
        decoration: _inputDecoration(
          'Example: Keep space on the left for the headline.',
        ),
      ),
      if (_purpose?['product_upload_enabled'] == true) ...[
        const SizedBox(height: 20),
        _referenceImagePicker(),
      ],
      const SizedBox(height: 16),
      Container(
        padding: const EdgeInsets.all(12),
        decoration: BoxDecoration(
          color: const Color(0xFFF0FDF4),
          borderRadius: BorderRadius.circular(12),
        ),
        child: const Row(
          children: [
            Icon(Icons.fact_check_outlined, color: Color(0xFF15803D), size: 20),
            SizedBox(width: 10),
            Expanded(
              child: Text(
                'Next, you will review the post content before any image is generated.',
                style: TextStyle(
                  color: Color(0xFF166534),
                  fontSize: 12,
                  fontWeight: FontWeight.w600,
                ),
              ),
            ),
          ],
        ),
      ),
      const SizedBox(height: 28),
      FilledButton.icon(
        onPressed: _previewLoading ? null : _requestContentPreview,
        style: _primaryButtonStyle,
        icon: _previewLoading
            ? const SizedBox(
                width: 18,
                height: 18,
                child: CircularProgressIndicator(
                  strokeWidth: 2,
                  color: Colors.white,
                ),
              )
            : const Icon(Icons.auto_awesome_outlined),
        label: Text(
          _previewLoading ? 'Preparing content...' : 'Review Post Content',
        ),
      ),
    ],
  );

  Widget _referenceImagePicker() => Column(
    crossAxisAlignment: CrossAxisAlignment.start,
    children: [
      const Text(
        'Reference images (Optional)',
        style: TextStyle(
          fontWeight: FontWeight.w600,
          fontSize: 13,
          color: Color(0xFF334155),
        ),
      ),
      const SizedBox(height: 6),
      Text(
        '$_referenceCount of $_referenceLimit selected. Add product images from My Business or upload from this device.',
        style: const TextStyle(color: Color(0xFF64748B), fontSize: 12),
      ),
      const SizedBox(height: 10),
      Row(
        children: [
          Expanded(
            child: OutlinedButton.icon(
              onPressed: _chooseProducts,
              icon: const Icon(Icons.inventory_2_outlined),
              label: const Text('My Business'),
              style: OutlinedButton.styleFrom(
                minimumSize: const Size.fromHeight(48),
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(12),
                ),
              ),
            ),
          ),
          const SizedBox(width: 10),
          Expanded(
            child: OutlinedButton.icon(
              onPressed: _uploadDeviceReferences,
              icon: const Icon(Icons.upload_file_outlined),
              label: const Text('Upload device'),
              style: OutlinedButton.styleFrom(
                minimumSize: const Size.fromHeight(48),
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(12),
                ),
              ),
            ),
          ),
        ],
      ),
      if (_directReferenceUploads.isNotEmpty) ...[
        const SizedBox(height: 10),
        Wrap(
          spacing: 8,
          runSpacing: 8,
          children: _directReferenceUploads.map((upload) {
            final name = upload['name']?.toString().trim().isNotEmpty == true
                ? upload['name'].toString()
                : 'Device image';
            return InputChip(
              avatar: const Icon(Icons.image_outlined, size: 16),
              label: Text(name, overflow: TextOverflow.ellipsis),
              onDeleted: () =>
                  setState(() => _directReferenceUploads.remove(upload)),
            );
          }).toList(),
        ),
      ],
    ],
  );

  Future<void> _requestContentPreview() async {
    if (!_validateBrief()) return;
    if (_purpose == null) {
      _showError('Please choose a Custom Post Type.');
      return;
    }
    if (_scope == null) {
      _showError('Please choose a business category before preparing content.');
      return;
    }

    setState(() {
      _previewLoading = true;
      _previewNotice = null;
    });

    try {
      final response = await ApiService.post('/business-ai/content-preview', {
        'purpose_key': _purpose!['key'],
        ..._scopePayload(),
        if (_style?['key'] != null) 'style_key': _style!['key'],
        'palette_mode': _paletteMode,
        'language_id': _languages.isEmpty ? null : _languages.first['id'],
        'brief': _briefPayload(),
        'user_instruction': _visualInstruction.text.trim(),
      });
      final data = jsonDecode(response.body);
      if (response.statusCode >= 200 &&
          response.statusCode < 300 &&
          data is Map &&
          data['success'] == true &&
          data['preview'] is Map) {
        _applyContentPreview(Map<String, dynamic>.from(data['preview'] as Map));
        return;
      }

      if (response.statusCode == 401 ||
          response.statusCode == 403 ||
          response.statusCode == 422) {
        _showError(
          data is Map
              ? '${data['message'] ?? 'Content preview could not be prepared.'}'
              : 'Content preview could not be prepared.',
        );
        return;
      }
      _applyContentPreview(
        _fallbackContentPreview(),
        notice:
            'Live content data is not available yet. Please review this draft carefully.',
      );
    } catch (error) {
      _applyContentPreview(
        _fallbackContentPreview(),
        notice:
            'Live content data is not available yet. Please review this draft carefully.',
      );
    } finally {
      if (mounted) setState(() => _previewLoading = false);
    }
  }

  void _applyContentPreview(Map<String, dynamic> preview, {String? notice}) {
    final fallback = _fallbackContentPreview();
    final merged = <String, dynamic>{...fallback, ...preview};
    final contentLines = merged['content_lines'];
    final content = merged['content']?.toString().trim().isNotEmpty == true
        ? merged['content'].toString().trim()
        : _contentFromLines(contentLines);
    if (!mounted) return;
    setState(() {
      _contentPreview = <String, dynamic>{...merged, 'content': content};
      _reviewHeadline.text =
          merged['headline']?.toString().trim().isNotEmpty == true
          ? merged['headline'].toString().trim()
          : fallback['headline'].toString();
      _reviewContent.text = content.isNotEmpty
          ? content
          : fallback['content'].toString();
      _reviewCta.text = merged['cta']?.toString().trim().isNotEmpty == true
          ? merged['cta'].toString().trim()
          : fallback['cta'].toString();
      _previewNotice = notice;
      _step = 2;
    });
  }

  String _contentFromLines(dynamic lines) {
    if (lines is! List) return '';
    return lines
        .map((line) {
          if (line is Map)
            return (line['text'] ?? line['content'] ?? line['value'] ?? '')
                .toString()
                .trim();
          return line.toString().trim();
        })
        .where((line) => line.isNotEmpty)
        .join('\n');
  }

  Map<String, dynamic> _fallbackContentPreview() {
    final brief = _briefPayload();
    final businessName = _business == null
        ? 'your business'
        : _businessLabel(_business!);
    final details = brief.values
        .where((value) => value.trim().isNotEmpty)
        .toList();
    final firstDetail = details.isEmpty ? null : details.first;
    final ctaCandidates = brief.entries
        .where(
          (entry) =>
              entry.key.toLowerCase().contains('cta') &&
              entry.value.trim().isNotEmpty,
        )
        .toList();
    final ctaEntry = ctaCandidates.isEmpty ? null : ctaCandidates.first;
    final scopeGeneralData =
        _scope?['general_data'] ?? _scope?['general_content'];
    return <String, dynamic>{
      'headline': firstDetail == null
          ? '${_purpose?['title'] ?? 'Custom Post'} for $businessName'
          : firstDetail,
      'content':
          'A clear ${_purpose?['title'] ?? 'custom post'} based on your selected business details. Review and edit the message before generating the design.',
      'cta': ctaEntry?.value ?? 'Contact us today',
      'user_brief': brief,
      'my_business_data': _business ?? <String, dynamic>{},
      'general_data': scopeGeneralData ?? <String>[],
      'content_instruction': _scope?['content_instruction'],
    };
  }

  List<String> _sourceLines(dynamic source, {required String fallback}) {
    if (source == null) return <String>[fallback];
    if (source is String)
      return source.trim().isEmpty
          ? <String>[fallback]
          : <String>[source.trim()];
    if (source is List) {
      final lines = source
          .expand((item) => _sourceLines(item, fallback: ''))
          .where((item) => item.isNotEmpty)
          .toList();
      return lines.isEmpty ? <String>[fallback] : lines;
    }
    if (source is Map) {
      final directText = source['text'] ?? source['point'];
      if (directText != null) {
        return _sourceLines(directText, fallback: fallback);
      }
      final nested =
          source['items'] ??
          source['data'] ??
          source['facts'] ??
          source['points'];
      if (nested != null) return _sourceLines(nested, fallback: fallback);
      final lines = <String>[];
      source.forEach((key, value) {
        if (const {
          'id',
          'business_id',
          'scope_id',
          'logo',
          'logo_url',
          'image',
          'image_url',
        }.contains(key.toString()))
          return;
        if (value == null || value is Map) return;
        if (value is List) {
          lines.addAll(_sourceLines(value, fallback: ''));
          return;
        }
        final text = value.toString().trim();
        if (text.isNotEmpty && text != 'null') {
          lines.add('${key.toString().replaceAll('_', ' ')}: $text');
        }
      });
      return lines.isEmpty ? <String>[fallback] : lines;
    }
    return <String>[source.toString()];
  }

  Widget _sourceCard({
    required String title,
    required IconData icon,
    required Color color,
    required dynamic source,
    required String fallback,
  }) {
    final lines = _sourceLines(source, fallback: fallback);
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: color.withOpacity(.07),
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: color.withOpacity(.25)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Icon(icon, color: color, size: 19),
              const SizedBox(width: 8),
              Text(
                title,
                style: TextStyle(
                  color: color,
                  fontWeight: FontWeight.w800,
                  fontSize: 13,
                ),
              ),
            ],
          ),
          const SizedBox(height: 8),
          for (final line in lines.take(5))
            Padding(
              padding: const EdgeInsets.only(bottom: 4),
              child: Text(
                '- $line',
                style: const TextStyle(
                  color: Color(0xFF334155),
                  fontSize: 12,
                  height: 1.25,
                ),
              ),
            ),
        ],
      ),
    );
  }

  Widget _reviewPostContent() {
    final preview = _contentPreview ?? _fallbackContentPreview();
    return ListView(
      key: const ValueKey('content-preview'),
      padding: const EdgeInsets.fromLTRB(20, 0, 20, 32),
      children: [
        const Text(
          'Your Post Preview',
          style: TextStyle(
            fontSize: 22,
            fontWeight: FontWeight.w900,
            color: Color(0xFF172033),
          ),
        ),
        const SizedBox(height: 6),
        const Text(
          'Review the content before your editable design is generated.',
          style: TextStyle(color: Color(0xFF64748B)),
        ),
        if (_previewNotice != null) ...[
          const SizedBox(height: 14),
          Container(
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(
              color: const Color(0xFFFFFBEB),
              borderRadius: BorderRadius.circular(12),
            ),
            child: Row(
              children: [
                const Icon(
                  Icons.info_outline,
                  color: Color(0xFFB45309),
                  size: 20,
                ),
                const SizedBox(width: 9),
                Expanded(
                  child: Text(
                    _previewNotice!,
                    style: const TextStyle(
                      color: Color(0xFF92400E),
                      fontSize: 12,
                    ),
                  ),
                ),
              ],
            ),
          ),
        ],
        const SizedBox(height: 18),
        _sourceCard(
          title: 'User Brief',
          icon: Icons.edit_note_outlined,
          color: const Color(0xFF2563EB),
          source: preview['user_brief'],
          fallback: 'No additional brief details were added.',
        ),
        const SizedBox(height: 12),
        _sourceCard(
          title: 'My Business Data',
          icon: Icons.business_outlined,
          color: const Color(0xFF16A34A),
          source: preview['my_business_data'] ?? preview['business_data'],
          fallback: 'Your saved business details will be used when available.',
        ),
        const SizedBox(height: 12),
        _sourceCard(
          title: 'General Data',
          icon: Icons.library_books_outlined,
          color: const Color(0xFF7C3AED),
          source: preview['general_data'],
          fallback:
              'No approved general data is available for this post type yet.',
        ),
        const SizedBox(height: 20),
        const Text(
          'Edit final post content',
          style: TextStyle(
            fontSize: 16,
            fontWeight: FontWeight.w900,
            color: Color(0xFF172033),
          ),
        ),
        const SizedBox(height: 12),
        _reviewTextField('Headline', _reviewHeadline, maxLines: 2),
        const SizedBox(height: 12),
        _reviewTextField('Content', _reviewContent, maxLines: 5),
        const SizedBox(height: 12),
        _reviewTextField('Call to action', _reviewCta, maxLines: 2),
        const SizedBox(height: 10),
        const Text(
          'Please review your clinic or business details before generating.',
          style: TextStyle(color: Color(0xFF64748B), fontSize: 11),
        ),
        const SizedBox(height: 24),
        OutlinedButton.icon(
          onPressed: () => setState(() => _step = 1),
          icon: const Icon(Icons.edit_outlined),
          label: const Text('Edit Brief'),
          style: OutlinedButton.styleFrom(
            minimumSize: const Size.fromHeight(50),
            shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(14),
            ),
          ),
        ),
        const SizedBox(height: 12),
        OutlinedButton.icon(
          onPressed: _previewLoading ? null : _requestContentPreview,
          icon: _previewLoading
              ? const SizedBox(
                  width: 18,
                  height: 18,
                  child: CircularProgressIndicator(strokeWidth: 2),
                )
              : const Icon(Icons.refresh_outlined),
          label: Text(
            _previewLoading
                ? 'Refreshing preview...'
                : 'Refresh Content Preview',
          ),
          style: OutlinedButton.styleFrom(
            minimumSize: const Size.fromHeight(50),
            shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(14),
            ),
          ),
        ),
        const SizedBox(height: 12),
        FilledButton.icon(
          onPressed: () => setState(() => _step = 3),
          style: _primaryButtonStyle,
          icon: const Icon(Icons.check_circle_outline),
          label: const Text('Approve and Choose Style'),
        ),
      ],
    );
  }

  Widget _reviewTextField(
    String label,
    TextEditingController controller, {
    required int maxLines,
  }) => Column(
    crossAxisAlignment: CrossAxisAlignment.start,
    children: [
      Text(
        label,
        style: const TextStyle(
          fontWeight: FontWeight.w700,
          fontSize: 13,
          color: Color(0xFF334155),
        ),
      ),
      const SizedBox(height: 6),
      TextField(
        controller: controller,
        maxLines: maxLines,
        decoration: _inputDecoration('Add $label'),
      ),
    ],
  );

  Widget _selectStyle() => ListView(
    key: const ValueKey('style'),
    padding: const EdgeInsets.fromLTRB(20, 0, 20, 32),
    children: [
      const Text(
        'Select Custom Post Style',
        style: TextStyle(
          fontSize: 20,
          fontWeight: FontWeight.w900,
          color: Color(0xFF172033),
        ),
      ),
      const SizedBox(height: 6),
      const Text(
        'Choose a visual look for your post',
        style: TextStyle(color: Color(0xFF64748B)),
      ),
      const SizedBox(height: 20),
      if (_availableStyles.isEmpty)
        Container(
          padding: const EdgeInsets.all(16),
          decoration: BoxDecoration(
            color: const Color(0xFFFFFBEB),
            borderRadius: BorderRadius.circular(14),
          ),
          child: const Text(
            'No active style is available for this Custom Post Type yet. Please ask your admin to add one.',
            style: TextStyle(color: Color(0xFF92400E)),
          ),
        ),
      if (_availableStyles.isNotEmpty)
        GridView.builder(
          shrinkWrap: true,
          physics: const NeverScrollableScrollPhysics(),
          itemCount: _availableStyles.length,
          gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
            crossAxisCount: 2,
            childAspectRatio: 0.85,
            crossAxisSpacing: 16,
            mainAxisSpacing: 16,
          ),
          itemBuilder: (_, index) {
            final style = _availableStyles[index];
            final selected = style['key'] == _style?['key'];
            final colors = List<dynamic>.from(style['colors'] ?? const []);
            final c1 = _hex('${colors.isNotEmpty ? colors.first : '#4338CA'}');
            final c2 = _hex('${colors.length > 1 ? colors[1] : '#0F172A'}');

            return GestureDetector(
              onTap: () => setState(() => _style = style),
              child: Container(
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(16),
                  border: Border.all(
                    color: selected
                        ? const Color(0xFF6434E8)
                        : Colors.transparent,
                    width: 2,
                  ),
                  boxShadow: [
                    BoxShadow(
                      color: Colors.black.withOpacity(0.04),
                      blurRadius: 10,
                      offset: const Offset(0, 4),
                    ),
                  ],
                ),
                child: Stack(
                  children: [
                    Column(
                      crossAxisAlignment: CrossAxisAlignment.stretch,
                      children: [
                        Expanded(
                          child: Container(
                            margin: const EdgeInsets.all(4),
                            decoration: BoxDecoration(
                              gradient: LinearGradient(
                                colors: [c1, c2],
                                begin: Alignment.topLeft,
                                end: Alignment.bottomRight,
                              ),
                              borderRadius: BorderRadius.circular(12),
                            ),
                            alignment: Alignment.center,
                            child: const Text(
                              'STYLE\nPREVIEW',
                              textAlign: TextAlign.center,
                              style: TextStyle(
                                color: Colors.white54,
                                fontWeight: FontWeight.w900,
                                fontSize: 16,
                              ),
                            ),
                          ),
                        ),
                        Padding(
                          padding: const EdgeInsets.symmetric(vertical: 12),
                          child: Text(
                            '${style['name']}',
                            style: const TextStyle(
                              fontSize: 13,
                              fontWeight: FontWeight.w700,
                              color: Color(0xFF172033),
                            ),
                            textAlign: TextAlign.center,
                          ),
                        ),
                      ],
                    ),
                    if (selected)
                      Positioned(
                        top: 10,
                        right: 10,
                        child: Container(
                          decoration: const BoxDecoration(
                            color: Color(0xFF6434E8),
                            shape: BoxShape.circle,
                          ),
                          padding: const EdgeInsets.all(4),
                          child: const Icon(
                            Icons.check,
                            color: Colors.white,
                            size: 14,
                          ),
                        ),
                      ),
                  ],
                ),
              ),
            );
          },
        ),
      if (_availableStyles.isNotEmpty) ...[
        const SizedBox(height: 20),
        const Text(
          'Colour theme',
          style: TextStyle(
            fontWeight: FontWeight.w800,
            color: Color(0xFF172033),
          ),
        ),
        const SizedBox(height: 8),
        RadioListTile<String>(
          value: 'style_colors',
          groupValue: _paletteMode,
          onChanged: (value) => setState(() => _paletteMode = value!),
          title: const Text('Use selected style colours'),
          subtitle: const Text(
            'Use this Custom Post Style’s primary and secondary colours.',
          ),
          contentPadding: EdgeInsets.zero,
        ),
        if (_canUseBusinessTheme)
          RadioListTile<String>(
            value: 'business_theme',
            groupValue: _paletteMode,
            onChanged: (value) => setState(() => _paletteMode = value!),
            title: const Text('Use my business theme'),
            subtitle: const Text(
              'Use the primary and secondary colours saved in My Business.',
            ),
            contentPadding: EdgeInsets.zero,
          )
        else
          const Text(
            'Save both business theme colours in My Business to use them here.',
            style: TextStyle(color: Color(0xFF64748B), fontSize: 12),
          ),
      ],
      const SizedBox(height: 24),
      Container(
        padding: const EdgeInsets.all(14),
        decoration: BoxDecoration(
          color: const Color(0xFFF8FAFC),
          borderRadius: BorderRadius.circular(12),
          border: Border.all(color: const Color(0xFFE2E8F0)),
        ),
        child: Row(
          children: const [
            Icon(Icons.info_outline, color: Color(0xFF6434E8), size: 20),
            SizedBox(width: 10),
            Expanded(
              child: Text(
                'Only text stays editable after generation',
                style: TextStyle(
                  color: Color(0xFF334155),
                  fontWeight: FontWeight.w600,
                  fontSize: 12,
                ),
              ),
            ),
          ],
        ),
      ),
      const SizedBox(height: 24),
      FilledButton.icon(
        onPressed: _submitting ? null : _generate,
        style: _primaryButtonStyle,
        icon: _submitting
            ? const SizedBox(
                width: 18,
                height: 18,
                child: CircularProgressIndicator(
                  strokeWidth: 2,
                  color: Colors.white,
                ),
              )
            : const Icon(Icons.auto_awesome),
        label: Text(
          _submitting
              ? 'Starting...'
              : 'Generate with AI  •  $_generationCost credit',
        ),
      ),
      const SizedBox(height: 20),
      const Divider(),
      const SizedBox(height: 10),
      _generationSettings(),
    ],
  );

  Widget _generationSettings() => Column(
    crossAxisAlignment: CrossAxisAlignment.start,
    children: [
      const Text(
        'Advanced settings',
        style: TextStyle(fontWeight: FontWeight.w800, color: Color(0xFF64748B)),
      ),
      const SizedBox(height: 12),
      DropdownButtonFormField<String>(
        initialValue: _model?['id']?.toString(),
        decoration: _inputDecoration('AI model'),
        items: _models.map((raw) {
          final model = Map<String, dynamic>.from(raw as Map);
          return DropdownMenuItem(
            value: model['id']?.toString(),
            child: Text('${model['display_name']}'),
          );
        }).toList(),
        onChanged: (id) {
          if (id == null) return;
          final raw = _models.cast<Map?>().firstWhere(
            (item) => item?['id']?.toString() == id,
            orElse: () => null,
          );
          if (raw == null) return;
          final model = Map<String, dynamic>.from(raw);
          final available = List<dynamic>.from(model['quality_variants'] ?? [])
              .cast<Map?>()
              .firstWhere(
                (item) => item?['is_available'] == true,
                orElse: () => null,
              );
          setState(() {
            _model = model;
            _quality = available?['key']?.toString();
            _sizeKey = _availableSizes.isEmpty
                ? null
                : _availableSizes.first['key']?.toString();
          });
        },
      ),
      const SizedBox(height: 10),
      Row(
        children: [
          Expanded(
            child: DropdownButtonFormField<String>(
              initialValue: _quality,
              decoration: _inputDecoration('Quality'),
              items: List<dynamic>.from(_model?['quality_variants'] ?? [])
                  .where((item) => item is Map && item['is_available'] == true)
                  .map((raw) {
                    final item = Map<String, dynamic>.from(raw as Map);
                    return DropdownMenuItem(
                      value: item['key']?.toString(),
                      child: Text('${item['display_name']}'),
                    );
                  })
                  .toList(),
              onChanged: (value) => setState(() => _quality = value),
            ),
          ),
          const SizedBox(width: 10),
          Expanded(
            child: DropdownButtonFormField<String>(
              initialValue: _sizeKey,
              decoration: _inputDecoration('Size'),
              items: _availableSizes
                  .map(
                    (item) => DropdownMenuItem(
                      value: item['key']?.toString(),
                      child: Text(
                        '${item['label'] ?? item['size'] ?? item['key']}',
                      ),
                    ),
                  )
                  .toList(),
              onChanged: (value) => setState(() => _sizeKey = value),
            ),
          ),
        ],
      ),
    ],
  );

  Widget _generatedArtworkPreview() {
    final status = _job?['status']?.toString() ?? 'queued';
    final imageUrl = _job?['image_url']?.toString() ?? '';
    final editable = _job?['editable_document'];
    final docId = editable is Map
        ? editable['document_id']?.toString() ?? ''
        : '';
    final ready = status == 'completed' && docId.isNotEmpty;

    return ListView(
      key: const ValueKey('preview'),
      padding: const EdgeInsets.fromLTRB(20, 0, 20, 32),
      children: [
        if (!ready) ...[
          const SizedBox(height: 40),
          const Center(
            child: CircularProgressIndicator(color: Color(0xFF6434E8)),
          ),
          const SizedBox(height: 20),
          Text(
            status == 'failed'
                ? '${_job?['error_message'] ?? 'Generation failed.'}'
                : 'Creating one artwork with editable layers...',
            textAlign: TextAlign.center,
            style: const TextStyle(color: Color(0xFF64748B)),
          ),
        ],
        if (ready) ...[
          Center(
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
              decoration: BoxDecoration(
                color: const Color(0xFFECFDF5),
                borderRadius: BorderRadius.circular(99),
                border: Border.all(color: const Color(0xFFA7F3D0)),
              ),
              child: const Text(
                'Editable text only',
                style: TextStyle(
                  color: Color(0xFF047857),
                  fontWeight: FontWeight.w700,
                  fontSize: 11,
                ),
              ),
            ),
          ),
          const SizedBox(height: 16),
          Container(
            decoration: BoxDecoration(
              borderRadius: BorderRadius.circular(20),
              boxShadow: [
                BoxShadow(
                  color: Colors.black.withOpacity(0.08),
                  blurRadius: 16,
                  offset: const Offset(0, 8),
                ),
              ],
            ),
            clipBehavior: Clip.antiAlias,
            child: AspectRatio(
              aspectRatio: 1,
              child: imageUrl.isNotEmpty
                  ? Image.network(
                      imageUrl,
                      fit: BoxFit.cover,
                      errorBuilder: (_, _, _) => const Center(
                        child: Icon(Icons.broken_image_outlined),
                      ),
                    )
                  : const Center(child: CircularProgressIndicator()),
            ),
          ),
          const SizedBox(height: 24),
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Expanded(
                child: _actionColumn(
                  Icons.edit_outlined,
                  'Edit Design',
                  () => Get.to(() => AiEditableEditorScreen(documentId: docId)),
                ),
              ),
              const SizedBox(width: 8),
              Expanded(
                child: _actionColumn(
                  Icons.auto_awesome,
                  'Another\nVersion',
                  _openNewVersion,
                ),
              ),
              const SizedBox(width: 8),
              Expanded(
                child: _actionColumn(
                  Icons.style_outlined,
                  'Change\nStyle',
                  () => setState(() {
                    _generationKind = 'style_change';
                    _step = 3;
                  }),
                ),
              ),
              const SizedBox(width: 8),
              Expanded(
                child: _actionColumn(
                  Icons.description_outlined,
                  'Change\nBrief',
                  () => setState(() {
                    _generationKind = 'brief_change';
                    _step = 1;
                  }),
                ),
              ),
            ],
          ),
          const SizedBox(height: 16),
          OutlinedButton.icon(
            onPressed: _savingDraft ? null : _saveDraft,
            icon: _savingDraft
                ? const SizedBox(
                    width: 18,
                    height: 18,
                    child: CircularProgressIndicator(strokeWidth: 2),
                  )
                : const Icon(Icons.bookmark_add_outlined),
            label: Text(
              _job?['is_saved_draft'] == true
                  ? 'Saved in My Designs'
                  : 'Save Draft',
            ),
            style: OutlinedButton.styleFrom(
              minimumSize: const Size.fromHeight(50),
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(14),
              ),
            ),
          ),
        ],
      ],
    );
  }

  Widget _actionColumn(IconData icon, String label, VoidCallback onTap) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(12),
      child: Column(
        children: [
          Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              border: Border.all(color: const Color(0xFFE2E8F0)),
              borderRadius: BorderRadius.circular(16),
            ),
            child: Icon(icon, color: const Color(0xFF172033), size: 28),
          ),
          const SizedBox(height: 8),
          Text(
            label,
            textAlign: TextAlign.center,
            style: const TextStyle(
              fontSize: 11,
              fontWeight: FontWeight.w600,
              color: Color(0xFF334155),
            ),
          ),
        ],
      ),
    );
  }

  Future<void> _openNewVersion() async {
    final instruction = TextEditingController(text: _visualInstruction.text);
    await showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (sheetContext) => Container(
        decoration: const BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
        ),
        padding: EdgeInsets.fromLTRB(
          20,
          12,
          20,
          MediaQuery.of(context).viewInsets.bottom + 20,
        ),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Center(
              child: Container(
                width: 40,
                height: 4,
                decoration: BoxDecoration(
                  color: const Color(0xFFE2E8F0),
                  borderRadius: BorderRadius.circular(2),
                ),
              ),
            ),
            const SizedBox(height: 16),
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                const Text(
                  'Create another version',
                  style: TextStyle(fontWeight: FontWeight.w900, fontSize: 20),
                ),
                IconButton(
                  icon: const Icon(Icons.close),
                  onPressed: () => Navigator.pop(sheetContext),
                ),
              ],
            ),
            const SizedBox(height: 16),
            const Text(
              'Describe what you want',
              style: TextStyle(
                fontWeight: FontWeight.w700,
                fontSize: 13,
                color: Color(0xFF334155),
              ),
            ),
            const SizedBox(height: 8),
            TextField(
              controller: instruction,
              maxLines: 4,
              decoration: InputDecoration(
                hintText: 'Make the design more premium...',
                hintStyle: const TextStyle(color: Color(0xFF94A3B8)),
                filled: true,
                fillColor: const Color(0xFFF8FAFC),
                border: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(12),
                  borderSide: BorderSide.none,
                ),
              ),
            ),
            const SizedBox(height: 16),
            const Text(
              'Add product photos (Optional)',
              style: TextStyle(
                fontWeight: FontWeight.w700,
                fontSize: 13,
                color: Color(0xFF334155),
              ),
            ),
            const SizedBox(height: 8),
            OutlinedButton.icon(
              onPressed: () {
                Navigator.pop(sheetContext);
                _chooseProducts();
              },
              icon: const Icon(
                Icons.photo_library_outlined,
                color: Color(0xFF172033),
              ),
              label: Text(
                '${_productIds.length} photos selected. Tap to change.',
                style: const TextStyle(color: Color(0xFF172033)),
              ),
              style: OutlinedButton.styleFrom(
                minimumSize: const Size.fromHeight(50),
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(12),
                ),
              ),
            ),
            const SizedBox(height: 10),
            OutlinedButton.icon(
              onPressed: () {
                Navigator.pop(sheetContext);
                _uploadDeviceReferences();
              },
              icon: const Icon(
                Icons.upload_file_outlined,
                color: Color(0xFF172033),
              ),
              label: Text(
                '${_directReferenceUploads.length} device photo${_directReferenceUploads.length == 1 ? '' : 's'} selected. Upload or change.',
                style: const TextStyle(color: Color(0xFF172033)),
              ),
              style: OutlinedButton.styleFrom(
                minimumSize: const Size.fromHeight(50),
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(12),
                ),
              ),
            ),
            const SizedBox(height: 20),
            Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: const Color(0xFFF0EDFF),
                borderRadius: BorderRadius.circular(12),
              ),
              child: Row(
                children: const [
                  Icon(Icons.auto_awesome, color: Color(0xFF6434E8), size: 20),
                  SizedBox(width: 10),
                  Expanded(
                    child: Text(
                      'A new design will get fresh editable text layers only.',
                      style: TextStyle(
                        color: Color(0xFF4C1D95),
                        fontSize: 12,
                        fontWeight: FontWeight.w500,
                      ),
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 20),
            FilledButton.icon(
              onPressed: () {
                Navigator.pop(sheetContext);
                _generate(
                  versionInstruction: instruction.text.trim(),
                  generationKind: 'another_version',
                );
              },
              style: _primaryButtonStyle,
              icon: const Icon(Icons.auto_awesome),
              label: Text('Generate New Version  •  $_generationCost credit'),
            ),
          ],
        ),
      ),
    );
    instruction.dispose();
  }

  Widget _errorState() => Center(
    child: Padding(
      padding: const EdgeInsets.all(28),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          const Icon(
            Icons.cloud_off_rounded,
            size: 50,
            color: Color(0xFF94A3B8),
          ),
          const SizedBox(height: 12),
          Text(_error!, textAlign: TextAlign.center),
          const SizedBox(height: 14),
          FilledButton(onPressed: _loadOptions, child: const Text('Try again')),
        ],
      ),
    ),
  );

  static ButtonStyle get _primaryButtonStyle => FilledButton.styleFrom(
    backgroundColor: const Color(0xFF6434E8),
    foregroundColor: Colors.white,
    minimumSize: const Size.fromHeight(52),
    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
  );
  static Color _hex(String value) {
    final clean = value.replaceFirst('#', '');
    return Color(int.parse('FF$clean', radix: 16));
  }

  static IconData _purposeIcon(String value) => switch (value) {
    'work' => Icons.work_outline_rounded,
    'local_offer' => Icons.local_offer_outlined,
    'inventory' => Icons.inventory_2_outlined,
    'campaign' => Icons.campaign_outlined,
    'volunteer_activism' => Icons.volunteer_activism_outlined,
    _ => Icons.auto_awesome_rounded,
  };
  static Color _purposeAccent(String value) => switch (value) {
    'work' => const Color(0xFF6D3DF0),
    'local_offer' => const Color(0xFFF97316),
    'inventory' => const Color(0xFF2563EB),
    'campaign' => const Color(0xFFEF4444),
    'volunteer_activism' => const Color(0xFF16A34A),
    _ => const Color(0xFF7C3AED),
  };
  static String _purposeEmoji(String value) => switch (value) {
    'work' => '💼',
    'local_offer' => '🏷️',
    'inventory' => '📦',
    'campaign' => '📢',
    'volunteer_activism' => '🌱',
    _ => '✨',
  };
  static String _purposeEmojiUrl(String value) => switch (value) {
    'work' =>
      'https://raw.githubusercontent.com/microsoft/fluentui-emoji/main/assets/Briefcase/3D/briefcase_3d.png',
    'local_offer' =>
      'https://raw.githubusercontent.com/microsoft/fluentui-emoji/main/assets/Label/3D/label_3d.png',
    'inventory' =>
      'https://raw.githubusercontent.com/microsoft/fluentui-emoji/main/assets/Package/3D/package_3d.png',
    'campaign' =>
      'https://raw.githubusercontent.com/microsoft/fluentui-emoji/main/assets/Megaphone/3D/megaphone_3d.png',
    'volunteer_activism' =>
      'https://raw.githubusercontent.com/microsoft/fluentui-emoji/main/assets/Seedling/3D/seedling_3d.png',
    _ =>
      'https://raw.githubusercontent.com/microsoft/fluentui-emoji/main/assets/Sparkles/3D/sparkles_3d.png',
  };
}

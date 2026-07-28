import 'dart:convert';

import 'package:flutter/foundation.dart' show kIsWeb;
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:image_picker/image_picker.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../controllers/festival_ai_job_controller.dart';
import '../services/api_service.dart';
import '../widgets/shared_header.dart';
import '../widgets/festival_ai_creations_sheet.dart';
import 'subscription_plans_screen.dart';

class AiTrendsScreen extends StatefulWidget {
  const AiTrendsScreen({super.key});

  @override
  State<AiTrendsScreen> createState() => _AiTrendsScreenState();
}

class _AiTrendsScreenState extends State<AiTrendsScreen> {
  final TextEditingController _instructionController = TextEditingController();
  final TextEditingController _uploadedProductNameController =
      TextEditingController();
  final Set<int> _selectedProductIds = <int>{};
  XFile? _uploadedProductImage;
  String? _productMode;
  bool _modelDropdownOpen = false;
  bool _aiEditableV1Available = false;
  bool _createEditableV1 = false;

  bool _loading = true;
  bool _submitting = false;
  String? _error;
  Map<String, dynamic>? _quota;
  List<dynamic> _festivals = [];
  List<dynamic> _models = [];
  List<dynamic> _languages = [];
  List<dynamic> _products = [];
  Map<String, dynamic>? _festival;
  Map<String, dynamic>? _style;
  Map<String, dynamic>? _model;
  Map<String, dynamic>? _language;
  String? _quality;
  String? _sizeKey;
  late final FestivalAiJobController _jobs;

  @override
  void initState() {
    super.initState();
    _jobs = Get.isRegistered<FestivalAiJobController>()
        ? Get.find<FestivalAiJobController>()
        : Get.put(FestivalAiJobController(), permanent: true);
    _loadOptions();
    _jobs.refreshHistory();
  }

  @override
  void dispose() {
    _instructionController.dispose();
    _uploadedProductNameController.dispose();
    super.dispose();
  }

  Future<void> _loadOptions() async {
    setState(() {
      _loading = true;
      _error = null;
    });

    try {
      final response = await ApiService.get('/festival-ai/options');
      final data = jsonDecode(response.body) as Map<String, dynamic>;
      if (response.statusCode != 200 || data['success'] != true) {
        throw Exception(
          data['message'] ??
              'Festival AI could not be loaded (server status ${response.statusCode}).',
        );
      }

      final festivals = List<dynamic>.from(data['festivals'] ?? []);
      final models = List<dynamic>.from(data['models'] ?? []);
      final languages = List<dynamic>.from(data['languages'] ?? []);
      final editableConfig = data['ai_editable_v1'];
      final editableAvailable =
          editableConfig is Map && editableConfig['enabled'] == true;
      Map<String, dynamic>? initialModel;
      String? initialQuality;
      for (final item in models) {
        final model = Map<String, dynamic>.from(item as Map);
        Map<String, dynamic>? firstAvailable;
        Map<String, dynamic>? defaultAvailable;
        final defaultQuality = model['default_quality']?.toString();
        for (final item in List<dynamic>.from(
          model['quality_variants'] ?? [],
        )) {
          final variant = Map<String, dynamic>.from(item as Map);
          if (variant['is_available'] == true) {
            firstAvailable ??= variant;
            if (variant['key']?.toString() == defaultQuality) {
              defaultAvailable = variant;
            }
          }
        }
        final preferredVariant = defaultAvailable ?? firstAvailable;
        if (preferredVariant != null) {
          initialModel = model;
          initialQuality = preferredVariant['key']?.toString();
          break;
        }
      }
      setState(() {
        _quota = Map<String, dynamic>.from(data['quota'] ?? {});
        _festivals = festivals;
        _models = models;
        _languages = languages;
        _festival = festivals.isNotEmpty
            ? Map<String, dynamic>.from(festivals.first)
            : null;
        _style = _styles.isNotEmpty
            ? Map<String, dynamic>.from(_styles.first)
            : null;
        _model = initialModel;
        _language = languages.isNotEmpty
            ? Map<String, dynamic>.from(languages.first)
            : null;
        _quality = initialQuality;
        _sizeKey = _availableSizes.isNotEmpty
            ? _availableSizes.first['key']?.toString()
            : null;
        _aiEditableV1Available = editableAvailable;
        if (!editableAvailable) _createEditableV1 = false;
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

  List<dynamic> get _styles => List<dynamic>.from(_festival?['styles'] ?? []);

  List<Map<String, dynamic>> get _modelVariants => _models.expand((item) {
    final model = Map<String, dynamic>.from(item as Map);
    final variants = List<dynamic>.from(model['quality_variants'] ?? []);
    final qualities = variants.isEmpty
        ? _qualitiesForModel(
            model,
          ).map((quality) => {'key': quality, 'display_name': quality}).toList()
        : variants;

    return qualities.map((item) {
      final variant = Map<String, dynamic>.from(item as Map);
      return {
        ...model,
        'quality_key': variant['key'],
        'variant_name': variant['display_name'],
        'is_available': variant['is_available'] ?? false,
      };
    });
  }).toList();

  List<String> _qualitiesForModel(Map<String, dynamic> model) =>
      List<String>.from(
        model['qualities'] ?? [],
      ).map((value) => value.toString()).toList();

  List<Map<String, dynamic>> get _availableSizes {
    final styleSizes = List<String>.from(
      _style?['allowed_size_keys'] ?? [],
    ).map((value) => value.toString()).toSet();
    return List<dynamic>.from(_model?['sizes'] ?? [])
        .map((item) => Map<String, dynamic>.from(item as Map))
        .where((size) => styleSizes.contains(size['key']?.toString()))
        .toList();
  }

  int get _maxProducts => _model?['max_product_images'] is int
      ? _model!['max_product_images'] as int
      : int.tryParse(_model?['max_product_images']?.toString() ?? '0') ?? 0;

  bool get _styleNeedsProduct => _style?['product_required'] == true;
  bool get _allowProductUpload => _festival?['allow_product_upload'] == true;
  bool get _requiresUploadedProductName =>
      _festival?['require_product_name_for_upload'] == true;

  void _selectFestival(Map<String, dynamic>? festival) {
    if (festival == null) return;
    setState(() {
      _festival = festival;
      _style = _styles.isNotEmpty
          ? Map<String, dynamic>.from(_styles.first)
          : null;
      _selectedProductIds.clear();
      _uploadedProductImage = null;
      _productMode = null;
      _instructionController.clear();
      _uploadedProductNameController.clear();
      _ensureSelections();
    });
  }

  void _selectStyle(Map<String, dynamic> style) {
    setState(() {
      _style = style;
      _ensureSelections();
    });
  }

  void _selectModel(Map<String, dynamic> model) {
    setState(() {
      _model = model;
      _selectedProductIds.clear();
      _uploadedProductImage = null;
      _productMode = null;
      _instructionController.clear();
      _uploadedProductNameController.clear();
      _ensureSelections();
    });
  }

  void _selectModelVariant(Map<String, dynamic> variant) {
    if (variant['is_available'] != true) {
      _showUpgradePrompt(variant);
      return;
    }
    final quality = variant['quality_key']?.toString();
    if (quality == null || quality.isEmpty) return;
    _selectModel(variant);
    setState(() => _quality = quality);
  }

  void _showUpgradePrompt(Map<String, dynamic> variant) {
    final name = variant['variant_name']?.toString() ?? 'This AI model';
    showDialog<void>(
      context: context,
      builder: (context) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(18)),
        title: const Row(
          children: [
            Icon(Icons.workspace_premium_rounded, color: Color(0xFFF59E0B)),
            SizedBox(width: 8),
            Text('Premium model'),
          ],
        ),
        content: Text(
          '$name is not included in your current plan. Upgrade to premium to use it.',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('Not now'),
          ),
          ElevatedButton(
            style: ElevatedButton.styleFrom(
              backgroundColor: const Color(0xFF4F46E5),
              foregroundColor: Colors.white,
            ),
            onPressed: () {
              Navigator.pop(context);
              Navigator.push(
                context,
                MaterialPageRoute(
                  builder: (context) => const SubscriptionPlansScreen(),
                ),
              );
            },
            child: const Text('Upgrade to premium'),
          ),
        ],
      ),
    );
  }

  void _selectLanguage(Map<String, dynamic> language) {
    setState(() => _language = language);
  }

  void _ensureSelections() {
    final availableQualities =
        List<dynamic>.from(_model?['quality_variants'] ?? [])
            .map((item) => Map<String, dynamic>.from(item as Map))
            .where((variant) => variant['is_available'] == true)
            .map((variant) => variant['key']?.toString())
            .whereType<String>()
            .toList();
    final preferredQuality = _model?['default_quality']?.toString();
    if (!availableQualities.contains(_quality)) {
      _quality =
          preferredQuality != null &&
              availableQualities.contains(preferredQuality)
          ? preferredQuality
          : (availableQualities.isNotEmpty ? availableQualities.first : null);
    }
    final validKeys = _availableSizes
        .map((size) => size['key']?.toString())
        .toSet();
    if (!validKeys.contains(_sizeKey)) {
      _sizeKey = validKeys.isNotEmpty ? validKeys.first : null;
    }
  }

  Future<void> _loadProducts() async {
    if (_products.isNotEmpty) return;
    final prefs = await SharedPreferences.getInstance();
    final userId = prefs.getString('userId') ?? '';
    if (userId.isEmpty) return;

    final response = await ApiService.post('/products/list', {
      'userId': userId,
    });
    final data = jsonDecode(response.body) as Map<String, dynamic>;
    if (response.statusCode == 200 && data['success'] == true) {
      _products = List<dynamic>.from(data['products']?['data'] ?? []);
    } else {
      throw Exception(data['message'] ?? 'Products could not be loaded.');
    }
  }

  Future<void> _pickProducts() async {
    if (_maxProducts == 0) {
      _showMessage(
        'This model does not support product reference images.',
        isError: true,
      );
      return;
    }

    try {
      setState(() {
        _productMode = 'choose';
        _uploadedProductImage = null;
        _instructionController.clear();
        _uploadedProductNameController.clear();
      });
      await _loadProducts();
      if (!mounted) return;
      await showModalBottomSheet<void>(
        context: context,
        isScrollControlled: true,
        backgroundColor: Colors.transparent,
        builder: (context) {
          return StatefulBuilder(
            builder: (context, setSheetState) {
              return Container(
                height: MediaQuery.of(context).size.height * .72,
                decoration: const BoxDecoration(
                  color: Color(0xFFF8FAFC),
                  borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
                ),
                child: Column(
                  children: [
                    Container(
                      margin: const EdgeInsets.only(top: 12),
                      width: 40,
                      height: 4,
                      decoration: BoxDecoration(
                        color: const Color(0xFFCBD5E1),
                        borderRadius: BorderRadius.circular(4),
                      ),
                    ),
                    Padding(
                      padding: const EdgeInsets.fromLTRB(20, 16, 12, 10),
                      child: Row(
                        children: [
                          const Icon(
                            Icons.inventory_2_outlined,
                            color: Color(0xFF6366F1),
                          ),
                          const SizedBox(width: 10),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                const Text(
                                  'Choose products',
                                  style: TextStyle(
                                    fontSize: 18,
                                    fontWeight: FontWeight.w800,
                                  ),
                                ),
                                Text(
                                  'Up to $_maxProducts product image${_maxProducts == 1 ? '' : 's'} can guide this visual.',
                                  style: const TextStyle(
                                    fontSize: 12,
                                    color: Color(0xFF64748B),
                                  ),
                                ),
                              ],
                            ),
                          ),
                          TextButton(
                            onPressed: () => Navigator.pop(context),
                            child: const Text('Done'),
                          ),
                        ],
                      ),
                    ),
                    Expanded(
                      child: _products.isEmpty
                          ? const Center(
                              child: Text(
                                'Add products in My Business to use them here.',
                              ),
                            )
                          : ListView.builder(
                              padding: const EdgeInsets.fromLTRB(16, 4, 16, 24),
                              itemCount: _products.length,
                              itemBuilder: (context, index) {
                                final product = Map<String, dynamic>.from(
                                  _products[index] as Map,
                                );
                                final id = int.tryParse(
                                  product['id']?.toString() ?? '',
                                );
                                final selected =
                                    id != null &&
                                    _selectedProductIds.contains(id);
                                final imageUrl =
                                    product['image_url']?.toString() ?? '';
                                return Card(
                                  elevation: 0,
                                  color: Colors.white,
                                  margin: const EdgeInsets.only(bottom: 8),
                                  shape: RoundedRectangleBorder(
                                    borderRadius: BorderRadius.circular(16),
                                    side: BorderSide(
                                      color: selected
                                          ? const Color(0xFF6366F1)
                                          : const Color(0xFFE2E8F0),
                                    ),
                                  ),
                                  child: CheckboxListTile(
                                    value: selected,
                                    activeColor: const Color(0xFF6366F1),
                                    secondary: ClipRRect(
                                      borderRadius: BorderRadius.circular(10),
                                      child: SizedBox(
                                        height: 44,
                                        width: 44,
                                        child: imageUrl.isEmpty
                                            ? const ColoredBox(
                                                color: Color(0xFFF1F5F9),
                                                child: Icon(
                                                  Icons.inventory_2_outlined,
                                                  color: Color(0xFF94A3B8),
                                                ),
                                              )
                                            : Image.network(
                                                imageUrl,
                                                fit: BoxFit.cover,
                                                errorBuilder: (_, __, ___) =>
                                                    const ColoredBox(
                                                      color: Color(0xFFF1F5F9),
                                                      child: Icon(
                                                        Icons
                                                            .inventory_2_outlined,
                                                        color: Color(
                                                          0xFF94A3B8,
                                                        ),
                                                      ),
                                                    ),
                                              ),
                                      ),
                                    ),
                                    title: Text(
                                      product['display_name']?.toString() ??
                                          product['title']?.toString() ??
                                          'Product',
                                      maxLines: 1,
                                      overflow: TextOverflow.ellipsis,
                                      style: const TextStyle(
                                        fontWeight: FontWeight.w700,
                                      ),
                                    ),
                                    subtitle: Text(
                                      product['category_name']?.toString() ??
                                          '',
                                      maxLines: 1,
                                      overflow: TextOverflow.ellipsis,
                                    ),
                                    onChanged: id == null
                                        ? null
                                        : (checked) {
                                            setSheetState(() {
                                              if (checked == true) {
                                                if (_selectedProductIds.length <
                                                    _maxProducts) {
                                                  _selectedProductIds.add(id);
                                                }
                                              } else {
                                                _selectedProductIds.remove(id);
                                              }
                                            });
                                            setState(() {});
                                          },
                                  ),
                                );
                              },
                            ),
                    ),
                  ],
                ),
              );
            },
          );
        },
      );
    } catch (error) {
      _showMessage(
        error.toString().replaceFirst('Exception: ', ''),
        isError: true,
      );
    }
  }

  Future<void> _pickUploadedProduct() async {
    if (_maxProducts == 0) {
      _showMessage(
        'This model does not support product reference images.',
        isError: true,
      );
      return;
    }
    if (!_allowProductUpload) {
      _showMessage(
        'Product photo upload is not enabled for this festival.',
        isError: true,
      );
      return;
    }

    final image = await ImagePicker().pickImage(
      source: ImageSource.gallery,
      imageQuality: 88,
    );
    if (image == null || !mounted) return;

    setState(() {
      _productMode = 'upload';
      _uploadedProductImage = image;
      _selectedProductIds.clear();
      _uploadedProductNameController.clear();
    });
  }

  void _changeProductSource() {
    setState(() {
      _productMode = null;
      _uploadedProductImage = null;
      _selectedProductIds.clear();
      _instructionController.clear();
      _uploadedProductNameController.clear();
    });
  }

  Future<void> _generate() async {
    if (_festival == null ||
        _style == null ||
        _model == null ||
        _language == null ||
        _quality == null ||
        _sizeKey == null) {
      _showMessage(
        'Choose a festival, style, model, quality, size, and text language first.',
        isError: true,
      );
      return;
    }
    final hasChosenProduct = _selectedProductIds.isNotEmpty;
    final hasUploadedProduct = _uploadedProductImage != null;
    if (_styleNeedsProduct && !hasChosenProduct && !hasUploadedProduct) {
      _showMessage('This style needs at least one product.', isError: true);
      return;
    }
    if (_productMode == 'choose' && !hasChosenProduct) {
      _showMessage(
        'Choose at least one product before generating.',
        isError: true,
      );
      return;
    }
    if (_productMode == 'upload' && !hasUploadedProduct) {
      _showMessage('Upload a product photo before generating.', isError: true);
      return;
    }
    if (_productMode == 'upload' &&
        _requiresUploadedProductName &&
        _uploadedProductNameController.text.trim().isEmpty) {
      _showMessage('Enter the uploaded product name first.', isError: true);
      return;
    }

    setState(() {
      _submitting = true;
    });
    try {
      final prefs = await SharedPreferences.getInstance();
      final body = {
        'userId': prefs.getString('userId') ?? '',
        'festival_id': _festival!['id'],
        'style_id': _style!['id'],
        'model_id': _model!['id'],
        'quality': _quality,
        'size_key': _sizeKey,
        'language_id': _language!['id'],
        'user_instruction': _instructionController.text.trim(),
        'product_ids': _selectedProductIds.toList(),
        'product_mode': _productMode ?? 'none',
        'uploaded_product_name': _uploadedProductNameController.text.trim(),
        'output_mode': _createEditableV1 ? 'editable_v1' : 'flat',
      };
      final response = _productMode == 'upload'
          ? await _createGenerationWithUpload(body)
          : await ApiService.post('/festival-ai/generations', body);
      Map<String, dynamic> data;
      try {
        data = jsonDecode(response.body) as Map<String, dynamic>;
      } catch (_) {
        throw Exception(
          'Festival AI request failed (HTTP ${response.statusCode}). Please try again. If it continues, contact support.',
        );
      }
      if (response.statusCode != 202 || data['success'] != true) {
        throw Exception(
          data['message'] ??
              data['error'] ??
              'Festival AI request failed (HTTP ${response.statusCode}).',
        );
      }

      setState(() {
        _submitting = false;
        _quota = {
          ...?_quota,
          'remaining':
              ((int.tryParse(_quota?['remaining']?.toString() ?? '0') ?? 0) - 1)
                  .clamp(0, 999999),
        };
      });
      await _jobs.track(Map<String, dynamic>.from(data['job'] as Map));
    } catch (error) {
      if (mounted) {
        setState(() {
          _submitting = false;
        });
        _showMessage(
          error.toString().replaceFirst('Exception: ', ''),
          isError: true,
        );
      }
    }
  }

  Future<dynamic> _createGenerationWithUpload(Map<String, dynamic> body) async {
    final image = _uploadedProductImage;
    if (image == null) throw Exception('Upload a product photo first.');

    final bytes = await image.readAsBytes();
    final fields = Map<String, String>.fromEntries(
      body.entries
          .where((entry) => entry.key != 'product_ids')
          .map((entry) => MapEntry(entry.key, entry.value.toString())),
    );
    return ApiService.multipartPost(
      '/festival-ai/generations',
      fields,
      fileKey: 'uploaded_product_image',
      filePath: kIsWeb ? null : image.path,
      fileBytes: kIsWeb ? bytes : null,
      fileName: image.name,
    );
  }

  void _showMessage(String message, {required bool isError}) {
    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(message),
        backgroundColor: isError
            ? const Color(0xFFDC2626)
            : const Color(0xFF059669),
        behavior: SnackBarBehavior.floating,
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return SafeArea(
      child: Scaffold(
        backgroundColor: const Color(0xFFF8FAFC),
        body: Column(
          children: [
            const SharedHeader(),
            Expanded(child: _buildBody()),
          ],
        ),
      ),
    );
  }

  Widget _buildBody() {
    if (_loading) {
      return const Center(
        child: CircularProgressIndicator(color: Color(0xFF6366F1)),
      );
    }
    if (_error != null) {
      return Center(
        child: Padding(
          padding: const EdgeInsets.all(28),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              const Icon(
                Icons.auto_awesome_outlined,
                size: 52,
                color: Color(0xFF94A3B8),
              ),
              const SizedBox(height: 14),
              Text(
                _error!,
                textAlign: TextAlign.center,
                style: const TextStyle(color: Color(0xFF475569)),
              ),
              const SizedBox(height: 16),
              ElevatedButton.icon(
                onPressed: _loadOptions,
                icon: const Icon(Icons.refresh),
                label: const Text('Try again'),
              ),
            ],
          ),
        ),
      );
    }
    if (_festivals.isEmpty || _models.isEmpty) {
      return Center(
        child: Padding(
          padding: const EdgeInsets.all(28),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              const Icon(
                Icons.auto_awesome,
                size: 52,
                color: Color(0xFF6366F1),
              ),
              const SizedBox(height: 14),
              const Text(
                'Festival AI is being prepared',
                style: TextStyle(fontSize: 19, fontWeight: FontWeight.w800),
              ),
              const SizedBox(height: 8),
              Text(
                _models.isEmpty
                    ? 'Your current plan does not include an active AI image model yet.'
                    : 'No active festival AI style is available right now.',
                textAlign: TextAlign.center,
                style: const TextStyle(color: Color(0xFF64748B)),
              ),
            ],
          ),
        ),
      );
    }

    return RefreshIndicator(
      onRefresh: _loadOptions,
      child: ListView(
        padding: const EdgeInsets.fromLTRB(12, 10, 12, 96),
        children: [
          _heroCard(),
          const SizedBox(height: 2),
          _myCreationsShortcut(),
          const SizedBox(height: 12),
          _sectionTitle('1. Choose festival'),
          _festivalDropDown(),
          const SizedBox(height: 12),
          _sectionTitle('2. Choose look'),
          _styleSelector(),
          const SizedBox(height: 12),
          _sectionTitle('3. Choose AI model'),
          _optionCard(),
          const SizedBox(height: 12),
          _sectionTitle('4. Add product'),
          _productCard(),
          if (_aiEditableV1Available) ...[
            const SizedBox(height: 12),
            _editableLayersOption(),
          ],
          const SizedBox(height: 14),
          _generateButton(),
          Obx(() {
            final job = _jobs.visibleJob;
            return job == null
                ? const SizedBox.shrink()
                : Padding(
                    padding: const EdgeInsets.only(top: 14),
                    child: _jobCard(job),
                  );
          }),
        ],
      ),
    );
  }

  Widget _heroCard() {
    final remaining = _quota?['remaining']?.toString() ?? '0';
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          colors: [Color(0xFF4338CA), Color(0xFF7C3AED)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(18),
      ),
      child: Row(
        children: [
          const Icon(Icons.auto_awesome_rounded, color: Colors.white, size: 28),
          const SizedBox(width: 10),
          const Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'Festival AI Studio',
                  style: TextStyle(
                    color: Colors.white,
                    fontSize: 17,
                    fontWeight: FontWeight.w800,
                  ),
                ),
                SizedBox(height: 3),
                Text(
                  'Create a visual with your product and chosen look.',
                  style: TextStyle(color: Color(0xFFE9D5FF), fontSize: 10.5),
                ),
              ],
            ),
          ),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 9, vertical: 6),
            decoration: BoxDecoration(
              color: Colors.white.withOpacity(.16),
              borderRadius: BorderRadius.circular(10),
            ),
            child: Column(
              children: [
                Text(
                  remaining,
                  style: const TextStyle(
                    color: Colors.white,
                    fontSize: 16,
                    fontWeight: FontWeight.w800,
                  ),
                ),
                const Text(
                  'left',
                  style: TextStyle(color: Color(0xFFE9D5FF), fontSize: 10),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _sectionTitle(String value) => Text(
    value,
    style: const TextStyle(
      fontSize: 13,
      fontWeight: FontWeight.w800,
      color: Color(0xFF1E293B),
    ),
  );

  Widget _myCreationsShortcut() => Align(
    alignment: Alignment.centerRight,
    child: TextButton.icon(
      onPressed: () => FestivalAiCreationsSheet.show(context),
      icon: const Icon(Icons.auto_awesome_rounded, size: 15),
      label: const Text('My creations'),
      style: TextButton.styleFrom(
        foregroundColor: const Color(0xFF4F46E5),
        textStyle: const TextStyle(fontSize: 11, fontWeight: FontWeight.w800),
        padding: const EdgeInsets.symmetric(horizontal: 5, vertical: 4),
      ),
    ),
  );

  Widget _festivalDropDown() {
    return InkWell(
      onTap: _showFestivalPicker,
      borderRadius: BorderRadius.circular(14),
      child: Container(
        margin: const EdgeInsets.only(top: 6),
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
        decoration: _cardDecoration(),
        child: Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            Expanded(
              child: Text(
                _festival?['title']?.toString() ?? 'Select Festival',
                style: const TextStyle(
                  fontSize: 14,
                  fontWeight: FontWeight.w600,
                  color: Color(0xFF1E293B),
                ),
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
              ),
            ),
            const Icon(
              Icons.keyboard_arrow_down_rounded,
              color: Color(0xFF64748B),
            ),
          ],
        ),
      ),
    );
  }

  void _showFestivalPicker() {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (context) {
        return Container(
          height: MediaQuery.of(context).size.height * 0.65,
          decoration: const BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
          ),
          child: Column(
            children: [
              const SizedBox(height: 12),
              Container(
                width: 40,
                height: 4,
                decoration: BoxDecoration(
                  color: const Color(0xFFCBD5E1),
                  borderRadius: BorderRadius.circular(4),
                ),
              ),
              const Padding(
                padding: EdgeInsets.fromLTRB(20, 20, 20, 10),
                child: Align(
                  alignment: Alignment.centerLeft,
                  child: Text(
                    'Choose Festival',
                    style: TextStyle(fontSize: 18, fontWeight: FontWeight.w800),
                  ),
                ),
              ),
              Expanded(
                child: ListView.builder(
                  padding: const EdgeInsets.only(bottom: 24),
                  itemCount: _festivals.length,
                  itemBuilder: (context, index) {
                    final festival = Map<String, dynamic>.from(
                      _festivals[index] as Map,
                    );
                    final isSelected = festival['id'] == _festival?['id'];
                    return ListTile(
                      contentPadding: const EdgeInsets.symmetric(
                        horizontal: 20,
                      ),
                      title: Text(
                        festival['title']?.toString() ?? 'Festival',
                        style: TextStyle(
                          fontWeight: isSelected
                              ? FontWeight.w700
                              : FontWeight.w500,
                          color: isSelected
                              ? const Color(0xFF6366F1)
                              : const Color(0xFF1E293B),
                        ),
                      ),
                      trailing: isSelected
                          ? const Icon(
                              Icons.check_circle,
                              color: Color(0xFF6366F1),
                            )
                          : null,
                      onTap: () {
                        _selectFestival(festival);
                        Navigator.pop(context);
                      },
                    );
                  },
                ),
              ),
            ],
          ),
        );
      },
    );
  }

  void _openStylePreview(Map<String, dynamic> style) {
    final previews = List<dynamic>.from(style['preview_images'] ?? []);
    if (previews.isEmpty) {
      _selectStyle(style);
      return;
    }

    showDialog(
      context: context,
      useSafeArea: false,
      builder: (context) {
        return Scaffold(
          backgroundColor: Colors.black,
          body: Stack(
            children: [
              PageView.builder(
                itemCount: previews.length,
                itemBuilder: (context, index) {
                  return InteractiveViewer(
                    child: Image.network(
                      previews[index].toString(),
                      fit: BoxFit.contain,
                    ),
                  );
                },
              ),
              if (previews.length > 1)
                Positioned(
                  bottom: MediaQuery.of(context).padding.bottom + 100,
                  left: 0,
                  right: 0,
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: List.generate(
                      previews.length,
                      (index) => Container(
                        margin: const EdgeInsets.symmetric(horizontal: 4),
                        width: 8,
                        height: 8,
                        decoration: const BoxDecoration(
                          color: Colors.white70,
                          shape: BoxShape.circle,
                        ),
                      ),
                    ),
                  ),
                ),
              Positioned(
                top: MediaQuery.of(context).padding.top + 10,
                right: 16,
                child: IconButton(
                  icon: const Icon(Icons.close, color: Colors.white, size: 30),
                  onPressed: () => Navigator.pop(context),
                ),
              ),
              Positioned(
                bottom: MediaQuery.of(context).padding.bottom + 20,
                left: 20,
                right: 20,
                child: ElevatedButton(
                  onPressed: () {
                    _selectStyle(style);
                    Navigator.pop(context);
                  },
                  style: ElevatedButton.styleFrom(
                    backgroundColor: const Color(0xFF6366F1),
                    padding: const EdgeInsets.symmetric(vertical: 16),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(16),
                    ),
                  ),
                  child: const Text(
                    'Select this Look',
                    style: TextStyle(
                      fontSize: 16,
                      fontWeight: FontWeight.bold,
                      color: Colors.white,
                    ),
                  ),
                ),
              ),
            ],
          ),
        );
      },
    );
  }

  Widget _styleSelector() {
    return SizedBox(
      height: 112,
      child: ListView.separated(
        padding: const EdgeInsets.only(top: 6),
        scrollDirection: Axis.horizontal,
        itemCount: _styles.length,
        separatorBuilder: (_, __) => const SizedBox(width: 8),
        itemBuilder: (context, index) {
          final style = Map<String, dynamic>.from(_styles[index] as Map);
          final selected = style['id'] == _style?['id'];
          final previews = List<dynamic>.from(style['preview_images'] ?? []);
          final image = previews.isNotEmpty ? previews.first.toString() : '';
          return InkWell(
            onTap: () => _selectStyle(style),
            borderRadius: BorderRadius.circular(14),
            child: Container(
              width: 106,
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(14),
                border: Border.all(
                  color: selected
                      ? const Color(0xFF6366F1)
                      : const Color(0xFFE2E8F0),
                  width: selected ? 2 : 1,
                ),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Expanded(
                    child: Stack(
                      fit: StackFit.expand,
                      children: [
                        ClipRRect(
                          borderRadius: const BorderRadius.vertical(
                            top: Radius.circular(12),
                          ),
                          child: image.isEmpty
                              ? _stylePreviewFallback(style)
                              : Image.network(
                                  image,
                                  fit: BoxFit.cover,
                                  filterQuality: FilterQuality.medium,
                                  loadingBuilder: (_, child, loadingProgress) =>
                                      loadingProgress == null
                                      ? child
                                      : _stylePreviewFallback(
                                          style,
                                          isLoading: true,
                                        ),
                                  errorBuilder: (_, __, ___) =>
                                      _stylePreviewFallback(style),
                                ),
                        ),
                        if (previews.isNotEmpty)
                          Positioned(
                            top: 4,
                            right: 4,
                            child: InkWell(
                              onTap: () => _openStylePreview(style),
                              child: Container(
                                padding: const EdgeInsets.all(4),
                                decoration: const BoxDecoration(
                                  color: Colors.black45,
                                  shape: BoxShape.circle,
                                ),
                                child: const Icon(
                                  Icons.zoom_out_map,
                                  size: 14,
                                  color: Colors.white,
                                ),
                              ),
                            ),
                          ),
                      ],
                    ),
                  ),
                  Padding(
                    padding: const EdgeInsets.fromLTRB(7, 6, 7, 7),
                    child: Text(
                      style['name']?.toString() ?? 'Style',
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(
                        fontSize: 10.5,
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                  ),
                ],
              ),
            ),
          );
        },
      ),
    );
  }

  Widget _stylePreviewFallback(
    Map<String, dynamic> style, {
    bool isLoading = false,
  }) {
    final name = style['name']?.toString().trim() ?? 'Style';
    final initial = name.isEmpty ? 'S' : name.substring(0, 1).toUpperCase();

    return Container(
      decoration: const BoxDecoration(
        gradient: LinearGradient(
          colors: [Color(0xFF5B4BE8), Color(0xFF9B5DE5)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
      ),
      child: Center(
        child: isLoading
            ? const SizedBox(
                width: 20,
                height: 20,
                child: CircularProgressIndicator(
                  strokeWidth: 2,
                  color: Colors.white,
                ),
              )
            : Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Text(
                    initial,
                    style: const TextStyle(
                      color: Colors.white,
                      fontSize: 24,
                      fontWeight: FontWeight.w800,
                    ),
                  ),
                  const Icon(
                    Icons.auto_awesome_rounded,
                    size: 14,
                    color: Color(0xFFEDE9FE),
                  ),
                ],
              ),
      ),
    );
  }

  Widget _modelSelector() {
    final variants = _modelVariants;
    // Find currently selected variant display name
    final selectedVariant = (_model != null && _quality != null)
        ? variants.cast<Map<String, dynamic>?>().firstWhere(
            (v) => v!['id'] == _model!['id'] && v['quality_key'] == _quality,
            orElse: () => null,
          )
        : null;
    final selectedName =
        selectedVariant?['variant_name']?.toString() ?? 'Select model';

    return Container(
      margin: const EdgeInsets.only(top: 8),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(
          color: _modelDropdownOpen
              ? const Color(0xFF6366F1)
              : const Color(0xFFE2E8F0),
        ),
      ),
      clipBehavior: Clip.antiAlias,
      child: Column(
        children: [
          // ── Trigger bar ──
          InkWell(
            onTap: () =>
                setState(() => _modelDropdownOpen = !_modelDropdownOpen),
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 14),
              color: _modelDropdownOpen
                  ? const Color(0xFFFAF9FF)
                  : Colors.white,
              child: Row(
                children: [
                  Icon(
                    Icons.auto_awesome_outlined,
                    size: 18,
                    color: _modelDropdownOpen
                        ? const Color(0xFF6366F1)
                        : const Color(0xFF94A3B8),
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    child: Text(
                      selectedName,
                      style: TextStyle(
                        fontSize: 14,
                        fontWeight: FontWeight.w600,
                        color: selectedVariant != null
                            ? const Color(0xFF1E293B)
                            : const Color(0xFF94A3B8),
                      ),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                  ),
                  AnimatedRotation(
                    turns: _modelDropdownOpen ? 0.5 : 0,
                    duration: const Duration(milliseconds: 200),
                    child: const Icon(
                      Icons.keyboard_arrow_down_rounded,
                      color: Color(0xFF64748B),
                    ),
                  ),
                ],
              ),
            ),
          ),
          // ── Expandable options ──
          AnimatedSize(
            duration: const Duration(milliseconds: 250),
            curve: Curves.easeInOut,
            child: _modelDropdownOpen
                ? Column(
                    children: variants.asMap().entries.map((entry) {
                      final index = entry.key;
                      final variant = entry.value;
                      final available = variant['is_available'] == true;
                      final isSelected =
                          _model != null &&
                          variant['id'] == _model!['id'] &&
                          variant['quality_key'] == _quality;
                      final displayName =
                          variant['variant_name']?.toString() ?? 'AI Model';

                      return InkWell(
                        onTap: () {
                          _selectModelVariant(variant);
                          if (variant['is_available'] == true) {
                            setState(() => _modelDropdownOpen = false);
                          }
                        },
                        child: Container(
                          padding: const EdgeInsets.symmetric(
                            horizontal: 14,
                            vertical: 12,
                          ),
                          decoration: BoxDecoration(
                            color: isSelected
                                ? const Color(0xFFEEF2FF)
                                : Colors.white,
                            border: Border(
                              top: BorderSide(
                                color: index == 0
                                    ? const Color(0xFFE9E7FF)
                                    : const Color(0xFFF1F0FF),
                                width: index == 0 ? 1 : 0.5,
                              ),
                            ),
                          ),
                          child: Row(
                            children: [
                              Icon(
                                isSelected
                                    ? Icons.radio_button_checked
                                    : Icons.radio_button_off,
                                size: 20,
                                color: isSelected
                                    ? const Color(0xFF6366F1)
                                    : available
                                    ? const Color(0xFFCBD5E1)
                                    : const Color(0xFFE2E8F0),
                              ),
                              const SizedBox(width: 12),
                              Expanded(
                                child: Text(
                                  displayName,
                                  style: TextStyle(
                                    fontSize: 14,
                                    fontWeight: isSelected
                                        ? FontWeight.w700
                                        : FontWeight.w500,
                                    color: available
                                        ? (isSelected
                                              ? const Color(0xFF4F46E5)
                                              : const Color(0xFF1E293B))
                                        : const Color(0xFFADB5BD),
                                  ),
                                ),
                              ),
                              if (!available)
                                Container(
                                  padding: const EdgeInsets.symmetric(
                                    horizontal: 8,
                                    vertical: 3,
                                  ),
                                  decoration: BoxDecoration(
                                    gradient: const LinearGradient(
                                      colors: [
                                        Color(0xFFF59E0B),
                                        Color(0xFFF97316),
                                      ],
                                    ),
                                    borderRadius: BorderRadius.circular(8),
                                  ),
                                  child: const Text(
                                    'Premium',
                                    style: TextStyle(
                                      fontSize: 9,
                                      fontWeight: FontWeight.w800,
                                      color: Colors.white,
                                    ),
                                  ),
                                ),
                            ],
                          ),
                        ),
                      );
                    }).toList(),
                  )
                : const SizedBox.shrink(),
          ),
        ],
      ),
    );
  }

  void _showLanguagePicker() {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (context) {
        return Container(
          height: MediaQuery.of(context).size.height * 0.5,
          decoration: const BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
          ),
          child: Column(
            children: [
              const SizedBox(height: 12),
              Container(
                width: 40,
                height: 4,
                decoration: BoxDecoration(
                  color: const Color(0xFFCBD5E1),
                  borderRadius: BorderRadius.circular(4),
                ),
              ),
              const Padding(
                padding: EdgeInsets.fromLTRB(20, 20, 20, 10),
                child: Align(
                  alignment: Alignment.centerLeft,
                  child: Text(
                    'Select Language',
                    style: TextStyle(fontSize: 18, fontWeight: FontWeight.w800),
                  ),
                ),
              ),
              Expanded(
                child: ListView.builder(
                  padding: const EdgeInsets.only(bottom: 24),
                  itemCount: _languages.length,
                  itemBuilder: (context, index) {
                    final language = Map<String, dynamic>.from(
                      _languages[index] as Map,
                    );
                    final isSelected = language['id'] == _language?['id'];
                    return ListTile(
                      contentPadding: const EdgeInsets.symmetric(
                        horizontal: 20,
                      ),
                      title: Text(
                        language['title']?.toString() ?? 'Language',
                        style: TextStyle(
                          fontWeight: isSelected
                              ? FontWeight.w700
                              : FontWeight.w500,
                          color: isSelected
                              ? const Color(0xFF6366F1)
                              : const Color(0xFF1E293B),
                        ),
                      ),
                      trailing: isSelected
                          ? const Icon(
                              Icons.check_circle,
                              color: Color(0xFF6366F1),
                            )
                          : null,
                      onTap: () {
                        _selectLanguage(language);
                        Navigator.pop(context);
                      },
                    );
                  },
                ),
              ),
            ],
          ),
        );
      },
    );
  }

  Widget _optionCard() {
    return Container(
      margin: const EdgeInsets.only(top: 6),
      padding: const EdgeInsets.all(16),
      decoration: _cardDecoration(),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text(
            'Choose AI model',
            style: TextStyle(
              fontSize: 12,
              fontWeight: FontWeight.w700,
              color: Color(0xFF475569),
            ),
          ),
          _modelSelector(),
          const SizedBox(height: 18),
          const Text(
            'Post size',
            style: TextStyle(
              fontSize: 13,
              fontWeight: FontWeight.w800,
              color: Color(0xFF1E293B),
            ),
          ),
          const SizedBox(height: 10),
          Wrap(
            spacing: 8,
            runSpacing: 8,
            children: _availableSizes.map((size) {
              final key = size['key']?.toString() ?? '';
              final ratio = size['ratio']?.toString() ?? key;
              final selected = _sizeKey == key;
              // Parse ratio for icon
              final parts = ratio.split(':');
              final w = double.tryParse(parts.isNotEmpty ? parts[0] : '1') ?? 1;
              final h = double.tryParse(parts.length > 1 ? parts[1] : '1') ?? 1;
              final isSquare = (w - h).abs() < 0.01;
              final isLandscape = w > h;

              return InkWell(
                onTap: () => setState(() => _sizeKey = key),
                borderRadius: BorderRadius.circular(10),
                child: Container(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 12,
                    vertical: 10,
                  ),
                  decoration: BoxDecoration(
                    color: selected ? const Color(0xFFEEF2FF) : Colors.white,
                    borderRadius: BorderRadius.circular(10),
                    border: Border.all(
                      color: selected
                          ? const Color(0xFF6366F1)
                          : const Color(0xFFE2E8F0),
                    ),
                  ),
                  child: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      // Aspect ratio icon
                      Container(
                        width: isSquare ? 14 : (isLandscape ? 18 : 11),
                        height: isSquare ? 14 : (isLandscape ? 11 : 18),
                        decoration: BoxDecoration(
                          borderRadius: BorderRadius.circular(2),
                          border: Border.all(
                            color: selected
                                ? const Color(0xFF4F46E5)
                                : const Color(0xFF94A3B8),
                            width: 1.5,
                          ),
                        ),
                      ),
                      const SizedBox(width: 6),
                      Text(
                        ratio,
                        style: TextStyle(
                          fontSize: 12,
                          fontWeight: selected
                              ? FontWeight.w700
                              : FontWeight.w500,
                          color: selected
                              ? const Color(0xFF4F46E5)
                              : const Color(0xFF64748B),
                        ),
                      ),
                    ],
                  ),
                ),
              );
            }).toList(),
          ),
          const SizedBox(height: 18),
          const Text(
            'Text language',
            style: TextStyle(
              fontSize: 13,
              fontWeight: FontWeight.w800,
              color: Color(0xFF1E293B),
            ),
          ),
          const SizedBox(height: 10),
          InkWell(
            onTap: _showLanguagePicker,
            borderRadius: BorderRadius.circular(12),
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 14),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: const Color(0xFFE2E8F0)),
              ),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Text(
                    _language?['title']?.toString() ?? 'Select language',
                    style: const TextStyle(
                      fontSize: 13,
                      color: Color(0xFF1E293B),
                      fontWeight: FontWeight.w500,
                    ),
                  ),
                  const Icon(
                    Icons.language,
                    color: Color(0xFF64748B),
                    size: 18,
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _productCard() {
    final count = _selectedProductIds.length;
    final choosing = _productMode == 'choose';
    final uploading = _productMode == 'upload';
    final canUseProduct = _maxProducts > 0;

    return Container(
      margin: const EdgeInsets.only(top: 6),
      padding: const EdgeInsets.all(12),
      decoration: _cardDecoration(),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            _styleNeedsProduct
                ? 'A product is required for this look'
                : 'Add a product (optional)',
            style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w800),
          ),
          const SizedBox(height: 3),
          Text(
            canUseProduct
                ? 'Use one source only: choose saved products or upload a product photo.'
                : 'Choose another AI model to use a product reference image.',
            style: const TextStyle(fontSize: 10.5, color: Color(0xFF64748B)),
          ),
          const SizedBox(height: 10),
          Row(
            children: [
              Expanded(
                child: _productSourceButton(
                  title: choosing && count > 0
                      ? '$count selected'
                      : 'Choose product',
                  icon: Icons.inventory_2_outlined,
                  active: choosing,
                  disabled: uploading || !canUseProduct,
                  onTap: _pickProducts,
                ),
              ),
              const SizedBox(width: 8),
              Expanded(
                child: _productSourceButton(
                  title: uploading ? 'Photo selected' : 'Upload product',
                  icon: Icons.add_photo_alternate_outlined,
                  active: uploading,
                  disabled: choosing || !canUseProduct || !_allowProductUpload,
                  onTap: _pickUploadedProduct,
                ),
              ),
            ],
          ),
          if (_productMode != null)
            Align(
              alignment: Alignment.centerRight,
              child: TextButton.icon(
                onPressed: _changeProductSource,
                icon: const Icon(Icons.swap_horiz_rounded, size: 15),
                label: const Text('Change source'),
                style: TextButton.styleFrom(
                  padding: const EdgeInsets.only(top: 8),
                  textStyle: const TextStyle(
                    fontSize: 11,
                    fontWeight: FontWeight.w700,
                  ),
                ),
              ),
            ),
          if (uploading) ...[
            _uploadedProductSummary(),
            const SizedBox(height: 10),
            _uploadedProductNameField(),
            const SizedBox(height: 10),
            _instructionCard(),
          ],
        ],
      ),
    );
  }

  Widget _productSourceButton({
    required String title,
    required IconData icon,
    required bool active,
    required bool disabled,
    required VoidCallback onTap,
  }) {
    final color = active ? const Color(0xFF4F46E5) : const Color(0xFF64748B);
    return InkWell(
      onTap: disabled ? null : onTap,
      borderRadius: BorderRadius.circular(12),
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 180),
        padding: const EdgeInsets.symmetric(vertical: 10, horizontal: 8),
        decoration: BoxDecoration(
          color: active ? const Color(0xFFF1F0FF) : const Color(0xFFF8FAFC),
          borderRadius: BorderRadius.circular(12),
          border: Border.all(
            color: active ? const Color(0xFF6366F1) : const Color(0xFFE2E8F0),
          ),
        ),
        child: Row(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(
              icon,
              color: disabled ? const Color(0xFFCBD5E1) : color,
              size: 17,
            ),
            const SizedBox(width: 6),
            Flexible(
              child: Text(
                title,
                overflow: TextOverflow.ellipsis,
                style: TextStyle(
                  color: disabled ? const Color(0xFF94A3B8) : color,
                  fontSize: 11,
                  fontWeight: FontWeight.w800,
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _uploadedProductSummary() => Container(
    padding: const EdgeInsets.all(9),
    decoration: BoxDecoration(
      color: const Color(0xFFF8FAFC),
      borderRadius: BorderRadius.circular(10),
    ),
    child: Row(
      children: [
        const Icon(Icons.image_rounded, size: 18, color: Color(0xFF6366F1)),
        const SizedBox(width: 7),
        Expanded(
          child: Text(
            _uploadedProductImage?.name ?? 'Product photo',
            overflow: TextOverflow.ellipsis,
            style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w700),
          ),
        ),
        const Icon(
          Icons.check_circle_rounded,
          size: 17,
          color: Color(0xFF059669),
        ),
      ],
    ),
  );

  Widget _uploadedProductNameField() {
    return TextField(
      controller: _uploadedProductNameController,
      maxLength: 150,
      textInputAction: TextInputAction.next,
      decoration: InputDecoration(
        labelText: _requiresUploadedProductName
            ? 'Product name *'
            : 'Product name',
        hintText: 'e.g. Commercial juicer',
        counterText: '',
        filled: true,
        fillColor: Colors.white,
        contentPadding: const EdgeInsets.symmetric(
          horizontal: 13,
          vertical: 12,
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: const BorderSide(color: Color(0xFFE2E8F0)),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: const BorderSide(color: Color(0xFF6366F1)),
        ),
      ),
    );
  }

  Widget _instructionCard() {
    final max =
        int.tryParse(
          _festival?['max_user_instruction_characters']?.toString() ?? '250',
        ) ??
        250;
    return Container(
      margin: const EdgeInsets.only(top: 6),
      padding: const EdgeInsets.all(4),
      decoration: _cardDecoration(),
      child: TextField(
        controller: _instructionController,
        maxLength: max,
        maxLines: 3,
        decoration: const InputDecoration(
          border: InputBorder.none,
          contentPadding: EdgeInsets.all(12),
          hintText:
              'Product instruction: place it in the lower-right, keep label and logo clear.',
        ),
      ),
    );
  }

  Widget _editableLayersOption() {
    return Container(
      decoration: _cardDecoration(),
      child: SwitchListTile.adaptive(
        value: _createEditableV1,
        onChanged: _submitting
            ? null
            : (value) => setState(() => _createEditableV1 = value),
        activeThumbColor: const Color(0xFF4F46E5),
        secondary: const Icon(Icons.layers_outlined, color: Color(0xFF4F46E5)),
        title: const Text(
          'Editable AI layers',
          style: TextStyle(fontWeight: FontWeight.w800),
        ),
        subtitle: const Text(
          'Creates a separate background, objects, effects and native text. No frame is added.',
          style: TextStyle(fontSize: 11, height: 1.25),
        ),
      ),
    );
  }

  Widget _generateButton() {
    return Obx(() {
      final hasActiveJob = _jobs.hasActiveJob;
      return SizedBox(
        height: 52,
        child: ElevatedButton.icon(
          onPressed: _submitting
              ? null
              : hasActiveJob
              ? () => FestivalAiCreationsSheet.show(context)
              : _generate,
          style: ElevatedButton.styleFrom(
            backgroundColor: const Color(0xFF4F46E5),
            foregroundColor: Colors.white,
            shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(16),
            ),
          ),
          icon: _submitting
              ? const SizedBox(
                  height: 20,
                  width: 20,
                  child: CircularProgressIndicator(
                    strokeWidth: 2,
                    color: Colors.white,
                  ),
                )
              : Icon(
                  hasActiveJob ? Icons.visibility_outlined : Icons.auto_awesome,
                ),
          label: Text(
            _submitting
                ? 'Queueing your visual...'
                : hasActiveJob
                ? 'View current generation'
                : _createEditableV1
                ? 'Generate editable visual'
                : 'Generate festival visual',
            style: const TextStyle(fontWeight: FontWeight.w800),
          ),
        ),
      );
    });
  }

  Widget _jobCard(Map<String, dynamic> job) {
    final status = job['status']?.toString() ?? 'queued';
    final imageUrl = job['image_url']?.toString() ?? '';
    final completed = status == 'completed';
    final failed = status == 'failed';
    final submitting = status == 'submitting';
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: failed ? const Color(0xFFFEF2F2) : Colors.white,
        borderRadius: BorderRadius.circular(18),
        border: Border.all(
          color: failed ? const Color(0xFFFECACA) : const Color(0xFFE2E8F0),
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Icon(
                failed
                    ? Icons.error_outline
                    : completed
                    ? Icons.check_circle_outline
                    : submitting
                    ? Icons.cloud_upload_outlined
                    : Icons.hourglass_top,
                color: failed
                    ? const Color(0xFFDC2626)
                    : completed
                    ? const Color(0xFF059669)
                    : const Color(0xFF6366F1),
              ),
              const SizedBox(width: 8),
              Text(
                failed
                    ? 'Generation failed'
                    : completed
                    ? 'Your visual is ready'
                    : submitting
                    ? 'Preparing your request'
                    : 'Generating in background',
                style: const TextStyle(fontWeight: FontWeight.w800),
              ),
            ],
          ),
          if (!completed && !failed)
            Padding(
              padding: EdgeInsets.only(top: 8),
              child: Text(
                submitting
                    ? 'Uploading your product and checking your selected model…'
                    : 'You can stay here or continue using the app. This card will update automatically.',
                style: const TextStyle(fontSize: 12, color: Color(0xFF64748B)),
              ),
            ),
          if (failed)
            Padding(
              padding: const EdgeInsets.only(top: 8),
              child: Text(
                job['error_message']?.toString() ?? 'Your quota was restored.',
                style: const TextStyle(fontSize: 12, color: Color(0xFFB91C1C)),
              ),
            ),
          if (completed && imageUrl.isNotEmpty) ...[
            const SizedBox(height: 12),
            ClipRRect(
              borderRadius: BorderRadius.circular(14),
              child: Image.network(
                imageUrl,
                width: double.infinity,
                fit: BoxFit.cover,
                errorBuilder: (_, __, ___) => const SizedBox(
                  height: 160,
                  child: Center(
                    child: Text('Image ready. Please refresh to view it.'),
                  ),
                ),
              ),
            ),
          ],
        ],
      ),
    );
  }

  BoxDecoration _cardDecoration() => BoxDecoration(
    color: Colors.white,
    borderRadius: BorderRadius.circular(14),
    border: Border.all(color: const Color(0xFFE9E7FF)),
    boxShadow: const [
      BoxShadow(color: Color(0x0C4F46E5), blurRadius: 12, offset: Offset(0, 4)),
    ],
  );
}

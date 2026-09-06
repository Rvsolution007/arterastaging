import 'dart:convert';
import 'dart:io';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:image_picker/image_picker.dart';
import 'package:cached_network_image/cached_network_image.dart';
import '../utils/app_colors.dart';
import '../utils/app_text_styles.dart';
import '../utils/app_spacing.dart';
import '../controllers/home_controller.dart';
import '../services/api_service.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../widgets/multi_select_dropdown.dart';
import '../widgets/cascading_business_dropdowns.dart';

class BusinessProfileScreen extends StatefulWidget {
  final Map<String, dynamic>? business;
  final bool isNew;
  final String? redirectRoute;
  final dynamic redirectArguments;

  const BusinessProfileScreen({
    Key? key,
    this.business,
    this.isNew = false,
    this.redirectRoute,
    this.redirectArguments,
  }) : super(key: key);

  @override
  State<BusinessProfileScreen> createState() => _BusinessProfileScreenState();
}

class _BusinessProfileScreenState extends State<BusinessProfileScreen> {
  final HomeController hc = Get.find<HomeController>();

  late TextEditingController _nameCtrl;
  late TextEditingController _emailCtrl;
  late TextEditingController _phoneCtrl;
  late TextEditingController _websiteCtrl;
  late TextEditingController _addressCtrl;
  late TextEditingController _brandPrimaryColorCtrl;
  late TextEditingController _brandSecondaryColorCtrl;

  List<TextEditingController> _extraEmailCtrls = [];
  List<TextEditingController> _extraPhoneCtrls = [];
  List<TextEditingController> _extraWebsiteCtrls = [];
  List<TextEditingController> _extraAddressCtrls = [];

  File? _selectedImage;
  bool _isLoading = false;
  bool _sameAsPersonalInfo = false;

  Set<TextEditingController> _hiddenEmails = {};
  Set<TextEditingController> _hiddenPhones = {};
  Set<TextEditingController> _hiddenWebsites = {};
  Set<TextEditingController> _hiddenAddresses = {};
  bool _hideBusinessName = false;
  bool _hideBusinessLogo = false;

  String _selectedCategoryId = '1';
  List<String> _selectedSubCategoryIds = [];
  List<String> _selectedBusinessTypeIds = [];
  bool _hasTypesForSelectedSubCategory = false;
  List<String> _selectedProductIds = [];
  final Map<String, String> _initialProductNames = {};

  // ── Per-Category Selection Cache ──
  // Saves selections when user switches category, restores when they switch back
  final Map<String, Map<String, dynamic>> _categoryCacheMap = {};
  int _cascadingKey = 0; // Force rebuild key for CascadingBusinessDropdowns
  int _productKey = 0; // Force rebuild key for Products MultiSelectDropdown

  String _logoUrl = '';
  String _businessId = '';

  /// A previous API response could contain the server's cached localhost URL.
  /// Replace only that stale host for this logo preview; business/category
  /// selection logic remains untouched.
  String get _previewLogoUrl {
    final raw = _logoUrl.trim();
    if (raw.isEmpty) return '';
    if (!raw.startsWith('http')) {
      return '${hc.uploadsBaseUrl}/${raw.replaceFirst(RegExp(r'^/+'), '')}';
    }

    final uri = Uri.tryParse(raw);
    final localHost =
        uri != null &&
        (uri.host == 'localhost' ||
            uri.host == '127.0.0.1' ||
            uri.host == '::1');
    final uploadsOffset = uri?.path.indexOf('/uploads/') ?? -1;
    if (localHost && uploadsOffset >= 0) {
      final relativePath = uri!.path.substring(
        uploadsOffset + '/uploads/'.length,
      );
      return '${hc.uploadsBaseUrl}/$relativePath';
    }

    return raw;
  }

  Future<void> _toggleSameAsPersonal(bool? val) async {
    bool isSame = val ?? false;
    setState(() {
      _sameAsPersonalInfo = isSame;
    });

    if (isSame) {
      final prefs = await SharedPreferences.getInstance();
      final personalEmail = prefs.getString('emailId') ?? '';
      final personalPhone = prefs.getString('phoneNumber') ?? '';

      setState(() {
        _emailCtrl.text = personalEmail;
        _phoneCtrl.text = personalPhone;
      });
    } else {
      setState(() {
        _emailCtrl.clear();
        _phoneCtrl.clear();
      });
    }
  }

  Future<List<Map<String, dynamic>>> _fetchProducts(String query) async {
    if (_selectedSubCategoryIds.isEmpty) return [];
    if (_hasTypesForSelectedSubCategory && _selectedBusinessTypeIds.isEmpty) {
      return [];
    }

    try {
      final res = await ApiService.post('/business-products/search', {
        'business_sub_category_id': _selectedSubCategoryIds.join(','),
        'business_type_id': _selectedBusinessTypeIds.join(','),
        'query': query,
      });
      if (res.statusCode == 200) {
        final data = jsonDecode(res.body);
        List<dynamic> list = data['data'] ?? [];
        return list.map((e) => Map<String, dynamic>.from(e)).toList();
      }
    } catch (_) {}
    return [];
  }

  @override
  void initState() {
    super.initState();

    // Determine data source
    bool useHc = widget.business == null && !widget.isNew;
    Map<String, dynamic> biz = widget.business ?? {};

    String getBizStr(String key1, String key2, String hcVal) {
      if (widget.isNew) return '';
      if (useHc) return hcVal;
      return biz[key1]?.toString() ?? biz[key2]?.toString() ?? '';
    }

    List<String> getBizList(String key, List<String> hcVal) {
      if (widget.isNew) return [];
      if (useHc) return hcVal;
      return (biz[key] as List<dynamic>? ?? [])
          .map((e) => e.toString())
          .toList();
    }

    _nameCtrl = TextEditingController(
      text: getBizStr('name', 'bussinessName', hc.businessName.value),
    );
    _emailCtrl = TextEditingController(
      text: getBizStr('email', 'bussinessEmail', hc.businessEmail.value),
    );
    _phoneCtrl = TextEditingController(
      text: getBizStr('mobileNo', 'mobile_no', hc.businessPhone.value),
    );
    _websiteCtrl = TextEditingController(
      text: getBizStr('website', 'bussinessWebsite', hc.businessWebsite.value),
    );
    _addressCtrl = TextEditingController(
      text: getBizStr('address', 'bussinessAddress', hc.businessAddress.value),
    );
    _brandPrimaryColorCtrl = TextEditingController(
      text: getBizStr('brand_primary_color', 'brandPrimaryColor', ''),
    );
    _brandSecondaryColorCtrl = TextEditingController(
      text: getBizStr('brand_secondary_color', 'brandSecondaryColor', ''),
    );

    _logoUrl = getBizStr('logo', 'bussinessLogo', hc.businessLogo.value);
    _businessId = getBizStr('id', 'bussinessId', hc.businessId.value);

    // Category ID
    String catId = '';
    if (!widget.isNew) {
      if (useHc) {
        catId = hc.businessCategoryId.value;
      } else {
        if (biz['businessCategory'] != null &&
            biz['businessCategory']['businessCategoryId'] != null) {
          catId = biz['businessCategory']['businessCategoryId'].toString();
        } else if (biz['business_category_id'] != null) {
          catId = biz['business_category_id'].toString();
        } else if (biz['businessCategoryId'] != null) {
          catId = biz['businessCategoryId'].toString();
        }
      }
    }
    if (catId.isEmpty) catId = '1';

    // verify if catId exists in hc.profileCategories
    bool catExists = hc.profileCategories.any(
      (cat) =>
          (cat['businessCategoryId']?.toString() ?? cat['id']?.toString()) ==
          catId,
    );
    if (!catExists && hc.profileCategories.isNotEmpty) {
      catId =
          hc.profileCategories.first['businessCategoryId']?.toString() ??
          hc.profileCategories.first['id']?.toString() ??
          '1';
    }
    _selectedCategoryId = catId;

    // Multi-Select IDs
    _selectedSubCategoryIds = getBizList(
      'business_sub_category_ids',
      hc.businessSubCategoryIds.toList(),
    );
    _selectedBusinessTypeIds = getBizList(
      'business_type_ids',
      hc.businessTypeIds.toList(),
    );

    if (widget.isNew) {
      _selectedProductIds = [];
    } else if (useHc) {
      _selectedProductIds = hc.products.map((p) => p['id'].toString()).toList();
      for (var p in hc.products) {
        _initialProductNames[p['id'].toString()] = p['name']?.toString() ?? '';
      }
    } else {
      _selectedProductIds = (biz['products'] as List<dynamic>? ?? [])
          .map((e) => e['id'].toString())
          .toList();
      for (var p in (biz['products'] as List<dynamic>? ?? [])) {
        _initialProductNames[p['id'].toString()] = p['name']?.toString() ?? '';
      }
    }

    List<String> extEmails = getBizList('extra_emails', hc.extraEmails);
    List<String> extPhones = getBizList('extra_mobile_numbers', hc.extraPhones);
    List<String> extWebsites = getBizList('extra_websites', hc.extraWebsites);
    List<String> extAddrs = getBizList('extra_addresses', hc.extraAddresses);

    _extraEmailCtrls = extEmails
        .map((e) => TextEditingController(text: e))
        .toList();
    _extraPhoneCtrls = extPhones
        .map((e) => TextEditingController(text: e))
        .toList();
    _extraWebsiteCtrls = extWebsites
        .map((e) => TextEditingController(text: e))
        .toList();
    _extraAddressCtrls = extAddrs
        .map((e) => TextEditingController(text: e))
        .toList();

    // Initialize hidden states
    Map<String, dynamic> hf = {};
    if (useHc) {
      hf = hc.hiddenFrameFields;
    } else if (!widget.isNew && biz['hidden_frame_fields'] != null) {
      if (biz['hidden_frame_fields'] is Map) {
        hf = Map<String, dynamic>.from(biz['hidden_frame_fields']);
      }
    }

    List<dynamic> hEmails = hf['emails'] ?? [];
    List<dynamic> hPhones = hf['mobile_numbers'] ?? [];
    List<dynamic> hWebs = hf['websites'] ?? [];
    List<dynamic> hAddrs = hf['addresses'] ?? [];
    _hideBusinessName =
        hf['business_name'] == true ||
        hf['business_name'] == 1 ||
        '${hf['business_name']}'.toLowerCase() == 'true';
    _hideBusinessLogo =
        hf['logo'] == true ||
        hf['logo'] == 1 ||
        '${hf['logo']}'.toLowerCase() == 'true';

    if (hEmails.contains(_emailCtrl.text)) _hiddenEmails.add(_emailCtrl);
    if (hPhones.contains(_phoneCtrl.text)) _hiddenPhones.add(_phoneCtrl);
    if (hWebs.contains(_websiteCtrl.text)) _hiddenWebsites.add(_websiteCtrl);
    if (hAddrs.contains(_addressCtrl.text)) _hiddenAddresses.add(_addressCtrl);

    for (int i = 0; i < extEmails.length; i++) {
      if (hEmails.contains(extEmails[i]))
        _hiddenEmails.add(_extraEmailCtrls[i]);
    }
    for (int i = 0; i < extPhones.length; i++) {
      if (hPhones.contains(extPhones[i]))
        _hiddenPhones.add(_extraPhoneCtrls[i]);
    }
    for (int i = 0; i < extWebsites.length; i++) {
      if (hWebs.contains(extWebsites[i]))
        _hiddenWebsites.add(_extraWebsiteCtrls[i]);
    }
    for (int i = 0; i < extAddrs.length; i++) {
      if (hAddrs.contains(extAddrs[i]))
        _hiddenAddresses.add(_extraAddressCtrls[i]);
    }
  }

  @override
  void dispose() {
    _nameCtrl.dispose();
    _emailCtrl.dispose();
    _phoneCtrl.dispose();
    _websiteCtrl.dispose();
    _addressCtrl.dispose();
    _brandPrimaryColorCtrl.dispose();
    _brandSecondaryColorCtrl.dispose();

    for (var ctrl in _extraEmailCtrls) {
      ctrl.dispose();
    }
    for (var ctrl in _extraPhoneCtrls) {
      ctrl.dispose();
    }
    for (var ctrl in _extraWebsiteCtrls) {
      ctrl.dispose();
    }
    for (var ctrl in _extraAddressCtrls) {
      ctrl.dispose();
    }
    super.dispose();
  }

  Future<void> _pickImage() async {
    final ImagePicker picker = ImagePicker();
    final XFile? image = await picker.pickImage(source: ImageSource.gallery);
    if (image != null) {
      setState(() {
        _selectedImage = File(image.path);
      });
    }
  }

  Future<void> _saveProfile() async {
    if (_nameCtrl.text.isEmpty) {
      Get.snackbar(
        'Error',
        'Business name is required',
        backgroundColor: Colors.red,
        colorText: Colors.white,
      );
      return;
    }

    if (_phoneCtrl.text.isNotEmpty) {
      String digits = _phoneCtrl.text.replaceAll(RegExp(r'\D'), '');
      bool isValid = false;
      if (digits.length == 10) isValid = true;
      if (digits.length == 12 && digits.startsWith('91')) isValid = true;
      if (!isValid) {
        Get.snackbar(
          'Error',
          'Please enter a valid 10-digit mobile number (with optional +91)',
          backgroundColor: Colors.red,
          colorText: Colors.white,
        );
        return;
      }
    }

    if (!_hasValidBusinessTheme()) {
      Get.snackbar(
        'Business theme',
        'Enter both theme colours as #RRGGBB, or leave both blank.',
        backgroundColor: Colors.red,
        colorText: Colors.white,
      );
      return;
    }

    setState(() => _isLoading = true);

    // Build hidden_frame_fields
    List<String> hiddenEmails = [];
    List<String> hiddenPhones = [];
    List<String> hiddenWebsites = [];
    List<String> hiddenAddresses = [];

    if (_hiddenEmails.contains(_emailCtrl) && _emailCtrl.text.isNotEmpty)
      hiddenEmails.add(_emailCtrl.text);
    for (var c in _extraEmailCtrls) {
      if (_hiddenEmails.contains(c) && c.text.isNotEmpty)
        hiddenEmails.add(c.text);
    }

    if (_hiddenPhones.contains(_phoneCtrl) && _phoneCtrl.text.isNotEmpty)
      hiddenPhones.add(_phoneCtrl.text);
    for (var c in _extraPhoneCtrls) {
      if (_hiddenPhones.contains(c) && c.text.isNotEmpty)
        hiddenPhones.add(c.text);
    }

    if (_hiddenWebsites.contains(_websiteCtrl) && _websiteCtrl.text.isNotEmpty)
      hiddenWebsites.add(_websiteCtrl.text);
    for (var c in _extraWebsiteCtrls) {
      if (_hiddenWebsites.contains(c) && c.text.isNotEmpty)
        hiddenWebsites.add(c.text);
    }

    if (_hiddenAddresses.contains(_addressCtrl) && _addressCtrl.text.isNotEmpty)
      hiddenAddresses.add(_addressCtrl.text);
    for (var c in _extraAddressCtrls) {
      if (_hiddenAddresses.contains(c) && c.text.isNotEmpty)
        hiddenAddresses.add(c.text);
    }

    try {
      final isNewBusiness =
          widget.isNew ||
          (widget.business == null && hc.businessId.value.isEmpty);
      final endpoint = isNewBusiness ? '/add-business' : '/update-business';

      final prefs = await SharedPreferences.getInstance();
      final userId =
          prefs.getString('userId') ??
          prefs.getString('user_id') ??
          prefs.getString('id') ??
          '';

      if (isNewBusiness && userId.isEmpty) {
        setState(() => _isLoading = false);
        Get.snackbar(
          'Session Error',
          'Your session data is missing. Please log out and log back in to save.',
          backgroundColor: Colors.orange,
          colorText: Colors.white,
          duration: const Duration(seconds: 4),
        );
        return;
      }

      dynamic response;

      if (_selectedImage != null) {
        final fields = <String, String>{
          if (isNewBusiness) 'userId': userId,
          if (!isNewBusiness) 'bussinessId': _businessId,
          'bussinessName': _nameCtrl.text,
          'bussinessEmail': _emailCtrl.text,
          'bussinessNumber': _phoneCtrl.text,
          'bussinessWebsite': _websiteCtrl.text,
          'bussinessAddress': _addressCtrl.text,
          'businessCategoryId': _selectedCategoryId,
          'businessSubCategoryIds': _selectedSubCategoryIds.join(','),
          'businessTypeIds': _selectedBusinessTypeIds.join(','),
          'product_ids': _selectedProductIds.join(','),
          'brand_primary_color': _brandPrimaryColorCtrl.text.trim(),
          'brand_secondary_color': _brandSecondaryColorCtrl.text.trim(),
          'extra_emails': jsonEncode(
            _extraEmailCtrls
                .map((c) => c.text)
                .where((t) => t.isNotEmpty)
                .toList(),
          ),
          'extra_mobile_numbers': jsonEncode(
            _extraPhoneCtrls
                .map((c) => c.text)
                .where((t) => t.isNotEmpty)
                .toList(),
          ),
          'extra_websites': jsonEncode(
            _extraWebsiteCtrls
                .map((c) => c.text)
                .where((t) => t.isNotEmpty)
                .toList(),
          ),
          'extra_addresses': jsonEncode(
            _extraAddressCtrls
                .map((c) => c.text)
                .where((t) => t.isNotEmpty)
                .toList(),
          ),
          'hidden_frame_fields': jsonEncode({
            'business_name': _hideBusinessName,
            'logo': _hideBusinessLogo,
            'emails': hiddenEmails,
            'mobile_numbers': hiddenPhones,
            'websites': hiddenWebsites,
            'addresses': hiddenAddresses,
          }),
        };

        response = await ApiService.multipartPost(
          endpoint,
          fields,
          fileKey: 'bussinessImage',
          filePath: _selectedImage!.path,
        );
      } else {
        response = await ApiService.post(endpoint, {
          if (isNewBusiness) 'userId': userId,
          if (!isNewBusiness) 'bussinessId': _businessId,
          'bussinessName': _nameCtrl.text,
          'bussinessEmail': _emailCtrl.text,
          'bussinessNumber': _phoneCtrl.text,
          'bussinessWebsite': _websiteCtrl.text,
          'bussinessAddress': _addressCtrl.text,
          'businessCategoryId': _selectedCategoryId,
          'businessSubCategoryIds': _selectedSubCategoryIds,
          'businessTypeIds': _selectedBusinessTypeIds,
          'product_ids': _selectedProductIds,
          'brand_primary_color': _brandPrimaryColorCtrl.text.trim(),
          'brand_secondary_color': _brandSecondaryColorCtrl.text.trim(),
          'extra_emails': _extraEmailCtrls
              .map((c) => c.text)
              .where((t) => t.isNotEmpty)
              .toList(),
          'extra_mobile_numbers': _extraPhoneCtrls
              .map((c) => c.text)
              .where((t) => t.isNotEmpty)
              .toList(),
          'extra_websites': _extraWebsiteCtrls
              .map((c) => c.text)
              .where((t) => t.isNotEmpty)
              .toList(),
          'extra_addresses': _extraAddressCtrls
              .map((c) => c.text)
              .where((t) => t.isNotEmpty)
              .toList(),
          'hidden_frame_fields': {
            'business_name': _hideBusinessName,
            'logo': _hideBusinessLogo,
            'emails': hiddenEmails,
            'mobile_numbers': hiddenPhones,
            'websites': hiddenWebsites,
            'addresses': hiddenAddresses,
          },
        });
      }

      if (response.statusCode == 200) {
        // Refresh businesses
        await hc.loadBusinessInfo();
        await hc.fetchHomeData();

        setState(() => _isLoading = false);

        if (widget.redirectRoute != null) {
          Get.offNamed(
            widget.redirectRoute!,
            arguments: widget.redirectArguments,
          );
        } else {
          Get.back(result: true); // Return true to indicate success
        }
        Get.snackbar(
          'Success',
          isNewBusiness
              ? 'Business created successfully'
              : 'Business updated successfully',
          snackPosition: SnackPosition.BOTTOM,
          backgroundColor: AppColors.success,
          colorText: Colors.white,
          margin: const EdgeInsets.all(16),
          duration: const Duration(seconds: 1),
        );
      } else {
        setState(() => _isLoading = false);
        String errorMsg = 'Failed to save business';
        try {
          final body = jsonDecode(response.body);
          if (body['message'] != null) {
            if (body['message'] is List) {
              errorMsg = (body['message'] as List).join('\n');
            } else {
              errorMsg = body['message'].toString();
            }
          }
        } catch (_) {}
        Get.snackbar(
          'Error',
          errorMsg,
          snackPosition: SnackPosition.BOTTOM,
          backgroundColor: Colors.red,
          colorText: Colors.white,
          margin: const EdgeInsets.all(16),
        );
      }
    } catch (e) {
      setState(() => _isLoading = false);
      Get.snackbar(
        'Error',
        'Network error: $e',
        snackPosition: SnackPosition.BOTTOM,
        backgroundColor: Colors.red,
        colorText: Colors.white,
        margin: const EdgeInsets.all(16),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF8FAFC),
      appBar: AppBar(
        title: Text(
          widget.isNew ? 'Create Business' : 'Edit Business',
          style: const TextStyle(
            fontWeight: FontWeight.w800,
            fontSize: 16,
            color: Color(0xFF172033),
          ),
        ),
        backgroundColor: const Color(0xFFF8FAFC),
        elevation: 0,
        centerTitle: true,
        leading: IconButton(
          icon: const Icon(Icons.arrow_back, color: Color(0xFF172033)),
          onPressed: () => Get.back(),
        ),
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.fromLTRB(20, 16, 20, 32),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            // Logo Upload
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(18),
                border: Border.all(color: const Color(0xFFEFF2F7)),
                boxShadow: [
                  BoxShadow(
                    color: Colors.black.withOpacity(0.035),
                    blurRadius: 12,
                    offset: const Offset(0, 5),
                  ),
                ],
              ),
              child: Center(
                child: Column(
                  children: [
                    const Text(
                      'Upload Business Logo',
                      style: TextStyle(
                        fontSize: 15,
                        fontWeight: FontWeight.w800,
                        color: Color(0xFF172033),
                      ),
                    ),
                    const SizedBox(height: 14),
                    Stack(
                      children: [
                        Container(
                          width: 108,
                          height: 108,
                          decoration: BoxDecoration(
                            color: Colors.white,
                            shape: BoxShape.circle,
                            border: Border.all(
                              color: const Color(0xFFE2E8F0),
                              width: 2,
                            ),
                            boxShadow: [
                              BoxShadow(
                                color: Colors.black.withOpacity(0.08),
                                blurRadius: 16,
                                offset: const Offset(0, 7),
                              ),
                            ],
                          ),
                          clipBehavior: Clip.antiAlias,
                          child: _selectedImage != null
                              ? Image.file(_selectedImage!, fit: BoxFit.cover)
                              : (_previewLogoUrl.isNotEmpty
                                    ? CachedNetworkImage(
                                        key: ValueKey(_previewLogoUrl),
                                        imageUrl: _previewLogoUrl,
                                        fit: BoxFit.cover,
                                        errorWidget: (_, __, ___) => Icon(
                                          Icons.storefront_outlined,
                                          size: 50,
                                          color: AppColors.gray400,
                                        ),
                                      )
                                    : Icon(
                                        Icons.storefront_outlined,
                                        size: 50,
                                        color: AppColors.gray400,
                                      )),
                        ),
                        Positioned(
                          bottom: 0,
                          right: 0,
                          child: GestureDetector(
                            onTap: _pickImage,
                            child: Container(
                              padding: const EdgeInsets.all(8),
                              decoration: BoxDecoration(
                                color: const Color(0xFF6434E8),
                                shape: BoxShape.circle,
                                border: Border.all(
                                  color: Colors.white,
                                  width: 3,
                                ),
                              ),
                              child: const Icon(
                                Icons.camera_alt,
                                color: Colors.white,
                                size: 20,
                              ),
                            ),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 8),
                    _buildHideInFrameToggle(
                      'Hide logo in frame',
                      _hideBusinessLogo,
                      (value) => setState(() => _hideBusinessLogo = value),
                    ),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 24),

            // Form Fields
            _buildInputField(
              'Business Name',
              Icons.storefront_outlined,
              _nameCtrl,
            ),
            _buildHideInFrameToggle(
              'Hide business name in frame',
              _hideBusinessName,
              (value) => setState(() => _hideBusinessName = value),
            ),
            AppSpacing.gapV16,
            CascadingBusinessDropdowns(
              key: ValueKey('cascade_$_cascadingKey'),
              initialCategoryId: _selectedCategoryId,
              initialSubCategoryIds: _selectedSubCategoryIds,
              initialBusinessTypeIds: _selectedBusinessTypeIds,
              onSelected: (categoryId, subCategoryIds, businessTypeIds, hasTypes) {
                // If category changed, cache old selections & try restore from cache
                if (categoryId != _selectedCategoryId &&
                    categoryId.isNotEmpty) {
                  // Save current category's selections to cache
                  if (_selectedCategoryId.isNotEmpty) {
                    _categoryCacheMap[_selectedCategoryId] = {
                      'subCategoryIds': List<String>.from(
                        _selectedSubCategoryIds,
                      ),
                      'businessTypeIds': List<String>.from(
                        _selectedBusinessTypeIds,
                      ),
                      'productIds': List<String>.from(_selectedProductIds),
                      'hasTypes': _hasTypesForSelectedSubCategory,
                    };
                  }

                  // Check if new category has cached selections
                  final cached = _categoryCacheMap[categoryId];
                  setState(() {
                    _selectedCategoryId = categoryId;
                    if (cached != null) {
                      _selectedSubCategoryIds = List<String>.from(
                        cached['subCategoryIds'] ?? [],
                      );
                      _selectedBusinessTypeIds = List<String>.from(
                        cached['businessTypeIds'] ?? [],
                      );
                      _selectedProductIds = List<String>.from(
                        cached['productIds'] ?? [],
                      );
                      _hasTypesForSelectedSubCategory =
                          cached['hasTypes'] ?? false;
                    } else {
                      _selectedSubCategoryIds = subCategoryIds;
                      _selectedBusinessTypeIds = businessTypeIds;
                      _selectedProductIds = [];
                      _hasTypesForSelectedSubCategory = hasTypes;
                    }
                    _cascadingKey++;
                    _productKey++;
                  });
                } else {
                  // Same category — normal update
                  setState(() {
                    _selectedCategoryId = categoryId;
                    _selectedSubCategoryIds = subCategoryIds;
                    _selectedBusinessTypeIds = businessTypeIds;
                    _hasTypesForSelectedSubCategory = hasTypes;
                    _selectedProductIds.clear();
                    _productKey++;
                  });
                }
              },
            ),
            AppSpacing.gapV16,
            MultiSelectDropdown(
              key: ValueKey('products_$_productKey'),
              title: 'Products / Services',
              initialSelectedIds: _selectedProductIds,
              initialSelectedNames: _initialProductNames,
              fetchItems: _fetchProducts,
              onChanged: (ids) => setState(() => _selectedProductIds = ids),
            ),
            AppSpacing.gapV16,
            _buildBusinessThemeFields(),
            AppSpacing.gapV16,
            Container(
              decoration: BoxDecoration(
                color: const Color(0xFFF5F3FF),
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: const Color(0xFFD8CCFF)),
              ),
              child: CheckboxListTile(
                title: const Text(
                  'Same as personal info (Email & Phone)',
                  style: TextStyle(
                    fontSize: 13,
                    fontWeight: FontWeight.w600,
                    color: Color(0xFF334155),
                  ),
                ),
                value: _sameAsPersonalInfo,
                onChanged: _toggleSameAsPersonal,
                controlAffinity: ListTileControlAffinity.leading,
                contentPadding: const EdgeInsets.symmetric(
                  horizontal: 8,
                  vertical: 0,
                ),
                activeColor: const Color(0xFF6434E8),
                dense: true,
              ),
            ),
            AppSpacing.gapV12,
            _buildDynamicInputFields(
              'Email Address',
              Icons.email_outlined,
              _emailCtrl,
              _extraEmailCtrls,
              _hiddenEmails,
              () {
                setState(() => _extraEmailCtrls.add(TextEditingController()));
              },
              keyboardType: TextInputType.emailAddress,
              primaryReadOnly: _sameAsPersonalInfo,
            ),
            AppSpacing.gapV16,
            _buildDynamicInputFields(
              'Phone Number',
              Icons.phone_outlined,
              _phoneCtrl,
              _extraPhoneCtrls,
              _hiddenPhones,
              () {
                setState(() => _extraPhoneCtrls.add(TextEditingController()));
              },
              keyboardType: TextInputType.phone,
              primaryReadOnly: _sameAsPersonalInfo,
            ),
            AppSpacing.gapV16,
            _buildDynamicInputFields(
              'Website',
              Icons.language,
              _websiteCtrl,
              _extraWebsiteCtrls,
              _hiddenWebsites,
              () {
                setState(() => _extraWebsiteCtrls.add(TextEditingController()));
              },
              keyboardType: TextInputType.url,
            ),
            AppSpacing.gapV16,
            _buildDynamicInputFields(
              'Address',
              Icons.location_on_outlined,
              _addressCtrl,
              _extraAddressCtrls,
              _hiddenAddresses,
              () {
                setState(() => _extraAddressCtrls.add(TextEditingController()));
              },
              maxLines: 3,
            ),

            AppSpacing.gapV32,

            // Save Button
            FilledButton(
              onPressed: _isLoading ? null : _saveProfile,
              style: FilledButton.styleFrom(
                backgroundColor: const Color(0xFF6434E8),
                foregroundColor: Colors.white,
                minimumSize: const Size.fromHeight(52),
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(14),
                ),
              ),
              child: _isLoading
                  ? const SizedBox(
                      width: 24,
                      height: 24,
                      child: CircularProgressIndicator(
                        color: Colors.white,
                        strokeWidth: 2,
                      ),
                    )
                  : const Text(
                      'Save Changes',
                      style: TextStyle(
                        fontWeight: FontWeight.w800,
                        fontSize: 15,
                      ),
                    ),
            ),
            const SizedBox(height: 40),
          ],
        ),
      ),
    );
  }

  bool _hasValidBusinessTheme() {
    final primary = _brandPrimaryColorCtrl.text.trim();
    final secondary = _brandSecondaryColorCtrl.text.trim();
    if (primary.isEmpty && secondary.isEmpty) return true;
    final hex = RegExp(r'^#[A-Fa-f0-9]{6}$');
    return hex.hasMatch(primary) && hex.hasMatch(secondary);
  }

  Widget _buildBusinessThemeFields() => Container(
    padding: const EdgeInsets.all(14),
    decoration: BoxDecoration(
      color: const Color(0xFFF7F5FF),
      borderRadius: BorderRadius.circular(12),
      border: Border.all(color: const Color(0xFFE2D9FF)),
    ),
    child: Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Row(
          children: [
            Icon(Icons.palette_outlined, color: Color(0xFF6434E8)),
            SizedBox(width: 8),
            Text(
              'Business theme colours (optional)',
              style: TextStyle(
                fontWeight: FontWeight.w800,
                color: Color(0xFF334155),
              ),
            ),
          ],
        ),
        const SizedBox(height: 5),
        const Text(
          'Save a primary and secondary colour to offer “My business theme” in Custom Post styles.',
          style: TextStyle(fontSize: 12, color: Color(0xFF64748B)),
        ),
        const SizedBox(height: 12),
        Row(
          children: [
            Expanded(
              child: TextField(
                controller: _brandPrimaryColorCtrl,
                maxLength: 7,
                textCapitalization: TextCapitalization.characters,
                decoration: InputDecoration(
                  hintText: 'Primary #6434E8',
                  counterText: '',
                  filled: true,
                  fillColor: Colors.white,
                  border: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(12),
                  ),
                ),
              ),
            ),
            const SizedBox(width: 10),
            Expanded(
              child: TextField(
                controller: _brandSecondaryColorCtrl,
                maxLength: 7,
                textCapitalization: TextCapitalization.characters,
                decoration: InputDecoration(
                  hintText: 'Secondary #172033',
                  counterText: '',
                  filled: true,
                  fillColor: Colors.white,
                  border: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(12),
                  ),
                ),
              ),
            ),
          ],
        ),
      ],
    ),
  );

  Widget _buildInputField(
    String label,
    IconData icon,
    TextEditingController controller, {
    TextInputType? keyboardType,
    int maxLines = 1,
  }) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Padding(
          padding: const EdgeInsets.only(left: 4, bottom: 8),
          child: Text(
            label,
            style: const TextStyle(
              fontWeight: FontWeight.w700,
              fontSize: 12,
              color: Color(0xFF334155),
            ),
          ),
        ),
        _buildSingleInput(
          icon,
          controller,
          keyboardType: keyboardType,
          maxLines: maxLines,
        ),
      ],
    );
  }

  Widget _buildHideInFrameToggle(
    String label,
    bool value,
    ValueChanged<bool> onChanged,
  ) {
    return InkWell(
      onTap: () => onChanged(!value),
      borderRadius: BorderRadius.circular(8),
      child: Padding(
        padding: const EdgeInsets.only(top: 6, left: 2),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Checkbox(
              value: value,
              activeColor: const Color(0xFF6434E8),
              onChanged: (checked) => onChanged(checked ?? false),
            ),
            Text(
              label,
              style: const TextStyle(
                fontSize: 12,
                color: Color(0xFF64748B),
                fontWeight: FontWeight.w600,
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildDynamicInputFields(
    String label,
    IconData icon,
    TextEditingController primaryCtrl,
    List<TextEditingController> extraCtrls,
    Set<TextEditingController> hiddenSet,
    VoidCallback onAdd, {
    TextInputType? keyboardType,
    int maxLines = 1,
    bool primaryReadOnly = false,
  }) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            Padding(
              padding: const EdgeInsets.only(left: 4, bottom: 8),
              child: Text(
                label,
                style: const TextStyle(
                  fontWeight: FontWeight.w700,
                  fontSize: 12,
                  color: Color(0xFF334155),
                ),
              ),
            ),
            if (extraCtrls.length < 5)
              GestureDetector(
                onTap: onAdd,
                child: Padding(
                  padding: const EdgeInsets.only(right: 4, bottom: 8),
                  child: const Icon(
                    Icons.add_circle_outline_rounded,
                    color: Color(0xFF6434E8),
                    size: 21,
                  ),
                ),
              ),
          ],
        ),
        _buildSingleInputWithHide(
          icon,
          primaryCtrl,
          hiddenSet,
          keyboardType: keyboardType,
          maxLines: maxLines,
          readOnly: primaryReadOnly,
        ),
        ...extraCtrls.asMap().entries.map((entry) {
          int idx = entry.key;
          TextEditingController ctrl = entry.value;
          return Padding(
            padding: const EdgeInsets.only(top: 12.0),
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Expanded(
                  child: _buildSingleInputWithHide(
                    icon,
                    ctrl,
                    hiddenSet,
                    keyboardType: keyboardType,
                    maxLines: maxLines,
                    readOnly: false,
                  ),
                ),
                IconButton(
                  icon: const Icon(
                    Icons.close_rounded,
                    color: Color(0xFF94A3B8),
                  ),
                  onPressed: () {
                    setState(() {
                      hiddenSet.remove(ctrl);
                      ctrl.dispose();
                      extraCtrls.removeAt(idx);
                    });
                  },
                ),
              ],
            ),
          );
        }),
      ],
    );
  }

  Widget _buildSingleInputWithHide(
    IconData icon,
    TextEditingController controller,
    Set<TextEditingController> hiddenSet, {
    TextInputType? keyboardType,
    int maxLines = 1,
    bool readOnly = false,
  }) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        _buildSingleInput(
          icon,
          controller,
          keyboardType: keyboardType,
          maxLines: maxLines,
          readOnly: readOnly,
        ),
        Row(
          children: [
            Checkbox(
              value: hiddenSet.contains(controller),
              onChanged: (val) {
                setState(() {
                  if (val == true) {
                    hiddenSet.add(controller);
                  } else {
                    hiddenSet.remove(controller);
                  }
                });
              },
              activeColor: const Color(0xFF6434E8),
            ),
            const Text(
              'Hide in frame',
              style: TextStyle(fontSize: 12, color: Color(0xFF64748B)),
            ),
          ],
        ),
      ],
    );
  }

  Widget _buildSingleInput(
    IconData icon,
    TextEditingController controller, {
    TextInputType? keyboardType,
    int maxLines = 1,
    bool readOnly = false,
  }) {
    return TextField(
      controller: controller,
      keyboardType: keyboardType,
      maxLines: maxLines,
      readOnly: readOnly,
      style: const TextStyle(
        fontSize: 14,
        color: Color(0xFF172033),
        fontWeight: FontWeight.w500,
      ),
      decoration: InputDecoration(
        prefixIcon: Padding(
          padding: EdgeInsets.only(
            left: 15,
            right: 11,
            bottom: maxLines > 1 ? (maxLines - 1) * 20.0 : 0,
          ),
          child: Icon(icon, color: const Color(0xFF94A3B8), size: 20),
        ),
        prefixIconConstraints: const BoxConstraints(minWidth: 0, minHeight: 0),
        contentPadding: const EdgeInsets.symmetric(
          horizontal: 15,
          vertical: 14,
        ),
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: const BorderSide(color: Color(0xFFE2E8F0)),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: const BorderSide(color: Color(0xFFE2E8F0)),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: const BorderSide(color: Color(0xFF6434E8), width: 1.5),
        ),
        filled: true,
        fillColor: readOnly ? const Color(0xFFF1F5F9) : const Color(0xFFFBFCFE),
      ),
    );
  }
}

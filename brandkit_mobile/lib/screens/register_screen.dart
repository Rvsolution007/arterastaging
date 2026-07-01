import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import '../controllers/auth_controller.dart';
import '../services/api_service.dart';
import '../utils/app_colors.dart';
import '../utils/app_spacing.dart';
import '../utils/app_text_styles.dart';
import '../widgets/multi_select_dropdown.dart';
import '../widgets/cascading_business_dropdowns.dart';
class RegisterScreen extends StatefulWidget {
  const RegisterScreen({super.key});

  @override
  State<RegisterScreen> createState() => _RegisterScreenState();
}

class _RegisterScreenState extends State<RegisterScreen> {
  final AuthController authController = Get.find<AuthController>();
  
  final TextEditingController nameController = TextEditingController();
  final TextEditingController emailController = TextEditingController();
  final TextEditingController phoneController = TextEditingController();
  final TextEditingController passwordController = TextEditingController();
  final TextEditingController referralController = TextEditingController();
  
  // Business fields
  final TextEditingController bizNameController = TextEditingController();
  
  List<dynamic> _categories = [];
  String _selectedCategoryId = '1';
  List<String> _selectedSubCategoryIds = [];
  List<String> _selectedBusinessTypeIds = [];
  bool _hasTypesForSelectedSubCategory = false;
  List<String> _selectedProductIds = [];
  
  // Per-category selection cache
  final Map<String, Map<String, dynamic>> _categoryCacheMap = {};
  int _cascadingKey = 0;
  int _productKey = 0;

  @override
  void initState() {
    super.initState();
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

  void _submit() {
    if (nameController.text.isEmpty || 
        emailController.text.isEmpty || 
        phoneController.text.isEmpty || 
        passwordController.text.isEmpty) {
      Get.snackbar('Error', 'Please fill all required personal fields', backgroundColor: Colors.redAccent, colorText: Colors.white);
      return;
    }

    if (bizNameController.text.isEmpty) {
      Get.snackbar('Error', 'Please provide a Business Name', backgroundColor: Colors.redAccent, colorText: Colors.white);
      return;
    }

    authController.register(
      name: nameController.text, 
      email: emailController.text, 
      phone: phoneController.text, 
      password: passwordController.text,
      referralCode: referralController.text,
      businessName: bizNameController.text,
      businessCategoryId: _selectedCategoryId,
      businessSubCategoryIds: _selectedSubCategoryIds,
      businessTypeIds: _selectedBusinessTypeIds,
      productIds: _selectedProductIds,
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        backgroundColor: Colors.transparent,
        elevation: 0,
        iconTheme: const IconThemeData(color: AppColors.textPrimary),
      ),
      body: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(24.0),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              const Text(
                'Create Account',
                textAlign: TextAlign.center,
                style: TextStyle(fontSize: 28, fontWeight: FontWeight.bold, color: AppColors.textPrimary),
              ),
              const SizedBox(height: 10),
              const Text(
                'Join Artera to create stunning posts',
                textAlign: TextAlign.center,
                style: TextStyle(fontSize: 16, color: AppColors.textSecondary),
              ),
              const SizedBox(height: 40),

              // Personal Details Section
              const Text('Personal Details', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: AppColors.textPrimary)),
              const SizedBox(height: 16),
              TextField(
                controller: nameController,
                decoration: InputDecoration(
                  labelText: 'Full Name *',
                  prefixIcon: const Icon(Icons.person_outline),
                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                ),
              ),
              const SizedBox(height: 16),
              TextField(
                controller: emailController,
                decoration: InputDecoration(
                  labelText: 'Email Address *',
                  prefixIcon: const Icon(Icons.email_outlined),
                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                ),
                keyboardType: TextInputType.emailAddress,
              ),
              const SizedBox(height: 16),
              TextField(
                controller: phoneController,
                decoration: InputDecoration(
                  labelText: 'Mobile Number *',
                  prefixIcon: const Icon(Icons.phone_outlined),
                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                ),
                keyboardType: TextInputType.phone,
              ),
              const SizedBox(height: 16),
              TextField(
                controller: passwordController,
                decoration: InputDecoration(
                  labelText: 'Password *',
                  prefixIcon: const Icon(Icons.lock_outline),
                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                ),
                obscureText: true,
              ),
              const SizedBox(height: 16),
              TextField(
                controller: referralController,
                decoration: InputDecoration(
                  labelText: 'Referral Code (Optional)',
                  prefixIcon: const Icon(Icons.card_giftcard),
                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                ),
              ),
              
              const SizedBox(height: 32),
              
              // Business Details Section
              const Text('Business Details', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: AppColors.textPrimary)),
              const SizedBox(height: 16),
              TextField(
                controller: bizNameController,
                decoration: InputDecoration(
                  labelText: 'Business Name *',
                  prefixIcon: const Icon(Icons.business),
                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                ),
              ),
              const SizedBox(height: 16),
              
              CascadingBusinessDropdowns(
                key: ValueKey('reg_cascade_$_cascadingKey'),
                initialCategoryId: _selectedCategoryId,
                initialSubCategoryIds: _selectedSubCategoryIds,
                initialBusinessTypeIds: _selectedBusinessTypeIds,
                onSelected: (categoryId, subCategoryIds, businessTypeIds, hasTypes) {
                  if (categoryId != _selectedCategoryId && categoryId.isNotEmpty) {
                    if (_selectedCategoryId.isNotEmpty) {
                      _categoryCacheMap[_selectedCategoryId] = {
                        'subCategoryIds': List<String>.from(_selectedSubCategoryIds),
                        'businessTypeIds': List<String>.from(_selectedBusinessTypeIds),
                        'productIds': List<String>.from(_selectedProductIds),
                        'hasTypes': _hasTypesForSelectedSubCategory,
                      };
                    }
                    final cached = _categoryCacheMap[categoryId];
                    setState(() {
                      _selectedCategoryId = categoryId;
                      if (cached != null) {
                        _selectedSubCategoryIds = List<String>.from(cached['subCategoryIds'] ?? []);
                        _selectedBusinessTypeIds = List<String>.from(cached['businessTypeIds'] ?? []);
                        _selectedProductIds = List<String>.from(cached['productIds'] ?? []);
                        _hasTypesForSelectedSubCategory = cached['hasTypes'] ?? false;
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
              
              const SizedBox(height: 16),
              MultiSelectDropdown(
                key: ValueKey('reg_products_$_productKey'),
                title: 'Products / Services (Optional)',
                initialSelectedIds: _selectedProductIds,
                fetchItems: _fetchProducts,
                onChanged: (ids) => setState(() => _selectedProductIds = ids),
              ),

              const SizedBox(height: 32),
              Obx(() => ElevatedButton(
                onPressed: authController.isLoading.value ? null : _submit,
                style: ElevatedButton.styleFrom(
                  backgroundColor: AppColors.primary,
                  padding: const EdgeInsets.symmetric(vertical: 16),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                ),
                child: authController.isLoading.value
                    ? const CircularProgressIndicator(color: Colors.white)
                    : const Text('Sign Up', style: TextStyle(fontSize: 18, color: Colors.white)),
              )),
              const SizedBox(height: 20),
              TextButton(
                onPressed: () {
                  Get.back();
                },
                child: const Text('Already have an account? Login', style: TextStyle(color: AppColors.primary)),
              )
            ],
          ),
        ),
      ),
    );
  }

}

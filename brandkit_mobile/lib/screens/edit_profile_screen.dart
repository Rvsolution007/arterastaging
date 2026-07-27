import 'dart:io';
import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:image_picker/image_picker.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../services/api_service.dart';
import '../services/secure_token_store.dart';
import '../utils/app_colors.dart';
import '../utils/app_spacing.dart';
import '../utils/app_text_styles.dart';
import '../controllers/home_controller.dart';

class EditProfileScreen extends StatefulWidget {
  const EditProfileScreen({Key? key}) : super(key: key);

  @override
  State<EditProfileScreen> createState() => _EditProfileScreenState();
}

class _EditProfileScreenState extends State<EditProfileScreen> {
  final _formKey = GlobalKey<FormState>();
  
  final _nameCtrl = TextEditingController();
  final _emailCtrl = TextEditingController();
  final _phoneCtrl = TextEditingController();
  final _currentPasswordCtrl = TextEditingController();
  final _passwordCtrl = TextEditingController();

  bool _isLoading = false;
  File? _selectedImage;
  String? _currentImageUrl;
  String? _userId;

  @override
  void initState() {
    super.initState();
    _loadUserData();
  }

  Future<void> _loadUserData() async {
    final prefs = await SharedPreferences.getInstance();
    setState(() {
      _userId = prefs.getString('userId');
      _nameCtrl.text = prefs.getString('userName') ?? '';
      _emailCtrl.text = prefs.getString('emailId') ?? '';
      _phoneCtrl.text = prefs.getString('phoneNumber') ?? '';
      _currentImageUrl = prefs.getString('profileImage');
    });
  }

  Future<void> _pickImage() async {
    final picker = ImagePicker();
    final pickedFile = await picker.pickImage(source: ImageSource.gallery);
    if (pickedFile != null) {
      setState(() {
        _selectedImage = File(pickedFile.path);
      });
    }
  }

  Future<void> _saveProfile() async {
    if (!_formKey.currentState!.validate()) return;

    if (_passwordCtrl.text.isNotEmpty && _currentPasswordCtrl.text.isEmpty) {
      Get.snackbar('Current password required', 'Enter your current password to set a new password.',
          backgroundColor: Colors.red, colorText: Colors.white);
      return;
    }
    if (_passwordCtrl.text.isNotEmpty && !_isStrongPassword(_passwordCtrl.text)) {
      Get.snackbar('Choose a stronger password', 'Use 10+ characters with uppercase, lowercase, number and symbol.',
          backgroundColor: Colors.red, colorText: Colors.white);
      return;
    }

    setState(() => _isLoading = true);

    try {
      Map<String, String> fields = {
        'id': _userId ?? '',
        'name': _nameCtrl.text,
        'email': _emailCtrl.text,
        'mobile_no': _phoneCtrl.text,
      };

      var response;
      if (_selectedImage != null) {
        response = await ApiService.multipartPost(
          '/profile-update',
          fields,
          fileKey: 'image',
          filePath: _selectedImage!.path,
        );
      } else {
        response = await ApiService.post('/profile-update', fields);
      }

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['status'] == 'Success') {
          var passwordChanged = false;
          if (_passwordCtrl.text.isNotEmpty) {
            final passRes = await ApiService.post('/change-password', {
              'currentPassword': _currentPasswordCtrl.text,
              'newPassword': _passwordCtrl.text,
            });
            if (passRes.statusCode != 200) {
               Get.snackbar('Error', 'Profile updated but password change failed', 
                backgroundColor: Colors.red, colorText: Colors.white);
                setState(() => _isLoading = false);
                return;
            }
            passwordChanged = true;
          }

          final prefs = await SharedPreferences.getInstance();
          await prefs.setString('userName', _nameCtrl.text);
          await prefs.setString('emailId', _emailCtrl.text);
          await prefs.setString('phoneNumber', _phoneCtrl.text);

          if (data['data'] != null && data['data']['profileImage'] != null) {
            await prefs.setString('profileImage', data['data']['profileImage']);
          }

          // Reload business info so changes immediately reflect across the app
          if (Get.isRegistered<HomeController>()) {
            await Get.find<HomeController>().loadBusinessInfo();
          }

          if (passwordChanged) {
            await SecureTokenStore.clear();
            await prefs.clear();
            Get.offAllNamed('/LoginScreen');
            Get.snackbar('Password updated', 'Please sign in again with your new password.',
                backgroundColor: Colors.green, colorText: Colors.white);
            return;
          }

          Get.back();
          Get.snackbar('Success', 'Profile updated successfully',
              backgroundColor: Colors.green, colorText: Colors.white);
        } else {
          Get.snackbar('Error', data['message'] ?? 'Failed to update profile',
              backgroundColor: Colors.red, colorText: Colors.white);
        }
      } else {
        Get.snackbar('Error', 'Server error. Please try again.',
            backgroundColor: Colors.red, colorText: Colors.white);
      }
    } catch (e) {
      Get.snackbar('Error', 'An error occurred: $e',
          backgroundColor: Colors.red, colorText: Colors.white);
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  bool _isStrongPassword(String value) {
    return value.length >= 10 &&
        RegExp(r'[a-z]').hasMatch(value) &&
        RegExp(r'[A-Z]').hasMatch(value) &&
        RegExp(r'[0-9]').hasMatch(value) &&
        RegExp(r'[^A-Za-z0-9]').hasMatch(value);
  }

  Widget _buildTextField({
    required TextEditingController controller,
    required String label,
    required String hint,
    required IconData prefixIcon,
    bool obscureText = false,
    TextInputType? keyboardType,
    String? Function(String?)? validator,
  }) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(label, style: AppTextStyles.bodyMedium.copyWith(fontWeight: FontWeight.bold)),
        const SizedBox(height: 8),
        Container(
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(16),
            border: Border.all(color: AppColors.gray200, width: 1),
          ),
          child: TextFormField(
            controller: controller,
            obscureText: obscureText,
            keyboardType: keyboardType,
            validator: validator,
            decoration: InputDecoration(
              hintText: hint,
              hintStyle: const TextStyle(color: AppColors.gray400),
              prefixIcon: Icon(prefixIcon, color: AppColors.gray400),
              border: InputBorder.none,
              contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 16),
            ),
          ),
        ),
      ],
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        title: Text('Edit Profile', style: AppTextStyles.heading2),
        backgroundColor: Colors.white,
        elevation: 0,
        iconTheme: const IconThemeData(color: AppColors.textPrimary),
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(24.0),
        child: Form(
          key: _formKey,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.center,
            children: [
              Center(
                child: GestureDetector(
                  onTap: _pickImage,
                  child: Stack(
                    children: [
                      Container(
                        width: 100,
                        height: 100,
                        decoration: BoxDecoration(
                          color: Colors.white,
                          shape: BoxShape.circle,
                          border: Border.all(color: AppColors.gray200, width: 1),
                        ),
                        child: _selectedImage != null
                            ? ClipOval(child: Image.file(_selectedImage!, fit: BoxFit.cover))
                            : (_currentImageUrl != null && _currentImageUrl!.isNotEmpty)
                                ? ClipOval(child: Image.network(_currentImageUrl!, fit: BoxFit.cover))
                                : const Icon(Icons.person, size: 50, color: AppColors.textSecondary),
                      ),
                      Positioned(
                        bottom: 0,
                        right: 0,
                        child: Container(
                          padding: const EdgeInsets.all(6),
                          decoration: const BoxDecoration(
                            color: AppColors.primary,
                            shape: BoxShape.circle,
                          ),
                          child: const Icon(Icons.camera_alt, color: Colors.white, size: 16),
                        ),
                      ),
                    ],
                  ),
                ),
              ),
              const SizedBox(height: 32),
              
              _buildTextField(
                controller: _nameCtrl,
                label: 'Full Name',
                hint: 'Enter your full name',
                prefixIcon: Icons.person_outline,
                validator: (val) => val == null || val.isEmpty ? 'Required' : null,
              ),
              const SizedBox(height: 16),
              
              _buildTextField(
                controller: _emailCtrl,
                label: 'Email Address',
                hint: 'Enter your email',
                prefixIcon: Icons.email_outlined,
                keyboardType: TextInputType.emailAddress,
                validator: (val) => val == null || val.isEmpty ? 'Required' : null,
              ),
              const SizedBox(height: 16),
              
              _buildTextField(
                controller: _phoneCtrl,
                label: 'Mobile Number',
                hint: 'Enter your mobile number',
                prefixIcon: Icons.phone_outlined,
                keyboardType: TextInputType.phone,
                validator: (val) => val == null || val.isEmpty ? 'Required' : null,
              ),
              const SizedBox(height: 16),
              
              _buildTextField(
                controller: _currentPasswordCtrl,
                label: 'Current Password',
                hint: 'Required only to change password',
                prefixIcon: Icons.lock_outline,
                obscureText: true,
              ),
              const SizedBox(height: 16),

              _buildTextField(
                controller: _passwordCtrl,
                label: 'New Password (Optional)',
                hint: '10+ chars, uppercase, number & symbol',
                prefixIcon: Icons.lock_outline,
                obscureText: true,
              ),
              const SizedBox(height: 32),
              
              SizedBox(
                width: double.infinity,
                child: ElevatedButton(
                  onPressed: _isLoading ? null : _saveProfile,
                  style: ElevatedButton.styleFrom(
                    backgroundColor: AppColors.primary,
                    padding: const EdgeInsets.symmetric(vertical: 16),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                  ),
                  child: _isLoading 
                      ? const SizedBox(width: 24, height: 24, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                      : const Text('Save Changes', style: TextStyle(color: Colors.white, fontSize: 16, fontWeight: FontWeight.bold)),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  @override
  void dispose() {
    _nameCtrl.dispose();
    _emailCtrl.dispose();
    _phoneCtrl.dispose();
    _currentPasswordCtrl.dispose();
    _passwordCtrl.dispose();
    super.dispose();
  }
}

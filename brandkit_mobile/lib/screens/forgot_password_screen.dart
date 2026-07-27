import 'package:flutter/material.dart';
import 'package:get/get.dart';
import '../controllers/auth_controller.dart';
import '../utils/app_colors.dart';

class ForgotPasswordScreen extends StatefulWidget {
  const ForgotPasswordScreen({super.key});

  @override
  State<ForgotPasswordScreen> createState() => _ForgotPasswordScreenState();
}

class _ForgotPasswordScreenState extends State<ForgotPasswordScreen> {
  final AuthController authController = Get.find<AuthController>();
  final TextEditingController emailController = TextEditingController();
  final TextEditingController otpController = TextEditingController();
  final TextEditingController newPasswordController = TextEditingController();
  final TextEditingController confirmPasswordController = TextEditingController();
  
  int _step = 0; // 0: Email, 1: OTP, 2: New Password

  bool _obscureNew = true;
  bool _obscureConfirm = true;

  @override
  void dispose() {
    emailController.dispose();
    otpController.dispose();
    newPasswordController.dispose();
    confirmPasswordController.dispose();
    super.dispose();
  }

  void _submitEmail() async {
    if (emailController.text.isNotEmpty) {
      bool success = await authController.forgotPassword(emailController.text);
      if (success) {
        setState(() {
          _step = 1;
        });
      }
    } else {
      Get.snackbar('Error', 'Please enter your email', backgroundColor: Colors.redAccent, colorText: Colors.white);
    }
  }

  void _submitOtp() async {
    if (otpController.text.length == 6) {
      bool success = await authController.verifyOtp(emailController.text, otpController.text);
      if (success) {
        setState(() {
          _step = 2;
        });
      }
    } else {
      Get.snackbar('Error', 'Please enter a valid 6-digit OTP', backgroundColor: Colors.redAccent, colorText: Colors.white);
    }
  }

  void _submitNewPassword() async {
    if (!_isStrongPassword(newPasswordController.text)) {
      Get.snackbar('Error', 'Use 10+ characters with uppercase, lowercase, number and symbol', backgroundColor: Colors.redAccent, colorText: Colors.white);
      return;
    }
    if (newPasswordController.text != confirmPasswordController.text) {
      Get.snackbar('Error', 'Passwords do not match', backgroundColor: Colors.redAccent, colorText: Colors.white);
      return;
    }
    
    bool success = await authController.updatePassword(emailController.text, otpController.text, newPasswordController.text);
    if (success) {
      Get.offAllNamed('/LoginScreen'); // Back to login on success
    }
  }

  bool _isStrongPassword(String value) {
    return value.length >= 10 &&
        RegExp(r'[a-z]').hasMatch(value) &&
        RegExp(r'[A-Z]').hasMatch(value) &&
        RegExp(r'[0-9]').hasMatch(value) &&
        RegExp(r'[^A-Za-z0-9]').hasMatch(value);
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
              Text(
                _step == 0 ? 'Forgot Password' : _step == 1 ? 'Verify OTP' : 'Reset Password',
                textAlign: TextAlign.center,
                style: const TextStyle(fontSize: 28, fontWeight: FontWeight.bold, color: AppColors.textPrimary),
              ),
              const SizedBox(height: 10),
              Text(
                _step == 0 
                  ? 'Enter your email address to receive a 6-digit reset code.' 
                  : _step == 1 
                    ? 'Enter the 6-digit code sent to ${emailController.text}'
                    : 'Create a new password for your account.',
                textAlign: TextAlign.center,
                style: const TextStyle(fontSize: 16, color: AppColors.textSecondary),
              ),
              const SizedBox(height: 40),
              
              if (_step == 0) ...[
                TextField(
                  controller: emailController,
                  decoration: InputDecoration(
                    labelText: 'Email Address',
                    prefixIcon: const Icon(Icons.email_outlined),
                    border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                  ),
                  keyboardType: TextInputType.emailAddress,
                ),
                const SizedBox(height: 30),
                Obx(() => ElevatedButton(
                  onPressed: authController.isLoading.value ? null : _submitEmail,
                  style: ElevatedButton.styleFrom(
                    backgroundColor: AppColors.primary,
                    padding: const EdgeInsets.symmetric(vertical: 16),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                  ),
                  child: authController.isLoading.value
                      ? const CircularProgressIndicator(color: Colors.white)
                      : const Text('Send Reset Code', style: TextStyle(fontSize: 18, color: Colors.white)),
                )),
              ] 
              else if (_step == 1) ...[
                TextField(
                  controller: otpController,
                  decoration: InputDecoration(
                    labelText: '6-Digit OTP',
                    prefixIcon: const Icon(Icons.lock_clock),
                    border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                  ),
                  keyboardType: TextInputType.number,
                  maxLength: 6,
                  textAlign: TextAlign.center,
                  style: const TextStyle(fontSize: 24, letterSpacing: 8, fontWeight: FontWeight.bold),
                ),
                const SizedBox(height: 10),
                Obx(() => ElevatedButton(
                  onPressed: authController.isLoading.value ? null : _submitOtp,
                  style: ElevatedButton.styleFrom(
                    backgroundColor: AppColors.primary,
                    padding: const EdgeInsets.symmetric(vertical: 16),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                  ),
                  child: authController.isLoading.value
                      ? const CircularProgressIndicator(color: Colors.white)
                      : const Text('Verify Code', style: TextStyle(fontSize: 18, color: Colors.white)),
                )),
              ] 
              else if (_step == 2) ...[
                TextField(
                  controller: newPasswordController,
                  decoration: InputDecoration(
                    labelText: 'New Password',
                    prefixIcon: const Icon(Icons.lock_outline),
                    suffixIcon: IconButton(
                      icon: Icon(_obscureNew ? Icons.visibility_off : Icons.visibility),
                      onPressed: () => setState(() => _obscureNew = !_obscureNew),
                    ),
                    border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                  ),
                  obscureText: _obscureNew,
                ),
                const SizedBox(height: 20),
                TextField(
                  controller: confirmPasswordController,
                  decoration: InputDecoration(
                    labelText: 'Re-enter New Password',
                    prefixIcon: const Icon(Icons.lock_outline),
                    suffixIcon: IconButton(
                      icon: Icon(_obscureConfirm ? Icons.visibility_off : Icons.visibility),
                      onPressed: () => setState(() => _obscureConfirm = !_obscureConfirm),
                    ),
                    border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                  ),
                  obscureText: _obscureConfirm,
                ),
                const SizedBox(height: 30),
                Obx(() => ElevatedButton(
                  onPressed: authController.isLoading.value ? null : _submitNewPassword,
                  style: ElevatedButton.styleFrom(
                    backgroundColor: AppColors.primary,
                    padding: const EdgeInsets.symmetric(vertical: 16),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                  ),
                  child: authController.isLoading.value
                      ? const CircularProgressIndicator(color: Colors.white)
                      : const Text('Save New Password', style: TextStyle(fontSize: 18, color: Colors.white)),
                )),
              ],
            ],
          ),
        ),
      ),
    );
  }
}

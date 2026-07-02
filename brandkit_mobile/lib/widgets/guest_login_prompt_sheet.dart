import 'package:flutter/material.dart';
import 'package:get/get.dart';
import '../screens/login_screen.dart';
import '../screens/register_screen.dart';
import '../utils/app_colors.dart';

class GuestLoginPromptSheet extends StatelessWidget {
  final String redirectRoute;
  final dynamic redirectArguments;

  const GuestLoginPromptSheet({
    super.key,
    required this.redirectRoute,
    this.redirectArguments,
  });



  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: const BoxDecoration(
        color: AppColors.background,
        borderRadius: BorderRadius.only(
          topLeft: Radius.circular(24),
          topRight: Radius.circular(24),
        ),
      ),
      padding: const EdgeInsets.all(24),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          // Drag Handle
          Center(
            child: Container(
              width: 40,
              height: 4,
              decoration: BoxDecoration(
                color: AppColors.gray300,
                borderRadius: BorderRadius.circular(2),
              ),
            ),
          ),
          const SizedBox(height: 32),
          
          // Icon
          Container(
            width: 80,
            height: 80,
            decoration: BoxDecoration(
              color: AppColors.primary.withValues(alpha: 0.1),
              shape: BoxShape.circle,
            ),
            child: const Icon(
              Icons.lock_person_rounded,
              size: 40,
              color: AppColors.primary,
            ),
          ),
          const SizedBox(height: 24),
          
          // Title
          const Text(
            'Login Required',
            textAlign: TextAlign.center,
            style: TextStyle(
              fontSize: 24,
              fontWeight: FontWeight.bold,
              color: AppColors.textPrimary,
            ),
          ),
          const SizedBox(height: 12),
          
          // Description
          const Text(
            'You need an account to create, edit, and save designs. Join us to unlock your creative potential!',
            textAlign: TextAlign.center,
            style: TextStyle(
              fontSize: 16,
              color: AppColors.textSecondary,
              height: 1.5,
            ),
          ),
          const SizedBox(height: 32),
          
          // Login Button
          ElevatedButton(
            onPressed: () {
              Get.back(); // Close sheet
              Get.to(() => LoginScreen(
                redirectRoute: redirectRoute,
                redirectArguments: redirectArguments,
              ));
            },
            style: ElevatedButton.styleFrom(
              backgroundColor: AppColors.primary,
              padding: const EdgeInsets.symmetric(vertical: 16),
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(12),
              ),
              elevation: 0,
            ),
            child: const Text(
              'Log In',
              style: TextStyle(
                fontSize: 16,
                fontWeight: FontWeight.bold,
                color: Colors.white,
              ),
            ),
          ),
          const SizedBox(height: 12),
          
          // Register Button
          OutlinedButton(
            onPressed: () {
              Get.back(); // Close sheet
              Get.to(() => RegisterScreen(
                redirectRoute: redirectRoute,
                redirectArguments: redirectArguments,
              ));
            },
            style: OutlinedButton.styleFrom(
              padding: const EdgeInsets.symmetric(vertical: 16),
              side: const BorderSide(color: AppColors.primary, width: 2),
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(12),
              ),
            ),
            child: const Text(
              'Create an Account',
              style: TextStyle(
                fontSize: 16,
                fontWeight: FontWeight.bold,
                color: AppColors.primary,
              ),
            ),
          ),
          const SizedBox(height: 16),
          
          // Maybe Later Button
          TextButton(
            onPressed: () => Get.back(),
            child: const Text(
              'Maybe Later',
              style: TextStyle(
                fontSize: 14,
                color: AppColors.textSecondary,
              ),
            ),
          ),
          const SizedBox(height: 16), // SafeArea padding
        ],
      ),
    );
  }
}

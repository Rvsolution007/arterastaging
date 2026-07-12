import 'package:flutter/material.dart';
import 'package:get/get.dart';
import '../utils/app_colors.dart';
import '../utils/app_text_styles.dart';
import '../screens/business_profile_screen.dart';

class CreateBusinessPromptSheet extends StatelessWidget {
  final String redirectRoute;
  final dynamic redirectArguments;

  const CreateBusinessPromptSheet({
    Key? key,
    required this.redirectRoute,
    this.redirectArguments,
  }) : super(key: key);

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 32),
      decoration: const BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.center,
        children: [
          Container(
            width: 80,
            height: 80,
            decoration: BoxDecoration(
              color: AppColors.primary.withOpacity(0.1),
              shape: BoxShape.circle,
            ),
            child: const Icon(Icons.business_center_rounded, size: 40, color: AppColors.primary),
          ),
          const SizedBox(height: 24),
          const Text(
            'Create Your Business',
            style: TextStyle(
              fontSize: 24,
              fontWeight: FontWeight.bold,
              color: AppColors.textPrimary,
            ),
            textAlign: TextAlign.center,
          ),
          const SizedBox(height: 12),
          const Text(
            'You need to add business details (like logo and contact info) before you can start designing posts.',
            style: TextStyle(
              fontSize: 16,
              color: AppColors.textSecondary,
              height: 1.4,
            ),
            textAlign: TextAlign.center,
          ),
          const SizedBox(height: 32),
          SizedBox(
            width: double.infinity,
            child: ElevatedButton(
              onPressed: () {
                Get.back(); // Close sheet
                Get.to(() => BusinessProfileScreen(
                  isNew: true, 
                  redirectRoute: redirectRoute, 
                  redirectArguments: redirectArguments
                ));
              },
              style: ElevatedButton.styleFrom(
                backgroundColor: AppColors.primary,
                padding: const EdgeInsets.symmetric(vertical: 16),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
              ),
              child: const Text('Add Business Details', style: TextStyle(color: Colors.white, fontSize: 16, fontWeight: FontWeight.bold)),
            ),
          ),
          const SizedBox(height: 16),
          TextButton(
            onPressed: () => Get.back(),
            child: const Text('Cancel', style: TextStyle(color: AppColors.textSecondary)),
          ),
        ],
      ),
    );
  }
}

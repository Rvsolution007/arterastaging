import 'package:brandkit_mobile/utils/string_extensions.dart';
import '../config/app_config.dart';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import '../controllers/auth_controller.dart';
import 'package:cached_network_image/cached_network_image.dart';
import '../utils/app_colors.dart';
import '../utils/app_text_styles.dart';
import '../utils/app_spacing.dart';
import '../controllers/home_controller.dart';
import '../widgets/common/settings_item.dart';
import 'ai_setup_wizard_screen.dart';
import 'products_screen.dart';
import 'business_profile_screen.dart';
import 'products_screen.dart';
import 'business_profile_screen.dart';
import 'catalogue_columns_screen.dart';
import 'frames_screen.dart';
import 'business_list_screen.dart';
import 'downloads_screen.dart';
import 'edit_profile_screen.dart';

class MyBusinessScreen extends StatelessWidget {
  const MyBusinessScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final HomeController hc = Get.find<HomeController>();

    return SafeArea(
      child: Scaffold(
        backgroundColor: Colors.white,
        body: SingleChildScrollView(
          physics: const AlwaysScrollableScrollPhysics(),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              _buildProfileHeader(context, hc),
              _buildActionGrid(context),
              const SizedBox(height: 100),
            ],
          ),
        ),
      ),
    );
  }

  // ── 1. Profile Header ──
  Widget _buildProfileHeader(BuildContext context, HomeController hc) {
    return Container(
      decoration: const BoxDecoration(
        gradient: LinearGradient(
          colors: [Color(0xFFE0F2FE), Colors.white],
          begin: Alignment.topCenter,
          end: Alignment.bottomCenter,
        ),
      ),
      padding: const EdgeInsets.fromLTRB(24, 40, 24, 32),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Expanded(
            child: Row(
              children: [
                Obx(() {
                  return CircleAvatar(
                    radius: 36,
                    backgroundColor: AppColors.slate100,
                    child: hc.userProfileImage.value.isNotEmpty
                        ? CachedNetworkImage(
                            imageUrl: hc.userProfileImage.value.startsWith('http') ? hc.userProfileImage.value : '${hc.uploadsBaseUrl}/${hc.userProfileImage.value}',
                            imageBuilder: (context, imageProvider) => Container(
                              decoration: BoxDecoration(
                                shape: BoxShape.circle,
                                image: DecorationImage(image: imageProvider, fit: BoxFit.cover),
                              ),
                            ),
                            placeholder: (_, __) => _buildPlaceholderIcon(),
                            errorWidget: (_, __, ___) => _buildPlaceholderIcon(),
                          )
                        : _buildPlaceholderIcon(),
                  );
                }),
                AppSpacing.gapH20,
                Expanded(
                  child: Obx(() => Text(
                    hc.userName.value.isNotEmpty ? hc.userName.value : 'User Name',
                    style: AppTextStyles.heading2,
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                  )),
                ),
              ],
            ),
          ),
          const SizedBox(width: 12),
          Column(
            mainAxisAlignment: MainAxisAlignment.center,
            crossAxisAlignment: CrossAxisAlignment.end,
            children: [
              GestureDetector(
                onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const EditProfileScreen())),
                child: Container(
                  padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                  decoration: BoxDecoration(
                    color: AppColors.slate50,
                    borderRadius: BorderRadius.circular(8),
                    border: Border.all(color: AppColors.slate200),
                  ),
                  child: const Text(
                    'Edit Profile',
                    style: TextStyle(color: AppColors.slate600, fontWeight: FontWeight.w600, fontSize: 12),
                  ),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildPlaceholderIcon() {
    return Center(
      child: Icon(Icons.person_outline, color: AppColors.slate400, size: 36),
    );
  }

  // ── 2. Action Grid ──
  Widget _buildActionGrid(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 8),
      child: Wrap(
        spacing: 16,
        runSpacing: 16,
        children: [
          if (AppConfig.isLocal)
            _buildActionCard(
              title: 'ai_setup'.trFormat,
              icon: Icons.smart_toy_outlined,
              iconBg: AppColors.primary,
              iconColor: Colors.white,
              isGradient: true,
              onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const AiSetupWizardScreen())),
            ),
          _buildActionCard(
            title: 'products'.trFormat,
            icon: Icons.inventory_2_outlined,
            iconBg: AppColors.slate100,
            iconColor: AppColors.slate600,
            onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const ProductsScreen())),
          ),
          _buildActionCard(
            title: 'catalogue_setting'.trFormat,
            icon: Icons.list_alt_outlined,
            iconBg: AppColors.slate100,
            iconColor: AppColors.slate600,
            onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const CatalogueColumnsScreen())),
          ),
          _buildActionCard(
            title: 'frames'.trFormat,
            icon: Icons.filter_frames_outlined,
            iconBg: AppColors.slate100,
            iconColor: AppColors.slate600,
            onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const FramesScreen())),
          ),
          _buildActionCard(
            title: 'my_businesses'.trFormat,
            icon: Icons.business_center_outlined,
            iconBg: AppColors.indigo50,
            iconColor: AppColors.indigo500,
            onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const BusinessListScreen())),
          ),
          _buildActionCard(
            title: 'downloads'.trFormat,
            icon: Icons.download_outlined,
            iconBg: AppColors.indigo50,
            iconColor: AppColors.indigo500,
            onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const DownloadsScreen())),
          ),
        ],
      ),
    );
  }

  Widget _buildActionCard({
    required String title,
    required IconData icon,
    required Color iconBg,
    required Color iconColor,
    bool isGradient = false,
    bool isFullWidth = false,
    required VoidCallback onTap,
  }) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        width: isFullWidth ? double.infinity : (Get.width - 48 - 16) / 2, // Accounting for padding and spacing
        height: 145, // Fixed height to prevent uneven grids
        padding: const EdgeInsets.all(24),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(24),
          border: Border.all(color: AppColors.slate50),
          boxShadow: AppColors.cardShadow,
        ),
        child: Column(
          children: [
            Container(
              width: 44,
              height: 44,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                color: isGradient ? null : iconBg,
                gradient: isGradient ? AppColors.gradientPurple : null,
                boxShadow: isGradient ? AppColors.primaryShadow : null,
              ),
              child: Icon(icon, color: iconColor, size: 24),
            ),
            AppSpacing.gapV16,
            Text(
              title,
              style: TextStyle(
                fontWeight: FontWeight.w700,
                color: AppColors.gray700,
                fontSize: 14,
              ),
              textAlign: TextAlign.center,
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
            ),
          ],
        ),
      ),
    );
  }

}

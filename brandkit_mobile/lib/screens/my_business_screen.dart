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
import 'notifications_screen.dart';
import 'support_screen.dart';
import 'faqs_screen.dart';
import 'catalogue_columns_screen.dart';
import 'frames_screen.dart';
import 'business_list_screen.dart';
import 'downloads_screen.dart';
import '../widgets/error_submission_dialog.dart';

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
              _buildSettingsList(context, hc),
              _buildHelpSupportTitle(),
              _buildSupportList(context),
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
          Row(
            children: [
              Obx(() {
                final logo = hc.businessLogo.value;
                return Container(
                  width: 64,
                  height: 64,
                  decoration: BoxDecoration(
                    shape: BoxShape.circle,
                    border: Border.all(color: AppColors.slate200),
                    color: logo.isEmpty ? AppColors.slate100 : Colors.white,
                  ),
                  clipBehavior: Clip.antiAlias,
                  child: logo.isNotEmpty
                      ? CachedNetworkImage(
                          imageUrl: '${hc.uploadsBaseUrl}/$logo',
                          fit: BoxFit.cover,
                          errorWidget: (_, __, ___) => _buildPlaceholderIcon(),
                        )
                      : _buildPlaceholderIcon(),
                );
              }),
              AppSpacing.gapH20,
              Obx(() => Text(
                hc.businessName.value.isNotEmpty ? hc.businessName.value : 'Business',
                style: AppTextStyles.heading2,
              )),
            ],
          ),
          GestureDetector(
            onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const BusinessProfileScreen())),
            child: Text(
              'EDIT',
              style: TextStyle(
                color: AppColors.indigo600,
                fontWeight: FontWeight.w700,
                fontSize: 15,
                letterSpacing: 0.5,
              ),
            ),
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
          _buildActionCard(
            title: 'AI Setup',
            icon: Icons.smart_toy_outlined,
            iconBg: AppColors.primary,
            iconColor: Colors.white,
            isGradient: true,
            onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const AiSetupWizardScreen())),
          ),
          _buildActionCard(
            title: 'Products',
            icon: Icons.inventory_2_outlined,
            iconBg: AppColors.slate100,
            iconColor: AppColors.slate600,
            onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const ProductsScreen())),
          ),
          _buildActionCard(
            title: 'Catalogue Setting',
            icon: Icons.list_alt_outlined,
            iconBg: AppColors.slate100,
            iconColor: AppColors.slate600,
            onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const CatalogueColumnsScreen())),
          ),
          _buildActionCard(
            title: 'Frames',
            icon: Icons.filter_frames_outlined,
            iconBg: AppColors.slate100,
            iconColor: AppColors.slate600,
            onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const FramesScreen())),
          ),
          _buildActionCard(
            title: 'My Businesses',
            icon: Icons.business_center_outlined,
            iconBg: AppColors.indigo50,
            iconColor: AppColors.indigo500,
            onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const BusinessListScreen())),
          ),
          _buildActionCard(
            title: 'Downloads',
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

  // ── 3. Settings List ──
  Widget _buildSettingsList(BuildContext context, HomeController hc) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 24),
      child: Column(
        children: [
          Obx(() {
            final hc = Get.find<HomeController>();
            return SettingsItem(
              icon: Icons.notifications_none_rounded,
              title: 'Notifications',
              iconColor: AppColors.red400,
              iconBgColor: AppColors.slate50,
              badgeCount: hc.notifications.length,
              onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const NotificationsScreen())),
            );
          }),
          SettingsItem(
            icon: Icons.logout,
            title: 'Logout',
            iconColor: AppColors.red500,
            iconBgColor: AppColors.red50,
            onTap: () {
              Get.dialog(
                AlertDialog(
                  title: const Text('Logout', style: TextStyle(fontWeight: FontWeight.w800)),
                  content: const Text('Are you sure you want to logout?'),
                  actions: [
                    TextButton(
                      onPressed: () => Get.back(),
                      child: Text('Cancel', style: TextStyle(color: AppColors.gray500)),
                    ),
                    TextButton(
                      onPressed: () {
                        Get.back();
                        Get.find<AuthController>().logout();
                      },
                      child: Text('Logout', style: TextStyle(color: AppColors.red500, fontWeight: FontWeight.w700)),
                    ),
                  ],
                ),
              );
            },
          ),
        ],
      ),
    );
  }

  // ── 4. Help & Support Title ──
  Widget _buildHelpSupportTitle() {
    return Padding(
      padding: const EdgeInsets.fromLTRB(24, 16, 24, 16),
      child: Text(
        'Help & Support',
        style: TextStyle(
          fontSize: 18,
          fontWeight: FontWeight.w700,
          color: AppColors.gray600,
          letterSpacing: -0.2,
        ),
      ),
    );
  }

  // ── 5. Support List ──
  Widget _buildSupportList(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 8),
      child: Column(
        children: [
          SettingsItem(
            icon: Icons.chat_bubble_outline_rounded,
            title: 'Help & Support',
            iconColor: AppColors.gray400,
            iconBgColor: AppColors.slate50,
            onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const SupportScreen())),
          ),
          SettingsItem(
            icon: Icons.help_outline_rounded,
            title: 'FAQs',
            iconColor: AppColors.gray400,
            iconBgColor: AppColors.slate50,
            onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const FaqsScreen())),
          ),
          SettingsItem(
            icon: Icons.bug_report_outlined,
            title: 'Report a Problem',
            iconColor: AppColors.gray400,
            iconBgColor: AppColors.slate50,
            onTap: () {
              Get.dialog(
                const ErrorSubmissionDialog(
                  errorCode: 'MANUAL_REPORT',
                  errorMessage: 'User reporting a problem manually from My Business Support.',
                ),
              );
            },
          ),
        ],
      ),
    );
  }
}

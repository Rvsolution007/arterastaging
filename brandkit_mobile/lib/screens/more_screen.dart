import 'package:flutter/material.dart';
import 'package:get/get.dart';
import '../utils/app_colors.dart';
import '../controllers/auth_controller.dart';
import '../utils/app_text_styles.dart';
import '../utils/app_spacing.dart';
import '../widgets/common/settings_item.dart';
import '../widgets/common/custom_switch.dart';
import '../widgets/subscription_card_widget.dart';
import 'notifications_screen.dart';
import 'support_screen.dart';
import 'subscription_plans_screen.dart';
import 'faqs_screen.dart';
import '../controllers/home_controller.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'partner_dashboard_screen.dart';
import '../widgets/error_submission_dialog.dart';
import 'referral_screen.dart';

class MoreScreen extends StatelessWidget {
  const MoreScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return SafeArea(
      child: Scaffold(
        backgroundColor: AppColors.background,
        body: SingleChildScrollView(
          physics: const AlwaysScrollableScrollPhysics(),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              _buildHeader(),
              // ── Subscription Card ──
              SubscriptionCardWidget(
                onUpgradeTap: () {
                  Navigator.push(context, MaterialPageRoute(
                    builder: (_) => const SubscriptionPlansScreen(),
                  ));
                },
              ),
              AppSpacing.gapV16,
              _buildBusinessSettings(context),
              AppSpacing.gapV24,
              _buildPartnerSettings(context),
              _buildAppPreferences(context),
              AppSpacing.gapV24,
              _buildAboutApp(context),
              AppSpacing.gapV16,
              _buildAccountSettings(context),
              _buildFooter(),
              const SizedBox(height: 100),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildHeader() {
    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 24, 16, 16),
      child: Text('Settings', style: AppTextStyles.heading2),
    );
  }

  Widget _buildSectionTitle(String title) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 8),
      child: Text(
        title,
        style: TextStyle(
          fontSize: 17,
          fontWeight: FontWeight.w700,
          color: AppColors.gray900,
        ),
      ),
    );
  }

  Widget _buildSectionContainer({required List<Widget> children}) {
    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        border: Border.symmetric(
          horizontal: BorderSide(color: AppColors.gray100),
        ),
      ),
      child: Column(children: children),
    );
  }

  Widget _buildBusinessSettings(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        _buildSectionTitle('Business Settings'),
        _buildSectionContainer(
          children: [
            SettingsItem(
              icon: Icons.translate_rounded,
              title: 'Preferred Languages',
              subtitle: 'Select languages', // Would be reactive in real app
              iconColor: AppColors.gray500,
              iconBgColor: Colors.transparent,
              onTap: () => _openLanguageDrawer(context),
            ),
            SettingsItem(
              icon: Icons.alternate_email_rounded,
              title: 'Add Watermark',
              iconColor: AppColors.gray500,
              iconBgColor: Colors.transparent,
              trailing: CustomSwitch(value: false, onChanged: (v) {}),
            ),
          ],
        ),
      ],
    );
  }

  Widget _buildPartnerSettings(BuildContext context) {
    return FutureBuilder<SharedPreferences>(
      future: SharedPreferences.getInstance(),
      builder: (context, snapshot) {
        if (!snapshot.hasData) return const SizedBox.shrink();
        final prefs = snapshot.data!;
        final isPartner = prefs.getBool('isPartner') ?? false;

        if (!isPartner) return const SizedBox.shrink();

        return Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            _buildSectionTitle('Partner Program'),
            _buildSectionContainer(
              children: [
                SettingsItem(
                  icon: Icons.monetization_on_outlined,
                  title: 'Partner Dashboard',
                  subtitle: 'View earnings & request withdrawal',
                  iconColor: AppColors.success,
                  iconBgColor: Colors.transparent,
                  onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const PartnerDashboardScreen())),
                ),
              ],
            ),
            AppSpacing.gapV24,
          ],
        );
      },
    );
  }

  Widget _buildAppPreferences(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        _buildSectionTitle('App Preferences'),
        _buildSectionContainer(
          children: [
            Obx(() {
              final hc = Get.find<HomeController>();
              return SettingsItem(
                icon: Icons.notifications_none_rounded,
                title: 'Notifications',
                badgeCount: hc.notifications.length,
                iconColor: AppColors.gray500,
                iconBgColor: Colors.transparent,
                onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const NotificationsScreen())),
              );
            }),
            SettingsItem(
              icon: Icons.dark_mode_outlined,
              title: 'Dark Mode',
              iconColor: AppColors.gray500,
              iconBgColor: Colors.transparent,
              trailing: CustomSwitch(value: false, onChanged: (v) {}),
            ),
            SettingsItem(
              icon: Icons.language_rounded,
              title: 'App Language',
              iconColor: AppColors.gray500,
              iconBgColor: Colors.transparent,
              trailing: Row(
                children: [
                  Text('English', style: AppTextStyles.settingsSubtitle),
                  AppSpacing.gapH4,
                  Icon(Icons.chevron_right, color: AppColors.gray300, size: 22),
                ],
              ),
              onTap: () {},
            ),
            SettingsItem(
              icon: Icons.share_outlined,
              title: 'Add Share Text',
              subtitle: 'Include share text when sharing',
              iconColor: AppColors.gray500,
              iconBgColor: Colors.transparent,
              trailing: CustomSwitch(value: true, onChanged: (v) {}),
            ),
          ],
        ),
      ],
    );
  }

  Widget _buildAboutApp(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        _buildSectionTitle('About App'),
        _buildSectionContainer(
          children: [
            SettingsItem(icon: Icons.help_outline, title: 'Help & Support', iconColor: AppColors.gray500, iconBgColor: Colors.transparent, onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const SupportScreen()))),
            SettingsItem(icon: Icons.chat_bubble_outline, title: 'FAQs', iconColor: AppColors.gray500, iconBgColor: Colors.transparent, onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const FaqsScreen()))),
            SettingsItem(icon: Icons.rss_feed, title: 'Blog', iconColor: AppColors.gray500, iconBgColor: Colors.transparent, onTap: () {}),
            SettingsItem(
              icon: Icons.bug_report_outlined,
              title: 'Report a Problem',
              iconColor: AppColors.gray500,
              iconBgColor: Colors.transparent,
              onTap: () {
                Get.dialog(
                  const ErrorSubmissionDialog(
                    errorCode: 'MANUAL_REPORT',
                    errorMessage: 'User reporting a problem manually from Settings.',
                  ),
                );
              },
            ),
            SettingsItem(icon: Icons.lock_outline, title: 'Privacy Policy', iconColor: AppColors.gray500, iconBgColor: Colors.transparent, onTap: () {}),
            SettingsItem(icon: Icons.description_outlined, title: 'Terms & Conditions', iconColor: AppColors.gray500, iconBgColor: Colors.transparent, onTap: () {}),
            SettingsItem(icon: Icons.credit_card_outlined, title: 'Refund Policy', iconColor: AppColors.gray500, iconBgColor: Colors.transparent, onTap: () {}),
          ],
        ),
      ],
    );
  }

  Widget _buildAccountSettings(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        _buildSectionTitle('Account'),
        _buildSectionContainer(
          children: [
            SettingsItem(
              icon: Icons.card_giftcard_outlined,
              title: 'Invite & Earn',
              subtitle: 'Invite friends, get premium free',
              iconColor: AppColors.success,
              iconBgColor: Colors.transparent,
              onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const ReferralScreen())),
            ),
            SettingsItem(
              icon: Icons.person_add_outlined,
              title: 'Follow Us',
              iconColor: AppColors.gray500,
              iconBgColor: Colors.transparent,
              trailing: Row(
                children: [
                  _buildSocialIcon(Icons.facebook),
                  AppSpacing.gapH8,
                  _buildSocialIcon(Icons.camera_alt_outlined),
                  AppSpacing.gapH8,
                  _buildSocialIcon(null, label: 'X'),
                  AppSpacing.gapH8,
                  _buildSocialIcon(Icons.play_arrow_rounded),
                ],
              ),
            ),
            SettingsItem(
              icon: Icons.person_remove_outlined,
              title: 'Delete Your Account',
              iconColor: AppColors.gray500,
              iconBgColor: Colors.transparent,
              onTap: () {},
            ),
            SettingsItem(
              icon: Icons.logout,
              title: 'Logout',
              iconColor: AppColors.red500,
              iconBgColor: Colors.transparent,
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
      ],
    );
  }

  Widget _buildSocialIcon(IconData? icon, {String? label}) {
    return Container(
      width: 36,
      height: 36,
      decoration: BoxDecoration(
        shape: BoxShape.circle,
        color: AppColors.slate50,
        border: Border.all(color: AppColors.gray100),
      ),
      child: Center(
        child: icon != null
            ? Icon(icon, size: 16, color: AppColors.gray700)
            : Text(label ?? '', style: TextStyle(fontSize: 12, fontWeight: FontWeight.w900, color: AppColors.gray700)),
      ),
    );
  }

  Widget _buildFooter() {
    return Padding(
      padding: const EdgeInsets.only(top: 32, bottom: 48),
      child: Center(
        child: Column(
          children: [
            Opacity(
              opacity: 0.6,
              child: Column(
                children: [
                  Text('App Version 6.49', style: TextStyle(fontSize: 13, fontWeight: FontWeight.w700, color: AppColors.gray600)),
                  AppSpacing.gapV4,
                  Row(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Text('Made with ', style: TextStyle(fontSize: 13, fontWeight: FontWeight.w700, color: AppColors.gray600)),
                      Icon(Icons.favorite, color: AppColors.red500, size: 16),
                      Text(' in India', style: TextStyle(fontSize: 13, fontWeight: FontWeight.w700, color: AppColors.gray600)),
                    ],
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  // ── Language Drawer ──
  void _openLanguageDrawer(BuildContext context) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (context) => const LanguageSelectionDrawer(),
    );
  }
}

class LanguageSelectionDrawer extends StatefulWidget {
  const LanguageSelectionDrawer({super.key});

  @override
  State<LanguageSelectionDrawer> createState() => _LanguageSelectionDrawerState();
}

class _LanguageSelectionDrawerState extends State<LanguageSelectionDrawer> {
  final List<String> allLanguages = [
    'Hindi', 'English', 'Gujarati', 'Marathi', 'Telugu', 'Tamil', 'Bengali',
    'Punjabi', 'Kannada', 'Malayalam', 'Urdu', 'Odia', 'Assamese', 'Maithili',
    'Sanskrit', 'Konkani', 'Manipuri'
  ];
  final Set<String> selectedLanguages = {'English'}; // Example default
  String searchQuery = '';

  @override
  Widget build(BuildContext context) {
    final filteredLanguages = allLanguages
        .where((lang) => lang.toLowerCase().contains(searchQuery.toLowerCase()))
        .toList();

    return Container(
      height: MediaQuery.of(context).size.height * 0.85,
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: const BorderRadius.vertical(top: Radius.circular(32)),
        boxShadow: AppColors.elevatedShadow,
      ),
      child: Column(
        children: [
          // Header
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 16),
            decoration: BoxDecoration(
              border: Border(bottom: BorderSide(color: AppColors.gray100)),
            ),
            child: Row(
              children: [
                GestureDetector(
                  onTap: () => Navigator.pop(context),
                  child: Container(
                    padding: const EdgeInsets.all(8),
                    decoration: BoxDecoration(
                      shape: BoxShape.circle,
                      color: Colors.transparent,
                    ),
                    child: Icon(Icons.arrow_back, color: AppColors.gray900),
                  ),
                ),
                AppSpacing.gapH8,
                Text('Select Language', style: TextStyle(fontSize: 20, fontWeight: FontWeight.w900, color: AppColors.gray900)),
              ],
            ),
          ),
          
          // Search
          Padding(
            padding: const EdgeInsets.all(24),
            child: Container(
              decoration: BoxDecoration(
                color: AppColors.gray50,
                borderRadius: BorderRadius.circular(16),
              ),
              child: TextField(
                onChanged: (val) => setState(() => searchQuery = val),
                decoration: InputDecoration(
                  hintText: 'Search languages',
                  prefixIcon: Icon(Icons.search, color: AppColors.gray400),
                  border: InputBorder.none,
                  enabledBorder: InputBorder.none,
                  focusedBorder: InputBorder.none,
                  filled: false,
                ),
              ),
            ),
          ),

          // List
          Expanded(
            child: filteredLanguages.isEmpty
                ? Center(
                    child: Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Container(
                          width: 64, height: 64,
                          decoration: BoxDecoration(shape: BoxShape.circle, color: AppColors.gray50),
                          child: Icon(Icons.translate, color: AppColors.gray300, size: 32),
                        ),
                        AppSpacing.gapV16,
                        Text('No languages found', style: TextStyle(color: AppColors.gray400, fontWeight: FontWeight.w700, fontSize: 16)),
                      ],
                    ),
                  )
                : ListView.builder(
                    padding: const EdgeInsets.symmetric(horizontal: 24),
                    itemCount: filteredLanguages.length,
                    itemBuilder: (context, index) {
                      final lang = filteredLanguages[index];
                      final isSelected = selectedLanguages.contains(lang);
                      return InkWell(
                        onTap: () {
                          setState(() {
                            if (isSelected) {
                              selectedLanguages.remove(lang);
                            } else {
                              if (selectedLanguages.length >= 5) {
                                Get.snackbar('Limit Reached', 'You can select up to 5 languages maximum',
                                    snackPosition: SnackPosition.BOTTOM, backgroundColor: AppColors.orange500, colorText: Colors.white);
                              } else {
                                selectedLanguages.add(lang);
                              }
                            }
                          });
                        },
                        child: Container(
                          padding: const EdgeInsets.symmetric(vertical: 16),
                          decoration: BoxDecoration(
                            border: Border(bottom: BorderSide(color: AppColors.gray50)),
                            color: isSelected ? AppColors.blue500.withValues(alpha: 0.05) : Colors.transparent,
                          ),
                          child: Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              Text(lang, style: TextStyle(fontSize: 17, fontWeight: FontWeight.w700, color: isSelected ? AppColors.blue600 : AppColors.gray800)),
                              if (isSelected)
                                Container(
                                  width: 24, height: 24,
                                  decoration: BoxDecoration(
                                    shape: BoxShape.circle,
                                    color: AppColors.blue600,
                                    boxShadow: [BoxShadow(color: AppColors.blue500.withValues(alpha: 0.3), blurRadius: 8)],
                                  ),
                                  child: const Icon(Icons.check, color: Colors.white, size: 14),
                                ),
                            ],
                          ),
                        ),
                      );
                    },
                  ),
          ),

          // Bottom Actions
          Container(
            padding: const EdgeInsets.all(24),
            decoration: BoxDecoration(
              color: Colors.white.withValues(alpha: 0.9),
              border: Border(top: BorderSide(color: AppColors.gray50)),
            ),
            child: Row(
              children: [
                Expanded(
                  child: TextButton(
                    onPressed: () => Navigator.pop(context),
                    child: Text('Cancel', style: TextStyle(color: AppColors.gray500, fontSize: 16, fontWeight: FontWeight.w700)),
                  ),
                ),
                Expanded(
                  flex: 2,
                  child: ElevatedButton(
                    style: ElevatedButton.styleFrom(
                      backgroundColor: AppColors.blue600,
                      padding: const EdgeInsets.symmetric(vertical: 16),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                      elevation: 10,
                      shadowColor: AppColors.blue200,
                    ),
                    onPressed: () {
                      // Apply changes
                      Navigator.pop(context);
                    },
                    child: Row(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Text('Apply Changes', style: TextStyle(fontSize: 16, fontWeight: FontWeight.w700)),
                        AppSpacing.gapH8,
                        Icon(Icons.arrow_forward, size: 20),
                      ],
                    ),
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

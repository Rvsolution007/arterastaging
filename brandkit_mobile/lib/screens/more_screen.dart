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
import 'billing_history_screen.dart';
import '../controllers/home_controller.dart';
import '../controllers/subscription_controller.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'partner_dashboard_screen.dart';
import '../widgets/error_submission_dialog.dart';
import 'referral_screen.dart';
import '../services/translation_service.dart';
import 'achievements_screen.dart';
import 'challenges_screen.dart';

class MoreScreen extends StatefulWidget {
  const MoreScreen({super.key});

  @override
  State<MoreScreen> createState() => _MoreScreenState();
}

class _MoreScreenState extends State<MoreScreen> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      Get.find<SubscriptionController>().refreshFromApi();
    });
  }

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
              _buildPartnerSettings(context),
              _buildAppPreferences(context),
              AppSpacing.gapV24,
              _buildGamification(context),
              AppSpacing.gapV24,
              _buildBillingAndPayments(context),
              AppSpacing.gapV24,
              _buildHelpAndSupport(context),
              AppSpacing.gapV24,
              _buildAboutApp(context),
              AppSpacing.gapV24,
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
      child: Text('settings'.tr, style: AppTextStyles.heading2),
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
            _buildSectionTitle('partner_program'.tr),
            _buildSectionContainer(
              children: [
                SettingsItem(
                  icon: Icons.monetization_on_outlined,
                  title: 'partner_dashboard'.tr,
                  subtitle: 'view_earnings'.tr,
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
        _buildSectionTitle('app_preferences'.tr),
        _buildSectionContainer(
          children: [
            SettingsItem(
              icon: Icons.translate_rounded,
              title: 'preferred_languages'.tr,
              iconColor: AppColors.gray500,
              iconBgColor: Colors.transparent,
              trailing: Row(
                children: [
                  Text(
                    TranslationService.availableLanguages.isEmpty 
                      ? 'English' 
                      : TranslationService.availableLanguages.firstWhere(
                          (l) => l['code'] == TranslationService.savedLangCode, 
                          orElse: () => {'title': 'English'}
                        )['title'], 
                    style: AppTextStyles.settingsSubtitle
                  ),
                  AppSpacing.gapH4,
                  Icon(Icons.chevron_right, color: AppColors.gray300, size: 22),
                ],
              ),
              onTap: () => _showLanguageBottomSheet(context),
            ),
            Obx(() {
              final hc = Get.find<HomeController>();
              return SettingsItem(
                icon: Icons.notifications_none_rounded,
                title: 'notifications'.tr,
                badgeCount: hc.notifications.length,
                iconColor: AppColors.gray500,
                iconBgColor: Colors.transparent,
                onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const NotificationsScreen())),
              );
            }),
          ],
        ),
      ],
    );
  }

  Widget _buildGamification(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        _buildSectionTitle('Gamification & Rewards'),
        _buildSectionContainer(
          children: [
            SettingsItem(
              icon: Icons.military_tech_outlined,
              title: 'My Achievements',
              subtitle: 'View your badges and progress',
              iconColor: Colors.orange.shade500,
              iconBgColor: Colors.transparent,
              onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const AchievementsScreen())),
            ),
          ],
        ),
      ],
    );
  }

  Widget _buildBillingAndPayments(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        _buildSectionTitle('Billing & Payments'),
        _buildSectionContainer(
          children: [
            SettingsItem(
              icon: Icons.receipt_long_outlined,
              title: 'Billing & Payment History',
              subtitle: 'View invoices and past payments',
              iconColor: AppColors.blue600,
              iconBgColor: Colors.transparent,
              onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const BillingHistoryScreen())),
            ),
          ],
        ),
      ],
    );
  }

  Widget _buildHelpAndSupport(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        _buildSectionTitle('Help & Support'),
        _buildSectionContainer(
          children: [
            SettingsItem(icon: Icons.help_outline, title: 'help_support'.tr, iconColor: AppColors.gray500, iconBgColor: Colors.transparent, onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const SupportScreen()))),
            SettingsItem(icon: Icons.chat_bubble_outline, title: 'faqs'.tr, iconColor: AppColors.gray500, iconBgColor: Colors.transparent, onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const FaqsScreen()))),
            SettingsItem(
              icon: Icons.bug_report_outlined,
              title: 'report_problem'.tr,
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
          ],
        ),
      ],
    );
  }

  Widget _buildAboutApp(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        _buildSectionTitle('about_app'.tr),
        _buildSectionContainer(
          children: [
            SettingsItem(icon: Icons.rss_feed, title: 'blog'.tr, iconColor: AppColors.gray500, iconBgColor: Colors.transparent, onTap: () {}),
            SettingsItem(icon: Icons.lock_outline, title: 'privacy_policy'.tr, iconColor: AppColors.gray500, iconBgColor: Colors.transparent, onTap: () {}),
            SettingsItem(icon: Icons.description_outlined, title: 'terms_conditions'.tr, iconColor: AppColors.gray500, iconBgColor: Colors.transparent, onTap: () {}),
            SettingsItem(icon: Icons.credit_card_outlined, title: 'refund_policy'.tr, iconColor: AppColors.gray500, iconBgColor: Colors.transparent, onTap: () {}),
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

  void _showLanguageBottomSheet(BuildContext context) {
    showModalBottomSheet(
      context: context,
      backgroundColor: Colors.white,
      shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(20))),
      builder: (BuildContext context) {
        return SafeArea(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Padding(
                padding: const EdgeInsets.all(16.0),
                child: Text('select_language'.tr, style: AppTextStyles.heading3),
              ),
              const Divider(height: 1),
              StatefulBuilder(
                builder: (BuildContext context, StateSetter setModalState) {
                  String currentLang = TranslationService.savedLangCode ?? 'en';
                  
                  return Column(
                    children: TranslationService.availableLanguages.map((lang) {
                      bool isSelected = currentLang == lang['code'];
                      return RadioListTile<String>(
                        activeColor: AppColors.primary,
                        title: Text(lang['title'], style: TextStyle(fontWeight: isSelected ? FontWeight.bold : FontWeight.normal)),
                        value: lang['code'],
                        groupValue: currentLang,
                        onChanged: (String? value) {
                          if (value != null) {
                            TranslationService.changeLanguage(value);
                            setState(() {});
                            Navigator.pop(context);
                          }
                        },
                      );
                    }).toList(),
                  );
                }
              ),
            ],
          ),
        );
      },
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
  final Set<String> selectedLanguages = {};
  String searchQuery = '';
  final HomeController homeController = Get.find<HomeController>();

  @override
  void initState() {
    super.initState();
    _loadSelectedLanguages();
  }

  Future<void> _loadSelectedLanguages() async {
    final prefs = await SharedPreferences.getInstance();
    final saved = prefs.getStringList('selectedLanguages');
    if (saved != null && saved.isNotEmpty) {
      setState(() {
        selectedLanguages.addAll(saved);
      });
    } else {
      // Default to English if nothing saved and it exists in the list
      setState(() {
        selectedLanguages.add('English');
      });
    }
  }

  Future<void> _saveSelectedLanguages() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setStringList('selectedLanguages', selectedLanguages.toList());
  }

  @override
  Widget build(BuildContext context) {
    return Obx(() {
      final allLanguagesData = homeController.languages;
      final List<String> allLanguages = allLanguagesData
          .map((e) => (e['title'] ?? '').toString())
          .where((title) => title.isNotEmpty)
          .toList();
      
      // Capitalize first letter for display
      final formattedLanguages = allLanguages.map((title) {
        if (title.isEmpty) return title;
        return title[0].toUpperCase() + title.substring(1).toLowerCase();
      }).toList();

      final filteredLanguages = formattedLanguages
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
                    onPressed: () async {
                      // Apply changes
                      await _saveSelectedLanguages();
                      Get.snackbar('Success', 'Preferred languages updated successfully',
                          snackPosition: SnackPosition.BOTTOM, 
                          backgroundColor: AppColors.success, 
                          colorText: Colors.white);
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
    });
  }
}

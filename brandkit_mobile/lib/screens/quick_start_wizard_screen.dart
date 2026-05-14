import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../services/api_service.dart';
import '../controllers/home_controller.dart';
import '../utils/app_colors.dart';
import '../utils/app_text_styles.dart';
import '../utils/app_spacing.dart';
import 'business_profile_screen.dart';
import 'ai_setup_wizard_screen.dart';
import 'detail_list_screen.dart';

/// 4-step guided Quick Start wizard.
/// Step 1: Business Profile fill
/// Step 2: AI Catalogue Setup
/// Step 3: Festival Post — select frame & download
/// Step 4: Custom Template — verify product data & download
class QuickStartWizardScreen extends StatefulWidget {
  const QuickStartWizardScreen({super.key});

  @override
  State<QuickStartWizardScreen> createState() => _QuickStartWizardScreenState();
}

class _QuickStartWizardScreenState extends State<QuickStartWizardScreen> with TickerProviderStateMixin {
  final List<bool> _stepsCompleted = [false, false, false, false];
  final List<bool> _stepsInProgress = [false, false, false, false];
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _loadProgress();
  }

  Future<void> _loadProgress() async {
    final prefs = await SharedPreferences.getInstance();
    setState(() {
      for (int i = 0; i < 4; i++) {
        _stepsCompleted[i] = prefs.getBool('quickstart_step_${i}_done') ?? false;
      }
      _isLoading = false;
    });
  }

  Future<void> _markStepDone(int step) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setBool('quickstart_step_${step}_done', true);
    setState(() {
      _stepsCompleted[step] = true;
      _stepsInProgress[step] = false;
    });
  }

  int get _completedCount => _stepsCompleted.where((s) => s).length;
  double get _overallProgress => _completedCount / 4.0;
  bool get _allDone => _completedCount == 4;

  // ── Step Handlers ──

  Future<void> _startStep1() async {
    setState(() => _stepsInProgress[0] = true);
    await Navigator.push(context, MaterialPageRoute(builder: (_) => const BusinessProfileScreen()));
    // Check if profile is now filled
    final hc = Get.find<HomeController>();
    if (hc.businessName.value.isNotEmpty && hc.businessEmail.value.isNotEmpty) {
      await _markStepDone(0);
    } else {
      setState(() => _stepsInProgress[0] = false);
    }
  }

  Future<void> _startStep2() async {
    setState(() => _stepsInProgress[1] = true);
    await Navigator.push(context, MaterialPageRoute(builder: (_) => const AiSetupWizardScreen()));
    // Check if wizard completed (products exist)
    try {
      final prefs = await SharedPreferences.getInstance();
      final userId = prefs.getString('userId') ?? '';
      final response = await ApiService.post('/setup-wizard/status', {'userId': userId});
      final data = jsonDecode(response.body);
      if (data['success'] == true && (data['isConfigured'] == true || data['hasProducts'] == true)) {
        await _markStepDone(1);
      }
    } catch (_) {}
    setState(() => _stepsInProgress[1] = false);
  }

  Future<void> _startStep3() async {
    setState(() => _stepsInProgress[2] = true);
    final hc = Get.find<HomeController>();
    if (hc.upcomingFestivals.isEmpty) {
      await hc.fetchHomeData();
    }

    if (hc.upcomingFestivals.isNotEmpty) {
      final festival = hc.upcomingFestivals.first;
      final id = festival['festivalId'] ?? festival['id'] ?? 0;
      final title = festival['festivalTitle'] ?? festival['title'] ?? 'Festival';

      await Navigator.push(context, MaterialPageRoute(
        builder: (_) => DetailListScreen(type: 'festival', id: id, title: title),
      ));
      await _markStepDone(2);
    } else {
      Get.snackbar('No Festivals', 'No festivals available right now. Please try later.',
          snackPosition: SnackPosition.BOTTOM, backgroundColor: AppColors.orange500, colorText: Colors.white);
      setState(() => _stepsInProgress[2] = false);
    }
  }

  Future<void> _startStep4() async {
    setState(() => _stepsInProgress[3] = true);
    final hc = Get.find<HomeController>();
    if (hc.customPosts.isEmpty) {
      await hc.fetchHomeData();
    }

    if (hc.customPosts.isNotEmpty) {
      final customPost = hc.customPosts.first;
      final id = customPost['customCategoryId'] ?? 0;
      final title = customPost['customCategoryName'] ?? 'Custom';

      await Navigator.push(context, MaterialPageRoute(
        builder: (_) => DetailListScreen(type: 'custom', id: id, title: title),
      ));
      await _markStepDone(3);
    } else {
      Get.snackbar('No Templates', 'No custom templates available. Please try later.',
          snackPosition: SnackPosition.BOTTOM, backgroundColor: AppColors.orange500, colorText: Colors.white);
      setState(() => _stepsInProgress[3] = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        backgroundColor: Colors.white,
        elevation: 0,
        leading: IconButton(
          icon: Icon(Icons.arrow_back, color: AppColors.gray900),
          onPressed: () => Navigator.pop(context),
        ),
        title: Text('Quick Start', style: AppTextStyles.heading4),
        centerTitle: false,
        actions: [
          if (_allDone)
            Container(
              margin: const EdgeInsets.only(right: 16),
              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
              decoration: BoxDecoration(
                color: AppColors.success.withValues(alpha: 0.1),
                borderRadius: BorderRadius.circular(20),
              ),
              child: Row(
                children: [
                  Icon(Icons.check_circle, color: AppColors.success, size: 16),
                  const SizedBox(width: 4),
                  Text('All Done!', style: TextStyle(color: AppColors.success, fontSize: 12, fontWeight: FontWeight.w800)),
                ],
              ),
            ),
        ],
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator(color: AppColors.primary))
          : SingleChildScrollView(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // ── Overall Progress ──
                  _buildOverallProgress(),
                  AppSpacing.gapV24,

                  // ── Steps ──
                  _buildStepCard(
                    index: 0,
                    icon: Icons.business_outlined,
                    iconColor: AppColors.blue600,
                    iconBg: AppColors.blue600.withValues(alpha: 0.1),
                    title: 'Setup Business Profile',
                    subtitle: 'Add your business name, logo, email, phone & address',
                    actionLabel: 'Fill Profile',
                    onAction: _startStep1,
                  ),
                  _buildStepConnector(0),

                  _buildStepCard(
                    index: 1,
                    icon: Icons.auto_awesome_outlined,
                    iconColor: AppColors.purple600,
                    iconBg: AppColors.purple600.withValues(alpha: 0.1),
                    title: 'AI Catalogue Setup',
                    subtitle: 'Upload catalogue PDF, set columns & extract product data with AI',
                    actionLabel: 'Start Wizard',
                    onAction: _startStep2,
                  ),
                  _buildStepConnector(1),

                  _buildStepCard(
                    index: 2,
                    icon: Icons.celebration_outlined,
                    iconColor: AppColors.orange600,
                    iconBg: AppColors.orange600.withValues(alpha: 0.1),
                    title: 'Create Festival Post',
                    subtitle: 'Select a festival, pick a frame, and download your first post',
                    actionLabel: 'Select Festival',
                    onAction: _startStep3,
                  ),
                  _buildStepConnector(2),

                  _buildStepCard(
                    index: 3,
                    icon: Icons.palette_outlined,
                    iconColor: AppColors.indigo600,
                    iconBg: AppColors.indigo600.withValues(alpha: 0.1),
                    title: 'Create Custom Template',
                    subtitle: 'Pick a template, verify your product details & download',
                    actionLabel: 'Select Template',
                    onAction: _startStep4,
                  ),

                  const SizedBox(height: 40),
                ],
              ),
            ),
    );
  }

  Widget _buildOverallProgress() {
    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [Color(0xFF1E293B), Color(0xFF334155)],
        ),
        borderRadius: BorderRadius.circular(20),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.1),
            blurRadius: 12,
            offset: const Offset(0, 6),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    _allDone ? '🎉 All Steps Complete!' : '🚀 Getting Started',
                    style: const TextStyle(
                      color: Colors.white,
                      fontSize: 18,
                      fontWeight: FontWeight.w900,
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    '$_completedCount of 4 steps completed',
                    style: TextStyle(
                      color: Colors.white.withValues(alpha: 0.6),
                      fontSize: 13,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                ],
              ),
              Stack(
                alignment: Alignment.center,
                children: [
                  SizedBox(
                    width: 52,
                    height: 52,
                    child: CircularProgressIndicator(
                      value: _overallProgress,
                      backgroundColor: Colors.white.withValues(alpha: 0.1),
                      color: _allDone ? AppColors.success : AppColors.indigo500,
                      strokeWidth: 4,
                    ),
                  ),
                  Text(
                    '${(_overallProgress * 100).toInt()}%',
                    style: const TextStyle(
                      color: Colors.white,
                      fontSize: 13,
                      fontWeight: FontWeight.w900,
                    ),
                  ),
                ],
              ),
            ],
          ),
          const SizedBox(height: 14),
          TweenAnimationBuilder<double>(
            tween: Tween(begin: 0, end: _overallProgress),
            duration: const Duration(milliseconds: 800),
            curve: Curves.easeOutCubic,
            builder: (_, value, __) {
              return Container(
                height: 6,
                decoration: BoxDecoration(
                  color: Colors.white.withValues(alpha: 0.1),
                  borderRadius: BorderRadius.circular(3),
                ),
                child: FractionallySizedBox(
                  alignment: Alignment.centerLeft,
                  widthFactor: value,
                  child: Container(
                    decoration: BoxDecoration(
                      gradient: LinearGradient(
                        colors: _allDone
                            ? [AppColors.success, const Color(0xFF059669)]
                            : [AppColors.indigo500, AppColors.purple600],
                      ),
                      borderRadius: BorderRadius.circular(3),
                    ),
                  ),
                ),
              );
            },
          ),
        ],
      ),
    );
  }

  Widget _buildStepCard({
    required int index,
    required IconData icon,
    required Color iconColor,
    required Color iconBg,
    required String title,
    required String subtitle,
    required String actionLabel,
    required VoidCallback onAction,
  }) {
    final isDone = _stepsCompleted[index];
    final inProgress = _stepsInProgress[index];

    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(
          color: isDone ? AppColors.success.withValues(alpha: 0.3) : AppColors.gray100,
          width: isDone ? 1.5 : 1,
        ),
        boxShadow: AppColors.cardShadow,
      ),
      child: Row(
        children: [
          // Step number / checkmark
          Container(
            width: 44,
            height: 44,
            decoration: BoxDecoration(
              color: isDone ? AppColors.success : iconBg,
              borderRadius: BorderRadius.circular(14),
            ),
            child: Center(
              child: isDone
                  ? const Icon(Icons.check, color: Colors.white, size: 22)
                  : Icon(icon, color: iconColor, size: 22),
            ),
          ),
          const SizedBox(width: 14),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Text(
                      'Step ${index + 1}',
                      style: TextStyle(
                        fontSize: 10,
                        fontWeight: FontWeight.w800,
                        color: isDone ? AppColors.success : AppColors.gray400,
                        letterSpacing: 0.5,
                      ),
                    ),
                    if (isDone) ...[
                      const SizedBox(width: 6),
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 1),
                        decoration: BoxDecoration(
                          color: AppColors.success.withValues(alpha: 0.1),
                          borderRadius: BorderRadius.circular(4),
                        ),
                        child: Text(
                          'DONE',
                          style: TextStyle(fontSize: 8, fontWeight: FontWeight.w900, color: AppColors.success, letterSpacing: 0.5),
                        ),
                      ),
                    ],
                  ],
                ),
                const SizedBox(height: 2),
                Text(
                  title,
                  style: TextStyle(
                    fontSize: 14,
                    fontWeight: FontWeight.w800,
                    color: isDone ? AppColors.gray500 : AppColors.gray900,
                  ),
                ),
                const SizedBox(height: 2),
                Text(
                  subtitle,
                  style: TextStyle(
                    fontSize: 11,
                    color: AppColors.gray400,
                    fontWeight: FontWeight.w600,
                  ),
                  maxLines: 2,
                ),
              ],
            ),
          ),
          const SizedBox(width: 8),
          if (!isDone)
            GestureDetector(
              onTap: inProgress ? null : onAction,
              child: Container(
                padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
                decoration: BoxDecoration(
                  color: inProgress ? AppColors.gray100 : AppColors.primary,
                  borderRadius: BorderRadius.circular(12),
                ),
                child: inProgress
                    ? SizedBox(
                        width: 16, height: 16,
                        child: CircularProgressIndicator(color: AppColors.primary, strokeWidth: 2),
                      )
                    : Text(
                        'Start',
                        style: TextStyle(
                          color: Colors.white,
                          fontSize: 12,
                          fontWeight: FontWeight.w800,
                        ),
                      ),
              ),
            ),
          if (isDone)
            Icon(Icons.check_circle, color: AppColors.success, size: 28),
        ],
      ),
    );
  }

  Widget _buildStepConnector(int afterIndex) {
    final isDone = _stepsCompleted[afterIndex];
    return Padding(
      padding: const EdgeInsets.only(left: 37),
      child: Container(
        width: 2,
        height: 24,
        decoration: BoxDecoration(
          color: isDone ? AppColors.success.withValues(alpha: 0.3) : AppColors.gray200,
          borderRadius: BorderRadius.circular(1),
        ),
      ),
    );
  }
}

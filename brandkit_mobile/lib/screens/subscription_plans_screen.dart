import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import '../services/api_service.dart';
import '../controllers/subscription_controller.dart';
import '../utils/app_colors.dart';
import '../utils/app_text_styles.dart';
import '../utils/app_spacing.dart';
import '../widgets/checkout_bottom_sheet.dart';

/// Full-page subscription plans screen.
/// Shows all available plans from /subscription-plan API with
/// feature limits comparison and current plan usage progress.
class SubscriptionPlansScreen extends StatefulWidget {
  const SubscriptionPlansScreen({super.key});

  @override
  State<SubscriptionPlansScreen> createState() => _SubscriptionPlansScreenState();
}

class _SubscriptionPlansScreenState extends State<SubscriptionPlansScreen> {
  List<dynamic> _plans = [];
  bool _isLoading = true;
  int _selectedPlanIndex = 0;
  final PageController _pageController = PageController(viewportFraction: 0.88);

  @override
  void initState() {
    super.initState();
    _fetchPlans();
  }

  @override
  void dispose() {
    _pageController.dispose();
    super.dispose();
  }

  Future<void> _fetchPlans() async {
    try {
      final response = await ApiService.get('/subscription-plan');
      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        setState(() {
          _plans = data is List ? data : [];
          _isLoading = false;
        });
      }
    } catch (e) {
      debugPrint('Error fetching plans: $e');
      setState(() => _isLoading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final sc = Get.find<SubscriptionController>();

    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        backgroundColor: Colors.white,
        elevation: 0,
        leading: IconButton(
          icon: Icon(Icons.arrow_back, color: AppColors.gray900),
          onPressed: () => Navigator.pop(context),
        ),
        title: Text('Subscription Plans', style: AppTextStyles.heading4),
        centerTitle: false,
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator(color: AppColors.primary))
          : _plans.isEmpty
              ? Center(
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Icon(Icons.card_membership, size: 64, color: AppColors.gray300),
                      AppSpacing.gapV16,
                      Text('No plans available', style: TextStyle(color: AppColors.gray400, fontSize: 16, fontWeight: FontWeight.w700)),
                    ],
                  ),
                )
              : SingleChildScrollView(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      // ── Current Plan Summary ──
                      _buildCurrentPlanHeader(sc),
                      AppSpacing.gapV16,

                      // ── Plans PageView ──
                      Padding(
                        padding: const EdgeInsets.symmetric(horizontal: 16),
                        child: Text(
                          'AVAILABLE PLANS',
                          style: TextStyle(
                            fontSize: 11,
                            fontWeight: FontWeight.w800,
                            color: AppColors.gray400,
                            letterSpacing: 1.5,
                          ),
                        ),
                      ),
                      AppSpacing.gapV12,
                      SizedBox(
                        height: 520,
                        child: PageView.builder(
                          controller: _pageController,
                          itemCount: _plans.length,
                          onPageChanged: (i) => setState(() => _selectedPlanIndex = i),
                          itemBuilder: (context, index) {
                            final plan = _plans[index];
                            final isCurrentPlan = sc.planName.value == plan['planName'];
                            return _buildPlanCard(plan, isCurrentPlan, sc);
                          },
                        ),
                      ),
                      AppSpacing.gapV16,

                      // ── Page Indicator ──
                      Center(
                        child: Row(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: List.generate(_plans.length, (i) {
                            return AnimatedContainer(
                              duration: const Duration(milliseconds: 300),
                              width: _selectedPlanIndex == i ? 24 : 8,
                              height: 8,
                              margin: const EdgeInsets.symmetric(horizontal: 3),
                              decoration: BoxDecoration(
                                color: _selectedPlanIndex == i ? AppColors.indigo600 : AppColors.gray200,
                                borderRadius: BorderRadius.circular(4),
                              ),
                            );
                          }),
                        ),
                      ),
                      const SizedBox(height: 40),
                    ],
                  ),
                ),
    );
  }

  Widget _buildCurrentPlanHeader(SubscriptionController sc) {
    return Obx(() {
      final features = sc.getFeatureUsageList();
      return Container(
        margin: const EdgeInsets.fromLTRB(16, 16, 16, 0),
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
              color: Colors.black.withValues(alpha: 0.15),
              blurRadius: 16,
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
                Row(
                  children: [
                    Container(
                      padding: const EdgeInsets.all(8),
                      decoration: BoxDecoration(
                        color: Colors.white.withValues(alpha: 0.1),
                        borderRadius: BorderRadius.circular(10),
                      ),
                      child: const Icon(Icons.workspace_premium, color: Colors.amber, size: 20),
                    ),
                    const SizedBox(width: 12),
                    Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          'Your Current Plan',
                          style: TextStyle(
                            color: Colors.white.withValues(alpha: 0.6),
                            fontSize: 11,
                            fontWeight: FontWeight.w700,
                          ),
                        ),
                        Text(
                          sc.planName.value.isNotEmpty ? sc.planName.value : 'Free',
                          style: const TextStyle(
                            color: Colors.white,
                            fontSize: 22,
                            fontWeight: FontWeight.w900,
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
                if (sc.hasActivePlan)
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                    decoration: BoxDecoration(
                      color: sc.isExpiringSoon
                          ? Colors.red.withValues(alpha: 0.2)
                          : Colors.green.withValues(alpha: 0.2),
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: Text(
                      '${sc.daysRemaining}d left',
                      style: TextStyle(
                        color: sc.isExpiringSoon ? Colors.red.shade300 : Colors.green.shade300,
                        fontSize: 12,
                        fontWeight: FontWeight.w800,
                      ),
                    ),
                  ),
              ],
            ),
            if (features.isNotEmpty) ...[
              const SizedBox(height: 16),
              ...features.map((f) => _buildMiniUsageBar(f)),
            ],
          ],
        ),
      );
    });
  }

  Widget _buildMiniUsageBar(FeatureUsageInfo f) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(
                f.name,
                style: TextStyle(color: Colors.white.withValues(alpha: 0.9), fontSize: 12, fontWeight: FontWeight.w600),
                overflow: TextOverflow.ellipsis,
              ),
              RichText(
                text: TextSpan(
                  children: [
                    TextSpan(text: '${f.used} ', style: const TextStyle(color: Colors.white, fontSize: 12, fontWeight: FontWeight.bold)),
                    TextSpan(text: 'used of ', style: TextStyle(color: Colors.white.withValues(alpha: 0.5), fontSize: 11)),
                    TextSpan(text: '${f.limit}', style: const TextStyle(color: Colors.white, fontSize: 12, fontWeight: FontWeight.bold)),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 6),
          Container(
            height: 6,
            decoration: BoxDecoration(
              color: Colors.white.withValues(alpha: 0.1),
              borderRadius: BorderRadius.circular(3),
            ),
            child: FractionallySizedBox(
              alignment: Alignment.centerLeft,
              widthFactor: f.percentage.clamp(0.0, 1.0),
              child: Container(
                decoration: BoxDecoration(
                  color: f.color,
                  borderRadius: BorderRadius.circular(3),
                  boxShadow: [
                    BoxShadow(color: f.color.withValues(alpha: 0.5), blurRadius: 4, offset: const Offset(0, 1)),
                  ],
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildPlanCard(Map<String, dynamic> plan, bool isCurrentPlan, SubscriptionController sc) {
    final featureLimits = plan['featureLimits'] as Map<String, dynamic>?;
    final details = plan['planDetail'] as List<dynamic>?;
    final price = plan['discountPrice'] ?? plan['planPrice'] ?? 0;
    final originalPrice = plan['planPrice'] ?? 0;
    final hasDiscount = plan['discountPrice'] != null && plan['discountPrice'] != plan['planPrice'] && plan['discountPrice'] > 0;

    final featureDisplayNames = {
      'custom_post': 'Custom Posts',
      'daily_drip': 'Daily Drip',
      'magic_cloner': 'Magic Cloner',
      'festival_post': 'Festival Posts',
      'business_category_post': 'Category Posts',
      'photoroom_bg': 'BG Remover',
    };

    return Container(
      margin: const EdgeInsets.symmetric(horizontal: 6, vertical: 4),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(24),
        border: isCurrentPlan ? Border.all(color: AppColors.indigo500, width: 2.5) : Border.all(color: AppColors.gray100),
        boxShadow: [
          BoxShadow(
            color: isCurrentPlan
                ? AppColors.indigo500.withValues(alpha: 0.15)
                : Colors.black.withValues(alpha: 0.04),
            blurRadius: 16,
            offset: const Offset(0, 6),
          ),
        ],
      ),
      child: SingleChildScrollView(
        padding: const EdgeInsets.all(20),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // ── Plan Header ──
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      plan['planName'] ?? 'Plan',
                      style: TextStyle(
                        fontSize: 24,
                        fontWeight: FontWeight.w900,
                        color: AppColors.gray900,
                        letterSpacing: -0.5,
                      ),
                    ),
                    Text(
                      plan['duration'] ?? '',
                      style: TextStyle(
                        fontSize: 13,
                        fontWeight: FontWeight.w600,
                        color: AppColors.gray400,
                      ),
                    ),
                  ],
                ),
                if (isCurrentPlan)
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                    decoration: BoxDecoration(
                      gradient: const LinearGradient(colors: [Color(0xFF4F46E5), Color(0xFF7C3AED)]),
                      borderRadius: BorderRadius.circular(20),
                    ),
                    child: const Text(
                      'CURRENT',
                      style: TextStyle(color: Colors.white, fontSize: 10, fontWeight: FontWeight.w900, letterSpacing: 1),
                    ),
                  ),
              ],
            ),
            const SizedBox(height: 12),

            // ── Price ──
            Row(
              crossAxisAlignment: CrossAxisAlignment.end,
              children: [
                Text(
                  '₹${price is int ? price : (price as num).toInt()}',
                  style: TextStyle(
                    fontSize: 36,
                    fontWeight: FontWeight.w900,
                    color: AppColors.gray900,
                    letterSpacing: -1,
                  ),
                ),
                if (hasDiscount) ...[
                  const SizedBox(width: 8),
                  Padding(
                    padding: const EdgeInsets.only(bottom: 6),
                    child: Text(
                      '₹$originalPrice',
                      style: TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.w700,
                        color: AppColors.gray300,
                        decoration: TextDecoration.lineThrough,
                      ),
                    ),
                  ),
                ],
              ],
            ),
            const SizedBox(height: 16),

            // ── Feature Limits Grid ──
            if (featureLimits != null) ...[
              Container(
                padding: const EdgeInsets.all(14),
                decoration: BoxDecoration(
                  color: AppColors.gray50,
                  borderRadius: BorderRadius.circular(14),
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'FEATURE LIMITS',
                      style: TextStyle(
                        fontSize: 10,
                        fontWeight: FontWeight.w800,
                        color: AppColors.gray400,
                        letterSpacing: 1.2,
                      ),
                    ),
                    const SizedBox(height: 10),
                    ...featureLimits.entries.map((entry) {
                      final name = featureDisplayNames[entry.key] ?? entry.key;
                      final limits = entry.value as Map<String, dynamic>;
                      final baseLimit = limits['base_limit'] ?? 0;
                      // Show usage for current plan
                      final usageFeatures = sc.getFeatureUsageList();
                      final usage = usageFeatures.where((f) => f.key == entry.key).firstOrNull;

                      return Padding(
                        padding: const EdgeInsets.only(bottom: 8),
                        child: Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            Text(
                              name,
                              style: TextStyle(fontSize: 12, fontWeight: FontWeight.w700, color: AppColors.gray700),
                            ),
                            Row(
                              children: [
                                if (isCurrentPlan && usage != null) ...[
                                  Text(
                                    '${usage.used} Used',
                                    style: TextStyle(fontSize: 11, fontWeight: FontWeight.w700, color: usage.color),
                                  ),
                                  Text(
                                    ' / ',
                                    style: TextStyle(fontSize: 11, fontWeight: FontWeight.w500, color: AppColors.gray400),
                                  ),
                                ],
                                Text(
                                  baseLimit > 0 ? '$baseLimit Total' : 'Ad Only',
                                  style: TextStyle(
                                    fontSize: 12,
                                    fontWeight: FontWeight.w800,
                                    color: baseLimit > 0 ? AppColors.indigo600 : AppColors.orange500,
                                  ),
                                ),
                              ],
                            ),
                          ],
                        ),
                      );
                    }),
                  ],
                ),
              ),
              const SizedBox(height: 12),
            ],

            // ── Plan Details ──
            if (details != null && details.isNotEmpty)
              ...details.map((detail) => Padding(
                padding: const EdgeInsets.only(bottom: 6),
                child: Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Icon(Icons.check_circle, color: AppColors.success, size: 16),
                    const SizedBox(width: 8),
                    Expanded(
                      child: Text(
                        detail.toString(),
                        style: TextStyle(fontSize: 13, color: AppColors.gray600, fontWeight: FontWeight.w600),
                      ),
                    ),
                  ],
                ),
              )),

            const SizedBox(height: 16),

            // ── Action Button ──
            GestureDetector(
              onTap: isCurrentPlan ? null : () {
                showModalBottomSheet(
                  context: context,
                  isScrollControlled: true,
                  backgroundColor: Colors.transparent,
                  builder: (ctx) => Padding(
                    padding: EdgeInsets.only(bottom: MediaQuery.of(ctx).viewInsets.bottom),
                    child: CheckoutBottomSheet(plan: plan),
                  ),
                );
              },
              child: Container(
                width: double.infinity,
                padding: const EdgeInsets.symmetric(vertical: 14),
                decoration: BoxDecoration(
                  gradient: isCurrentPlan
                      ? null
                      : const LinearGradient(colors: [Color(0xFF4F46E5), Color(0xFF7C3AED)]),
                  color: isCurrentPlan ? AppColors.gray100 : null,
                  borderRadius: BorderRadius.circular(14),
                ),
                child: Center(
                  child: Text(
                    isCurrentPlan ? '✓ Active Plan' : 'Upgrade Now',
                    style: TextStyle(
                      color: isCurrentPlan ? AppColors.gray400 : Colors.white,
                      fontSize: 15,
                      fontWeight: FontWeight.w800,
                    ),
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

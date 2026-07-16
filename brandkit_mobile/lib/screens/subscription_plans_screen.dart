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
/// Includes a Monthly/Yearly toggle to switch between pricing.
class SubscriptionPlansScreen extends StatefulWidget {
  const SubscriptionPlansScreen({super.key});

  @override
  State<SubscriptionPlansScreen> createState() => _SubscriptionPlansScreenState();
}

class _SubscriptionPlansScreenState extends State<SubscriptionPlansScreen> {
  List<dynamic> _plans = [];
  bool _isLoading = true;
  bool _isYearly = true; // Default to yearly

  @override
  void initState() {
    super.initState();
    _fetchPlans();
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
      backgroundColor: const Color(0xFFF8FAFC), // Premium light background (Slate 50)
      appBar: AppBar(
        backgroundColor: const Color(0xFFF8FAFC),
        elevation: 0,
        leading: IconButton(
          icon: const Icon(Icons.arrow_back, color: Color(0xFF0F172A)),
          onPressed: () => Navigator.pop(context),
        ),
        title: const Text('Subscription Plans', style: TextStyle(color: Color(0xFF0F172A), fontSize: 20, fontWeight: FontWeight.bold, letterSpacing: -0.5)),
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
                      AppSpacing.gapV24,

                      // ── Monthly / Yearly Toggle ──
                      _buildBillingToggle(),
                      AppSpacing.gapV24,

                      // ── Available Plans Label ──
                      Padding(
                        padding: const EdgeInsets.symmetric(horizontal: 20),
                        child: Row(
                          children: [
                            const Text(
                              'ALL PACKAGES',
                              style: TextStyle(
                                fontSize: 13,
                                fontWeight: FontWeight.w800,
                                color: Color(0xFF64748B), // Slate 500
                                letterSpacing: 1.5,
                              ),
                            ),
                            const SizedBox(width: 12),
                            const Expanded(child: Divider(color: Color(0xFFE2E8F0), thickness: 1.5)),
                          ],
                        ),
                      ),
                      AppSpacing.gapV16,

                      // ── Plans Vertical List ──
                      Padding(
                        padding: const EdgeInsets.symmetric(horizontal: 16.0),
                        child: Column(
                          children: List.generate(_plans.length, (index) {
                            final plan = _plans[index];
                            final isCurrentPlan = sc.planName.value == plan['planName'];
                            return Padding(
                              padding: const EdgeInsets.only(bottom: 20.0),
                              child: _buildPlanCard(plan, isCurrentPlan, sc),
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

  // ── Monthly / Yearly Toggle ──
  Widget _buildBillingToggle() {
    return Container(
      margin: const EdgeInsets.symmetric(horizontal: 16),
      padding: const EdgeInsets.all(5),
      decoration: BoxDecoration(
        color: const Color(0xFF0F172A), // Dark Slate background
        borderRadius: BorderRadius.circular(18),
        boxShadow: [
          BoxShadow(
            color: const Color(0xFF0F172A).withValues(alpha: 0.15),
            blurRadius: 12,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Row(
        children: [
          _buildToggleOption(
            label: 'Monthly',
            isSelected: !_isYearly,
            onTap: () => setState(() => _isYearly = false),
          ),
          _buildToggleOption(
            label: 'Yearly',
            isSelected: _isYearly,
            onTap: () => setState(() => _isYearly = true),
            badge: 'SAVE MORE',
          ),
        ],
      ),
    );
  }

  Widget _buildToggleOption({
    required String label,
    required bool isSelected,
    required VoidCallback onTap,
    String? badge,
  }) {
    return Expanded(
      child: GestureDetector(
        onTap: onTap,
        child: AnimatedContainer(
          duration: const Duration(milliseconds: 250),
          curve: Curves.easeInOut,
          padding: const EdgeInsets.symmetric(vertical: 14),
          decoration: BoxDecoration(
            gradient: isSelected
                ? const LinearGradient(colors: [Color(0xFF4F46E5), Color(0xFF7C3AED)])
                : null,
            color: isSelected ? null : Colors.transparent,
            borderRadius: BorderRadius.circular(14),
            boxShadow: isSelected
                ? [
                    BoxShadow(
                      color: const Color(0xFF4F46E5).withValues(alpha: 0.4),
                      blurRadius: 10,
                      offset: const Offset(0, 3),
                    )
                  ]
                : null,
          ),
          child: Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Text(
                label,
                style: TextStyle(
                  color: isSelected ? Colors.white : Colors.white.withValues(alpha: 0.5),
                  fontSize: 15,
                  fontWeight: FontWeight.w800,
                  letterSpacing: 0.3,
                ),
              ),
              if (badge != null && isSelected) ...[
                const SizedBox(width: 8),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 3),
                  decoration: BoxDecoration(
                    color: const Color(0xFFFCD34D),
                    borderRadius: BorderRadius.circular(6),
                  ),
                  child: Text(
                    badge,
                    style: const TextStyle(
                      color: Color(0xFF0F172A),
                      fontSize: 9,
                      fontWeight: FontWeight.w900,
                      letterSpacing: 0.5,
                    ),
                  ),
                ),
              ],
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildCurrentPlanHeader(SubscriptionController sc) {
    return Obx(() {
      final features = sc.getFeatureUsageList();
      return Container(
        margin: const EdgeInsets.fromLTRB(16, 16, 16, 0),
        padding: const EdgeInsets.all(24),
        decoration: BoxDecoration(
          // Deep premium gradient
          gradient: const LinearGradient(
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
            colors: [Color(0xFF0F172A), Color(0xFF1E1B4B)], // Slate 900 to Deep Indigo
          ),
          borderRadius: BorderRadius.circular(28),
          boxShadow: [
            BoxShadow(
              color: const Color(0xFF1E1B4B).withValues(alpha: 0.3),
              blurRadius: 24,
              offset: const Offset(0, 10),
            ),
          ],
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              crossAxisAlignment: CrossAxisAlignment.center,
              children: [
                Row(
                  children: [
                    Container(
                      padding: const EdgeInsets.all(12),
                      decoration: BoxDecoration(
                        gradient: const LinearGradient(
                          colors: [Color(0xFFFCD34D), Color(0xFFF59E0B)], // Amber 300 to Amber 500
                        ),
                        borderRadius: BorderRadius.circular(14),
                        boxShadow: [
                          BoxShadow(
                            color: const Color(0xFFF59E0B).withValues(alpha: 0.4),
                            blurRadius: 12,
                            offset: const Offset(0, 4),
                          )
                        ],
                      ),
                      child: const Icon(Icons.workspace_premium_rounded, color: Colors.white, size: 24),
                    ),
                    const SizedBox(width: 16),
                    Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          'Your Active Plan',
                          style: TextStyle(
                            color: Colors.white.withValues(alpha: 0.7),
                            fontSize: 12,
                            fontWeight: FontWeight.w600,
                            letterSpacing: 0.5,
                          ),
                        ),
                        const SizedBox(height: 2),
                        Text(
                          sc.planName.value.isNotEmpty ? sc.planName.value : 'Free Trial',
                          style: const TextStyle(
                            color: Colors.white,
                            fontSize: 26,
                            fontWeight: FontWeight.w900,
                            letterSpacing: -0.5,
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
                if (sc.hasActivePlan)
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
                    decoration: BoxDecoration(
                      color: sc.isExpiringSoon
                          ? const Color(0xFFFEF2F2).withValues(alpha: 0.15) // Red tinted
                          : const Color(0xFFECFDF5).withValues(alpha: 0.15), // Emerald tinted
                      borderRadius: BorderRadius.circular(20),
                      border: Border.all(
                        color: sc.isExpiringSoon ? const Color(0xFFFCA5A5) : const Color(0xFF6EE7B7),
                        width: 1,
                      ),
                    ),
                    child: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Icon(
                          Icons.timer_outlined,
                          size: 14,
                          color: sc.isExpiringSoon ? const Color(0xFFFCA5A5) : const Color(0xFF6EE7B7),
                        ),
                        const SizedBox(width: 4),
                        Text(
                          '${sc.daysRemaining} Days',
                          style: TextStyle(
                            color: sc.isExpiringSoon ? const Color(0xFFFCA5A5) : const Color(0xFF6EE7B7),
                            fontSize: 13,
                            fontWeight: FontWeight.w800,
                          ),
                        ),
                      ],
                    ),
                  ),
              ],
            ),
            if (features.isNotEmpty) ...[
              const SizedBox(height: 32),
              Text(
                'PLAN LIMITS & USAGE',
                style: TextStyle(
                  color: Colors.white.withValues(alpha: 0.5),
                  fontSize: 10,
                  fontWeight: FontWeight.w800,
                  letterSpacing: 1.5,
                ),
              ),
              const SizedBox(height: 16),
              ...features.map((f) => _buildAnimatedUsageBar(f)),
            ],
          ],
        ),
      );
    });
  }

  Widget _buildAnimatedUsageBar(FeatureUsageInfo f) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 18),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Row(
                children: [
                  Icon(f.icon, size: 16, color: Colors.white.withValues(alpha: 0.8)),
                  const SizedBox(width: 8),
                  Text(
                    f.name,
                    style: const TextStyle(
                      color: Colors.white,
                      fontSize: 14,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                ],
              ),
              RichText(
                text: TextSpan(
                  children: [
                    TextSpan(
                      text: f.limit > 0 ? '${f.used} ' : (f.maxAdUses > 0 ? '${f.adUsed} ' : '0 '),
                      style: TextStyle(color: f.color, fontSize: 15, fontWeight: FontWeight.w900),
                    ),
                    TextSpan(
                      text: f.limit > 0 ? '/ ${f.limit}' : (f.maxAdUses > 0 ? '/ ${f.maxAdUses} (Ad Based)' : ' (Ad Based)'),
                      style: TextStyle(color: Colors.white.withValues(alpha: 0.6), fontSize: 13, fontWeight: FontWeight.w600),
                    ),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 10),
          Container(
            height: 10,
            decoration: BoxDecoration(
              color: Colors.white.withValues(alpha: 0.08),
              borderRadius: BorderRadius.circular(10),
            ),
            child: Stack(
              children: [
                FractionallySizedBox(
                  alignment: Alignment.centerLeft,
                  widthFactor: f.percentage.clamp(0.0, 1.0),
                  child: AnimatedContainer(
                    duration: const Duration(milliseconds: 600),
                    curve: Curves.easeOutCubic,
                    decoration: BoxDecoration(
                      color: f.color,
                      borderRadius: BorderRadius.circular(10),
                      boxShadow: [
                        BoxShadow(
                          color: f.color.withValues(alpha: 0.6),
                          blurRadius: 8,
                          offset: const Offset(0, 2),
                        ),
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

  Widget _buildPlanCard(Map<String, dynamic> plan, bool isCurrentPlan, SubscriptionController sc) {
    final featureLimits = plan['featureLimits'] as Map<String, dynamic>?;
    final details = plan['planDetail'] as List<dynamic>?;

    // Dynamic pricing based on toggle
    final num displayPrice;
    final num displayOriginalPrice;
    final String durationLabel;

    if (_isYearly) {
      final yDiscount = (plan['yearlyDiscountPrice'] ?? 0) as num;
      final yBase = (plan['yearlyPrice'] ?? 0) as num;
      displayPrice = yDiscount > 0 ? yDiscount : (plan['discountPrice'] ?? plan['planPrice'] ?? 0);
      displayOriginalPrice = yBase > 0 ? yBase : (plan['planPrice'] ?? 0);
      durationLabel = ' billed yearly';
    } else {
      final mDiscount = (plan['monthlyDiscountPrice'] ?? 0) as num;
      final mBase = (plan['monthlyPrice'] ?? 0) as num;
      displayPrice = mDiscount > 0 ? mDiscount : mBase;
      displayOriginalPrice = mBase;
      durationLabel = ' billed monthly';
    }

    final hasDiscount = displayPrice > 0 && displayPrice < displayOriginalPrice;

    final String planName = plan['planName'] ?? 'Plan';
    final bool isPopular = planName.toLowerCase().contains('growth') || 
                           planName.toLowerCase().contains('pro') || 
                           planName.toLowerCase().contains('business');

    // Custom descriptions matching the premium pricing plans design
    String planDescription = 'A complete plan with all branding tools and templates.';
    if (planName.toLowerCase().contains('starter') || planName.toLowerCase().contains('basic') || planName.toLowerCase().contains('free')) {
      planDescription = 'For small teams or early-stage creators getting started.';
    } else if (planName.toLowerCase().contains('growth') || planName.toLowerCase().contains('pro')) {
      planDescription = 'For growing businesses ready to scale their design marketing.';
    } else if (planName.toLowerCase().contains('custom') || planName.toLowerCase().contains('enterprise') || planName.toLowerCase().contains('premium')) {
      planDescription = 'For large organizations managing high-volume design kits.';
    }

    String featuresSectionHeader = 'Includes:';
    if (planName.toLowerCase().contains('growth') || planName.toLowerCase().contains('pro')) {
      featuresSectionHeader = 'Includes everything in Basic, plus:';
    } else if (planName.toLowerCase().contains('custom') || planName.toLowerCase().contains('enterprise') || planName.toLowerCase().contains('premium')) {
      featuresSectionHeader = 'Includes everything in Growth, plus:';
    }

    final featureDisplayNames = {
      'custom_post': 'Custom Posts',
      'festival_post': 'Festival Posts',
      'category_post': 'Category Posts',
      'photoroom_bg': 'BG Remover',
    };

    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(24),
        border: Border.all(
          color: isCurrentPlan 
              ? const Color(0xFF4F46E5) 
              : (isPopular ? const Color(0xFFE2E8F0) : const Color(0xFFF1F5F9)), 
          width: isCurrentPlan ? 2.5 : 1.5,
        ),
        boxShadow: [
          BoxShadow(
            color: const Color(0xFF0F172A).withValues(alpha: 0.04),
            blurRadius: 16,
            offset: const Offset(0, 8),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Padding(
            padding: const EdgeInsets.all(24),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // ── Plan Name & Badge ──
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Text(
                      planName,
                      style: const TextStyle(
                        fontSize: 20,
                        fontWeight: FontWeight.w800,
                        color: Color(0xFF0F172A),
                        letterSpacing: -0.5,
                      ),
                    ),
                    if (isPopular)
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                        decoration: BoxDecoration(
                          color: const Color(0xFFEEF2FF), // light purple/indigo
                          borderRadius: BorderRadius.circular(20),
                        ),
                        child: const Row(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            Icon(Icons.star_rounded, color: Color(0xFF4F46E5), size: 14),
                            SizedBox(width: 4),
                            Text(
                              'Popular',
                              style: TextStyle(
                                color: Color(0xFF4F46E5),
                                fontSize: 11,
                                fontWeight: FontWeight.w800,
                              ),
                            ),
                          ],
                        ),
                      )
                    else if (isCurrentPlan)
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                        decoration: BoxDecoration(
                          color: const Color(0xFFECFDF5), // light green
                          borderRadius: BorderRadius.circular(20),
                        ),
                        child: const Text(
                          'Active',
                          style: TextStyle(
                            color: Color(0xFF10B981),
                            fontSize: 11,
                            fontWeight: FontWeight.w800,
                          ),
                        ),
                      ),
                  ],
                ),
                const SizedBox(height: 16),

                // ── Price ──
                Row(
                  crossAxisAlignment: CrossAxisAlignment.baseline,
                  textBaseline: TextBaseline.alphabetic,
                  children: [
                    Text(
                      '₹${displayPrice is int ? displayPrice : (displayPrice).toInt()}',
                      style: const TextStyle(
                        fontSize: 44,
                        fontWeight: FontWeight.w900,
                        color: Color(0xFF0F172A),
                        letterSpacing: -1.0,
                      ),
                    ),
                    const SizedBox(width: 4),
                    Text(
                      durationLabel,
                      style: const TextStyle(
                        fontSize: 13,
                        fontWeight: FontWeight.w600,
                        color: Color(0xFF94A3B8),
                      ),
                    ),
                  ],
                ),
                if (hasDiscount) ...[
                  const SizedBox(height: 2),
                  Text(
                    'Regular: ₹${displayOriginalPrice.toInt()}',
                    style: const TextStyle(
                      fontSize: 13,
                      fontWeight: FontWeight.w600,
                      color: Color(0xFF94A3B8),
                      decoration: TextDecoration.lineThrough,
                    ),
                  ),
                ],
                const SizedBox(height: 12),

                // ── Description ──
                Text(
                  planDescription,
                  style: const TextStyle(
                    fontSize: 14,
                    fontWeight: FontWeight.w500,
                    color: Color(0xFF64748B),
                    height: 1.4,
                  ),
                ),
                const SizedBox(height: 24),
                const Divider(color: Color(0xFFF1F5F9), thickness: 1.5),
                const SizedBox(height: 20),

                // ── Features Section Heading ──
                const Text(
                  'Features',
                  style: TextStyle(
                    fontSize: 15,
                    fontWeight: FontWeight.w800,
                    color: Color(0xFF0F172A),
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  featuresSectionHeader,
                  style: const TextStyle(
                    fontSize: 13,
                    fontWeight: FontWeight.w600,
                    color: Color(0xFF94A3B8),
                  ),
                ),
                const SizedBox(height: 16),

                // ── Included Features ──
                if (featureLimits != null)
                  ...featureLimits.entries.map((entry) {
                    final name = featureDisplayNames[entry.key] ?? entry.key;
                    final limits = entry.value as Map<String, dynamic>;
                    final baseLimit = limits['base_limit'] ?? 0;
                    final textVal = baseLimit > 0 ? '$baseLimit $name' : 'Unlimited $name';

                    return Padding(
                      padding: const EdgeInsets.only(bottom: 12),
                      child: Row(
                        children: [
                          Container(
                            padding: const EdgeInsets.all(4),
                            decoration: const BoxDecoration(
                              color: Color(0xFFEEF2FF),
                              shape: BoxShape.circle,
                            ),
                            child: const Icon(Icons.check, size: 12, color: Color(0xFF4F46E5)),
                          ),
                          const SizedBox(width: 12),
                          Expanded(
                            child: Text(
                              textVal,
                              style: const TextStyle(
                                fontSize: 14,
                                fontWeight: FontWeight.w600,
                                color: Color(0xFF334155),
                              ),
                            ),
                          ),
                        ],
                      ),
                    );
                  }),

                // ── Bullet Point Plan Details ──
                if (details != null && details.isNotEmpty)
                  ...details.map((detail) => Padding(
                        padding: const EdgeInsets.only(bottom: 12),
                        child: Row(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Container(
                              padding: const EdgeInsets.all(4),
                              decoration: const BoxDecoration(
                                color: Color(0xFFEEF2FF),
                                shape: BoxShape.circle,
                              ),
                              child: const Icon(Icons.check, size: 12, color: Color(0xFF4F46E5)),
                            ),
                            const SizedBox(width: 12),
                            Expanded(
                              child: Text(
                                detail.toString(),
                                style: const TextStyle(
                                  fontSize: 14,
                                  fontWeight: FontWeight.w600,
                                  color: Color(0xFF334155),
                                ),
                              ),
                            ),
                          ],
                        ),
                      )),
                const SizedBox(height: 24),

                // ── Action Button ──
                GestureDetector(
                  onTap: isCurrentPlan ? null : () async {
                    bool isUpgrade = sc.hasPaidActivePlan;
                    Map<String, dynamic>? upgradePreview;
                    
                    if (isUpgrade) {
                      showDialog(
                        context: context,
                        barrierDismissible: false,
                        builder: (c) => const Center(child: CircularProgressIndicator()),
                      );
                      upgradePreview = await sc.getUpgradePreview(plan['id'].toString());
                      if (context.mounted) Navigator.pop(context); // Close loading
                    }

                    if (!context.mounted) return;

                    showModalBottomSheet(
                      context: context,
                      isScrollControlled: true,
                      backgroundColor: Colors.transparent,
                      builder: (ctx) => Padding(
                        padding: EdgeInsets.only(bottom: MediaQuery.of(ctx).viewInsets.bottom),
                        child: CheckoutBottomSheet(
                          plan: plan,
                          planType: _isYearly ? 'Yearly' : 'Monthly',
                          isUpgrade: isUpgrade,
                          upgradePreview: upgradePreview,
                        ),
                      ),
                    );
                  },
                  child: Container(
                    width: double.infinity,
                    padding: const EdgeInsets.symmetric(vertical: 16),
                    decoration: BoxDecoration(
                      gradient: isCurrentPlan
                          ? null
                          : (isPopular
                              ? const LinearGradient(colors: [Color(0xFF4F46E5), Color(0xFF7C3AED)])
                              : null),
                      color: isCurrentPlan
                          ? const Color(0xFFF1F5F9)
                          : (isPopular ? null : Colors.white),
                      borderRadius: BorderRadius.circular(16),
                      border: (!isCurrentPlan && !isPopular)
                          ? Border.all(color: const Color(0xFFE2E8F0), width: 1.5)
                          : null,
                      boxShadow: (isCurrentPlan || !isPopular) ? null : [
                        BoxShadow(
                          color: const Color(0xFF4F46E5).withValues(alpha: 0.25),
                          blurRadius: 12,
                          offset: const Offset(0, 4),
                        ),
                      ],
                    ),
                    child: Center(
                      child: Text(
                        isCurrentPlan 
                            ? 'Currently Active' 
                            : 'Upgrade to $planName',
                        style: TextStyle(
                          color: isCurrentPlan 
                              ? const Color(0xFF94A3B8) 
                              : (isPopular ? Colors.white : const Color(0xFF4F46E5)),
                          fontSize: 16,
                          fontWeight: FontWeight.w800,
                          letterSpacing: 0.3,
                        ),
                      ),
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

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

                      // ── Plans Horizontal List ──
                      SizedBox(
                        height: 650, // Premium horizontal layout height constraint
                        child: PageView.builder(
                          controller: PageController(viewportFraction: 0.88),
                          physics: const BouncingScrollPhysics(),
                          itemCount: _plans.length,
                          itemBuilder: (context, index) {
                            final plan = _plans[index];
                            final isCurrentPlan = sc.planName.value == plan['planName'];
                            return Padding(
                              padding: const EdgeInsets.symmetric(horizontal: 8.0, vertical: 8.0),
                              child: _buildPlanCard(plan, isCurrentPlan, sc),
                            );
                          },
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
                      text: '${f.used} ',
                      style: TextStyle(color: f.color, fontSize: 15, fontWeight: FontWeight.w900),
                    ),
                    TextSpan(
                      text: f.limit > 0 ? '/ ${f.limit}' : ' (Ad Based)',
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
      // Fallback to old columns if new ones are 0
      displayPrice = yDiscount > 0 ? yDiscount : (plan['discountPrice'] ?? plan['planPrice'] ?? 0);
      displayOriginalPrice = yBase > 0 ? yBase : (plan['planPrice'] ?? 0);
      durationLabel = '/year';
    } else {
      final mDiscount = (plan['monthlyDiscountPrice'] ?? 0) as num;
      final mBase = (plan['monthlyPrice'] ?? 0) as num;
      displayPrice = mDiscount > 0 ? mDiscount : mBase;
      displayOriginalPrice = mBase;
      durationLabel = '/month';
    }

    final hasDiscount = displayPrice > 0 && displayPrice < displayOriginalPrice;

    final featureDisplayNames = {
      'custom_post': {'name': 'Custom Posts', 'icon': Icons.edit_note_rounded},

      'festival_post': {'name': 'Festival Posts', 'icon': Icons.celebration_rounded},
      'category_post': {'name': 'Category Posts', 'icon': Icons.category_rounded},
      'photoroom_bg': {'name': 'BG Remover', 'icon': Icons.layers_clear_rounded},
    };

    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(28),
        border: isCurrentPlan ? Border.all(color: const Color(0xFF6366F1), width: 3) : Border.all(color: const Color(0xFFF1F5F9), width: 2), // Indigo 500 / Slate 100
        boxShadow: [
          BoxShadow(
            color: isCurrentPlan
                ? const Color(0xFF6366F1).withValues(alpha: 0.15)
                : const Color(0xFF94A3B8).withValues(alpha: 0.08),
            blurRadius: 20,
            offset: const Offset(0, 8),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // ── Plan Header ──
          Container(
            padding: const EdgeInsets.all(24),
            decoration: BoxDecoration(
              color: isCurrentPlan ? const Color(0xFFEEF2FF) : Colors.transparent, // Indigo 50
              borderRadius: const BorderRadius.only(topLeft: Radius.circular(26), topRight: Radius.circular(26)),
            ),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        children: [
                          Flexible(
                            child: Text(
                              plan['planName'] ?? 'Plan',
                              style: const TextStyle(
                                fontSize: 24,
                                fontWeight: FontWeight.w900,
                                color: Color(0xFF0F172A),
                                letterSpacing: -0.5,
                              ),
                            ),
                          ),
                          if (isCurrentPlan) ...[
                            const SizedBox(width: 12),
                            Container(
                              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                              decoration: BoxDecoration(
                                color: const Color(0xFF6366F1),
                                borderRadius: BorderRadius.circular(12),
                              ),
                              child: const Text(
                                'ACTIVE',
                                style: TextStyle(color: Colors.white, fontSize: 10, fontWeight: FontWeight.w900, letterSpacing: 0.5),
                              ),
                            ),
                          ],
                        ],
                      ),
                      const SizedBox(height: 6),
                      Text(
                        _isYearly ? '1 Year Plan' : '1 Month Plan',
                        style: const TextStyle(
                          fontSize: 14,
                          fontWeight: FontWeight.w600,
                          color: Color(0xFF64748B),
                        ),
                      ),
                    ],
                  ),
                ),
                Column(
                  crossAxisAlignment: CrossAxisAlignment.end,
                  children: [
                    Row(
                      crossAxisAlignment: CrossAxisAlignment.end,
                      children: [
                        Text(
                          '₹${displayPrice is int ? displayPrice : (displayPrice).toInt()}',
                          style: const TextStyle(
                            fontSize: 32,
                            fontWeight: FontWeight.w900,
                            color: Color(0xFF0F172A),
                            letterSpacing: -1,
                          ),
                        ),
                        Padding(
                          padding: const EdgeInsets.only(bottom: 5),
                          child: Text(
                            durationLabel,
                            style: const TextStyle(
                              fontSize: 14,
                              fontWeight: FontWeight.w600,
                              color: Color(0xFF94A3B8),
                            ),
                          ),
                        ),
                      ],
                    ),
                    if (hasDiscount) ...[
                      const SizedBox(height: 2),
                      Text(
                        '₹${displayOriginalPrice is int ? displayOriginalPrice : displayOriginalPrice.toInt()}',
                        style: const TextStyle(
                          fontSize: 16,
                          fontWeight: FontWeight.w700,
                          color: Color(0xFF94A3B8),
                          decoration: TextDecoration.lineThrough,
                        ),
                      ),
                    ],
                  ],
                ),
              ],
            ),
          ),
          
          if (isCurrentPlan) const Divider(height: 1, color: Color(0xFFE0E7FF)),
          
          Padding(
            padding: const EdgeInsets.all(24),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // ── Feature Limits List ──
                if (featureLimits != null) ...[
                  const Text(
                    'INCLUDED FEATURES',
                    style: TextStyle(
                      fontSize: 11,
                      fontWeight: FontWeight.w800,
                      color: Color(0xFF94A3B8),
                      letterSpacing: 1.2,
                    ),
                  ),
                  const SizedBox(height: 16),
                  ...featureLimits.entries.map((entry) {
                    final meta = featureDisplayNames[entry.key] as Map<String, dynamic>?;
                    final name = meta?['name'] as String? ?? entry.key;
                    final icon = meta?['icon'] as IconData? ?? Icons.check_circle_outline;
                    final limits = entry.value as Map<String, dynamic>;
                    final baseLimit = limits['base_limit'] ?? 0;

                    return Padding(
                      padding: const EdgeInsets.only(bottom: 14),
                      child: Row(
                        children: [
                          Container(
                            padding: const EdgeInsets.all(6),
                            decoration: BoxDecoration(
                              color: const Color(0xFFF1F5F9), // Slate 100
                              borderRadius: BorderRadius.circular(8),
                            ),
                            child: Icon(icon, size: 16, color: const Color(0xFF475569)), // Slate 600
                          ),
                          const SizedBox(width: 12),
                          Expanded(
                            child: Text(
                              name,
                              style: const TextStyle(fontSize: 15, fontWeight: FontWeight.w600, color: Color(0xFF334155)),
                            ),
                          ),
                          Text(
                            baseLimit > 0 ? '$baseLimit Posts' : 'Ad Only',
                            style: TextStyle(
                              fontSize: 14,
                              fontWeight: FontWeight.w800,
                              color: baseLimit > 0 ? const Color(0xFF4F46E5) : const Color(0xFFF59E0B),
                            ),
                          ),
                        ],
                      ),
                    );
                  }),
                  const SizedBox(height: 8),
                  const Divider(color: Color(0xFFF1F5F9), thickness: 1.5),
                  const SizedBox(height: 16),
                ],

                // ── Plan Details ──
                if (details != null && details.isNotEmpty)
                  ...details.map((detail) => Padding(
                    padding: const EdgeInsets.only(bottom: 10),
                    child: Row(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Icon(Icons.verified_rounded, color: Color(0xFF10B981), size: 18),
                        const SizedBox(width: 10),
                        Expanded(
                          child: Text(
                            detail.toString(),
                            style: const TextStyle(fontSize: 14, color: Color(0xFF475569), fontWeight: FontWeight.w500),
                          ),
                        ),
                      ],
                    ),
                  )),

                const SizedBox(height: 24),

                // ── Action Button ──
                GestureDetector(
                  onTap: isCurrentPlan ? null : () {
                    showModalBottomSheet(
                      context: context,
                      isScrollControlled: true,
                      backgroundColor: Colors.transparent,
                      builder: (ctx) => Padding(
                        padding: EdgeInsets.only(bottom: MediaQuery.of(ctx).viewInsets.bottom),
                        child: CheckoutBottomSheet(
                          plan: plan,
                          planType: _isYearly ? 'Yearly' : 'Monthly',
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
                          : const LinearGradient(colors: [Color(0xFF4F46E5), Color(0xFF7C3AED)]),
                      color: isCurrentPlan ? const Color(0xFFF1F5F9) : null,
                      borderRadius: BorderRadius.circular(16),
                      boxShadow: isCurrentPlan ? null : [
                        BoxShadow(
                          color: const Color(0xFF4F46E5).withValues(alpha: 0.3),
                          blurRadius: 12,
                          offset: const Offset(0, 4),
                        ),
                      ],
                    ),
                    child: Center(
                      child: Text(
                        isCurrentPlan ? '✓ Currently Active' : 'Upgrade to ${plan['planName']}',
                        style: TextStyle(
                          color: isCurrentPlan ? const Color(0xFF94A3B8) : Colors.white,
                          fontSize: 16,
                          fontWeight: FontWeight.w800,
                          letterSpacing: 0.5,
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

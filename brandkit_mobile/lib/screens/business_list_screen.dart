import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:cached_network_image/cached_network_image.dart';
import '../utils/app_colors.dart';
import '../utils/app_text_styles.dart';
import '../utils/app_spacing.dart';
import '../controllers/home_controller.dart';
import '../controllers/subscription_controller.dart';
import 'business_profile_screen.dart';
import 'subscription_plans_screen.dart';
import '../widgets/limit_reached_sheet.dart';

class BusinessListScreen extends StatefulWidget {
  const BusinessListScreen({super.key});

  @override
  State<BusinessListScreen> createState() => _BusinessListScreenState();
}

class _BusinessListScreenState extends State<BusinessListScreen> {
  late HomeController hc;
  late SubscriptionController sc;

  @override
  void initState() {
    super.initState();
    hc = Get.find<HomeController>();
    sc = Get.find<SubscriptionController>();
    // Refresh businesses fresh each time screen opens — don't block UI
    hc.loadBusinessInfo();
  }

  Future<void> _refresh() async {
    await hc.loadBusinessInfo();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        title: Text('My Businesses', style: AppTextStyles.heading4),
        leading: IconButton(
          icon: Icon(Icons.arrow_back, color: AppColors.gray900),
          onPressed: () => Get.back(),
        ),
        actions: [
          IconButton(
            icon: Icon(Icons.add_circle, color: AppColors.primary, size: 28),
            onPressed: () {
              if (hc.businesses.length >= sc.businessLimit.value) {
                final featureInfo = FeatureUsageInfo(
                  key: 'businesses',
                  name: 'Businesses',
                  icon: Icons.business_center,
                  used: hc.businesses.length,
                  limit: sc.businessLimit.value,
                  percentage: 1.0,
                  color: Colors.red,
                  adRewardsRemaining: 0,
                  state: 'active',
                  adUsed: 0,
                  maxAdUses: 0,
                );
                LimitReachedSheet.show(
                  context: context,
                  feature: featureInfo,
                  onUpgrade: () => Get.to(() => const SubscriptionPlansScreen()),
                );
              } else {
                Get.to(() => const BusinessProfileScreen(isNew: true))
                    ?.then((_) => hc.loadBusinessInfo());
              }
            },
          ),
          const SizedBox(width: 8),
        ],
      ),
      body: Obx(() {
        if (hc.businesses.isEmpty) {
          return Center(
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Icon(Icons.business_center_outlined, size: 64, color: AppColors.gray300),
                AppSpacing.gapV16,
                Text('No businesses found', style: AppTextStyles.bodyLarge),
                AppSpacing.gapV8,
                ElevatedButton(
                  onPressed: () =>
                      Get.to(() => const BusinessProfileScreen(isNew: true))
                          ?.then((_) => hc.loadBusinessInfo()),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: AppColors.indigo600,
                    padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 12),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                  ),
                  child: Text('Add Business',
                      style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
                ),
              ],
            ),
          );
        }

        return RefreshIndicator(
          onRefresh: _refresh,
          child: ListView.separated(
            padding: const EdgeInsets.all(24),
            itemCount: hc.businesses.length,
            separatorBuilder: (_, __) => AppSpacing.gapV16,
            itemBuilder: (context, index) {
              final business = hc.businesses[index];
              final name = business['name']?.toString() ?? 'Business Name';

              String category = '';
              if (business['businessCategory'] != null &&
                  business['businessCategory']['businessCategoryName'] != null) {
                category = business['businessCategory']['businessCategoryName'].toString();
              } else if (business['category_name'] != null) {
                category = business['category_name'].toString();
              }

              final logo = business['logo']?.toString() ?? '';
              final isDefault = business['is_default'] == 1 ||
                  business['isDefault'] == true ||
                  business['is_default'] == "1" ||
                  business['isDefault'] == 1;

              return GestureDetector(
                onTap: () =>
                    Get.to(() => BusinessProfileScreen(business: business, isNew: false))
                        ?.then((_) => hc.loadBusinessInfo()),
                child: Container(
                  padding: const EdgeInsets.all(20),
                  decoration: BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.circular(20),
                    border: Border.all(
                        color: isDefault ? AppColors.indigo200 : AppColors.gray100,
                        width: isDefault ? 2 : 1),
                    boxShadow: AppColors.cardShadow,
                  ),
                  child: Row(
                    children: [
                      Container(
                        width: 60,
                        height: 60,
                        decoration: BoxDecoration(
                          shape: BoxShape.circle,
                          color: AppColors.slate50,
                          border: Border.all(color: AppColors.gray200),
                        ),
                        clipBehavior: Clip.antiAlias,
                        child: logo.isNotEmpty
                            ? CachedNetworkImage(
                                imageUrl: logo.startsWith('http')
                                    ? logo
                                    : '${hc.uploadsBaseUrl}/$logo',
                                fit: BoxFit.cover,
                                errorWidget: (_, __, ___) =>
                                    Icon(Icons.storefront, color: AppColors.gray400),
                              )
                            : Icon(Icons.storefront, color: AppColors.gray400),
                      ),
                      AppSpacing.gapH16,
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Row(
                              children: [
                                Expanded(
                                  child: Text(
                                    name,
                                    style: AppTextStyles.heading4.copyWith(fontSize: 16),
                                    maxLines: 1,
                                    overflow: TextOverflow.ellipsis,
                                  ),
                                ),
                                if (isDefault) ...[
                                  const SizedBox(width: 8),
                                  Container(
                                    padding: const EdgeInsets.symmetric(
                                        horizontal: 8, vertical: 4),
                                    decoration: BoxDecoration(
                                      color: AppColors.indigo50,
                                      borderRadius: BorderRadius.circular(8),
                                    ),
                                    child: Text('ACTIVE',
                                        style: TextStyle(
                                            color: AppColors.indigo600,
                                            fontSize: 10,
                                            fontWeight: FontWeight.bold)),
                                  ),
                                ] else ...[
                                  const SizedBox(width: 8),
                                  GestureDetector(
                                    onTap: () => hc.setActiveBusiness(business),
                                    child: Container(
                                      padding: const EdgeInsets.symmetric(
                                          horizontal: 8, vertical: 4),
                                      decoration: BoxDecoration(
                                        color: Colors.white,
                                        border: Border.all(color: AppColors.indigo200),
                                        borderRadius: BorderRadius.circular(8),
                                      ),
                                      child: Text('SET ACTIVE',
                                          style: TextStyle(
                                              color: AppColors.indigo600,
                                              fontSize: 10,
                                              fontWeight: FontWeight.bold)),
                                    ),
                                  ),
                                ],
                              ],
                            ),
                            if (category.isNotEmpty) ...[
                              AppSpacing.gapV4,
                              Text(
                                category,
                                style: AppTextStyles.bodyMedium
                                    .copyWith(color: AppColors.gray500),
                                maxLines: 1,
                                overflow: TextOverflow.ellipsis,
                              ),
                            ],
                          ],
                        ),
                      ),
                      const SizedBox(width: 12),
                      Icon(Icons.edit_outlined, color: AppColors.gray400, size: 20),
                    ],
                  ),
                ),
              );
            },
          ),
        );
      }),
    );
  }
}

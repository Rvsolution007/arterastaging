import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:cached_network_image/cached_network_image.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../controllers/home_controller.dart';
import '../controllers/subscription_controller.dart';
import '../utils/colors.dart';
import '../utils/styles.dart';
import '../utils/spacing.dart';
import '../screens/subscription_plans_screen.dart';
import '../screens/achievements_screen.dart';
import '../screens/notifications_screen.dart';

class SharedHeader extends StatelessWidget {
  const SharedHeader({Key? key}) : super(key: key);

  @override
  Widget build(BuildContext context) {
    final HomeController hc = Get.find<HomeController>();

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
      decoration: const BoxDecoration(
        gradient: LinearGradient(
          colors: [Color(0xFFE0F2FE), Colors.white],
          begin: Alignment.topCenter,
          end: Alignment.bottomCenter,
        ),
      ),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Expanded(
            child: Row(children: [
              Obx(() {
                final logo = hc.businessLogo.value;
                return Container(
                  width: 40, height: 40,
                  decoration: BoxDecoration(
                    shape: BoxShape.circle,
                    border: Border.all(color: AppColors.gray200),
                    color: logo.isEmpty ? AppColors.primary : Colors.white,
                  ),
                  clipBehavior: Clip.antiAlias,
                  child: logo.isNotEmpty
                      ? CachedNetworkImage(imageUrl: '${hc.uploadsBaseUrl}/$logo', fit: BoxFit.cover,
                          errorWidget: (_, __, ___) => _buildInitials(hc))
                      : _buildInitials(hc),
                );
              }),
              AppSpacing.gapH10,
              Expanded(
                child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                  Obx(() => Text(
                    hc.businessName.value.isNotEmpty ? hc.businessName.value : 'update_business'.tr,
                    style: AppTextStyles.cardTitle,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  )),
                  Text('business'.tr, style: AppTextStyles.cardSubtitle),
                ]),
              ),
            ]),
          ),
          const SizedBox(width: 8),
          Row(children: [
            // ── Subscription Badge ──
            Obx(() {
              final sc = Get.find<SubscriptionController>();
              final planLabel = sc.planName.value.isNotEmpty ? sc.planName.value : 'free'.tr;
              final isFree = !sc.hasActivePlan;
              return GestureDetector(
                onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const SubscriptionPlansScreen())),
                child: Container(
                  margin: const EdgeInsets.only(right: 8),
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 7),
                  decoration: BoxDecoration(
                    gradient: isFree
                        ? LinearGradient(colors: [Colors.amber.shade600, Colors.orange.shade700])
                        : const LinearGradient(
                            colors: [Color(0xFFD4AF37), Color(0xFF8B6508)], // Premium Dark Gold
                            begin: Alignment.topLeft,
                            end: Alignment.bottomRight,
                          ),
                    borderRadius: BorderRadius.circular(10),
                    boxShadow: [
                      BoxShadow(
                        color: (isFree ? Colors.amber : const Color(0xFF8B6508)).withValues(alpha: 0.3),
                        blurRadius: 6,
                        offset: const Offset(0, 2),
                      ),
                    ],
                  ),
                  child: Row(children: [
                    Icon(Icons.workspace_premium, color: Colors.white, size: 14),
                    const SizedBox(width: 4),
                    Text(
                      planLabel,
                      style: const TextStyle(color: Colors.white, fontSize: 11, fontWeight: FontWeight.w800),
                    ),
                    if (sc.isExpiringSoon) ...[const SizedBox(width: 4), Container(width: 6, height: 6, decoration: const BoxDecoration(color: Colors.red, shape: BoxShape.circle))],
                  ]),
                ),
              );
            }),
            // ── Gamification Streak Badge ──
            FutureBuilder<SharedPreferences>(
              future: SharedPreferences.getInstance(),
              builder: (context, snapshot) {
                if (!snapshot.hasData) return const SizedBox.shrink();
                final streak = snapshot.data!.getInt('currentStreak') ?? 0;
                if (streak < 1) return const SizedBox.shrink();
                
                return GestureDetector(
                  onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const AchievementsScreen())),
                  child: Container(
                    margin: const EdgeInsets.only(right: 8),
                    padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 7),
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(10),
                      border: Border.all(color: Colors.orange.shade300, width: 1.5),
                      boxShadow: [
                        BoxShadow(
                          color: Colors.orange.withValues(alpha: 0.15),
                          blurRadius: 4,
                          offset: const Offset(0, 2),
                        ),
                      ],
                    ),
                    child: Row(
                      children: [
                        Icon(Icons.local_fire_department, color: Colors.orange.shade600, size: 16),
                        const SizedBox(width: 4),
                        Text(
                          '$streak',
                          style: TextStyle(
                            color: Colors.orange.shade700,
                            fontWeight: FontWeight.w800,
                            fontSize: 12,
                          ),
                        ),
                      ],
                    ),
                  ),
                );
              },
            ),
            // ── Notifications Button ──
            GestureDetector(
              onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const NotificationsScreen())),
              child: Container(
                padding: const EdgeInsets.all(8),
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(12),
                  boxShadow: [
                    BoxShadow(
                      color: Colors.black.withValues(alpha: 0.05),
                      blurRadius: 10,
                      offset: const Offset(0, 4),
                    ),
                  ],
                  border: Border.all(color: AppColors.gray200.withValues(alpha: 0.5)),
                ),
                child: Stack(
                  clipBehavior: Clip.none,
                  children: [
                    const Icon(Icons.notifications_outlined, color: AppColors.gray600, size: 22),
                    Obx(() {
                      if (hc.notifications.isEmpty) return const SizedBox.shrink();
                      return Positioned(
                        top: -4,
                        right: -4,
                        child: Container(
                          padding: const EdgeInsets.all(4),
                          decoration: const BoxDecoration(color: Colors.red, shape: BoxShape.circle),
                          constraints: const BoxConstraints(minWidth: 16, minHeight: 16),
                          child: Text(
                            hc.notifications.length.toString(),
                            style: const TextStyle(color: Colors.white, fontSize: 9, fontWeight: FontWeight.bold),
                            textAlign: TextAlign.center,
                          ),
                        ),
                      );
                    }),
                  ],
                ),
              ),
            ),
          ]),
        ],
      ),
    );
  }

  Widget _buildInitials(HomeController hc) {
    final name = hc.businessName.value;
    final initials = name.isNotEmpty
        ? name.split(' ').take(2).map((w) => w.isNotEmpty ? w[0] : '').join().toUpperCase()
        : 'B';
    return Center(child: Text(initials, style: TextStyle(fontWeight: FontWeight.w700, fontSize: 12, color: Colors.white)));
  }
}

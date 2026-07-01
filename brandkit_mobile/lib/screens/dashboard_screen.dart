import 'package:flutter/material.dart';
import 'package:get/get.dart';
import '../utils/app_colors.dart';
import 'home_screen.dart';
import 'template_grid_screen.dart';
import 'my_business_screen.dart';
import 'ai_trends_screen.dart';
import 'more_screen.dart';

import '../controllers/ad_controller.dart';
import 'ai_chat_screen.dart';
import 'support_tickets_screen.dart';
import '../config/app_config.dart';
import '../widgets/coming_soon_widget.dart';
class DashboardScreen extends StatefulWidget {
  const DashboardScreen({super.key});

  @override
  State<DashboardScreen> createState() => _DashboardScreenState();
}

class _DashboardScreenState extends State<DashboardScreen> {
  int _currentIndex = 0;

  List<Widget> get _pages => [
    const HomeScreen(),
    AppConfig.isProduction ? const ComingSoonWidget(title: 'Custom') : const TemplateGridScreen(),
    const MyBusinessScreen(),
    AppConfig.isProduction ? const ComingSoonWidget(title: 'Greetings') : const AiTrendsScreen(),
    const MoreScreen(),
  ];

  @override
  Widget build(BuildContext context) {
    final AdController adController = Get.find<AdController>();

    return Scaffold(
      backgroundColor: AppColors.background,
      body: _pages[_currentIndex],
      floatingActionButton: Column(
        mainAxisAlignment: MainAxisAlignment.end,
        children: [
          FloatingActionButton(
            heroTag: 'ai_support',
            onPressed: () => Get.to(() => const SupportTicketsScreen()),
            backgroundColor: const Color(0xFF667EEA),
            child: const Icon(Icons.smart_toy, color: Colors.white, size: 28),
          ),

        ],
      ),
      bottomNavigationBar: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          // ── Sticky Banner Ad (above bottom nav) ──
          // Shown on tabs: Home(0), Custom(1), AI Trends(3), More(4)
          // NOT shown on My Business(2)
          Obx(() {
            final bannerWidget = adController.getBannerWidget(_currentIndex);
            if (bannerWidget != null) {
              return Container(
                color: Colors.white,
                width: double.infinity,
                alignment: Alignment.center,
                child: bannerWidget,
              );
            }
            return const SizedBox.shrink();
          }),
          // ── Custom Bottom Navigation Bar (Modern Attractive UI) ──
          Container(
            decoration: BoxDecoration(
              color: Colors.white,
              boxShadow: [
                BoxShadow(
                  color: Colors.black.withOpacity(0.04),
                  blurRadius: 16,
                  offset: const Offset(0, -4),
                ),
              ],
            ),
            child: SafeArea(
              child: Padding(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.spaceAround,
                  children: [
                    _buildNavItem(0, Icons.home_outlined, Icons.home, 'home'.tr),
                    if (!AppConfig.isStaging) _buildNavItem(1, Icons.image_outlined, Icons.image, 'custom'.tr),
                    _buildNavItem(2, Icons.storefront_outlined, Icons.storefront, 'business'.tr),
                    if (!AppConfig.isStaging) _buildNavItem(3, Icons.celebration_outlined, Icons.celebration, 'Greetings'),
                    _buildNavItem(4, Icons.menu, Icons.menu, 'more'.tr),
                  ],
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildNavItem(int index, IconData icon, IconData activeIcon, String label) {
    final isSelected = _currentIndex == index;
    
    // Capitalize first letter for a cleaner look
    final displayLabel = label.isNotEmpty 
        ? '${label[0].toUpperCase()}${label.substring(1).toLowerCase()}' 
        : '';
    
    return GestureDetector(
      behavior: HitTestBehavior.opaque,
      onTap: () => setState(() => _currentIndex = index),
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 200),
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            AnimatedContainer(
              duration: const Duration(milliseconds: 250),
              curve: Curves.easeOut,
              padding: EdgeInsets.all(isSelected ? 6.0 : 0.0),
              decoration: BoxDecoration(
                color: isSelected ? AppColors.primary.withOpacity(0.15) : Colors.transparent,
                borderRadius: BorderRadius.circular(16),
              ),
              child: Icon(
                isSelected ? activeIcon : icon,
                color: isSelected ? AppColors.primary : Colors.grey.shade500,
                size: isSelected ? 26 : 24,
              ),
            ),
            const SizedBox(height: 4),
            Text(
              displayLabel,
              style: TextStyle(
                color: isSelected ? AppColors.textPrimary : Colors.grey.shade500,
                fontWeight: isSelected ? FontWeight.bold : FontWeight.w500,
                fontSize: 10,
              ),
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
            ),
          ],
        ),
      ),
    );
  }
}

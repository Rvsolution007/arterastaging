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

class DashboardScreen extends StatefulWidget {
  const DashboardScreen({super.key});

  @override
  State<DashboardScreen> createState() => _DashboardScreenState();
}

class _DashboardScreenState extends State<DashboardScreen> {
  int _currentIndex = 0;

  final List<Widget> _pages = [
    const HomeScreen(),
    const TemplateGridScreen(),
    const MyBusinessScreen(),
    const AiTrendsScreen(),
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
          // ── Custom Bottom Navigation Bar (1:1 Ratio Items) ──
          Container(
            decoration: BoxDecoration(
              color: Colors.white,
              boxShadow: [
                BoxShadow(
                  color: Colors.black.withOpacity(0.1),
                  blurRadius: 10,
                  offset: const Offset(0, -2),
                ),
              ],
            ),
            child: SafeArea(
              child: Row(
                children: [
                  _buildNavItem(0, Icons.home_outlined, Icons.home, 'home'.tr.toUpperCase()),
                  _buildNavItem(1, Icons.image_outlined, Icons.image, 'custom'.tr.toUpperCase()),
                  _buildNavItem(2, Icons.storefront_outlined, Icons.storefront, 'business'.tr.toUpperCase()),
                  _buildNavItem(3, Icons.celebration_outlined, Icons.celebration, 'GREETINGS'),
                  _buildNavItem(4, Icons.menu, Icons.menu, 'more'.tr.toUpperCase()),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildNavItem(int index, IconData icon, IconData activeIcon, String label) {
    final isSelected = _currentIndex == index;
    final color = isSelected ? AppColors.primary : AppColors.textMuted;
    
    return Expanded(
      child: GestureDetector(
        behavior: HitTestBehavior.opaque,
        onTap: () => setState(() => _currentIndex = index),
        child: Padding(
          padding: const EdgeInsets.symmetric(vertical: 10),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Icon(
                isSelected ? activeIcon : icon,
                color: color,
                size: 24,
              ),
              const SizedBox(height: 4),
              Text(
                label,
                style: TextStyle(
                  color: color,
                  fontWeight: isSelected ? FontWeight.bold : FontWeight.w600,
                  fontSize: 9,
                ),
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
              ),
            ],
          ),
        ),
      ),
    );
  }
}

import 'package:flutter/material.dart';
import 'package:get/get.dart';
import '../utils/app_colors.dart';
import 'home_screen.dart';
import 'template_grid_screen.dart';
import 'my_business_screen.dart';
import 'ai_trends_screen.dart';
import 'more_screen.dart';
import '../widgets/magic_cloner_sheet.dart';
import '../controllers/ad_controller.dart';

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
      floatingActionButton: (_currentIndex == 0 || _currentIndex == 1) ? FloatingActionButton(
        onPressed: () {
          showModalBottomSheet(
            context: context,
            isScrollControlled: true,
            backgroundColor: Colors.transparent,
            builder: (context) => const MagicClonerSheet(),
          );
        },
        backgroundColor: Colors.purple.shade500,
        child: const Icon(Icons.auto_awesome, color: Colors.white, size: 28),
      ) : null,
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
          // ── Bottom Navigation Bar ──
          BottomNavigationBar(
            currentIndex: _currentIndex,
            onTap: (index) {
              setState(() {
                _currentIndex = index;
              });
            },
            type: BottomNavigationBarType.fixed,
            selectedItemColor: AppColors.primary,
            unselectedItemColor: AppColors.textMuted,
            backgroundColor: Colors.white,
            selectedLabelStyle: const TextStyle(fontWeight: FontWeight.bold, fontSize: 10),
            unselectedLabelStyle: const TextStyle(fontWeight: FontWeight.w600, fontSize: 10),
            elevation: 20,
            items: const [
              BottomNavigationBarItem(icon: Icon(Icons.home_outlined), activeIcon: Icon(Icons.home), label: 'HOME'),
              BottomNavigationBarItem(icon: Icon(Icons.image_outlined), activeIcon: Icon(Icons.image), label: 'CUSTOM'),
              BottomNavigationBarItem(icon: Icon(Icons.storefront_outlined), activeIcon: Icon(Icons.storefront), label: 'MY BUSINESS'),
              BottomNavigationBarItem(icon: Icon(Icons.auto_awesome_outlined), activeIcon: Icon(Icons.auto_awesome), label: 'AI TRENDS'),
              BottomNavigationBarItem(icon: Icon(Icons.menu), activeIcon: Icon(Icons.menu), label: 'MORE'),
            ],
          ),
        ],
      ),
    );
  }
}

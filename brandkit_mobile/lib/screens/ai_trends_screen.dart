import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:cached_network_image/cached_network_image.dart';
import '../utils/app_colors.dart';
import '../utils/app_text_styles.dart';
import '../utils/app_spacing.dart';
import '../controllers/home_controller.dart';
import '../widgets/common/section_header.dart';
import 'detail_list_screen.dart';

class AiTrendsScreen extends StatelessWidget {
  const AiTrendsScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final HomeController hc = Get.find<HomeController>();

    return SafeArea(
      child: Scaffold(
        backgroundColor: Colors.white,
        body: SingleChildScrollView(
          physics: const AlwaysScrollableScrollPhysics(),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              _buildHeader(hc),
              AppSpacing.gapV24,
              _buildAiTrendsSection(hc),
              AppSpacing.gapV32,
              _buildBusinessSpecialSection(),
              AppSpacing.gapV32,
              _buildReelsMakerSection(hc),
              const SizedBox(height: 100),
            ],
          ),
        ),
      ),
    );
  }

  // ── 1. Header (Same as Home) ──
  Widget _buildHeader(HomeController hc) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
      decoration: BoxDecoration(
        color: Colors.white,
        border: Border(bottom: BorderSide(color: AppColors.gray50)),
      ),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Row(
            children: [
              Obx(() {
                final logo = hc.businessLogo.value;
                return Container(
                  width: 40,
                  height: 40,
                  decoration: BoxDecoration(
                    shape: BoxShape.circle,
                    color: logo.isEmpty ? AppColors.indigo600 : Colors.white,
                    boxShadow: [
                      BoxShadow(
                        color: Colors.black.withValues(alpha: 0.05),
                        blurRadius: 4,
                      )
                    ],
                  ),
                  clipBehavior: Clip.antiAlias,
                  child: logo.isNotEmpty
                      ? CachedNetworkImage(
                          imageUrl: '${hc.uploadsBaseUrl}/$logo',
                          fit: BoxFit.cover,
                          errorWidget: (_, __, ___) => _buildInitials(hc),
                        )
                      : _buildInitials(hc),
                );
              }),
              AppSpacing.gapH12,
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Obx(() => Text(
                        hc.businessName.value.isNotEmpty ? hc.businessName.value : 'business'.tr,
                        style: AppTextStyles.cardTitle,
                      )),
                  Text('business'.tr, style: TextStyle(color: AppColors.textSecondary, fontSize: 11)),
                ],
              ),
            ],
          ),
          Row(
            children: [
              Container(
                width: 40,
                height: 40,
                decoration: BoxDecoration(
                  shape: BoxShape.circle,
                  color: AppColors.slate100,
                ),
                child: Icon(Icons.notifications_none_rounded, color: AppColors.gray500, size: 20),
              ),
              AppSpacing.gapH12,
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 8),
                decoration: BoxDecoration(
                  color: AppColors.indigo600,
                  borderRadius: BorderRadius.circular(12),
                  boxShadow: AppColors.primaryShadow,
                ),
                child: Row(
                  children: [
                    Icon(Icons.bolt, color: Colors.white, size: 16),
                    const SizedBox(width: 4),
                    Text('quick'.tr, style: AppTextStyles.buttonPrimary.copyWith(fontSize: 12)),
                  ],
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildInitials(HomeController hc) {
    final name = hc.businessName.value;
    final initials = name.isNotEmpty
        ? name.split(' ').take(2).map((w) => w.isNotEmpty ? w[0] : '').join().toUpperCase()
        : 'B';
    return Center(
      child: Text(
        initials,
        style: TextStyle(fontWeight: FontWeight.w700, fontSize: 12, color: Colors.white),
      ),
    );
  }

  // ── 2. AI Trends Section ──
  Widget _buildAiTrendsSection(HomeController hc) {
    return Column(
      children: [
        SectionHeader(
          icon: Icons.auto_awesome_rounded,
          iconColor: AppColors.indigo500,
          title: 'ai_trends'.tr,
          actionText: 'View All',
          trailing: Container(
            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
            decoration: BoxDecoration(
              color: AppColors.indigo50,
              borderRadius: BorderRadius.circular(6),
            ),
            child: Text(
              'NEW',
              style: TextStyle(
                color: AppColors.indigo600,
                fontSize: 10,
                fontWeight: FontWeight.w900,
                letterSpacing: 0.5,
              ),
            ),
          ),
        ),
        AppSpacing.gapV16,
        
        // Filter Pills
        Obx(() {
          if (hc.customPosts.isEmpty) return const SizedBox.shrink();
          return SizedBox(
            height: 40,
            child: ListView.separated(
              scrollDirection: Axis.horizontal,
              padding: const EdgeInsets.symmetric(horizontal: 16),
              itemCount: hc.customPosts.length,
              separatorBuilder: (_, __) => AppSpacing.gapH12,
              itemBuilder: (context, i) {
                final isSelected = i == 0;
                return Container(
                  padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 10),
                  decoration: BoxDecoration(
                    color: isSelected ? AppColors.indigo600 : AppColors.slate100,
                    borderRadius: BorderRadius.circular(999),
                    boxShadow: isSelected ? AppColors.primaryShadow : null,
                  ),
                  child: Center(
                    child: Text(
                      hc.customPosts[i]['customCategoryName'] ?? 'category'.tr,
                      style: TextStyle(color: isSelected ? Colors.white : AppColors.gray800, fontWeight: FontWeight.w600, fontSize: 13),
                    ),
                  ),
                );
              },
            ),
          );
        }),
        AppSpacing.gapV16,

        // Cards Grid
        SizedBox(
          height: 220,
          child: Obx(() {
            if (hc.customPosts.isEmpty) return const SizedBox.shrink();
            return ListView.builder(
              scrollDirection: Axis.horizontal,
              padding: const EdgeInsets.symmetric(horizontal: 16),
              itemCount: hc.customPosts.length,
              itemBuilder: (context, i) {
                final post = hc.customPosts[i];
                final img = post['customCategoryIcon'] ?? '';
                final fullImg = img.toString().startsWith('http') ? img : '${hc.uploadsBaseUrl}/$img';
                
                return GestureDetector(
                  onTap: () => Navigator.push(context, MaterialPageRoute(
                    builder: (_) => DetailListScreen(
                      type: 'custom',
                      id: post['customCategoryId'] ?? 0,
                      title: post['customCategoryName'] ?? 'custom'.tr,
                    ),
                  )),
                  child: Container(
                    width: 180,
                    margin: const EdgeInsets.only(right: 16),
                    decoration: BoxDecoration(
                      borderRadius: BorderRadius.circular(24),
                      color: AppColors.gray200,
                      border: Border.all(color: AppColors.gray50),
                      boxShadow: AppColors.cardShadow,
                      image: img.toString().isNotEmpty
                          ? DecorationImage(
                              image: CachedNetworkImageProvider(fullImg),
                              fit: BoxFit.cover,
                            )
                          : null,
                    ),
                    child: Container(
                      decoration: BoxDecoration(
                        borderRadius: BorderRadius.circular(24),
                        gradient: LinearGradient(
                          begin: Alignment.bottomCenter,
                          end: Alignment.topCenter,
                          colors: [
                            Colors.black.withValues(alpha: 0.2),
                            Colors.transparent,
                          ],
                        ),
                      ),
                      child: img.toString().isEmpty
                          ? Center(child: Icon(Icons.image, color: AppColors.gray400, size: 40))
                          : null,
                    ),
                  ),
                );
              },
            );
          }),
        ),
      ],
    );
  }

  // ── 3. Business Special Section ──
  Widget _buildBusinessSpecialSection() {
    return Column(
      children: [
        SectionHeader(
          icon: Icons.business_center,
          iconColor: Colors.teal,
          title: 'business_special'.tr,
          actionText: 'View All',
          onAction: () {},
        ),
        AppSpacing.gapV16,
        SizedBox(
          height: 220,
          child: ListView(
            scrollDirection: Axis.horizontal,
            padding: const EdgeInsets.symmetric(horizontal: 16),
            children: [
              _buildPlaceholderCard('https://images.unsplash.com/photo-1529156069898-49953e39b3ac?w=400&q=80'),
              AppSpacing.gapH16,
              _buildPlaceholderCard('https://images.unsplash.com/photo-1604594849809-dfedbc827105?w=400&q=80'),
            ],
          ),
        ),
      ],
    );
  }

  Widget _buildPlaceholderCard(String imageUrl) {
    return Container(
      width: 180,
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(32),
        color: AppColors.gray200,
        boxShadow: AppColors.cardShadow,
        border: Border.all(color: AppColors.gray50),
        image: DecorationImage(
          image: CachedNetworkImageProvider(imageUrl),
          fit: BoxFit.cover,
        ),
      ),
    );
  }

  // ── 4. Reels Maker Section ──
  Widget _buildReelsMakerSection(HomeController hc) {
    return Column(
      children: [
        SectionHeader(
          icon: Icons.play_circle_filled,
          iconColor: AppColors.sky500,
          title: 'reels_maker'.tr,
          actionText: 'View All',
          onAction: () {},
          trailing: Container(
            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
            decoration: BoxDecoration(
              color: AppColors.red50,
              borderRadius: BorderRadius.circular(6),
            ),
            child: Text(
              'HOT',
              style: TextStyle(
                color: AppColors.red500,
                fontSize: 10,
                fontWeight: FontWeight.w900,
                letterSpacing: 0.5,
              ),
            ),
          ),
        ),
        AppSpacing.gapV16,
        SizedBox(
          height: 230,
          child: Obx(() {
            if (hc.videos.isEmpty) return const SizedBox.shrink();
            return ListView.builder(
              scrollDirection: Axis.horizontal,
              padding: const EdgeInsets.symmetric(horizontal: 16),
              itemCount: hc.videos.length,
              itemBuilder: (context, i) {
                final video = hc.videos[i];
                final img = video['videoImage'] ?? video['image'] ?? '';
                final fullImg = img.toString().startsWith('http') ? img : '${hc.uploadsBaseUrl}/$img';
                
                return Container(
                  width: 130,
                  margin: const EdgeInsets.only(right: 16),
                  decoration: BoxDecoration(
                    borderRadius: BorderRadius.circular(28),
                    color: AppColors.gray200,
                    boxShadow: AppColors.cardShadow,
                    border: Border.all(color: AppColors.gray50),
                    image: img.toString().isNotEmpty
                        ? DecorationImage(
                            image: CachedNetworkImageProvider(fullImg),
                            fit: BoxFit.cover,
                          )
                        : null,
                  ),
                );
              },
            );
          }),
        ),
      ],
    );
  }
}

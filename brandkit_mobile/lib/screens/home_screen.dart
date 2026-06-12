import 'package:flutter/material.dart';
import 'package:flutter/foundation.dart';
import 'package:url_launcher/url_launcher.dart';
import 'package:get/get.dart';
import 'dart:convert';
import 'package:intl/intl.dart';
import 'package:cached_network_image/cached_network_image.dart';
import '../utils/app_colors.dart';
import '../utils/app_text_styles.dart';
import '../utils/app_spacing.dart';
import '../controllers/home_controller.dart';
import '../controllers/subscription_controller.dart';
import '../controllers/ad_controller.dart';
import '../widgets/common/search_bar_widget.dart';
import '../widgets/common/section_header.dart';
import '../widgets/story_viewer.dart';
import '../services/api_service.dart';
import 'detail_list_screen.dart';
import 'custom_posts_screen.dart';
import 'subscription_plans_screen.dart';
import 'quick_start_wizard_screen.dart';
import 'notifications_screen.dart';
import 'package:shared_preferences/shared_preferences.dart';

import 'achievements_screen.dart';

import '../widgets/shared_header.dart';

class HomeScreen extends StatefulWidget {
  const HomeScreen({super.key});
  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  final now = DateTime.now();

  @override
  void initState() {
    super.initState();
    ApiService.trackActivity(action: 'home_visit');
  }

  @override
  Widget build(BuildContext context) {
    final HomeController hc = Get.put(HomeController());

    return SafeArea(
      child: RefreshIndicator(
        color: AppColors.primary,
        onRefresh: () => hc.fetchHomeData(),
        child: SingleChildScrollView(
          physics: const AlwaysScrollableScrollPhysics(),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const SharedHeader(),
              Obx(() => hc.showQuickStart.value ? _buildQuickStartButton(context) : const SizedBox.shrink()),
              _buildSearchBar(),
              _buildStories(hc),
              _buildFestivalSection(hc),
              _buildCustomPostsSection(hc),
              _buildCategorySection(hc),
              _buildNewsSection(hc),
              _buildVideosSection(hc),
              const SizedBox(height: 100),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildQuickStartButton(BuildContext context) {
    return GestureDetector(
      onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const QuickStartWizardScreen())),
      child: Container(
        margin: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
        decoration: BoxDecoration(
          color: AppColors.primary,
          borderRadius: BorderRadius.circular(12),
          boxShadow: [BoxShadow(color: AppColors.primary.withValues(alpha: 0.3), blurRadius: 8, offset: const Offset(0, 4))],
        ),
        child: Row(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(Icons.rocket_launch, color: Colors.white, size: 18),
            const SizedBox(width: 8),
            Text('quick_start'.tr, style: AppTextStyles.buttonPrimary.copyWith(fontSize: 14)),
          ],
        ),
      ),
    );
  }

  // Header is now in SharedHeader

  // ── 2. Search ──
  Widget _buildSearchBar() {
    return Padding(
      padding: const EdgeInsets.only(left: 16, right: 16, top: 4, bottom: 16),
      child: SearchBarWidget(hintText: 'search_categories_festivals'.tr),
    );
  }

  // ── 3. Stories ──
  Widget _buildStories(HomeController hc) {
    return Obx(() {
      if (hc.stories.isEmpty) return const SizedBox.shrink();
      return SizedBox(
        height: 90,
        child: ListView.separated(
          scrollDirection: Axis.horizontal,
          padding: const EdgeInsets.symmetric(horizontal: 16),
          itemCount: hc.stories.length,
          separatorBuilder: (_, __) => const SizedBox(width: 16),
          itemBuilder: (context, i) {
            final story = hc.stories[i];
            final img = story['image'] ?? story['storyImage'] ?? '';
            final label = story['story_type'] ?? story['storyType'] ?? 'story'.tr;
            final images = <String>[];
            if (story['story_images'] is List) {
              for (var si in story['story_images']) {
                images.add(si.toString().startsWith('http') ? si : '${hc.uploadsBaseUrl}/$si');
              }
            }
            if (images.isEmpty && img.isNotEmpty) {
              images.add(img.startsWith('http') ? img : '${hc.uploadsBaseUrl}/$img');
            }

            return GestureDetector(
              onTap: () {
                if (images.isNotEmpty) {
                  Navigator.push(context, MaterialPageRoute(
                    builder: (_) => StoryViewer(
                      images: images,
                      linkTitle: story['external_link_title']?.toString(),
                      linkUrl: story['external_link']?.toString(),
                      businessName: hc.businessName.value,
                      businessLogo: hc.businessLogo.value.isNotEmpty ? '${hc.uploadsBaseUrl}/${hc.businessLogo.value}' : null,
                    ),
                  ));
                }
              },
              child: Column(mainAxisAlignment: MainAxisAlignment.center, children: [
                Container(
                  width: 66, height: 66,
                  padding: const EdgeInsets.all(2.5),
                  decoration: BoxDecoration(shape: BoxShape.circle, gradient: AppColors.storyRingGradient),
                  child: Container(
                    decoration: BoxDecoration(shape: BoxShape.circle, border: Border.all(color: Colors.white, width: 2), color: AppColors.gray100),
                    clipBehavior: Clip.antiAlias,
                    child: images.isNotEmpty
                        ? CachedNetworkImage(imageUrl: images.first, fit: BoxFit.cover)
                        : Icon(Icons.image, color: AppColors.gray400, size: 24),
                  ),
                ),
                const SizedBox(height: 6),
                SizedBox(
                  width: 64,
                  child: Text(label.toString(), style: AppTextStyles.storyLabel, textAlign: TextAlign.center, maxLines: 1, overflow: TextOverflow.ellipsis),
                ),
              ]),
            );
          },
        ),
      );
    });
  }

  // ── 4. Festival Calendar ──
  Widget _buildFestivalSection(HomeController hc) {
    return Column(children: [
      AppSpacing.gapV8,
      Padding(
        padding: const EdgeInsets.symmetric(horizontal: 16),
        child: Row(mainAxisAlignment: MainAxisAlignment.spaceBetween, children: [
          Row(children: [
            Icon(Icons.calendar_today_outlined, color: AppColors.gray400, size: 24),
            AppSpacing.gapH12,
            Text('${'upcoming_festivals'.tr} ${now.year}', style: AppTextStyles.heading3),
          ]),
          Text(DateFormat('MMMM').format(now), style: AppTextStyles.sectionSubtitle),
        ]),
      ),
      AppSpacing.gapV16,
      SizedBox(
        height: 96,
        child: Obx(() {
          final selectedIdx = hc.selectedDateIndex.value;
          
          return ListView.builder(
            clipBehavior: Clip.none,
            scrollDirection: Axis.horizontal,
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
            itemCount: 12,
            itemBuilder: (context, i) {
              final date = now.add(Duration(days: i));
              final isSelected = selectedIdx == i;
            return GestureDetector(
              onTap: () {
                if (hc.selectedDateIndex.value == i) {
                  hc.fetchHomeData(); // Deselect and show upcoming
                } else {
                  hc.fetchFestivalsByDate(DateFormat('yyyy-MM-dd').format(date), i);
                }
              },
              child: AnimatedContainer(
                duration: const Duration(milliseconds: 300),
                width: 55, margin: const EdgeInsets.only(right: 16),
                decoration: BoxDecoration(
                  color: isSelected ? AppColors.primary : Colors.white,
                  borderRadius: BorderRadius.circular(16),
                  border: isSelected ? null : Border.all(color: AppColors.gray100),
                  boxShadow: isSelected ? [BoxShadow(color: AppColors.indigo100.withValues(alpha: 0.8), blurRadius: 12, offset: const Offset(0, 6))] : null,
                ),
                transform: isSelected ? (Matrix4.identity()..scale(1.1)) : Matrix4.identity(),
                child: Column(mainAxisAlignment: MainAxisAlignment.center, children: [
                  Text(DateFormat('EEE').format(date).toUpperCase(),
                    style: AppTextStyles.datePickerDay.copyWith(color: isSelected ? AppColors.indigo100 : AppColors.gray400)),
                  const SizedBox(height: 2),
                  Text(DateFormat('dd').format(date),
                    style: AppTextStyles.datePickerDate.copyWith(color: isSelected ? Colors.white : AppColors.textPrimary)),
                ]),
              ),
            );
          },
        ); }),
      ),
      AppSpacing.gapV16,
      // Festival Cards
      SizedBox(
        height: 200,
        child: Obx(() {
          Widget contentWidget;
          
          if (hc.isLoading.value || hc.isFestivalLoading.value) {
            contentWidget = Center(key: const ValueKey('loading'), child: CircularProgressIndicator(color: AppColors.primary));
          } else if (hc.upcomingFestivals.isEmpty) {
            contentWidget = Center(
              key: const ValueKey('empty'),
              child: Column(mainAxisAlignment: MainAxisAlignment.center, children: [
                Icon(Icons.celebration_outlined, size: 48, color: AppColors.gray300),
                AppSpacing.gapV8,
                Text('no_festivals_found'.tr, style: TextStyle(color: AppColors.gray400, fontWeight: FontWeight.w500)),
              ])
            );
          } else {
            contentWidget = ListView.builder(
              key: ValueKey('list_${hc.selectedDateIndex.value}'),
              scrollDirection: Axis.horizontal,
              padding: const EdgeInsets.symmetric(horizontal: 16),
              itemCount: hc.upcomingFestivals.length,
              itemBuilder: (context, i) {
                final f = hc.upcomingFestivals[i];
                final imgUrl = f['festivalImage'] ?? f['image'] ?? '';
                final fullImg = imgUrl.toString().startsWith('http') ? imgUrl : '${hc.uploadsBaseUrl}/$imgUrl';
                String dateStr = '';
                try { dateStr = (f['festivalDate'] ?? f['festivals_date'] ?? '').toString().split(' ')[0]; } catch (_) {}

                return GestureDetector(
                  onTap: () => Get.toNamed('/details', arguments: {
                    'type': 'festival',
                    'id': f['festivalId'] ?? f['id'] ?? 0,
                    'title': f['festivalTitle'] ?? f['title'] ?? 'festival'.tr,
                  }),
                  child: Container(
                    width: 155, margin: const EdgeInsets.only(right: 16),
                    decoration: BoxDecoration(
                      borderRadius: BorderRadius.circular(32),
                      color: AppColors.gray200,
                      image: imgUrl.toString().isNotEmpty ? DecorationImage(image: CachedNetworkImageProvider(fullImg), fit: BoxFit.cover) : null,
                    ),
                    child: Stack(children: [
                      if (imgUrl.toString().isEmpty)
                        Center(child: Icon(Icons.celebration, size: 40, color: AppColors.gray400)),
                      if (dateStr.isNotEmpty)
                        Positioned(bottom: 16, left: 16, child: Container(
                          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                          decoration: BoxDecoration(color: Colors.black.withValues(alpha: 0.2), borderRadius: BorderRadius.circular(8)),
                          child: Text(dateStr.length >= 8 ? dateStr.substring(8) : dateStr,
                            style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w900, fontSize: 15)),
                        )),
                    ]),
                  ),
                );
              },
            );
          }

          return AnimatedSwitcher(
            duration: const Duration(milliseconds: 300),
            switchInCurve: Curves.easeIn,
            switchOutCurve: Curves.easeOut,
            child: contentWidget,
          );
        }),
      ),
      AppSpacing.gapV32,
    ]);
  }

  // ── 5. Categories ──
  Widget _buildCategorySection(HomeController hc) {
    return Column(children: [
      SectionHeader(icon: Icons.layers_outlined, title: 'category_posts'.tr),
      AppSpacing.gapV16,
      SizedBox(
        child: Obx(() {
          if (hc.isLoading.value) return const Center(child: Padding(padding: EdgeInsets.all(32), child: CircularProgressIndicator()));
          if (hc.categories.isEmpty) return Center(child: Padding(padding: EdgeInsets.all(32), child: Text('no_categories_found'.tr)));
          return GridView.builder(
            physics: const NeverScrollableScrollPhysics(),
            shrinkWrap: true,
            padding: const EdgeInsets.symmetric(horizontal: 16),
            gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
              crossAxisCount: 3,
              crossAxisSpacing: 12,
              mainAxisSpacing: 16,
              childAspectRatio: 0.8,
            ),
            itemCount: hc.categories.length,
            itemBuilder: (context, i) {
              final cat = hc.categories[i];
              final img = cat['categoryIcon'] ?? cat['icon'] ?? '';
              final fullImg = img.toString().startsWith('http') ? img : '${hc.uploadsBaseUrl}/$img';
              return GestureDetector(
                onTap: () => Get.toNamed('/details', arguments: {
                  'type': 'category',
                  'id': cat['categoryId'] ?? cat['id'] ?? 0,
                  'title': cat['categoryName'] ?? cat['name'] ?? 'category'.tr,
                }),
                child: Column(
                  children: [
                    Expanded(
                      child: Container(
                        decoration: BoxDecoration(
                          borderRadius: BorderRadius.circular(16),
                          color: AppColors.gray200,
                          image: img.toString().isNotEmpty ? DecorationImage(image: CachedNetworkImageProvider(fullImg), fit: BoxFit.cover) : null,
                          boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.05), blurRadius: 4, offset: const Offset(0, 2))],
                        ),
                        child: img.toString().isEmpty ? const Center(child: Icon(Icons.category, color: Colors.grey)) : null,
                      ),
                    ),
                    const SizedBox(height: 8),
                    Text(
                      cat['categoryName'] ?? cat['name'] ?? '',
                      style: const TextStyle(color: AppColors.textPrimary, fontWeight: FontWeight.w600, fontSize: 12),
                      maxLines: 2,
                      textAlign: TextAlign.center,
                      overflow: TextOverflow.ellipsis,
                    ),
                  ],
                ),
              );
            },
          );
        }),
      ),
      AppSpacing.gapV32,
    ]);
  }

  Widget _buildCustomPostsSection(HomeController hc) {
    return Obx(() {
      final templates = hc.recentCustomPosts;
      if (templates.isEmpty) {
        if (hc.isLoading.value) {
          return const Center(child: Padding(
            padding: EdgeInsets.all(40.0),
            child: CircularProgressIndicator(),
          ));
        }
        return const SizedBox.shrink();
      }
      return Column(children: [
        SectionHeader(
          icon: Icons.work_outline, 
          title: 'new_custom_posts'.tr, 
          actionText: 'view_all'.tr, 
          onAction: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const CustomPostsScreen()))
        ),
        AppSpacing.gapV16,
        SizedBox(
          height: 170,
          child: ListView.builder(
            scrollDirection: Axis.horizontal,
            padding: const EdgeInsets.symmetric(horizontal: 16),
            itemCount: templates.length,
            itemBuilder: (context, i) {
              final template = templates[i];
              String imgUrl = template['image'] ?? '';
              // Fix for emulator/local testing where asset() generates public/uploads instead of uploads
              imgUrl = imgUrl.replaceAll('public/uploads', 'uploads');
              
              // Calculate width from aspect ratio
              double ar = 1.0;
              try {
                final arStr = template['aspect_ratio']?.toString() ?? '1:1';
                final parts = arStr.split(':');
                if (parts.length == 2) ar = double.parse(parts[0]) / double.parse(parts[1]);
              } catch (_) {}
              final cardWidth = 170.0 * ar;
              
              return GestureDetector(
                onTap: () async {
                  final AdController adController = Get.find<AdController>();
                  ApiService.trackActivity(
                    action: 'select_template',
                    itemType: 'custom',
                    itemId: template['id'].toString(),
                  );
                  await adController.handleFeatureAccess(
                    context: context,
                    feature: 'custom_post',
                    onAccessGranted: () async {
                      if (kIsWeb) {
                        final editorUrl = await ApiService.getWebEditorUrl(
                          type: template['type'] ?? 'business_custom_frame',
                          id: template['id'].toString(),
                          designUrl: imgUrl,
                        );
                        final uri = Uri.parse(editorUrl);
                        if (await canLaunchUrl(uri)) {
                          await launchUrl(uri, webOnlyWindowName: '_self');
                        }
                        return;
                      }

                      final fc = adController.adConfig.value?.features['custom_post'];
                      if (fc != null && fc.baseLimit > 0) {
                        Get.snackbar(
                          'usage_update'.tr, 
                          '${fc.used}/${fc.baseLimit} ${'custom_posts_used'.tr}',
                          snackPosition: SnackPosition.BOTTOM,
                          backgroundColor: Colors.black87,
                          colorText: Colors.white,
                          margin: const EdgeInsets.all(16),
                          borderRadius: 8,
                          duration: const Duration(seconds: 2),
                        );
                      }
                      
                      final editorQuery = Uri(queryParameters: {
                        'type': template['type'] ?? 'business_custom_frame',
                        'id': template['id'].toString(),
                        'designUrl': imgUrl,
                      }).query;
                      Get.toNamed(
                        '/editor?$editorQuery',
                        arguments: {
                          'frameData': template,
                        },
                      );
                    }
                  );
                },
                child: Container(
                  width: cardWidth, height: 170, margin: const EdgeInsets.only(right: 16),
                  decoration: BoxDecoration(
                    borderRadius: BorderRadius.circular(20),
                    color: Colors.white,
                    boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.05), blurRadius: 4, offset: const Offset(0, 2))],
                  ),
                  clipBehavior: Clip.antiAlias,
                  child: imgUrl.isNotEmpty
                      ? Image.network(imgUrl, fit: BoxFit.cover,
                          width: double.infinity, height: double.infinity,
                          errorBuilder: (_, __, ___) => const Center(child: Icon(Icons.image, color: Colors.grey)),
                        )
                      : const Center(child: Icon(Icons.image, color: Colors.grey)),
                ),
              );
            },
          ),
        ),
        AppSpacing.gapV32,
      ]);
    });
  }

  // ── 7. News ──
  Widget _buildNewsSection(HomeController hc) {
    return Obx(() {
      if (hc.news.isEmpty) return const SizedBox.shrink();
      return Column(children: [
        SectionHeader(icon: Icons.newspaper_outlined, title: 'news_updates'.tr),
        AppSpacing.gapV16,
        SizedBox(
          height: 230,
          child: ListView.builder(
            scrollDirection: Axis.horizontal,
            padding: const EdgeInsets.symmetric(horizontal: 16),
            itemCount: hc.news.length,
            itemBuilder: (context, i) {
              final article = hc.news[i];
              final img = article['image'] ?? article['newsImage'] ?? '';
              final fullImg = img.toString().startsWith('http') ? img : '${hc.uploadsBaseUrl}/$img';
              return Container(
                width: 280, margin: const EdgeInsets.only(right: 16),
                decoration: BoxDecoration(
                  color: Colors.white, borderRadius: BorderRadius.circular(24),
                  border: Border.all(color: AppColors.gray100),
                  boxShadow: AppColors.cardShadow,
                ),
                clipBehavior: Clip.antiAlias,
                child: Column(children: [
                  SizedBox(
                    height: 140,
                    width: double.infinity,
                    child: img.toString().isNotEmpty
                        ? CachedNetworkImage(imageUrl: fullImg, fit: BoxFit.cover)
                        : Container(color: AppColors.gray100, child: Icon(Icons.newspaper, color: AppColors.gray400)),
                  ),
                  Padding(
                    padding: const EdgeInsets.all(16),
                    child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                      Text(article['title'] ?? article['newsTitle'] ?? '', style: TextStyle(color: AppColors.gray900, fontWeight: FontWeight.w700, fontSize: 14.5), maxLines: 2, overflow: TextOverflow.ellipsis),
                      const SizedBox(height: 6),
                      Text(article['description'] ?? article['newsDescription'] ?? '', style: TextStyle(color: AppColors.gray500, fontSize: 12.5), maxLines: 2, overflow: TextOverflow.ellipsis),
                    ]),
                  ),
                ]),
              );
            },
          ),
        ),
        AppSpacing.gapV32,
      ]);
    });
  }

  // ── 8. Videos ──
  Widget _buildVideosSection(HomeController hc) {
    return Obx(() {
      if (hc.videos.isEmpty) return const SizedBox.shrink();
      return Column(children: [
        SectionHeader(
          icon: Icons.play_circle_outline,
          title: 'Videos',
          trailing: Row(children: [
            Container(
              width: 40, height: 40,
              decoration: BoxDecoration(shape: BoxShape.circle, color: AppColors.sky50),
              child: Icon(Icons.play_arrow_rounded, color: AppColors.sky500, size: 20),
            ),
          ]),
        ),
        AppSpacing.gapV16,
        SizedBox(
          height: 200,
          child: ListView.builder(
            scrollDirection: Axis.horizontal,
            padding: const EdgeInsets.symmetric(horizontal: 16),
            itemCount: hc.videos.length,
            itemBuilder: (context, i) {
              final video = hc.videos[i];
              final img = video['image'] ?? video['videoImage'] ?? '';
              final fullImg = img.toString().startsWith('http') ? img : '${hc.uploadsBaseUrl}/$img';
              return Container(
                width: 130, margin: const EdgeInsets.only(right: 16),
                decoration: BoxDecoration(
                  borderRadius: BorderRadius.circular(28),
                  color: AppColors.gray200,
                  border: Border.all(color: Colors.white.withValues(alpha: 0.5)),
                  boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.1), blurRadius: 8, offset: const Offset(0, 4))],
                  image: img.toString().isNotEmpty ? DecorationImage(image: CachedNetworkImageProvider(fullImg), fit: BoxFit.cover) : null,
                ),
                child: Container(
                  decoration: BoxDecoration(
                    borderRadius: BorderRadius.circular(28),
                    color: Colors.black.withValues(alpha: 0.2),
                  ),
                  child: Center(
                    child: Container(
                      width: 40, height: 40,
                      decoration: BoxDecoration(
                        shape: BoxShape.circle,
                        color: Colors.white.withValues(alpha: 0.3),
                      ),
                      child: Icon(Icons.play_arrow_rounded, color: Colors.white, size: 24),
                    ),
                  ),
                ),
              );
            },
          ),
        ),
        AppSpacing.gapV32,
      ]);
    });
  }
}

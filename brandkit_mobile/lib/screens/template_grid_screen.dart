import 'package:flutter/material.dart';
import 'package:flutter/foundation.dart' show kIsWeb;
import 'package:url_launcher/url_launcher.dart';
import 'package:get/get.dart';
import '../utils/app_colors.dart';
import '../controllers/home_controller.dart';
import '../controllers/ad_controller.dart';
import '../services/api_service.dart';
import 'editor_screen.dart';
import 'custom_posts_screen.dart';

class TemplateGridScreen extends StatelessWidget {
  const TemplateGridScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final HomeController homeController = Get.find<HomeController>();

    return SafeArea(
      child: SingleChildScrollView(
        physics: const AlwaysScrollableScrollPhysics(),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Header
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 16.0, vertical: 16.0),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Row(
                    children: [
                      Container(
                        width: 40, height: 40,
                        decoration: BoxDecoration(
                          shape: BoxShape.circle,
                          color: AppColors.primary,
                        ),
                        child: const Icon(Icons.person, color: Colors.white, size: 20),
                      ),
                      const SizedBox(width: 10),
                      Obx(() => Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            homeController.businessName.value.isNotEmpty 
                                ? homeController.businessName.value.toUpperCase() 
                                : 'business'.tr,
                            style: TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: AppColors.textPrimary),
                          ),
                          Text('custom_templates'.tr, style: TextStyle(fontSize: 11, color: AppColors.textSecondary, fontWeight: FontWeight.w500)),
                        ],
                      )),
                    ],
                  ),
                  Row(
                    children: [
                      Container(
                        width: 36, height: 36,
                        decoration: BoxDecoration(shape: BoxShape.circle, color: Colors.grey.shade100),
                        child: Icon(Icons.notifications_none, color: AppColors.textSecondary, size: 20),
                      ),
                      const SizedBox(width: 12),
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                        decoration: BoxDecoration(
                          color: AppColors.primary,
                          borderRadius: BorderRadius.circular(20),
                          boxShadow: [BoxShadow(color: AppColors.primary.withOpacity(0.3), blurRadius: 8, offset: const Offset(0, 4))],
                        ),
                        child: Row(
                          children: [
                            const Icon(Icons.bolt, color: Colors.white, size: 16),
                            const SizedBox(width: 4),
                            Text('quick'.tr, style: const TextStyle(color: Colors.white, fontSize: 13, fontWeight: FontWeight.bold)),
                          ],
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),

            // Search Bar
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 16.0),
              child: Container(
                padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 4),
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(24),
                  border: Border.all(color: Colors.grey.shade200),
                ),
                child: Row(
                  children: [
                    const Icon(Icons.search, color: Colors.grey),
                    const SizedBox(width: 12),
                    Expanded(
                      child: TextField(
                        decoration: InputDecoration(
                          border: InputBorder.none,
                          hintText: 'search_templates_categories'.tr,
                          hintStyle: const TextStyle(fontSize: 14, color: Colors.grey),
                        ),
                      ),
                    ),
                    Container(
                      width: 32, height: 32,
                      decoration: BoxDecoration(shape: BoxShape.circle, color: Colors.grey.shade100),
                      child: const Icon(Icons.mic, color: Colors.grey, size: 18),
                    ),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 28),

            // 1. Create Something New
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 16.0),
              child: Row(
                children: [
                  Container(
                    width: 32, height: 32,
                    decoration: BoxDecoration(shape: BoxShape.circle, color: AppColors.primary.withOpacity(0.1)),
                    child: Icon(Icons.auto_awesome, color: AppColors.primary, size: 16),
                  ),
                  const SizedBox(width: 12),
                  Text('create_something_new'.tr, style: TextStyle(fontSize: 17, fontWeight: FontWeight.bold, color: AppColors.textPrimary)),
                ],
              ),
            ),
            const SizedBox(height: 20),

            // Template Type Cards
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 16.0),
              child: Row(
                crossAxisAlignment: CrossAxisAlignment.end,
                children: [
                  Expanded(
                    child: _buildTemplateCard(
                      gradient: const [Color(0xFFDAE0EB), Color(0xFFE4D6EB)],
                      icon: Icons.grid_view,
                      iconColor: const Color(0xFF4B5563),
                      label: 'post'.tr,
                      size: '2000×2000',
                      aspectRatio: 1.0,
                    ),
                  ),
                  const SizedBox(width: 20),
                  Expanded(
                    child: _buildTemplateCard(
                      gradient: const [Color(0xFFFCE4E6), Color(0xFFF4F3CA)],
                      icon: Icons.movie_creation_outlined,
                      iconColor: const Color(0xFF7C2D3D),
                      label: 'story'.tr,
                      size: '1080×1920',
                      aspectRatio: 9 / 16,
                    ),
                  ),
                  const SizedBox(width: 20),
                  Expanded(
                    child: _buildTemplateCard(
                      gradient: const [Color(0xFFD5EFED), Color(0xFFE4EEF1)],
                      icon: Icons.image_outlined,
                      iconColor: const Color(0xFF1E3A8A),
                      label: 'ads_infographics'.tr,
                      size: '2000×2500',
                      aspectRatio: 9 / 16,
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 36),

            // 2. New Posts (Recent 10)
            _buildSectionHeader(
              icon: Icons.fiber_new_rounded,
              iconBgColor: Colors.green.shade50,
              iconColor: Colors.green.shade600,
              title: 'new_posts'.tr,
            ),
            const SizedBox(height: 16),
            SizedBox(
              height: 170,
              child: Obx(() {
                final recentPosts = homeController.recentCustomPosts;
                if (recentPosts.isEmpty) {
                  if (homeController.isLoading.value) {
                    return const Center(child: CircularProgressIndicator());
                  }
                  return Center(
                    child: Text('no_templates_yet'.tr, style: TextStyle(color: Colors.grey.shade400)),
                  );
                }
                return ListView.builder(
                  scrollDirection: Axis.horizontal,
                  padding: const EdgeInsets.symmetric(horizontal: 16),
                  itemCount: recentPosts.length,
                  itemBuilder: (context, index) {
                    final post = recentPosts[index];
                    return _buildTemplatePreviewCard(
                      context: context,
                      post: post,
                      homeController: homeController,
                    );
                  },
                );
              }),
            ),
            const SizedBox(height: 28),

            // 3. Dynamic Purpose-Based Sections
            Obx(() {
              final purposes = homeController.customPosts;
              if (purposes.isEmpty && !homeController.isLoading.value) {
                return const SizedBox.shrink();
              }
              return Column(
                children: purposes.map<Widget>((purpose) {
                  final purposeName = purpose['customCategoryName'] ?? 'templates'.tr;
                  final purposeId = purpose['customCategoryId'];
                  final posts = (purpose['posts'] as List<dynamic>?) ?? [];

                  if (posts.isEmpty) return const SizedBox.shrink();

                  // Pick an icon based on index for visual variety
                  final purposeIndex = purposes.indexOf(purpose);
                  final sectionColors = _getSectionColors(purposeIndex);

                  return Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      // Section Header with "View All"
                      Padding(
                        padding: const EdgeInsets.symmetric(horizontal: 16.0),
                        child: Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            Expanded(
                              child: Row(
                                children: [
                                  Container(
                                    width: 32, height: 32,
                                    decoration: BoxDecoration(
                                      shape: BoxShape.circle,
                                      color: sectionColors['bgColor'],
                                    ),
                                    child: Icon(sectionColors['icon'] as IconData, color: sectionColors['iconColor'] as Color, size: 16),
                                  ),
                                  const SizedBox(width: 12),
                                  Flexible(
                                    child: Text(
                                      purposeName,
                                      style: TextStyle(fontSize: 17, fontWeight: FontWeight.bold, color: AppColors.textPrimary),
                                      maxLines: 1,
                                      overflow: TextOverflow.ellipsis,
                                    ),
                                  ),
                                ],
                              ),
                            ),
                            if (posts.length > 4)
                              GestureDetector(
                                onTap: () {
                                  Get.to(() => CustomPostsScreen(initialCategoryId: purposeId));
                                },
                                child: Row(
                                  children: [
                                    Text('view_all'.tr, style: TextStyle(color: AppColors.primary, fontWeight: FontWeight.bold, fontSize: 14)),
                                    Icon(Icons.chevron_right, color: AppColors.primary, size: 18),
                                  ],
                                ),
                              ),
                          ],
                        ),
                      ),
                      const SizedBox(height: 14),

                      // Horizontal Template List
                      SizedBox(
                        height: 170,
                        child: ListView.builder(
                          scrollDirection: Axis.horizontal,
                          padding: const EdgeInsets.symmetric(horizontal: 16),
                          itemCount: posts.length > 6 ? 6 : posts.length,
                          itemBuilder: (context, index) {
                            final post = posts[index];
                            return _buildTemplatePreviewCard(
                              context: context,
                              post: post,
                              homeController: homeController,
                            );
                          },
                        ),
                      ),
                      const SizedBox(height: 28),
                    ],
                  );
                }).toList(),
              );
            }),

            const SizedBox(height: 100),
          ],
        ),
      ),
    );
  }

  // ─── SHARED TEMPLATE PREVIEW CARD ───
  Widget _buildTemplatePreviewCard({
    required BuildContext context,
    required Map<String, dynamic> post,
    required HomeController homeController,
    double height = 170,
  }) {
    final imgUrl = post['image'] ?? '';
    
    // Calculate width from aspect ratio (e.g., "1:1" = square, "9:16" = portrait)
    double aspectRatio = 1.0;
    try {
      final ar = post['aspect_ratio']?.toString() ?? '1:1';
      final parts = ar.split(':');
      if (parts.length == 2) {
        aspectRatio = double.parse(parts[0]) / double.parse(parts[1]);
      }
    } catch (_) {}
    final cardWidth = height * aspectRatio;
    
    return GestureDetector(
      onTap: () async {
        final AdController adController = Get.find<AdController>();
        await adController.handleFeatureAccess(
          context: context,
          feature: 'custom_post',
          onAccessGranted: () async {
            if (kIsWeb) {
              final editorUrl = await ApiService.getWebEditorUrl(
                type: post['type'] ?? 'business_custom_frame',
                id: post['id'].toString(),
                designUrl: imgUrl,
              );
              final uri = Uri.parse(editorUrl);
              if (await canLaunchUrl(uri)) {
                await launchUrl(uri, webOnlyWindowName: '_self');
              }
              return;
            }

            final editorQuery = Uri(queryParameters: {
              'type': post['type'] ?? 'business_custom_frame',
              'id': post['id'].toString(),
              'designUrl': imgUrl,
            }).query;
            Get.toNamed(
              '/editor?$editorQuery',
              arguments: {
                'frameData': post,
              },
            );
          },
        );
      },
      child: Container(
        width: cardWidth,
        height: height,
        margin: const EdgeInsets.only(right: 14),
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(18),
          color: Colors.white,
          boxShadow: [
            BoxShadow(color: Colors.black.withOpacity(0.06), blurRadius: 8, offset: const Offset(0, 3)),
          ],
        ),
        clipBehavior: Clip.antiAlias,
        child: imgUrl.isNotEmpty
            ? Image.network(
                imgUrl,
                fit: BoxFit.cover,
                width: double.infinity,
                height: double.infinity,
                loadingBuilder: (context, child, loadingProgress) {
                  if (loadingProgress == null) return child;
                  return Center(
                    child: CircularProgressIndicator(
                      strokeWidth: 2,
                      value: loadingProgress.expectedTotalBytes != null
                          ? loadingProgress.cumulativeBytesLoaded / loadingProgress.expectedTotalBytes!
                          : null,
                      color: const Color(0xFF6366F1),
                    ),
                  );
                },
                errorBuilder: (_, __, ___) => _buildPlaceholderIcon(),
              )
            : _buildPlaceholderIcon(),
      ),
    );
  }

  Widget _buildPlaceholderIcon() {
    return Container(
      color: const Color(0xFFF1F5F9),
      child: Center(
        child: Icon(Icons.image_outlined, color: Colors.grey.shade300, size: 36),
      ),
    );
  }

  // ─── SECTION HEADER WIDGET ───
  Widget _buildSectionHeader({
    required IconData icon,
    required Color iconBgColor,
    required Color iconColor,
    required String title,
  }) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 16.0),
      child: Row(
        children: [
          Container(
            width: 32, height: 32,
            decoration: BoxDecoration(shape: BoxShape.circle, color: iconBgColor),
            child: Icon(icon, color: iconColor, size: 16),
          ),
          const SizedBox(width: 12),
          Text(title, style: TextStyle(fontSize: 17, fontWeight: FontWeight.bold, color: AppColors.textPrimary)),
        ],
      ),
    );
  }

  // ─── SECTION COLORS (visual variety per purpose) ───
  Map<String, dynamic> _getSectionColors(int index) {
    final palettes = [
      {'icon': Icons.storefront, 'bgColor': Colors.indigo.shade50, 'iconColor': Colors.indigo.shade600},
      {'icon': Icons.local_offer, 'bgColor': Colors.orange.shade50, 'iconColor': Colors.orange.shade600},
      {'icon': Icons.celebration, 'bgColor': Colors.pink.shade50, 'iconColor': Colors.pink.shade600},
      {'icon': Icons.campaign, 'bgColor': Colors.teal.shade50, 'iconColor': Colors.teal.shade600},
      {'icon': Icons.star, 'bgColor': Colors.amber.shade50, 'iconColor': Colors.amber.shade700},
      {'icon': Icons.rocket_launch, 'bgColor': Colors.purple.shade50, 'iconColor': Colors.purple.shade600},
      {'icon': Icons.trending_up, 'bgColor': Colors.cyan.shade50, 'iconColor': Colors.cyan.shade600},
      {'icon': Icons.handshake, 'bgColor': Colors.lime.shade50, 'iconColor': Colors.lime.shade700},
    ];
    return palettes[index % palettes.length];
  }

  // ─── TEMPLATE TYPE CARD (Post/Story/Ads) ───
  Widget _buildTemplateCard({
    required List<Color> gradient,
    required IconData icon,
    required Color iconColor,
    required String label,
    required String size,
    required double aspectRatio,
  }) {
    return Column(
      children: [
        AspectRatio(
          aspectRatio: aspectRatio.clamp(0.5, 1.2),
          child: Container(
            decoration: BoxDecoration(
              gradient: LinearGradient(begin: Alignment.topCenter, end: Alignment.bottomCenter, colors: gradient),
              borderRadius: BorderRadius.circular(22),
              border: Border.all(color: Colors.white.withOpacity(0.5)),
              boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.05), blurRadius: 8, offset: const Offset(0, 4))],
            ),
            child: Center(
              child: Container(
                width: 40, height: 40,
                decoration: BoxDecoration(
                  borderRadius: BorderRadius.circular(10),
                  border: Border.all(color: iconColor.withOpacity(0.3), width: 1.5),
                ),
                child: Icon(icon, color: iconColor.withOpacity(0.6), size: 22),
              ),
            ),
          ),
        ),
        const SizedBox(height: 8),
        Text(label, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 12, color: Color(0xFF374151)), textAlign: TextAlign.center, maxLines: 1, overflow: TextOverflow.ellipsis),
        const SizedBox(height: 2),
        Text(size, style: TextStyle(fontSize: 10, color: AppColors.textMuted, fontWeight: FontWeight.w500)),
      ],
    );
  }
}

import 'package:brandkit_mobile/utils/string_extensions.dart';
import 'package:flutter/material.dart';
import '../widgets/coming_soon_widget.dart';
import '../config/app_config.dart';
import 'package:flutter/foundation.dart';
import 'package:url_launcher/url_launcher.dart';
import 'package:get/get.dart';
import 'package:brandkit_mobile/controllers/auth_controller.dart';
import '../utils/app_colors.dart';
import '../utils/app_text_styles.dart';
import '../utils/app_spacing.dart';
import '../controllers/home_controller.dart';
import '../widgets/product_picker_sheet.dart';
import '../services/api_service.dart';
import 'package:infinite_scroll_pagination/infinite_scroll_pagination.dart';
import 'dart:convert';

class CustomPostsScreen extends StatefulWidget {
  final int? initialCategoryId;

  const CustomPostsScreen({super.key, this.initialCategoryId});

  @override
  State<CustomPostsScreen> createState() => _CustomPostsScreenState();
}

class _CustomPostsScreenState extends State<CustomPostsScreen> {
  final HomeController hc = Get.find<HomeController>();
  int _selectedCategoryId = 0;
  String _selectedTag = '';
  bool _isLoading = false;
  String? _currentProductName;

  final PagingController<int, dynamic> _pagingController =
      PagingController(firstPageKey: 1);

  @override
  void initState() {
    super.initState();
    if (widget.initialCategoryId != null) {
      _selectedCategoryId = widget.initialCategoryId!;
    } else if (hc.customPosts.isNotEmpty) {
      _selectedCategoryId = hc.customPosts.first['customCategoryId'] ?? 0;
    }
    
    _pagingController.addPageRequestListener((pageKey) {
      _fetchPage(pageKey);
    });
  }

  Future<void> _fetchPage(int pageKey) async {
    try {
      if (_selectedCategoryId == 0 && hc.customPosts.isNotEmpty) {
        _selectedCategoryId = hc.customPosts.first['customCategoryId'] ?? 0;
      }
      
      String url = '/custom-post-category-paginated?category_id=$_selectedCategoryId&page=$pageKey&limit=20';
      if (_selectedTag.isNotEmpty) {
        url += '&tag=$_selectedTag';
      }
      final response = await ApiService.get(url);
      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        final List newItems = data['data'] ?? [];
        final currentPage = data['current_page'] ?? 1;
        final lastPage = data['last_page'] ?? 1;
        
        final isLastPage = currentPage >= lastPage;
        
        if (isLastPage) {
          _pagingController.appendLastPage(newItems);
        } else {
          final nextPageKey = pageKey + 1;
          _pagingController.appendPage(newItems, nextPageKey);
        }
      } else {
        _pagingController.error = 'failed_to_load_custom_posts'.trFormat;
      }
    } catch (error) {
      _pagingController.error = error;
    }
  }

  void _onCategorySelected(int categoryId) {
    if (_selectedCategoryId != categoryId) {
      setState(() {
        _selectedCategoryId = categoryId;
        _selectedTag = ''; // Reset tag when category changes
      });
      _pagingController.refresh();
    }
  }

  void _showProductPicker() {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (context) => ProductPickerSheet(
        frameId: '', // Will be set per-template; this is for "swap all in category"
        onProductSwapped: (swapResult) {
          // After swap, refresh the entire custom posts data to reflect new AI content
          setState(() {
            _currentProductName = swapResult['product_name'];
          });
          // Trigger a full refresh of custom posts from the server
          hc.fetchCustomPosts();
          _pagingController.refresh();
        },
      ),
    );
  }

  @override
  void dispose() {
    _pagingController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    if (AppConfig.isProduction) {
      return Scaffold(
        backgroundColor: Colors.white,
        appBar: AppBar(
          title: Text('custom_posts'.trFormat, style: const TextStyle(color: Colors.black)),
          backgroundColor: Colors.white,
          elevation: 0,
          iconTheme: const IconThemeData(color: Colors.black),
        ),
        body: ComingSoonWidget(title: 'custom_posts'.trFormat),
      );
    }

    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        title: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text('custom_posts'.trFormat, style: AppTextStyles.heading4),
            Text('ai_generated_content'.trFormat, style: AppTextStyles.cardSubtitle),
          ],
        ),
        leading: IconButton(
          icon: Icon(Icons.arrow_back, color: AppColors.gray900),
          onPressed: () => Get.back(),
        ),
        elevation: 0,
      ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: _showProductPicker,
        backgroundColor: const Color(0xFF6366F1),
        icon: const Icon(Icons.swap_horiz, color: Colors.white, size: 20),
        label: Text(
          _currentProductName ?? 'change_product'.trFormat,
          style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w700, fontSize: 13),
        ),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      ),
      body: Obx(() {
        if (hc.customPosts.isEmpty) {
          return Center(
            child: Text('no_custom_posts_available'.trFormat, style: AppTextStyles.bodyMedium),
          );
        }

        final selectedCategory = hc.customPosts.firstWhere(
          (c) => c['customCategoryId'] == _selectedCategoryId,
          orElse: () => hc.customPosts.first,
        );

        final List<dynamic> categoryTags = selectedCategory['tags'] ?? [];

        return Column(
          children: [
            // Filter Chips
            SizedBox(
              height: 60,
              child: ListView.separated(
                scrollDirection: Axis.horizontal,
                padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                itemCount: hc.customPosts.length,
                separatorBuilder: (_, __) => AppSpacing.gapH12,
                itemBuilder: (context, index) {
                  final cat = hc.customPosts[index];
                  final catId = cat['customCategoryId'] ?? 0;
                  final isSelected = _selectedCategoryId == catId;
                  return GestureDetector(
                    onTap: () => _onCategorySelected(catId),
                    child: Container(
                      padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 8),
                      decoration: BoxDecoration(
                        color: isSelected ? AppColors.indigo600 : Colors.white,
                        borderRadius: BorderRadius.circular(999),
                        border: isSelected ? null : Border.all(color: AppColors.gray200),
                        boxShadow: isSelected ? AppColors.primaryShadow : null,
                      ),
                      child: Center(
                        child: Text(
                          cat['customCategoryName'] ?? 'category'.trFormat,
                          style: TextStyle(
                            color: isSelected ? Colors.white : AppColors.gray500,
                            fontWeight: FontWeight.w700,
                            fontSize: 14,
                          ),
                        ),
                      ),
                    ),
                  );
                },
              ),
            ),
            
            // Tags Filter Chips (if any)
            if (categoryTags.isNotEmpty)
              SizedBox(
                height: 50,
                child: ListView.separated(
                  scrollDirection: Axis.horizontal,
                  padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                  itemCount: categoryTags.length + 1, // +1 for "All"
                  separatorBuilder: (_, __) => AppSpacing.gapH8,
                  itemBuilder: (context, index) {
                    final isAll = index == 0;
                    final tag = isAll ? '' : categoryTags[index - 1].toString();
                    final isSelected = _selectedTag == tag;
                    
                    return GestureDetector(
                      onTap: () {
                        if (_selectedTag != tag) {
                          setState(() {
                            _selectedTag = tag;
                          });
                          _pagingController.refresh();
                        }
                      },
                      child: Container(
                        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 6),
                        decoration: BoxDecoration(
                          color: isSelected ? AppColors.gray900 : Colors.white,
                          borderRadius: BorderRadius.circular(8),
                          border: isSelected ? null : Border.all(color: AppColors.gray300),
                        ),
                        child: Center(
                          child: Text(
                            isAll ? 'all'.trFormat : tag,
                            style: TextStyle(
                              color: isSelected ? Colors.white : AppColors.gray700,
                              fontWeight: FontWeight.w600,
                              fontSize: 12,
                            ),
                          ),
                        ),
                      ),
                    );
                  },
                ),
              ),
            
            // Grid
            Expanded(
              child: PagedGridView<int, dynamic>(
                pagingController: _pagingController,
                padding: const EdgeInsets.all(16),
                gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                  crossAxisCount: 2,
                  crossAxisSpacing: 16,
                  mainAxisSpacing: 16,
                  childAspectRatio: 0.75,
                ),
                builderDelegate: PagedChildBuilderDelegate<dynamic>(
                  noItemsFoundIndicatorBuilder: (context) => _buildEmptyState(),
                  firstPageProgressIndicatorBuilder: (context) => _buildSkeletonGrid(),
                  newPageProgressIndicatorBuilder: (context) => const Padding(
                    padding: EdgeInsets.all(16.0),
                    child: Center(child: CircularProgressIndicator()),
                  ),
                  itemBuilder: (context, post, index) {
                    final imgUrl = post['image'] ?? '';

                    return GestureDetector(
                      onTap: () async {
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
                        Get.find<AuthController>().checkAndNavigateToEditor(
                          '/editor?$editorQuery',
                          arguments: {
                            'frameData': {
                              ...post,
                              'customCategoryName': selectedCategory['customCategoryName'],
                            },
                          },
                        );
                      },
                      child: Stack(
                        children: [
                          Container(
                            decoration: BoxDecoration(
                              color: AppColors.gray200,
                              borderRadius: BorderRadius.circular(20),
                              boxShadow: AppColors.cardShadow,
                            ),
                            clipBehavior: Clip.antiAlias,
                            child: imgUrl.isNotEmpty
                                ? Image.network(imgUrl, fit: BoxFit.cover,
                                    width: double.infinity, height: double.infinity,
                                    loadingBuilder: (context, child, loadingProgress) {
                                      if (loadingProgress == null) return child;
                                      return _buildSkeletonItem();
                                    },
                                    errorBuilder: (_, __, ___) => const Center(child: Icon(Icons.image_outlined, color: Colors.grey, size: 36)),
                                  )
                                : const Center(child: Icon(Icons.image_outlined, color: Colors.grey, size: 36)),
                          ),
                          // Swap Product icon overlay
                          Positioned(
                            bottom: 8,
                            right: 8,
                            child: GestureDetector(
                              onTap: () {
                                final frameId = post['id']?.toString() ?? '';
                                showModalBottomSheet(
                                  context: context,
                                  isScrollControlled: true,
                                  backgroundColor: Colors.transparent,
                                  builder: (ctx) => ProductPickerSheet(
                                    frameId: frameId,
                                    onProductSwapped: (swapResult) {
                                      // Update this specific template's JSON with new AI content
                                      if (swapResult['json'] != null) {
                                        setState(() {
                                          post['json'] = swapResult['json'];
                                          _currentProductName = swapResult['product_name'];
                                        });
                                      }
                                    },
                                  ),
                                );
                              },
                              child: Container(
                                width: 32,
                                height: 32,
                                decoration: BoxDecoration(
                                  color: Colors.black.withOpacity(0.6),
                                  borderRadius: BorderRadius.circular(10),
                                ),
                                child: const Icon(Icons.swap_horiz, color: Colors.white, size: 18),
                              ),
                            ),
                          ),
                        ],
                      ),
                    );
                  },
                ),
              ),
            ),
          ],
        );
      }),
    );
  }

  Widget _buildSkeletonGrid() {
    return GridView.builder(
      padding: const EdgeInsets.all(16),
      gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
        crossAxisCount: 2,
        crossAxisSpacing: 16,
        mainAxisSpacing: 16,
        childAspectRatio: 0.75,
      ),
      itemCount: 8,
      itemBuilder: (context, index) => _buildSkeletonItem(),
    );
  }

  Widget _buildSkeletonItem() {
    return Container(
      decoration: BoxDecoration(
        color: Colors.grey.shade200,
        borderRadius: BorderRadius.circular(20),
      ),
    );
  }

  Widget _buildEmptyState() {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Container(
            width: 64, height: 64,
            decoration: BoxDecoration(shape: BoxShape.circle, color: AppColors.slate50),
            child: Icon(Icons.image_not_supported_outlined, color: AppColors.slate300, size: 32),
          ),
          AppSpacing.gapV16,
          Text('no_posts_found_for_category'.trFormat, style: TextStyle(color: AppColors.gray400, fontWeight: FontWeight.w600)),
        ],
      ),
    );
  }
}

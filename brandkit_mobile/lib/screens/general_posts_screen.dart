import 'package:flutter/material.dart';
import 'package:get/get.dart';
import '../utils/app_colors.dart';
import '../utils/app_text_styles.dart';
import '../utils/app_spacing.dart';

class GeneralPostsScreen extends StatefulWidget {
  final String? initialCategoryId;
  
  const GeneralPostsScreen({super.key, this.initialCategoryId});

  @override
  State<GeneralPostsScreen> createState() => _GeneralPostsScreenState();
}

class _GeneralPostsScreenState extends State<GeneralPostsScreen> {
  String _selectedCategoryId = 'all';
  bool _isLoading = true;
  List<dynamic> _categories = [];
  List<dynamic> _posts = [];

  @override
  void initState() {
    super.initState();
    if (widget.initialCategoryId != null) {
      _selectedCategoryId = widget.initialCategoryId!;
    }
    _fetchData();
  }

  Future<void> _fetchData() async {
    setState(() => _isLoading = true);
    // TODO: Connect to actual API endpoints for general posts
    await Future.delayed(const Duration(seconds: 1)); // Mock loading
    
    // Mock Categories
    _categories = [
      {'id': 'all', 'name': 'All'},
      {'id': '1', 'name': 'Motivation'},
      {'id': '2', 'name': 'Good Morning'},
      {'id': '3', 'name': 'Business Tips'},
    ];
    
    // Mock Posts
    _posts = List.generate(6, (index) => {
      'id': index,
      'title': 'Post $index',
      'image': 'https://via.placeholder.com/400x400'
    });
    
    setState(() => _isLoading = false);
  }

  void _onCategorySelected(String categoryId) {
    setState(() {
      _selectedCategoryId = categoryId;
    });
    _fetchData();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        title: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text('General Posts', style: AppTextStyles.heading4),
            Text('AI Generated Content', style: AppTextStyles.cardSubtitle),
          ],
        ),
        leading: IconButton(
          icon: Icon(Icons.arrow_back, color: AppColors.gray900),
          onPressed: () => Get.back(),
        ),
        actions: [
          Container(
            margin: const EdgeInsets.only(right: 16),
            width: 40, height: 40,
            decoration: BoxDecoration(shape: BoxShape.circle, color: AppColors.purple500.withValues(alpha: 0.1)),
            child: Icon(Icons.auto_awesome_rounded, color: AppColors.purple500, size: 20),
          ),
        ],
        elevation: 0,
      ),
      body: Column(
        children: [
          // Filter Chips
          if (!_isLoading && _categories.isNotEmpty)
            SizedBox(
              height: 60,
              child: ListView.separated(
                scrollDirection: Axis.horizontal,
                padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                itemCount: _categories.length,
                separatorBuilder: (_, __) => AppSpacing.gapH12,
                itemBuilder: (context, index) {
                  final cat = _categories[index];
                  final isSelected = _selectedCategoryId == cat['id'].toString();
                  return GestureDetector(
                    onTap: () => _onCategorySelected(cat['id'].toString()),
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
                          cat['name'],
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
            
          // Grid
          Expanded(
            child: _isLoading
                ? const Center(child: CircularProgressIndicator())
                : _posts.isEmpty
                    ? Center(
                        child: Column(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Container(
                              width: 64, height: 64,
                              decoration: BoxDecoration(shape: BoxShape.circle, color: AppColors.slate50),
                              child: Icon(Icons.image_not_supported_outlined, color: AppColors.slate300, size: 32),
                            ),
                            AppSpacing.gapV16,
                            Text('No posts found for this category', style: TextStyle(color: AppColors.gray400, fontWeight: FontWeight.w600)),
                          ],
                        ),
                      )
                    : GridView.builder(
                        padding: const EdgeInsets.all(16),
                        gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                          crossAxisCount: 2,
                          crossAxisSpacing: 16,
                          mainAxisSpacing: 16,
                          childAspectRatio: 1.0,
                        ),
                        itemCount: _posts.length,
                        itemBuilder: (context, index) {
                          // TODO: Replace with actual advanced custom post rendering in Phase 5
                          return Container(
                            decoration: BoxDecoration(
                              color: AppColors.gray200,
                              borderRadius: BorderRadius.circular(20),
                              image: DecorationImage(
                                image: NetworkImage(_posts[index]['image']),
                                fit: BoxFit.cover,
                              ),
                            ),
                          );
                        },
                      ),
          ),
        ],
      ),
    );
  }
}

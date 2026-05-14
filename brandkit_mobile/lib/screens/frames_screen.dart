import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:cached_network_image/cached_network_image.dart';
import '../utils/app_colors.dart';
import '../utils/app_text_styles.dart';
import '../utils/app_spacing.dart';
import '../services/api_service.dart';
import '../controllers/home_controller.dart';
import 'package:shared_preferences/shared_preferences.dart';

class FramesScreen extends StatefulWidget {
  const FramesScreen({super.key});

  @override
  State<FramesScreen> createState() => _FramesScreenState();
}

class _FramesScreenState extends State<FramesScreen> with SingleTickerProviderStateMixin {
  final HomeController hc = Get.find<HomeController>();
  late TabController _tabController;
  
  bool _isLoading = true;
  List<dynamic> _allFrames = [];
  List<String> _likedFrameIds = [];
  
  String _selectedTheme = 'all'; // 'all', 'light', 'dark'

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 2, vsync: this);
    _loadFrames();
  }
  
  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  Future<void> _loadFrames() async {
    setState(() => _isLoading = true);
    try {
      String bcId = hc.businessCategoryId.value;
      
      // Fetch all frames
      final framesRes = await ApiService.get('/get-all-frames?business_category_id=$bcId');
      debugPrint("Frames API status: ${framesRes.statusCode}");
      if (framesRes.statusCode == 200) {
        final data = jsonDecode(framesRes.body);
        debugPrint("Frames API data keys: ${data.keys}");
        if (data['success'] == true) {
          _allFrames = List<dynamic>.from(data['data'] ?? []);
          debugPrint("Total frames loaded: ${_allFrames.length}");
          if (_allFrames.isNotEmpty) {
            debugPrint("First frame keys: ${_allFrames[0].keys}");
          }
        }
      } else {
        debugPrint("Frames API error body: ${framesRes.body}");
      }
      
      // Fetch favorites
      final prefs = await SharedPreferences.getInstance();
      final userId = prefs.getString('userId') ?? '';
      final favRes = await ApiService.get('/user-favorites?userId=$userId');
      if (favRes.statusCode == 200) {
        final data = jsonDecode(favRes.body);
        if (data['success'] == true) {
          _likedFrameIds = (data['data'] as List<dynamic>).map((e) => e.toString()).toList();
        }
      }
    } catch (e, stack) {
      debugPrint("Error loading frames: $e");
      debugPrint("Stack: $stack");
    } finally {
      setState(() => _isLoading = false);
    }
  }

  Future<void> _toggleFavorite(String frameId) async {
    bool isCurrentlyLiked = _likedFrameIds.contains(frameId);
    
    // Optimistic update
    setState(() {
      if (isCurrentlyLiked) {
        _likedFrameIds.remove(frameId);
      } else {
        _likedFrameIds.add(frameId);
      }
    });
    
    try {
      final prefs = await SharedPreferences.getInstance();
      final userId = prefs.getString('userId') ?? '';
      final res = await ApiService.post('/user-favorite-frame', {
        'userId': userId,
        'frameId': frameId
      });
      if (res.statusCode != 200) {
        throw Exception('Failed to update favorite');
      }
    } catch (e) {
      // Revert on error
      setState(() {
        if (isCurrentlyLiked) {
          _likedFrameIds.add(frameId);
        } else {
          _likedFrameIds.remove(frameId);
        }
      });
      Get.snackbar('Error', 'Could not update favorite state.', backgroundColor: Colors.red, colorText: Colors.white);
    }
  }

  List<dynamic> _getFilteredFrames(bool onlyLiked) {
    // Determine active requirements based on hidden fields
    int activeEmails = (hc.businessEmail.value.isNotEmpty ? 1 : 0) + hc.extraEmails.length;
    int activePhones = (hc.businessPhone.value.isNotEmpty ? 1 : 0) + hc.extraPhones.length;
    int activeWebsites = (hc.businessWebsite.value.isNotEmpty ? 1 : 0) + hc.extraWebsites.length;
    int activeAddresses = (hc.businessAddress.value.isNotEmpty ? 1 : 0) + hc.extraAddresses.length;
    
    final hf = hc.hiddenFrameFields;
    if (hf['emails'] != null) activeEmails -= (hf['emails'] as List).length;
    if (hf['mobile_numbers'] != null) activePhones -= (hf['mobile_numbers'] as List).length;
    if (hf['websites'] != null) activeWebsites -= (hf['websites'] as List).length;
    if (hf['addresses'] != null) activeAddresses -= (hf['addresses'] as List).length;
    
    activeEmails = activeEmails.clamp(0, 99);
    activePhones = activePhones.clamp(0, 99);
    activeWebsites = activeWebsites.clamp(0, 99);
    activeAddresses = activeAddresses.clamp(0, 99);

    return _allFrames.where((frame) {
      if (onlyLiked && !_likedFrameIds.contains(frame['id']?.toString())) return false;
      
      if (_selectedTheme != 'all' && frame['theme'] != 'all' && frame['theme'] != _selectedTheme) {
        return false;
      }
      
      int reqEmail = int.tryParse(frame['req_email']?.toString() ?? '0') ?? 0;
      int reqPhone = int.tryParse(frame['req_phone']?.toString() ?? '0') ?? 0;
      int reqWeb = int.tryParse(frame['req_website']?.toString() ?? '0') ?? 0;
      int reqAddress = int.tryParse(frame['req_address']?.toString() ?? '0') ?? 0;
      
      if (reqEmail != activeEmails) return false;
      if (reqPhone != activePhones) return false;
      if (reqWeb != activeWebsites) return false;
      if (reqAddress != activeAddresses) return false;
      
      return true;
    }).toList();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        title: Text('Frames', style: AppTextStyles.heading4),
        leading: IconButton(
          icon: Icon(Icons.arrow_back, color: AppColors.gray900),
          onPressed: () => Get.back(),
        ),
        bottom: TabBar(
          controller: _tabController,
          labelColor: AppColors.indigo600,
          unselectedLabelColor: AppColors.gray500,
          indicatorColor: AppColors.indigo600,
          tabs: const [
            Tab(text: 'All Frames'),
            Tab(text: 'Liked Frames'),
          ],
        ),
      ),
      body: Column(
        children: [
          _buildFilters(),
          Expanded(
            child: _isLoading 
              ? const Center(child: CircularProgressIndicator())
              : TabBarView(
                  controller: _tabController,
                  children: [
                    _buildFramesGrid(false),
                    _buildFramesGrid(true),
                  ],
                ),
          ),
        ],
      ),
    );
  }

  Widget _buildFilters() {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
      color: Colors.white,
      child: Row(
        children: [
          const Text('Theme:', style: TextStyle(fontWeight: FontWeight.bold)),
          const SizedBox(width: 12),
          _buildFilterChip('All', 'all'),
          const SizedBox(width: 8),
          _buildFilterChip('Light', 'light'),
          const SizedBox(width: 8),
          _buildFilterChip('Dark', 'dark'),
        ],
      ),
    );
  }

  Widget _buildFilterChip(String label, String value) {
    bool isSelected = _selectedTheme == value;
    return GestureDetector(
      onTap: () => setState(() => _selectedTheme = value),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
        decoration: BoxDecoration(
          color: isSelected ? AppColors.indigo600 : AppColors.gray100,
          borderRadius: BorderRadius.circular(20),
        ),
        child: Text(
          label,
          style: TextStyle(
            color: isSelected ? Colors.white : AppColors.gray700,
            fontSize: 13,
            fontWeight: isSelected ? FontWeight.w600 : FontWeight.normal,
          ),
        ),
      ),
    );
  }

  Widget _buildFramesGrid(bool onlyLiked) {
    final frames = _getFilteredFrames(onlyLiked);
    
    if (frames.isEmpty) {
      return Center(
        child: Text(
          onlyLiked ? 'No liked frames found.' : 'No frames match your profile criteria.',
          style: AppTextStyles.bodyMedium.copyWith(color: AppColors.gray500),
        ),
      );
    }
    
    return GridView.builder(
      padding: const EdgeInsets.all(16),
      gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
        crossAxisCount: 2,
        crossAxisSpacing: 16,
        mainAxisSpacing: 16,
        childAspectRatio: 1,
      ),
      itemCount: frames.length,
      itemBuilder: (context, index) {
        final frame = frames[index];
        bool isLiked = _likedFrameIds.contains(frame['id']?.toString());
        
        return Container(
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(16),
            border: Border.all(color: AppColors.gray200),
            boxShadow: AppColors.cardShadow,
          ),
          clipBehavior: Clip.antiAlias,
          child: Stack(
            children: [
              Positioned.fill(
                child: CachedNetworkImage(
                  imageUrl: frame['thumbnail_url'] ?? frame['full_url'],
                  fit: BoxFit.cover,
                  errorWidget: (_, __, ___) => const Center(child: Icon(Icons.image_not_supported, color: Colors.grey)),
                ),
              ),
              Positioned(
                top: 8,
                right: 8,
                child: GestureDetector(
                  onTap: () => _toggleFavorite(frame['id']?.toString() ?? ''),
                  child: Container(
                    padding: const EdgeInsets.all(6),
                    decoration: BoxDecoration(
                      color: Colors.black.withOpacity(0.5),
                      shape: BoxShape.circle,
                    ),
                    child: AnimatedSwitcher(
                      duration: const Duration(milliseconds: 300),
                      transitionBuilder: (child, animation) => ScaleTransition(scale: animation, child: child),
                      child: Icon(
                        isLiked ? Icons.favorite : Icons.favorite_border,
                        key: ValueKey<bool>(isLiked),
                        color: isLiked ? Colors.redAccent : Colors.white,
                        size: 20,
                      ),
                    ),
                  ),
                ),
              ),
            ],
          ),
        );
      },
    );
  }
}

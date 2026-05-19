import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:google_mobile_ads/google_mobile_ads.dart';
import 'package:cached_network_image/cached_network_image.dart';
import '../services/api_service.dart';
import '../services/ad_service.dart';
import '../services/download_service.dart';
import '../controllers/ad_controller.dart';
import '../utils/app_colors.dart';
import 'editor_screen.dart';
import 'package:http/http.dart' as http;
import 'package:gal/gal.dart';
import 'dart:io';
import 'package:path_provider/path_provider.dart';

class DetailListScreen extends StatefulWidget {
  final String type; // 'festival', 'category', 'custom', 'business_custom'
  final int id;
  final String title;

  const DetailListScreen({
    super.key,
    required this.type,
    required this.id,
    required this.title,
  });

  @override
  State<DetailListScreen> createState() => _DetailListScreenState();
}

class _DetailListScreenState extends State<DetailListScreen> {
  bool isLoading = true;
  List<dynamic> frames = [];
  List<dynamic> videos = [];
  int selectedIndex = 0;
  String itemName = '';
  String itemImage = '';
  String activeTab = 'images'; // 'images' or 'videos'

  // ── Native Ad for template list ──
  NativeAd? _nativeAd;
  bool _isNativeAdLoaded = false;

  @override
  void initState() {
    super.initState();
    fetchFrames();
    _loadNativeAd();
    ApiService.trackActivity(
      action: 'select_template',
      itemType: widget.type,
      itemId: widget.id.toString(),
    );
  }

  @override
  void dispose() {
    _nativeAd?.dispose();
    super.dispose();
  }

  /// Load a Native Ad to inject into the template grid
  void _loadNativeAd() {
    _nativeAd = NativeAd(
      adUnitId: AdService.nativeAdUnitId,
      request: const AdRequest(),
      listener: NativeAdListener(
        onAdLoaded: (ad) {
          debugPrint('[DetailList] Native ad loaded.');
          setState(() => _isNativeAdLoaded = true);
        },
        onAdFailedToLoad: (ad, error) {
          debugPrint('[DetailList] Native ad failed: $error');
          ad.dispose();
          _isNativeAdLoaded = false;
        },
      ),
      nativeTemplateStyle: NativeTemplateStyle(
        templateType: TemplateType.small,
        mainBackgroundColor: Colors.white,
        cornerRadius: 12.0,
        callToActionTextStyle: NativeTemplateTextStyle(
          textColor: Colors.white,
          backgroundColor: AppColors.primary,
          style: NativeTemplateFontStyle.bold,
          size: 14.0,
        ),
        primaryTextStyle: NativeTemplateTextStyle(
          textColor: AppColors.textPrimary,
          style: NativeTemplateFontStyle.bold,
          size: 14.0,
        ),
        secondaryTextStyle: NativeTemplateTextStyle(
          textColor: AppColors.textSecondary,
          style: NativeTemplateFontStyle.normal,
          size: 12.0,
        ),
      ),
    )..load();
  }

  Future<void> fetchFrames() async {
    try {
      setState(() => isLoading = true);
      
      final response = await ApiService.get('/get-frames?type=${widget.type}&id=${widget.id}');
      
      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        setState(() {
          frames = data['frames'] ?? [];
          videos = data['videos'] ?? [];
          itemName = data['itemName'] ?? widget.title;
          itemImage = data['itemImage'] ?? '';
          selectedIndex = 0;
        });
      } else {
        debugPrint('Failed to fetch frames: ${response.statusCode} ${response.body}');
      }
    } catch (e) {
      debugPrint('Error fetching frames: $e');
    } finally {
      setState(() => isLoading = false);
    }
  }

  String get selectedImageUrl {
    if (activeTab == 'images') {
      if (frames.isNotEmpty && selectedIndex < frames.length) {
        return frames[selectedIndex]['image'] ?? '';
      }
    } else {
      if (videos.isNotEmpty && selectedIndex < videos.length) {
        return videos[selectedIndex]['videoUrl'] ?? '';
      }
    }
    return itemImage;
  }

  Future<void> _handleDownload() async {
    final url = selectedImageUrl;
    if (url.isEmpty) {
      Get.snackbar('Error', 'No design selected to download.', snackPosition: SnackPosition.BOTTOM);
      return;
    }

    // Phase 2: Show Interstitial ad on download for premium templates
    // when base limit is reached
    bool isPaid = false;
    if (activeTab == 'images') {
      final list = filteredFrames;
      isPaid = list.isNotEmpty && selectedIndex < list.length && (list[selectedIndex]['isPaid'] == true);
    } else {
      isPaid = videos.isNotEmpty && selectedIndex < videos.length && (videos[selectedIndex]['isPaid'] == true);
    }

    String featureKey = 'festival_post';
    if (widget.type == 'category' || widget.type == 'business_custom') {
      featureKey = 'business_category_post';
    }

    final adController = Get.find<AdController>();
    adController.handlePremiumDownloadAd(
      feature: featureKey,
      isPaid: isPaid,
    );

    Get.snackbar('Download', 'Downloading design...', snackPosition: SnackPosition.BOTTOM);
    
    try {
      final response = await http.get(Uri.parse(url));
      if (response.statusCode == 200) {
        final fileName = "brandkit_design_${DateTime.now().millisecondsSinceEpoch}";
        final isVideo = url.toLowerCase().endsWith('.mp4');
        if (isVideo) {
          final tempDir = await getTemporaryDirectory();
          final tempFile = File('${tempDir.path}/$fileName.mp4');
          await tempFile.writeAsBytes(response.bodyBytes);
          await Gal.putVideo(tempFile.path);
        } else {
          await Gal.putImageBytes(
            response.bodyBytes,
            name: fileName,
          );
        }
        
        await DownloadService.saveDownload(
          response.bodyBytes,
          isVideo: isVideo,
          fileName: fileName,
        );
        ApiService.trackActivity(
          action: 'download_template',
          itemType: widget.type,
          itemId: widget.id.toString(),
        );
        Get.snackbar('Success', 'Design saved to gallery successfully!', snackPosition: SnackPosition.BOTTOM);
      } else {
        Get.snackbar('Error', 'Failed to download file.', snackPosition: SnackPosition.BOTTOM);
      }
    } catch (e) {
      debugPrint('Download error: $e');
      Get.snackbar('Error', 'Failed to save design.', snackPosition: SnackPosition.BOTTOM);
    }
  }

  /// Build the grid items list, injecting a native ad after every 6th real item
  List<Widget> _buildGridItems() {
    List<Widget> items = [];
    int nativeAdInsertIndex = 6; // Insert native ad after 6th frame
    
    final currentList = activeTab == 'images' ? frames : videos;

    for (int i = 0; i < currentList.length; i++) {
      // Insert native ad at the designated position
      if (i == nativeAdInsertIndex && _isNativeAdLoaded && _nativeAd != null) {
        items.add(
          Container(
            decoration: BoxDecoration(
              borderRadius: BorderRadius.circular(12),
              color: Colors.white,
              border: Border.all(color: Colors.grey.shade200),
            ),
            clipBehavior: Clip.antiAlias,
            child: AdWidget(ad: _nativeAd!),
          ),
        );
      }

      final item = currentList[i];
      final isSelected = i == selectedIndex;

      if (activeTab == 'videos') {
        final videoUrl = item['videoUrl'] ?? '';
        items.add(
          GestureDetector(
            onTap: () {
              setState(() => selectedIndex = i);
            },
            child: Container(
              decoration: BoxDecoration(
                borderRadius: BorderRadius.circular(12),
                border: Border.all(
                  color: isSelected ? AppColors.primary : Colors.transparent,
                  width: 3,
                ),
                color: Colors.black87,
              ),
              clipBehavior: Clip.antiAlias,
              child: Stack(
                fit: StackFit.expand,
                children: [
                  const Center(
                    child: Icon(Icons.play_circle_fill, color: Colors.white70, size: 48),
                  ),
                  // Paid badge
                  Positioned(
                    top: 6,
                    right: 6,
                    child: Container(
                      padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 3),
                      decoration: BoxDecoration(
                        color: item['isPaid'] == true ? Colors.black45 : Colors.green.withOpacity(0.8),
                        borderRadius: BorderRadius.circular(6),
                      ),
                      child: Text(
                        item['isPaid'] == true ? 'PREMIUM' : 'FREE',
                        style: const TextStyle(fontSize: 9, fontWeight: FontWeight.w900, color: Colors.white, letterSpacing: 0.5),
                      ),
                    ),
                  ),
                  // Selected checkmark
                  if (isSelected)
                    Positioned(
                      bottom: 4,
                      right: 4,
                      child: Container(
                        width: 24,
                        height: 24,
                        decoration: BoxDecoration(
                          color: AppColors.primary,
                          shape: BoxShape.circle,
                        ),
                        child: const Icon(Icons.check, color: Colors.white, size: 14),
                      ),
                    ),
                ],
              ),
            ),
          ),
        );
      } else {
        final imgUrl = item['image'] ?? '';
        items.add(
          GestureDetector(
            onTap: () {
              setState(() => selectedIndex = i);
            },
            child: Container(
              decoration: BoxDecoration(
                borderRadius: BorderRadius.circular(12),
                border: Border.all(
                  color: isSelected ? AppColors.primary : Colors.transparent,
                  width: 3,
                ),
                color: const Color(0xFFF1F5F9),
              ),
              clipBehavior: Clip.antiAlias,
              child: Stack(
                fit: StackFit.expand,
                children: [
                  imgUrl.isNotEmpty
                      ? CachedNetworkImage(
                          imageUrl: imgUrl,
                          fit: BoxFit.cover,
                          placeholder: (context, url) => const Center(child: CircularProgressIndicator(strokeWidth: 2)),
                          errorWidget: (context, url, error) => const Center(child: Icon(Icons.broken_image, color: Colors.grey)),
                        )
                      : const Center(child: Icon(Icons.image, color: Colors.grey, size: 30)),
                  // Paid badge
                  if (item['isPaid'] == true)
                    Positioned(
                      top: 4,
                      right: 4,
                      child: Container(
                        padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                        decoration: BoxDecoration(
                          color: Colors.amber,
                          borderRadius: BorderRadius.circular(4),
                        ),
                        child: const Text('PRO', style: TextStyle(fontSize: 8, fontWeight: FontWeight.w900, color: Colors.white)),
                      ),
                    ),
                  // Selected checkmark
                  if (isSelected)
                    Positioned(
                      bottom: 4,
                      right: 4,
                      child: Container(
                        width: 24,
                        height: 24,
                        decoration: BoxDecoration(
                          color: AppColors.primary,
                          shape: BoxShape.circle,
                        ),
                        child: const Icon(Icons.check, color: Colors.white, size: 14),
                      ),
                    ),
                ],
              ),
            ),
          ),
        );
      }
    }

    return items;
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.white,
      body: isLoading
          ? const Center(child: CircularProgressIndicator())
          : Column(
              children: [
                // Header (matching web's app-header)
                SafeArea(
                  bottom: false,
                  child: Container(
                    height: 56,
                    decoration: BoxDecoration(
                      color: Colors.white,
                      border: Border(bottom: BorderSide(color: Colors.grey.shade100)),
                    ),
                    padding: const EdgeInsets.symmetric(horizontal: 8),
                    child: Row(
                      children: [
                        IconButton(
                          icon: const Icon(Icons.chevron_left, color: Color(0xFF334155), size: 28),
                          onPressed: () => Navigator.pop(context),
                        ),
                        Expanded(
                          child: Text(
                            'Select Design',
                            style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w700, color: Color(0xFF1E293B)),
                            overflow: TextOverflow.ellipsis,
                          ),
                        ),
                        // Download button — triggers interstitial ad
                        _buildHeaderButton(
                          icon: Icons.download_outlined,
                          bgColor: const Color(0xFFF1F5F9),
                          iconColor: const Color(0xFF475569),
                          onTap: _handleDownload,
                        ),
                        const SizedBox(width: 8),
                        // Next button (opens editor)
                        GestureDetector(
                          onTap: () async {
                            if (activeTab == 'videos') {
                              Get.snackbar('Notice', 'Videos cannot be edited. Please download directly.', snackPosition: SnackPosition.BOTTOM);
                              return;
                            }

                            Map<String, dynamic>? frameData;
                            if (frames.isNotEmpty && selectedIndex < frames.length) {
                              frameData = frames[selectedIndex];
                            } else if (itemImage.isNotEmpty) {
                              // Fallback to the category image if no frames are available
                              frameData = {
                                'frameId': widget.id,
                                'type': widget.type,
                                'image': itemImage,
                                'language': 'All',
                                'languageId': 0,
                                'isPaid': false,
                                'height': 1080,
                                'width': 1080,
                                'imageType': 'square',
                                'aspectRatio': '1:1',
                              };
                            }

                            if (frameData != null) {
                              final AdController adController = Get.find<AdController>();
                              
                              bool isPaid = false;
                              if (activeTab == 'images') {
                                isPaid = frames.isNotEmpty && selectedIndex < frames.length && (frames[selectedIndex]['isPaid'] == true);
                              } else {
                                isPaid = videos.isNotEmpty && selectedIndex < videos.length && (videos[selectedIndex]['isPaid'] == true);
                              }
                              
                              // Map widget.type to feature key
                              String featureKey = 'festival_post';
                              if (widget.type == 'category' || widget.type == 'business_custom') {
                                featureKey = 'business_category_post';
                              }

                              await adController.handlePostAccess(
                                context: context,
                                feature: featureKey,
                                isPaid: isPaid,
                                onAccessGranted: () {
                                  final fc = adController.adConfig.value?.features[featureKey];
                                  if (fc != null && fc.baseLimit > 0) {
                                    Get.snackbar(
                                      'Usage Update', 
                                      '${fc.used}/${fc.baseLimit} usage remaining.',
                                      snackPosition: SnackPosition.BOTTOM,
                                      backgroundColor: Colors.black87,
                                      colorText: Colors.white,
                                      margin: const EdgeInsets.all(16),
                                      borderRadius: 8,
                                      duration: const Duration(seconds: 2),
                                    );
                                  }

                                  Get.toNamed('/editor', arguments: {
                                    'type': widget.type,
                                    'id': widget.id,
                                    'frameData': frameData!,
                                    'designUrl': selectedImageUrl.isNotEmpty ? selectedImageUrl : itemImage,
                                  });
                                }
                              );
                            } else {
                              Get.snackbar('Notice', 'No design available to edit.');
                            }
                          },
                          child: Container(
                            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                            decoration: BoxDecoration(
                              color: AppColors.primary,
                              borderRadius: BorderRadius.circular(8),
                            ),
                            child: Row(
                              children: const [
                                Icon(Icons.edit, color: Colors.white, size: 18),
                                SizedBox(width: 6),
                                Text('Next', style: TextStyle(color: Colors.white, fontWeight: FontWeight.w700, fontSize: 14)),
                              ],
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                ),

                // Preview Section (matching web's preview-section)
                Container(
                  color: const Color(0xFFF1F5F9),
                  width: double.infinity,
                  padding: const EdgeInsets.all(24),
                  child: Center(
                    child: Container(
                      constraints: const BoxConstraints(maxHeight: 350),
                      decoration: BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.circular(16),
                        boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.1), blurRadius: 30, offset: const Offset(0, 10))],
                        border: Border.all(color: Colors.black.withOpacity(0.05)),
                      ),
                      clipBehavior: Clip.antiAlias,
                      child: selectedImageUrl.isNotEmpty
                          ? CachedNetworkImage(
                              imageUrl: selectedImageUrl,
                              fit: BoxFit.cover,
                              width: double.infinity,
                              placeholder: (context, url) => const Center(child: CircularProgressIndicator()),
                              errorWidget: (context, url, error) => _buildPreviewPlaceholder(),
                            )
                          : _buildPreviewPlaceholder(),
                    ),
                  ),
                ),

                // Scroll Content (matching web's scroll-content)
                Expanded(
                  child: Column(
                    children: [
                      // Filter Tabs (Images / Videos)
                      Container(
                        decoration: BoxDecoration(
                          color: Colors.white,
                          border: Border(bottom: BorderSide(color: const Color(0xFFF8FAFC))),
                        ),
                        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                        child: Row(
                          children: [
                            Expanded(
                              child: GestureDetector(
                                onTap: () => setState(() {
                                  activeTab = 'images';
                                  selectedIndex = 0;
                                }),
                                child: Container(
                                  padding: const EdgeInsets.symmetric(vertical: 10),
                                  decoration: BoxDecoration(
                                    color: activeTab == 'images' ? AppColors.primary : Colors.white,
                                    borderRadius: BorderRadius.circular(10),
                                    border: Border.all(color: activeTab == 'images' ? AppColors.primary : const Color(0xFFF1F5F9), width: 1.5),
                                    boxShadow: activeTab == 'images' ? [BoxShadow(color: AppColors.primary.withOpacity(0.2), blurRadius: 12, offset: const Offset(0, 4))] : null,
                                  ),
                                  child: Row(
                                    mainAxisAlignment: MainAxisAlignment.center,
                                    children: [
                                      Icon(Icons.image, size: 16, color: activeTab == 'images' ? Colors.white : const Color(0xFF64748B)),
                                      const SizedBox(width: 8),
                                      Text('Images', style: TextStyle(fontWeight: FontWeight.w700, fontSize: 14, color: activeTab == 'images' ? Colors.white : const Color(0xFF64748B))),
                                    ],
                                  ),
                                ),
                              ),
                            ),
                            const SizedBox(width: 10),
                            Expanded(
                              child: GestureDetector(
                                onTap: () => setState(() {
                                  activeTab = 'videos';
                                  selectedIndex = 0;
                                }),
                                child: Container(
                                  padding: const EdgeInsets.symmetric(vertical: 10),
                                  decoration: BoxDecoration(
                                    color: activeTab == 'videos' ? AppColors.primary : Colors.white,
                                    borderRadius: BorderRadius.circular(10),
                                    border: Border.all(color: activeTab == 'videos' ? AppColors.primary : const Color(0xFFF1F5F9), width: 1.5),
                                    boxShadow: activeTab == 'videos' ? [BoxShadow(color: AppColors.primary.withOpacity(0.2), blurRadius: 12, offset: const Offset(0, 4))] : null,
                                  ),
                                  child: Row(
                                    mainAxisAlignment: MainAxisAlignment.center,
                                    children: [
                                      Icon(Icons.play_arrow, size: 16, color: activeTab == 'videos' ? Colors.white : const Color(0xFF64748B)),
                                      const SizedBox(width: 8),
                                      Text('Videos', style: TextStyle(fontWeight: FontWeight.w700, fontSize: 14, color: activeTab == 'videos' ? Colors.white : const Color(0xFF64748B))),
                                    ],
                                  ),
                                ),
                              ),
                            ),
                          ],
                        ),
                      ),

                      // Frames Grid (with Native Ad injected)
                      Expanded(
                        child: (activeTab == 'images' && frames.isEmpty) || (activeTab == 'videos' && videos.isEmpty)
                            ? Center(
                                child: activeTab == 'images'
                                    ? Column(
                                        mainAxisAlignment: MainAxisAlignment.center,
                                        children: [
                                          Icon(Icons.image_not_supported_outlined, size: 64, color: Colors.grey.shade300),
                                          const SizedBox(height: 16),
                                          Text('No designs found', style: TextStyle(fontSize: 16, color: AppColors.textSecondary, fontWeight: FontWeight.w600)),
                                          const SizedBox(height: 8),
                                          Text('Designs for "${widget.title}" will appear here', style: TextStyle(fontSize: 13, color: AppColors.textMuted)),
                                        ],
                                      )
                                    : Column(
                                        mainAxisAlignment: MainAxisAlignment.center,
                                        children: [
                                          Container(
                                            width: 72,
                                            height: 72,
                                            decoration: const BoxDecoration(
                                              shape: BoxShape.circle,
                                              gradient: LinearGradient(colors: [Color(0xFFF1F5F9), Color(0xFFE2E8F0)]),
                                            ),
                                            child: const Icon(Icons.videocam_off_outlined, size: 32, color: Color(0xFF94A3B8)),
                                          ),
                                          const SizedBox(height: 16),
                                          const Text('No Videos Yet', style: TextStyle(fontSize: 16, color: Color(0xFF475569), fontWeight: FontWeight.w700)),
                                          const SizedBox(height: 8),
                                          const Text('Videos for this post will appear here\nonce they\'re added', textAlign: TextAlign.center, style: TextStyle(fontSize: 13, color: Color(0xFF94A3B8), height: 1.5)),
                                        ],
                                      ),
                              )
                            : GridView.count(
                                padding: const EdgeInsets.all(16),
                                crossAxisCount: activeTab == 'videos' ? 2 : 3,
                                crossAxisSpacing: 10,
                                mainAxisSpacing: 10,
                                childAspectRatio: activeTab == 'videos' ? 9 / 16 : 4 / 5,
                                children: _buildGridItems(),
                              ),
                      ),
                    ],
                  ),
                ),
              ],
            ),
    );
  }

  Widget _buildHeaderButton({required IconData icon, required Color bgColor, required Color iconColor, required VoidCallback onTap}) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        width: 38,
        height: 38,
        decoration: BoxDecoration(color: bgColor, borderRadius: BorderRadius.circular(8)),
        child: Icon(icon, size: 20, color: iconColor),
      ),
    );
  }

  Widget _buildPreviewPlaceholder() {
    if (activeTab == 'videos') {
      return Container(
        height: 250,
        color: Colors.black87,
        child: Center(
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              const Icon(Icons.play_circle_fill, size: 64, color: Colors.white70),
              const SizedBox(height: 12),
              Text('Video Preview', style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w600, color: Colors.white)),
            ],
          ),
        ),
      );
    }
    
    return Container(
      height: 250,
      color: const Color(0xFFF1F5F9),
      child: Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(Icons.image_outlined, size: 64, color: Colors.grey.shade300),
            const SizedBox(height: 12),
            Text(widget.title, style: TextStyle(fontSize: 16, fontWeight: FontWeight.w600, color: AppColors.textSecondary)),
          ],
        ),
      ),
    );
  }
}

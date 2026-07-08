import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:google_mobile_ads/google_mobile_ads.dart';
import 'package:cached_network_image/cached_network_image.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../services/api_service.dart';
import '../services/ad_service.dart';
import '../services/download_service.dart';
import '../controllers/ad_controller.dart';
import '../controllers/auth_controller.dart';
import '../controllers/subscription_controller.dart';
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
  String imageFilter = 'Normal'; // 'Normal' or 'AI'
  String selectedLanguage = 'All';
  List<String> availableLanguages = ['All'];
  List<String> preferredLanguages = [];

  Future<void> _loadPreferredLanguages() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final saved = prefs.getStringList('selectedLanguages');
      if (saved != null) {
        setState(() {
          preferredLanguages = saved;
        });
      }
    } catch (e) {
      debugPrint('Error loading preferred languages: $e');
    }
  }

  List<dynamic> get currentFrames {
    if (activeTab == 'images') {
      List<dynamic> filtered = frames.where((f) {
        bool isAiFrame = f['isAi'] == true;
        bool matchesAi = imageFilter == 'AI' ? isAiFrame : !isAiFrame;
        if (!matchesAi) return false;

        // Language filtering:
        final frameLang = (f['language'] ?? '').toString().trim().toLowerCase();
        
        if (selectedLanguage != 'All') {
          if (frameLang != selectedLanguage.toLowerCase()) return false;
        } else {
          if (preferredLanguages.isNotEmpty) {
            bool hasAll = preferredLanguages.any((pref) => pref.trim().toLowerCase() == 'all');
            if (!hasAll) {
              if (frameLang != 'all' && frameLang.isNotEmpty) {
                bool match = preferredLanguages.any((pref) => pref.trim().toLowerCase() == frameLang);
                if (!match) return false;
              }
            }
          }
        }
        return true;
      }).toList();
      debugPrint('[DetailList] Filtered frames: ${filtered.length} out of ${frames.length}');
      return filtered;
    }
    return videos;
  }

  // ── Native Ad for template list ──
  NativeAd? _nativeAd;
  bool _isNativeAdLoaded = false;

  @override
  void initState() {
    super.initState();
    _loadPreferredLanguages().then((_) {
      fetchFrames();
    });
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
      
      http.Response response;
      if (widget.type == 'greeting') {
        response = await ApiService.get('/get_greetings_by_category?id=${widget.id}');
      } else {
        response = await ApiService.get('/get-frames?type=${widget.type}&id=${widget.id}');
      }
      
      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        debugPrint('[DetailList] Fetched frames! Total count: ${data['frames']?.length ?? 0}');
        setState(() {
          frames = data['frames'] ?? [];
          videos = data['videos'] ?? [];
          
          Set<String> langs = {'All'};
          for (var f in frames) {
            String l = (f['language'] ?? '').toString().trim();
            if (l.isNotEmpty && l.toLowerCase() != 'all') {
              l = l[0].toUpperCase() + l.substring(1).toLowerCase();
              langs.add(l);
            }
          }
          availableLanguages = langs.toList();
          
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
    final list = currentFrames;
    if (activeTab == 'images') {
      if (list.isNotEmpty && selectedIndex < list.length) {
        return list[selectedIndex]['image'] ?? '';
      }
    } else {
      if (list.isNotEmpty && selectedIndex < list.length) {
        return list[selectedIndex]['videoUrl'] ?? '';
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
    final list = currentFrames;
    if (list.isNotEmpty && selectedIndex < list.length) {
      isPaid = list[selectedIndex]['isPaid'] == true;
    }
    if (widget.type == 'custom' || widget.type == 'business_custom') {
      isPaid = true; // All custom templates are paid
    }

    String featureKey = 'festival_post';
    if (widget.type == 'category' || widget.type == 'business_custom') {
      featureKey = 'category_post';
    }
    if (widget.type == 'custom') {
      featureKey = 'custom_post';
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
        final fileName = "artera_design_${DateTime.now().millisecondsSinceEpoch}";
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
        String trackItemId = widget.id.toString();
        if (list.isNotEmpty && selectedIndex < list.length) {
          trackItemId = list[selectedIndex]['frameId']?.toString() ?? widget.id.toString();
        }

        ApiService.trackActivity(
          action: 'download_template',
          itemType: widget.type,
          itemId: trackItemId,
          isPremium: isPaid,
        );

        // Refresh subscription limits so usage counters update immediately in UI
        try {
          await Get.find<SubscriptionController>().refreshFromApi();
        } catch (_) {}

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
    
    final currentList = currentFrames;

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
                        // Language Dropdown
                        if (availableLanguages.length > 1)
                          PopupMenuButton<String>(
                            initialValue: selectedLanguage,
                            onSelected: (String item) {
                              setState(() {
                                selectedLanguage = item;
                                selectedIndex = 0;
                              });
                            },
                            offset: const Offset(0, 40),
                            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                            color: Colors.white,
                            child: Container(
                              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
                              decoration: BoxDecoration(
                                color: const Color(0xFFF1F5F9),
                                borderRadius: BorderRadius.circular(8),
                              ),
                              child: Row(
                                children: [
                                  const Icon(Icons.language, size: 18, color: Color(0xFF475569)),
                                  const SizedBox(width: 6),
                                  Text(
                                    selectedLanguage,
                                    style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w600, color: Color(0xFF475569)),
                                  ),
                                  const SizedBox(width: 4),
                                  const Icon(Icons.arrow_drop_down, size: 16, color: Color(0xFF475569)),
                                ],
                              ),
                            ),
                            itemBuilder: (BuildContext context) => availableLanguages.map((String lang) {
                              return PopupMenuItem<String>(
                                value: lang,
                                child: Text(lang, style: TextStyle(
                                  fontWeight: selectedLanguage == lang ? FontWeight.bold : FontWeight.normal,
                                  color: selectedLanguage == lang ? AppColors.primary : Colors.black87,
                                )),
                              );
                            }).toList(),
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
                            final list = currentFrames;
                            if (activeTab == 'images' && list.isNotEmpty && selectedIndex < list.length) {
                              frameData = list[selectedIndex];
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
                              if (list.isNotEmpty && selectedIndex < list.length) {
                                final item = list[selectedIndex];
                                isPaid = item['isPaid'] == true || item['isPaid'] == '1' || item['isPaid'] == 1 || item['premium'] == true || item['premium'] == '1' || item['premium'] == 1;
                              }
                              if (widget.type == 'custom' || widget.type == 'business_custom') {
                                isPaid = true; // All custom templates are paid
                              }
                              
                              // Map widget.type to feature key
                              String featureKey = 'festival_post';
                              if (widget.type == 'category' || widget.type == 'business_custom') {
                                featureKey = 'category_post';
                              }
                              if (widget.type == 'custom') {
                                featureKey = 'custom_post';
                              }

                              final fc = adController.adConfig.value?.features[featureKey];
                              
                              void goToEditor() {
                                final editorQuery = Uri(queryParameters: {
                                  'type': widget.type,
                                  'id': widget.id.toString(),
                                  'designUrl': selectedImageUrl.isNotEmpty ? selectedImageUrl : itemImage,
                                }).query;
                                Get.find<AuthController>().checkAndNavigateToEditor(
                                  '/editor?$editorQuery',
                                  arguments: {
                                    'frameData': frameData!,
                                  },
                                );
                              }

                              // ── FREE TEMPLATES ──
                              // Never blocked by limit. No count is consumed.
                              if (!isPaid) {
                                goToEditor();
                                return;
                              }

                              // ── PRO (PAID) TEMPLATES ──
                              if (fc == null) {
                                // No config → treat as locked
                                adController.handlePostAccess(
                                  context: context,
                                  feature: featureKey,
                                  isPaid: isPaid,
                                  onAccessGranted: goToEditor,
                                );
                                return;
                              }

                              // Within base limit → direct access, no ad
                              final bool withinBase = fc.baseLimit > 0 && fc.used < fc.baseLimit;
                              if (withinBase) {
                                goToEditor();
                                return;
                              }

                              // Base limit exhausted (or base = 0) but ad slots remain → show ad
                              final bool adAvailable = fc.maxAdUses > 0 && fc.adUsed < fc.maxAdUses;
                              if (adAvailable) {
                                await adController.handlePostAccess(
                                  context: context,
                                  feature: featureKey,
                                  isPaid: isPaid,
                                  onAccessGranted: goToEditor,
                                );
                                return;
                              }

                              // Both base and ad limits exhausted → show Limit Reached
                              await adController.handlePostAccess(
                                context: context,
                                feature: featureKey,
                                isPaid: isPaid,
                                onAccessGranted: goToEditor,
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
                      if (widget.type != 'greeting')
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

                      // AI Filter (Only for Images)
                      if (activeTab == 'images' && widget.type != 'greeting')
                        Container(
                          decoration: BoxDecoration(
                            color: Colors.white,
                            border: Border(bottom: BorderSide(color: const Color(0xFFF8FAFC))),
                          ),
                          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                          child: Row(
                            children: [
                              _buildFilterChip('Normal', imageFilter == 'Normal', () {
                                setState(() {
                                  imageFilter = 'Normal';
                                  selectedIndex = 0;
                                });
                              }),
                              // const SizedBox(width: 8),
                              // _buildFilterChip('AI', imageFilter == 'AI', () {
                              //   setState(() {
                              //     imageFilter = 'AI';
                              //     selectedIndex = 0;
                              //   });
                              // }),
                            ],
                          ),
                        ),

                      // Frames Grid (with Native Ad injected)
                      Expanded(
                        child: currentFrames.isEmpty
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

  Widget _buildFilterChip(String label, bool isSelected, VoidCallback onTap) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 6),
        decoration: BoxDecoration(
          color: isSelected ? AppColors.primary.withOpacity(0.1) : Colors.white,
          borderRadius: BorderRadius.circular(20),
          border: Border.all(color: isSelected ? AppColors.primary : Colors.grey.shade300, width: 1.5),
        ),
        child: Text(
          label,
          style: TextStyle(
            fontSize: 13,
            fontWeight: isSelected ? FontWeight.w700 : FontWeight.w600,
            color: isSelected ? AppColors.primary : Colors.grey.shade600,
          ),
        ),
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

import 'dart:convert';
import 'package:flutter/foundation.dart';
import 'package:get/get.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../services/api_service.dart';

class HomeController extends GetxController {
  var isLoading = true.obs;
  var isFestivalLoading = false.obs;
  
  // Data observables
  var categories = [].obs;
  var upcomingFestivals = [].obs;
  var customCategories = [].obs;
  var profileCategories = [].obs;
  var customPosts = [].obs;
  var recentCustomPosts = [].obs;  // Recent 10 templates for "New Posts" section
  var stories = [].obs;
  var news = [].obs;
  var videos = [].obs;
  var notifications = [].obs;
  
  // Business info
  var businessName = ''.obs;
  var businessLogo = ''.obs;
  var businessEmail = ''.obs;
  var businessPhone = ''.obs;
  var businessWebsite = ''.obs;
  var businessAddress = ''.obs;
  var businessCategoryId = ''.obs;
  var businessId = ''.obs;
  
  // List of all businesses
  var businesses = [].obs;
  
  var extraEmails = <String>[].obs;
  var extraPhones = <String>[].obs;
  var extraWebsites = <String>[].obs;
  var extraAddresses = <String>[].obs;
  var hiddenFrameFields = <String, dynamic>{}.obs;

  // Search
  var searchQuery = ''.obs;
  
  // Selected date for festival calendar
  var selectedDateIndex = (-1).obs; // -1 means "Upcoming" (no specific date selected)
  
  // Flattened list of all custom templates across all categories
  List<dynamic> get allCustomTemplates {
    List<dynamic> all = [];
    for (var category in customPosts) {
      if (category['posts'] != null) {
        all.addAll(category['posts']);
      }
    }
    return all;
  }

  @override
  void onInit() {
    super.onInit();
    fetchHomeData();
    loadBusinessInfo();
  }

  void clearData() {
    businessName.value = '';
    businessLogo.value = '';
    businessEmail.value = '';
    businessPhone.value = '';
    businessWebsite.value = '';
    businessAddress.value = '';
    businessCategoryId.value = '';
    businessId.value = '';
    businesses.clear();
    
    extraEmails.clear();
    extraPhones.clear();
    extraWebsites.clear();
    extraAddresses.clear();
    hiddenFrameFields.clear();
    
    customPosts.clear();
    categories.clear();
    upcomingFestivals.clear();
    customCategories.clear();
    profileCategories.clear();
  }

  Future<void> loadBusinessInfo() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final userId = prefs.getString('userId') ?? '';
      final response = await ApiService.get('/business?userId=$userId');
      debugPrint('[HomeCtrl] Business API status: ${response.statusCode}');
      debugPrint('[HomeCtrl] Business API body: ${response.body}');
      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        
        // Store full list
        if (data is List) {
          businesses.value = data;
        } else if (data['data'] is List) {
          businesses.value = data['data'];
        } else {
          businesses.value = [data];
        }

        // Set default business to the first one (which should be the default)
        final biz = businesses.isNotEmpty ? businesses.first : {};
        
        businessName.value = biz['name']?.toString() ?? '';
        businessLogo.value = biz['logo']?.toString() ?? '';
        businessEmail.value = biz['email']?.toString() ?? '';
        businessPhone.value = biz['mobileNo']?.toString() ?? biz['mobile_no']?.toString() ?? '';
        businessWebsite.value = biz['website']?.toString() ?? '';
        businessAddress.value = biz['address']?.toString() ?? '';
        businessId.value = biz['id']?.toString() ?? '';
        
        // businessCategoryId comes from nested businessCategory object OR top-level
        if (biz['businessCategory'] != null && biz['businessCategory']['businessCategoryId'] != null) {
          businessCategoryId.value = biz['businessCategory']['businessCategoryId'].toString();
        } else if (biz['business_category_id'] != null) {
          businessCategoryId.value = biz['business_category_id'].toString();
        } else if (biz['businessCategoryId'] != null) {
          businessCategoryId.value = biz['businessCategoryId'].toString();
        }
        
        extraEmails.value = (biz['extra_emails'] as List<dynamic>? ?? []).map((e) => e.toString()).toList();
        extraPhones.value = (biz['extra_mobile_numbers'] as List<dynamic>? ?? []).map((e) => e.toString()).toList();
        extraWebsites.value = (biz['extra_websites'] as List<dynamic>? ?? []).map((e) => e.toString()).toList();
        extraAddresses.value = (biz['extra_addresses'] as List<dynamic>? ?? []).map((e) => e.toString()).toList();
        
        if (biz['hidden_frame_fields'] != null) {
          hiddenFrameFields.value = Map<String, dynamic>.from(biz['hidden_frame_fields']);
        } else {
          hiddenFrameFields.value = {};
        }
      }
    } catch (e) {
      // Business info fetch failed, not critical
    }
  }

  Future<void> fetchHomeData() async {
    try {
      isLoading(true);
      selectedDateIndex.value = -1; // Reset selection on refresh

      
      // 1. Fetch main home data
      final response = await ApiService.get('/get-home-data');
      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        categories.value = data['Category'] ?? [];
        upcomingFestivals.value = data['Festival'] ?? [];
        customCategories.value = data['BusinessCategory'] ?? [];
        profileCategories.value = data['ProfileBusinessCategory'] ?? [];
      }
      
      // 2. If festivals are empty from home data, try dedicated /festival endpoint
      if (upcomingFestivals.isEmpty) {
        try {
          final festivalResponse = await ApiService.get('/festival');
          if (festivalResponse.statusCode == 200) {
            final festivalData = jsonDecode(festivalResponse.body);
            if (festivalData is List) {
              upcomingFestivals.value = festivalData;
            }
          }
        } catch (e) {
          // Festival fetch failed
        }
      }
      
      // 3. If categories are empty, try dedicated /category endpoint
      if (categories.isEmpty) {
        try {
          final catResponse = await ApiService.get('/category');
          if (catResponse.statusCode == 200) {
            final catData = jsonDecode(catResponse.body);
            if (catData is List) {
              categories.value = catData;
            }
          }
        } catch (e) {
          // Category fetch failed
        }
      }
      
      // 4. Fetch custom posts for magic cloner (send userId for AI content injection if available)
      try {
        isLoading(true);
        final prefs = await SharedPreferences.getInstance();
        String userId = prefs.getString('userId') ?? '';
        
        // If userId is empty, wait briefly (useful for Web reloads)
        if (userId.isEmpty) {
          await Future.delayed(const Duration(milliseconds: 500));
          userId = prefs.getString('userId') ?? '';
        }

        debugPrint('[HomeCtrl] Fetching custom posts for userId: "$userId"');
        
        // Fetch custom posts regardless of userId. (userId is optional for AI replacement)
        final url = userId.isNotEmpty ? '/custom-post?userId=$userId' : '/custom-post';
        final customResponse = await ApiService.get(url);
        debugPrint('[HomeCtrl] Custom Post API status: ${customResponse.statusCode}');
        
        if (customResponse.statusCode == 200) {
          final customData = jsonDecode(customResponse.body);
          final List<dynamic> posts = customData['data'] ?? [];
          debugPrint('[HomeCtrl] Received ${posts.length} custom post categories');
          customPosts.value = posts;
          recentCustomPosts.value = customData['recent_posts'] ?? [];
        } else {
          debugPrint('[HomeCtrl] Failed to fetch custom posts: ${customResponse.body}');
        }
      } catch (e) {
        debugPrint('[HomeCtrl] Custom posts fetch error: $e');
      }

      // 5. Fetch stories
      try {
        final storyResponse = await ApiService.get('/story');
        if (storyResponse.statusCode == 200) {
          final storyData = jsonDecode(storyResponse.body);
          if (storyData is List) {
            stories.value = storyData;
          } else if (storyData['data'] is List) {
            stories.value = storyData['data'];
          }
        }
      } catch (e) {
        // Stories fetch failed
      }

      // 6. Fetch news
      try {
        final newsResponse = await ApiService.get('/news');
        if (newsResponse.statusCode == 200) {
          final newsData = jsonDecode(newsResponse.body);
          if (newsData is List) {
            news.value = newsData;
          } else if (newsData['data'] is List) {
            news.value = newsData['data'];
          }
        }
      } catch (e) {
        // News fetch failed
      }

      // 7. Fetch videos
      try {
        final videoResponse = await ApiService.get('/get-video');
        if (videoResponse.statusCode == 200) {
          final videoData = jsonDecode(videoResponse.body);
          if (videoData is List) {
            videos.value = videoData;
          } else if (videoData['data'] is List) {
            videos.value = videoData['data'];
          }
        }
      } catch (e) {
        // Videos fetch failed
      }
      // 8. Fetch notifications
      try {
        final notifResponse = await ApiService.get('/notifications');
        if (notifResponse.statusCode == 200) {
          final notifData = jsonDecode(notifResponse.body);
          if (notifData is List) {
            notifications.value = notifData;
          }
        }
      } catch (e) {
        // Notifications fetch failed
      }
    } catch (e) {
      Get.snackbar('Error', 'An error occurred while fetching data');
    } finally {
      isLoading(false);
    }
  }

  /// Re-fetch only the custom posts data (used after product swap to refresh AI content).
  /// More efficient than fetchHomeData() since it skips festivals, stories, news, etc.
  Future<void> fetchCustomPosts() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final userId = prefs.getString('userId') ?? '';
      final url = userId.isNotEmpty ? '/custom-post?userId=$userId' : '/custom-post';
      final response = await ApiService.get(url);
      
      if (response.statusCode == 200) {
        final customData = jsonDecode(response.body);
        final List<dynamic> posts = customData['data'] ?? [];
        debugPrint('[HomeCtrl] fetchCustomPosts: Refreshed ${posts.length} categories');
        customPosts.value = posts;
        recentCustomPosts.value = customData['recent_posts'] ?? [];
      }
    } catch (e) {
      debugPrint('[HomeCtrl] fetchCustomPosts error: $e');
    }
  }

  /// Fetch festivals filtered by date (matches web's fetchFestivalsByDate)
  Future<void> fetchFestivalsByDate(String date, int index) async {
    selectedDateIndex.value = index;
    isFestivalLoading.value = true;
    try {
      final response = await ApiService.get('/festival?date=$date');
      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data is List) {
          upcomingFestivals.value = data;
        } else if (data['data'] is List) {
          upcomingFestivals.value = data['data'];
        }
      } else {
        upcomingFestivals.value = [];
      }
    } catch (e) {
      upcomingFestivals.value = [];
    } finally {
      isFestivalLoading.value = false;
    }
  }

  /// Get the base URL for image assets
  String get uploadsBaseUrl {
    // Extract base URL from ApiService and build uploads path
    final apiBase = ApiService.baseUrl;
    final uri = Uri.parse(apiBase);
    final baseUrl = '${uri.scheme}://${uri.host}${uri.port != 80 && uri.port != 443 ? ':${uri.port}' : ''}/Artera/uploads';
    debugPrint('[HomeCtrl] Generated uploadsBaseUrl: $baseUrl');
    return baseUrl;
  }
}

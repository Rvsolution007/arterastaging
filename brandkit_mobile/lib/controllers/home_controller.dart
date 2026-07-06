import 'dart:convert';
import 'package:flutter/foundation.dart';
import 'package:get/get.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../services/api_service.dart';
import '../config/app_config.dart';
import 'subscription_controller.dart';
import '../services/notification_service.dart';

class HomeController extends GetxController {
  var isLoading = true.obs;
  var isFestivalLoading = false.obs;
  var showQuickStart = false.obs;
  
  // Data observables
  var categories = [].obs;
  var upcomingFestivals = [].obs;
  var customCategories = [].obs;
  var profileCategories = [].obs;
  var customPosts = [].obs;
  var greetingCategories = [].obs; // Added for Greetings
  var recentCustomPosts = [].obs;  // Recent 10 templates for "New Posts" section
  var stories = [].obs;
  var news = [].obs;
  var videos = [].obs;
  var notifications = [].obs;
  var languages = [].obs;

  
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

  var businessSubCategoryIds = <String>[].obs;
  var businessTypeIds = <String>[].obs;
  var products = [].obs;

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
    checkQuickStartVisibility();
    fetchHomeData();
    loadBusinessInfo();
    _refreshFcmToken();
  }

  Future<void> checkQuickStartVisibility() async {
    final prefs = await SharedPreferences.getInstance();
    final isHidden = prefs.getBool('quickstart_hidden') ?? false;
    showQuickStart.value = !isHidden;
  }
  
  Future<void> hideQuickStartPermanently() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setBool('quickstart_hidden', true);
    showQuickStart.value = false;
  }

  Future<void> _refreshFcmToken() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final userId = prefs.getString('userId');
      if (userId != null && userId.isNotEmpty) {
        final token = await NotificationService().getToken();
        if (token != null) {
          debugPrint("[HomeCtrl] Refreshing FCM Token on startup");
          await ApiService.post('/register-fcm', {
            'userId': userId,
            'fcmToken': token,
            'deviceId': 'flutter_device',
          });
        }
      }
    } catch (e) {
      debugPrint("[HomeCtrl] Failed to refresh FCM Token: $e");
    }
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

    businessSubCategoryIds.clear();
    businessTypeIds.clear();
    products.clear();
    
    customPosts.clear();
    greetingCategories.clear();
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

        businessSubCategoryIds.value = (biz['business_sub_category_ids'] as List<dynamic>? ?? []).map((e) => e.toString()).toList();
        businessTypeIds.value = (biz['business_type_ids'] as List<dynamic>? ?? []).map((e) => e.toString()).toList();
        products.value = biz['products'] as List<dynamic>? ?? [];
      }
    } catch (e) {
      // Business info fetch failed, not critical
    }
  }

  Future<void> fetchHomeData() async {
    try {
      isLoading(true);
      selectedDateIndex.value = -1; // Reset selection on refresh

      final prefs = await SharedPreferences.getInstance();
      final userId = prefs.getString('userId') ?? '';

      // ── PERF: Fire ALL API calls in parallel instead of sequential ──
      // Previously these were 9 sequential awaits (~4-5s total).
      // Now they all start at the same time and we await all together.
      final customUrl = userId.isNotEmpty ? '/custom-post-category?userId=$userId' : '/custom-post-category';
      
      final results = await Future.wait([
        ApiService.get('/get-home-data'),                  // [0] Main home data
        ApiService.get(customUrl),                         // [1] Custom posts
        ApiService.get('/get_greeting_categories'),        // [2] Greetings
        ApiService.get('/story'),                           // [3] Stories
        ApiService.get('/news'),                            // [4] News
        ApiService.get('/get-video'),                       // [5] Videos
        ApiService.get('/notifications'),                   // [6] Notifications
        ApiService.get('/language'),                        // [7] Languages
      ], eagerError: false);

      // ── Parse [0] Main home data ──
      if (results[0].statusCode == 200) {
        final data = jsonDecode(results[0].body);
        categories.value = data['Category'] ?? [];
        upcomingFestivals.value = data['Festival'] ?? [];
        customCategories.value = data['BusinessCategory'] ?? [];
        profileCategories.value = data['ProfileBusinessCategory'] ?? [];
      }

      // Also refresh subscription and limits silently
      try {
        if (Get.isRegistered<SubscriptionController>()) {
          Get.find<SubscriptionController>().refreshFromApi();
        }
      } catch (e) {
        debugPrint('[HomeCtrl] Failed to refresh subscription: $e');
      }

      // Fallback: If festivals are empty, try dedicated endpoint
      if (upcomingFestivals.isEmpty) {
        try {
          final festivalResponse = await ApiService.get('/festival');
          if (festivalResponse.statusCode == 200) {
            final festivalData = jsonDecode(festivalResponse.body);
            if (festivalData is List) {
              upcomingFestivals.value = festivalData;
            }
          }
        } catch (e) { /* Festival fetch failed */ }
      }

      // Fallback: If categories are empty, try dedicated endpoint
      if (categories.isEmpty) {
        try {
          final catResponse = await ApiService.get('/category');
          if (catResponse.statusCode == 200) {
            final catData = jsonDecode(catResponse.body);
            if (catData is List) {
              categories.value = catData;
            }
          }
        } catch (e) { /* Category fetch failed */ }
      }

      // ── Parse [1] Custom posts ──
      try {
        if (results[1].statusCode == 200) {
          final customData = jsonDecode(results[1].body);
          final List<dynamic> posts = customData['data'] ?? [];
          debugPrint('[HomeCtrl] Received ${posts.length} custom post categories');
          customPosts.value = posts;
          recentCustomPosts.value = customData['recent_posts'] ?? [];
        }
      } catch (e) {
        debugPrint('[HomeCtrl] Custom posts parse error: $e');
      }

      // ── Parse [2] Greetings ──
      try {
        if (results[2].statusCode == 200) {
          final greetingData = jsonDecode(results[2].body);
          final List<dynamic> posts = greetingData['data'] ?? [];
          debugPrint('[HomeCtrl] Received ${posts.length} greeting categories');
          greetingCategories.value = posts;
        }
      } catch (e) {
        debugPrint('[HomeCtrl] Greeting categories parse error: $e');
      }

      // ── Parse [3] Stories ──
      try {
        if (results[3].statusCode == 200) {
          final storyData = jsonDecode(results[3].body);
          if (storyData is List) {
            stories.value = storyData;
          } else if (storyData['data'] is List) {
            stories.value = storyData['data'];
          }
        }
      } catch (e) { /* Stories fetch failed */ }

      // ── Parse [4] News ──
      try {
        if (results[4].statusCode == 200) {
          final newsData = jsonDecode(results[4].body);
          if (newsData is List) {
            news.value = newsData;
          } else if (newsData['data'] is List) {
            news.value = newsData['data'];
          }
        }
      } catch (e) { /* News fetch failed */ }

      // ── Parse [5] Videos ──
      try {
        if (results[5].statusCode == 200) {
          final videoData = jsonDecode(results[5].body);
          if (videoData is List) {
            videos.value = videoData;
          } else if (videoData['data'] is List) {
            videos.value = videoData['data'];
          }
        }
      } catch (e) { /* Videos fetch failed */ }

      // ── Parse [6] Notifications ──
      try {
        if (results[6].statusCode == 200) {
          final notifData = jsonDecode(results[6].body);
          if (notifData is List) {
            notifications.value = notifData;
          }
        }
      } catch (e) { /* Notifications fetch failed */ }

      // ── Parse [7] Languages ──
      try {
        if (results[7].statusCode == 200) {
          final langData = jsonDecode(results[7].body);
          if (langData is List) {
            languages.value = langData;
          }
        }
      } catch (e) { /* Languages fetch failed */ }
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
      final url = userId.isNotEmpty ? '/custom-post-category?userId=$userId' : '/custom-post-category';
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
    final hostPart = '${uri.scheme}://${uri.host}${uri.port != 80 && uri.port != 443 ? ':${uri.port}' : ''}';
    // On local, uploads are at /Artera/uploads; on staging/prod, at /uploads
    final uploadsPath = AppConfig.isLocal ? '/Artera/uploads' : '/uploads';
    final baseUrl = '$hostPart$uploadsPath';
    debugPrint('[HomeCtrl] Generated uploadsBaseUrl: $baseUrl');
    return baseUrl;
  }
}

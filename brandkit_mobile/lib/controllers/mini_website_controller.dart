import 'dart:convert';
import 'package:get/get.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:flutter/material.dart';
import '../services/api_service.dart';
import 'home_controller.dart';

class MiniWebsiteController extends GetxController {
  var templates = [].obs;
  var myLinks = [].obs;
  var isLoading = false.obs;
  var isGenerating = false.obs;

  @override
  void onInit() {
    super.onInit();
    fetchTemplates();
    fetchMyLinks();
  }

  Future<void> fetchTemplates() async {
    try {
      isLoading.value = true;
      final response = await ApiService.get('/mini-website/templates');
      if (response.statusCode == 200) {
        final decoded = jsonDecode(response.body);
        templates.value = decoded['data'] ?? [];
      }
    } catch (e) {
      debugPrint('Error fetching templates: $e');
    } finally {
      isLoading.value = false;
    }
  }

  Future<void> fetchMyLinks() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final userId = prefs.getString('userId');
      if (userId == null) return;

      final response = await ApiService.get('/mini-website/my-links?user_id=$userId');
      if (response.statusCode == 200) {
        final decoded = jsonDecode(response.body);
        myLinks.value = decoded['data'] ?? [];
      }
    } catch (e) {
      debugPrint('Error fetching my links: $e');
    }
  }

  Future<bool> generateWebsite(int templateId) async {
    try {
      isGenerating.value = true;
      final prefs = await SharedPreferences.getInstance();
      final userId = prefs.getString('userId');
      
      final homeCtrl = Get.find<HomeController>();
      final businessId = homeCtrl.businessId.value;

      final response = await ApiService.post('/mini-website/generate', {
        'user_id': userId,
        'business_id': businessId,
        'mini_website_template_id': templateId,
      });

      if (response.statusCode == 200) {
        await fetchMyLinks();
        return true;
      }
      return false;
    } catch (e) {
      debugPrint('Error generating website: $e');
      return false;
    } finally {
      isGenerating.value = false;
    }
  }
}

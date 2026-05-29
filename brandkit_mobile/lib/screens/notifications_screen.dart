import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:cached_network_image/cached_network_image.dart';
import 'package:url_launcher/url_launcher.dart';
import '../utils/app_colors.dart';
import '../utils/app_text_styles.dart';
import '../utils/app_spacing.dart';
import '../services/api_service.dart';
import 'detail_list_screen.dart';

class NotificationsScreen extends StatefulWidget {
  const NotificationsScreen({super.key});

  @override
  State<NotificationsScreen> createState() => _NotificationsScreenState();
}

class _NotificationsScreenState extends State<NotificationsScreen> {
  bool _isLoading = true;
  List<dynamic> _notifications = [];

  @override
  void initState() {
    super.initState();
    _fetchNotifications();
  }

  Future<void> _fetchNotifications() async {
    try {
      final response = await ApiService.get('/notifications');
      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        setState(() {
          _notifications = data is List ? data : (data['data'] ?? []);
          _isLoading = false;
        });
      } else {
        setState(() => _isLoading = false);
      }
    } catch (e) {
      setState(() {
        _notifications = [];
        _isLoading = false;
      });
    }
  }

  /// Handle notification item tap — navigate based on type
  void _onNotificationTap(Map<String, dynamic> notif) {
    final String type = notif['type']?.toString() ?? '';
    final int typeId = int.tryParse(notif['type_id']?.toString() ?? '') ?? 0;
    final String title = notif['title']?.toString() ?? 'Details';

    debugPrint("Notification tap: type=$type, type_id=$typeId, title=$title");

    if (type == 'externalLink') {
      final link = notif['external_link']?.toString() ?? '';
      if (link.isNotEmpty) {
        launchUrl(Uri.parse(link), mode: LaunchMode.externalApplication);
      }
      return;
    }

    if (typeId > 0 && (type == 'category' || type == 'festival' || type == 'custom' || type == 'subscriptionPlan')) {
      Get.to(() => DetailListScreen(
        type: type == 'subscriptionPlan' ? 'category' : type,
        id: typeId,
        title: title,
      ));
      return;
    }

    // For types without a detail page, just show the full message
    if (type == 'ai_campaign' || type.isEmpty) {
      Get.snackbar(
        notif['title'] ?? 'Notification',
        notif['message'] ?? '',
        snackPosition: SnackPosition.BOTTOM,
        duration: const Duration(seconds: 4),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.white,
      appBar: AppBar(
        title: Text('Notifications', style: AppTextStyles.heading4),
        leading: IconButton(
          icon: Icon(Icons.arrow_back, color: AppColors.gray900),
          onPressed: () => Get.back(),
        ),
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : _notifications.isEmpty
              ? _buildEmptyState()
              : ListView.separated(
                  padding: const EdgeInsets.all(24),
                  itemCount: _notifications.length,
                  separatorBuilder: (_, __) => AppSpacing.gapV16,
                  itemBuilder: (context, index) {
                    final notif = _notifications[index];
                    final fullImg = notif['image']?.toString() ?? '';
                    final String type = notif['type']?.toString() ?? '';
                    final bool isClickable = type == 'festival' || type == 'category' || type == 'custom' || type == 'subscriptionPlan' || type == 'externalLink';

                    return GestureDetector(
                      onTap: () => _onNotificationTap(Map<String, dynamic>.from(notif)),
                      child: Container(
                        padding: const EdgeInsets.all(16),
                        decoration: BoxDecoration(
                          color: Colors.white,
                          borderRadius: BorderRadius.circular(24),
                          border: Border.all(color: AppColors.gray100),
                          boxShadow: AppColors.cardShadow,
                        ),
                        child: Row(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Container(
                              width: 48,
                              height: 48,
                              decoration: BoxDecoration(
                                color: AppColors.indigo50,
                                borderRadius: BorderRadius.circular(16),
                              ),
                              clipBehavior: Clip.antiAlias,
                              child: fullImg.isNotEmpty
                                  ? CachedNetworkImage(
                                      imageUrl: fullImg,
                                      fit: BoxFit.cover,
                                      errorWidget: (context, url, error) => Icon(Icons.notifications_none_rounded, color: AppColors.indigo500),
                                    )
                                  : Icon(Icons.notifications_none_rounded, color: AppColors.indigo500),
                            ),
                            AppSpacing.gapH16,
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Row(
                                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      Expanded(
                                        child: Text(
                                          notif['title'] ?? 'Notification',
                                          style: TextStyle(
                                            fontWeight: FontWeight.w700,
                                            color: AppColors.gray900,
                                            fontSize: 15,
                                            height: 1.2,
                                          ),
                                        ),
                                      ),
                                      AppSpacing.gapH8,
                                      Text(
                                        notif['created_at']?.toString().substring(0, 10) ?? 'Just now',
                                        style: TextStyle(
                                          fontWeight: FontWeight.w700,
                                          fontSize: 10,
                                          color: AppColors.slate400,
                                        ),
                                      ),
                                    ],
                                  ),
                                  AppSpacing.gapV4,
                                  Text(
                                    notif['message'] ?? '',
                                    style: TextStyle(
                                      color: AppColors.gray500,
                                      fontSize: 13,
                                      height: 1.5,
                                    ),
                                    maxLines: 2,
                                    overflow: TextOverflow.ellipsis,
                                  ),
                                  if (isClickable) ...[
                                    AppSpacing.gapV8,
                                    Row(
                                      children: [
                                        Text(
                                          'Tap to view',
                                          style: TextStyle(
                                            color: AppColors.primary,
                                            fontSize: 12,
                                            fontWeight: FontWeight.w600,
                                          ),
                                        ),
                                        const SizedBox(width: 4),
                                        Icon(Icons.arrow_forward_ios, size: 10, color: AppColors.primary),
                                      ],
                                    ),
                                  ],
                                ],
                              ),
                            ),
                          ],
                        ),
                      ),
                    );
                  },
                ),
    );
  }

  Widget _buildEmptyState() {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Container(
            width: 80,
            height: 80,
            decoration: BoxDecoration(
              color: AppColors.slate50,
              shape: BoxShape.circle,
            ),
            child: Icon(Icons.notifications_off_outlined, color: AppColors.slate300, size: 40),
          ),
          AppSpacing.gapV16,
          Text('No Notifications', style: AppTextStyles.heading3),
          AppSpacing.gapV4,
          Text(
            "You haven't received any notifications yet.",
            style: AppTextStyles.bodySmall,
          ),
        ],
      ),
    );
  }
}

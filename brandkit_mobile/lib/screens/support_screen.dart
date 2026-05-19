import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import '../utils/app_colors.dart';
import '../utils/app_text_styles.dart';
import '../utils/app_spacing.dart';
import '../services/api_service.dart';

class SupportScreen extends StatefulWidget {
  const SupportScreen({super.key});

  @override
  State<SupportScreen> createState() => _SupportScreenState();
}

class _SupportScreenState extends State<SupportScreen> {
  Map<String, dynamic> _appSettings = {};
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _fetchSettings();
  }

  Future<void> _fetchSettings() async {
    try {
      final response = await ApiService.get('/get-setting'); // Assuming standard Laravel endpoint
      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        setState(() {
          _appSettings = data is List && data.isNotEmpty ? data[0] : (data['data'] ?? {});
          _isLoading = false;
        });
      } else {
        _mockSettings();
      }
    } catch (e) {
      _mockSettings();
    }
  }

  void _mockSettings() {
    setState(() {
      _appSettings = {
        'whatsapp_number': '+919876543210',
        'contact': '+91 98765 43210',
        'email': 'support@artera.app',
      };
      _isLoading = false;
    });
  }

  void _launchUrl(String type, String value) {
    // In a real app, use url_launcher
    Get.snackbar(
      'Opening $type',
      value,
      snackPosition: SnackPosition.BOTTOM,
      backgroundColor: AppColors.indigo600,
      colorText: Colors.white,
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.white,
      appBar: AppBar(
        title: Text('Help & Support', style: AppTextStyles.heading4),
        leading: IconButton(
          icon: Icon(Icons.arrow_back, color: AppColors.gray900),
          onPressed: () => Get.back(),
        ),
        elevation: 0,
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : Stack(
              children: [
                SingleChildScrollView(
                  padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 24),
                  child: Column(
                    children: [
                      // Header Illustration
                      Container(
                        width: 96,
                        height: 96,
                        decoration: BoxDecoration(
                          color: AppColors.indigo50,
                          borderRadius: BorderRadius.circular(32),
                        ),
                        child: Center(
                          child: Icon(Icons.headphones_outlined, color: AppColors.indigo600, size: 48),
                        ),
                      ),
                      AppSpacing.gapV24,
                      Text('How can we help?', style: AppTextStyles.heading2),
                      AppSpacing.gapV8,
                      Text(
                        'Choose your preferred way to connect with our support team.',
                        style: AppTextStyles.bodyMedium.copyWith(color: AppColors.gray500),
                        textAlign: TextAlign.center,
                      ),
                      AppSpacing.gapV40,

                      // Contact Cards
                      if (_appSettings['whatsapp_number'] != null)
                        _buildContactCard(
                          title: 'WhatsApp Us',
                          subtitle: 'Fast Response',
                          icon: Icons.chat_bubble_outline_rounded,
                          color: Colors.teal,
                          bgColor: Colors.teal.withValues(alpha: 0.1),
                          iconBgColor: Colors.teal,
                          onTap: () => _launchUrl('WhatsApp', _appSettings['whatsapp_number']),
                        ),
                      
                      AppSpacing.gapV16,
                      
                      if (_appSettings['contact'] != null)
                        _buildContactCard(
                          title: 'Call Support',
                          subtitle: _appSettings['contact'],
                          icon: Icons.phone_outlined,
                          color: AppColors.sky500,
                          bgColor: AppColors.sky50,
                          iconBgColor: AppColors.sky500,
                          onTap: () => _launchUrl('Phone', _appSettings['contact']),
                        ),

                      AppSpacing.gapV16,
                      
                      if (_appSettings['email'] != null)
                        _buildContactCard(
                          title: 'Email Us',
                          subtitle: _appSettings['email'],
                          icon: Icons.mail_outline_rounded,
                          color: AppColors.indigo600,
                          bgColor: AppColors.indigo50,
                          iconBgColor: AppColors.indigo600,
                          onTap: () => _launchUrl('Email', _appSettings['email']),
                        ),
                        
                      const SizedBox(height: 100),
                    ],
                  ),
                ),
                
                // Bottom Footer
                Positioned(
                  bottom: 0,
                  left: 0,
                  right: 0,
                  child: Container(
                    padding: const EdgeInsets.symmetric(vertical: 24),
                    decoration: BoxDecoration(
                      gradient: LinearGradient(
                        begin: Alignment.bottomCenter,
                        end: Alignment.topCenter,
                        colors: [Colors.white, Colors.white.withValues(alpha: 0.0)],
                      ),
                    ),
                    child: Center(
                      child: Text(
                        'SUPPORT AVAILABLE 24/7',
                        style: TextStyle(
                          fontSize: 11,
                          fontWeight: FontWeight.w900,
                          color: AppColors.gray300,
                          letterSpacing: 2.0,
                        ),
                      ),
                    ),
                  ),
                ),
              ],
            ),
    );
  }

  Widget _buildContactCard({
    required String title,
    required String subtitle,
    required IconData icon,
    required Color color,
    required Color bgColor,
    required Color iconBgColor,
    required VoidCallback onTap,
  }) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.all(20),
        decoration: BoxDecoration(
          color: bgColor,
          borderRadius: BorderRadius.circular(24),
        ),
        child: Row(
          children: [
            Container(
              width: 48,
              height: 48,
              decoration: BoxDecoration(
                color: iconBgColor,
                borderRadius: BorderRadius.circular(16),
                boxShadow: [
                  BoxShadow(
                    color: iconBgColor.withValues(alpha: 0.3),
                    blurRadius: 12,
                    offset: const Offset(0, 4),
                  ),
                ],
              ),
              child: Icon(icon, color: Colors.white, size: 24),
            ),
            AppSpacing.gapH16,
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    title,
                    style: TextStyle(
                      fontWeight: FontWeight.w700,
                      color: color.withValues(alpha: 0.8).withAlpha(255), // Darken the color a bit
                      fontSize: 16,
                    ),
                  ),
                  AppSpacing.gapV4,
                  Text(
                    subtitle,
                    style: TextStyle(
                      fontWeight: FontWeight.w700,
                      color: color.withValues(alpha: 0.7),
                      fontSize: 12,
                      letterSpacing: 0.5,
                      // uppercase if it's "Fast Response"
                      // but we just leave it as is, or use uppercase conditionally
                    ),
                  ),
                ],
              ),
            ),
            Icon(Icons.chevron_right, color: color.withValues(alpha: 0.5), size: 24),
          ],
        ),
      ),
    );
  }
}

import 'package:flutter/material.dart';
import 'package:flutter_widget_from_html/flutter_widget_from_html.dart';
import 'package:get/get.dart';
import '../utils/app_colors.dart';
import '../utils/app_text_styles.dart';
import '../utils/app_spacing.dart';
import '../utils/string_extensions.dart';
import '../services/translation_service.dart';
import 'dart:ui';

class PolicyScreen extends StatelessWidget {
  final String title;
  final String htmlContent;

  const PolicyScreen({
    super.key,
    required this.title,
    required this.htmlContent,
  });

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        backgroundColor: Colors.transparent,
        elevation: 0,
        leading: IconButton(
          icon: Icon(Icons.arrow_back_ios_new, color: AppColors.gray900, size: 20),
          onPressed: () => Get.back(),
        ),
        title: Text(
          title,
          style: AppTextStyles.heading2.copyWith(fontSize: 20),
        ),
        centerTitle: true,
      ),
      body: Stack(
        children: [
          // Background Gradient effect for Premium UI
          Positioned(
            top: -50,
            left: -50,
            child: Container(
              width: 200,
              height: 200,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                color: AppColors.primary.withOpacity(0.15),
              ),
            ),
          ),
          Positioned(
            bottom: -100,
            right: -50,
            child: Container(
              width: 300,
              height: 300,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                color: AppColors.secondary.withOpacity(0.1),
              ),
            ),
          ),
          // Glassmorphism effect
          Positioned.fill(
            child: BackdropFilter(
              filter: ImageFilter.blur(sigmaX: 30, sigmaY: 30),
              child: Container(
                color: Colors.white.withOpacity(0.5),
              ),
            ),
          ),
          // Content
          SafeArea(
            child: htmlContent.isEmpty
                ? Center(
                    child: Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Icon(Icons.description_outlined, size: 64, color: AppColors.gray300),
                        AppSpacing.gapV16,
                        Text(
                          'no_content_available'.trFormat,
                          style: AppTextStyles.bodyMedium.copyWith(color: AppColors.gray500),
                        ),
                      ],
                    ),
                  )
                : SingleChildScrollView(
                    physics: const BouncingScrollPhysics(),
                    padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 16),
                    child: Container(
                      padding: const EdgeInsets.all(24),
                      decoration: BoxDecoration(
                        color: Colors.white.withOpacity(0.9),
                        borderRadius: BorderRadius.circular(24),
                        boxShadow: [
                          BoxShadow(
                            color: Colors.black.withOpacity(0.04),
                            blurRadius: 24,
                            offset: const Offset(0, 8),
                          ),
                        ],
                        border: Border.all(color: Colors.white, width: 2),
                      ),
                      child: HtmlWidget(
                        htmlContent,
                        textStyle: AppTextStyles.bodyMedium.copyWith(
                          color: AppColors.gray800,
                          height: 1.6,
                        ),
                        customStylesBuilder: (element) {
                          if (element.localName == 'h1' || element.localName == 'h2' || element.localName == 'h3') {
                            return {
                              'color': '#111827', // AppColors.gray900 equivalent
                              'font-weight': '700',
                              'margin-top': '24px',
                              'margin-bottom': '16px',
                            };
                          }
                          if (element.localName == 'a') {
                            return {'color': '#3b82f6', 'text-decoration': 'none'}; // primary color
                          }
                          return null;
                        },
                      ),
                    ),
                  ),
          ),
        ],
      ),
    );
  }
}

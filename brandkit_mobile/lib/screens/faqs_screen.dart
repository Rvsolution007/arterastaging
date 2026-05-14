import 'package:flutter/material.dart';
import 'package:get/get.dart';
import '../utils/app_colors.dart';
import '../utils/app_text_styles.dart';
import '../utils/app_spacing.dart';
import 'support_screen.dart';

class FaqsScreen extends StatefulWidget {
  const FaqsScreen({super.key});

  @override
  State<FaqsScreen> createState() => _FaqsScreenState();
}

class _FaqsScreenState extends State<FaqsScreen> {
  final List<Map<String, String>> _faqs = [
    {
      'q': 'How to edit my business profile?',
      'a': 'Go to the Business tab and click on the "EDIT" button at the top right of your profile header. You can then update your name, logo, and other details.'
    },
    {
      'q': 'Where can I see my downloads?',
      'a': 'You can access all your saved designs and downloaded content through the "Downloads" button on the Business page action grid.'
    },
    {
      'q': 'How to change the language?',
      'a': 'You can change the language from the "More" tab under App Preferences. This will update the content language across the app.'
    },
    {
      'q': 'Are the templates free to use?',
      'a': 'Most templates are free. Premium templates are marked with a "Pro" badge and require an active subscription to download.'
    },
    {
      'q': 'How can I contact support?',
      'a': 'Visit the "Help & Support" section on the Business page to connect with us via WhatsApp, Call, or Email.'
    }
  ];

  int? _expandedIndex;

  void _toggleFaq(int index) {
    setState(() {
      if (_expandedIndex == index) {
        _expandedIndex = null;
      } else {
        _expandedIndex = index;
      }
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.white,
      appBar: AppBar(
        title: Text('Common Questions', style: AppTextStyles.heading4),
        leading: IconButton(
          icon: Icon(Icons.arrow_back, color: AppColors.gray900),
          onPressed: () => Get.back(),
        ),
        elevation: 0,
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 16),
        child: Column(
          children: [
            ...List.generate(_faqs.length, (index) {
              final isExpanded = _expandedIndex == index;
              final faq = _faqs[index];

              return GestureDetector(
                onTap: () => _toggleFaq(index),
                child: AnimatedContainer(
                  duration: const Duration(milliseconds: 300),
                  margin: const EdgeInsets.only(bottom: 16),
                  decoration: BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.circular(24),
                    border: Border.all(color: AppColors.slate100),
                    boxShadow: isExpanded ? AppColors.cardShadow : null,
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Padding(
                        padding: const EdgeInsets.all(24),
                        child: Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            Expanded(
                              child: Text(
                                faq['q']!,
                                style: TextStyle(
                                  fontWeight: FontWeight.w700,
                                  color: isExpanded ? AppColors.indigo600 : AppColors.gray800,
                                  fontSize: 15,
                                ),
                              ),
                            ),
                            AppSpacing.gapH16,
                            AnimatedRotation(
                              turns: isExpanded ? 0.5 : 0.0,
                              duration: const Duration(milliseconds: 300),
                              child: Icon(
                                Icons.keyboard_arrow_down,
                                color: isExpanded ? AppColors.indigo500 : AppColors.slate400,
                              ),
                            ),
                          ],
                        ),
                      ),
                      if (isExpanded)
                        Padding(
                          padding: const EdgeInsets.only(left: 24, right: 24, bottom: 24),
                          child: Container(
                            padding: const EdgeInsets.only(top: 16),
                            decoration: BoxDecoration(
                              border: Border(top: BorderSide(color: AppColors.slate50)),
                            ),
                            child: Text(
                              faq['a']!,
                              style: TextStyle(
                                color: AppColors.slate500,
                                fontSize: 14,
                                height: 1.6,
                              ),
                            ),
                          ),
                        ),
                    ],
                  ),
                ),
              );
            }),

            AppSpacing.gapV32,
            
            Text(
              'Still have questions?',
              style: TextStyle(color: AppColors.slate400, fontSize: 14, fontWeight: FontWeight.w600),
            ),
            AppSpacing.gapV16,
            
            ElevatedButton(
              onPressed: () => Get.to(() => const SupportScreen()),
              style: ElevatedButton.styleFrom(
                backgroundColor: AppColors.indigo600,
                padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 16),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(999)),
                elevation: 8,
                shadowColor: AppColors.indigo100,
              ),
              child: Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  const Icon(Icons.chat_bubble_outline_rounded, color: Colors.white, size: 20),
                  AppSpacing.gapH8,
                  Text('Contact Support', style: AppTextStyles.buttonPrimary),
                ],
              ),
            ),
            
            const SizedBox(height: 60),
          ],
        ),
      ),
    );
  }
}

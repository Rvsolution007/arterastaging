import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'dart:convert';
import 'package:flutter_widget_from_html/flutter_widget_from_html.dart';
import '../utils/app_colors.dart';
import '../utils/app_text_styles.dart';
import '../utils/app_spacing.dart';
import '../services/api_service.dart';
import 'support_screen.dart';

class FaqsScreen extends StatefulWidget {
  const FaqsScreen({super.key});

  @override
  State<FaqsScreen> createState() => _FaqsScreenState();
}

class _FaqsScreenState extends State<FaqsScreen> {
  bool _isLoading = true;
  String _error = '';
  
  // Data structure: Category Name -> List of FAQs
  Map<String, List<dynamic>> _groupedFaqs = {};
  Map<String, List<dynamic>> _filteredFaqs = {};
  
  String _searchQuery = '';
  int? _expandedId;

  @override
  void initState() {
    super.initState();
    _fetchFaqs();
  }

  Future<void> _fetchFaqs() async {
    setState(() {
      _isLoading = true;
      _error = '';
    });

    try {
      final response = await ApiService.get('/faqs');
      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['data'] != null) {
          setState(() {
            _groupedFaqs = Map<String, List<dynamic>>.from(data['data']);
            _filteredFaqs = Map<String, List<dynamic>>.from(data['data']);
          });
        }
      } else {
        setState(() {
          _error = 'Failed to load FAQs. Please try again.';
        });
      }
    } catch (e) {
      setState(() {
        _error = 'An error occurred. Please check your connection.';
      });
    } finally {
      setState(() {
        _isLoading = false;
      });
    }
  }

  void _filterFaqs(String query) {
    _searchQuery = query.toLowerCase();
    
    if (_searchQuery.isEmpty) {
      setState(() {
        _filteredFaqs = Map.from(_groupedFaqs);
      });
      return;
    }

    Map<String, List<dynamic>> filtered = {};
    
    _groupedFaqs.forEach((category, faqs) {
      final matchedFaqs = faqs.where((faq) {
        final question = (faq['question'] ?? '').toString().toLowerCase();
        final answer = (faq['answer'] ?? '').toString().toLowerCase();
        return question.contains(_searchQuery) || answer.contains(_searchQuery);
      }).toList();
      
      if (matchedFaqs.isNotEmpty) {
        filtered[category] = matchedFaqs;
      }
    });

    setState(() {
      _filteredFaqs = filtered;
    });
  }

  void _toggleFaq(int id) {
    setState(() {
      if (_expandedId == id) {
        _expandedId = null;
      } else {
        _expandedId = id;
      }
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.white,
      appBar: AppBar(
        title: Text('Knowledge Base', style: AppTextStyles.heading4),
        leading: IconButton(
          icon: Icon(Icons.arrow_back, color: AppColors.gray900),
          onPressed: () => Get.back(),
        ),
        elevation: 0,
      ),
      body: Column(
        children: [
          // Search Bar
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 16),
            decoration: BoxDecoration(
              color: Colors.white,
              border: Border(bottom: BorderSide(color: AppColors.slate100)),
            ),
            child: TextField(
              onChanged: _filterFaqs,
              decoration: InputDecoration(
                hintText: 'Search for answers...',
                prefixIcon: Icon(Icons.search, color: AppColors.slate400),
                filled: true,
                fillColor: AppColors.slate50,
                contentPadding: const EdgeInsets.symmetric(vertical: 0),
                border: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(12),
                  borderSide: BorderSide.none,
                ),
              ),
            ),
          ),
          
          Expanded(
            child: _isLoading
                ? const Center(child: CircularProgressIndicator())
                : _error.isNotEmpty
                    ? Center(child: Text(_error, style: TextStyle(color: Colors.red)))
                    : _filteredFaqs.isEmpty
                        ? _buildEmptyState()
                        : _buildFaqList(),
          ),
        ],
      ),
    );
  }

  Widget _buildEmptyState() {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(Icons.search_off, size: 64, color: AppColors.slate300),
          AppSpacing.gapV16,
          Text(
            'No FAQs found matching "$_searchQuery"',
            style: TextStyle(color: AppColors.slate500, fontSize: 16),
          ),
        ],
      ),
    );
  }

  Widget _buildFaqList() {
    return SingleChildScrollView(
      padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 24),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          ..._filteredFaqs.entries.map((entry) {
            final category = entry.key;
            final faqs = entry.value;

            return Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Padding(
                  padding: const EdgeInsets.only(bottom: 16, top: 8),
                  child: Text(
                    category.toUpperCase(),
                    style: TextStyle(
                      color: AppColors.slate500,
                      fontWeight: FontWeight.w700,
                      fontSize: 12,
                      letterSpacing: 1.2,
                    ),
                  ),
                ),
                ...faqs.map((faq) {
                  final id = faq['id'] as int;
                  final question = faq['question'] as String? ?? '';
                  final answerHtml = faq['answer'] as String? ?? '';
                  final isExpanded = _expandedId == id;

                  return GestureDetector(
                    onTap: () => _toggleFaq(id),
                    child: AnimatedContainer(
                      duration: const Duration(milliseconds: 300),
                      margin: const EdgeInsets.only(bottom: 16),
                      decoration: BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.circular(16),
                        border: Border.all(color: AppColors.slate100),
                        boxShadow: isExpanded ? AppColors.cardShadow : null,
                      ),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Padding(
                            padding: const EdgeInsets.all(20),
                            child: Row(
                              mainAxisAlignment: MainAxisAlignment.spaceBetween,
                              crossAxisAlignment: CrossAxisAlignment.center,
                              children: [
                                Expanded(
                                  child: Text(
                                    question,
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
                              padding: const EdgeInsets.only(left: 20, right: 20, bottom: 20),
                              child: Container(
                                padding: const EdgeInsets.only(top: 16),
                                decoration: BoxDecoration(
                                  border: Border(top: BorderSide(color: AppColors.slate50)),
                                ),
                                child: HtmlWidget(
                                  answerHtml,
                                  textStyle: TextStyle(
                                    color: AppColors.slate600,
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
                }).toList(),
                AppSpacing.gapV16,
              ],
            );
          }).toList(),

          AppSpacing.gapV32,
          Center(
            child: Text(
              'Still have questions?',
              style: TextStyle(color: AppColors.slate400, fontSize: 14, fontWeight: FontWeight.w600),
            ),
          ),
          AppSpacing.gapV16,
          Center(
            child: ElevatedButton(
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
          ),
          const SizedBox(height: 60),
        ],
      ),
    );
  }
}

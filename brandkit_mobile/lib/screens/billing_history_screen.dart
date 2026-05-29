import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:url_launcher/url_launcher.dart';
import '../services/api_service.dart';
import '../utils/app_colors.dart';
import '../utils/app_text_styles.dart';
import '../utils/app_spacing.dart';

class BillingHistoryScreen extends StatefulWidget {
  const BillingHistoryScreen({super.key});

  @override
  State<BillingHistoryScreen> createState() => _BillingHistoryScreenState();
}

class _BillingHistoryScreenState extends State<BillingHistoryScreen> {
  bool _isLoading = true;
  List<dynamic> _transactions = [];
  String? _error;

  @override
  void initState() {
    super.initState();
    _fetchHistory();
  }

  Future<void> _fetchHistory() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final userIdDynamic = prefs.get('userId');
      int? userId;
      if (userIdDynamic is int) {
        userId = userIdDynamic;
      } else if (userIdDynamic is String) {
        userId = int.tryParse(userIdDynamic);
      }
      
      if (userId == null) {
        setState(() {
          _error = 'User ID not found. Please log in again.';
          _isLoading = false;
        });
        return;
      }

      final response = await ApiService.getPaymentHistory(userId);
      if (response['status'] == 'Success') {
        setState(() {
          _transactions = response['data'] ?? [];
          _isLoading = false;
        });
      } else {
        setState(() {
          _error = response['message'] ?? 'Failed to load history';
          _isLoading = false;
        });
      }
    } catch (e) {
      setState(() {
        _error = e.toString();
        _isLoading = false;
      });
    }
  }

  Future<void> _downloadInvoice(String url) async {
    final uri = Uri.parse(url);
    if (await canLaunchUrl(uri)) {
      await launchUrl(uri, mode: LaunchMode.externalApplication);
    } else {
      Get.snackbar('Error', 'Could not open invoice URL',
          backgroundColor: AppColors.red500, colorText: Colors.white);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        elevation: 0,
        backgroundColor: Colors.white,
        title: Text('Billing & Payment History', style: AppTextStyles.heading3),
        iconTheme: IconThemeData(color: AppColors.gray900),
      ),
      body: _buildBody(),
    );
  }

  Widget _buildBody() {
    if (_isLoading) {
      return const Center(child: CircularProgressIndicator());
    }

    if (_error != null) {
      return Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(Icons.error_outline, size: 64, color: AppColors.red500),
            AppSpacing.gapV16,
            Text(_error!, style: TextStyle(color: AppColors.red500, fontSize: 16)),
            AppSpacing.gapV16,
            ElevatedButton(
              onPressed: () {
                setState(() {
                  _isLoading = true;
                  _error = null;
                });
                _fetchHistory();
              },
              child: const Text('Retry'),
            )
          ],
        ),
      );
    }

    if (_transactions.isEmpty) {
      return Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(Icons.receipt_long_outlined, size: 80, color: AppColors.gray300),
            AppSpacing.gapV16,
            Text('No Payment History', style: AppTextStyles.heading3),
            AppSpacing.gapV8,
            Text('You do not have any past payments.', style: AppTextStyles.bodyMedium),
          ],
        ),
      );
    }

    return ListView.separated(
      padding: const EdgeInsets.all(16),
      itemCount: _transactions.length,
      separatorBuilder: (_, __) => AppSpacing.gapV16,
      itemBuilder: (context, index) {
        final t = _transactions[index];
        final isSuccess = t['status'].toString().toLowerCase() == 'success' || t['status'].toString().toLowerCase() == 'completed';

        return Container(
          padding: const EdgeInsets.all(16),
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(16),
            border: Border.all(color: AppColors.gray100),
            boxShadow: AppColors.cardShadow,
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Expanded(
                    child: Text(
                      t['plan_name'] ?? 'Subscription',
                      style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w800),
                    ),
                  ),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                    decoration: BoxDecoration(
                      color: isSuccess ? AppColors.success.withValues(alpha: 0.1) : AppColors.orange500.withValues(alpha: 0.1),
                      borderRadius: BorderRadius.circular(20),
                    ),
                    child: Text(
                      t['status'].toString().toUpperCase(),
                      style: TextStyle(
                        fontSize: 12,
                        fontWeight: FontWeight.w700,
                        color: isSuccess ? AppColors.success : AppColors.orange500,
                      ),
                    ),
                  ),
                ],
              ),
              AppSpacing.gapV12,
              Row(
                children: [
                  Icon(Icons.calendar_today, size: 16, color: AppColors.gray500),
                  AppSpacing.gapH8,
                  Text(t['date'] ?? '', style: TextStyle(color: AppColors.gray600, fontSize: 14)),
                  const Spacer(),
                  Text(
                    '₹${t['total_paid']}',
                    style: TextStyle(fontSize: 18, fontWeight: FontWeight.w800, color: AppColors.primary),
                  ),
                ],
              ),
              AppSpacing.gapV8,
              Row(
                children: [
                  Icon(Icons.payment, size: 16, color: AppColors.gray500),
                  AppSpacing.gapH8,
                  Text('Method: ${t['payment_type'] ?? 'N/A'}', style: TextStyle(color: AppColors.gray600, fontSize: 14)),
                ],
              ),
              AppSpacing.gapV16,
              SizedBox(
                width: double.infinity,
                child: OutlinedButton.icon(
                  onPressed: () => _downloadInvoice(t['invoice_url']),
                  icon: const Icon(Icons.download_rounded),
                  label: const Text('View / Download Invoice'),
                  style: OutlinedButton.styleFrom(
                    foregroundColor: AppColors.blue600,
                    side: BorderSide(color: AppColors.blue600),
                    padding: const EdgeInsets.symmetric(vertical: 12),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                  ),
                ),
              ),
            ],
          ),
        );
      },
    );
  }
}

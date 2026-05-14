import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:get/get.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../services/api_service.dart';
import '../utils/app_colors.dart';
import '../utils/app_text_styles.dart';

class ReferralScreen extends StatefulWidget {
  const ReferralScreen({super.key});

  @override
  State<ReferralScreen> createState() => _ReferralScreenState();
}

class _ReferralScreenState extends State<ReferralScreen> {
  bool _isLoading = true;
  Map<String, dynamic>? _referralData;
  String? _errorMessage;

  @override
  void initState() {
    super.initState();
    _fetchReferralData();
  }

  Future<void> _fetchReferralData() async {
    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    try {
      final prefs = await SharedPreferences.getInstance();
      final userId = prefs.getString('userId');

      if (userId == null) {
        setState(() {
          _errorMessage = "User not logged in.";
          _isLoading = false;
        });
        return;
      }

      final response = await ApiService.get('/referral-detail?userId=$userId');

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['status'] == 'Error') {
          setState(() {
            _errorMessage = data['message'];
            _isLoading = false;
          });
        } else {
          setState(() {
            _referralData = data;
            _isLoading = false;
          });
        }
      } else {
        setState(() {
          _errorMessage = 'Failed to load referral details.';
          _isLoading = false;
        });
      }
    } catch (e) {
      setState(() {
        _errorMessage = 'Connection error: $e';
        _isLoading = false;
      });
    }
  }

  void _copyCode() {
    if (_referralData != null && _referralData!['referralCode'] != null) {
      final code = _referralData!['referralCode'];
      Clipboard.setData(ClipboardData(text: code));
      Get.snackbar('Copied', 'Referral Code $code copied to clipboard!', 
        snackPosition: SnackPosition.BOTTOM, 
        backgroundColor: Colors.black87, 
        colorText: Colors.white,
        margin: const EdgeInsets.all(16)
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        title: const Text('Invite & Earn', style: TextStyle(fontWeight: FontWeight.w800, color: Colors.black87)),
        backgroundColor: Colors.white,
        elevation: 0,
        iconTheme: const IconThemeData(color: Colors.black87),
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : _errorMessage != null
              ? Center(
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Icon(Icons.error_outline, size: 48, color: Colors.red[300]),
                      const SizedBox(height: 16),
                      Text(_errorMessage!, style: const TextStyle(fontSize: 16, color: Colors.black54)),
                      const SizedBox(height: 16),
                      ElevatedButton(onPressed: _fetchReferralData, child: const Text('Retry'))
                    ],
                  ),
                )
              : _buildContent(),
    );
  }

  Widget _buildContent() {
    final code = _referralData!['referralCode'] ?? 'N/A';
    final totalRefer = _referralData!['totalReferUser'] ?? 0;
    
    return RefreshIndicator(
      onRefresh: _fetchReferralData,
      child: SingleChildScrollView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            // Header Image/Icon Card
            Container(
              padding: const EdgeInsets.all(24),
              decoration: BoxDecoration(
                gradient: const LinearGradient(
                  colors: [AppColors.primary, AppColors.indigo500],
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                ),
                borderRadius: BorderRadius.circular(24),
                boxShadow: AppColors.primaryShadow,
              ),
              child: Column(
                children: [
                  Container(
                    width: 72, height: 72,
                    decoration: BoxDecoration(color: Colors.white.withOpacity(0.2), shape: BoxShape.circle),
                    child: const Icon(Icons.card_giftcard, color: Colors.white, size: 36),
                  ),
                  const SizedBox(height: 16),
                  const Text('Invite Friends, Earn Rewards!', style: TextStyle(color: Colors.white, fontSize: 20, fontWeight: FontWeight.w800)),
                  const SizedBox(height: 8),
                  const Text('Share your code with friends. When they join, you get rewarded with premium access!', 
                    textAlign: TextAlign.center,
                    style: TextStyle(color: Colors.white70, fontSize: 13)
                  ),
                ],
              ),
            ),
            const SizedBox(height: 24),

            // Code Copy Box
            Container(
              padding: const EdgeInsets.all(20),
              decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(20), border: Border.all(color: AppColors.gray200)),
              child: Column(
                children: [
                  Text('Your Referral Code', style: TextStyle(color: AppColors.gray500, fontSize: 13, fontWeight: FontWeight.w600)),
                  const SizedBox(height: 12),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 12),
                    decoration: BoxDecoration(color: AppColors.indigo50, borderRadius: BorderRadius.circular(12), border: Border.all(color: AppColors.primary, width: 2)),
                    child: Row(
                      mainAxisAlignment: MainAxisAlignment.center,
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Text(code, style: const TextStyle(fontSize: 24, fontWeight: FontWeight.w900, color: AppColors.primary, letterSpacing: 2)),
                        const SizedBox(width: 16),
                        GestureDetector(
                          onTap: _copyCode,
                          child: Container(
                            padding: const EdgeInsets.all(8),
                            decoration: BoxDecoration(color: AppColors.primary, shape: BoxShape.circle),
                            child: const Icon(Icons.copy, color: Colors.white, size: 16),
                          ),
                        )
                      ],
                    ),
                  ),
                  const SizedBox(height: 16),
                  ElevatedButton.icon(
                    onPressed: _copyCode,
                    style: ElevatedButton.styleFrom(
                      backgroundColor: AppColors.primary,
                      padding: const EdgeInsets.symmetric(vertical: 12, horizontal: 24),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                    ),
                    icon: const Icon(Icons.copy, size: 18, color: Colors.white,),
                    label: const Text('Copy Code', style: TextStyle(fontSize: 15, fontWeight: FontWeight.bold, color: Colors.white)),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 24),

            // Stats
            Row(
              children: [
                Expanded(
                  child: Container(
                    padding: const EdgeInsets.all(20),
                    decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(20), border: Border.all(color: AppColors.gray200)),
                    child: Column(
                      children: [
                        Icon(Icons.people_alt_outlined, color: AppColors.primary, size: 32),
                        const SizedBox(height: 12),
                        Text('$totalRefer', style: const TextStyle(fontSize: 24, fontWeight: FontWeight.w900, color: AppColors.gray900)),
                        const SizedBox(height: 4),
                        Text('Total Invites', style: TextStyle(fontSize: 12, color: AppColors.gray500, fontWeight: FontWeight.w600)),
                      ],
                    ),
                  ),
                ),
                const SizedBox(width: 16),
                Expanded(
                  child: Container(
                    padding: const EdgeInsets.all(20),
                    decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(20), border: Border.all(color: AppColors.gray200)),
                    child: Column(
                      children: [
                        Icon(Icons.workspace_premium_outlined, color: Colors.orange, size: 32),
                        const SizedBox(height: 12),
                        Text('${_referralData!['totalSubscriptionUsingRefer'] ?? 0}', style: const TextStyle(fontSize: 24, fontWeight: FontWeight.w900, color: AppColors.gray900)),
                        const SizedBox(height: 4),
                        Text('Premium Joins', style: TextStyle(fontSize: 12, color: AppColors.gray500, fontWeight: FontWeight.w600)),
                      ],
                    ),
                  ),
                ),
              ],
            )
          ],
        ),
      ),
    );
  }
}

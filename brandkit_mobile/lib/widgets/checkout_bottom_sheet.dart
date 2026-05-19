import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:razorpay_flutter/razorpay_flutter.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../services/api_service.dart';
import '../utils/app_colors.dart';
import '../controllers/subscription_controller.dart';

class CheckoutBottomSheet extends StatefulWidget {
  final Map<String, dynamic> plan;
  final String planType; // 'Monthly' or 'Yearly'

  const CheckoutBottomSheet({super.key, required this.plan, this.planType = 'Yearly'});

  @override
  State<CheckoutBottomSheet> createState() => _CheckoutBottomSheetState();
}

class _CheckoutBottomSheetState extends State<CheckoutBottomSheet> {
  late Razorpay _razorpay;
  bool _isLoadingSettings = true;
  bool _isProcessingPayment = false;
  
  Map<String, dynamic>? _paymentSettings;
  
  final TextEditingController _couponController = TextEditingController();
  bool _isApplyingCoupon = false;
  String? _appliedCouponCode;
  num _discountPercent = 0;
  
  late num _basePrice;

  @override
  void initState() {
    super.initState();
    // Determine price based on planType
    if (widget.planType == 'Monthly') {
      final mDiscount = (widget.plan['monthlyDiscountPrice'] ?? 0) as num;
      final mBase = (widget.plan['monthlyPrice'] ?? 0) as num;
      _basePrice = (mDiscount > 0 && mDiscount < mBase) ? mDiscount : mBase;
    } else {
      final yDiscount = (widget.plan['yearlyDiscountPrice'] ?? 0) as num;
      final yBase = (widget.plan['yearlyPrice'] ?? 0) as num;
      // Fallback to old columns if new ones are 0
      if (yDiscount > 0 || yBase > 0) {
        _basePrice = (yDiscount > 0 && yDiscount < yBase) ? yDiscount : yBase;
      } else {
        final dp = widget.plan['discountPrice'] ?? 0;
        final pp = widget.plan['planPrice'] ?? 0;
        _basePrice = (dp > 0 && dp < pp) ? dp : pp;
      }
    }
    
    _razorpay = Razorpay();
    _razorpay.on(Razorpay.EVENT_PAYMENT_SUCCESS, _handlePaymentSuccess);
    _razorpay.on(Razorpay.EVENT_PAYMENT_ERROR, _handlePaymentError);
    _razorpay.on(Razorpay.EVENT_EXTERNAL_WALLET, _handleExternalWallet);
    
    _fetchPaymentSettings();
  }

  @override
  void dispose() {
    _razorpay.clear();
    _couponController.dispose();
    super.dispose();
  }

  Future<void> _fetchPaymentSettings() async {
    try {
      final res = await ApiService.getPaymentDetails();
      if (res.statusCode == 200) {
        setState(() {
          _paymentSettings = jsonDecode(res.body);
          _isLoadingSettings = false;
        });
      }
    } catch (e) {
      debugPrint('Error fetching payment settings: $e');
      setState(() => _isLoadingSettings = false);
    }
  }

  Future<void> _applyCoupon() async {
    final code = _couponController.text.trim();
    if (code.isEmpty) return;

    setState(() => _isApplyingCoupon = true);
    try {
      final prefs = await SharedPreferences.getInstance();
      final userId = prefs.getString('userId') ?? '';
      
      final res = await ApiService.applyCoupon(userId, code);
      debugPrint('[Coupon] Response status: ${res.statusCode}, body: ${res.body}');
      if (res.statusCode == 200) {
        final data = jsonDecode(res.body);
        final rawDiscount = data['discount'];
        debugPrint('[Coupon] Raw discount value: $rawDiscount (type: ${rawDiscount.runtimeType})');
        final parsedDiscount = double.tryParse(rawDiscount.toString()) ?? 0.0;
        debugPrint('[Coupon] Parsed discount: $parsedDiscount, Base price: $_basePrice');
        setState(() {
          _appliedCouponCode = code;
          _discountPercent = parsedDiscount;
        });
        Get.snackbar('Success', 'Coupon Applied: ${parsedDiscount.toStringAsFixed(0)}% off!', backgroundColor: Colors.green, colorText: Colors.white);
      } else {
        final data = jsonDecode(res.body);
        Get.snackbar('Error', data['message'] ?? 'Invalid Coupon', backgroundColor: Colors.red, colorText: Colors.white);
      }
    } catch (e) {
      Get.snackbar('Error', 'Failed to apply coupon', backgroundColor: Colors.red, colorText: Colors.white);
    } finally {
      setState(() => _isApplyingCoupon = false);
    }
  }

  void _startRazorpayPayment() async {
    final razorpayKey = _paymentSettings?['razorpayKeyId'];
    if (razorpayKey == null || razorpayKey.isEmpty) {
      Get.snackbar('Error', 'Razorpay is not configured properly.');
      return;
    }

    final discountAmount = (_basePrice * _discountPercent / 100);
    final finalPrice = _basePrice - discountAmount;
    if (finalPrice <= 0) {
      // Free or fully discounted, process directly
      _processDirectSubscription('FREE_${DateTime.now().millisecondsSinceEpoch}', 'Free');
      return;
    }

    final prefs = await SharedPreferences.getInstance();
    final userEmail = prefs.getString('emailId') ?? 'user@example.com';

    var options = {
      'key': razorpayKey,
      'amount': (finalPrice * 100).toInt(),
      'name': 'Artera Subscription',
      'description': widget.plan['planName'],
      'prefill': {'email': userEmail},
    };

    try {
      _razorpay.open(options);
    } catch (e) {
      debugPrint('Error opening Razorpay: $e');
      Get.snackbar('Error', 'Failed to initialize Razorpay checkout. $e');
    }
  }

  void _handlePaymentSuccess(PaymentSuccessResponse response) {
    _processDirectSubscription(response.paymentId ?? 'UNKNOWN', 'Razorpay');
  }

  void _handlePaymentError(PaymentFailureResponse response) {
    Get.snackbar('Payment Failed', response.message ?? 'Transaction cancelled', backgroundColor: Colors.red, colorText: Colors.white);
  }

  void _handleExternalWallet(ExternalWalletResponse response) {
    Get.snackbar('External Wallet', 'Selected: ${response.walletName}');
  }

  Future<void> _processDirectSubscription(String paymentId, String paymentType) async {
    setState(() => _isProcessingPayment = true);
    try {
      final prefs = await SharedPreferences.getInstance();
      final userId = prefs.getString('userId') ?? '';
      
      final discountAmount = (_basePrice * _discountPercent / 100);
      final finalPrice = _basePrice - discountAmount;
      
      final body = {
        'userId': userId,
        'planId': widget.plan['id'],
        'paymentId': paymentId,
        'paymentType': paymentType,
        'paymentAmount': finalPrice,
        'planType': widget.planType,
        if (_appliedCouponCode != null) 'code': _appliedCouponCode,
      };

      final res = await ApiService.createPayment(body);
      if (res.statusCode == 200) {
        Get.back(); // Close sheet
        Get.snackbar('Success', 'Subscription Activated!', backgroundColor: Colors.green, colorText: Colors.white);
        
        // Refresh limits
        Get.find<SubscriptionController>().refreshFromApi();
      } else {
        Get.snackbar('Error', 'Failed to save transaction.', backgroundColor: Colors.red, colorText: Colors.white);
      }
    } catch (e) {
      Get.snackbar('Error', 'An error occurred.', backgroundColor: Colors.red, colorText: Colors.white);
    } finally {
      if (mounted) setState(() => _isProcessingPayment = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final discountAmount = (_basePrice * _discountPercent / 100);
    final finalPrice = (_basePrice - discountAmount).clamp(0, double.infinity);

    return Container(
      padding: const EdgeInsets.all(24),
      decoration: const BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      child: SafeArea(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Text(
              'Checkout',
              style: TextStyle(fontSize: 22, fontWeight: FontWeight.bold, color: AppColors.gray900),
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: 24),
            
            // Plan Info
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: AppColors.gray50,
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: AppColors.gray200),
              ),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(widget.plan['planName'], style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                      Text('${widget.planType} Plan', style: TextStyle(color: AppColors.gray500, fontSize: 13)),
                    ],
                  ),
                  Text('₹$_basePrice', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 18)),
                ],
              ),
            ),
            const SizedBox(height: 16),

            // Coupon Code
            if (_appliedCouponCode == null)
              Row(
                children: [
                  Expanded(
                    child: TextField(
                      controller: _couponController,
                      decoration: InputDecoration(
                        hintText: 'Enter Coupon Code',
                        isDense: true,
                        contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                        border: OutlineInputBorder(borderRadius: BorderRadius.circular(8)),
                      ),
                    ),
                  ),
                  const SizedBox(width: 8),
                  ElevatedButton(
                    onPressed: _isApplyingCoupon ? null : _applyCoupon,
                    style: ElevatedButton.styleFrom(
                      backgroundColor: AppColors.indigo600,
                      foregroundColor: Colors.white,
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                    ),
                    child: _isApplyingCoupon 
                        ? const SizedBox(width: 16, height: 16, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                        : const Text('Apply'),
                  ),
                ],
              )
            else
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                decoration: BoxDecoration(color: Colors.green.withValues(alpha: 0.1), borderRadius: BorderRadius.circular(8)),
                child: Row(
                  children: [
                    const Icon(Icons.check_circle, color: Colors.green, size: 18),
                    const SizedBox(width: 6),
                    Expanded(
                      child: Text('$_appliedCouponCode applied', style: const TextStyle(color: Colors.green, fontWeight: FontWeight.w600, fontSize: 13), overflow: TextOverflow.ellipsis),
                    ),
                    const SizedBox(width: 8),
                    Text('-${_discountPercent.toStringAsFixed(0)}%', style: const TextStyle(color: Colors.green, fontWeight: FontWeight.bold, fontSize: 13)),
                  ],
                ),
              ),
            
            const SizedBox(height: 24),
            
            // Total
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                const Text('Total Payable', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
                Text('₹$finalPrice', style: TextStyle(fontSize: 24, fontWeight: FontWeight.bold, color: AppColors.indigo600)),
              ],
            ),
            
            const SizedBox(height: 24),

            // Payment Buttons
            if (_isLoadingSettings)
              const Center(child: CircularProgressIndicator())
            else if (_isProcessingPayment)
              const Center(child: Column(
                children: [
                  CircularProgressIndicator(),
                  SizedBox(height: 8),
                  Text('Processing transaction...'),
                ],
              ))
            else ...[
              if (finalPrice == 0)
                 ElevatedButton(
                  onPressed: () => _processDirectSubscription('FREE_${DateTime.now().millisecondsSinceEpoch}', 'Free'),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: AppColors.indigo600,
                    foregroundColor: Colors.white,
                    padding: const EdgeInsets.symmetric(vertical: 16),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                  ),
                  child: const Text('Get Plan for Free', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
                )
              else if (_paymentSettings?['razorpayEnable'] == '1')
                ElevatedButton(
                  onPressed: _startRazorpayPayment,
                  style: ElevatedButton.styleFrom(
                    backgroundColor: Colors.blueAccent.shade700,
                    foregroundColor: Colors.white,
                    padding: const EdgeInsets.symmetric(vertical: 16),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                  ),
                  child: const Text('Pay with Razorpay', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
                )
              else
                const Text('No payment methods available', textAlign: TextAlign.center, style: TextStyle(color: Colors.red)),
            ],
          ],
        ),
      ),
    );
  }
}

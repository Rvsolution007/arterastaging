import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:brandkit_mobile/controllers/auth_controller.dart';
import 'package:brandkit_mobile/services/api_service.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'dart:io';
import 'dart:convert';
import 'package:flutter/foundation.dart';

class ErrorSubmissionDialog extends StatefulWidget {
  final String errorCode;
  final String errorMessage;

  const ErrorSubmissionDialog({
    Key? key,
    required this.errorCode,
    required this.errorMessage,
  }) : super(key: key);

  @override
  State<ErrorSubmissionDialog> createState() => _ErrorSubmissionDialogState();
}

class _ErrorSubmissionDialogState extends State<ErrorSubmissionDialog> {
  bool _isSubmitting = false;
  bool _showTechnicalDetails = false;

  String get _userFriendlyMessage {
    final msg = widget.errorMessage.toLowerCase();
    if (msg.contains('renderflex') || msg.contains('overflow')) {
      return "We've detected a small layout issue on your screen. This won't affect your work, but reporting it helps us fix it!";
    }
    if (msg.contains('decoding') || msg.contains('image')) {
      return "There was a problem loading some images. Please check your internet connection or try again later.";
    }
    if (msg.contains('404') || msg.contains('not found')) {
      return "We couldn't find the data you were looking for. It might have been moved or deleted.";
    }
    return "Something didn't go quite right in the app. Would you like to report this to our team?";
  }

  Future<void> _submitError() async {
    // ... same logic as before ...
    setState(() {
      _isSubmitting = true;
    });

    try {
      final prefs = await SharedPreferences.getInstance();
      final userId = prefs.getString('userId');

      if (userId == null || userId.isEmpty) {
        Get.snackbar("Error", "You must be logged in to report problems.",
            backgroundColor: Colors.red, colorText: Colors.white);
        return;
      }

      String deviceInfo = 'Unknown';
      if (kIsWeb) {
        deviceInfo = 'Web Browser';
      } else {
        deviceInfo = Platform.isAndroid ? 'Android' : (Platform.isIOS ? 'iOS' : 'Other');
        try {
          deviceInfo += ' ' + Platform.operatingSystemVersion;
        } catch (e) { }
      }

      final response = await ApiService.post('/report-error', {
        'userId': userId,
        'error_code': widget.errorCode,
        'error_message': widget.errorMessage, // Keep technical message for admin
        'device_info': deviceInfo,
      });

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['status'] == 'success') {
          if (mounted) {
            Navigator.of(context).pop();
          }
          Get.snackbar(
            "Thank You!",
            "Your report has been submitted. Our team will look into it.",
            backgroundColor: Colors.green,
            colorText: Colors.white,
            snackPosition: SnackPosition.BOTTOM,
          );
          return;
        }
      }
      Get.snackbar("Failed", "Could not submit report.",
          backgroundColor: Colors.red, colorText: Colors.white);
    } catch (e) {
      Get.snackbar("Error", "Something went wrong while submitting.",
          backgroundColor: Colors.red, colorText: Colors.white);
    } finally {
      if (mounted) {
        setState(() {
          _isSubmitting = false;
        });
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Dialog(
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(24)),
      child: Padding(
        padding: const EdgeInsets.all(24.0),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: Colors.amber.shade50,
                shape: BoxShape.circle,
              ),
              child: Icon(Icons.bug_report_outlined, color: Colors.amber.shade800, size: 40),
            ),
            const SizedBox(height: 20),
            const Text(
              "Report a Problem",
              style: TextStyle(fontSize: 20, fontWeight: FontWeight.w900),
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: 12),
            Text(
              _userFriendlyMessage,
              style: TextStyle(fontSize: 14, color: Colors.grey.shade600, height: 1.5),
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: 24),
            
            // Technical details toggle
            InkWell(
              onTap: () => setState(() => _showTechnicalDetails = !_showTechnicalDetails),
              child: Padding(
                padding: const EdgeInsets.symmetric(vertical: 8.0),
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    Text(
                      _showTechnicalDetails ? "Hide technical details" : "Show technical details",
                      style: TextStyle(fontSize: 12, color: Colors.blue.shade700, fontWeight: FontWeight.bold),
                    ),
                    Icon(
                      _showTechnicalDetails ? Icons.keyboard_arrow_up : Icons.keyboard_arrow_down,
                      size: 16,
                      color: Colors.blue.shade700,
                    ),
                  ],
                ),
              ),
            ),
            
            if (_showTechnicalDetails) ...[
              const SizedBox(height: 8),
              Container(
                width: double.infinity,
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: Colors.grey.shade50,
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(color: Colors.grey.shade200),
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text("Code: ${widget.errorCode}", style: const TextStyle(fontSize: 11, fontWeight: FontWeight.bold, fontFamily: 'monospace')),
                    const SizedBox(height: 4),
                    Text(widget.errorMessage, style: const TextStyle(fontSize: 10, color: Colors.grey, fontFamily: 'monospace')),
                  ],
                ),
              ),
            ],
            
            const SizedBox(height: 24),
            Row(
              children: [
                Expanded(
                  child: TextButton(
                    onPressed: _isSubmitting ? null : () => Get.back(),
                    child: Text("Not Now", style: TextStyle(color: Colors.grey.shade600, fontWeight: FontWeight.bold)),
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  flex: 2,
                  child: ElevatedButton(
                    onPressed: _isSubmitting ? null : _submitError,
                    style: ElevatedButton.styleFrom(
                      backgroundColor: Colors.indigo,
                      foregroundColor: Colors.white,
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                      padding: const EdgeInsets.symmetric(vertical: 14),
                      elevation: 0,
                    ),
                    child: _isSubmitting
                        ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                        : const Text("Submit Report", style: TextStyle(fontWeight: FontWeight.bold)),
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}

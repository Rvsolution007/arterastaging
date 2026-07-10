import 'dart:io';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:package_info_plus/package_info_plus.dart';
import 'package:url_launcher/url_launcher.dart';

class AppUpdateController extends GetxController {
  
  static void showUpdateDialogIfNeeded(Map<String, dynamic> appUpdateData, {bool isManualCheck = false}) async {
    if (appUpdateData.isEmpty) {
      if (isManualCheck) {
        Get.snackbar('Update', 'Failed to fetch update info.', backgroundColor: Colors.orange, colorText: Colors.white);
      }
      return;
    }

    bool updatePopupShow = appUpdateData['updatePopupShow']?.toString() == '1';
    if (!updatePopupShow && !isManualCheck) {
      return;
    }

    String newVersionCodeStr = appUpdateData['newAppVersionCode']?.toString() ?? '0';
    // Sometimes version could be passed as double "1.8", so we parse it safely
    double newVersionDouble = double.tryParse(newVersionCodeStr) ?? 0.0;
    int newVersionCode = newVersionDouble.toInt();
    
    PackageInfo packageInfo = await PackageInfo.fromPlatform();
    int currentVersionCode = int.tryParse(packageInfo.buildNumber) ?? 0;

    if (currentVersionCode < newVersionCode) {
      // Update available
      bool cancelOption = appUpdateData['cancelOption']?.toString() == '1';
      String description = appUpdateData['description'] ?? 'A new version of the app is available. Please update to the latest version to get the best experience.';
      String appLink = appUpdateData['appLink'] ?? '';

      _showUpdateDialog(description, appLink, cancelOption);
    } else {
      if (isManualCheck) {
        Get.snackbar('Up to Date', 'You are already using the latest version of the app.', 
          backgroundColor: Colors.green, colorText: Colors.white);
      }
    }
  }

  static void _showUpdateDialog(String description, String appLink, bool cancelOption) {
    Get.dialog(
      WillPopScope(
        onWillPop: () async => cancelOption, // Prevent back button if forced
        child: AlertDialog(
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(15)),
          title: Row(
            children: [
              Icon(Icons.system_update, color: Colors.blueAccent),
              SizedBox(width: 10),
              Text('Update Available', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 18)),
            ],
          ),
          content: Text(description),
          actions: [
            if (cancelOption)
              TextButton(
                onPressed: () {
                  Get.back();
                },
                child: Text('LATER', style: TextStyle(color: Colors.grey)),
              ),
            ElevatedButton(
              onPressed: () async {
                if (appLink.isNotEmpty) {
                  Uri url = Uri.parse(appLink);
                  if (await canLaunchUrl(url)) {
                    await launchUrl(url, mode: LaunchMode.externalApplication);
                  }
                }
              },
              style: ElevatedButton.styleFrom(
                backgroundColor: Colors.blueAccent,
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
              ),
              child: Text('UPDATE NOW', style: TextStyle(color: Colors.white)),
            ),
          ],
        ),
      ),
      barrierDismissible: cancelOption, // Prevent tap outside if forced
    );
  }
}

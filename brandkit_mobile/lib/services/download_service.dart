import 'dart:convert';
import 'dart:io';
import 'package:flutter/foundation.dart';
import 'package:path_provider/path_provider.dart';
import 'package:shared_preferences/shared_preferences.dart';

class DownloadService {
  static const String _prefsKey = 'my_downloads';

  static Future<void> saveDownload(Uint8List bytes, {required bool isVideo, required String fileName}) async {
    if (kIsWeb) return; // Not supported on web
    
    try {
      final directory = await getApplicationDocumentsDirectory();
      final downloadsDir = Directory('${directory.path}/brandkit_downloads');
      if (!await downloadsDir.exists()) {
        await downloadsDir.create(recursive: true);
      }
      
      final ext = isVideo ? '.mp4' : '.png';
      final file = File('${downloadsDir.path}/${fileName}$ext');
      await file.writeAsBytes(bytes);
      
      // Save to SharedPreferences
      final prefs = await SharedPreferences.getInstance();
      List<String> downloads = prefs.getStringList(_prefsKey) ?? [];
      
      final newDownload = {
        'path': file.path,
        'type': isVideo ? 'video' : 'image',
        'timestamp': DateTime.now().toIso8601String(),
        'fileName': fileName,
      };
      
      // Remove if exists to prevent duplicates, then insert at top
      downloads.removeWhere((item) {
        final map = jsonDecode(item);
        return map['path'] == file.path;
      });
      
      downloads.insert(0, jsonEncode(newDownload));
      await prefs.setStringList(_prefsKey, downloads);
      
      debugPrint('[DownloadService] Saved to local storage: ${file.path}');
    } catch (e) {
      debugPrint('[DownloadService] Error saving download: $e');
    }
  }

  static Future<List<Map<String, dynamic>>> getDownloads() async {
    if (kIsWeb) return [];
    
    try {
      final prefs = await SharedPreferences.getInstance();
      List<String> downloads = prefs.getStringList(_prefsKey) ?? [];
      List<Map<String, dynamic>> validDownloads = [];
      List<String> validDownloadStrings = [];
      
      bool updated = false;

      for (String itemStr in downloads) {
        try {
          final item = jsonDecode(itemStr) as Map<String, dynamic>;
          final path = item['path'];
          if (path != null && await File(path).exists()) {
            validDownloads.add(item);
            validDownloadStrings.add(itemStr);
          } else {
            updated = true;
          }
        } catch (_) {
          updated = true;
        }
      }
      
      if (updated) {
        await prefs.setStringList(_prefsKey, validDownloadStrings);
      }
      
      return validDownloads;
    } catch (e) {
      debugPrint('[DownloadService] Error getting downloads: $e');
      return [];
    }
  }
}

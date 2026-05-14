import 'dart:io';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import '../utils/app_colors.dart';
import '../utils/app_text_styles.dart';
import '../services/download_service.dart';
import '../widgets/story_viewer.dart';

class DownloadsScreen extends StatefulWidget {
  const DownloadsScreen({super.key});

  @override
  State<DownloadsScreen> createState() => _DownloadsScreenState();
}

class _DownloadsScreenState extends State<DownloadsScreen> {
  bool _isLoading = true;
  List<Map<String, dynamic>> _downloads = [];

  @override
  void initState() {
    super.initState();
    _loadDownloads();
  }

  Future<void> _loadDownloads() async {
    final downloads = await DownloadService.getDownloads();
    if (mounted) {
      setState(() {
        _downloads = downloads;
        _isLoading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        title: Text('My Downloads', style: AppTextStyles.heading4),
        leading: IconButton(
          icon: Icon(Icons.arrow_back, color: AppColors.gray900),
          onPressed: () => Get.back(),
        ),
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : _downloads.isEmpty
              ? Center(
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Icon(Icons.download_for_offline_outlined, size: 64, color: AppColors.gray300),
                      const SizedBox(height: 16),
                      Text('No downloads yet', style: AppTextStyles.bodyLarge),
                      const SizedBox(height: 8),
                      Text('Designs and videos you download will appear here', 
                        style: AppTextStyles.bodyMedium.copyWith(color: AppColors.gray500)),
                    ],
                  ),
                )
              : GridView.builder(
                  padding: const EdgeInsets.all(16),
                  gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                    crossAxisCount: 2,
                    crossAxisSpacing: 16,
                    mainAxisSpacing: 16,
                    childAspectRatio: 1,
                  ),
                  itemCount: _downloads.length,
                  itemBuilder: (context, index) {
                    final item = _downloads[index];
                    final isVideo = item['type'] == 'video';
                    final path = item['path'] as String;

                    return GestureDetector(
                      onTap: () {
                        if (!isVideo) {
                          Navigator.push(context, MaterialPageRoute(
                            builder: (_) => StoryViewer(
                              images: [path],
                              isLocalFile: true,
                            ),
                          ));
                        } else {
                          Get.snackbar('Video', 'Video playback not fully supported in local preview yet. Check your gallery.', snackPosition: SnackPosition.BOTTOM);
                        }
                      },
                      child: Container(
                        decoration: BoxDecoration(
                          borderRadius: BorderRadius.circular(16),
                          color: AppColors.gray200,
                          image: !isVideo ? DecorationImage(
                            image: FileImage(File(path)),
                            fit: BoxFit.cover,
                          ) : null,
                          boxShadow: [
                            BoxShadow(
                              color: Colors.black.withValues(alpha: 0.1),
                              blurRadius: 4,
                              offset: const Offset(0, 2),
                            ),
                          ],
                        ),
                        clipBehavior: Clip.antiAlias,
                        child: isVideo
                            ? Center(
                                child: Container(
                                  padding: const EdgeInsets.all(12),
                                  decoration: BoxDecoration(
                                    color: Colors.black.withValues(alpha: 0.5),
                                    shape: BoxShape.circle,
                                  ),
                                  child: const Icon(Icons.play_arrow, color: Colors.white, size: 32),
                                ),
                              )
                            : null,
                      ),
                    );
                  },
                ),
    );
  }
}

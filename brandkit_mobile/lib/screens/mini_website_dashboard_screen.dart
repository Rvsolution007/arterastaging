import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:flutter/services.dart';
import '../controllers/mini_website_controller.dart';
import 'mini_website_templates_screen.dart';
import '../utils/app_colors.dart';
import '../widgets/coming_soon_widget.dart';
import '../config/app_config.dart';

class MiniWebsiteDashboardScreen extends StatelessWidget {
  MiniWebsiteDashboardScreen({super.key});

  final MiniWebsiteController controller = Get.put(MiniWebsiteController());

  @override
  Widget build(BuildContext context) {
    if (!AppConfig.isLocal) {
      return Scaffold(
        backgroundColor: Colors.white,
        appBar: AppBar(
          title: const Text('Mini Website', style: TextStyle(color: Colors.black)),
          backgroundColor: Colors.white,
          elevation: 0,
          iconTheme: const IconThemeData(color: Colors.black),
        ),
        body: const ComingSoonWidget(title: 'Mini Website'),
      );
    }

    return Scaffold(
      backgroundColor: Colors.white,
      appBar: AppBar(
        title: const Text('My Mini Websites', style: TextStyle(color: Colors.black)),
        backgroundColor: Colors.white,
        elevation: 0,
        iconTheme: const IconThemeData(color: Colors.black),
      ),
      body: Obx(() {
        if (controller.myLinks.isEmpty) {
          return Center(
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Icon(Icons.language, size: 80, color: Colors.grey[300]),
                const SizedBox(height: 16),
                const Text(
                  'No Mini Websites Yet!',
                  style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                ),
                const SizedBox(height: 8),
                const Text('Create your first digital business card/website.'),
                const SizedBox(height: 24),
                ElevatedButton(
                  onPressed: () {
                    Get.to(() => MiniWebsiteTemplatesScreen());
                  },
                  style: ElevatedButton.styleFrom(
                    backgroundColor: AppColors.primary,
                    padding: const EdgeInsets.symmetric(horizontal: 32, vertical: 12),
                  ),
                  child: const Text('Create New'),
                )
              ],
            ),
          );
        }

        return RefreshIndicator(
          onRefresh: () => controller.fetchMyLinks(),
          child: ListView.builder(
            padding: const EdgeInsets.all(16),
            itemCount: controller.myLinks.length,
            itemBuilder: (context, index) {
              final site = controller.myLinks[index];
              return Card(
                margin: const EdgeInsets.only(bottom: 16),
                elevation: 2,
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                child: Padding(
                  padding: const EdgeInsets.all(16.0),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        children: [
                          site['preview_image'] != null
                              ? ClipRRect(
                                  borderRadius: BorderRadius.circular(8),
                                  child: Image.network(site['preview_image'], width: 60, height: 60, fit: BoxFit.cover),
                                )
                              : Container(
                                  width: 60, height: 60,
                                  decoration: BoxDecoration(color: Colors.grey[200], borderRadius: BorderRadius.circular(8)),
                                  child: const Icon(Icons.image, color: Colors.grey),
                                ),
                          const SizedBox(width: 16),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(site['template_name'] ?? 'Template', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                                const SizedBox(height: 4),
                                Row(
                                  children: [
                                    const Icon(Icons.remove_red_eye, size: 14, color: Colors.grey),
                                    const SizedBox(width: 4),
                                    Text('${site['views_count']} Views', style: const TextStyle(color: Colors.grey)),
                                  ],
                                ),
                              ],
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 16),
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                        decoration: BoxDecoration(
                          color: Colors.grey[100],
                          borderRadius: BorderRadius.circular(8),
                          border: Border.all(color: Colors.grey[300]!),
                        ),
                        child: Row(
                          children: [
                            Expanded(
                              child: Text(
                                site['url'],
                                style: const TextStyle(color: Colors.blue, overflow: TextOverflow.ellipsis),
                              ),
                            ),
                            IconButton(
                              icon: const Icon(Icons.copy, size: 20),
                              onPressed: () {
                                Clipboard.setData(ClipboardData(text: site['url']));
                                Get.snackbar('Copied', 'Link copied to clipboard!', snackPosition: SnackPosition.BOTTOM);
                              },
                            )
                          ],
                        ),
                      )
                    ],
                  ),
                ),
              );
            },
          ),
        );
      }),
      floatingActionButton: Obx(() {
        if (controller.myLinks.isNotEmpty) {
          return FloatingActionButton(
            backgroundColor: AppColors.primary,
            onPressed: () => Get.to(() => MiniWebsiteTemplatesScreen()),
            child: const Icon(Icons.add),
          );
        }
        return const SizedBox.shrink();
      }),
    );
  }
}

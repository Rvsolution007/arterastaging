import 'package:flutter/material.dart';
import 'package:get/get.dart';
import '../controllers/mini_website_controller.dart';
import '../utils/app_colors.dart';

class MiniWebsiteTemplatesScreen extends StatelessWidget {
  MiniWebsiteTemplatesScreen({super.key});

  final MiniWebsiteController controller = Get.find<MiniWebsiteController>();

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.grey[100],
      appBar: AppBar(
        title: const Text('Select Template', style: TextStyle(color: Colors.black)),
        backgroundColor: Colors.white,
        elevation: 0,
        iconTheme: const IconThemeData(color: Colors.black),
      ),
      body: Obx(() {
        if (controller.isLoading.value) {
          return const Center(child: CircularProgressIndicator());
        }

        if (controller.templates.isEmpty) {
          return const Center(child: Text('No templates available right now.'));
        }

        return GridView.builder(
          padding: const EdgeInsets.all(16),
          gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
            crossAxisCount: 2,
            crossAxisSpacing: 16,
            mainAxisSpacing: 16,
            childAspectRatio: 0.65,
          ),
          itemCount: controller.templates.length,
          itemBuilder: (context, index) {
            final template = controller.templates[index];
            return GestureDetector(
              onTap: () => _showGenerateDialog(context, template),
              child: Card(
                elevation: 2,
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    Expanded(
                      child: ClipRRect(
                        borderRadius: const BorderRadius.vertical(top: Radius.circular(12)),
                        child: template['preview_image'] != null
                            ? Image.network(template['preview_image'], fit: BoxFit.cover)
                            : Container(color: Colors.grey[300], child: const Icon(Icons.image, color: Colors.grey)),
                      ),
                    ),
                    Padding(
                      padding: const EdgeInsets.all(12.0),
                      child: Column(
                        children: [
                          Text(
                            template['name'] ?? 'Template',
                            style: const TextStyle(fontWeight: FontWeight.bold),
                            textAlign: TextAlign.center,
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                          ),
                          const SizedBox(height: 8),
                          Container(
                            width: double.infinity,
                            padding: const EdgeInsets.symmetric(vertical: 6),
                            decoration: BoxDecoration(
                              color: AppColors.primary,
                              borderRadius: BorderRadius.circular(20),
                            ),
                            child: const Text(
                              'Use Template',
                              textAlign: TextAlign.center,
                              style: TextStyle(color: Colors.white, fontSize: 12, fontWeight: FontWeight.bold),
                            ),
                          )
                        ],
                      ),
                    )
                  ],
                ),
              ),
            );
          },
        );
      }),
    );
  }

  void _showGenerateDialog(BuildContext context, dynamic template) {
    showDialog(
      context: context,
      builder: (context) {
        return AlertDialog(
          title: const Text('Generate Website'),
          content: Text('Do you want to generate a mini website using "${template['name']}" with your active business profile?'),
          actions: [
            TextButton(
              onPressed: () => Get.back(),
              child: const Text('Cancel', style: TextStyle(color: Colors.grey)),
            ),
            Obx(() {
              return ElevatedButton(
                onPressed: controller.isGenerating.value
                    ? null
                    : () async {
                        final success = await controller.generateWebsite(template['id']);
                        if (success) {
                          Get.back(); // close dialog
                          Get.back(); // go back to dashboard
                          Get.snackbar('Success', 'Mini Website generated successfully!', snackPosition: SnackPosition.BOTTOM);
                        } else {
                          Get.snackbar('Error', 'Failed to generate website.', snackPosition: SnackPosition.BOTTOM, backgroundColor: Colors.red, colorText: Colors.white);
                        }
                      },
                style: ElevatedButton.styleFrom(backgroundColor: AppColors.primary),
                child: controller.isGenerating.value
                    ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                    : const Text('Generate Now'),
              );
            }),
          ],
        );
      },
    );
  }
}

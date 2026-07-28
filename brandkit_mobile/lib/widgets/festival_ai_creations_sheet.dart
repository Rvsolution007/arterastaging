import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../controllers/festival_ai_job_controller.dart';
import '../features/ai_editable_v1/screens/ai_editable_editor_screen.dart';

class FestivalAiCreationsSheet extends StatelessWidget {
  const FestivalAiCreationsSheet({super.key});

  static Future<void> show(BuildContext context) {
    return showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (_) => const FestivalAiCreationsSheet(),
    );
  }

  @override
  Widget build(BuildContext context) {
    final jobs = Get.find<FestivalAiJobController>();
    return DraggableScrollableSheet(
      initialChildSize: .72,
      minChildSize: .45,
      maxChildSize: .92,
      builder: (context, scrollController) => Container(
        decoration: const BoxDecoration(
          color: Color(0xFFF8FAFC),
          borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
        ),
        child: Column(
          children: [
            const SizedBox(height: 10),
            Container(
              width: 38,
              height: 4,
              decoration: BoxDecoration(
                color: const Color(0xFFCBD5E1),
                borderRadius: BorderRadius.circular(4),
              ),
            ),
            Padding(
              padding: const EdgeInsets.fromLTRB(18, 14, 10, 10),
              child: Row(
                children: [
                  const Icon(
                    Icons.auto_awesome_rounded,
                    color: Color(0xFF5B4BE8),
                    size: 20,
                  ),
                  const SizedBox(width: 8),
                  const Expanded(
                    child: Text(
                      'My AI creations',
                      style: TextStyle(
                        fontSize: 17,
                        fontWeight: FontWeight.w800,
                        color: Color(0xFF1E293B),
                      ),
                    ),
                  ),
                  Obx(
                    () => IconButton(
                      tooltip: 'Refresh',
                      onPressed: jobs.isRefreshingHistory.value
                          ? null
                          : () => jobs.refreshHistory(showLoader: true),
                      icon: jobs.isRefreshingHistory.value
                          ? const SizedBox(
                              width: 18,
                              height: 18,
                              child: CircularProgressIndicator(strokeWidth: 2),
                            )
                          : const Icon(
                              Icons.refresh_rounded,
                              color: Color(0xFF5B4BE8),
                            ),
                    ),
                  ),
                ],
              ),
            ),
            Expanded(
              child: Obx(() {
                final history = jobs.history;
                if (history.isEmpty) {
                  return ListView(
                    controller: scrollController,
                    children: const [
                      SizedBox(height: 95),
                      Icon(
                        Icons.auto_awesome_outlined,
                        color: Color(0xFFB6B8D8),
                        size: 48,
                      ),
                      SizedBox(height: 12),
                      Center(
                        child: Text(
                          'Your AI creations will appear here.',
                          style: TextStyle(
                            color: Color(0xFF64748B),
                            fontSize: 13,
                          ),
                        ),
                      ),
                    ],
                  );
                }

                return RefreshIndicator(
                  onRefresh: () => jobs.refreshHistory(showLoader: true),
                  child: ListView.separated(
                    controller: scrollController,
                    padding: const EdgeInsets.fromLTRB(14, 4, 14, 28),
                    itemCount: history.length,
                    separatorBuilder: (_, _) => const SizedBox(height: 9),
                    itemBuilder: (_, index) =>
                        _creationCard(context, history[index]),
                  ),
                );
              }),
            ),
          ],
        ),
      ),
    );
  }

  Widget _creationCard(BuildContext context, Map<String, dynamic> job) {
    final status = job['status']?.toString() ?? 'queued';
    final completed = status == 'completed';
    final failed = status == 'failed';
    final imageUrl = job['image_url']?.toString() ?? '';
    final editable = job['editable_document'];
    final editableInfo = editable is Map
        ? Map<String, dynamic>.from(editable)
        : const <String, dynamic>{};
    final editableDocumentId = editableInfo['document_id']?.toString() ?? '';
    final festival = job['festival_title']?.toString().trim();
    final title = (festival == null || festival.isEmpty)
        ? 'Festival AI visual'
        : festival;
    final subtitle = failed
        ? (job['error_message']?.toString() ?? 'Your quota was restored.')
        : completed
        ? '${job['style_name'] ?? 'Selected look'} • Ready'
        : status == 'processing'
        ? 'Creating your visual in the background'
        : 'Waiting to start generation';
    final color = failed
        ? const Color(0xFFDC2626)
        : completed
        ? const Color(0xFF059669)
        : const Color(0xFF5B4BE8);

    return InkWell(
      onTap: completed
          ? () {
              if (editableDocumentId.isNotEmpty) {
                Get.to(
                  () => AiEditableEditorScreen(documentId: editableDocumentId),
                );
                return;
              }
              if (imageUrl.isNotEmpty) _preview(context, imageUrl, title);
            }
          : null,
      borderRadius: BorderRadius.circular(16),
      child: Container(
        padding: const EdgeInsets.all(10),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(16),
          border: Border.all(
            color: failed ? const Color(0xFFFECACA) : const Color(0xFFE9E7FF),
          ),
        ),
        child: Row(
          children: [
            ClipRRect(
              borderRadius: BorderRadius.circular(11),
              child: Container(
                width: 58,
                height: 58,
                color: const Color(0xFFF2F0FF),
                child: completed && imageUrl.isNotEmpty
                    ? Image.network(
                        imageUrl,
                        fit: BoxFit.cover,
                        errorBuilder: (_, _, _) =>
                            Icon(Icons.image_outlined, color: color),
                      )
                    : Icon(
                        failed
                            ? Icons.error_outline_rounded
                            : Icons.auto_awesome_rounded,
                        color: color,
                      ),
              ),
            ),
            const SizedBox(width: 10),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    title,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(
                      fontWeight: FontWeight.w800,
                      fontSize: 13,
                      color: Color(0xFF1E293B),
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    subtitle,
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(
                      fontSize: 10.5,
                      color: Color(0xFF64748B),
                      height: 1.25,
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(width: 6),
            Column(
              children: [
                Container(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 7,
                    vertical: 4,
                  ),
                  decoration: BoxDecoration(
                    color: color.withValues(alpha: .10),
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Text(
                    completed
                        ? 'Ready'
                        : failed
                        ? 'Failed'
                        : status == 'processing'
                        ? 'Creating'
                        : 'Queued',
                    style: TextStyle(
                      color: color,
                      fontSize: 9,
                      fontWeight: FontWeight.w800,
                    ),
                  ),
                ),
                if (completed) ...[
                  const SizedBox(height: 7),
                  if (editableDocumentId.isNotEmpty)
                    IconButton(
                      tooltip: 'Edit layers',
                      padding: EdgeInsets.zero,
                      constraints: const BoxConstraints.tightFor(
                        width: 28,
                        height: 28,
                      ),
                      onPressed: () => Get.to(
                        () => AiEditableEditorScreen(
                          documentId: editableDocumentId,
                        ),
                      ),
                      icon: const Icon(
                        Icons.layers_outlined,
                        size: 18,
                        color: Color(0xFF4F46E5),
                      ),
                    )
                  else
                    Icon(Icons.open_in_full_rounded, size: 14, color: color),
                ],
              ],
            ),
          ],
        ),
      ),
    );
  }

  void _preview(BuildContext context, String imageUrl, String title) {
    showDialog<void>(
      context: context,
      builder: (_) => Dialog(
        insetPadding: const EdgeInsets.all(18),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Padding(
              padding: const EdgeInsets.fromLTRB(16, 14, 8, 8),
              child: Row(
                children: [
                  Expanded(
                    child: Text(
                      title,
                      style: const TextStyle(fontWeight: FontWeight.w800),
                    ),
                  ),
                  IconButton(
                    onPressed: () => Navigator.pop(context),
                    icon: const Icon(Icons.close_rounded),
                  ),
                ],
              ),
            ),
            InteractiveViewer(
              child: Image.network(
                imageUrl,
                fit: BoxFit.contain,
                errorBuilder: (_, _, _) => const Padding(
                  padding: EdgeInsets.all(34),
                  child: Text('Image preview could not be loaded.'),
                ),
              ),
            ),
            const SizedBox(height: 12),
          ],
        ),
      ),
    );
  }
}

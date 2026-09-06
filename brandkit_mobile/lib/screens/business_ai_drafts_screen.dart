import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../features/ai_editable_v1/screens/ai_editable_editor_screen.dart';
import '../services/api_service.dart';

/// User-owned Custom AI designs explicitly saved from the result screen.
class BusinessAiDraftsScreen extends StatefulWidget {
  const BusinessAiDraftsScreen({super.key});

  @override
  State<BusinessAiDraftsScreen> createState() => _BusinessAiDraftsScreenState();
}

class _BusinessAiDraftsScreenState extends State<BusinessAiDraftsScreen> {
  bool _loading = true;
  String? _error;
  List<Map<String, dynamic>> _drafts = const [];

  @override
  void initState() {
    super.initState();
    _loadDrafts();
  }

  Future<void> _loadDrafts() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final response = await ApiService.get('/business-ai/drafts');
      final data = jsonDecode(response.body);
      if (response.statusCode != 200 ||
          data is! Map ||
          data['success'] != true) {
        throw Exception(
          data is Map ? data['message'] : 'My Designs could not be loaded.',
        );
      }
      final drafts = (data['drafts'] as List? ?? const [])
          .whereType<Map>()
          .map((item) => Map<String, dynamic>.from(item))
          .toList();
      if (mounted) setState(() => _drafts = drafts);
    } catch (error) {
      if (mounted) {
        setState(
          () => _error = error.toString().replaceFirst('Exception: ', ''),
        );
      }
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  String _savedLabel(Map<String, dynamic> draft) {
    final raw = draft['saved_as_draft_at'] ?? draft['created_at'];
    final date = DateTime.tryParse('${raw ?? ''}')?.toLocal();
    if (date == null) return 'Saved draft';
    final minute = date.minute.toString().padLeft(2, '0');
    return 'Saved ${date.day}/${date.month}/${date.year}, ${date.hour}:$minute';
  }

  void _restore(Map<String, dynamic> draft) {
    final editable = draft['editable_document'];
    final documentId = editable is Map
        ? editable['document_id']?.toString() ?? ''
        : '';
    if (documentId.isEmpty) {
      Get.snackbar(
        'Draft unavailable',
        'The editable text for this draft is still being prepared.',
      );
      return;
    }
    Get.to(() => AiEditableEditorScreen(documentId: documentId));
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF8FAFC),
      appBar: AppBar(
        title: const Text('My Designs'),
        centerTitle: true,
        actions: [
          IconButton(
            tooltip: 'Refresh',
            onPressed: _loading ? null : _loadDrafts,
            icon: const Icon(Icons.refresh_outlined),
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: _loadDrafts,
        child: _loading
            ? const Center(
                child: CircularProgressIndicator(color: Color(0xFF6434E8)),
              )
            : _error != null
            ? ListView(
                children: [
                  const SizedBox(height: 160),
                  const Icon(
                    Icons.cloud_off_outlined,
                    size: 52,
                    color: Color(0xFF94A3B8),
                  ),
                  const SizedBox(height: 12),
                  Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 28),
                    child: Text(_error!, textAlign: TextAlign.center),
                  ),
                  const SizedBox(height: 12),
                  Center(
                    child: FilledButton(
                      onPressed: _loadDrafts,
                      child: const Text('Try again'),
                    ),
                  ),
                ],
              )
            : _drafts.isEmpty
            ? ListView(
                children: const [
                  SizedBox(height: 150),
                  Icon(
                    Icons.bookmark_border_rounded,
                    size: 58,
                    color: Color(0xFF94A3B8),
                  ),
                  SizedBox(height: 14),
                  Center(
                    child: Text(
                      'No saved designs yet',
                      style: TextStyle(
                        fontSize: 18,
                        fontWeight: FontWeight.w800,
                      ),
                    ),
                  ),
                  SizedBox(height: 8),
                  Padding(
                    padding: EdgeInsets.symmetric(horizontal: 42),
                    child: Text(
                      'Generate a Custom Post, then tap Save Draft to keep it here.',
                      textAlign: TextAlign.center,
                      style: TextStyle(color: Color(0xFF64748B)),
                    ),
                  ),
                ],
              )
            : ListView.separated(
                padding: const EdgeInsets.all(16),
                itemCount: _drafts.length,
                separatorBuilder: (_, _) => const SizedBox(height: 14),
                itemBuilder: (_, index) => _draftCard(_drafts[index]),
              ),
      ),
    );
  }

  Widget _draftCard(Map<String, dynamic> draft) {
    final imageUrl = draft['image_url']?.toString() ?? '';
    return Container(
      clipBehavior: Clip.antiAlias,
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(18),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: .05),
            blurRadius: 12,
            offset: const Offset(0, 5),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          AspectRatio(
            aspectRatio: 1.55,
            child: imageUrl.isEmpty
                ? const ColoredBox(
                    color: Color(0xFFF1F5F9),
                    child: Center(
                      child: Icon(
                        Icons.image_not_supported_outlined,
                        color: Color(0xFF94A3B8),
                      ),
                    ),
                  )
                : Image.network(
                    imageUrl,
                    fit: BoxFit.cover,
                    errorBuilder: (_, _, _) => const ColoredBox(
                      color: Color(0xFFF1F5F9),
                      child: Center(
                        child: Icon(
                          Icons.broken_image_outlined,
                          color: Color(0xFF94A3B8),
                        ),
                      ),
                    ),
                  ),
          ),
          Padding(
            padding: const EdgeInsets.fromLTRB(14, 13, 14, 14),
            child: Row(
              children: [
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        '${draft['purpose_title'] ?? 'Custom Post'}',
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: const TextStyle(
                          fontSize: 16,
                          fontWeight: FontWeight.w800,
                        ),
                      ),
                      const SizedBox(height: 3),
                      Text(
                        _savedLabel(draft),
                        style: const TextStyle(
                          color: Color(0xFF64748B),
                          fontSize: 12,
                        ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(width: 12),
                FilledButton.icon(
                  onPressed: () => _restore(draft),
                  icon: const Icon(Icons.edit_outlined, size: 18),
                  label: const Text('Restore'),
                  style: FilledButton.styleFrom(
                    backgroundColor: const Color(0xFF6434E8),
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

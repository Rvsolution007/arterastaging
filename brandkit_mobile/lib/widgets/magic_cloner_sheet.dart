import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';
import '../services/api_service.dart';
import '../utils/app_colors.dart';
import '../screens/editor_screen.dart';

class MagicClonerSheet extends StatefulWidget {
  const MagicClonerSheet({super.key});

  @override
  State<MagicClonerSheet> createState() => _MagicClonerSheetState();
}

class _MagicClonerSheetState extends State<MagicClonerSheet> {
  final ImagePicker _picker = ImagePicker();
  bool _isLoading = false;
  List<dynamic> _suggestedTemplates = [];
  Map<String, dynamic>? _designVibe;
  String? _errorMessage;

  Future<void> _pickAndUploadImage() async {
    try {
      final XFile? image = await _picker.pickImage(source: ImageSource.gallery);
      if (image == null) return;

      setState(() {
        _isLoading = true;
        _errorMessage = null;
        _suggestedTemplates = [];
      });

      // Send to API
      final response = await ApiService.uploadMagicClonerImage('/ai-magic-cloner', image.path);

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['success'] == true) {
          setState(() {
            _suggestedTemplates = data['suggested_templates'] ?? [];
            _designVibe = data['design_vibe_detected'];
            _isLoading = false;
          });
        } else {
          setState(() {
            _errorMessage = data['message'] ?? 'Failed to analyze image.';
            _isLoading = false;
          });
        }
      } else {
        setState(() {
          _errorMessage = 'Server error: ${response.statusCode}';
          _isLoading = false;
        });
      }
    } catch (e) {
      setState(() {
        _errorMessage = 'An error occurred: $e';
        _isLoading = false;
      });
    }
  }

  void _selectTemplate(Map<String, dynamic> template) {
    // Navigate to EditorScreen, passing the AI analysis data
    // The EditorScreen will need to be updated to handle this
    Navigator.pop(context); // Close the sheet
    
    // We treat this as a 'custom' template type to open in the editor
    Navigator.push(context, MaterialPageRoute(
      builder: (_) => EditorScreen(
        type: 'custom', 
        id: template['id'],
        frameData: template,
        designUrl: template['thumb'] ?? '',
        aiAnalysisData: template['ai_analysis_data'],
        mappingRules: template['frontend_mapping_rules'],
      ),
    ));
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: const BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.vertical(top: Radius.circular(32)),
      ),
      padding: const EdgeInsets.all(24),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          // Drag handle
          Container(
            width: 48,
            height: 6,
            decoration: BoxDecoration(
              color: Colors.grey.shade300,
              borderRadius: BorderRadius.circular(3),
            ),
            margin: const EdgeInsets.only(bottom: 24),
          ),

          // Icon and Title
          Container(
            width: 64,
            height: 64,
            decoration: BoxDecoration(
              color: Colors.purple.shade50,
              shape: BoxShape.circle,
            ),
            child: Icon(Icons.auto_awesome, color: Colors.purple.shade600, size: 32),
          ),
          const SizedBox(height: 16),
          const Text(
            'AI Magic Cloner',
            style: TextStyle(fontSize: 22, fontWeight: FontWeight.w900, color: Color(0xFF1E293B)),
          ),
          const SizedBox(height: 8),
          const Text(
            'Upload any inspiration image and we will extract its brand vibe, colors, and layout to generate magical templates for you.',
            textAlign: TextAlign.center,
            style: TextStyle(fontSize: 14, fontWeight: FontWeight.w500, color: Color(0xFF64748B)),
          ),
          const SizedBox(height: 24),

          // Upload Area or Loading or Results
          if (_isLoading)
            _buildLoadingState()
          else if (_suggestedTemplates.isNotEmpty)
            _buildResultsState()
          else
            _buildUploadBox(),

          if (_errorMessage != null)
            Padding(
              padding: const EdgeInsets.only(top: 16),
              child: Text(_errorMessage!, style: const TextStyle(color: Colors.red, fontWeight: FontWeight.bold)),
            ),

          SizedBox(height: MediaQuery.of(context).padding.bottom),
        ],
      ),
    );
  }

  Widget _buildUploadBox() {
    return GestureDetector(
      onTap: _pickAndUploadImage,
      child: Container(
        width: double.infinity,
        padding: const EdgeInsets.symmetric(vertical: 32),
        decoration: BoxDecoration(
          color: Colors.purple.shade50.withOpacity(0.5),
          borderRadius: BorderRadius.circular(24),
          border: Border.all(color: Colors.purple.shade200, width: 2, style: BorderStyle.solid), // Should ideally be dashed, but standard Flutter border is solid
        ),
        child: Column(
          children: [
            Container(
              width: 48,
              height: 48,
              decoration: const BoxDecoration(
                color: Colors.white,
                shape: BoxShape.circle,
                boxShadow: [BoxShadow(color: Colors.black12, blurRadius: 4)],
              ),
              child: Icon(Icons.cloud_upload_outlined, color: Colors.purple.shade500),
            ),
            const SizedBox(height: 12),
            Text('Upload Inspiration', style: TextStyle(color: Colors.purple.shade600, fontWeight: FontWeight.bold, fontSize: 16)),
            const SizedBox(height: 4),
            const Text('JPG, PNG (Max. 5MB)', style: TextStyle(color: Colors.grey, fontSize: 12, fontWeight: FontWeight.w600)),
          ],
        ),
      ),
    );
  }

  Widget _buildLoadingState() {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 24),
      child: Column(
        children: [
          CircularProgressIndicator(color: Colors.purple.shade600),
          const SizedBox(height: 16),
          const Text('🪄 Extracting brand vibe...', style: TextStyle(color: Color(0xFF334155), fontWeight: FontWeight.bold)),
        ],
      ),
    );
  }

  Widget _buildResultsState() {
    return Column(
      children: [
        const Text('🪄 Magical Templates Generated', style: TextStyle(fontWeight: FontWeight.bold, color: Color(0xFF1E293B))),
        const SizedBox(height: 16),
        SizedBox(
          height: 140,
          child: ListView.builder(
            scrollDirection: Axis.horizontal,
            itemCount: _suggestedTemplates.length,
            itemBuilder: (context, index) {
              final template = _suggestedTemplates[index];
              final score = template['match_score'] ?? 0;
              
              return GestureDetector(
                onTap: () => _selectTemplate(template),
                child: Container(
                  width: 120,
                  margin: const EdgeInsets.only(right: 12),
                  decoration: BoxDecoration(
                    borderRadius: BorderRadius.circular(16),
                    border: Border.all(color: Colors.purple.shade100, width: 2),
                  ),
                  clipBehavior: Clip.antiAlias,
                  child: Stack(
                    fit: StackFit.expand,
                    children: [
                      Image.network(
                        template['thumb'] ?? '',
                        fit: BoxFit.cover,
                        errorBuilder: (_, __, ___) => const Icon(Icons.broken_image, color: Colors.grey),
                      ),
                      Positioned(
                        top: 4,
                        right: 4,
                        child: Container(
                          padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                          decoration: BoxDecoration(color: Colors.green, borderRadius: BorderRadius.circular(10)),
                          child: Text('$score%', style: const TextStyle(color: Colors.white, fontSize: 10, fontWeight: FontWeight.bold)),
                        ),
                      ),
                      Positioned(
                        bottom: 0, left: 0, right: 0,
                        child: Container(
                          padding: const EdgeInsets.all(8),
                          decoration: const BoxDecoration(
                            gradient: LinearGradient(begin: Alignment.bottomCenter, end: Alignment.topCenter, colors: [Colors.black87, Colors.transparent]),
                          ),
                          child: const Text('Use Template', textAlign: TextAlign.center, style: TextStyle(color: Colors.white, fontSize: 10, fontWeight: FontWeight.bold)),
                        ),
                      ),
                    ],
                  ),
                ),
              );
            },
          ),
        ),
        const SizedBox(height: 16),
        TextButton(
          onPressed: () {
            setState(() {
              _suggestedTemplates = [];
              _designVibe = null;
            });
          },
          child: Text('Upload Another Image', style: TextStyle(color: Colors.purple.shade600, fontWeight: FontWeight.bold)),
        )
      ],
    );
  }
}

import 'dart:convert';
import 'dart:async';
import 'package:flutter/material.dart';
import 'package:flutter/foundation.dart' show kIsWeb;
import 'package:file_picker/file_picker.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:confetti/confetti.dart';
import 'package:dotted_border/dotted_border.dart';
import '../services/api_service.dart';
import '../utils/app_colors.dart';

class AiSetupWizardScreen extends StatefulWidget {
  const AiSetupWizardScreen({super.key});

  @override
  State<AiSetupWizardScreen> createState() => _AiSetupWizardScreenState();
}

class _AiSetupWizardScreenState extends State<AiSetupWizardScreen> {
  int _currentStep = 1;
  bool _isLoading = true;
  String _userId = '';
  bool _isConfigured = true;
  bool _isUploadingPdf = false;
  bool _isPickerActive = false;
  double _uploadProgress = 0.0;
  
  // Step 1 Data
  String _sourceType = 'pdf';
  final TextEditingController _urlController = TextEditingController();
  String _pdfPath = '';
  List<int>? _pdfBytes;
  String _pdfName = '';
  String _pdfSize = '';

  // Step 2 Data
  List<dynamic> _columns = [];
  String _businessDetails = '';
  
  // Step 3 Data
  List<dynamic> _products = [];
  int _totalProducts = 0;

  // Step 4 Data
  late ConfettiController _confettiController;
  int _statsCols = 0;
  int _statsCats = 0;
  int _statsProds = 0;

  // AI Processing State
  bool _isAiProcessing = false;
  String _aiStatusText = '';
  int _aiElapsedSeconds = 0;
  Timer? _aiTimer;
  Timer? _aiStatusTimer;

  @override
  void initState() {
    super.initState();
    _confettiController = ConfettiController(duration: const Duration(seconds: 3));
    _checkStatus();
  }

  @override
  void dispose() {
    _urlController.dispose();
    _confettiController.dispose();
    _aiTimer?.cancel();
    _aiStatusTimer?.cancel();
    super.dispose();
  }

  Future<void> _checkStatus() async {
    final prefs = await SharedPreferences.getInstance();
    _userId = prefs.getString('userId') ?? '';
    
    if (_userId.isEmpty) {
      if (mounted) Navigator.pop(context);
      return;
    }

    try {
      final response = await ApiService.post('/setup-wizard/status', {'userId': _userId});
      final data = jsonDecode(response.body);
      if (data['success'] == true) {
        if (mounted) {
          setState(() {
            _isConfigured = data['isConfigured'] ?? true;
            if (data['cachedColumns'] != null && data['cachedColumns'].isNotEmpty) {
              _columns = data['cachedColumns'];
              _currentStep = 2;
            }
            _isLoading = false;
          });
        }
      }
    } catch (e) {
      debugPrint('Error checking status: $e');
      if (mounted) setState(() => _isLoading = false);
    }
  }

  void _startAiProcessing(List<String> statuses) {
    setState(() {
      _isAiProcessing = true;
      _aiElapsedSeconds = 0;
      _aiStatusText = statuses[0];
    });

    _aiTimer?.cancel();
    _aiTimer = Timer.periodic(const Duration(seconds: 1), (timer) {
      if (mounted) setState(() => _aiElapsedSeconds++);
    });

    _aiStatusTimer?.cancel();
    int statusIdx = 0;
    _aiStatusTimer = Timer.periodic(const Duration(seconds: 3), (timer) {
      statusIdx = (statusIdx + 1) % statuses.length;
      if (mounted) setState(() => _aiStatusText = statuses[statusIdx]);
    });
  }

  void _stopAiProcessing() {
    _aiTimer?.cancel();
    _aiStatusTimer?.cancel();
    if (mounted) setState(() => _isAiProcessing = false);
  }

  String get _formattedTime {
    final mins = (_aiElapsedSeconds / 60).floor().toString().padLeft(2, '0');
    final secs = (_aiElapsedSeconds % 60).toString().padLeft(2, '0');
    return '$mins:$secs';
  }

  Future<void> _pickPdf() async {
    if (_isPickerActive) return;
    
    try {
      _isPickerActive = true;
      
      // On Flutter Web, FileType.custom with allowedExtensions can crash 
      // due to MIME type mapping issues in Chrome. Use FileType.any on web
      // and validate extension manually.
      FilePickerResult? result;
      if (kIsWeb) {
        result = await FilePicker.platform.pickFiles(
          type: FileType.any,
          withData: true,
        );
      } else {
        result = await FilePicker.platform.pickFiles(
          type: FileType.custom,
          allowedExtensions: ['pdf'],
          withData: true,
        );
      }

      if (result != null && result.files.isNotEmpty) {
        final file = result.files.single;
        
        // Validate PDF extension manually (especially needed for web)
        if (!file.name.toLowerCase().endsWith('.pdf')) {
          if (mounted) {
            ScaffoldMessenger.of(context).showSnackBar(
              const SnackBar(content: Text('Please select a PDF file only.'), backgroundColor: Colors.red),
            );
          }
          return;
        }
        
        final sizeMb = file.size / (1024 * 1024);
        
        if (sizeMb > 60) {
          if (mounted) ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('File too large. Maximum size is 60MB.')));
          return;
        }
        
        // On Flutter Web, file.path is ALWAYS null. Only bytes are available.
        // On native, both path and bytes can be available.
        if (file.bytes == null && (file.path == null || file.path!.isEmpty)) {
          if (mounted) ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Failed to read file data. Please try again.')));
          return;
        }

        setState(() {
          _isUploadingPdf = true;
          _uploadProgress = 0.0;
        });

        // Smooth upload progress animation
        for (int i = 1; i <= 10; i++) {
          await Future.delayed(const Duration(milliseconds: 80));
          if (mounted) {
            setState(() {
              _uploadProgress = i / 10.0;
            });
          }
        }

        if (mounted) {
          setState(() {
            _pdfPath = file.path ?? '';
            _pdfBytes = file.bytes;
            _pdfName = file.name;
            _pdfSize = '${sizeMb.toStringAsFixed(1)} MB';
            _sourceType = 'pdf';
            _isUploadingPdf = false;
          });
          debugPrint('PDF loaded: name=$_pdfName, pathEmpty=${_pdfPath.isEmpty}, bytesNull=${_pdfBytes == null}, bytesLen=${_pdfBytes?.length ?? 0}');
        }
      }
    } catch (e) {
      debugPrint('Error picking PDF: $e');
      if (mounted) {
        setState(() {
          _isUploadingPdf = false;
        });
        
        String errorMsg = e.toString();
        if (errorMsg.contains('already_active')) {
           return;
        }
        
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('File selection error: ${e.toString().length > 80 ? e.toString().substring(0, 80) : e}'), backgroundColor: Colors.red),
        );
      }
    } finally {
      _isPickerActive = false;
    }
  }

  Future<void> _analyzeCatalogue() async {
    if (_sourceType == 'website' && _urlController.text.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Please enter a website URL')));
      return;
    }
    if (_sourceType == 'pdf' && _pdfPath.isEmpty && (_pdfBytes == null || _pdfBytes!.isEmpty)) {
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Please select a PDF file first by tapping the dotted box.')));
      return;
    }

    _startAiProcessing(['Uploading file...', 'Extracting text contents...', 'Sending to Vertex AI...', 'AI is analyzing structure...']);
    
    try {
      final response = await ApiService.uploadSetupWizardSource(
        '/setup-wizard/analyze',
        {
          'userId': _userId,
          'source_type': _sourceType,
          if (_sourceType == 'website') 'website_url': _urlController.text,
        },
        // On web, file.path is always null/empty. Pass null so bytes fallback is used.
        filePath: (_sourceType == 'pdf' && _pdfPath.isNotEmpty) ? _pdfPath : null,
        fileBytes: _sourceType == 'pdf' ? _pdfBytes : null,
        fileName: _sourceType == 'pdf' ? _pdfName : null,
      );
      
      final data = jsonDecode(response.body);
      if (data['success'] == true) {
        setState(() {
          _columns = data['columns'] ?? [];
          if (data['business_details'] != null) _businessDetails = data['business_details'];
          _currentStep = 2;
        });
      } else {
        if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(data['message'] ?? 'Analysis failed.')));
      }
    } catch (e) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('An error occurred during analysis.')));
    } finally {
      _stopAiProcessing();
    }
  }

  void _addNewColumn() {
    setState(() {
      _columns.add({
        'name': '',
        'type': 'text',
        'options': null,
        'is_category': false,
        'is_unique': false,
        'is_combo': false,
      });
    });
  }

  Future<void> _importColumns() async {
    if (_columns.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Please add at least one column.')));
      return;
    }

    setState(() => _isLoading = true);
    
    // Add sort_order to columns
    for (int i = 0; i < _columns.length; i++) {
      _columns[i]['sort_order'] = i + 1;
      _columns[i]['is_title'] = false;
    }

    try {
      final response = await ApiService.post('/setup-wizard/import-columns', {
        'userId': _userId,
        'import_type': 'direct',
        'columns': _columns,
      });
      final data = jsonDecode(response.body);
      if (data['success'] == true) {
        setState(() {
          _statsCols = data['created'] ?? 0;
          _statsCats = (data['categories_created'] as List?)?.length ?? 0;
          _currentStep = 3;
        });
      } else {
        if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(data['message'] ?? 'Import failed.')));
      }
    } catch (e) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('An error occurred while saving columns.')));
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  Future<void> _extractProducts() async {
    _startAiProcessing(['Scanning PDF chunks...', 'Extracting data with AI...', 'Matching constraints...', 'Finalizing product list...']);
    try {
      final response = await ApiService.post('/setup-wizard/extract-products', {'userId': _userId});
      final data = jsonDecode(response.body);
      if (data['success'] == true) {
        setState(() {
          _products = data['products'] ?? [];
          _totalProducts = data['total'] ?? 0;
        });
      } else {
        if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(data['message'] ?? 'Extraction failed.')));
      }
    } catch (e) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('The server took too long to respond. Please try again.')));
    } finally {
      _stopAiProcessing();
    }
  }

  Future<void> _importProductsToSystem() async {
    setState(() => _isLoading = true);
    try {
      final response = await ApiService.post('/setup-wizard/import-products', {'userId': _userId});
      final data = jsonDecode(response.body);
      if (data['success'] == true) {
        await ApiService.post('/setup-wizard/complete', {'userId': _userId});
        setState(() {
          _statsProds = data['created'] ?? 0;
          _currentStep = 4;
        });
        _confettiController.play();
      } else {
        if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(data['message'] ?? 'Import failed.')));
      }
    } catch (e) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('An error occurred during import.')));
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  Future<void> _resetWizard() async {
    setState(() => _isLoading = true);
    try {
      await ApiService.post('/setup-wizard/reset', {'userId': _userId});
      setState(() {
        _currentStep = 1;
        _columns = [];
        _products = [];
        _pdfPath = '';
        _pdfBytes = null;
        _pdfName = '';
        _pdfSize = '';
        _urlController.clear();
      });
    } catch (e) {
      debugPrint('Reset error: $e');
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF8FAFC), // slate-50 equivalent
      appBar: AppBar(
        backgroundColor: Colors.white,
        elevation: 0,
        leading: IconButton(
          icon: const Icon(Icons.arrow_back, color: Colors.black87),
          onPressed: () => Navigator.pop(context),
        ),
        title: const Text('AI Setup Wizard', style: TextStyle(color: Colors.black87, fontSize: 18, fontWeight: FontWeight.bold)),
        actions: [
          if (!_isConfigured)
            Center(
              child: Container(
                margin: const EdgeInsets.only(right: 16),
                padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 4),
                decoration: BoxDecoration(color: Colors.red.shade100, borderRadius: BorderRadius.circular(20)),
                child: Text('Not Configured', style: TextStyle(color: Colors.red.shade700, fontSize: 12, fontWeight: FontWeight.bold)),
              ),
            )
        ],
      ),
      body: Stack(
        children: [
          if (_isLoading && !_isAiProcessing)
            const Center(child: CircularProgressIndicator(color: AppColors.primary))
          else
            SingleChildScrollView(
              padding: const EdgeInsets.only(bottom: 100),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  if (_currentStep < 4) _buildProgressBar(),
                  if (!_isConfigured) _buildConfigWarning(),
                  Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 16.0),
                    child: _buildCurrentStepContent(),
                  ),
                ],
              ),
            ),
          
          if (_isAiProcessing) _buildAiOverlay(),
          
          if (_currentStep == 4)
            Align(
              alignment: Alignment.topCenter,
              child: ConfettiWidget(
                confettiController: _confettiController,
                blastDirectionality: BlastDirectionality.explosive,
                shouldLoop: false,
                colors: const [Colors.indigo, Colors.green, Colors.orange],
              ),
            ),
        ],
      ),
    );
  }

  Widget _buildProgressBar() {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 32.0, vertical: 24.0),
      child: Stack(
        alignment: Alignment.center,
        children: [
          Container(
            height: 2,
            width: double.infinity,
            color: Colors.grey.shade300,
          ),
          Positioned(
            left: 0,
            child: Container(
              height: 2,
              width: _currentStep == 1 ? 0 : (_currentStep == 2 ? MediaQuery.of(context).size.width * 0.4 : MediaQuery.of(context).size.width),
              color: Colors.indigo,
            ),
          ),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              _buildStepIndicator(1, 'Upload'),
              _buildStepIndicator(2, 'Columns'),
              _buildStepIndicator(3, 'Products'),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildStepIndicator(int stepNum, String label) {
    bool isActive = _currentStep == stepNum;
    bool isDone = _currentStep > stepNum;
    
    return Column(
      mainAxisSize: MainAxisSize.min,
      children: [
        Container(
          width: 32,
          height: 32,
          decoration: BoxDecoration(
            color: isActive || isDone ? Colors.indigo : Colors.grey.shade200,
            shape: BoxShape.circle,
            border: Border.all(color: const Color(0xFFF8FAFC), width: 4),
            boxShadow: isActive ? [BoxShadow(color: Colors.indigo.withOpacity(0.3), blurRadius: 4)] : null,
          ),
          child: Center(
            child: isDone
              ? const Icon(Icons.check, color: Colors.white, size: 16)
              : Text('$stepNum', style: TextStyle(color: isActive ? Colors.white : Colors.grey.shade500, fontWeight: FontWeight.bold, fontSize: 14)),
          ),
        ),
        const SizedBox(height: 8),
        Text(
          label.toUpperCase(),
          style: TextStyle(
            fontSize: 10,
            fontWeight: FontWeight.bold,
            color: isActive || isDone ? Colors.indigo : Colors.grey.shade400,
          ),
        ),
      ],
    );
  }

  Widget _buildConfigWarning() {
    return Container(
      margin: const EdgeInsets.symmetric(horizontal: 16.0, vertical: 8.0),
      padding: const EdgeInsets.all(16.0),
      decoration: BoxDecoration(
        color: Colors.red.shade50,
        border: Border.all(color: Colors.red.shade100),
        borderRadius: BorderRadius.circular(16),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(Icons.info_outline, color: Colors.red.shade500, size: 20),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text('AI Not Configured', style: TextStyle(fontWeight: FontWeight.bold, color: Colors.red.shade800, fontSize: 14)),
                const SizedBox(height: 4),
                Text('Please ask the administrator to configure Vertex AI credentials in the AI Settings panel.', style: TextStyle(color: Colors.red.shade600, fontSize: 12)),
              ],
            ),
          )
        ],
      ),
    );
  }

  Widget _buildCurrentStepContent() {
    switch (_currentStep) {
      case 1: return _buildStep1();
      case 2: return _buildStep2();
      case 3: return _buildStep3();
      case 4: return _buildStep4();
      default: return const SizedBox();
    }
  }

  Widget _buildStep1() {
    return Container(
      padding: const EdgeInsets.all(24),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(32),
        border: Border.all(color: Colors.grey.shade100),
        boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.02), blurRadius: 10, offset: const Offset(0, 4))],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Row(
            children: [
              Container(
                width: 40, height: 40,
                decoration: BoxDecoration(color: Colors.orange.shade50, borderRadius: BorderRadius.circular(12)),
                child: Icon(Icons.description_outlined, color: Colors.orange.shade500, size: 20),
              ),
              const SizedBox(width: 12),
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text('Upload Catalogue', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: Colors.black87)),
                  Text('PDF or Website URL', style: TextStyle(fontSize: 12, color: Colors.grey.shade500)),
                ],
              )
            ],
          ),
          const SizedBox(height: 24),
          
          // Segmented Control
          Container(
            padding: const EdgeInsets.all(4),
            decoration: BoxDecoration(color: Colors.grey.shade100, borderRadius: BorderRadius.circular(12)),
            child: Row(
              children: [
                Expanded(
                  child: GestureDetector(
                    onTap: () => setState(() => _sourceType = 'pdf'),
                    child: Container(
                      padding: const EdgeInsets.symmetric(vertical: 10),
                      decoration: BoxDecoration(
                        color: _sourceType == 'pdf' ? Colors.white : Colors.transparent,
                        borderRadius: BorderRadius.circular(8),
                        boxShadow: _sourceType == 'pdf' ? [BoxShadow(color: Colors.black.withOpacity(0.05), blurRadius: 4)] : null,
                      ),
                      child: Center(child: Text('PDF Document', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: _sourceType == 'pdf' ? Colors.black87 : Colors.grey.shade500))),
                    ),
                  ),
                ),
                Expanded(
                  child: GestureDetector(
                    onTap: () => setState(() => _sourceType = 'website'),
                    child: Container(
                      padding: const EdgeInsets.symmetric(vertical: 10),
                      decoration: BoxDecoration(
                        color: _sourceType == 'website' ? Colors.white : Colors.transparent,
                        borderRadius: BorderRadius.circular(8),
                        boxShadow: _sourceType == 'website' ? [BoxShadow(color: Colors.black.withOpacity(0.05), blurRadius: 4)] : null,
                      ),
                      child: Center(child: Text('Website URL', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: _sourceType == 'website' ? Colors.black87 : Colors.grey.shade500))),
                    ),
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 24),
          
          if (_sourceType == 'pdf')
            GestureDetector(
              onTap: _isUploadingPdf ? null : _pickPdf,
              child: DottedBorder(
                options: RoundedRectDottedBorderOptions(
                  color: _pdfName.isNotEmpty ? Colors.green.shade400 : Colors.indigo.shade200,
                  strokeWidth: 2,
                  dashPattern: const [6, 4],
                  radius: const Radius.circular(24),
                ),
                child: Container(
                  width: double.infinity,
                  padding: const EdgeInsets.symmetric(vertical: 32, horizontal: 16),
                  decoration: BoxDecoration(
                    color: _pdfName.isNotEmpty ? Colors.green.shade50.withOpacity(0.3) : Colors.indigo.shade50.withOpacity(0.3), 
                    borderRadius: BorderRadius.circular(24)
                  ),
                  child: Column(
                    children: [
                      if (_isUploadingPdf) ...[
                        Container(
                          width: 64, height: 64,
                          padding: const EdgeInsets.all(16),
                          decoration: const BoxDecoration(color: Colors.white, shape: BoxShape.circle, boxShadow: [BoxShadow(color: Colors.black12, blurRadius: 8)]),
                          child: CircularProgressIndicator(value: _uploadProgress, color: Colors.indigo, backgroundColor: Colors.indigo.shade50, strokeWidth: 4),
                        ),
                        const SizedBox(height: 16),
                        const Text('Uploading PDF...', style: TextStyle(fontWeight: FontWeight.bold, color: Colors.black87)),
                        const SizedBox(height: 4),
                        Text('${(_uploadProgress * 100).toInt()}%', style: TextStyle(fontSize: 12, color: Colors.indigo.shade600, fontWeight: FontWeight.bold)),
                      ] else if (_pdfName.isNotEmpty) ...[
                        Container(
                          width: 64, height: 64,
                          decoration: BoxDecoration(color: Colors.green.shade500, shape: BoxShape.circle, boxShadow: [BoxShadow(color: Colors.green.withOpacity(0.3), blurRadius: 8)]),
                          child: const Icon(Icons.check_circle, color: Colors.white, size: 32),
                        ),
                        const SizedBox(height: 16),
                        const Text('PDF Uploaded Successfully!', style: TextStyle(fontWeight: FontWeight.bold, color: Colors.green)),
                        const SizedBox(height: 8),
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                          decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(12), border: Border.all(color: Colors.green.shade200)),
                          child: Row(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              const Icon(Icons.picture_as_pdf, color: Colors.redAccent, size: 16),
                              const SizedBox(width: 8),
                              Flexible(child: Text('$_pdfName ($_pdfSize)', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Colors.black87), overflow: TextOverflow.ellipsis)),
                            ],
                          ),
                        ),
                        const SizedBox(height: 12),
                        Text('Tap to change file', style: TextStyle(fontSize: 11, color: Colors.grey.shade500, decoration: TextDecoration.underline)),
                      ] else ...[
                        Container(
                          width: 64, height: 64,
                          decoration: const BoxDecoration(color: Colors.white, shape: BoxShape.circle, boxShadow: [BoxShadow(color: Colors.black12, blurRadius: 8)]),
                          child: Icon(Icons.cloud_upload_outlined, color: Colors.indigo.shade400, size: 32),
                        ),
                        const SizedBox(height: 16),
                        const Text('Tap to Upload PDF', style: TextStyle(fontWeight: FontWeight.bold, color: Colors.black87)),
                        const SizedBox(height: 4),
                        Text('AI will scan your PDF and understand your product structure automatically.', textAlign: TextAlign.center, style: TextStyle(fontSize: 12, color: Colors.grey.shade500)),
                      ]
                    ],
                  ),
                ),
              ),
            )
          else
            Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text('Website URL', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Colors.grey.shade700)),
                const SizedBox(height: 8),
                TextField(
                  controller: _urlController,
                  decoration: InputDecoration(
                    hintText: 'https://example.com/products',
                    filled: true,
                    fillColor: Colors.grey.shade50,
                    contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
                    border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide(color: Colors.grey.shade200)),
                    enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide(color: Colors.grey.shade200)),
                    focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide(color: Colors.indigo.shade400)),
                  ),
                ),
                const SizedBox(height: 8),
                Text('AI will crawl the page to understand your products.', style: TextStyle(fontSize: 11, color: Colors.grey.shade500)),
              ],
            ),
            
          const SizedBox(height: 24),
          ElevatedButton(
            onPressed: (_isConfigured && !_isUploadingPdf) ? _analyzeCatalogue : null,
            style: ElevatedButton.styleFrom(
              backgroundColor: Colors.indigo,
              padding: const EdgeInsets.symmetric(vertical: 16),
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
              elevation: 4,
              shadowColor: Colors.indigo.withOpacity(0.4),
            ),
            child: const Text('Start AI Analysis', style: TextStyle(fontSize: 15, fontWeight: FontWeight.bold, color: Colors.white)),
          ),
        ],
      ),
    );
  }

  Widget _buildStep2() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        Container(
          padding: const EdgeInsets.all(16),
          decoration: BoxDecoration(color: Colors.indigo.shade50, borderRadius: BorderRadius.circular(16)),
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Container(
                width: 40, height: 40,
                decoration: BoxDecoration(color: Colors.indigo.shade100, shape: BoxShape.circle),
                child: const Icon(Icons.auto_awesome, color: Colors.indigo, size: 20),
              ),
              const SizedBox(width: 16),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text('AI Found Data Structure', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14, color: Colors.black87)),
                    const SizedBox(height: 4),
                    Text('Review the columns AI found. Check "Is Category" for grouping, "Unique" for codes (SKU), and "Combo" for variants.', style: TextStyle(fontSize: 11, color: Colors.grey.shade700, height: 1.4)),
                  ],
                ),
              )
            ],
          ),
        ),
        const SizedBox(height: 16),
        
        ListView.separated(
          shrinkWrap: true,
          physics: const NeverScrollableScrollPhysics(),
          itemCount: _columns.length,
          separatorBuilder: (c, i) => const SizedBox(height: 12),
          itemBuilder: (context, index) {
            final col = _columns[index];
            bool isCat = col['is_category'] == true;
            return Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(16),
                border: Border.all(color: isCat ? Colors.orange.shade300 : Colors.grey.shade200),
                boxShadow: isCat ? [BoxShadow(color: Colors.orange.withOpacity(0.1), blurRadius: 8)] : null,
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Expanded(
                        child: TextField(
                          controller: TextEditingController(text: col['name'])..selection = TextSelection.collapsed(offset: col['name'].length),
                          onChanged: (val) => col['name'] = val,
                          decoration: const InputDecoration(border: InputBorder.none, isDense: true, contentPadding: EdgeInsets.zero, hintText: 'Column Name'),
                          style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15, color: Colors.black87),
                        ),
                      ),
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 8),
                        decoration: BoxDecoration(color: Colors.grey.shade50, borderRadius: BorderRadius.circular(8), border: Border.all(color: Colors.grey.shade200)),
                        child: DropdownButtonHideUnderline(
                          child: DropdownButton<String>(
                            value: col['type'],
                            isDense: true,
                            icon: const Icon(Icons.arrow_drop_down, size: 20),
                            style: TextStyle(fontSize: 12, color: Colors.grey.shade700),
                            onChanged: (String? newValue) {
                              setState(() => col['type'] = newValue!);
                            },
                            items: const [
                              DropdownMenuItem(value: 'text', child: Text('Text')),
                              DropdownMenuItem(value: 'textarea', child: Text('Textarea')),
                              DropdownMenuItem(value: 'number', child: Text('Number')),
                              DropdownMenuItem(value: 'select', child: Text('Select')),
                              DropdownMenuItem(value: 'multiselect', child: Text('Multi-Select')),
                              DropdownMenuItem(value: 'boolean', child: Text('Boolean')),
                            ],
                          ),
                        ),
                      ),
                      const SizedBox(width: 8),
                      GestureDetector(
                        onTap: () => setState(() => _columns.removeAt(index)),
                        child: Icon(Icons.delete_outline, color: Colors.grey.shade400, size: 20),
                      )
                    ],
                  ),
                  
                  if (col['type'] == 'select' || col['type'] == 'multiselect') ...[
                    const SizedBox(height: 12),
                    TextField(
                      controller: TextEditingController(text: (col['options'] as List?)?.join(', ') ?? '')..selection = TextSelection.collapsed(offset: ((col['options'] as List?)?.join(', ') ?? '').length),
                      onChanged: (val) => col['options'] = val.split(',').map((e) => e.trim()).toList(),
                      decoration: InputDecoration(
                        hintText: 'Options (comma separated)',
                        filled: true,
                        fillColor: Colors.grey.shade50,
                        isDense: true,
                        contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                        border: OutlineInputBorder(borderRadius: BorderRadius.circular(8), borderSide: BorderSide(color: Colors.grey.shade200)),
                      ),
                      style: TextStyle(fontSize: 12, color: Colors.grey.shade700),
                    ),
                  ],
                  
                  const SizedBox(height: 12),
                  Wrap(
                    spacing: 8,
                    runSpacing: 8,
                    children: [
                      _buildToggleTag('Category', isCat, Colors.orange, (val) {
                        setState(() {
                          // Uncheck others if category
                          if (val == true) {
                            for (var c in _columns) { c['is_category'] = false; }
                          }
                          col['is_category'] = val;
                        });
                      }),
                      _buildToggleTag('Unique', col['is_unique'] == true, Colors.indigo, (val) {
                        setState(() {
                          if (val == true) {
                            for (var c in _columns) { c['is_unique'] = false; }
                          }
                          col['is_unique'] = val;
                        });
                      }),
                      _buildToggleTag('Combo', col['is_combo'] == true, Colors.purple, (val) {
                        setState(() => col['is_combo'] = val);
                      }),
                    ],
                  )
                ],
              ),
            );
          },
        ),
        
        const SizedBox(height: 16),
        GestureDetector(
          onTap: _addNewColumn,
          child: DottedBorder(
            options: RoundedRectDottedBorderOptions(
              color: Colors.indigo.shade200,
              strokeWidth: 2,
              dashPattern: const [6, 4],
              radius: const Radius.circular(12),
            ),
            child: Container(
              width: double.infinity,
              padding: const EdgeInsets.symmetric(vertical: 12),
              color: Colors.transparent,
              child: const Center(child: Text('+ Add Missing Column', style: TextStyle(color: Colors.indigo, fontWeight: FontWeight.bold, fontSize: 13))),
            ),
          ),
        ),
        
        const SizedBox(height: 24),
        Row(
          children: [
            Expanded(
              flex: 1,
              child: ElevatedButton(
                onPressed: () => setState(() => _currentStep = 1),
                style: ElevatedButton.styleFrom(backgroundColor: Colors.grey.shade100, elevation: 0, padding: const EdgeInsets.symmetric(vertical: 16), shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12))),
                child: Text('Back', style: TextStyle(color: Colors.grey.shade700, fontWeight: FontWeight.bold)),
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              flex: 2,
              child: ElevatedButton(
                onPressed: _importColumns,
                style: ElevatedButton.styleFrom(backgroundColor: Colors.indigo, elevation: 4, shadowColor: Colors.indigo.withOpacity(0.4), padding: const EdgeInsets.symmetric(vertical: 16), shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12))),
                child: const Text('Save & Continue', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
              ),
            ),
          ],
        )
      ],
    );
  }

  Widget _buildToggleTag(String label, bool value, MaterialColor color, Function(bool?) onChanged) {
    return GestureDetector(
      onTap: () => onChanged(!value),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
        decoration: BoxDecoration(
          color: value ? color.shade50 : Colors.grey.shade50,
          border: Border.all(color: value ? color.shade200 : Colors.grey.shade200),
          borderRadius: BorderRadius.circular(6),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            SizedBox(
              width: 14, height: 14,
              child: Checkbox(
                value: value,
                onChanged: onChanged,
                activeColor: color,
                materialTapTargetSize: MaterialTapTargetSize.shrinkWrap,
                visualDensity: VisualDensity.compact,
                side: BorderSide(color: Colors.grey.shade400, width: 1.5),
              ),
            ),
            const SizedBox(width: 6),
            Text(label, style: TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: value ? color.shade700 : Colors.grey.shade600)),
          ],
        ),
      ),
    );
  }

  Widget _buildStep3() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        Container(
          padding: const EdgeInsets.all(24),
          decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(32), border: Border.all(color: Colors.grey.shade100)),
          child: Column(
            children: [
              Container(width: 64, height: 64, decoration: BoxDecoration(color: Colors.indigo.shade50, shape: BoxShape.circle), child: Icon(Icons.inventory_2_outlined, color: Colors.indigo.shade400, size: 32)),
              const SizedBox(height: 16),
              const Text('Extract Products', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 18, color: Colors.black87)),
              const SizedBox(height: 8),
              Text('AI will now read every page and extract all individual products matching your defined columns.', textAlign: TextAlign.center, style: TextStyle(fontSize: 12, color: Colors.grey.shade500)),
            ],
          ),
        ),
        const SizedBox(height: 16),
        
        Container(
          padding: const EdgeInsets.all(20),
          decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(24), border: Border.all(color: Colors.grey.shade100)),
          child: Column(
            children: [
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  const Text('Products Extracted', style: TextStyle(fontSize: 13, fontWeight: FontWeight.bold, color: Colors.black87)),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                    decoration: BoxDecoration(color: Colors.indigo.shade50, borderRadius: BorderRadius.circular(20)),
                    child: Text('$_totalProducts', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Colors.indigo.shade700)),
                  ),
                ],
              ),
              
              if (_products.isEmpty) ...[
                const SizedBox(height: 24),
                Padding(
                  padding: const EdgeInsets.symmetric(vertical: 24),
                  child: Text('Ready to extract', style: TextStyle(fontSize: 12, color: Colors.grey.shade400)),
                )
              ] else ...[
                const SizedBox(height: 16),
                Container(
                  height: 200,
                  decoration: BoxDecoration(border: Border.all(color: Colors.grey.shade100), borderRadius: BorderRadius.circular(12)),
                  child: SingleChildScrollView(
                    scrollDirection: Axis.horizontal,
                    child: SingleChildScrollView(
                      child: DataTable(
                        headingRowHeight: 40,
                        dataRowMinHeight: 40,
                        dataRowMaxHeight: 40,
                        headingRowColor: WidgetStateProperty.all(Colors.grey.shade50),
                        columns: (_products[0] as Map<String, dynamic>).keys.take(5).map((e) => DataColumn(label: Text(e, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 12, color: Colors.black54)))).toList(),
                        rows: _products.take(10).map((prod) {
                          return DataRow(
                            cells: (_products[0] as Map<String, dynamic>).keys.take(5).map((key) {
                              return DataCell(SizedBox(width: 80, child: Text(prod[key]?.toString() ?? '-', overflow: TextOverflow.ellipsis, style: const TextStyle(fontSize: 11))));
                            }).toList(),
                          );
                        }).toList(),
                      ),
                    ),
                  ),
                )
              ]
            ],
          ),
        ),
        const SizedBox(height: 24),
        
        if (_products.isEmpty)
          ElevatedButton(
            onPressed: _extractProducts,
            style: ElevatedButton.styleFrom(backgroundColor: Colors.indigo, padding: const EdgeInsets.symmetric(vertical: 16), shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)), elevation: 4),
            child: const Text('Extract Products Now', style: TextStyle(fontSize: 15, fontWeight: FontWeight.bold, color: Colors.white)),
          )
        else
          Row(
            children: [
              Expanded(
                flex: 1,
                child: ElevatedButton(
                  onPressed: _extractProducts,
                  style: ElevatedButton.styleFrom(backgroundColor: Colors.grey.shade100, elevation: 0, padding: const EdgeInsets.symmetric(vertical: 16), shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12))),
                  child: Text('Retry', style: TextStyle(color: Colors.grey.shade700, fontWeight: FontWeight.bold)),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                flex: 2,
                child: ElevatedButton(
                  onPressed: _importProductsToSystem,
                  style: ElevatedButton.styleFrom(backgroundColor: Colors.green, elevation: 4, shadowColor: Colors.green.withOpacity(0.4), padding: const EdgeInsets.symmetric(vertical: 16), shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12))),
                  child: const Text('Import to Database', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
                ),
              ),
            ],
          )
      ],
    );
  }

  Widget _buildStep4() {
    return Column(
      children: [
        const SizedBox(height: 32),
        Container(
          width: 96, height: 96,
          decoration: BoxDecoration(color: Colors.green.shade50, shape: BoxShape.circle),
          child: Icon(Icons.check, color: Colors.green.shade500, size: 48),
        ),
        const SizedBox(height: 24),
        const Text('Setup Complete!', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 24, color: Colors.black87)),
        const SizedBox(height: 8),
        Padding(
          padding: const EdgeInsets.symmetric(horizontal: 24),
          child: Text('Your product catalogue has been fully digitized. The AI Content Engine can now create daily posts for your products.', textAlign: TextAlign.center, style: TextStyle(fontSize: 13, color: Colors.grey.shade500)),
        ),
        const SizedBox(height: 32),
        
        Container(
          padding: const EdgeInsets.all(16),
          decoration: BoxDecoration(color: Colors.grey.shade50, borderRadius: BorderRadius.circular(16)),
          child: Column(
            children: [
              _buildStatRow('Columns Created', _statsCols.toString(), Colors.black87),
              const SizedBox(height: 8),
              _buildStatRow('Categories Created', _statsCats.toString(), Colors.black87),
              const SizedBox(height: 8),
              _buildStatRow('Products Imported', _statsProds.toString(), Colors.green.shade600, isBold: true),
            ],
          ),
        ),
        const SizedBox(height: 32),
        
        ElevatedButton(
          onPressed: () => Navigator.pop(context),
          style: ElevatedButton.styleFrom(minimumSize: const Size(double.infinity, 56), backgroundColor: Colors.indigo, shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)), elevation: 4),
          child: const Text('View My Products', style: TextStyle(fontSize: 15, fontWeight: FontWeight.bold, color: Colors.white)),
        ),
        const SizedBox(height: 16),
        TextButton(
          onPressed: _resetWizard,
          child: Text('Restart Setup Wizard', style: TextStyle(fontWeight: FontWeight.bold, color: Colors.grey.shade400)),
        )
      ],
    );
  }

  Widget _buildStatRow(String label, String value, Color valueColor, {bool isBold = false}) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Text(label, style: TextStyle(fontSize: 13, color: Colors.grey.shade600)),
        Text(value, style: TextStyle(fontSize: 14, fontWeight: isBold ? FontWeight.bold : FontWeight.w600, color: valueColor)),
      ],
    );
  }

  Widget _buildAiOverlay() {
    return Container(
      color: Colors.white.withOpacity(0.95),
      child: Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            SizedBox(
              width: 96, height: 96,
              child: Stack(
                alignment: Alignment.center,
                children: [
                  CircularProgressIndicator(value: 1.0, strokeWidth: 8, color: Colors.indigo.shade50),
                  const CircularProgressIndicator(strokeWidth: 8, color: Colors.indigo, strokeCap: StrokeCap.round),
                  const Icon(Icons.smart_toy, color: Colors.indigo, size: 40),
                ],
              ),
            ),
            const SizedBox(height: 24),
            const Text('AI is Thinking...', style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold, color: Colors.black87)),
            const SizedBox(height: 8),
            Text(_aiStatusText, style: TextStyle(fontSize: 14, color: Colors.grey.shade500)),
            const SizedBox(height: 8),
            Text('Elapsed time: $_formattedTime', style: const TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: Colors.indigo)),
            const SizedBox(height: 24),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
              decoration: BoxDecoration(color: Colors.indigo.shade50, borderRadius: BorderRadius.circular(20)),
              child: Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Container(width: 8, height: 8, decoration: const BoxDecoration(color: Colors.indigo, shape: BoxShape.circle)),
                  const SizedBox(width: 8),
                  Text('This may take up to 2-3 minutes', style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: Colors.indigo.shade700)),
                ],
              ),
            )
          ],
        ),
      ),
    );
  }
}

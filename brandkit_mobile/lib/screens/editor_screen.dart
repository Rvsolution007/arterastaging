import 'dart:convert';
import 'dart:io';
import 'dart:typed_data';
import 'package:flutter/material.dart';
import 'package:flutter/foundation.dart';
import 'package:webview_flutter/webview_flutter.dart';
import 'package:webview_flutter_android/webview_flutter_android.dart';
import 'package:file_picker/file_picker.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:gal/gal.dart';
import 'package:get/get.dart';
import '../utils/app_colors.dart';
import '../services/api_service.dart';
import '../services/download_service.dart';
import '../controllers/ad_controller.dart';
import '../controllers/auth_controller.dart';
import '../controllers/subscription_controller.dart';
import 'package:url_launcher/url_launcher.dart';
import '../widgets/web_editor_stub.dart' if (dart.library.html) '../widgets/web_editor_impl.dart';
import 'ai_chat_screen.dart';

class EditorScreen extends StatefulWidget {
  final String type;
  final int id;
  final Map<String, dynamic> frameData;
  final String designUrl;
  final Map<String, dynamic>? aiAnalysisData;
  final dynamic mappingRules;

  const EditorScreen({
    super.key,
    required this.type,
    required this.id,
    required this.frameData,
    required this.designUrl,
    this.aiAnalysisData,
    this.mappingRules,
  });

  @override
  State<EditorScreen> createState() => _EditorScreenState();
}

class _EditorScreenState extends State<EditorScreen> {
  late final WebViewController _controller;
  bool isLoading = true;
  bool isControllerInitialized = false;

  @override
  void initState() {
    super.initState();
    if (!kIsWeb) {
      _initWebView();
    } else {
      _initWebIframe();
    }
  }

  String _webEditorUrl = '';

  Future<void> _initWebIframe() async {
    final prefs = await SharedPreferences.getInstance();
    final userId = prefs.getString('userId') ?? '';
    final baseUrl = ApiService.baseUrl.replaceAll('/123456', '');
    final frameId = widget.frameData['frameId'];
    final targetUrl = '/edit/${widget.type}/${widget.id}?design=${Uri.encodeComponent(widget.designUrl)}&frame_id=$frameId';
    

    final editorUrl = '$baseUrl/webview-login?user_id=$userId&redirect=${Uri.encodeComponent(targetUrl)}';
    
    if (mounted) {
      setState(() {
        _webEditorUrl = editorUrl;
        isControllerInitialized = true;
        isLoading = false;
      });
    }
  }

  Future<void> _initWebView() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final userId = prefs.getString('userId') ?? '';
      
      final baseUrl = ApiService.baseUrl.replaceAll('/123456', '');
      final frameId = widget.frameData['frameId'];
      final targetUrl = '/edit/${widget.type}/${widget.id}?design=${Uri.encodeComponent(widget.designUrl)}&frame_id=$frameId';
      
      // Route through webview-login to sync the API token to the Web Session
      final editorUrl = '$baseUrl/webview-login?user_id=$userId&redirect=${Uri.encodeComponent(targetUrl)}';
      
      debugPrint('[Editor] Loading URL: $editorUrl');
      
      _controller = WebViewController()
        ..setJavaScriptMode(JavaScriptMode.unrestricted)
        ..clearCache()
        ..clearLocalStorage()
        ..setNavigationDelegate(NavigationDelegate(
          onPageStarted: (url) {
            debugPrint('[Editor] Page started: $url');
          },
          onPageFinished: (url) {
            debugPrint('[Editor] Page finished: $url');
            if (mounted) setState(() => isLoading = false);
            

          },
          onWebResourceError: (error) {
            debugPrint('[Editor] WebView error: ${error.description} (code: ${error.errorCode})');
            if (mounted) {
              setState(() => isLoading = false);
            }
          },
        ))
        ..addJavaScriptChannel(
          'FlutterBridge',
          onMessageReceived: (JavaScriptMessage message) async {
            if (message.message.startsWith('export:')) {
              final dataUrl = message.message.substring(7);
              
              // Map widget.type to feature key
              String featureKey = 'custom_post';
              if (widget.type == 'festival') {
                featureKey = 'festival_post';
              } else if (widget.type == 'category' || widget.type == 'business_custom') {
                featureKey = 'category_post';
              }

              final adController = Get.find<AdController>();
              
              if (featureKey == 'custom_post') {
                await adController.handleFeatureAccess(
                  context: context,
                  feature: featureKey,
                  onAccessGranted: () async {
                    await _saveExportedImage(dataUrl);
                  },
                );
              } else {
                // For festival/category posts edited in editor:
                // Phase 2 Interstitial ad on download for premium templates
                final bool isPaid = widget.frameData['isPaid'] ?? false;
                adController.handlePremiumDownloadAd(
                  feature: featureKey,
                  isPaid: isPaid,
                );
                await _saveExportedImage(dataUrl);
              }
            } else if (message.message.startsWith('showRewardedAd:')) {
              // Note: This block might not be triggered from webview anymore, but left for backward compatibility
            }
          },
        )
        ..loadRequest(Uri.parse(editorUrl));

      // ── Android: Enable file upload support for <input type="file"> ──
      if (Platform.isAndroid) {
        final androidController = _controller.platform as AndroidWebViewController;
        androidController.setOnShowFileSelector(_handleFilePicker);
      }

      if (mounted) {
        setState(() {
          isControllerInitialized = true;
        });
      }
    } catch (e, stack) {
      debugPrint('[Editor] Init error: $e');
      debugPrint('[Editor] Stack: $stack');
      if (mounted) {
        setState(() {
          isLoading = false;
          isControllerInitialized = false;
        });
      }
    }
  }

  Future<void> _handleWebRedirect() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final userId = prefs.getString('userId') ?? '';
      final baseUrl = ApiService.baseUrl.replaceAll('/123456', '');
      final targetUrl = '/edit/${widget.type}/${widget.id}?design=${Uri.encodeComponent(widget.designUrl)}';
      final editorUrl = '$baseUrl/webview-login?user_id=$userId&redirect=${Uri.encodeComponent(targetUrl)}';
      
      debugPrint('[Editor] Redirecting Web to: $editorUrl');
      
      if (await canLaunchUrl(Uri.parse(editorUrl))) {
        await launchUrl(
          Uri.parse(editorUrl), 
          webOnlyWindowName: '_self', // Open in same tab for better web flow
        );
      }
    } catch (e) {
      debugPrint('[Editor] Web redirect error: $e');
    }
  }

  /// Handles the native file picker when the WebView triggers <input type="file">
  Future<List<String>> _handleFilePicker(FileSelectorParams params) async {
    try {
      // Determine file type from accept types
      FileType fileType = FileType.image; // Default to images
      List<String>? allowedExtensions;
      
      if (params.acceptTypes.isNotEmpty) {
        final acceptStr = params.acceptTypes.join(',').toLowerCase();
        if (acceptStr.contains('image')) {
          fileType = FileType.image;
        } else if (acceptStr.contains('video')) {
          fileType = FileType.video;
        } else {
          fileType = FileType.any;
        }
      }

      final result = await FilePicker.platform.pickFiles(
        allowMultiple: false,
        type: fileType,
        allowedExtensions: allowedExtensions,
      );

      if (result != null && result.files.isNotEmpty && result.files.first.path != null) {
        final filePath = result.files.first.path!;
        return [Uri.file(filePath).toString()];
      }
    } catch (e) {
      debugPrint('File picker error: $e');
    }
    return [];
  }

  Future<void> _saveExportedImage(String dataUrl) async {
    try {
      if (dataUrl.isEmpty || !dataUrl.contains(',')) return;
      final base64Str = dataUrl.split(',').last;
      final bytes = base64Decode(base64Str);

      final fileName = "artera_design_${DateTime.now().millisecondsSinceEpoch}";
      await Gal.putImageBytes(
        Uint8List.fromList(bytes),
        name: fileName,
      );
      
      await DownloadService.saveDownload(
        Uint8List.fromList(bytes),
        isVideo: false,
        fileName: fileName,
      );

      final bool isPaid = widget.frameData['isPaid'] == true || widget.frameData['isPaid'] == '1' || widget.frameData['premium'] == true || widget.frameData['premium'] == '1';
      await ApiService.trackActivity(
        action: 'download_template',
        itemType: widget.type,
        itemId: widget.id.toString(),
        isPremium: isPaid,
      );

      // Refresh subscription limits so usage counters update immediately
      try {
        await Get.find<SubscriptionController>().refreshFromApi();
      } catch (_) {}

      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Design saved to gallery successfully!')),
        );
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Failed to save design: $e')),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    debugPrint('[EditorScreen] Building for type: ${widget.type}, id: ${widget.id}');
    
    if (kIsWeb) {
      return Scaffold(
        backgroundColor: Colors.white,
        appBar: AppBar(
          backgroundColor: Colors.white,
          elevation: 0.5,
          leading: IconButton(
            icon: const Icon(Icons.close, color: Color(0xFF334155)),
            onPressed: () => Navigator.pop(context),
          ),
          title: Text(
            'Edit Design',
            style: TextStyle(color: AppColors.textPrimary, fontWeight: FontWeight.w700, fontSize: 18),
          ),
          centerTitle: true,
          actions: [
            IconButton(
              icon: const Icon(Icons.help_outline, color: Color(0xFF334155)),
              onPressed: () {
                showModalBottomSheet(
                  context: context,
                  isScrollControlled: true,
                  backgroundColor: Colors.transparent,
                  builder: (context) => ClipRRect(
                    borderRadius: const BorderRadius.vertical(top: Radius.circular(20)),
                    child: Container(
                      height: MediaQuery.of(context).size.height * 0.75,
                      color: Colors.white,
                      child: const AiChatScreen(source: 'editor'),
                    ),
                  ),
                );
              },
            ),
          ],
        ),
        body: _webEditorUrl.isNotEmpty 
            ? getWebEditor(_webEditorUrl)
            : const Center(child: CircularProgressIndicator()),
      );
    }

    return Scaffold(
      backgroundColor: Colors.white,
      appBar: AppBar(
        backgroundColor: Colors.white,
        elevation: 0.5,
        leading: IconButton(
          icon: const Icon(Icons.close, color: Color(0xFF334155)),
          onPressed: () => Navigator.pop(context),
        ),
        title: Text(
          'Edit Design',
          style: TextStyle(color: AppColors.textPrimary, fontWeight: FontWeight.w700, fontSize: 18),
        ),
        centerTitle: true,
        actions: [
          IconButton(
            icon: const Icon(Icons.help_outline, color: Color(0xFF334155)),
            onPressed: () {
              showModalBottomSheet(
                context: context,
                isScrollControlled: true,
                backgroundColor: Colors.transparent,
                builder: (context) => ClipRRect(
                  borderRadius: const BorderRadius.vertical(top: Radius.circular(20)),
                  child: Container(
                    height: MediaQuery.of(context).size.height * 0.75,
                    color: Colors.white,
                    child: const AiChatScreen(source: 'editor'),
                  ),
                ),
              );
            },
          ),
        ],
      ),
      body: Stack(
        children: [
          if (isControllerInitialized)
            WebViewWidget(controller: _controller),
          if (isLoading || !isControllerInitialized)
            Container(
              color: Colors.white,
              child: Center(
                child: Column(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    CircularProgressIndicator(color: AppColors.primary),
                    const SizedBox(height: 16),
                    Text(
                      'Loading Editor...',
                      style: TextStyle(color: AppColors.textSecondary, fontWeight: FontWeight.w600),
                    ),
                  ],
                ),
              ),
            ),
        ],
      ),
    );
  }
}

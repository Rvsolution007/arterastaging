import 'package:brandkit_mobile/utils/string_extensions.dart';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:brandkit_mobile/controllers/auth_controller.dart';
import 'package:flutter/foundation.dart' show kIsWeb;
import 'package:url_launcher/url_launcher.dart';
import 'dart:async';
import 'dart:convert';
import '../utils/app_colors.dart';
import '../services/api_service.dart';
import '../config/app_config.dart';

class SearchScreen extends StatefulWidget {
  const SearchScreen({super.key});

  @override
  State<SearchScreen> createState() => _SearchScreenState();
}

class _SearchScreenState extends State<SearchScreen> {
  final TextEditingController _searchController = TextEditingController();
  final FocusNode _focusNode = FocusNode();
  
  Timer? _debounce;
  bool _isLoading = false;
  List<dynamic> _searchResults = [];
  String _lastQuery = '';

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      _focusNode.requestFocus();
    });
  }

  @override
  void dispose() {
    _searchController.dispose();
    _focusNode.dispose();
    _debounce?.cancel();
    super.dispose();
  }

  void _onSearchChanged(String query) {
    if (_debounce?.isActive ?? false) _debounce!.cancel();
    
    if (query.trim().isEmpty) {
      setState(() {
        _searchResults = [];
        _isLoading = false;
        _lastQuery = '';
      });
      return;
    }

    _debounce = Timer(const Duration(milliseconds: 500), () {
      _performSearch(query.trim());
    });
  }

  Future<void> _performSearch(String query) async {
    if (query == _lastQuery) return;
    
    setState(() {
      _isLoading = true;
      _lastQuery = query;
    });

    try {
      final response = await ApiService.post('/search', {'term': query});
      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (mounted) {
          setState(() {
            _searchResults = (data is List) ? data : (data['data'] ?? []);
            _isLoading = false;
          });
        }
      } else {
        if (mounted) {
          setState(() {
            _isLoading = false;
            _searchResults = [];
          });
        }
      }
    } catch (e) {
      if (mounted) {
        setState(() {
          _isLoading = false;
          _searchResults = [];
        });
      }
    }
  }

  void _openEditor(dynamic post) async {
    final imgUrl = post['image']?.toString() ?? '';
    final type = post['type'] ?? 'festival';
    final id = post['id']?.toString() ?? '';

    if (kIsWeb) {
      final editorUrl = await ApiService.getWebEditorUrl(
        type: type,
        id: id,
        designUrl: imgUrl,
      );
      final uri = Uri.parse(editorUrl);
      if (await canLaunchUrl(uri)) {
        await launchUrl(uri, webOnlyWindowName: '_self');
      }
      return;
    }

    final editorQuery = Uri(queryParameters: {
      'type': type,
      'id': id,
      'designUrl': imgUrl,
    }).query;
    
    Get.find<AuthController>().checkAndNavigateToEditor(
      '/editor?$editorQuery',
      arguments: {
        'frameData': post,
      },
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        backgroundColor: Colors.white,
        elevation: 0,
        leading: IconButton(
          icon: Icon(Icons.arrow_back, color: AppColors.gray900),
          onPressed: () => Navigator.pop(context),
        ),
        titleSpacing: 0,
        title: TextField(
          controller: _searchController,
          focusNode: _focusNode,
          onChanged: _onSearchChanged,
          textInputAction: TextInputAction.search,
          decoration: InputDecoration(
            hintText: 'search_templates_categories'.trFormat,
            hintStyle: TextStyle(color: AppColors.gray400, fontSize: 15),
            border: InputBorder.none,
            enabledBorder: InputBorder.none,
            focusedBorder: InputBorder.none,
          ),
          style: TextStyle(color: AppColors.gray900, fontSize: 16),
        ),
        actions: [
          if (_searchController.text.isNotEmpty)
            IconButton(
              icon: Icon(Icons.close, color: AppColors.gray500),
              onPressed: () {
                _searchController.clear();
                _onSearchChanged('');
              },
            ),
          const SizedBox(width: 8),
        ],
      ),
      body: _buildBody(),
    );
  }

  Widget _buildBody() {
    if (_isLoading) {
      return const Center(
        child: CircularProgressIndicator(),
      );
    }

    if (_searchController.text.isEmpty) {
      return Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(Icons.search, size: 64, color: AppColors.gray300),
            const SizedBox(height: 16),
            Text(
              'Search for festivals, categories\nand custom posts',
              textAlign: TextAlign.center,
              style: TextStyle(color: AppColors.gray500, fontSize: 16),
            ),
          ],
        ),
      );
    }

    if (_searchResults.isEmpty) {
      return Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(Icons.search_off, size: 64, color: AppColors.gray300),
            const SizedBox(height: 16),
            Text(
              'No results found for "${_searchController.text}"',
              style: TextStyle(color: AppColors.gray500, fontSize: 16),
            ),
          ],
        ),
      );
    }

    return GridView.builder(
      padding: const EdgeInsets.all(16),
      gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
        crossAxisCount: 2,
        mainAxisSpacing: 16,
        crossAxisSpacing: 16,
        childAspectRatio: 0.8,
      ),
      itemCount: _searchResults.length,
      itemBuilder: (context, index) {
        final post = _searchResults[index];
        final imgUrl = post['image']?.toString() ?? '';
        
        return GestureDetector(
          onTap: () => _openEditor(post),
          child: Container(
            decoration: BoxDecoration(
              borderRadius: BorderRadius.circular(16),
              color: Colors.white,
              boxShadow: [
                BoxShadow(color: Colors.black.withOpacity(0.05), blurRadius: 8, offset: const Offset(0, 2)),
              ],
            ),
            clipBehavior: Clip.antiAlias,
            child: Stack(
              children: [
                if (imgUrl.isNotEmpty)
                  Positioned.fill(
                    child: Image.network(
                      imgUrl,
                      fit: BoxFit.cover,
                      loadingBuilder: (context, child, progress) {
                        if (progress == null) return child;
                        return const Center(child: CircularProgressIndicator(strokeWidth: 2));
                      },
                      errorBuilder: (_, __, ___) => _buildPlaceholder(),
                    ),
                  )
                else
                  Positioned.fill(child: _buildPlaceholder()),

                Positioned(
                  top: 4,
                  right: 4,
                  child: Container(
                    padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 3),
                    decoration: BoxDecoration(
                      color: post['is_paid'] == true || post['is_paid'] == '1' || post['is_paid'] == 1 ? Colors.black45 : Colors.green.withOpacity(0.8),
                      borderRadius: BorderRadius.circular(6),
                    ),
                    child: Text(
                      post['is_paid'] == true || post['is_paid'] == '1' || post['is_paid'] == 1 ? 'PREMIUM' : 'FREE',
                      style: const TextStyle(fontSize: 9, fontWeight: FontWeight.w900, color: Colors.white, letterSpacing: 0.5),
                    ),
                  ),
                ),
                
                Positioned(
                  bottom: 8,
                  left: 8,
                  child: Container(
                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                    decoration: BoxDecoration(
                      color: Colors.black.withOpacity(0.6),
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: Text(
                      (post['type'] ?? '').toString().toUpperCase(),
                      style: const TextStyle(color: Colors.white, fontSize: 10, fontWeight: FontWeight.bold),
                    ),
                  ),
                ),
              ],
            ),
          ),
        );
      },
    );
  }

  Widget _buildPlaceholder() {
    return Container(
      color: const Color(0xFFF1F5F9),
      child: Center(
        child: Icon(Icons.image_outlined, color: Colors.grey.shade300, size: 36),
      ),
    );
  }
}

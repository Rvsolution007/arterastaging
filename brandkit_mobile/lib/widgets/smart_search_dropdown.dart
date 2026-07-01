import 'dart:convert';
import 'dart:async';
import 'package:flutter/material.dart';
import '../services/api_service.dart';
import '../utils/app_colors.dart';

class SmartSearchDropdown extends StatefulWidget {
  final String title;
  final Function(String categoryId, String subCategoryId, String? businessTypeId) onSelected;
  
  const SmartSearchDropdown({
    super.key, 
    required this.title, 
    required this.onSelected
  });

  @override
  State<SmartSearchDropdown> createState() => _SmartSearchDropdownState();
}

class _SmartSearchDropdownState extends State<SmartSearchDropdown> {
  final TextEditingController _searchController = TextEditingController();
  final FocusNode _focusNode = FocusNode();
  final LayerLink _layerLink = LayerLink();
  OverlayEntry? _overlayEntry;
  
  List<dynamic> _searchResults = [];
  bool _isLoading = false;
  Timer? _debounce;
  
  Map<String, dynamic>? _selectedItem;

  @override
  void initState() {
    super.initState();
    _focusNode.addListener(() {
      if (_focusNode.hasFocus) {
        _showOverlay();
        if (_searchController.text.isNotEmpty) {
          _performSearch(_searchController.text);
        }
      } else {
        _hideOverlay();
      }
    });
  }

  @override
  void dispose() {
    _searchController.dispose();
    _focusNode.dispose();
    _hideOverlay();
    _debounce?.cancel();
    super.dispose();
  }

  void _onSearchChanged(String query) {
    if (_debounce?.isActive ?? false) _debounce!.cancel();
    _debounce = Timer(const Duration(milliseconds: 500), () {
      if (query.isNotEmpty) {
        _performSearch(query);
      } else {
        setState(() {
          _searchResults = [];
          if (_overlayEntry != null) {
            _overlayEntry!.markNeedsBuild();
          }
        });
      }
    });
  }

  Future<void> _performSearch(String query) async {
    setState(() => _isLoading = true);
    if (_overlayEntry != null) _overlayEntry!.markNeedsBuild();
    
    try {
      final res = await ApiService.get('/business-profile/search?query=$query');
      if (res.statusCode == 200) {
        final data = jsonDecode(res.body);
        if (mounted) {
          setState(() {
            _searchResults = data['data'] ?? [];
            _isLoading = false;
          });
          if (_overlayEntry != null) _overlayEntry!.markNeedsBuild();
        }
      }
    } catch (e) {
      if (mounted) {
        setState(() => _isLoading = false);
        if (_overlayEntry != null) _overlayEntry!.markNeedsBuild();
      }
    }
  }

  void _showOverlay() {
    if (_overlayEntry != null) return;
    
    RenderBox renderBox = context.findRenderObject() as RenderBox;
    var size = renderBox.size;
    
    _overlayEntry = OverlayEntry(
      builder: (context) => Positioned(
        width: size.width,
        child: CompositedTransformFollower(
          link: _layerLink,
          showWhenUnlinked: false,
          offset: Offset(0.0, size.height + 5.0),
          child: Material(
            elevation: 4.0,
            borderRadius: BorderRadius.circular(12),
            color: Colors.white,
            child: ConstrainedBox(
              constraints: const BoxConstraints(maxHeight: 300),
              child: _buildSearchResults(),
            ),
          ),
        ),
      ),
    );
    
    Overlay.of(context).insert(_overlayEntry!);
  }

  void _hideOverlay() {
    _overlayEntry?.remove();
    _overlayEntry = null;
  }

  Widget _buildSearchResults() {
    return StatefulBuilder(
      builder: (context, setStateOverlay) {
        if (_isLoading) {
          return const Padding(
            padding: EdgeInsets.all(20.0),
            child: Center(child: CircularProgressIndicator()),
          );
        }
        
        if (_searchResults.isEmpty) {
          return Padding(
            padding: const EdgeInsets.all(20.0),
            child: Text(
              _searchController.text.isEmpty 
                  ? 'Type to search...' 
                  : 'No results found',
              style: const TextStyle(color: Colors.grey),
              textAlign: TextAlign.center,
            ),
          );
        }

        return ListView.separated(
          shrinkWrap: true,
          padding: EdgeInsets.zero,
          itemCount: _searchResults.length,
          separatorBuilder: (context, index) => const Divider(height: 1),
          itemBuilder: (context, index) {
            final item = _searchResults[index];
            final bool isSubCat = item['type'] == 'sub_category';
            
            return ListTile(
              contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 4),
              title: Text(
                item['name'],
                style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 16),
              ),
              subtitle: Text(
                isSubCat 
                    ? 'In ${item['category_name']}' 
                    : 'In ${item['category_name']} > ${item['sub_category_name']}',
                style: TextStyle(color: Colors.grey[600], fontSize: 12),
              ),
              trailing: Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                decoration: BoxDecoration(
                  color: isSubCat ? Colors.blue.withOpacity(0.1) : Colors.purple.withOpacity(0.1),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Text(
                  isSubCat ? 'Category' : 'Business Type',
                  style: TextStyle(
                    fontSize: 10,
                    color: isSubCat ? Colors.blue[800] : Colors.purple[800],
                    fontWeight: FontWeight.bold,
                  ),
                ),
              ),
              onTap: () {
                setState(() {
                  _selectedItem = item;
                  _searchController.text = item['name'];
                });
                _focusNode.unfocus();
                
                widget.onSelected(
                  item['category_id'].toString(),
                  item['sub_category_id'].toString(),
                  isSubCat ? null : item['business_type_id'].toString(),
                );
              },
            );
          },
        );
      },
    );
  }

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        CompositedTransformTarget(
          link: _layerLink,
          child: TextField(
            controller: _searchController,
            focusNode: _focusNode,
            onChanged: _onSearchChanged,
            decoration: InputDecoration(
              labelText: widget.title,
              hintText: 'e.g. AC Repair, Boutique, CA...',
              prefixIcon: const Icon(Icons.search, color: AppColors.primary),
              suffixIcon: _selectedItem != null 
                  ? IconButton(
                      icon: const Icon(Icons.close),
                      onPressed: () {
                        setState(() {
                          _selectedItem = null;
                          _searchController.clear();
                        });
                        widget.onSelected('', '', null);
                      },
                    )
                  : null,
              border: OutlineInputBorder(
                borderRadius: BorderRadius.circular(12),
                borderSide: const BorderSide(color: Colors.grey),
              ),
              focusedBorder: OutlineInputBorder(
                borderRadius: BorderRadius.circular(12),
                borderSide: const BorderSide(color: AppColors.primary, width: 2),
              ),
              filled: true,
              fillColor: _selectedItem != null ? AppColors.primary.withOpacity(0.05) : Colors.white,
            ),
          ),
        ),
        if (_selectedItem != null) ...[
          const SizedBox(height: 8),
          Container(
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(
              color: AppColors.primary.withOpacity(0.1),
              borderRadius: BorderRadius.circular(8),
              border: Border.all(color: AppColors.primary.withOpacity(0.3)),
            ),
            child: Row(
              children: [
                const Icon(Icons.check_circle, color: AppColors.primary, size: 20),
                const SizedBox(width: 8),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text(
                        'Selected Category Path:',
                        style: TextStyle(fontSize: 12, color: Colors.grey),
                      ),
                      Text(
                        _selectedItem!['type'] == 'sub_category'
                            ? '${_selectedItem!['category_name']} > ${_selectedItem!['name']}'
                            : '${_selectedItem!['category_name']} > ${_selectedItem!['sub_category_name']} > ${_selectedItem!['name']}',
                        style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
        ],
      ],
    );
  }
}

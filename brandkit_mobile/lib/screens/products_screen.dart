import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'dart:convert';
import 'dart:io';
import 'package:image_picker/image_picker.dart';
import 'package:flutter/foundation.dart' show kIsWeb;
import '../services/api_service.dart';
import '../utils/app_colors.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:image_picker/image_picker.dart';
import 'package:cached_network_image/cached_network_image.dart';

class ProductsScreen extends StatefulWidget {
  const ProductsScreen({super.key});

  @override
  State<ProductsScreen> createState() => _ProductsScreenState();
}

class _ProductsScreenState extends State<ProductsScreen> {
  bool _isLoading = true;
  String _userId = '';
  
  List<dynamic> _products = [];
  List<dynamic> _customColumns = [];
  List<String> _categories = [];
  String _searchQuery = '';
  String? _selectedCategory;

  @override
  void initState() {
    super.initState();
    _loadData();
  }

  Future<void> _loadData() async {
    final prefs = await SharedPreferences.getInstance();
    _userId = prefs.getString('userId') ?? '';

    if (_userId.isEmpty) {
      setState(() => _isLoading = false);
      return;
    }

    try {
      final response = await ApiService.post('/products/list', {
        'userId': _userId,
      });

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['success'] == true) {
          final rawProducts = data['products']['data'] ?? [];
          
          // Extract unique categories
          Set<String> cats = {};
          for (var p in rawProducts) {
            if (p['category_name'] != null && p['category_name'].toString().isNotEmpty) {
              cats.add(p['category_name']);
            }
          }

          setState(() {
            _products = rawProducts;
            _customColumns = data['customColumns'] ?? [];
            _categories = cats.toList()..sort();
            _isLoading = false;
          });
        } else {
          _showError(data['message'] ?? 'Failed to load products');
        }
      } else {
        _showError('Server error: ${response.statusCode}');
      }
    } catch (e) {
      _showError('Error connecting to server: $e');
    }
  }

  void _showError(String message) {
    setState(() => _isLoading = false);
    ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(message), backgroundColor: Colors.red));
  }

  List<dynamic> get _filteredProducts {
    return _products.where((p) {
      final matchesSearch = _searchQuery.isEmpty || 
          (p['title']?.toString().toLowerCase().contains(_searchQuery.toLowerCase()) ?? false) ||
          (p['sku']?.toString().toLowerCase().contains(_searchQuery.toLowerCase()) ?? false);
          
      final matchesCat = _selectedCategory == null || p['category_name'] == _selectedCategory;
      
      return matchesSearch && matchesCat;
    }).toList();
  }

  void _showEditModal(Map<String, dynamic> product) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (context) => _EditProductModal(
        product: product,
        customColumns: _customColumns,
        userId: _userId,
        onSave: () {
          Navigator.pop(context);
          setState(() => _isLoading = true);
          _loadData();
        },
        onDelete: () {
          Navigator.pop(context);
          setState(() => _isLoading = true);
          _loadData();
        },
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF8FAFC),
      appBar: AppBar(
        backgroundColor: Colors.white,
        elevation: 0,
        centerTitle: true,
        title: Column(
          children: [
            const Text('Products', style: TextStyle(color: Colors.black87, fontWeight: FontWeight.bold, fontSize: 18)),
            if (!_isLoading)
              Text('${_filteredProducts.length} items', style: const TextStyle(color: Colors.grey, fontSize: 12)),
          ],
        ),
        leading: IconButton(
          icon: const Icon(Icons.arrow_back, color: Colors.black87),
          onPressed: () => Navigator.pop(context),
        ),
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator(color: Colors.indigo))
          : Column(
              children: [
                // Search Bar
                Container(
                  color: Colors.white,
                  padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                  child: TextField(
                    onChanged: (v) => setState(() => _searchQuery = v),
                    decoration: InputDecoration(
                      hintText: 'Search products, Category...',
                      hintStyle: TextStyle(color: Colors.grey.shade400, fontSize: 14),
                      prefixIcon: Icon(Icons.search, color: Colors.grey.shade400, size: 20),
                      filled: true,
                      fillColor: const Color(0xFFF8FAFC),
                      contentPadding: const EdgeInsets.symmetric(vertical: 0),
                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide(color: Colors.grey.shade200)),
                      enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide(color: Colors.grey.shade200)),
                      focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: Colors.indigo)),
                    ),
                  ),
                ),
                
                // Categories
                if (_categories.isNotEmpty)
                  Container(
                    color: Colors.white,
                    height: 50,
                    child: ListView(
                      scrollDirection: Axis.horizontal,
                      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                      children: [
                        _buildCatChip('All', _selectedCategory == null, () => setState(() => _selectedCategory = null)),
                        ..._categories.map((c) => _buildCatChip(c, _selectedCategory == c, () => setState(() => _selectedCategory = c))),
                      ],
                    ),
                  ),

                // Products List
                Expanded(
                  child: _filteredProducts.isEmpty
                      ? Center(
                          child: Column(
                            mainAxisAlignment: MainAxisAlignment.center,
                            children: [
                              Icon(Icons.inventory_2_outlined, size: 64, color: Colors.grey.shade300),
                              const SizedBox(height: 16),
                              Text('No products found', style: TextStyle(color: Colors.grey.shade600, fontWeight: FontWeight.bold)),
                            ],
                          ),
                        )
                      : ListView.builder(
                          padding: const EdgeInsets.all(16),
                          itemCount: _filteredProducts.length,
                          itemBuilder: (context, index) {
                            final p = _filteredProducts[index];
                            return _buildProductCard(p);
                          },
                        ),
                ),
              ],
            ),
    );
  }

  Widget _buildCatChip(String label, bool isSelected, VoidCallback onTap) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        margin: const EdgeInsets.only(right: 8),
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 6),
        decoration: BoxDecoration(
          color: isSelected ? Colors.indigo : Colors.white,
          borderRadius: BorderRadius.circular(20),
          border: Border.all(color: isSelected ? Colors.indigo : Colors.grey.shade200),
          boxShadow: isSelected ? [BoxShadow(color: Colors.indigo.withOpacity(0.3), blurRadius: 4, offset: const Offset(0, 2))] : null,
        ),
        child: Text(label, style: TextStyle(color: isSelected ? Colors.white : Colors.grey.shade700, fontSize: 12, fontWeight: FontWeight.bold)),
      ),
    );
  }

  Widget _buildProductCard(Map<String, dynamic> p) {
    // Determine Display Title (same logic as web: Unique > Title fallback)
    String displayTitle = p['title'] ?? 'Product';
    String displayLabel = 'Product';
    
    // Find unique col
    final uniqueCol = _customColumns.firstWhere((c) => c['is_unique'] == 1 || c['is_unique'] == true, orElse: () => null);
    if (uniqueCol != null) {
      displayLabel = uniqueCol['name'];
      final cv = (p['custom_values'] as List?)?.firstWhere((v) => v['column_id'] == uniqueCol['id'], orElse: () => null);
      if (cv != null && cv['value'] != null && cv['value'].toString().isNotEmpty) {
        displayTitle = cv['value'];
      }
    }

    // Get listable columns
    final listCols = _customColumns.where((c) => c['show_on_list'] == 1 || c['show_on_list'] == true).toList();

    // Find category col
    final categoryCol = _customColumns.firstWhere((c) => c['is_category'] == 1 || c['is_category'] == true, orElse: () => null);
    String categoryValue = '';
    if (categoryCol != null) {
      final cv = (p['custom_values'] as List?)?.firstWhere((v) => v['column_id'] == categoryCol['id'], orElse: () => null);
      if (cv != null && cv['value'] != null && cv['value'].toString().isNotEmpty) {
        categoryValue = cv['value'];
      }
    }

    return GestureDetector(
      onTap: () => _showEditModal(p),
      child: Container(
        margin: const EdgeInsets.only(bottom: 16),
        padding: const EdgeInsets.all(20),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(20),
          border: Border.all(color: const Color(0xFFF1F5F9)), // slate-100
          boxShadow: const [BoxShadow(color: Color(0x0A000000), blurRadius: 4, offset: Offset(0, 1))], // shadow-sm
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // Icon/Avatar
                Container(
                  width: 48, height: 48,
                  decoration: BoxDecoration(
                    gradient: const LinearGradient(
                      colors: [Color(0xFF6366F1), Color(0xFF9333EA)], // indigo-500 to purple-600
                      begin: Alignment.topLeft, end: Alignment.bottomRight,
                    ),
                    borderRadius: BorderRadius.circular(16),
                  ),
                  child: p['image_url'] != null && p['image_url'].toString().isNotEmpty
                      ? ClipRRect(
                          borderRadius: BorderRadius.circular(16),
                          child: Image.network(
                            p['image_url'],
                            width: 48, height: 48,
                            fit: BoxFit.cover,
                            loadingBuilder: (context, child, loadingProgress) {
                              if (loadingProgress == null) return child;
                              return const Center(child: CircularProgressIndicator(strokeWidth: 2));
                            },
                            errorBuilder: (context, error, stackTrace) => const Icon(Icons.inventory_2_outlined, color: Colors.white, size: 24),
                          ),
                        )
                      : const Icon(Icons.inventory_2_outlined, color: Colors.white, size: 24),
                ),
                const SizedBox(width: 16),
                
                // Info
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Wrap(
                        crossAxisAlignment: WrapCrossAlignment.end,
                        spacing: 6,
                        children: [
                          Text('${displayLabel.toUpperCase()}:', 
                            style: GoogleFonts.inter(fontSize: 11, fontWeight: FontWeight.bold, color: const Color(0xFF94A3B8), letterSpacing: 0.5)),
                          Text(displayTitle, 
                            style: GoogleFonts.inter(fontSize: 18, fontWeight: FontWeight.w900, color: const Color(0xFF1E293B), letterSpacing: -0.5), maxLines: 1, overflow: TextOverflow.ellipsis),
                        ],
                      ),
                      // Show Category Flag instead of SKU
                      if (categoryValue.isNotEmpty || (p['category_name'] != null && p['category_name'].toString().isNotEmpty))
                        Padding(
                          padding: const EdgeInsets.only(top: 6),
                          child: Text('${(categoryCol != null ? categoryCol['name'] : 'CATEGORY').toUpperCase()}: ${categoryValue.isNotEmpty ? categoryValue : p['category_name']}', 
                            style: GoogleFonts.inter(fontSize: 10, fontWeight: FontWeight.bold, color: const Color(0xFF94A3B8), letterSpacing: 0.5)),
                        ),
                    ],
                  ),
                ),
                
                // Arrow
                Container(
                  width: 32, height: 32,
                  decoration: const BoxDecoration(color: Color(0xFFF8FAFC), shape: BoxShape.circle),
                  child: const Icon(Icons.chevron_right, color: Color(0xFF94A3B8), size: 16),
                ),
              ],
            ),
            
          ],
        ),
      ),
    );
  }
}

class _EditProductModal extends StatefulWidget {
  final Map<String, dynamic> product;
  final List<dynamic> customColumns;
  final String userId;
  final VoidCallback onSave;
  final VoidCallback onDelete;

  const _EditProductModal({
    required this.product,
    required this.customColumns,
    required this.userId,
    required this.onSave,
    required this.onDelete,
  });

  @override
  State<_EditProductModal> createState() => _EditProductModalState();
}

class _EditProductModalState extends State<_EditProductModal> {
  bool _isSaving = false;
  XFile? _imageFile;
  final ImagePicker _picker = ImagePicker();
  
  final TextEditingController _titleCtrl = TextEditingController();
  final TextEditingController _categoryCtrl = TextEditingController();
  final TextEditingController _skuCtrl = TextEditingController();
  final TextEditingController _mrpCtrl = TextEditingController();
  final TextEditingController _saleCtrl = TextEditingController();

  final Map<String, TextEditingController> _customCtrls = {};

  @override
  void initState() {
    super.initState();
    _titleCtrl.text = widget.product['title'] ?? '';
    _categoryCtrl.text = widget.product['category_name'] ?? '';
    _skuCtrl.text = widget.product['sku'] ?? '';
    _mrpCtrl.text = ((widget.product['mrp'] ?? 0) / 100).toStringAsFixed(2);
    _saleCtrl.text = ((widget.product['sale_price'] ?? 0) / 100).toStringAsFixed(2);

    for (var col in widget.customColumns) {
      final cv = (widget.product['custom_values'] as List?)?.firstWhere((v) => v['column_id'] == col['id'], orElse: () => null);
      String val = cv != null ? (cv['value']?.toString() ?? '') : '';
      
      if (val.startsWith('[')) {
        try {
          List parsed = jsonDecode(val);
          val = parsed.join(', ');
        } catch (_) {}
      }
      
      _customCtrls[col['id'].toString()] = TextEditingController(text: val);
    }
  }

  Future<void> _pickImage() async {
    final XFile? image = await _picker.pickImage(source: ImageSource.gallery, imageQuality: 80);
    if (image != null) {
      setState(() {
        _imageFile = image;
      });
    }
  }

  Future<void> _save() async {
    setState(() => _isSaving = true);
    
    final Map<String, String> fields = {
      'userId': widget.userId,
      'title': _titleCtrl.text,
      'category_name': _categoryCtrl.text,
      'sku': _skuCtrl.text,
      'mrp': _mrpCtrl.text,
      'sale_price': _saleCtrl.text,
    };

    _customCtrls.forEach((key, ctrl) {
      fields['custom_data[$key]'] = ctrl.text;
    });

    // Debug: Log image upload details
    List<int>? imageBytes;
    if (kIsWeb && _imageFile != null) {
      imageBytes = await _imageFile!.readAsBytes();
      debugPrint('[ProductSave] Web image picked: name=${_imageFile!.name}, size=${imageBytes.length} bytes');
    } else if (_imageFile != null) {
      debugPrint('[ProductSave] Native image picked: path=${_imageFile!.path}, name=${_imageFile!.name}');
    } else {
      debugPrint('[ProductSave] No new image selected');
    }

    try {
      final response = await ApiService.multipartPost(
        '/products/${widget.product['id']}/update',
        fields,
        fileKey: 'image',
        filePath: kIsWeb ? null : _imageFile?.path,
        fileBytes: kIsWeb ? imageBytes : null,
        fileName: _imageFile?.name,
      );

      debugPrint('[ProductSave] Server response: ${response.statusCode} - ${response.body}');

      if (response.statusCode == 200) {
        widget.onSave();
      } else {
        if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Failed to save (${response.statusCode})')));
      }
    } catch (e) {
      debugPrint('[ProductSave] Exception: $e');
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Error: $e')));
    } finally {
      if (mounted) setState(() => _isSaving = false);
    }
  }

  Future<void> _delete() async {
    final confirm = await showDialog<bool>(
      context: context,
      builder: (c) => AlertDialog(
        title: const Text('Delete Product?'),
        content: const Text('This cannot be undone.'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(c, false), child: const Text('Cancel')),
          TextButton(onPressed: () => Navigator.pop(c, true), child: const Text('Delete', style: TextStyle(color: Colors.red))),
        ],
      ),
    );

    if (confirm != true) return;

    setState(() => _isSaving = true);
    try {
      final response = await ApiService.post('/products/${widget.product['id']}/delete', {
        'userId': widget.userId,
      });
      if (response.statusCode == 200) {
        widget.onDelete();
      } else {
        ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Failed to delete')));
      }
    } finally {
      if (mounted) setState(() => _isSaving = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      height: MediaQuery.of(context).size.height * 0.9,
      decoration: const BoxDecoration(
        color: Color(0xFFF8FAFC),
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      child: Column(
        children: [
          // Header
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
            decoration: const BoxDecoration(color: Colors.white, borderRadius: BorderRadius.vertical(top: Radius.circular(24)), boxShadow: [BoxShadow(color: Colors.black12, blurRadius: 4)]),
            child: Row(
              children: [
                IconButton(icon: const Icon(Icons.close), onPressed: () => Navigator.pop(context)),
                const Expanded(child: Text('Edit Product', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold))),
                IconButton(icon: const Icon(Icons.delete_outline, color: Colors.red), onPressed: _delete),
                ElevatedButton(
                  onPressed: _isSaving ? null : _save,
                  style: ElevatedButton.styleFrom(backgroundColor: Colors.indigo, shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20))),
                  child: _isSaving ? const SizedBox(width: 16, height: 16, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2)) : const Text('Save', style: TextStyle(color: Colors.white)),
                ),
              ],
            ),
          ),
          
          // Form
          Expanded(
            child: ListView(
              padding: const EdgeInsets.all(16),
              children: [
                _buildSectionTitle('Product Image'),
                Container(
                  width: double.infinity,
                  margin: const EdgeInsets.only(bottom: 24),
                  padding: const EdgeInsets.all(16),
                  decoration: BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.circular(20),
                    border: Border.all(color: const Color(0xFFE2E8F0)),
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text('Product Image', style: TextStyle(fontWeight: FontWeight.w700, fontSize: 16)),
                      const SizedBox(height: 12),
                      GestureDetector(
                        onTap: _pickImage,
                        child: Container(
                          height: 180,
                          width: double.infinity,
                          decoration: BoxDecoration(
                            color: const Color(0xFFF8FAFC),
                            borderRadius: BorderRadius.circular(16),
                            border: Border.all(color: const Color(0xFFCBD5E1), style: BorderStyle.solid),
                          ),
                          child: _imageFile != null
                              ? ClipRRect(
                                  borderRadius: BorderRadius.circular(16),
                                  child: kIsWeb 
                                    ? Image.network(_imageFile!.path, fit: BoxFit.cover)
                                    : Image.file(File(_imageFile!.path), fit: BoxFit.cover),
                                )
                              : widget.product['image_url'] != null
                                  ? ClipRRect(
                                      borderRadius: BorderRadius.circular(16),
                                      child: Image.network(
                                        widget.product['image_url'],
                                        fit: BoxFit.cover,
                                        loadingBuilder: (context, child, loadingProgress) {
                                          if (loadingProgress == null) return child;
                                          return const Center(child: CircularProgressIndicator());
                                        },
                                        errorBuilder: (context, error, stackTrace) => const Icon(Icons.image, size: 50, color: Colors.grey),
                                      ),
                                    )
                                  : Column(
                                      mainAxisAlignment: MainAxisAlignment.center,
                                      children: [
                                        const Icon(Icons.add_a_photo_outlined, size: 32, color: Color(0xFF94A3B8)),
                                        const SizedBox(height: 8),
                                        const Text('Tap to upload image', style: TextStyle(color: Color(0xFF64748B))),
                                      ],
                                    ),
                        ),
                      ),
                    ],
                  ),
                ),
                _buildSectionTitle('System Fields'),
                _buildTextField('Title (Fallback)', _titleCtrl),
                Row(
                  children: [
                    Expanded(child: _buildTextField('Category', _categoryCtrl)),
                    const SizedBox(width: 12),
                    Expanded(child: _buildTextField('SKU', _skuCtrl)),
                  ],
                ),
                Row(
                  children: [
                    Expanded(child: _buildTextField('MRP', _mrpCtrl, isNum: true)),
                    const SizedBox(width: 12),
                    Expanded(child: _buildTextField('Sale Price', _saleCtrl, isNum: true)),
                  ],
                ),
                
                const SizedBox(height: 24),
                _buildSectionTitle('Custom Fields'),
                ...(() {
                  var sortedCols = List<dynamic>.from(widget.customColumns);
                  sortedCols.sort((a, b) {
                    bool aHasData = _customCtrls[a['id'].toString()]?.text.trim().isNotEmpty ?? false;
                    bool bHasData = _customCtrls[b['id'].toString()]?.text.trim().isNotEmpty ?? false;
                    if (aHasData && !bHasData) return -1;
                    if (!aHasData && bHasData) return 1;
                    return 0;
                  });
                  return sortedCols.map((col) {
                    return _buildTextField(
                      col['name'] + (col['is_combo'] == true || col['is_combo'] == 1 ? ' (Comma separated)' : ''), 
                      _customCtrls[col['id'].toString()]!
                    );
                  });
                })(),
                
                const SizedBox(height: 300), // Keyboard padding
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildSectionTitle(String title) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: Text(title, style: const TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: Colors.black87)),
    );
  }

  Widget _buildTextField(String label, TextEditingController ctrl, {bool isNum = false}) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(label, style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Colors.black54)),
          const SizedBox(height: 6),
          TextField(
            controller: ctrl,
            keyboardType: isNum ? TextInputType.number : TextInputType.text,
            decoration: InputDecoration(
              filled: true,
              fillColor: Colors.white,
              contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
              border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide(color: Colors.grey.shade200)),
              enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide(color: Colors.grey.shade200)),
              focusedBorder: const OutlineInputBorder(borderRadius: BorderRadius.all(Radius.circular(12)), borderSide: BorderSide(color: Colors.indigo)),
            ),
          ),
        ],
      ),
    );
  }
}

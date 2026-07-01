import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'dart:convert';
import 'dart:io';
import 'package:image_picker/image_picker.dart';
import 'package:flutter/foundation.dart' show kIsWeb;
import '../services/api_service.dart';
import '../utils/app_colors.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:cached_network_image/cached_network_image.dart';
import 'dart:async';

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
  String _searchQuery = '';
  String? _selectedCategory;
  
  // Custom column ID to selected option string
  Map<int, String?> _selectedFilters = {};

  bool _isExtracting = false;
  int _extractSeconds = 0;
  Timer? _extractTimer;

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
          setState(() {
            _products = rawProducts;
            _customColumns = data['customColumns'] ?? [];
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

  @override
  void dispose() {
    _extractTimer?.cancel();
    super.dispose();
  }

  void _showError(String message) {
    setState(() {
      _isLoading = false;
      _isExtracting = false;
    });
    _extractTimer?.cancel();
    if (!mounted) return;
    
    if (message.contains('Diagnosis') || message.length > 80) {
      showDialog(
        context: context,
        builder: (c) => AlertDialog(
          title: const Text('Extraction Error'),
          content: SingleChildScrollView(child: Text(message)),
          actions: [
            TextButton(onPressed: () => Navigator.pop(c), child: const Text('OK')),
          ],
        ),
      );
    } else {
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(message), backgroundColor: Colors.red));
    }
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

  void _showAddOptions() {
    showModalBottomSheet(
      context: context,
      shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(20))),
      builder: (context) => Padding(
        padding: const EdgeInsets.all(24.0),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Text('Add Product', style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold)),
            const SizedBox(height: 24),
            ListTile(
              leading: Container(
                padding: const EdgeInsets.all(10),
                decoration: BoxDecoration(color: Colors.indigo.withOpacity(0.1), borderRadius: BorderRadius.circular(10)),
                child: const Icon(Icons.edit_note, color: Colors.indigo),
              ),
              title: const Text('Manual Entry', style: TextStyle(fontWeight: FontWeight.bold)),
              subtitle: const Text('Fill in the product details yourself'),
              onTap: () {
                Navigator.pop(context);
                _showEditModal({}); // Empty map means creation
              },
            ),
            const SizedBox(height: 12),
            ListTile(
              leading: Container(
                padding: const EdgeInsets.all(10),
                decoration: BoxDecoration(color: Colors.green.withOpacity(0.1), borderRadius: BorderRadius.circular(10)),
                child: const Icon(Icons.document_scanner, color: Colors.green),
              ),
              title: const Text('AI Image Extraction', style: TextStyle(fontWeight: FontWeight.bold)),
              subtitle: const Text('Upload an image to auto-fill details'),
              onTap: () {
                Navigator.pop(context);
                _pickImageForAI();
              },
            ),
          ],
        ),
      ),
    );
  }

  Future<void> _pickImageForAI() async {
    final ImagePicker picker = ImagePicker();
    final XFile? image = await picker.pickImage(source: ImageSource.gallery);
    
    if (image != null) {
      _processImageWithAI(File(image.path));
    }
  }

  Future<void> _processImageWithAI(File imageFile) async {
    setState(() {
      _isLoading = true;
      _isExtracting = true;
      _extractSeconds = 0;
    });
    
    _extractTimer = Timer.periodic(const Duration(seconds: 1), (timer) {
      if (mounted) {
        setState(() {
          _extractSeconds++;
        });
      }
    });
    
    try {
      final response = await ApiService.multipartPost(
        '/products/extract-from-image',
        {'userId': _userId, 'source': 'mobile'},
        fileKey: 'image',
        filePath: kIsWeb ? null : imageFile.path,
        fileBytes: kIsWeb ? await imageFile.readAsBytes() : null,
      );
      
      _extractTimer?.cancel();
      setState(() => _isExtracting = false);
      
      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['success'] == true) {
          final List<dynamic> extractedProducts = data['products'] ?? [];
          setState(() => _isLoading = false);
          if (extractedProducts.isEmpty) {
            _showError('No products found in the image.');
          } else {
            _showReviewProductsSheet(extractedProducts);
          }
        } else {
          _showError(data['message'] ?? 'Extraction failed');
        }
      } else {
        String msg = 'Server error: ${response.statusCode}';
        try {
          final errData = jsonDecode(response.body);
          if (errData['diagnosis'] != null) {
            msg += '\n\nDiagnosis: ${errData['diagnosis']}';
          }
          if (errData['message'] != null) {
            msg += '\n${errData['message']}';
          }
        } catch (_) {}
        
        _showError(msg);
      }
    } catch (e) {
      _extractTimer?.cancel();
      setState(() => _isExtracting = false);
      _showError('Error connecting to server: $e');
    }
  }

  void _showReviewProductsSheet(List<dynamic> products) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (sheetContext) => _ReviewExtractedProductsSheet(
        products: List<Map<String, dynamic>>.from(products.map((p) => Map<String, dynamic>.from(p))),
        customColumns: _customColumns,
        userId: _userId,
        onSaveAll: (finalProducts) async {
          Navigator.pop(sheetContext);
          setState(() => _isLoading = true);
          
          try {
            final response = await ApiService.post('/products/bulk-create', {
              'userId': _userId,
              'products': jsonEncode(finalProducts),
            });
            
            if (!mounted) return;

            if (response.statusCode == 200) {
              final data = jsonDecode(response.body);
              if (data['success'] == true) {
                ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Products saved successfully!'), backgroundColor: Colors.green));
                _loadData();
              } else {
                _showError(data['message'] ?? 'Failed to save products');
              }
            } else {
              _showError('Server error: ${response.statusCode}');
            }
          } catch (e) {
            if (mounted) _showError('Error saving products: $e');
          }
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
      floatingActionButton: FloatingActionButton.extended(
        backgroundColor: Colors.indigo,
        onPressed: _showAddOptions,
        icon: const Icon(Icons.add, color: Colors.white),
        label: const Text('Add Product', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
      ),
      body: Stack(
        children: [
          _isLoading && !_isExtracting
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
                
                // Dynamic Column Filters (Manage Only)
                Padding(
                  padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                  child: Wrap(
                    spacing: 8,
                    runSpacing: 8,
                    children: _customColumns.where((c) => 
                      (c['type'] == 'select' || c['type'] == 'multiselect') && 
                      (c['is_unique'] != 1 && c['is_unique'] != true) &&
                      c['options'] != null
                    ).map((col) {
                      int colId = col['id'];
                      return InkWell(
                        onTap: () {
                          showModalBottomSheet(
                            context: context,
                            isScrollControlled: true,
                            backgroundColor: Colors.transparent,
                            builder: (context) => _ManageOptionsSheet(
                              userId: _userId,
                              onOptionsUpdated: _loadData,
                              columnId: colId,
                              columnName: col['name'],
                            ),
                          );
                        },
                        child: Container(
                          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                          decoration: BoxDecoration(
                            color: Colors.white,
                            borderRadius: BorderRadius.circular(20),
                            border: Border.all(color: Colors.grey.shade300),
                          ),
                          child: Row(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              Text('${col['name']}', style: TextStyle(fontWeight: FontWeight.bold, color: Colors.grey.shade700, fontSize: 13)),
                              const SizedBox(width: 4),
                              Icon(Icons.settings, size: 14, color: Colors.grey.shade500),
                            ],
                          ),
                        ),
                      );
                    }).toList(),
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
          if (_isExtracting)
            _buildPremiumLoadingOverlay(),
        ],
      ),
    );
  }

  Widget _buildPremiumLoadingOverlay() {
    final minutes = (_extractSeconds / 60).floor().toString().padLeft(2, '0');
    final seconds = (_extractSeconds % 60).toString().padLeft(2, '0');
    
    String statusText = 'Analyzing image...';
    if (_extractSeconds > 5) statusText = 'Identifying products...';
    if (_extractSeconds > 15) statusText = 'Extracting attributes & mapping columns...';
    if (_extractSeconds > 30) statusText = 'Running AI Vision models...';
    if (_extractSeconds > 60) statusText = 'Almost done! Optimizing data...';

    return Container(
      color: Colors.black54,
      width: double.infinity,
      height: double.infinity,
      child: Center(
        child: Container(
          margin: const EdgeInsets.symmetric(horizontal: 40),
          padding: const EdgeInsets.all(32),
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(24),
            boxShadow: const [
              BoxShadow(color: Colors.black26, blurRadius: 20, spreadRadius: 5)
            ],
          ),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Container(
                width: 80,
                height: 80,
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  color: Colors.indigo.withOpacity(0.1),
                  shape: BoxShape.circle,
                ),
                child: const CircularProgressIndicator(
                  color: Colors.indigo,
                  strokeWidth: 4,
                ),
              ),
              const SizedBox(height: 24),
              Text(
                'AI Extraction',
                style: GoogleFonts.inter(
                  fontSize: 22,
                  fontWeight: FontWeight.w900,
                  color: const Color(0xFF1E293B),
                ),
              ),
              const SizedBox(height: 8),
              Text(
                statusText,
                textAlign: TextAlign.center,
                style: GoogleFonts.inter(
                  fontSize: 14,
                  color: Colors.grey.shade600,
                  fontWeight: FontWeight.w500,
                ),
              ),
              const SizedBox(height: 24),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                decoration: BoxDecoration(
                  color: Colors.grey.shade100,
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    const Icon(Icons.timer_outlined, size: 16, color: Colors.indigo),
                    const SizedBox(width: 8),
                    Text(
                      '$minutes:$seconds',
                      style: GoogleFonts.inter(
                        fontSize: 16,
                        fontWeight: FontWeight.bold,
                        color: Colors.indigo,
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
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

class _ReviewExtractedProductsSheet extends StatefulWidget {
  final List<Map<String, dynamic>> products;
  final List<dynamic> customColumns;
  final String userId;
  final Function(List<Map<String, dynamic>>) onSaveAll;

  const _ReviewExtractedProductsSheet({
    required this.products,
    required this.customColumns,
    required this.userId,
    required this.onSaveAll,
  });

  @override
  State<_ReviewExtractedProductsSheet> createState() => _ReviewExtractedProductsSheetState();
}

class _ReviewExtractedProductsSheetState extends State<_ReviewExtractedProductsSheet> {
  late List<Map<String, dynamic>> _editedProducts;

  @override
  void initState() {
    super.initState();
    _editedProducts = List.from(widget.products);
  }

  void _editProduct(int index) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (context) => _EditProductModal(
        product: _editedProducts[index],
        customColumns: widget.customColumns,
        userId: widget.userId,
        onSave: () {}, // Not used in local edit
        onLocalSave: (editedData) {
          setState(() {
            // Re-map the flat data to the expected format
            Map<String, dynamic> updatedProduct = {
              'sku': editedData['sku'],
              'mrp': (double.tryParse(editedData['mrp']?.toString() ?? '0') ?? 0.0) * 100,
              'sale_price': (double.tryParse(editedData['sale_price']?.toString() ?? '0') ?? 0.0) * 100,
            };
            
            // Map title back to actual column name if it exists
            final titleCol = widget.customColumns.firstWhere((c) => c['is_title'] == 1 || c['is_title'] == true, orElse: () => null);
            if (titleCol != null) {
              updatedProduct[titleCol['name']] = editedData['title'];
            } else {
              updatedProduct['title'] = editedData['title'];
            }

            // Map category back to actual column name if it exists
            final catCol = widget.customColumns.firstWhere((c) => c['is_category'] == 1 || c['is_category'] == true, orElse: () => null);
            if (catCol != null) {
              updatedProduct[catCol['name']] = editedData['category_name'];
            } else {
              updatedProduct['category_name'] = editedData['category_name'];
            }
            
            // Add custom data
            editedData.forEach((key, value) {
              if (key.startsWith('custom_data[')) {
                String colId = key.replaceAll('custom_data[', '').replaceAll(']', '');
                final col = widget.customColumns.firstWhere((c) => c['id'].toString() == colId, orElse: () => null);
                if (col != null) {
                  updatedProduct[col['name']] = value;
                }
              }
            });
            
            _editedProducts[index] = updatedProduct;
          });
          Navigator.pop(context);
        },
      ),
    );
  }

  void _removeProduct(int index) {
    setState(() {
      _editedProducts.removeAt(index);
    });
    if (_editedProducts.isEmpty) {
      Navigator.pop(context);
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
            padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 16),
            decoration: const BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
            ),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                const Text('Review Products', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
                TextButton(
                  onPressed: () => Navigator.pop(context),
                  child: const Text('Cancel', style: TextStyle(color: Colors.grey)),
                ),
              ],
            ),
          ),
          
          // List
          Expanded(
            child: ListView.builder(
              padding: const EdgeInsets.all(16),
              itemCount: _editedProducts.length,
              itemBuilder: (context, index) {
                final p = _editedProducts[index];
                
                // Find title col
                final titleCol = widget.customColumns.firstWhere((c) => c['is_title'] == 1 || c['is_title'] == true, orElse: () => null);
                String titleKey = titleCol != null ? titleCol['name'] : 'title';
                String displayTitle = p[titleKey] ?? p['title'] ?? 'Unknown Product';
                
                // Find category col
                final catCol = widget.customColumns.firstWhere((c) => c['is_category'] == 1 || c['is_category'] == true, orElse: () => null);
                String catKey = catCol != null ? catCol['name'] : 'category_name';
                String displayCat = p[catKey] ?? p['category_name'] ?? 'No Category';
                
                return Card(
                  elevation: 0,
                  margin: const EdgeInsets.only(bottom: 12),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12), side: BorderSide(color: Colors.grey.shade200)),
                  child: ListTile(
                    contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                    title: Text(displayTitle, style: const TextStyle(fontWeight: FontWeight.bold)),
                    subtitle: Text(displayCat),
                    trailing: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        IconButton(
                          icon: const Icon(Icons.edit, color: Colors.indigo, size: 20),
                          onPressed: () => _editProduct(index),
                        ),
                        IconButton(
                          icon: const Icon(Icons.delete_outline, color: Colors.red, size: 20),
                          onPressed: () => _removeProduct(index),
                        ),
                      ],
                    ),
                  ),
                );
              },
            ),
          ),
          
          // Footer
          Container(
            padding: const EdgeInsets.all(20),
            decoration: BoxDecoration(
              color: Colors.white,
              boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.05), blurRadius: 10, offset: const Offset(0, -5))],
            ),
            child: SizedBox(
              width: double.infinity,
              height: 50,
              child: ElevatedButton(
                onPressed: _editedProducts.isEmpty ? null : () => widget.onSaveAll(_editedProducts),
                style: ElevatedButton.styleFrom(
                  backgroundColor: Colors.indigo,
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                  elevation: 0,
                ),
                child: Text('Save All (${_editedProducts.length})', style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Colors.white)),
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _EditProductModal extends StatefulWidget {
  final Map<String, dynamic> product;
  final List<dynamic> customColumns;
  final String userId;
  final VoidCallback onSave;
  final VoidCallback? onDelete;
  final Function(Map<String, dynamic>)? onLocalSave;

  const _EditProductModal({
    required this.product,
    required this.customColumns,
    required this.userId,
    required this.onSave,
    this.onDelete,
    this.onLocalSave,
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
    
    // Find title col
    final titleCol = widget.customColumns.firstWhere((c) => c['is_title'] == 1 || c['is_title'] == true, orElse: () => null);
    String titleKey = titleCol != null ? titleCol['name'] : 'title';
    _titleCtrl.text = widget.product[titleKey] ?? widget.product['title'] ?? '';

    // Find category col
    final catCol = widget.customColumns.firstWhere((c) => c['is_category'] == 1 || c['is_category'] == true, orElse: () => null);
    String catKey = catCol != null ? catCol['name'] : 'category_name';
    _categoryCtrl.text = widget.product[catKey] ?? widget.product['category_name'] ?? '';

    _skuCtrl.text = widget.product['sku'] ?? '';
    _mrpCtrl.text = ((widget.product['mrp'] ?? 0) / 100).toStringAsFixed(2);
    _saleCtrl.text = ((widget.product['sale_price'] ?? 0) / 100).toStringAsFixed(2);

    for (var col in widget.customColumns) {
      final cv = (widget.product['custom_values'] as List?)?.firstWhere((v) => v['column_id'] == col['id'], orElse: () => null);
      String val = cv != null ? (cv['value']?.toString() ?? '') : (widget.product[col['name']]?.toString() ?? '');
      
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

    if (widget.onLocalSave != null) {
      widget.onLocalSave!(fields);
      return;
    }

    final bool isCreate = widget.product['id'] == null;
    final String endpoint = isCreate ? '/products/create' : '/products/${widget.product['id']}/update';

    try {
      final response = await ApiService.multipartPost(
        endpoint,
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
        widget.onDelete?.call();
      } else {
        if (mounted) ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Failed to delete')));
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
                if (!widget.customColumns.any((c) => c['is_unique'] == 1 || c['is_unique'] == true))
                  _buildTextField('Title (Fallback)', _titleCtrl),
                Row(
                  children: [
                    if (!widget.customColumns.any((c) => c['is_category'] == 1 || c['is_category'] == true)) ...[
                      Expanded(child: _buildTextField('Category', _categoryCtrl)),
                      const SizedBox(width: 12),
                    ],
                    Expanded(child: _buildTextField('SKU (Auto-generated)', _skuCtrl)),
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
                    bool isUnique = col['is_unique'] == 1 || col['is_unique'] == true;
                    bool hasOptions = col['options'] != null;
                    bool isSelectType = col['type'] == 'select' || col['type'] == 'multiselect';
                    
                    if (!isUnique && hasOptions && isSelectType) {
                      List<dynamic> opts = col['options'] is String ? jsonDecode(col['options']) : col['options'];
                      bool isMulti = col['is_combo'] == 1 || col['is_combo'] == true || col['type'] == 'multiselect';
                      return _buildSelectionField(
                        col['name'],
                        _customCtrls[col['id'].toString()]!,
                        opts.map((e) => e.toString()).toList(),
                        isMulti: isMulti,
                      );
                    }
                    
                    return _buildTextField(
                      col['name'] + (col['is_combo'] == true || col['is_combo'] == 1 ? ' (Comma separated)' : ''), 
                      _customCtrls[col['id'].toString()]!
                    );
                  }).toList();
                })(),
                
                const SizedBox(height: 300), // Keyboard padding
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildSelectionField(String label, TextEditingController ctrl, List<String> options, {bool isMulti = false}) {
    List<String> currentSelections = ctrl.text.split(',').map((e) => e.trim()).where((e) => e.isNotEmpty).toList();

    return Padding(
      padding: const EdgeInsets.only(bottom: 16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(label + (isMulti ? ' (Multiple)' : ''), style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Colors.black54)),
          const SizedBox(height: 8),
          InkWell(
            onTap: () {
              _showSelectionDialog(label, ctrl, options, isMulti);
            },
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: Colors.grey.shade200),
              ),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Text(currentSelections.isEmpty ? 'Select options...' : '${currentSelections.length} selected', style: TextStyle(color: currentSelections.isEmpty ? Colors.grey : Colors.black87)),
                  const Icon(Icons.arrow_drop_down, color: Colors.grey),
                ],
              ),
            ),
          ),
          if (currentSelections.isNotEmpty)
            Padding(
              padding: const EdgeInsets.only(top: 8),
              child: Wrap(
                spacing: 8,
                runSpacing: 8,
                children: currentSelections.map((opt) {
                  return InputChip(
                    label: Text(opt, style: const TextStyle(fontSize: 12, color: Colors.indigo)),
                    backgroundColor: Colors.indigo.shade50,
                    deleteIconColor: Colors.indigo,
                    deleteIcon: const Icon(Icons.close, size: 14),
                    onDeleted: () {
                      setState(() {
                        currentSelections.remove(opt);
                        ctrl.text = currentSelections.join(', ');
                      });
                    },
                  );
                }).toList(),
              ),
            ),
        ],
      ),
    );
  }

  void _showSelectionDialog(String label, TextEditingController ctrl, List<String> options, bool isMulti) {
    String query = '';
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (context) {
        return StatefulBuilder(
          builder: (context, setModalState) {
            List<String> filtered = options.where((o) => o.toLowerCase().contains(query.toLowerCase())).toList();
            List<String> currentSelections = ctrl.text.split(',').map((e) => e.trim()).where((e) => e.isNotEmpty).toList();

            return Container(
              height: MediaQuery.of(context).size.height * 0.7,
              decoration: const BoxDecoration(color: Color(0xFFF8FAFC), borderRadius: BorderRadius.vertical(top: Radius.circular(24))),
              padding: const EdgeInsets.only(top: 16),
              child: Column(
                children: [
                  Container(width: 40, height: 4, decoration: BoxDecoration(color: Colors.grey.shade300, borderRadius: BorderRadius.circular(2))),
                  const SizedBox(height: 16),
                  Text('Select $label', style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Colors.black87)),
                  Padding(
                    padding: const EdgeInsets.all(16),
                    child: TextField(
                      onChanged: (v) => setModalState(() => query = v),
                      decoration: InputDecoration(
                        hintText: 'Search...',
                        prefixIcon: const Icon(Icons.search),
                        filled: true,
                        fillColor: Colors.white,
                        border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide(color: Colors.grey.shade200)),
                      ),
                    ),
                  ),
                  Expanded(
                    child: ListView.builder(
                      itemCount: filtered.length,
                      itemBuilder: (context, index) {
                        final opt = filtered[index];
                        final isSelected = currentSelections.contains(opt);
                        return ListTile(
                          title: Text(opt),
                          trailing: isSelected ? const Icon(Icons.check_circle, color: Colors.indigo) : const Icon(Icons.circle_outlined, color: Colors.grey),
                          onTap: () {
                            setModalState(() {
                              if (isMulti) {
                                if (isSelected) currentSelections.remove(opt);
                                else currentSelections.add(opt);
                                ctrl.text = currentSelections.join(', ');
                              } else {
                                ctrl.text = opt;
                                Navigator.pop(context);
                              }
                            });
                            setState(() {}); // Update the main UI
                          },
                        );
                      },
                    ),
                  ),
                ],
              ),
            );
          },
        );
      },
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

class _ManageOptionsSheet extends StatefulWidget {
  final String userId;
  final VoidCallback onOptionsUpdated;
  final int? columnId;
  final String columnName;

  const _ManageOptionsSheet({Key? key, required this.userId, required this.onOptionsUpdated, this.columnId, required this.columnName}) : super(key: key);

  @override
  __ManageOptionsSheetState createState() => __ManageOptionsSheetState();
}

class __ManageOptionsSheetState extends State<_ManageOptionsSheet> {
  bool _isLoading = true;
  List<dynamic> _options = [];

  @override
  void initState() {
    super.initState();
    _loadOptions();
  }

  Future<void> _loadOptions() async {
    setState(() => _isLoading = true);
    try {
      final response = await ApiService.post('/products/categories/list', {
        'userId': widget.userId,
        if (widget.columnId != null) 'columnId': widget.columnId
      });
      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['success'] == true) {
          if (mounted) {
            setState(() {
              _options = data['categories'] ?? [];
              _isLoading = false;
            });
          }
          return;
        }
      }
    } catch (e) {
      // Ignore
    }
    if (mounted) setState(() => _isLoading = false);
  }

  void _addOption() {
    final ctrl = TextEditingController();
    showDialog(
      context: context,
      builder: (c) => AlertDialog(
        title: Text('Add ${widget.columnName}'),
        content: TextField(
          controller: ctrl,
          decoration: InputDecoration(hintText: 'New Option Name'),
          autofocus: true,
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(c), child: const Text('Cancel')),
          TextButton(
            onPressed: () async {
              if (ctrl.text.trim().isEmpty) return;
              Navigator.pop(c);
              setState(() => _isLoading = true);
              try {
                final res = await ApiService.post('/products/categories/add', {
                  'userId': widget.userId, 
                  'name': ctrl.text.trim(),
                  if (widget.columnId != null) 'columnId': widget.columnId
                });
                if (res.statusCode == 200 && jsonDecode(res.body)['success'] == true) {
                  widget.onOptionsUpdated();
                  _loadOptions();
                } else {
                  if (mounted) setState(() => _isLoading = false);
                  if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(jsonDecode(res.body)['message'] ?? 'Failed'), backgroundColor: Colors.red));
                }
              } catch (e) {
                if (mounted) setState(() => _isLoading = false);
              }
            },
            child: const Text('Add'),
          ),
        ],
      ),
    );
  }

  void _editOption(String oldName) {
    final ctrl = TextEditingController(text: oldName);
    showDialog(
      context: context,
      builder: (c) => AlertDialog(
        title: Text('Edit ${widget.columnName}'),
        content: TextField(
          controller: ctrl,
          decoration: InputDecoration(hintText: 'New Option Name'),
          autofocus: true,
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(c), child: const Text('Cancel')),
          TextButton(
            onPressed: () async {
              if (ctrl.text.trim().isEmpty || ctrl.text.trim() == oldName) return;
              Navigator.pop(c);
              setState(() => _isLoading = true);
              try {
                final res = await ApiService.post('/products/categories/update', {
                  'userId': widget.userId, 
                  'old_name': oldName, 
                  'new_name': ctrl.text.trim(),
                  if (widget.columnId != null) 'columnId': widget.columnId
                });
                if (res.statusCode == 200 && jsonDecode(res.body)['success'] == true) {
                  widget.onOptionsUpdated();
                  _loadOptions();
                } else {
                  if (mounted) setState(() => _isLoading = false);
                  if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(jsonDecode(res.body)['message'] ?? 'Failed'), backgroundColor: Colors.red));
                }
              } catch (e) {
                if (mounted) setState(() => _isLoading = false);
              }
            },
            child: const Text('Save'),
          ),
        ],
      ),
    );
  }

  void _deleteOption(String name) {
    showDialog(
      context: context,
      builder: (c) => AlertDialog(
        title: Text('Delete ${widget.columnName}'),
        content: Text('Are you sure you want to delete "$name"? Connected products will NOT be deleted, but will have their option removed.'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(c), child: const Text('Cancel')),
          TextButton(
            onPressed: () async {
              Navigator.pop(c);
              setState(() => _isLoading = true);
              try {
                final res = await ApiService.post('/products/categories/delete', {
                  'userId': widget.userId, 
                  'name': name,
                  if (widget.columnId != null) 'columnId': widget.columnId
                });
                if (res.statusCode == 200 && jsonDecode(res.body)['success'] == true) {
                  widget.onOptionsUpdated();
                  _loadOptions();
                } else {
                  if (mounted) setState(() => _isLoading = false);
                  if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(jsonDecode(res.body)['message'] ?? 'Failed'), backgroundColor: Colors.red));
                }
              } catch (e) {
                if (mounted) setState(() => _isLoading = false);
              }
            },
            child: const Text('Delete', style: TextStyle(color: Colors.red)),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: const BoxDecoration(
        color: Color(0xFFF8FAFC),
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      padding: const EdgeInsets.only(top: 16),
      height: MediaQuery.of(context).size.height * 0.7,
      child: Column(
        children: [
          Container(width: 40, height: 4, decoration: BoxDecoration(color: Colors.grey.shade300, borderRadius: BorderRadius.circular(2))),
          const SizedBox(height: 16),
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 20),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text('Manage ${widget.columnName}', style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Colors.black87)),
                IconButton(
                  icon: const Icon(Icons.add_circle, color: Colors.indigo, size: 28),
                  onPressed: _addOption,
                  padding: EdgeInsets.zero,
                  constraints: const BoxConstraints(),
                ),
              ],
            ),
          ),
          const Divider(),
          Expanded(
            child: _isLoading
                ? const Center(child: CircularProgressIndicator(color: Colors.indigo))
                : _options.isEmpty
                    ? Center(child: Text('No options found. Add one!', style: TextStyle(color: Colors.grey.shade600)))
                    : ListView.builder(
                        itemCount: _options.length,
                        itemBuilder: (context, index) {
                          final opt = _options[index];
                          return ListTile(
                            leading: CircleAvatar(
                              backgroundColor: Colors.indigo.shade50,
                              child: Text(opt['count'].toString(), style: const TextStyle(color: Colors.indigo, fontSize: 12, fontWeight: FontWeight.bold)),
                            ),
                            title: Text(opt['name'], style: const TextStyle(fontWeight: FontWeight.bold, color: Colors.black87)),
                            trailing: Row(
                              mainAxisSize: MainAxisSize.min,
                              children: [
                                IconButton(icon: const Icon(Icons.edit, color: Colors.blue, size: 20), onPressed: () => _editOption(opt['name'])),
                                IconButton(icon: const Icon(Icons.delete, color: Colors.red, size: 20), onPressed: () => _deleteOption(opt['name'])),
                              ],
                            ),
                          );
                        },
                      ),
          ),
        ],
      ),
    );
  }
}

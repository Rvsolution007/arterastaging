import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'dart:convert';
import 'package:shared_preferences/shared_preferences.dart';
import '../services/api_service.dart';
import '../utils/app_colors.dart';
import '../utils/app_text_styles.dart';
import '../utils/app_spacing.dart';

class CatalogueColumnsScreen extends StatefulWidget {
  const CatalogueColumnsScreen({super.key});

  @override
  State<CatalogueColumnsScreen> createState() => _CatalogueColumnsScreenState();
}

class _CatalogueColumnsScreenState extends State<CatalogueColumnsScreen> {
  bool _isLoading = true;
  List<Map<String, dynamic>> _columns = [];

  @override
  void initState() {
    super.initState();
    _fetchColumns();
  }

  Future<void> _fetchColumns() async {
    setState(() => _isLoading = true);
    final prefs = await SharedPreferences.getInstance();
    final userId = prefs.getString('userId') ?? '';

    if (userId.isEmpty) {
      setState(() => _isLoading = false);
      return;
    }

    try {
      final response = await ApiService.post('/catalogue-columns', {'userId': userId});
      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['success'] == true) {
          setState(() {
            _columns = List<Map<String, dynamic>>.from(data['columns']);
          });
        }
      }
    } catch (e) {
      debugPrint('Error fetching columns: $e');
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  Future<void> _reorderData(int oldIndex, int newIndex) async {
    if (newIndex > oldIndex) newIndex -= 1;
    
    setState(() {
      final item = _columns.removeAt(oldIndex);
      _columns.insert(newIndex, item);
    });

    final prefs = await SharedPreferences.getInstance();
    final userId = prefs.getString('userId') ?? '';
    
    final order = _columns.map((c) => c['id']).toList();
    ApiService.post('/catalogue-columns/reorder', {
      'userId': userId,
      'order': order,
    });
  }

  Future<void> _toggleVisibility(int index) async {
    final colId = _columns[index]['id'];
    setState(() {
      _columns[index]['is_active'] = !(_columns[index]['is_active'] == 1 || _columns[index]['is_active'] == true);
    });

    final prefs = await SharedPreferences.getInstance();
    final userId = prefs.getString('userId') ?? '';

    ApiService.post('/catalogue-columns/$colId/toggle', {
      'userId': userId,
    });
  }

  void _openColumnModal({Map<String, dynamic>? column, int? index}) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (context) => _ColumnEditModal(
        column: column,
        onSave: (updatedColumn) async {
          Navigator.pop(context);
          setState(() => _isLoading = true);
          final prefs = await SharedPreferences.getInstance();
          final userId = prefs.getString('userId') ?? '';
          
          updatedColumn['userId'] = userId;
          await ApiService.post('/catalogue-columns/update', updatedColumn);
          _fetchColumns();
        },
        onDelete: index != null ? () async {
          Navigator.pop(context);
          setState(() => _isLoading = true);
          final prefs = await SharedPreferences.getInstance();
          final userId = prefs.getString('userId') ?? '';
          
          await ApiService.post('/catalogue-columns/${_columns[index]['id']}/delete', {'userId': userId});
          _fetchColumns();
        } : null,
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        title: Text('Catalogue Columns', style: AppTextStyles.heading4),
        leading: IconButton(
          icon: Icon(Icons.arrow_back, color: AppColors.gray900),
          onPressed: () => Get.back(),
        ),
        actions: [
          IconButton(
            icon: Container(
              padding: const EdgeInsets.all(4),
              decoration: BoxDecoration(
                color: AppColors.indigo600,
                shape: BoxShape.circle,
                boxShadow: AppColors.primaryShadow,
              ),
              child: const Icon(Icons.add, color: Colors.white, size: 20),
            ),
            onPressed: () => _openColumnModal(),
          ),
          const SizedBox(width: 8),
        ],
        elevation: 0,
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : Column(
              children: [
                Container(
                  margin: const EdgeInsets.all(16),
                  padding: const EdgeInsets.all(16),
                  decoration: BoxDecoration(
                    color: AppColors.indigo50,
                    borderRadius: BorderRadius.circular(16),
                    border: Border.all(color: AppColors.indigo100),
                  ),
                  child: Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Icon(Icons.info_outline, color: AppColors.indigo500, size: 20),
                      AppSpacing.gapH12,
                      Expanded(
                        child: Text(
                          'Define the custom properties for your products. The AI Content Engine uses these fields to generate accurate daily posts.',
                          style: TextStyle(color: AppColors.indigo700, fontSize: 12, height: 1.5),
                        ),
                      ),
                    ],
                  ),
                ),
                Expanded(
                  child: _columns.isEmpty
                      ? _buildEmptyState()
                      : ReorderableListView.builder(
                          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                          itemCount: _columns.length,
                          onReorder: _reorderData,
                          buildDefaultDragHandles: false,
                          proxyDecorator: (child, index, animation) {
                            return Material(
                              color: Colors.transparent,
                              shadowColor: AppColors.indigo200,
                              elevation: 10,
                              child: child,
                            );
                          },
                          itemBuilder: (context, index) {
                            final col = _columns[index];
                            final isActive = col['is_active'] == 1 || col['is_active'] == true;
                            
                            return Opacity(
                              key: ValueKey(col['id']),
                              opacity: isActive ? 1.0 : 0.6,
                              child: Container(
                                margin: const EdgeInsets.only(bottom: 12),
                                decoration: BoxDecoration(
                                  color: Colors.white,
                                  borderRadius: BorderRadius.circular(16),
                                  border: Border.all(color: AppColors.slate100),
                                  boxShadow: [
                                    BoxShadow(
                                      color: Colors.black.withValues(alpha: 0.02),
                                      blurRadius: 4,
                                      offset: const Offset(0, 2),
                                    )
                                  ],
                                ),
                                child: ListTile(
                                  contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                                  leading: ReorderableDragStartListener(
                                    index: index,
                                    child: Icon(Icons.drag_indicator, color: AppColors.slate300),
                                  ),
                                  title: Wrap(
                                    crossAxisAlignment: WrapCrossAlignment.center,
                                    spacing: 8,
                                    runSpacing: 4,
                                    children: [
                                      Text(
                                        col['name'],
                                        style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 15),
                                      ),
                                      if (col['is_system'] == true)
                                        _buildBadge('System', AppColors.slate100, AppColors.slate500),
                                      if (col['is_category'] == true)
                                        _buildBadge('Category', AppColors.orange500.withValues(alpha: 0.1), AppColors.orange600),
                                      if (col['is_unique'] == true)
                                        _buildBadge('Unique', AppColors.indigo100, AppColors.indigo600),
                                      if (col['is_combo'] == true)
                                        _buildBadge('Combo', AppColors.purple500.withValues(alpha: 0.1), AppColors.purple600),
                                    ],
                                  ),
                                  subtitle: Text(
                                    'Type: ${col['type']}${col['options'] != null && (col['options'] is List) ? ' | Options: ${(col['options'] as List).length}' : ''}',
                                    style: TextStyle(fontSize: 12, color: AppColors.gray500),
                                  ),
                                  trailing: Switch.adaptive(
                                    value: isActive,
                                    onChanged: (v) => _toggleVisibility(index),
                                    activeColor: AppColors.indigo600,
                                  ),
                                  onTap: () => _openColumnModal(column: col, index: index),
                                ),
                              ),
                            );
                          },
                        ),
                ),
              ],
            ),
    );
  }

  Widget _buildBadge(String text, Color bgColor, Color textColor) {
    return Container(
      margin: const EdgeInsets.only(right: 4),
      padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
      decoration: BoxDecoration(
        color: bgColor,
        borderRadius: BorderRadius.circular(4),
      ),
      child: Text(
        text.toUpperCase(),
        style: TextStyle(fontSize: 9, fontWeight: FontWeight.w900, color: textColor),
      ),
    );
  }

  Widget _buildEmptyState() {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Container(
            width: 64, height: 64,
            decoration: BoxDecoration(shape: BoxShape.circle, color: AppColors.slate50),
            child: Icon(Icons.view_column_outlined, color: AppColors.slate300, size: 32),
          ),
          AppSpacing.gapV16,
          Text('No Custom Columns', style: AppTextStyles.heading4),
          AppSpacing.gapV4,
          Text('Use AI Setup Wizard to create them automatically.', style: AppTextStyles.bodySmall),
        ],
      ),
    );
  }
}

class _ColumnEditModal extends StatefulWidget {
  final Map<String, dynamic>? column;
  final Function(Map<String, dynamic>) onSave;
  final VoidCallback? onDelete;

  const _ColumnEditModal({this.column, required this.onSave, this.onDelete});

  @override
  State<_ColumnEditModal> createState() => _ColumnEditModalState();
}

class _ColumnEditModalState extends State<_ColumnEditModal> {
  late TextEditingController _nameCtrl;
  late TextEditingController _optionsCtrl;
  String _type = 'text';
  bool _isRequired = false;
  bool _showOnList = false;
  String _specialType = 'none';
  bool _isSystem = false;

  @override
  void initState() {
    super.initState();
    final col = widget.column;
    _nameCtrl = TextEditingController(text: col?['name'] ?? '');
    
    // Handle options if it's a string or list
    String optionsStr = '';
    if (col?['options'] != null) {
      if (col?['options'] is List) {
        optionsStr = (col?['options'] as List).join(', ');
      } else if (col?['options'] is String) {
        try {
          List parsed = jsonDecode(col?['options']);
          optionsStr = parsed.join(', ');
        } catch (_) {
          optionsStr = col?['options'];
        }
      }
    }
    _optionsCtrl = TextEditingController(text: optionsStr);

    _type = col?['type'] ?? 'text';
    _isRequired = col?['is_required'] == 1 || col?['is_required'] == true;
    _showOnList = col?['show_on_list'] == 1 || col?['show_on_list'] == true;
    _isSystem = col?['is_system'] == 1 || col?['is_system'] == true;
    
    if (col != null) {
      if (col['is_category'] == 1 || col['is_category'] == true) _specialType = 'category';
      else if (col['is_unique'] == 1 || col['is_unique'] == true) _specialType = 'unique';
      else if (col['is_combo'] == 1 || col['is_combo'] == true) _specialType = 'combo';
      else if (col['is_title'] == 1 || col['is_title'] == true) _specialType = 'title';
    }
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      height: MediaQuery.of(context).size.height * 0.85,
      decoration: const BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.vertical(top: Radius.circular(32)),
      ),
      child: Column(
        children: [
          // Header
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 16),
            decoration: BoxDecoration(border: Border(bottom: BorderSide(color: AppColors.gray100))),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text(widget.column == null ? 'Add Column' : 'Edit Column', style: AppTextStyles.heading3),
                GestureDetector(
                  onTap: () => Navigator.pop(context),
                  child: Container(
                    padding: const EdgeInsets.all(8),
                    decoration: BoxDecoration(shape: BoxShape.circle, color: AppColors.slate100),
                    child: Icon(Icons.close, color: AppColors.gray500, size: 16),
                  ),
                ),
              ],
            ),
          ),
          
          // Body
          Expanded(
            child: SingleChildScrollView(
              padding: const EdgeInsets.all(24),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  _buildLabel('Column Name *'),
                  _buildTextField(_nameCtrl),
                  AppSpacing.gapV16,
                  
                  _buildLabel('Input Type *'),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 16),
                    decoration: BoxDecoration(
                      color: AppColors.slate50,
                      borderRadius: BorderRadius.circular(16),
                      border: Border.all(color: AppColors.slate200),
                    ),
                    child: DropdownButtonHideUnderline(
                      child: DropdownButton<String>(
                        value: _type,
                        isExpanded: true,
                        onChanged: (v) {
                          setState(() { _type = v!; });
                        },
                        items: const [
                          DropdownMenuItem(value: 'text', child: Text('Short Text')),
                          DropdownMenuItem(value: 'textarea', child: Text('Long Text')),
                          DropdownMenuItem(value: 'number', child: Text('Number')),
                          DropdownMenuItem(value: 'select', child: Text('Dropdown Select')),
                          DropdownMenuItem(value: 'multiselect', child: Text('Multi-Select')),
                          DropdownMenuItem(value: 'boolean', child: Text('Yes/No Switch')),
                          DropdownMenuItem(value: 'image', child: Text('Product Image')),
                        ],
                      ),
                    ),
                  ),
                  
                  if (['select', 'multiselect'].contains(_type)) ...[
                    AppSpacing.gapV16,
                    _buildLabel('Options (Comma separated)'),
                    _buildTextField(_optionsCtrl, hint: 'e.g. Red, Blue, Green'),
                  ],
                  
                  AppSpacing.gapV24,
                  
                  Container(
                    padding: const EdgeInsets.all(16),
                    decoration: BoxDecoration(
                      color: AppColors.slate50,
                      borderRadius: BorderRadius.circular(24),
                      border: Border.all(color: AppColors.slate100),
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        SwitchListTile(
                          contentPadding: EdgeInsets.zero,
                          title: const Text('Required Field', style: TextStyle(fontSize: 14, fontWeight: FontWeight.w700)),
                          value: _isRequired,
                          onChanged: (v) => setState(() => _isRequired = v),
                          activeColor: AppColors.indigo600,
                        ),
                        SwitchListTile(
                          contentPadding: EdgeInsets.zero,
                          title: const Text('Show in List', style: TextStyle(fontSize: 14, fontWeight: FontWeight.w700)),
                          subtitle: const Text('Display column in main products view', style: TextStyle(fontSize: 10)),
                          value: _showOnList,
                          onChanged: (v) => setState(() => _showOnList = v),
                          activeColor: AppColors.indigo600,
                        ),
                        
                        const Divider(height: 32),
                        
                        const Text('SPECIAL TYPES', style: TextStyle(fontSize: 11, fontWeight: FontWeight.w800, color: AppColors.slate400, letterSpacing: 1.0)),
                        AppSpacing.gapV16,
                        
                        _buildSpecialTypeRadio('category', 'Category Identifier', 'Used to group products.', AppColors.orange500),
                        _buildSpecialTypeRadio('unique', 'Unique Identifier (SKU)', 'Prevents duplicate imports.', AppColors.indigo500),
                        _buildSpecialTypeRadio('combo', 'Combo / Variant', 'Generates product variations.', AppColors.purple500),
                        _buildSpecialTypeRadio('title', 'Display Title', 'Main display name.', AppColors.blue500),
                        _buildSpecialTypeRadio('none', 'Normal Regular Field', '', AppColors.gray400),
                      ],
                    ),
                  ),
                  
                  // Ignore overlay opacity for system columns for simplicity in Flutter right now
                ],
              ),
            ),
          ),
          
          // Bottom Actions
          Container(
            padding: const EdgeInsets.all(24),
            decoration: BoxDecoration(border: Border(top: BorderSide(color: AppColors.gray100))),
            child: Row(
              children: [
                if (widget.onDelete != null && !_isSystem) ...[
                  Expanded(
                    child: TextButton(
                      onPressed: () {
                        widget.onDelete!();
                        Navigator.pop(context);
                      },
                      style: TextButton.styleFrom(
                        backgroundColor: AppColors.red50,
                        padding: const EdgeInsets.symmetric(vertical: 16),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                      ),
                      child: Text('Delete', style: TextStyle(color: AppColors.red500, fontWeight: FontWeight.w700)),
                    ),
                  ),
                  AppSpacing.gapH12,
                ],
                Expanded(
                  flex: 2,
                  child: ElevatedButton(
                    onPressed: () {
                      final updated = {
                        ...widget.column ?? {},
                        'name': _nameCtrl.text,
                        'type': _type,
                        'options': ['select', 'multiselect'].contains(_type) 
                            ? _optionsCtrl.text.split(',').map((e) => e.trim()).where((e) => e.isNotEmpty).toList()
                            : null,
                        'is_required': _isRequired,
                        'show_on_list': _showOnList,
                        'is_category': _specialType == 'category',
                        'is_unique': _specialType == 'unique',
                        'is_combo': _specialType == 'combo',
                        'is_title': _specialType == 'title',
                        'is_active': widget.column != null ? (widget.column!['is_active'] == 1 || widget.column!['is_active'] == true) : true,
                        'is_system': _isSystem,
                      };
                      widget.onSave(updated);
                      Navigator.pop(context);
                    },
                    style: ElevatedButton.styleFrom(
                      backgroundColor: AppColors.indigo600,
                      padding: const EdgeInsets.symmetric(vertical: 16),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                    ),
                    child: Text('Save Column', style: AppTextStyles.buttonPrimary),
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildLabel(String text) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: Text(text, style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w700, color: AppColors.gray700)),
    );
  }

  Widget _buildTextField(TextEditingController controller, {String? hint}) {
    return Container(
      decoration: BoxDecoration(
        color: AppColors.slate50,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: AppColors.slate200),
      ),
      child: TextField(
        controller: controller,
        style: const TextStyle(fontSize: 14),
        decoration: InputDecoration(
          hintText: hint,
          border: InputBorder.none,
          contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
        ),
      ),
    );
  }

  Widget _buildSpecialTypeRadio(String value, String title, String subtitle, Color color) {
    return RadioListTile<String>(
      contentPadding: EdgeInsets.zero,
      activeColor: color,
      value: value,
      groupValue: _specialType,
      onChanged: (v) => setState(() => _specialType = v!),
      title: Text(title, style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w700)),
      subtitle: subtitle.isNotEmpty ? Text(subtitle, style: const TextStyle(fontSize: 10, color: AppColors.gray500)) : null,
    );
  }
}

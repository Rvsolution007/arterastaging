import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import '../services/api_service.dart';
import '../utils/app_colors.dart';
import '../controllers/home_controller.dart';
import '../widgets/multi_select_dropdown.dart';

class CascadingBusinessDropdowns extends StatefulWidget {
  final String? initialCategoryId;
  final List<String> initialSubCategoryIds;
  final List<String> initialBusinessTypeIds;
  final Function(String categoryId, List<String> subCategoryIds, List<String> businessTypeIds, bool hasTypes) onSelected;

  const CascadingBusinessDropdowns({
    super.key,
    this.initialCategoryId,
    this.initialSubCategoryIds = const [],
    this.initialBusinessTypeIds = const [],
    required this.onSelected,
  });

  @override
  State<CascadingBusinessDropdowns> createState() => _CascadingBusinessDropdownsState();
}

class _CascadingBusinessDropdownsState extends State<CascadingBusinessDropdowns>
    with TickerProviderStateMixin {
  String? _selectedCategory;
  List<String> _selectedSubCategoryIds = [];
  List<String> _selectedTypeIds = [];

  List<dynamic> _categories = [];
  List<dynamic> _subCategories = [];
  List<dynamic> _types = [];

  bool _isLoadingSub = false;
  bool _isLoadingType = false;
  bool _hasTypes = false;

  // Smooth expand/collapse animations
  late AnimationController _subCatAnimController;
  late AnimationController _typeAnimController;

  @override
  void initState() {
    super.initState();
    _subCatAnimController = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 350),
    );
    _typeAnimController = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 350),
    );
    _loadCategories();
  }

  @override
  void dispose() {
    _subCatAnimController.dispose();
    _typeAnimController.dispose();
    super.dispose();
  }

  void _loadCategories() {
    try {
      final hc = Get.find<HomeController>();
      setState(() {
        _categories = hc.profileCategories;
        if (_categories.isEmpty && hc.customCategories.isNotEmpty) {
          _categories = hc.customCategories;
        }
      });
      
      if (widget.initialCategoryId != null && widget.initialCategoryId!.isNotEmpty) {
        if (_categories.any((c) => (c['businessCategoryId']?.toString() ?? c['id']?.toString()) == widget.initialCategoryId)) {
          _selectedCategory = widget.initialCategoryId;
          _fetchSubCategories(_selectedCategory!, preloadSubIds: widget.initialSubCategoryIds);
        }
      }
    } catch (e) {
      debugPrint('Error loading categories: $e');
    }
  }

  Future<void> _fetchSubCategories(String categoryId, {List<String>? preloadSubIds}) async {
    setState(() {
      _isLoadingSub = true;
      _subCategories = [];
      _selectedSubCategoryIds = [];
      _types = [];
      _selectedTypeIds = [];
      _hasTypes = false;
    });
    _typeAnimController.reverse();

    try {
      final res = await ApiService.get('/business-sub-category?id=$categoryId');
      if (res.statusCode == 200) {
        final data = jsonDecode(res.body);
        if (mounted) {
          setState(() {
            _subCategories = data is List ? data : (data['data'] ?? []);
          });
        }

        if (preloadSubIds != null && preloadSubIds.isNotEmpty) {
          final validSubIds = _subCategories
              .map((s) => s['businessSubCategoryId']?.toString() ?? '')
              .where((id) => preloadSubIds.contains(id))
              .toList();
              
          if (validSubIds.isNotEmpty) {
            _selectedSubCategoryIds = validSubIds;
            _fetchBusinessTypes(_selectedSubCategoryIds, preloadTypeIds: widget.initialBusinessTypeIds);
          }
        }
      }
    } catch (e) {
      debugPrint('Error fetching subcategories: $e');
    } finally {
      if (mounted) {
        setState(() => _isLoadingSub = false);
        _subCatAnimController.forward();
      }
    }
  }

  Future<void> _fetchBusinessTypes(List<String> subCategoryIds, {List<String>? preloadTypeIds}) async {
    if (subCategoryIds.isEmpty) {
      setState(() {
        _types = [];
        _selectedTypeIds = [];
        _hasTypes = false;
      });
      _typeAnimController.reverse();
      _notifyParent();
      return;
    }

    // Check if any of the selected subcategories have types flag
    bool hasFlag = false;
    for (String subId in subCategoryIds) {
      final sub = _subCategories.firstWhere(
        (s) => s['businessSubCategoryId']?.toString() == subId,
        orElse: () => null,
      );
      if (sub != null && sub['hasBusinessType'] != null) {
        if (sub['hasBusinessType'] == true || sub['hasBusinessType'] == 1 || sub['hasBusinessType'] == '1') {
          hasFlag = true;
          break;
        }
      } else {
        hasFlag = true;
        break;
      }
    }

    setState(() {
      _isLoadingType = true;
      _types = [];
      _selectedTypeIds = [];
      _hasTypes = hasFlag;
    });

    if (!hasFlag) {
      if (mounted) setState(() => _isLoadingType = false);
      _notifyParent();
      return;
    }

    try {
      final res = await ApiService.get('/business-type?id=${subCategoryIds.join(',')}');
      if (res.statusCode == 200) {
        final data = jsonDecode(res.body);
        if (mounted) {
          setState(() {
            _types = data is List ? data : (data['data'] ?? []);
            _hasTypes = _types.isNotEmpty;
          });
        }

        if (preloadTypeIds != null && preloadTypeIds.isNotEmpty && _hasTypes) {
          final validTypes = _types
              .map((t) => t['businessTypeId']?.toString() ?? '')
              .where((id) => preloadTypeIds.contains(id))
              .toList();
          if (validTypes.isNotEmpty) {
            _selectedTypeIds = validTypes;
          }
        }
      } else {
        if (mounted) setState(() => _hasTypes = false);
      }
    } catch (e) {
      debugPrint('Error fetching types: $e');
      if (mounted) setState(() => _hasTypes = false);
    } finally {
      if (mounted) {
        setState(() => _isLoadingType = false);
        if (_hasTypes) {
          _typeAnimController.forward();
        } else {
          _typeAnimController.reverse();
        }
      }
      _notifyParent();
    }
  }

  void _notifyParent() {
    widget.onSelected(
      _selectedCategory ?? '',
      _selectedSubCategoryIds,
      _hasTypes ? _selectedTypeIds : [],
      _hasTypes,
    );
  }

  Future<List<Map<String, dynamic>>> _localFetchSubCategories(String query) async {
    final list = _subCategories.map((e) {
      return <String, dynamic>{
        'id': e['businessSubCategoryId']?.toString() ?? '',
        'name': e['businessSubCategoryName'] ?? '',
      };
    }).toList();
    
    if (query.isEmpty) return list;
    return list.where((item) {
      final name = item['name'].toString().toLowerCase();
      return name.contains(query.toLowerCase());
    }).toList();
  }

  Future<List<Map<String, dynamic>>> _localFetchBusinessTypes(String query) async {
    final list = _types.map((e) {
      return <String, dynamic>{
        'id': e['businessTypeId']?.toString() ?? '',
        'name': e['businessTypeName'] ?? '',
      };
    }).toList();
    
    if (query.isEmpty) return list;
    return list.where((item) {
      final name = item['name'].toString().toLowerCase();
      return name.contains(query.toLowerCase());
    }).toList();
  }

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        // ── CATEGORY (Single Select — Premium Styled Dropdown) ──
        _buildPremiumCategoryDropdown(),

        // ── SUB-CATEGORY (Multi-Select — Smooth Animated) ──
        AnimatedSize(
          duration: const Duration(milliseconds: 350),
          curve: Curves.easeOutCubic,
          alignment: Alignment.topCenter,
          child: (_selectedCategory != null)
              ? Column(
                  children: [
                    const SizedBox(height: 16),
                    if (_isLoadingSub)
                      _buildLoadingShimmer('Loading Sub-Categories...')
                    else if (_subCategories.isNotEmpty)
                      FadeTransition(
                        opacity: _subCatAnimController,
                        child: MultiSelectDropdown(
                          title: 'Business Sub-Category *',
                          initialSelectedIds: _selectedSubCategoryIds,
                          initialSelectedNames: _buildSubCatNameMap(),
                          fetchItems: _localFetchSubCategories,
                          onChanged: (ids) {
                            if (!mounted) return;
                            setState(() => _selectedSubCategoryIds = ids);
                            _fetchBusinessTypes(ids);
                            _notifyParent();
                          },
                        ),
                      ),
                  ],
                )
              : const SizedBox.shrink(),
        ),

        // ── BUSINESS TYPE (Multi-Select — Smooth Animated, only if hasTypes) ──
        AnimatedSize(
          duration: const Duration(milliseconds: 350),
          curve: Curves.easeOutCubic,
          alignment: Alignment.topCenter,
          child: (_selectedSubCategoryIds.isNotEmpty && (_isLoadingType || _hasTypes))
              ? Column(
                  children: [
                    const SizedBox(height: 16),
                    if (_isLoadingType)
                      _buildLoadingShimmer('Loading Business Types...')
                    else if (_types.isNotEmpty)
                      FadeTransition(
                        opacity: _typeAnimController,
                        child: MultiSelectDropdown(
                          title: 'Business Type *',
                          initialSelectedIds: _selectedTypeIds,
                          initialSelectedNames: _buildTypeNameMap(),
                          fetchItems: _localFetchBusinessTypes,
                          onChanged: (ids) {
                            if (!mounted) return;
                            setState(() => _selectedTypeIds = ids);
                            _notifyParent();
                          },
                        ),
                      ),
                  ],
                )
              : const SizedBox.shrink(),
        ),
      ],
    );
  }

  // ── Helper: Build name maps for initial display ──
  Map<String, String> _buildSubCatNameMap() {
    final map = <String, String>{};
    for (var s in _subCategories) {
      final id = s['businessSubCategoryId']?.toString() ?? '';
      if (_selectedSubCategoryIds.contains(id)) {
        map[id] = s['businessSubCategoryName']?.toString() ?? '';
      }
    }
    return map;
  }

  Map<String, String> _buildTypeNameMap() {
    final map = <String, String>{};
    for (var t in _types) {
      final id = t['businessTypeId']?.toString() ?? '';
      if (_selectedTypeIds.contains(id)) {
        map[id] = t['businessTypeName']?.toString() ?? '';
      }
    }
    return map;
  }

  // ── Premium Category Dropdown (Single Select) ──
  Widget _buildPremiumCategoryDropdown() {
    String? safeValue = _selectedCategory;
    if (safeValue != null && _categories.isNotEmpty) {
      final exists = _categories.any((c) =>
          (c['businessCategoryId']?.toString() ?? c['id']?.toString()) == safeValue);
      if (!exists) safeValue = null;
    }

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        // Label
        Padding(
          padding: const EdgeInsets.only(left: 4, bottom: 8),
          child: Row(
            children: [
              Container(
                padding: const EdgeInsets.all(6),
                decoration: BoxDecoration(
                  color: AppColors.indigo600.withOpacity(0.1),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Icon(Icons.category_rounded, size: 16, color: AppColors.indigo600),
              ),
              const SizedBox(width: 8),
              const Text(
                'Business Category *',
                style: TextStyle(
                  fontSize: 13,
                  fontWeight: FontWeight.w600,
                  color: AppColors.textPrimary,
                  letterSpacing: -0.2,
                ),
              ),
            ],
          ),
        ),
        // Dropdown Container
        Container(
          padding: const EdgeInsets.symmetric(horizontal: 14),
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(14),
            border: Border.all(
              color: _selectedCategory != null
                  ? AppColors.indigo600.withOpacity(0.3)
                  : AppColors.gray200,
              width: _selectedCategory != null ? 1.5 : 1.0,
            ),
            boxShadow: [
              BoxShadow(
                color: _selectedCategory != null
                    ? AppColors.indigo600.withOpacity(0.06)
                    : Colors.black.withOpacity(0.03),
                blurRadius: _selectedCategory != null ? 12 : 6,
                offset: const Offset(0, 3),
              ),
            ],
          ),
          child: DropdownButtonHideUnderline(
            child: DropdownButton<String>(
              isExpanded: true,
              value: safeValue,
              icon: Container(
                padding: const EdgeInsets.all(4),
                decoration: BoxDecoration(
                  color: AppColors.gray100,
                  shape: BoxShape.circle,
                ),
                child: Icon(Icons.keyboard_arrow_down_rounded, size: 18, color: AppColors.gray600),
              ),
              hint: Row(
                children: [
                  Icon(Icons.add_circle_outline_rounded, size: 18, color: AppColors.gray400),
                  const SizedBox(width: 10),
                  Text(
                    'Select Category',
                    style: TextStyle(color: AppColors.gray400, fontSize: 14, fontWeight: FontWeight.w400),
                  ),
                ],
              ),
              selectedItemBuilder: (context) {
                return _categories.map((c) {
                  final name = c['businessCategoryName'] ?? c['name'] ?? '';
                  return Align(
                    alignment: Alignment.centerLeft,
                    child: Text(
                      name,
                      style: const TextStyle(
                        fontSize: 14,
                        fontWeight: FontWeight.w500,
                        color: AppColors.textPrimary,
                      ),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                  );
                }).toList();
              },
              items: _categories.map((c) {
                final id = c['businessCategoryId']?.toString() ?? c['id']?.toString() ?? '';
                final name = c['businessCategoryName'] ?? c['name'] ?? '';
                return DropdownMenuItem(
                  value: id,
                  child: Text(name, style: const TextStyle(fontSize: 14)),
                );
              }).toList(),
              onChanged: (val) {
                if (val != null && val != _selectedCategory) {
                  setState(() => _selectedCategory = val);
                  _fetchSubCategories(val);
                  _notifyParent();
                }
              },
            ),
          ),
        ),
      ],
    );
  }

  // ── Loading Shimmer ──
  Widget _buildLoadingShimmer(String label) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
      decoration: BoxDecoration(
        color: AppColors.gray50,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: AppColors.gray200),
      ),
      child: Row(
        children: [
          SizedBox(
            width: 18,
            height: 18,
            child: CircularProgressIndicator(
              strokeWidth: 2,
              color: AppColors.indigo600,
            ),
          ),
          const SizedBox(width: 12),
          Text(
            label,
            style: TextStyle(
              fontSize: 13,
              color: AppColors.gray500,
              fontWeight: FontWeight.w400,
            ),
          ),
        ],
      ),
    );
  }
}

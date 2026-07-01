import 'dart:async';
import 'package:flutter/material.dart';
import '../utils/app_colors.dart';

class MultiSelectDropdown extends StatefulWidget {
  final String title;
  final List<String> initialSelectedIds;
  final Future<List<Map<String, dynamic>>> Function(String query) fetchItems;
  final void Function(List<String> selectedIds) onChanged;
  final String idKey;
  final String nameKey;
  final bool isSingleSelect;
  final String? initialSingleName;
  final Map<String, String>? initialSelectedNames;

  const MultiSelectDropdown({
    super.key,
    required this.title,
    required this.initialSelectedIds,
    required this.fetchItems,
    required this.onChanged,
    this.idKey = 'id',
    this.nameKey = 'name',
    this.isSingleSelect = false,
    this.initialSingleName,
    this.initialSelectedNames,
  });

  @override
  State<MultiSelectDropdown> createState() => _MultiSelectDropdownState();
}

class _MultiSelectDropdownState extends State<MultiSelectDropdown>
    with SingleTickerProviderStateMixin {
  List<String> _selectedIds = [];
  Map<String, String> _selectedNames = {};
  late AnimationController _animController;
  late Animation<double> _scaleAnim;

  @override
  void initState() {
    super.initState();
    _selectedIds = List.from(widget.initialSelectedIds);
    if (widget.initialSelectedNames != null) {
      _selectedNames = Map.from(widget.initialSelectedNames!);
    }
    if (widget.isSingleSelect &&
        widget.initialSingleName != null &&
        _selectedIds.isNotEmpty) {
      _selectedNames[_selectedIds.first] = widget.initialSingleName!;
    }
    _animController = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 150),
    );
    _scaleAnim = Tween<double>(begin: 1.0, end: 0.97).animate(
      CurvedAnimation(parent: _animController, curve: Curves.easeInOut),
    );
  }

  @override
  void dispose() {
    _animController.dispose();
    super.dispose();
  }

  void _showSelectionSheet() {
    _animController.forward().then((_) => _animController.reverse());
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      transitionAnimationController: AnimationController(
        vsync: Navigator.of(context),
        duration: const Duration(milliseconds: 400),
      ),
      builder: (context) => _PremiumSelectionSheet(
        title: widget.title,
        fetchItems: widget.fetchItems,
        initialSelectedIds: _selectedIds,
        idKey: widget.idKey,
        nameKey: widget.nameKey,
        isSingleSelect: widget.isSingleSelect,
        onSelectionDone: (selectedIds, selectedNames) {
          setState(() {
            _selectedIds = selectedIds;
            _selectedNames.addAll(selectedNames);
            _selectedNames
                .removeWhere((key, value) => !_selectedIds.contains(key));
          });
          widget.onChanged(_selectedIds);
        },
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final bool hasSelection = _selectedIds.isNotEmpty;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Padding(
          padding: const EdgeInsets.only(left: 4, bottom: 8),
          child: Row(
            children: [
              Text(
                widget.title,
                style: const TextStyle(
                  fontSize: 13,
                  fontWeight: FontWeight.w600,
                  color: AppColors.gray600,
                  letterSpacing: 0.3,
                ),
              ),
              if (hasSelection) ...[
                const SizedBox(width: 8),
                Container(
                  padding:
                      const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                  decoration: BoxDecoration(
                    color: AppColors.indigo600.withOpacity(0.1),
                    borderRadius: BorderRadius.circular(10),
                  ),
                  child: Text(
                    '${_selectedIds.length}',
                    style: TextStyle(
                      fontSize: 11,
                      fontWeight: FontWeight.w700,
                      color: AppColors.indigo600,
                    ),
                  ),
                ),
              ],
            ],
          ),
        ),
        ScaleTransition(
          scale: _scaleAnim,
          child: GestureDetector(
            onTap: _showSelectionSheet,
            child: AnimatedContainer(
              duration: const Duration(milliseconds: 250),
              curve: Curves.easeOut,
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(16),
                border: Border.all(
                  color: hasSelection
                      ? AppColors.indigo600.withOpacity(0.3)
                      : AppColors.gray200,
                  width: hasSelection ? 1.5 : 1.0,
                ),
                boxShadow: [
                  BoxShadow(
                    color: hasSelection
                        ? AppColors.indigo600.withOpacity(0.06)
                        : Colors.black.withOpacity(0.03),
                    blurRadius: hasSelection ? 12 : 6,
                    offset: const Offset(0, 3),
                  ),
                ],
              ),
              child: Row(
                children: [
                  Expanded(
                    child: !hasSelection
                        ? Row(
                            children: [
                              Icon(Icons.add_circle_outline_rounded,
                                  size: 18, color: AppColors.gray400),
                              const SizedBox(width: 10),
                              Flexible(
                                child: Text(
                                  'Select ${widget.title}',
                                  style: TextStyle(
                                    color: AppColors.gray400,
                                    fontSize: 14,
                                    fontWeight: FontWeight.w400,
                                  ),
                                  maxLines: 1,
                                  overflow: TextOverflow.ellipsis,
                                ),
                              ),
                            ],
                          )
                        : widget.isSingleSelect
                            ? Text(
                                _selectedNames[_selectedIds.first] ??
                                    widget.initialSingleName ??
                                    _selectedIds.first,
                                style: const TextStyle(
                                  fontSize: 14,
                                  fontWeight: FontWeight.w500,
                                  color: AppColors.textPrimary,
                                ),
                                maxLines: 1,
                                overflow: TextOverflow.ellipsis,
                              )
                            : Wrap(
                                spacing: 6,
                                runSpacing: 6,
                                children: _selectedIds.map((id) {
                                  String name = _selectedNames[id] ?? id;
                                  return Container(
                                    padding: const EdgeInsets.symmetric(
                                        horizontal: 10, vertical: 5),
                                    decoration: BoxDecoration(
                                      gradient: LinearGradient(
                                        colors: [
                                          AppColors.indigo600.withOpacity(0.08),
                                          AppColors.indigo600.withOpacity(0.04),
                                        ],
                                      ),
                                      borderRadius: BorderRadius.circular(20),
                                      border: Border.all(
                                        color:
                                            AppColors.indigo600.withOpacity(0.15),
                                      ),
                                    ),
                                    child: Row(
                                      mainAxisSize: MainAxisSize.min,
                                      children: [
                                        Flexible(
                                          child: Text(
                                            name,
                                            style: TextStyle(
                                              fontSize: 12,
                                              fontWeight: FontWeight.w500,
                                              color: AppColors.indigo600,
                                            ),
                                            maxLines: 1,
                                            overflow: TextOverflow.ellipsis,
                                          ),
                                        ),
                                        const SizedBox(width: 4),
                                        GestureDetector(
                                          onTap: () {
                                            setState(() {
                                              _selectedIds.remove(id);
                                              _selectedNames.remove(id);
                                            });
                                            widget.onChanged(_selectedIds);
                                          },
                                          child: Icon(
                                            Icons.close_rounded,
                                            size: 14,
                                            color: AppColors.indigo600
                                                .withOpacity(0.6),
                                          ),
                                        ),
                                      ],
                                    ),
                                  );
                                }).toList(),
                              ),
                  ),
                  const SizedBox(width: 8),
                  AnimatedRotation(
                    turns: 0,
                    duration: const Duration(milliseconds: 300),
                    child: Container(
                      padding: const EdgeInsets.all(4),
                      decoration: BoxDecoration(
                        color: AppColors.gray100,
                        shape: BoxShape.circle,
                      ),
                      child: Icon(
                        Icons.keyboard_arrow_down_rounded,
                        size: 18,
                        color: AppColors.gray600,
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ),
        ),
      ],
    );
  }
}

// ─── Premium Selection Bottom Sheet ──────────────────────────────────────────

class _PremiumSelectionSheet extends StatefulWidget {
  final String title;
  final Future<List<Map<String, dynamic>>> Function(String query) fetchItems;
  final List<String> initialSelectedIds;
  final String idKey;
  final String nameKey;
  final bool isSingleSelect;
  final void Function(List<String> ids, Map<String, String> names)
      onSelectionDone;

  const _PremiumSelectionSheet({
    required this.title,
    required this.fetchItems,
    required this.initialSelectedIds,
    required this.idKey,
    required this.nameKey,
    required this.isSingleSelect,
    required this.onSelectionDone,
  });

  @override
  State<_PremiumSelectionSheet> createState() => _PremiumSelectionSheetState();
}

class _PremiumSelectionSheetState extends State<_PremiumSelectionSheet>
    with SingleTickerProviderStateMixin {
  List<Map<String, dynamic>> _items = [];
  List<String> _selectedIds = [];
  Map<String, String> _selectedNames = {};
  bool _isLoading = false;
  Timer? _debounce;
  final TextEditingController _searchCtrl = TextEditingController();
  final FocusNode _searchFocus = FocusNode();

  late AnimationController _listAnimController;

  @override
  void initState() {
    super.initState();
    _selectedIds = List.from(widget.initialSelectedIds);
    _listAnimController = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 500),
    );
    _loadItems('');
  }

  @override
  void dispose() {
    _debounce?.cancel();
    _searchCtrl.dispose();
    _searchFocus.dispose();
    _listAnimController.dispose();
    super.dispose();
  }

  void _loadItems(String query) async {
    setState(() => _isLoading = true);
    try {
      final items = await widget.fetchItems(query);
      if (mounted) {
        setState(() {
          _items = items;
          _isLoading = false;
        });
        _listAnimController.reset();
        _listAnimController.forward();
      }
    } catch (e) {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  void _onSearchChanged(String query) {
    if (_debounce?.isActive ?? false) _debounce!.cancel();
    _debounce = Timer(const Duration(milliseconds: 400), () {
      _loadItems(query);
    });
  }

  @override
  Widget build(BuildContext context) {
    final bottomPadding = MediaQuery.of(context).viewInsets.bottom;

    return Container(
      height: MediaQuery.of(context).size.height * 0.72,
      decoration: const BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.only(
          topLeft: Radius.circular(28),
          topRight: Radius.circular(28),
        ),
      ),
      child: Column(
        children: [
          // ── Handle Bar ──
          Center(
            child: Container(
              margin: const EdgeInsets.only(top: 12),
              width: 40,
              height: 4,
              decoration: BoxDecoration(
                color: AppColors.gray300,
                borderRadius: BorderRadius.circular(2),
              ),
            ),
          ),

          // ── Header ──
          Padding(
            padding: const EdgeInsets.fromLTRB(20, 16, 12, 0),
            child: Row(
              children: [
                Container(
                  padding: const EdgeInsets.all(8),
                  decoration: BoxDecoration(
                    color: AppColors.indigo600.withOpacity(0.1),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Icon(Icons.checklist_rounded,
                      color: AppColors.indigo600, size: 20),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        'Select ${widget.title}',
                        style: const TextStyle(
                          fontSize: 18,
                          fontWeight: FontWeight.w700,
                          color: AppColors.textPrimary,
                          letterSpacing: -0.3,
                        ),
                      ),
                      if (!widget.isSingleSelect)
                        Text(
                          '${_selectedIds.length} selected',
                          style: TextStyle(
                            fontSize: 12,
                            color: AppColors.gray500,
                            fontWeight: FontWeight.w400,
                          ),
                        ),
                    ],
                  ),
                ),
                TextButton(
                  onPressed: () {
                    widget.onSelectionDone(_selectedIds, _selectedNames);
                    Navigator.pop(context);
                  },
                  style: TextButton.styleFrom(
                    backgroundColor: AppColors.indigo600,
                    foregroundColor: Colors.white,
                    padding: const EdgeInsets.symmetric(
                        horizontal: 20, vertical: 10),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(12),
                    ),
                  ),
                  child: const Text('Done',
                      style:
                          TextStyle(fontWeight: FontWeight.w600, fontSize: 14)),
                ),
              ],
            ),
          ),

          const SizedBox(height: 16),

          // ── Search Bar ──
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 20),
            child: Container(
              decoration: BoxDecoration(
                color: AppColors.gray50,
                borderRadius: BorderRadius.circular(14),
                border: Border.all(color: AppColors.gray200),
              ),
              child: TextField(
                controller: _searchCtrl,
                focusNode: _searchFocus,
                onChanged: _onSearchChanged,
                style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w400),
                decoration: InputDecoration(
                  hintText: 'Search ${widget.title.toLowerCase()}...',
                  hintStyle: TextStyle(
                    color: AppColors.gray400,
                    fontSize: 14,
                    fontWeight: FontWeight.w400,
                  ),
                  prefixIcon: Icon(Icons.search_rounded,
                      color: AppColors.gray400, size: 20),
                  suffixIcon: _searchCtrl.text.isNotEmpty
                      ? GestureDetector(
                          onTap: () {
                            _searchCtrl.clear();
                            _onSearchChanged('');
                            setState(() {});
                          },
                          child: Icon(Icons.close_rounded,
                              color: AppColors.gray400, size: 18),
                        )
                      : null,
                  border: InputBorder.none,
                  contentPadding:
                      const EdgeInsets.symmetric(horizontal: 0, vertical: 12),
                ),
              ),
            ),
          ),

          const SizedBox(height: 12),

          // ── List ──
          Expanded(
            child: _isLoading
                ? Center(
                    child: Column(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        SizedBox(
                          width: 32,
                          height: 32,
                          child: CircularProgressIndicator(
                            strokeWidth: 2.5,
                            color: AppColors.indigo600,
                          ),
                        ),
                        const SizedBox(height: 12),
                        Text(
                          'Loading...',
                          style: TextStyle(
                            color: AppColors.gray400,
                            fontSize: 13,
                          ),
                        ),
                      ],
                    ),
                  )
                : _items.isEmpty
                    ? Center(
                        child: Column(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            Icon(Icons.search_off_rounded,
                                size: 48, color: AppColors.gray300),
                            const SizedBox(height: 12),
                            Text(
                              'No items found',
                              style: TextStyle(
                                color: AppColors.gray400,
                                fontSize: 14,
                                fontWeight: FontWeight.w500,
                              ),
                            ),
                          ],
                        ),
                      )
                    : ListView.builder(
                        padding: EdgeInsets.only(
                          left: 12,
                          right: 12,
                          bottom: bottomPadding + 20,
                        ),
                        itemCount: _items.length,
                        itemBuilder: (context, index) {
                          final item = _items[index];
                          final String id =
                              item[widget.idKey]?.toString() ?? '';
                          final String name =
                              item[widget.nameKey]?.toString() ?? '';
                          final bool isSelected = _selectedIds.contains(id);

                          return _AnimatedListItem(
                            index: index,
                            animController: _listAnimController,
                            child: GestureDetector(
                              onTap: () {
                                setState(() {
                                  if (widget.isSingleSelect) {
                                    _selectedIds = [id];
                                    _selectedNames = {id: name};
                                    widget.onSelectionDone(
                                        _selectedIds, _selectedNames);
                                    Navigator.pop(context);
                                    return;
                                  }
                                  if (isSelected) {
                                    _selectedIds.remove(id);
                                    _selectedNames.remove(id);
                                  } else {
                                    _selectedIds.add(id);
                                    _selectedNames[id] = name;
                                  }
                                });
                              },
                              child: AnimatedContainer(
                                duration: const Duration(milliseconds: 200),
                                curve: Curves.easeOut,
                                margin: const EdgeInsets.symmetric(
                                    vertical: 3, horizontal: 4),
                                padding: const EdgeInsets.symmetric(
                                    horizontal: 16, vertical: 14),
                                decoration: BoxDecoration(
                                  color: isSelected
                                      ? AppColors.indigo600.withOpacity(0.06)
                                      : Colors.white,
                                  borderRadius: BorderRadius.circular(14),
                                  border: Border.all(
                                    color: isSelected
                                        ? AppColors.indigo600.withOpacity(0.25)
                                        : AppColors.gray100,
                                    width: isSelected ? 1.5 : 1.0,
                                  ),
                                  boxShadow: isSelected
                                      ? [
                                          BoxShadow(
                                            color: AppColors.indigo600
                                                .withOpacity(0.08),
                                            blurRadius: 8,
                                            offset: const Offset(0, 2),
                                          ),
                                        ]
                                      : [],
                                ),
                                child: Row(
                                  children: [
                                    Expanded(
                                      child: Text(
                                        name,
                                        style: TextStyle(
                                          fontSize: 14,
                                          fontWeight: isSelected
                                              ? FontWeight.w600
                                              : FontWeight.w400,
                                          color: isSelected
                                              ? AppColors.indigo600
                                              : AppColors.textPrimary,
                                        ),
                                      ),
                                    ),
                                    AnimatedSwitcher(
                                      duration:
                                          const Duration(milliseconds: 200),
                                      transitionBuilder: (child, anim) =>
                                          ScaleTransition(
                                              scale: anim, child: child),
                                      child: isSelected
                                          ? Container(
                                              key: const ValueKey('checked'),
                                              padding: const EdgeInsets.all(2),
                                              decoration: BoxDecoration(
                                                color: AppColors.indigo600,
                                                shape: BoxShape.circle,
                                              ),
                                              child: const Icon(
                                                Icons.check_rounded,
                                                size: 16,
                                                color: Colors.white,
                                              ),
                                            )
                                          : Container(
                                              key: const ValueKey('unchecked'),
                                              width: 22,
                                              height: 22,
                                              decoration: BoxDecoration(
                                                shape: BoxShape.circle,
                                                border: Border.all(
                                                  color: AppColors.gray300,
                                                  width: 1.5,
                                                ),
                                              ),
                                            ),
                                    ),
                                  ],
                                ),
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
}

// ─── Animated List Item ──────────────────────────────────────────────────────

class _AnimatedListItem extends StatelessWidget {
  final int index;
  final AnimationController animController;
  final Widget child;

  const _AnimatedListItem({
    required this.index,
    required this.animController,
    required this.child,
  });

  @override
  Widget build(BuildContext context) {
    final start = (index * 0.05).clamp(0.0, 0.6);
    final end = (start + 0.4).clamp(0.0, 1.0);

    final slideAnim = Tween<Offset>(
      begin: const Offset(0, 0.15),
      end: Offset.zero,
    ).animate(CurvedAnimation(
      parent: animController,
      curve: Interval(start, end, curve: Curves.easeOutCubic),
    ));

    final fadeAnim = Tween<double>(begin: 0.0, end: 1.0).animate(
      CurvedAnimation(
        parent: animController,
        curve: Interval(start, end, curve: Curves.easeOut),
      ),
    );

    return SlideTransition(
      position: slideAnim,
      child: FadeTransition(
        opacity: fadeAnim,
        child: child,
      ),
    );
  }
}

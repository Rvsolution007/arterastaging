import 'dart:async';
import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../controllers/home_controller.dart';
import '../features/ai_editable_v1/screens/ai_editable_editor_screen.dart';
import '../services/api_service.dart';
import 'business_profile_screen.dart';

/// Custom Post's standalone Business AI journey. It does not create or load a
/// frame; the generated design opens the isolated editable-document editor.
class BusinessAiPostScreen extends StatefulWidget {
  const BusinessAiPostScreen({super.key});

  @override
  State<BusinessAiPostScreen> createState() => _BusinessAiPostScreenState();
}

class _BusinessAiPostScreenState extends State<BusinessAiPostScreen> {
  final _visualInstruction = TextEditingController();
  final Map<String, TextEditingController> _brief = {};
  final List<_CustomDetail> _customDetails = [];
  final Set<int> _productIds = <int>{};

  Timer? _poller;
  bool _loading = true;
  bool _submitting = false;
  String? _error;
  int _step = 0;
  Map<String, dynamic>? _purpose;
  Map<String, dynamic>? _style;
  Map<String, dynamic>? _model;
  String? _quality;
  String? _sizeKey;
  Map<String, dynamic>? _quota;
  int _generationCost = 1;
  List<dynamic> _purposes = [];
  List<dynamic> _styles = [];
  List<dynamic> _models = [];
  List<dynamic> _languages = [];
  List<dynamic> _products = [];
  Map<String, dynamic>? _job;

  @override
  void initState() {
    super.initState();
    _loadOptions();
  }

  @override
  void dispose() {
    _poller?.cancel();
    _visualInstruction.dispose();
    for (final controller in _brief.values) {
      controller.dispose();
    }
    for (final item in _customDetails) {
      item.dispose();
    }
    super.dispose();
  }

  Future<void> _loadOptions() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final response = await ApiService.get('/business-ai/options');
      final data = jsonDecode(response.body);
      if (response.statusCode != 200 || data is! Map || data['success'] != true) {
        throw Exception(data is Map ? data['message'] : 'Business Post AI could not be loaded.');
      }
      final models = List<dynamic>.from(data['models'] ?? []);
      Map<String, dynamic>? model;
      String? quality;
      for (final raw in models) {
        final candidate = Map<String, dynamic>.from(raw as Map);
        final variants = List<dynamic>.from(candidate['quality_variants'] ?? []);
        final available = variants.cast<Map?>().firstWhere(
          (item) => item?['is_available'] == true,
          orElse: () => null,
        );
        if (available != null) {
          model = candidate;
          quality = available['key']?.toString();
          break;
        }
      }
      setState(() {
        _quota = Map<String, dynamic>.from(data['quota'] ?? {});
        _generationCost = int.tryParse('${data['generation_cost'] ?? 1}') ?? 1;
        _purposes = List<dynamic>.from(data['purposes'] ?? []);
        _styles = List<dynamic>.from(data['styles'] ?? []);
        _models = models;
        _languages = List<dynamic>.from(data['languages'] ?? []);
        _purpose = _purposes.isEmpty ? null : Map<String, dynamic>.from(_purposes.first as Map);
        _style = _styles.isEmpty ? null : Map<String, dynamic>.from(_styles.first as Map);
        _model = model;
        _quality = quality;
        _sizeKey = _availableSizes.isEmpty ? null : _availableSizes.first['key']?.toString();
        _syncBriefFields();
        _loading = false;
      });
    } catch (error) {
      if (!mounted) return;
      setState(() {
        _loading = false;
        _error = error.toString().replaceFirst('Exception: ', '');
      });
    }
  }

  List<Map<String, dynamic>> get _availableSizes => List<dynamic>.from(
    _model?['sizes'] ?? [],
  ).map((item) => Map<String, dynamic>.from(item as Map)).toList();

  List<Map<String, dynamic>> get _purposeFields => List<dynamic>.from(
    _purpose?['fields'] ?? [],
  ).map((item) => Map<String, dynamic>.from(item as Map)).toList();

  int get _remaining => int.tryParse('${_quota?['remaining'] ?? 0}') ?? 0;
  bool get _canGenerate =>
      _purpose != null && _style != null && _model != null && _quality != null && _sizeKey != null;

  void _syncBriefFields() {
    final validKeys = _purposeFields.map((item) => item['key'].toString()).toSet();
    for (final key in _brief.keys.where((key) => !validKeys.contains(key)).toList()) {
      _brief.remove(key)?.dispose();
    }
    for (final field in _purposeFields) {
      _brief.putIfAbsent(field['key'].toString(), TextEditingController.new);
    }
  }

  void _selectPurpose(Map<String, dynamic> purpose) {
    setState(() {
      _purpose = purpose;
      _syncBriefFields();
      _step = 1;
    });
  }

  Future<void> _loadProducts() async {
    if (_products.isNotEmpty) return;
    final prefs = await SharedPreferences.getInstance();
    final userId = prefs.getString('userId') ?? '';
    final response = await ApiService.post('/products/list', {'userId': userId});
    final data = jsonDecode(response.body);
    if (response.statusCode != 200 || data is! Map || data['success'] != true) {
      throw Exception(data is Map ? data['message'] : 'Products could not be loaded.');
    }
    _products = List<dynamic>.from(data['products']?['data'] ?? []);
  }

  Future<void> _chooseProducts() async {
    try {
      await _loadProducts();
      if (!mounted) return;
      final selected = Set<int>.from(_productIds);
      await showModalBottomSheet<void>(
        context: context,
        isScrollControlled: true,
        builder: (sheetContext) => StatefulBuilder(
          builder: (_, setSheetState) => SafeArea(
            child: SizedBox(
              height: MediaQuery.of(context).size.height * .72,
              child: Column(
                children: [
                  const ListTile(
                    title: Text('Add product photos', style: TextStyle(fontWeight: FontWeight.w800)),
                    subtitle: Text('Up to 4 photos go into one AI artwork generation.'),
                  ),
                  Expanded(
                    child: ListView.builder(
                      itemCount: _products.length,
                      itemBuilder: (_, index) {
                        final product = Map<String, dynamic>.from(_products[index] as Map);
                        final id = int.tryParse('${product['id']}') ?? 0;
                        final checked = selected.contains(id);
                        return CheckboxListTile(
                          value: checked,
                          title: Text('${product['display_name'] ?? product['name'] ?? 'Product'}'),
                          subtitle: Text(checked ? 'Selected' : 'Tap to select'),
                          onChanged: (value) {
                            if (value == true && selected.length >= 4 && !checked) {
                              ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('A Business Post supports up to 4 product photos.')));
                              return;
                            }
                            setSheetState(() => value == true ? selected.add(id) : selected.remove(id));
                          },
                        );
                      },
                    ),
                  ),
                  Padding(
                    padding: const EdgeInsets.all(16),
                    child: FilledButton(
                      onPressed: () {
                        setState(() => _productIds
                          ..clear()
                          ..addAll(selected));
                        Navigator.pop(sheetContext);
                      },
                      style: _primaryButtonStyle,
                      child: Text('Use ${selected.length} photo${selected.length == 1 ? '' : 's'}'),
                    ),
                  ),
                ],
              ),
            ),
          ),
        ),
      );
    } catch (error) {
      _showError(error.toString().replaceFirst('Exception: ', ''));
    }
  }

  Future<void> _generate({String? versionInstruction}) async {
    for (final field in _purposeFields) {
      if (field['required'] == true && (_brief[field['key'].toString()]?.text.trim().isEmpty ?? true)) {
        _showError('${field['label']} is required.');
        return;
      }
    }
    if (!_canGenerate || _remaining < _generationCost) {
      _showError(_remaining < _generationCost ? 'Not enough AI credits available.' : 'Choose a model, quality and size.');
      return;
    }
    final brief = <String, String>{
      for (final item in _brief.entries) if (item.value.text.trim().isNotEmpty) item.key: item.value.text.trim(),
      for (var i = 0; i < _customDetails.length; i++)
        if (_customDetails[i].value.text.trim().isNotEmpty)
          'detail_${_customDetails[i].label.text.trim().isEmpty ? i + 1 : _customDetails[i].label.text.trim().replaceAll(' ', '_')}': _customDetails[i].value.text.trim(),
    };
    setState(() => _submitting = true);
    try {
      final response = await ApiService.post('/business-ai/generations', {
        'purpose_key': _purpose!['key'],
        'style_key': _style!['key'],
        'model_id': _model!['id'],
        'quality': _quality,
        'size_key': _sizeKey,
        'language_id': _languages.isEmpty ? null : _languages.first['id'],
        'brief': brief,
        'user_instruction': versionInstruction ?? _visualInstruction.text.trim(),
        'product_ids': _productIds.toList(),
      });
      final data = jsonDecode(response.body);
      if (response.statusCode != 202 || data is! Map || data['success'] != true) {
        throw Exception(data is Map ? data['message'] : 'Generation could not be started.');
      }
      setState(() {
        _job = Map<String, dynamic>.from(data['job'] as Map);
        _quota = {...?_quota, 'remaining': (_remaining - _generationCost).clamp(0, 999999)};
        _step = 3;
      });
      _startPolling();
    } catch (error) {
      _showError(error.toString().replaceFirst('Exception: ', ''));
    } finally {
      if (mounted) setState(() => _submitting = false);
    }
  }

  void _startPolling() {
    _poller?.cancel();
    _poller = Timer.periodic(const Duration(seconds: 3), (_) => _refreshJob());
    _refreshJob();
  }

  Future<void> _refreshJob() async {
    final id = _job?['id'];
    if (id == null) return;
    try {
      final response = await ApiService.get('/business-ai/generations/$id');
      final data = jsonDecode(response.body);
      if (response.statusCode == 200 && data is Map && data['success'] == true && data['job'] is Map) {
        final job = Map<String, dynamic>.from(data['job'] as Map);
        if (mounted) setState(() => _job = job);
        if (job['status'] == 'completed' || job['status'] == 'failed') _poller?.cancel();
      }
    } catch (_) {}
  }

  void _showError(String message) {
    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(message), backgroundColor: const Color(0xFFDC2626)));
  }

  PreferredSizeWidget _buildAppBar() {
    String title = '';
    Widget? leading;
    List<Widget>? actions;

    switch (_step) {
      case 0:
        title = 'Artera Create';
        leading = IconButton(icon: const Icon(Icons.menu, color: Color(0xFF172033)), onPressed: () => Navigator.pop(context));
        actions = [IconButton(icon: const Icon(Icons.notifications_outlined, color: Color(0xFF172033)), onPressed: () {})];
        break;
      case 1:
        title = 'Add Post Brief';
        leading = IconButton(icon: const Icon(Icons.arrow_back, color: Color(0xFF172033)), onPressed: () => setState(() => _step = 0));
        actions = [IconButton(icon: const Icon(Icons.help_outline, color: Color(0xFF172033)), onPressed: () {})];
        break;
      case 2:
        title = 'Select Custom Post Style';
        leading = IconButton(icon: const Icon(Icons.arrow_back, color: Color(0xFF172033)), onPressed: () => setState(() => _step = 1));
        actions = [IconButton(icon: const Icon(Icons.help_outline, color: Color(0xFF172033)), onPressed: () {})];
        break;
      case 3:
        title = 'AI Preview • Editable Design';
        leading = IconButton(icon: const Icon(Icons.arrow_back, color: Color(0xFF172033)), onPressed: () => setState(() => _step = 2));
        actions = [IconButton(icon: const Icon(Icons.file_download_outlined, color: Color(0xFF172033)), onPressed: () {})];
        break;
    }

    return AppBar(
      backgroundColor: const Color(0xFFF8FAFC),
      elevation: 0,
      centerTitle: true,
      leading: leading,
      title: Text(title, style: const TextStyle(color: Color(0xFF172033), fontWeight: FontWeight.w800, fontSize: 16)),
      actions: actions,
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF8FAFC),
      appBar: _buildAppBar(),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : _error != null
          ? _errorState()
          : SafeArea(
              top: false,
              child: Column(
                children: [
                  _stepper(),
                  Expanded(child: AnimatedSwitcher(duration: const Duration(milliseconds: 220), child: _currentStep())),
                ],
              ),
            ),
    );
  }

  Widget _currentStep() {
    return switch (_step) {
      0 => _choosePurpose(),
      1 => _addBrief(),
      2 => _selectStyle(),
      _ => _preview(),
    };
  }

  Widget _stepper() {
    if (_step == 0 || _step == 3) return const SizedBox.shrink();

    return Container(
      color: const Color(0xFFF8FAFC),
      padding: const EdgeInsets.only(top: 10, bottom: 20),
      child: Column(
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: List.generate(9, (index) {
              if (index.isOdd) {
                return Container(width: 32, height: 1.5, color: const Color(0xFFE2E8F0));
              }
              final stepIndex = index ~/ 2;
              final isCompleted = stepIndex < _step;
              final isActive = stepIndex == _step;
              final color = isCompleted || isActive ? const Color(0xFF6434E8) : const Color(0xFFCBD5E1);
              
              return Container(
                width: 28, height: 28,
                alignment: Alignment.center,
                decoration: BoxDecoration(
                  shape: BoxShape.circle,
                  color: isActive ? color : Colors.white,
                  border: Border.all(color: color, width: 1.5),
                ),
                child: Text('${stepIndex + 1}', style: TextStyle(color: isActive ? Colors.white : color, fontWeight: FontWeight.bold, fontSize: 13)),
              );
            }),
          ),
          const SizedBox(height: 12),
          Text('Step ${_step + 1} of 5', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: Color(0xFF475569))),
        ],
      ),
    );
  }

  Widget _choosePurpose() => ListView(
    key: const ValueKey('purpose'), padding: const EdgeInsets.fromLTRB(20, 16, 20, 36), children: [
      InkWell(
        onTap: _openBusinessContactDetails,
        borderRadius: BorderRadius.circular(16),
        child: Ink(
          padding: const EdgeInsets.all(16),
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(16),
            boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.04), blurRadius: 10, offset: const Offset(0, 4))],
          ),
          child: Row(
            children: [
               Container(
                 width: 40, height: 40,
                 decoration: BoxDecoration(color: const Color(0xFFF3F4F6), borderRadius: BorderRadius.circular(8)),
                 child: const Center(child: Text('A', style: TextStyle(color: Color(0xFFE11D48), fontSize: 24, fontWeight: FontWeight.w900))),
               ),
               const SizedBox(width: 12),
               const Expanded(child: Column(
                 crossAxisAlignment: CrossAxisAlignment.start,
                 children: [
                   Text('Artera Pixel', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                   Text('Logo • Contacts • My Business', style: TextStyle(color: Colors.grey, fontSize: 12)),
                 ],
               )),
               const Icon(Icons.chevron_right, color: Colors.grey),
            ],
          ),
        ),
      ),
      const SizedBox(height: 24),
      const Text('What will you create?', style: TextStyle(fontSize: 20, fontWeight: FontWeight.w900, color: Color(0xFF172033))),
      const SizedBox(height: 16),
      GridView.builder(
        shrinkWrap: true, physics: const NeverScrollableScrollPhysics(), itemCount: _purposes.length,
        gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(crossAxisCount: 2, mainAxisSpacing: 16, crossAxisSpacing: 16, mainAxisExtent: 172),
        itemBuilder: (_, index) {
          final purpose = Map<String, dynamic>.from(_purposes[index] as Map);
          final isSelected = purpose['key'] == _purpose?['key'];
          return InkWell(
            onTap: () => _selectPurpose(purpose), borderRadius: BorderRadius.circular(20),
            child: Ink(
              decoration: BoxDecoration(
                color: isSelected ? const Color(0xFF6434E8) : Colors.white,
                borderRadius: BorderRadius.circular(20),
                border: Border.all(color: isSelected ? const Color(0xFF6434E8) : const Color(0xFFF1F5F9), width: 1.5),
                boxShadow: isSelected ? [BoxShadow(color: const Color(0xFF6434E8).withOpacity(0.3), blurRadius: 12, offset: const Offset(0, 6))] : [BoxShadow(color: Colors.black.withOpacity(0.03), blurRadius: 8, offset: const Offset(0, 4))],
              ),
              padding: const EdgeInsets.all(14),
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Container(
                    width: 54, height: 54,
                    decoration: BoxDecoration(
                      color: isSelected ? Colors.white.withOpacity(0.18) : _purposeAccent('${purpose['icon']}').withOpacity(0.12),
                      borderRadius: BorderRadius.circular(18),
                    ),
                    child: Icon(
                      _purposeIcon('${purpose['icon']}'),
                      color: isSelected ? Colors.white : _purposeAccent('${purpose['icon']}'),
                      size: 28,
                    ),
                  ),
                  const SizedBox(height: 10),
                  Text(
                    '${purpose['title']}',
                    textAlign: TextAlign.center,
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                    style: TextStyle(fontWeight: FontWeight.w800, fontSize: 13.5, height: 1.12, color: isSelected ? Colors.white : const Color(0xFF172033)),
                  ),
                  const SizedBox(height: 4),
                  Builder(builder: (context) {
                    final text = (purpose['subtitle'] ?? purpose['description'])?.toString();
                    if (text == null || text.trim().isEmpty || text == 'null') return const SizedBox.shrink();
                    return Text(text, textAlign: TextAlign.center, maxLines: 2, overflow: TextOverflow.ellipsis, style: TextStyle(fontSize: 10, color: isSelected ? Colors.white70 : const Color(0xFF64748B), height: 1.2));
                  }),
                ],
              ),
            ),
          );
        },
      ),
    ],
  );

  Future<void> _openBusinessContactDetails() async {
    final homeController = Get.find<HomeController>();
    await homeController.loadBusinessInfo();
    if (!mounted) return;

    if (homeController.businesses.isEmpty) {
      await Get.to<dynamic>(() => const BusinessProfileScreen(isNew: true));
      return;
    }

    final selected = homeController.businesses.firstWhere(
      (item) => item is Map &&
          (item['isDefault'] == true ||
              item['is_default'] == true ||
              item['is_default'] == 1 ||
              item['is_default'] == '1'),
      orElse: () => homeController.businesses.first,
    );
    if (selected is! Map) return;

    await Get.to<dynamic>(() => BusinessProfileScreen(
          business: Map<String, dynamic>.from(selected),
        ));
    if (mounted) await homeController.loadBusinessInfo();
  }

  InputDecoration _inputDecoration(String hint) => InputDecoration(
    hintText: hint,
    hintStyle: const TextStyle(color: Color(0xFF94A3B8), fontSize: 14),
    contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
    border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: Color(0xFFCBD5E1))),
    enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: Color(0xFFCBD5E1))),
    focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: Color(0xFF6434E8), width: 1.5)),
    filled: true, fillColor: Colors.white,
  );

  Widget _addBrief() => ListView(
    key: const ValueKey('brief'), padding: const EdgeInsets.fromLTRB(20, 0, 20, 32), children: [
      Text('${_purpose?['title']} Brief', style: const TextStyle(fontSize: 22, fontWeight: FontWeight.w900)),
      const SizedBox(height: 16),
      for (final field in _purposeFields) 
        Padding(
          padding: const EdgeInsets.only(bottom: 16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text('${field['label']}${field['required'] == true ? ' *' : ''}', style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 13, color: Color(0xFF334155))),
              const SizedBox(height: 6),
              TextField(
                controller: _brief[field['key'].toString()],
                decoration: _inputDecoration('${field['hint'] ?? ''}'),
              ),
            ],
          ),
        ),
      for (final detail in _customDetails)
        Padding(
          padding: const EdgeInsets.only(bottom: 12),
          child: Row(
            children: [
              Expanded(child: TextField(controller: detail.label, decoration: _inputDecoration('Detail label'))),
              const SizedBox(width: 8),
              Expanded(child: TextField(controller: detail.value, decoration: _inputDecoration('Detail value'))),
              IconButton(onPressed: () => setState(() { detail.dispose(); _customDetails.remove(detail); }), icon: const Icon(Icons.close, color: Color(0xFF94A3B8)))
            ],
          ),
        ),
      OutlinedButton.icon(
        onPressed: () => setState(() => _customDetails.add(_CustomDetail())),
        icon: const Icon(Icons.add, color: Color(0xFF6434E8), size: 20),
        label: const Text('Add detail', style: TextStyle(color: Color(0xFF6434E8), fontWeight: FontWeight.w700)),
        style: OutlinedButton.styleFrom(
          minimumSize: const Size.fromHeight(50),
          side: const BorderSide(color: Color(0xFF6434E8), width: 1.5),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
        ),
      ),
      const SizedBox(height: 20),
      Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text('Additional visual instruction (Optional)', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 13, color: Color(0xFF334155))),
          const SizedBox(height: 6),
          TextField(controller: _visualInstruction, maxLines: 3, decoration: _inputDecoration('Keep space on left for headline')),
        ],
      ),
      const SizedBox(height: 30),
      FilledButton(onPressed: () => setState(() => _step = 2), style: _primaryButtonStyle, child: const Row(mainAxisAlignment: MainAxisAlignment.center, children: [Text('Next', style: TextStyle(fontSize: 16)), SizedBox(width: 8), Icon(Icons.arrow_forward, size: 20)])),
    ],
  );

  Widget _selectStyle() => ListView(
    key: const ValueKey('style'), padding: const EdgeInsets.fromLTRB(20, 0, 20, 32), children: [
      const Text('Select Custom Post Style', style: TextStyle(fontSize: 20, fontWeight: FontWeight.w900, color: Color(0xFF172033))),
      const SizedBox(height: 6),
      const Text('Choose a visual look for your post', style: TextStyle(color: Color(0xFF64748B))),
      const SizedBox(height: 20),
      GridView.builder(
        shrinkWrap: true, physics: const NeverScrollableScrollPhysics(), itemCount: _styles.length, 
        gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(crossAxisCount: 2, childAspectRatio: 0.85, crossAxisSpacing: 16, mainAxisSpacing: 16), 
        itemBuilder: (_, index) {
          final style = Map<String, dynamic>.from(_styles[index] as Map); 
          final selected = style['key'] == _style?['key']; 
          final colors = List<dynamic>.from(style['colors'] ?? const []);
          final c1 = _hex('${colors.isNotEmpty ? colors.first : '#4338CA'}'); 
          final c2 = _hex('${colors.length > 1 ? colors[1] : '#0F172A'}');
          
          return GestureDetector(
            onTap: () => setState(() => _style = style),
            child: Container(
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(16),
                border: Border.all(color: selected ? const Color(0xFF6434E8) : Colors.transparent, width: 2),
                boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.04), blurRadius: 10, offset: const Offset(0, 4))],
              ),
              child: Stack(
                children: [
                  Column(
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      Expanded(
                        child: Container(
                          margin: const EdgeInsets.all(4),
                          decoration: BoxDecoration(
                            gradient: LinearGradient(colors: [c1, c2], begin: Alignment.topLeft, end: Alignment.bottomRight),
                            borderRadius: BorderRadius.circular(12),
                          ),
                          alignment: Alignment.center,
                          child: const Text('STYLE\nPREVIEW', textAlign: TextAlign.center, style: TextStyle(color: Colors.white54, fontWeight: FontWeight.w900, fontSize: 16)),
                        )
                      ),
                      Padding(
                        padding: const EdgeInsets.symmetric(vertical: 12),
                        child: Text('${style['name']}', style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w700, color: Color(0xFF172033)), textAlign: TextAlign.center),
                      )
                    ]
                  ),
                  if (selected)
                    Positioned(
                      top: 10, right: 10,
                      child: Container(
                        decoration: const BoxDecoration(color: Color(0xFF6434E8), shape: BoxShape.circle),
                        padding: const EdgeInsets.all(4),
                        child: const Icon(Icons.check, color: Colors.white, size: 14),
                      ),
                    ),
                ],
              ),
            ),
          );
      }),
      const SizedBox(height: 24),
      Container(
        padding: const EdgeInsets.all(14),
        decoration: BoxDecoration(color: const Color(0xFFF8FAFC), borderRadius: BorderRadius.circular(12), border: Border.all(color: const Color(0xFFE2E8F0))),
        child: Row(children: const [
          Icon(Icons.info_outline, color: Color(0xFF6434E8), size: 20),
          SizedBox(width: 10),
          Expanded(child: Text('Text, shapes and icons stay editable', style: TextStyle(color: Color(0xFF334155), fontWeight: FontWeight.w600, fontSize: 12))),
        ]),
      ),
      const SizedBox(height: 24),
      FilledButton.icon(
        onPressed: _submitting ? null : _generate, 
        style: _primaryButtonStyle, 
        icon: _submitting ? const SizedBox(width: 18, height: 18, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white)) : const Icon(Icons.auto_awesome), 
        label: Text(_submitting ? 'Starting...' : 'Generate with AI  •  $_generationCost credit')
      ),
      const SizedBox(height: 20),
      const Divider(),
      const SizedBox(height: 10),
      _generationSettings(),
    ],
  );

  Widget _generationSettings() => Column(
    crossAxisAlignment: CrossAxisAlignment.start,
    children: [
      const Text('Advanced settings', style: TextStyle(fontWeight: FontWeight.w800, color: Color(0xFF64748B))),
      const SizedBox(height: 12),
      DropdownButtonFormField<String>(
        initialValue: _model?['id']?.toString(),
        decoration: _inputDecoration('AI model'),
        items: _models.map((raw) {
          final model = Map<String, dynamic>.from(raw as Map);
          return DropdownMenuItem(value: model['id']?.toString(), child: Text('${model['display_name']}'));
        }).toList(),
        onChanged: (id) {
          if (id == null) return;
          final raw = _models.cast<Map?>().firstWhere((item) => item?['id']?.toString() == id, orElse: () => null);
          if (raw == null) return;
          final model = Map<String, dynamic>.from(raw);
          final available = List<dynamic>.from(model['quality_variants'] ?? []).cast<Map?>().firstWhere(
            (item) => item?['is_available'] == true,
            orElse: () => null,
          );
          setState(() {
            _model = model;
            _quality = available?['key']?.toString();
            _sizeKey = _availableSizes.isEmpty ? null : _availableSizes.first['key']?.toString();
          });
        },
      ),
      const SizedBox(height: 10),
      Row(
        children: [
          Expanded(
            child: DropdownButtonFormField<String>(
              initialValue: _quality,
              decoration: _inputDecoration('Quality'),
              items: List<dynamic>.from(_model?['quality_variants'] ?? [])
                  .where((item) => item is Map && item['is_available'] == true)
                  .map((raw) {
                    final item = Map<String, dynamic>.from(raw as Map);
                    return DropdownMenuItem(value: item['key']?.toString(), child: Text('${item['display_name']}'));
                  }).toList(),
              onChanged: (value) => setState(() => _quality = value),
            ),
          ),
          const SizedBox(width: 10),
          Expanded(
            child: DropdownButtonFormField<String>(
              initialValue: _sizeKey,
              decoration: _inputDecoration('Size'),
              items: _availableSizes.map((item) => DropdownMenuItem(value: item['key']?.toString(), child: Text('${item['label'] ?? item['size'] ?? item['key']}'))).toList(),
              onChanged: (value) => setState(() => _sizeKey = value),
            ),
          ),
        ],
      ),
    ],
  );

  Widget _preview() {
    final status = _job?['status']?.toString() ?? 'queued'; 
    final imageUrl = _job?['image_url']?.toString() ?? ''; 
    final editable = _job?['editable_document']; 
    final docId = editable is Map ? editable['document_id']?.toString() ?? '' : '';
    final ready = status == 'completed' && docId.isNotEmpty;
    
    return ListView(key: const ValueKey('preview'), padding: const EdgeInsets.fromLTRB(20, 0, 20, 32), children: [
      if (!ready) ...[
        const SizedBox(height: 40),
        const Center(child: CircularProgressIndicator(color: Color(0xFF6434E8))),
        const SizedBox(height: 20),
        Text(status == 'failed' ? '${_job?['error_message'] ?? 'Generation failed.'}' : 'Creating one artwork with editable layers...', textAlign: TextAlign.center, style: const TextStyle(color: Color(0xFF64748B))),
      ],
      if (ready) ...[
        Center(
          child: Container(
            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
            decoration: BoxDecoration(
              color: const Color(0xFFECFDF5),
              borderRadius: BorderRadius.circular(99),
              border: Border.all(color: const Color(0xFFA7F3D0)),
            ),
            child: const Text('Editable text, icons and shapes', style: TextStyle(color: Color(0xFF047857), fontWeight: FontWeight.w700, fontSize: 11)),
          ),
        ),
        const SizedBox(height: 16),
        Container(
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(20),
            boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.08), blurRadius: 16, offset: const Offset(0, 8))],
          ),
          clipBehavior: Clip.antiAlias,
          child: AspectRatio(
            aspectRatio: 1, 
            child: imageUrl.isNotEmpty ? Image.network(imageUrl, fit: BoxFit.cover, errorBuilder: (_, _, _) => const Center(child: Icon(Icons.broken_image_outlined))) : const Center(child: CircularProgressIndicator())
          ),
        ),
        const SizedBox(height: 24),
        Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Expanded(child: _actionColumn(Icons.edit_outlined, 'Edit Design', () => Get.to(() => AiEditableEditorScreen(documentId: docId)))),
            const SizedBox(width: 8),
            Expanded(child: _actionColumn(Icons.auto_awesome, 'Another\nVersion', _openNewVersion)),
            const SizedBox(width: 8),
            Expanded(child: _actionColumn(Icons.style_outlined, 'Change\nStyle', () => setState(() => _step = 2))),
            const SizedBox(width: 8),
            Expanded(child: _actionColumn(Icons.description_outlined, 'Change\nBrief', () => setState(() => _step = 1))),
          ],
        ),
      ],
    ]);
  }

  Widget _actionColumn(IconData icon, String label, VoidCallback onTap) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(12),
      child: Column(
        children: [
          Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              border: Border.all(color: const Color(0xFFE2E8F0)),
              borderRadius: BorderRadius.circular(16),
            ),
            child: Icon(icon, color: const Color(0xFF172033), size: 28),
          ),
          const SizedBox(height: 8),
          Text(label, textAlign: TextAlign.center, style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w600, color: Color(0xFF334155))),
        ],
      ),
    );
  }

  Future<void> _openNewVersion() async {
    final instruction = TextEditingController(text: _visualInstruction.text);
    await showModalBottomSheet<void>(
      context: context, 
      isScrollControlled: true, 
      backgroundColor: Colors.transparent,
      builder: (sheetContext) => Container(
        decoration: const BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
        ),
        padding: EdgeInsets.fromLTRB(20, 12, 20, MediaQuery.of(context).viewInsets.bottom + 20), 
        child: Column(
          mainAxisSize: MainAxisSize.min, 
          crossAxisAlignment: CrossAxisAlignment.start, 
          children: [
            Center(child: Container(width: 40, height: 4, decoration: BoxDecoration(color: const Color(0xFFE2E8F0), borderRadius: BorderRadius.circular(2)))),
            const SizedBox(height: 16),
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                const Text('Create another version', style: TextStyle(fontWeight: FontWeight.w900, fontSize: 20)),
                IconButton(icon: const Icon(Icons.close), onPressed: () => Navigator.pop(sheetContext)),
              ],
            ),
            const SizedBox(height: 16),
            const Text('Describe what you want', style: TextStyle(fontWeight: FontWeight.w700, fontSize: 13, color: Color(0xFF334155))),
            const SizedBox(height: 8),
            TextField(
              controller: instruction, maxLines: 4, 
              decoration: InputDecoration(
                hintText: 'Make the design more premium...',
                hintStyle: const TextStyle(color: Color(0xFF94A3B8)),
                filled: true, fillColor: const Color(0xFFF8FAFC),
                border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide.none),
              )
            ), 
            const SizedBox(height: 16),
            const Text('Add product photos (Optional)', style: TextStyle(fontWeight: FontWeight.w700, fontSize: 13, color: Color(0xFF334155))),
            const SizedBox(height: 8),
            OutlinedButton.icon(
              onPressed: () { Navigator.pop(sheetContext); _chooseProducts(); }, 
              icon: const Icon(Icons.photo_library_outlined, color: Color(0xFF172033)), 
              label: Text('${_productIds.length} photos selected. Tap to change.', style: const TextStyle(color: Color(0xFF172033))),
              style: OutlinedButton.styleFrom(minimumSize: const Size.fromHeight(50), shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12))),
            ),
            const SizedBox(height: 20),
            Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(color: const Color(0xFFF0EDFF), borderRadius: BorderRadius.circular(12)),
              child: Row(children: const [
                Icon(Icons.auto_awesome, color: Color(0xFF6434E8), size: 20),
                SizedBox(width: 10),
                Expanded(child: Text('A new design will get fresh editable text, shapes and icons.', style: TextStyle(color: Color(0xFF4C1D95), fontSize: 12, fontWeight: FontWeight.w500))),
              ]),
            ),
            const SizedBox(height: 20),
            FilledButton.icon(
              onPressed: () { Navigator.pop(sheetContext); _generate(versionInstruction: instruction.text.trim()); }, 
              style: _primaryButtonStyle, 
              icon: const Icon(Icons.auto_awesome), 
              label: Text('Generate New Version  •  $_generationCost credit')
            ),
          ]
        )
      )
    );
    instruction.dispose();
  }

  Widget _errorState() => Center(child: Padding(padding: const EdgeInsets.all(28), child: Column(mainAxisSize: MainAxisSize.min, children: [const Icon(Icons.cloud_off_rounded, size: 50, color: Color(0xFF94A3B8)), const SizedBox(height: 12), Text(_error!, textAlign: TextAlign.center), const SizedBox(height: 14), FilledButton(onPressed: _loadOptions, child: const Text('Try again'))])));

  static ButtonStyle get _primaryButtonStyle => FilledButton.styleFrom(backgroundColor: const Color(0xFF6434E8), foregroundColor: Colors.white, minimumSize: const Size.fromHeight(52), shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)));
  static Color _hex(String value) { final clean = value.replaceFirst('#', ''); return Color(int.parse('FF$clean', radix: 16)); }
  static IconData _purposeIcon(String value) => switch (value) {
    'work' => Icons.work_outline_rounded,
    'local_offer' => Icons.local_offer_outlined,
    'inventory' => Icons.inventory_2_outlined,
    'campaign' => Icons.campaign_outlined,
    'volunteer_activism' => Icons.volunteer_activism_outlined,
    _ => Icons.auto_awesome_rounded,
  };
  static Color _purposeAccent(String value) => switch (value) {
    'work' => const Color(0xFF6D3DF0),
    'local_offer' => const Color(0xFFF97316),
    'inventory' => const Color(0xFF2563EB),
    'campaign' => const Color(0xFFEF4444),
    'volunteer_activism' => const Color(0xFF16A34A),
    _ => const Color(0xFF7C3AED),
  };
  static String _purposeEmoji(String value) => switch (value) { 'work' => '💼', 'local_offer' => '🏷️', 'inventory' => '📦', 'campaign' => '📢', 'volunteer_activism' => '🌱', _ => '✨' };
  static String _purposeEmojiUrl(String value) => switch (value) {
    'work' => 'https://raw.githubusercontent.com/microsoft/fluentui-emoji/main/assets/Briefcase/3D/briefcase_3d.png',
    'local_offer' => 'https://raw.githubusercontent.com/microsoft/fluentui-emoji/main/assets/Label/3D/label_3d.png',
    'inventory' => 'https://raw.githubusercontent.com/microsoft/fluentui-emoji/main/assets/Package/3D/package_3d.png',
    'campaign' => 'https://raw.githubusercontent.com/microsoft/fluentui-emoji/main/assets/Megaphone/3D/megaphone_3d.png',
    'volunteer_activism' => 'https://raw.githubusercontent.com/microsoft/fluentui-emoji/main/assets/Seedling/3D/seedling_3d.png',
    _ => 'https://raw.githubusercontent.com/microsoft/fluentui-emoji/main/assets/Sparkles/3D/sparkles_3d.png'
  };
}

class _CustomDetail {
  final label = TextEditingController();
  final value = TextEditingController();
  void dispose() { label.dispose(); value.dispose(); }
}

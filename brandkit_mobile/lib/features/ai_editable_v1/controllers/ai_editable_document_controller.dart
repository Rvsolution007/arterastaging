import 'dart:convert';

import 'package:get/get.dart';

import '../../../services/api_service.dart';

/// Controller for the standalone, frame-free AI Editable V1 contract.
///
/// It intentionally does not depend on NativeEditorController or any frame
/// configuration. All coordinates stored in the manifest are absolute,
/// top-left canvas values.
class AiEditableDocumentController extends GetxController {
  AiEditableDocumentController(this.documentId);

  final String documentId;
  final document = Rxn<Map<String, dynamic>>();
  final selectedLayerId = RxnString();
  final isLoading = true.obs;
  final isSaving = false.obs;
  final errorMessage = RxnString();

  Map<String, dynamic>? get manifest {
    final value = document.value?['manifest'];
    return value is Map<String, dynamic> ? value : null;
  }

  List<Map<String, dynamic>> get layers {
    final raw = manifest?['layers'];
    if (raw is! List) return const [];
    return raw
        .whereType<Map>()
        .map((layer) => Map<String, dynamic>.from(layer))
        .toList()
      ..sort(
        (left, right) =>
            _number(left['z_index']).compareTo(_number(right['z_index'])),
      );
  }

  Future<void> load() async {
    isLoading.value = true;
    errorMessage.value = null;
    try {
      final response = await ApiService.get(
        '/ai-editable/v1/documents/$documentId',
      );
      final decoded = jsonDecode(response.body);
      if (response.statusCode != 200 ||
          decoded is! Map ||
          decoded['success'] != true ||
          decoded['document'] is! Map) {
        throw Exception(
          decoded is Map
              ? (decoded['message'] ??
                    'The editable document could not be loaded.')
              : 'The editable document could not be loaded.',
        );
      }
      document.value = _copy(Map<String, dynamic>.from(decoded['document']));
    } catch (error) {
      errorMessage.value = error.toString().replaceFirst('Exception: ', '');
    } finally {
      isLoading.value = false;
    }
  }

  void selectLayer(String? layerId) {
    selectedLayerId.value = layerId;
  }

  void toggleLayerVisibility(String layerId) {
    _mutateLayer(layerId, (layer) {
      layer['visible'] = !(layer['visible'] != false);
    });
  }

  void moveLayer(String layerId, double deltaX, double deltaY) {
    _mutateLayer(layerId, (layer) {
      if (layer['locked'] == true) return;
      final transform = Map<String, dynamic>.from(layer['transform'] as Map);
      transform['x'] = _number(transform['x']) + deltaX;
      transform['y'] = _number(transform['y']) + deltaY;
      layer['transform'] = transform;
    });
  }

  void setOpacity(String layerId, double opacity) {
    _mutateLayer(layerId, (layer) {
      layer['opacity'] = opacity.clamp(0.0, 1.0);
    });
  }

  void setText(String layerId, String text) {
    _mutateLayer(layerId, (layer) {
      if (layer['type'] != 'text') return;
      layer['text'] = text.trim();
    });
  }

  void setTransform(String layerId, Map<String, dynamic> transform) {
    _mutateLayer(layerId, (layer) {
      layer['transform'] = Map<String, dynamic>.from(transform);
    });
  }

  void setLayerStyle(String layerId, Map<String, dynamic> style) {
    _mutateLayer(layerId, (layer) {
      layer['style'] = Map<String, dynamic>.from(style);
    });
  }

  void setGradient(String layerId, Map<String, dynamic> gradient) {
    _mutateLayer(layerId, (layer) {
      if (layer['type'] != 'gradient') return;
      layer['gradient'] = Map<String, dynamic>.from(gradient);
    });
  }

  void reorderLayers(int oldIndex, int newIndex) {
    final copy = _copyDocument();
    final manifestCopy = Map<String, dynamic>.from(copy['manifest'] as Map);
    final ordered =
        List<Map<String, dynamic>>.from(
          (manifestCopy['layers'] as List).whereType<Map>().map(
            (item) => Map<String, dynamic>.from(item),
          ),
        )..sort(
          (left, right) =>
              _number(left['z_index']).compareTo(_number(right['z_index'])),
        );
    if (oldIndex < 0 || oldIndex >= ordered.length) return;
    var target = newIndex;
    if (target > oldIndex) target -= 1;
    target = target.clamp(0, ordered.length - 1).toInt();
    final moved = ordered.removeAt(oldIndex);
    ordered.insert(target, moved);
    for (var index = 0; index < ordered.length; index++) {
      ordered[index]['z_index'] = index;
    }
    manifestCopy['layers'] = ordered;
    copy['manifest'] = manifestCopy;
    document.value = copy;
  }

  Future<bool> save() async {
    final current = document.value;
    if (current == null || isSaving.value) return false;
    isSaving.value = true;
    errorMessage.value = null;
    try {
      final response = await ApiService.post(
        '/ai-editable/v1/documents/$documentId/save',
        {
          'expected_revision': current['revision'],
          'manifest': current['manifest'],
        },
      );
      final decoded = jsonDecode(response.body);
      if (response.statusCode != 200 ||
          decoded is! Map ||
          decoded['success'] != true ||
          decoded['document'] is! Map) {
        throw Exception(
          decoded is Map
              ? (decoded['message'] ??
                    'The editable document could not be saved.')
              : 'The editable document could not be saved.',
        );
      }
      document.value = _copy(Map<String, dynamic>.from(decoded['document']));
      return true;
    } catch (error) {
      errorMessage.value = error.toString().replaceFirst('Exception: ', '');
      return false;
    } finally {
      isSaving.value = false;
    }
  }

  void _mutateLayer(
    String layerId,
    void Function(Map<String, dynamic>) mutate,
  ) {
    final copy = _copyDocument();
    final manifestCopy = Map<String, dynamic>.from(copy['manifest'] as Map);
    final rawLayers = List<dynamic>.from(manifestCopy['layers'] as List);
    final index = rawLayers.indexWhere(
      (item) => item is Map && item['id']?.toString() == layerId,
    );
    if (index < 0) return;
    final layer = Map<String, dynamic>.from(rawLayers[index] as Map);
    mutate(layer);
    rawLayers[index] = layer;
    manifestCopy['layers'] = rawLayers;
    copy['manifest'] = manifestCopy;
    document.value = copy;
  }

  Map<String, dynamic> _copyDocument() {
    final current = document.value;
    if (current == null) return <String, dynamic>{};
    return _copy(current);
  }

  Map<String, dynamic> _copy(Map<String, dynamic> source) =>
      Map<String, dynamic>.from(jsonDecode(jsonEncode(source)) as Map);

  static double _number(dynamic value) =>
      value is num ? value.toDouble() : double.tryParse('$value') ?? 0;
}

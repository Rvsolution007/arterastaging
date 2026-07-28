import 'dart:typed_data';
import 'dart:ui' as ui;
import 'dart:math' as math;

import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';
import 'package:flutter/rendering.dart' show RenderRepaintBoundary;
import 'package:gal/gal.dart';
import 'package:get/get.dart';
import 'package:google_fonts/google_fonts.dart';

import '../controllers/ai_editable_document_controller.dart';

/// A dedicated AI editor. It never loads a frame, template zip, or the
/// existing NativeEditorController, so frames cannot appear in this screen.
class AiEditableEditorScreen extends StatefulWidget {
  const AiEditableEditorScreen({super.key, required this.documentId});

  final String documentId;

  @override
  State<AiEditableEditorScreen> createState() => _AiEditableEditorScreenState();
}

class _AiEditableEditorScreenState extends State<AiEditableEditorScreen> {
  final _canvasKey = GlobalKey();
  late final AiEditableDocumentController _controller;

  @override
  void initState() {
    super.initState();
    _controller = Get.put(
      AiEditableDocumentController(widget.documentId),
      tag: widget.documentId,
    );
    _controller.load();
  }

  @override
  void dispose() {
    Get.delete<AiEditableDocumentController>(tag: widget.documentId);
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF1F5F9),
      appBar: AppBar(
        title: const Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text('AI editable editor'),
            Text(
              'Layers only - no frame',
              style: TextStyle(fontSize: 11, fontWeight: FontWeight.w400),
            ),
          ],
        ),
        actions: [
          IconButton(
            tooltip: 'Layers',
            onPressed: _showLayers,
            icon: const Icon(Icons.layers_outlined),
          ),
          Obx(
            () => IconButton(
              tooltip: 'Save',
              onPressed: _controller.isSaving.value ? null : _save,
              icon: _controller.isSaving.value
                  ? const SizedBox(
                      height: 18,
                      width: 18,
                      child: CircularProgressIndicator(strokeWidth: 2),
                    )
                  : const Icon(Icons.save_outlined),
            ),
          ),
          IconButton(
            tooltip: 'Export PNG',
            onPressed: _exportPng,
            icon: const Icon(Icons.file_download_outlined),
          ),
        ],
      ),
      body: Obx(() {
        if (_controller.isLoading.value) {
          return const Center(child: CircularProgressIndicator());
        }
        final error = _controller.errorMessage.value;
        if (_controller.document.value == null) {
          return _FailureState(
            message: error ?? 'This editable design is unavailable.',
            onRetry: _controller.load,
          );
        }
        final manifest = _controller.manifest!;
        return Column(
          children: [
            if (error != null)
              Container(
                width: double.infinity,
                color: const Color(0xFFFEF2F2),
                padding: const EdgeInsets.all(10),
                child: Text(
                  error,
                  style: const TextStyle(color: Color(0xFFB91C1C)),
                ),
              ),
            Expanded(child: _canvas(manifest)),
            _selectedLayerToolbar(),
          ],
        );
      }),
    );
  }

  Widget _canvas(Map<String, dynamic> manifest) {
    final canvas = Map<String, dynamic>.from(manifest['canvas'] as Map);
    final canvasWidth = _number(canvas['width']);
    final canvasHeight = _number(canvas['height']);
    final layers = _controller.layers;

    return Padding(
      padding: const EdgeInsets.all(16),
      child: InteractiveViewer(
        minScale: .6,
        maxScale: 4,
        child: Center(
          child: AspectRatio(
            aspectRatio: canvasWidth / canvasHeight,
            child: RepaintBoundary(
              key: _canvasKey,
              child: LayoutBuilder(
                builder: (context, constraints) {
                  final scale = constraints.maxWidth / canvasWidth;
                  return Material(
                    color: Colors.white,
                    clipBehavior: Clip.antiAlias,
                    child: Stack(
                      fit: StackFit.expand,
                      children: [
                        for (final layer in layers) _canvasLayer(layer, scale),
                      ],
                    ),
                  );
                },
              ),
            ),
          ),
        ),
      ),
    );
  }

  Widget _canvasLayer(Map<String, dynamic> layer, double scale) {
    if (layer['visible'] == false) return const SizedBox.shrink();
    final transform = Map<String, dynamic>.from(layer['transform'] as Map);
    final id = layer['id']?.toString() ?? '';
    final selected = _controller.selectedLayerId.value == id;
    final width = _number(transform['width']) * scale;
    final height = _number(transform['height']) * scale;
    final rotation = _number(transform['rotation']) * 3.14159265359 / 180;

    return Positioned(
      left: _number(transform['x']) * scale,
      top: _number(transform['y']) * scale,
      width: width,
      height: height,
      child: Transform.rotate(
        angle: rotation,
        alignment: Alignment.center,
        child: Opacity(
          opacity: _number(layer['opacity']).clamp(0, 1).toDouble(),
          child: GestureDetector(
            behavior: HitTestBehavior.translucent,
            onTap: () => _controller.selectLayer(id),
            onPanUpdate: layer['locked'] == true
                ? null
                : (details) => _controller.moveLayer(
                    id,
                    details.delta.dx / scale,
                    details.delta.dy / scale,
                  ),
            child: DecoratedBox(
              decoration: selected
                  ? BoxDecoration(
                      border: Border.all(
                        color: const Color(0xFF4F46E5),
                        width: 2,
                      ),
                    )
                  : const BoxDecoration(),
              child: _layerContent(layer),
            ),
          ),
        ),
      ),
    );
  }

  Widget _layerContent(Map<String, dynamic> layer) {
    final style = layer['style'] is Map
        ? Map<String, dynamic>.from(layer['style'] as Map)
        : const <String, dynamic>{};
    final shadow = style['shadow'] is Map
        ? Map<String, dynamic>.from(style['shadow'] as Map)
        : null;
    final blur = _number(style['blur']);
    Widget child;
    switch (layer['type']) {
      case 'gradient':
        child = _gradientLayer(layer);
        break;
      case 'text':
        child = _textLayer(layer, style);
        break;
      case 'shape':
      case 'effect':
        child = _shapeLayer(layer, style);
        break;
      case 'icon':
        child = _iconLayer(style);
        break;
      case 'bitmap':
      default:
        child = _bitmapLayer(layer);
        break;
    }

    if (blur > 0) {
      child = ImageFiltered(
        imageFilter: ui.ImageFilter.blur(sigmaX: blur, sigmaY: blur),
        child: child,
      );
    }
    if (shadow != null) {
      child = DecoratedBox(
        decoration: BoxDecoration(
          boxShadow: [
            BoxShadow(
              color: _color(shadow['color'], fallback: Colors.black).withValues(
                alpha: _number(shadow['opacity']).clamp(0, 1).toDouble(),
              ),
              blurRadius: _number(shadow['blur']),
              spreadRadius: _number(shadow['spread']),
              offset: Offset(_number(shadow['x']), _number(shadow['y'])),
            ),
          ],
        ),
        child: child,
      );
    }
    return child;
  }

  Widget _bitmapLayer(Map<String, dynamic> layer) {
    final asset = layer['asset'] is Map
        ? Map<String, dynamic>.from(layer['asset'] as Map)
        : const <String, dynamic>{};
    final url = (asset['url'] ?? asset['src'] ?? '').toString();
    if (url.isEmpty) {
      return const ColoredBox(color: Color(0xFFE2E8F0));
    }
    return CachedNetworkImage(
      imageUrl: url,
      fit: _boxFit(asset['fit']?.toString()),
      placeholder: (_, _) => const ColoredBox(color: Color(0xFFE2E8F0)),
      errorWidget: (_, _, _) => const Center(
        child: Icon(Icons.broken_image_outlined, color: Color(0xFF94A3B8)),
      ),
    );
  }

  Widget _gradientLayer(Map<String, dynamic> layer) {
    final gradient = Map<String, dynamic>.from(layer['gradient'] as Map);
    final colorValues = gradient['colors'] is List
        ? List<dynamic>.from(gradient['colors'] as List)
        : const <dynamic>[];
    final colors = colorValues
        .map((value) => _color(value, fallback: Colors.transparent))
        .toList();
    final safeColors = colors.length >= 2
        ? colors
        : const [Color(0xFFF97316), Color(0xFFEF4444)];
    final angle = _number(gradient['angle']);
    final radians = angle * 3.14159265359 / 180;
    final begin = Alignment(-math.cos(radians), -math.sin(radians));
    final end = Alignment(math.cos(radians), math.sin(radians));
    final decoration = gradient['type'] == 'radial'
        ? BoxDecoration(gradient: RadialGradient(colors: safeColors))
        : BoxDecoration(
            gradient: LinearGradient(
              begin: begin,
              end: end,
              colors: safeColors,
            ),
          );
    return DecoratedBox(decoration: decoration);
  }

  Widget _textLayer(Map<String, dynamic> layer, Map<String, dynamic> style) {
    final color = _color(style['color'], fallback: Colors.black);
    final text = layer['text']?.toString() ?? '';
    final fontSize = _number(style['font_size']);
    final baseStyle = TextStyle(
      color: color,
      fontSize: fontSize > 0 ? fontSize : 32,
      fontFamily: style['font_family']?.toString(),
      fontWeight: _fontWeight(style['font_weight']),
      height: _number(style['line_height']) > 0
          ? _number(style['line_height'])
          : 1.1,
      shadows: _textShadows(style),
    );
    return FittedBox(
      alignment: Alignment.topLeft,
      fit: BoxFit.scaleDown,
      child: Text(
        text,
        softWrap: true,
        style: _fontFor(style['font_token'], baseStyle),
      ),
    );
  }

  Widget _shapeLayer(Map<String, dynamic> layer, Map<String, dynamic> style) {
    final kind = style['kind']?.toString() ?? 'rectangle';
    final radius = kind == 'circle'
        ? 9999.0
        : kind == 'pill'
        ? 999.0
        : _number(style['radius']);
    return DecoratedBox(
      decoration: BoxDecoration(
        color: _color(style['color'], fallback: const Color(0x66000000)),
        borderRadius: BorderRadius.circular(radius),
        border: style['border_color'] == null
            ? null
            : Border.all(
                color: _color(
                  style['border_color'],
                  fallback: Colors.transparent,
                ),
                width: _number(style['border_width']),
              ),
      ),
    );
  }

  Widget _iconLayer(Map<String, dynamic> style) {
    final iconName = style['icon_name']?.toString() ?? 'star';
    final icon = switch (iconName) {
      'phone' => Icons.phone_rounded,
      'whatsapp' => Icons.chat_rounded,
      'email' => Icons.email_rounded,
      'website' => Icons.language_rounded,
      'location' => Icons.location_on_rounded,
      'arrow' => Icons.arrow_forward_rounded,
      _ => Icons.star_rounded,
    };
    return FittedBox(
      fit: BoxFit.contain,
      child: Icon(icon, color: _color(style['color'], fallback: Colors.white)),
    );
  }

  Widget _selectedLayerToolbar() {
    return Obx(() {
      final selectedId = _controller.selectedLayerId.value;
      final layer = _controller.layers.cast<Map<String, dynamic>?>().firstWhere(
        (item) => item?['id']?.toString() == selectedId,
        orElse: () => null,
      );
      if (layer == null) {
        return const SafeArea(
          top: false,
          child: Padding(
            padding: EdgeInsets.all(16),
            child: Text('Tap a layer to move it or change its properties.'),
          ),
        );
      }
      final opacity = _number(layer['opacity']).clamp(0, 1).toDouble();
      return SafeArea(
        top: false,
        child: Material(
          color: Colors.white,
          child: Padding(
            padding: const EdgeInsets.fromLTRB(16, 10, 16, 12),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Expanded(
                      child: Text(
                        layer['name']?.toString() ?? 'Layer',
                        style: const TextStyle(fontWeight: FontWeight.w800),
                      ),
                    ),
                    if (layer['type'] == 'text')
                      TextButton.icon(
                        onPressed: () => _editText(layer),
                        icon: const Icon(Icons.edit_outlined, size: 17),
                        label: const Text('Text'),
                      ),
                    TextButton.icon(
                      onPressed: () => _editTransform(layer),
                      icon: const Icon(Icons.open_with_outlined, size: 17),
                      label: const Text('Position'),
                    ),
                    IconButton(
                      tooltip: layer['visible'] == false
                          ? 'Show layer'
                          : 'Hide layer',
                      onPressed: () => _controller.toggleLayerVisibility(
                        layer['id'].toString(),
                      ),
                      icon: Icon(
                        layer['visible'] == false
                            ? Icons.visibility_off
                            : Icons.visibility,
                      ),
                    ),
                  ],
                ),
                Row(
                  children: [
                    const Icon(Icons.opacity, size: 18),
                    Expanded(
                      child: Slider(
                        value: opacity,
                        onChanged: (value) => _controller.setOpacity(
                          layer['id'].toString(),
                          value,
                        ),
                      ),
                    ),
                  ],
                ),
                _layerSpecificControls(layer),
              ],
            ),
          ),
        ),
      );
    });
  }

  Widget _layerSpecificControls(Map<String, dynamic> layer) {
    final type = layer['type']?.toString();
    final style = layer['style'] is Map
        ? Map<String, dynamic>.from(layer['style'] as Map)
        : const <String, dynamic>{};
    final hasShadow = style['shadow'] is Map;
    final hasBlur = style.containsKey('blur');
    if (type == 'text') {
      return Wrap(
        spacing: 4,
        children: [
          TextButton.icon(
            onPressed: () => _editTypography(layer),
            icon: const Icon(Icons.font_download_outlined, size: 17),
            label: const Text('Font & colour'),
          ),
        ],
      );
    }
    if (type == 'shape' || type == 'icon') {
      return Wrap(
        spacing: 4,
        children: [
          TextButton.icon(
            onPressed: () => _editLayerColor(layer),
            icon: const Icon(Icons.palette_outlined, size: 17),
            label: const Text('Colour'),
          ),
        ],
      );
    }
    if (type != 'gradient' && type != 'effect' && !hasShadow && !hasBlur) {
      return const SizedBox.shrink();
    }
    return Wrap(
      spacing: 4,
      runSpacing: 2,
      children: [
        if (type == 'gradient')
          TextButton.icon(
            onPressed: () => _editGradient(layer),
            icon: const Icon(Icons.gradient_outlined, size: 17),
            label: const Text('Gradient'),
          ),
        if (type == 'effect')
          TextButton.icon(
            onPressed: () => _editEffect(layer),
            icon: const Icon(Icons.auto_fix_high_outlined, size: 17),
            label: const Text('Effect'),
          ),
        if (hasShadow)
          TextButton.icon(
            onPressed: () => _editShadow(layer),
            icon: const Icon(Icons.blur_on_outlined, size: 17),
            label: const Text('Shadow'),
          ),
        if (hasBlur)
          TextButton.icon(
            onPressed: () => _editBlur(layer),
            icon: const Icon(Icons.blur_linear_outlined, size: 17),
            label: const Text('Blur'),
          ),
      ],
    );
  }

  Future<void> _editTransform(Map<String, dynamic> layer) async {
    final transform = Map<String, dynamic>.from(layer['transform'] as Map);
    final x = TextEditingController(text: _number(transform['x']).toString());
    final y = TextEditingController(text: _number(transform['y']).toString());
    final width = TextEditingController(
      text: _number(transform['width']).toString(),
    );
    final height = TextEditingController(
      text: _number(transform['height']).toString(),
    );
    final rotation = TextEditingController(
      text: _number(transform['rotation']).toString(),
    );
    final updated = await showDialog<Map<String, dynamic>>(
      context: context,
      builder: (dialogContext) => AlertDialog(
        title: const Text('Position and size'),
        content: SingleChildScrollView(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              _numberField(x, 'Left'),
              _numberField(y, 'Top'),
              _numberField(width, 'Width'),
              _numberField(height, 'Height'),
              _numberField(rotation, 'Rotation'),
            ],
          ),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(dialogContext),
            child: const Text('Cancel'),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(dialogContext, {
              'x': _inputNumber(x, _number(transform['x'])),
              'y': _inputNumber(y, _number(transform['y'])),
              'width': _inputNumber(
                width,
                _number(transform['width']),
              ).clamp(1, double.infinity),
              'height': _inputNumber(
                height,
                _number(transform['height']),
              ).clamp(1, double.infinity),
              'rotation': _inputNumber(
                rotation,
                _number(transform['rotation']),
              ),
            }),
            child: const Text('Apply'),
          ),
        ],
      ),
    );
    if (updated != null) {
      _controller.setTransform(layer['id'].toString(), updated);
    }
  }

  Future<void> _editGradient(Map<String, dynamic> layer) async {
    final gradient = Map<String, dynamic>.from(layer['gradient'] as Map);
    final colors = gradient['colors'] is List
        ? List<dynamic>.from(gradient['colors'] as List)
        : const <dynamic>[];
    final first = TextEditingController(
      text: colors.isNotEmpty ? colors.first.toString() : '#F97316',
    );
    final second = TextEditingController(
      text: colors.length > 1 ? colors[1].toString() : '#EF4444',
    );
    var angle = _number(gradient['angle']).clamp(0, 360).toDouble();
    var type = gradient['type']?.toString() == 'radial' ? 'radial' : 'linear';
    final updated = await showDialog<Map<String, dynamic>>(
      context: context,
      builder: (dialogContext) => StatefulBuilder(
        builder: (_, setDialogState) => AlertDialog(
          title: const Text('Gradient'),
          content: SingleChildScrollView(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                DropdownButtonFormField<String>(
                  initialValue: type,
                  decoration: const InputDecoration(labelText: 'Type'),
                  items: const [
                    DropdownMenuItem(value: 'linear', child: Text('Linear')),
                    DropdownMenuItem(value: 'radial', child: Text('Radial')),
                  ],
                  onChanged: (value) => setDialogState(() {
                    type = value ?? 'linear';
                  }),
                ),
                TextField(
                  controller: first,
                  decoration: const InputDecoration(
                    labelText: 'First colour (#RRGGBB)',
                  ),
                ),
                TextField(
                  controller: second,
                  decoration: const InputDecoration(
                    labelText: 'Second colour (#RRGGBB)',
                  ),
                ),
                const SizedBox(height: 12),
                Text('Angle: ${angle.round()} degrees'),
                Slider(
                  value: angle,
                  min: 0,
                  max: 360,
                  onChanged: (value) => setDialogState(() => angle = value),
                ),
              ],
            ),
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(dialogContext),
              child: const Text('Cancel'),
            ),
            FilledButton(
              onPressed: () => Navigator.pop(dialogContext, {
                'type': type,
                'angle': angle,
                'colors': [
                  _hexColor(first.text, '#F97316'),
                  _hexColor(second.text, '#EF4444'),
                ],
              }),
              child: const Text('Apply'),
            ),
          ],
        ),
      ),
    );
    if (updated != null) {
      _controller.setGradient(layer['id'].toString(), updated);
    }
  }

  Future<void> _editEffect(Map<String, dynamic> layer) async {
    final style = layer['style'] is Map
        ? Map<String, dynamic>.from(layer['style'] as Map)
        : <String, dynamic>{};
    final color = TextEditingController(
      text: style['color']?.toString() ?? '#FFFFFF',
    );
    final radius = TextEditingController(
      text: _number(style['radius']).toString(),
    );
    final blur = TextEditingController(text: _number(style['blur']).toString());
    final updated = await _editStyleDialog(
      title: 'Effect',
      fields: [
        _textInput(color, 'Colour (#RRGGBB)'),
        _numberField(radius, 'Radius'),
        _numberField(blur, 'Blur'),
      ],
      onApply: () => {
        ...style,
        'color': _hexColor(color.text, '#FFFFFF'),
        'radius': _inputNumber(
          radius,
          _number(style['radius']),
        ).clamp(0, double.infinity),
        'blur': _inputNumber(blur, _number(style['blur'])).clamp(0, 200),
      },
    );
    if (updated != null) {
      _controller.setLayerStyle(layer['id'].toString(), updated);
    }
  }

  Future<void> _editShadow(Map<String, dynamic> layer) async {
    final style = Map<String, dynamic>.from(layer['style'] as Map);
    final shadow = style['shadow'] is Map
        ? Map<String, dynamic>.from(style['shadow'] as Map)
        : <String, dynamic>{};
    final color = TextEditingController(
      text: shadow['color']?.toString() ?? '#000000',
    );
    final x = TextEditingController(text: _number(shadow['x']).toString());
    final y = TextEditingController(text: _number(shadow['y']).toString());
    final blur = TextEditingController(
      text: _number(shadow['blur']).toString(),
    );
    final opacity = TextEditingController(
      text: _number(shadow['opacity']).toString(),
    );
    final updated = await _editStyleDialog(
      title: 'Shadow',
      fields: [
        _textInput(color, 'Colour (#RRGGBB)'),
        _numberField(x, 'Horizontal offset'),
        _numberField(y, 'Vertical offset'),
        _numberField(blur, 'Blur'),
        _numberField(opacity, 'Opacity (0 to 1)'),
      ],
      onApply: () => {
        ...style,
        'shadow': {
          ...shadow,
          'color': _hexColor(color.text, '#000000'),
          'x': _inputNumber(x, _number(shadow['x'])),
          'y': _inputNumber(y, _number(shadow['y'])),
          'blur': _inputNumber(blur, _number(shadow['blur'])).clamp(0, 200),
          'opacity': _inputNumber(
            opacity,
            _number(shadow['opacity']),
          ).clamp(0, 1),
        },
      },
    );
    if (updated != null) {
      _controller.setLayerStyle(layer['id'].toString(), updated);
    }
  }

  Future<void> _editBlur(Map<String, dynamic> layer) async {
    final style = Map<String, dynamic>.from(layer['style'] as Map);
    var blur = _number(style['blur']).clamp(0, 200).toDouble();
    final updated = await showDialog<double>(
      context: context,
      builder: (dialogContext) => StatefulBuilder(
        builder: (_, setDialogState) => AlertDialog(
          title: const Text('Layer blur'),
          content: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Text('${blur.round()} px'),
              Slider(
                value: blur,
                min: 0,
                max: 200,
                onChanged: (value) => setDialogState(() => blur = value),
              ),
            ],
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(dialogContext),
              child: const Text('Cancel'),
            ),
            FilledButton(
              onPressed: () => Navigator.pop(dialogContext, blur),
              child: const Text('Apply'),
            ),
          ],
        ),
      ),
    );
    if (updated != null) {
      _controller.setLayerStyle(layer['id'].toString(), {
        ...style,
        'blur': updated,
      });
    }
  }

  Future<void> _editTypography(Map<String, dynamic> layer) async {
    final style = Map<String, dynamic>.from(layer['style'] as Map);
    var fontToken = style['font_token']?.toString() ?? 'sans';
    var fontSize = _number(style['font_size']).clamp(8, 200).toDouble();
    var isBold = _number(style['font_weight']) >= 700;
    final color = TextEditingController(
      text: style['color']?.toString() ?? '#FFFFFF',
    );
    final updated = await showDialog<Map<String, dynamic>>(
      context: context,
      builder: (dialogContext) => StatefulBuilder(
        builder: (_, setDialogState) => AlertDialog(
          title: const Text('Font'),
          content: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              DropdownButtonFormField<String>(
                initialValue: fontToken,
                decoration: const InputDecoration(labelText: 'Approved font'),
                items: const [
                  DropdownMenuItem(value: 'sans', child: Text('Hind')),
                  DropdownMenuItem(value: 'display', child: Text('Poppins')),
                  DropdownMenuItem(
                    value: 'serif',
                    child: Text('Playfair Display'),
                  ),
                  DropdownMenuItem(
                    value: 'devanagari',
                    child: Text('Noto Sans Devanagari'),
                  ),
                ],
                onChanged: (value) => setDialogState(() {
                  fontToken = value ?? 'sans';
                }),
              ),
              const SizedBox(height: 14),
              Text('Size: ${fontSize.round()} px'),
              Slider(
                value: fontSize,
                min: 8,
                max: 160,
                onChanged: (value) => setDialogState(() => fontSize = value),
              ),
              SwitchListTile(
                value: isBold,
                contentPadding: EdgeInsets.zero,
                title: const Text('Bold'),
                onChanged: (value) => setDialogState(() => isBold = value),
              ),
              TextField(
                controller: color,
                decoration: const InputDecoration(
                  labelText: 'Text colour (#RRGGBB)',
                ),
              ),
            ],
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(dialogContext),
              child: const Text('Cancel'),
            ),
            FilledButton(
              onPressed: () => Navigator.pop(dialogContext, {
                ...style,
                'font_token': fontToken,
                'font_size': fontSize,
                'font_weight': isBold ? 800 : 500,
                'color': _hexColor(color.text, '#FFFFFF'),
              }),
              child: const Text('Apply'),
            ),
          ],
        ),
      ),
    );
    if (updated != null) {
      _controller.setLayerStyle(layer['id'].toString(), updated);
    }
  }

  Future<void> _editLayerColor(Map<String, dynamic> layer) async {
    final style = layer['style'] is Map
        ? Map<String, dynamic>.from(layer['style'] as Map)
        : <String, dynamic>{};
    final color = TextEditingController(
      text: style['color']?.toString() ?? '#FFFFFF',
    );
    final updated = await _editStyleDialog(
      title: 'Layer colour',
      fields: [_textInput(color, 'Colour (#RRGGBB)')],
      onApply: () => {...style, 'color': _hexColor(color.text, '#FFFFFF')},
    );
    if (updated != null) {
      _controller.setLayerStyle(layer['id'].toString(), updated);
    }
  }

  Future<Map<String, dynamic>?> _editStyleDialog({
    required String title,
    required List<Widget> fields,
    required Map<String, dynamic> Function() onApply,
  }) {
    return showDialog<Map<String, dynamic>>(
      context: context,
      builder: (dialogContext) => AlertDialog(
        title: Text(title),
        content: SingleChildScrollView(
          child: Column(mainAxisSize: MainAxisSize.min, children: fields),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(dialogContext),
            child: const Text('Cancel'),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(dialogContext, onApply()),
            child: const Text('Apply'),
          ),
        ],
      ),
    );
  }

  static Widget _numberField(TextEditingController controller, String label) =>
      TextField(
        controller: controller,
        keyboardType: const TextInputType.numberWithOptions(
          decimal: true,
          signed: true,
        ),
        decoration: InputDecoration(labelText: label),
      );

  static Widget _textInput(TextEditingController controller, String label) =>
      TextField(
        controller: controller,
        decoration: InputDecoration(labelText: label),
      );

  static double _inputNumber(
    TextEditingController controller,
    double fallback,
  ) => double.tryParse(controller.text.trim()) ?? fallback;

  static String _hexColor(String value, String fallback) {
    final normalized = value.trim().replaceFirst('#', '');
    return RegExp(r'^[0-9a-fA-F]{6}$').hasMatch(normalized)
        ? '#${normalized.toUpperCase()}'
        : fallback;
  }

  Future<void> _save() async {
    final saved = await _controller.save();
    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(
          saved
              ? 'Layer changes saved.'
              : (_controller.errorMessage.value ?? 'Could not save changes.'),
        ),
        backgroundColor: saved
            ? const Color(0xFF059669)
            : const Color(0xFFDC2626),
      ),
    );
  }

  Future<void> _exportPng() async {
    try {
      final boundary =
          _canvasKey.currentContext?.findRenderObject()
              as RenderRepaintBoundary?;
      if (boundary == null) throw Exception('The canvas is not ready yet.');
      final canvas = _controller.manifest?['canvas'];
      final targetWidth = canvas is Map ? _number(canvas['width']) : 0;
      final sourceRatio = targetWidth > 0 && boundary.size.width > 0
          ? targetWidth / boundary.size.width
          : 2.0;
      // Export at the AI document resolution where practical. A cap prevents
      // a very large custom canvas from exhausting a phone's graphics memory.
      final image = await boundary.toImage(
        pixelRatio: sourceRatio.clamp(1, 4).toDouble(),
      );
      final bytes = await image.toByteData(format: ui.ImageByteFormat.png);
      if (bytes == null) throw Exception('The canvas could not be exported.');
      await Gal.putImageBytes(
        Uint8List.view(bytes.buffer),
        name: 'artera-ai-editable-${DateTime.now().millisecondsSinceEpoch}',
      );
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('PNG saved to your gallery.'),
          backgroundColor: Color(0xFF059669),
        ),
      );
    } catch (error) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(error.toString().replaceFirst('Exception: ', '')),
          backgroundColor: const Color(0xFFDC2626),
        ),
      );
    }
  }

  Future<void> _editText(Map<String, dynamic> layer) async {
    final input = TextEditingController(text: layer['text']?.toString() ?? '');
    final text = await showDialog<String>(
      context: context,
      builder: (dialogContext) => AlertDialog(
        title: const Text('Edit text'),
        content: TextField(controller: input, maxLines: 4, autofocus: true),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(dialogContext),
            child: const Text('Cancel'),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(dialogContext, input.text),
            child: const Text('Apply'),
          ),
        ],
      ),
    );
    if (text != null && text.trim().isNotEmpty) {
      _controller.setText(layer['id'].toString(), text);
    }
  }

  void _showLayers() {
    showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      builder: (sheetContext) => SafeArea(
        child: SizedBox(
          height: MediaQuery.of(sheetContext).size.height * .72,
          child: Column(
            children: [
              const Padding(
                padding: EdgeInsets.all(16),
                child: Row(
                  children: [
                    Icon(Icons.layers_outlined),
                    SizedBox(width: 8),
                    Text(
                      'AI image layers',
                      style: TextStyle(
                        fontSize: 18,
                        fontWeight: FontWeight.w800,
                      ),
                    ),
                  ],
                ),
              ),
              const Divider(height: 1),
              Expanded(
                child: Obx(() {
                  final layers = _controller.layers.reversed.toList();
                  return ReorderableListView.builder(
                    padding: const EdgeInsets.symmetric(vertical: 8),
                    itemCount: layers.length,
                    onReorder: _controller.reorderLayers,
                    itemBuilder: (_, index) {
                      final layer = layers[index];
                      final id = layer['id'].toString();
                      return ListTile(
                        key: ValueKey(id),
                        leading: Icon(_layerIcon(layer['type']?.toString())),
                        title: Text(layer['name']?.toString() ?? 'Layer'),
                        subtitle: Text(layer['type']?.toString() ?? 'layer'),
                        trailing: IconButton(
                          onPressed: () =>
                              _controller.toggleLayerVisibility(id),
                          icon: Icon(
                            layer['visible'] == false
                                ? Icons.visibility_off
                                : Icons.visibility,
                          ),
                        ),
                        onTap: () {
                          _controller.selectLayer(id);
                          Navigator.pop(sheetContext);
                        },
                      );
                    },
                  );
                }),
              ),
            ],
          ),
        ),
      ),
    );
  }

  static IconData _layerIcon(String? type) {
    switch (type) {
      case 'text':
        return Icons.text_fields;
      case 'gradient':
        return Icons.gradient;
      case 'shape':
        return Icons.category_outlined;
      case 'effect':
        return Icons.auto_fix_high_outlined;
      default:
        return Icons.image_outlined;
    }
  }

  static BoxFit _boxFit(String? value) =>
      value == 'contain' ? BoxFit.contain : BoxFit.cover;

  static FontWeight _fontWeight(dynamic value) {
    final weight = int.tryParse('$value') ?? 400;
    if (weight >= 800) return FontWeight.w800;
    if (weight >= 700) return FontWeight.w700;
    if (weight >= 600) return FontWeight.w600;
    if (weight >= 500) return FontWeight.w500;
    if (weight <= 300) return FontWeight.w300;
    return FontWeight.w400;
  }

  /// The planner can select only this approved, downloadable catalog. It
  /// never writes an arbitrary font name into a document. If a font cannot be
  /// fetched, google_fonts retains Flutter's safe fallback instead of making
  /// the layer disappear.
  static TextStyle _fontFor(dynamic token, TextStyle baseStyle) {
    switch (token?.toString()) {
      case 'display':
        return GoogleFonts.poppins(textStyle: baseStyle);
      case 'serif':
        return GoogleFonts.playfairDisplay(textStyle: baseStyle);
      case 'devanagari':
        return GoogleFonts.notoSansDevanagari(textStyle: baseStyle);
      case 'sans':
      default:
        return GoogleFonts.hind(textStyle: baseStyle);
    }
  }

  static List<Shadow> _textShadows(Map<String, dynamic> style) {
    final shadow = style['shadow'];
    if (shadow is! Map) return const [];
    return [
      Shadow(
        color: _color(
          shadow['color'],
          fallback: Colors.black,
        ).withValues(alpha: _number(shadow['opacity']).clamp(0, 1).toDouble()),
        offset: Offset(_number(shadow['x']), _number(shadow['y'])),
        blurRadius: _number(shadow['blur']),
      ),
    ];
  }

  static Color _color(dynamic value, {required Color fallback}) {
    final hex = value?.toString().trim() ?? '';
    final cleaned = hex.replaceFirst('#', '');
    if (!RegExp(r'^[0-9a-fA-F]{6}([0-9a-fA-F]{2})?$').hasMatch(cleaned)) {
      return fallback;
    }
    final normalized = cleaned.length == 6
        ? 'FF$cleaned'
        : cleaned.substring(6) + cleaned.substring(0, 6);
    return Color(int.parse(normalized, radix: 16));
  }

  static double _number(dynamic value) =>
      value is num ? value.toDouble() : double.tryParse('$value') ?? 0;
}

class _FailureState extends StatelessWidget {
  const _FailureState({required this.message, required this.onRetry});

  final String message;
  final Future<void> Function() onRetry;

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(28),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Icon(
              Icons.layers_clear_outlined,
              size: 52,
              color: Color(0xFF94A3B8),
            ),
            const SizedBox(height: 14),
            Text(message, textAlign: TextAlign.center),
            const SizedBox(height: 12),
            FilledButton.icon(
              onPressed: onRetry,
              icon: const Icon(Icons.refresh),
              label: const Text('Try again'),
            ),
          ],
        ),
      ),
    );
  }
}

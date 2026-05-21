import 'package:flutter/material.dart';
import 'dart:html' as html;
import 'dart:ui_web' as ui_web;

Widget getWebEditor(String url) {
  final String viewId = 'iframe-${DateTime.now().millisecondsSinceEpoch}';

  ui_web.platformViewRegistry.registerViewFactory(viewId, (int viewId) {
    var iframe = html.IFrameElement()
      ..src = url
      ..style.border = 'none'
      ..style.height = '100%'
      ..style.width = '100%';
    return iframe;
  });

  return HtmlElementView(viewType: viewId);
}

import 'package:flutter/material.dart';

/// Visual language for the native AdLive workspace.
abstract final class AdLiveDesign {
  static const ink = Color(0xFF102620);
  static const muted = Color(0xFF62756D);
  static const green = Color(0xFF008F69);
  static const deepGreen = Color(0xFF006B51);
  static const mint = Color(0xFFE8F8F0);
  static const canvas = Color(0xFFF5F8F6);
  static const line = Color(0xFFDCEAE3);
  static const amber = Color(0xFF986A20);

  static BoxDecoration surface({Color color = Colors.white}) => BoxDecoration(
    color: color,
    borderRadius: BorderRadius.circular(20),
    border: Border.all(color: line),
    boxShadow: const [
      BoxShadow(color: Color(0x0C17392D), blurRadius: 18, offset: Offset(0, 7)),
    ],
  );

  static TextStyle heading(double size) => TextStyle(
    color: ink,
    fontSize: size,
    fontWeight: FontWeight.w800,
    height: 1.14,
    letterSpacing: -0.45,
  );

  static TextStyle body({double size = 13, Color color = muted}) => TextStyle(
    color: color,
    fontSize: size,
    fontWeight: FontWeight.w500,
    height: 1.4,
  );
}

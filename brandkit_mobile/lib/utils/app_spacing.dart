import 'package:flutter/material.dart';

/// Spacing constants — matches Tailwind's spacing scale used in web preview
class AppSpacing {
  AppSpacing._();

  // ──────────────────────────────────────────────
  // Raw spacing values (Tailwind scale * 4px)
  // ──────────────────────────────────────────────
  static const double xs = 4.0;     // p-1
  static const double sm = 8.0;     // p-2
  static const double md = 12.0;    // p-3
  static const double base = 16.0;  // p-4
  static const double lg = 20.0;    // p-5
  static const double xl = 24.0;    // p-6
  static const double xxl = 28.0;   // p-7
  static const double xxxl = 32.0;  // p-8
  static const double huge = 40.0;  // p-10

  // ──────────────────────────────────────────────
  // Common paddings used in web preview
  // ──────────────────────────────────────────────
  static const EdgeInsets screenPadding = EdgeInsets.symmetric(horizontal: 16.0);
  static const EdgeInsets screenPaddingLg = EdgeInsets.symmetric(horizontal: 24.0);
  static const EdgeInsets cardPadding = EdgeInsets.all(16.0);
  static const EdgeInsets cardPaddingLg = EdgeInsets.all(20.0);
  static const EdgeInsets listItemPadding = EdgeInsets.symmetric(horizontal: 20.0, vertical: 16.0);
  static const EdgeInsets sectionPadding = EdgeInsets.only(top: 16.0);

  // ──────────────────────────────────────────────
  // Gap sizes
  // ──────────────────────────────────────────────
  static const SizedBox gapH4 = SizedBox(width: 4);
  static const SizedBox gapH8 = SizedBox(width: 8);
  static const SizedBox gapH10 = SizedBox(width: 10);
  static const SizedBox gapH12 = SizedBox(width: 12);
  static const SizedBox gapH16 = SizedBox(width: 16);
  static const SizedBox gapH20 = SizedBox(width: 20);

  static const SizedBox gapV4 = SizedBox(height: 4);
  static const SizedBox gapV6 = SizedBox(height: 6);
  static const SizedBox gapV8 = SizedBox(height: 8);
  static const SizedBox gapV12 = SizedBox(height: 12);
  static const SizedBox gapV16 = SizedBox(height: 16);
  static const SizedBox gapV20 = SizedBox(height: 20);
  static const SizedBox gapV24 = SizedBox(height: 24);
  static const SizedBox gapV32 = SizedBox(height: 32);
  static const SizedBox gapV40 = SizedBox(height: 40);

  // ──────────────────────────────────────────────
  // Border Radius (matching web preview)
  // ──────────────────────────────────────────────
  static const double radiusSm = 8.0;
  static const double radiusMd = 12.0;
  static const double radiusLg = 16.0;
  static const double radiusXl = 20.0;
  static const double radiusXxl = 24.0;
  static const double radiusXxxl = 32.0;
  static const double radiusFull = 999.0;

  static final BorderRadius borderRadiusSm = BorderRadius.circular(radiusSm);
  static final BorderRadius borderRadiusMd = BorderRadius.circular(radiusMd);
  static final BorderRadius borderRadiusLg = BorderRadius.circular(radiusLg);
  static final BorderRadius borderRadiusXl = BorderRadius.circular(radiusXl);
  static final BorderRadius borderRadiusXxl = BorderRadius.circular(radiusXxl);
  static final BorderRadius borderRadiusXxxl = BorderRadius.circular(radiusXxxl);
  static final BorderRadius borderRadiusFull = BorderRadius.circular(radiusFull);
}

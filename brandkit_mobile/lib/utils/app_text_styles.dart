import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'app_colors.dart';

/// Typography system — matches web preview's Poppins-based design
class AppTextStyles {
  AppTextStyles._();

  // ──────────────────────────────────────────────
  // Base font family
  // ──────────────────────────────────────────────
  static TextStyle _poppins({
    double fontSize = 14,
    FontWeight fontWeight = FontWeight.w400,
    Color color = AppColors.textPrimary,
    double? height,
    double? letterSpacing,
  }) {
    return GoogleFonts.poppins(
      fontSize: fontSize,
      fontWeight: fontWeight,
      color: color,
      height: height,
      letterSpacing: letterSpacing,
    );
  }

  // ──────────────────────────────────────────────
  // Headings
  // ──────────────────────────────────────────────
  static TextStyle heading1 = _poppins(
    fontSize: 28,
    fontWeight: FontWeight.w700,
    color: AppColors.gray950,
    letterSpacing: -0.5,
  );

  static TextStyle heading2 = _poppins(
    fontSize: 22,
    fontWeight: FontWeight.w700,
    color: AppColors.gray900,
    letterSpacing: -0.3,
  );

  static TextStyle heading3 = _poppins(
    fontSize: 19,
    fontWeight: FontWeight.w700,
    color: AppColors.gray800,
    letterSpacing: -0.2,
  );

  static TextStyle heading4 = _poppins(
    fontSize: 18,
    fontWeight: FontWeight.w700,
    color: AppColors.gray800,
    letterSpacing: -0.2,
  );

  // ──────────────────────────────────────────────
  // Section Headers
  // ──────────────────────────────────────────────
  static TextStyle sectionTitle = _poppins(
    fontSize: 18,
    fontWeight: FontWeight.w700,
    color: AppColors.gray800,
    letterSpacing: -0.2,
  );

  static TextStyle sectionSubtitle = _poppins(
    fontSize: 13,
    fontWeight: FontWeight.w600,
    color: AppColors.primary,
  );

  // ──────────────────────────────────────────────
  // Body Text
  // ──────────────────────────────────────────────
  static TextStyle bodyLarge = _poppins(
    fontSize: 16,
    fontWeight: FontWeight.w400,
    color: AppColors.textPrimary,
  );

  static TextStyle bodyMedium = _poppins(
    fontSize: 14,
    fontWeight: FontWeight.w400,
    color: AppColors.textPrimary,
  );

  static TextStyle bodySmall = _poppins(
    fontSize: 13,
    fontWeight: FontWeight.w400,
    color: AppColors.textSecondary,
  );

  // ──────────────────────────────────────────────
  // Labels & Captions
  // ──────────────────────────────────────────────
  static TextStyle label = _poppins(
    fontSize: 14,
    fontWeight: FontWeight.w700,
    color: AppColors.gray900,
  );

  static TextStyle labelSmall = _poppins(
    fontSize: 11,
    fontWeight: FontWeight.w600,
    color: AppColors.gray500,
    letterSpacing: 0.5,
  );

  static TextStyle caption = _poppins(
    fontSize: 12,
    fontWeight: FontWeight.w500,
    color: AppColors.textMuted,
  );

  static TextStyle captionBold = _poppins(
    fontSize: 12.5,
    fontWeight: FontWeight.w600,
    color: AppColors.textSecondary,
  );

  // ──────────────────────────────────────────────
  // Buttons
  // ──────────────────────────────────────────────
  static TextStyle buttonPrimary = _poppins(
    fontSize: 13,
    fontWeight: FontWeight.w700,
    color: Colors.white,
  );

  static TextStyle buttonSecondary = _poppins(
    fontSize: 14,
    fontWeight: FontWeight.w700,
    color: AppColors.primary,
  );

  // ──────────────────────────────────────────────
  // Navigation
  // ──────────────────────────────────────────────
  static TextStyle navLabel = _poppins(
    fontSize: 10,
    fontWeight: FontWeight.w700,
  );

  static TextStyle navLabelInactive = _poppins(
    fontSize: 10,
    fontWeight: FontWeight.w600,
    color: AppColors.textMuted,
  );

  // ──────────────────────────────────────────────
  // Cards
  // ──────────────────────────────────────────────
  static TextStyle cardTitle = _poppins(
    fontSize: 14,
    fontWeight: FontWeight.w700,
    color: AppColors.gray900,
  );

  static TextStyle cardSubtitle = _poppins(
    fontSize: 11,
    fontWeight: FontWeight.w500,
    color: AppColors.gray500,
  );

  // ──────────────────────────────────────────────
  // Settings list items
  // ──────────────────────────────────────────────
  static TextStyle settingsTitle = _poppins(
    fontSize: 15,
    fontWeight: FontWeight.w700,
    color: AppColors.gray800,
  );

  static TextStyle settingsSubtitle = _poppins(
    fontSize: 13,
    fontWeight: FontWeight.w500,
    color: AppColors.gray400,
  );

  // ──────────────────────────────────────────────
  // Badge
  // ──────────────────────────────────────────────
  static TextStyle badge = _poppins(
    fontSize: 10,
    fontWeight: FontWeight.w700,
    color: Colors.white,
  );

  // ──────────────────────────────────────────────
  // Date Picker
  // ──────────────────────────────────────────────
  static TextStyle datePickerDay = _poppins(
    fontSize: 10,
    fontWeight: FontWeight.w700,
    letterSpacing: 0.5,
  );

  static TextStyle datePickerDate = _poppins(
    fontSize: 20,
    fontWeight: FontWeight.w800,
  );

  // ──────────────────────────────────────────────
  // Story
  // ──────────────────────────────────────────────
  static TextStyle storyLabel = _poppins(
    fontSize: 10.5,
    fontWeight: FontWeight.w500,
    color: AppColors.gray800,
  );
}

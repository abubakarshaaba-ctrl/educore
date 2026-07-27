import 'package:flutter/services.dart';

/// Centralized haptic feedback for the EduCore mobile app.
///
/// Wraps HapticFeedback so every call site is consistent and can be
/// toggled or tested easily.
class HapticHelper {
  HapticHelper._();

  /// Light tap - card selection, tab switch, chip press.
  static void tap() => HapticFeedback.lightImpact();

  /// Medium tap - primary button press, form submission.
  static void medium() => HapticFeedback.mediumImpact();

  /// Heavy tap - destructive actions, confirm dialog.
  static void heavy() => HapticFeedback.heavyImpact();

  /// Success - record saved, clock-in confirmed.
  static void success() => HapticFeedback.mediumImpact();

  /// Error - validation failure, network error.
  static void error() => HapticFeedback.heavyImpact();

  /// Selection changed - dropdown, segmented button.
  static void selection() => HapticFeedback.selectionClick();
}

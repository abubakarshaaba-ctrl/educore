import 'package:flutter/material.dart';

import '../main.dart';
import 'haptic_helper.dart';

/// Shows a styled bottom sheet with drag handle and smooth entrance.
///
/// Usage:
/// ```dart
/// showStyledBottomSheet(
///   context: context,
///   title: 'Route Manifest',
///   child: MyContent(),
/// );
/// ```
Future<T?> showStyledBottomSheet<T>({
  required BuildContext context,
  required Widget child,
  String? title,
  bool isScrollControlled = false,
  double heightRatio = 0.75,
}) {
  HapticHelper.medium();
  return showModalBottomSheet<T>(
    context: context,
    isScrollControlled: isScrollControlled,
    backgroundColor: Colors.transparent,
    builder: (context) => _StyledBottomSheet(
      title: title,
      heightRatio: heightRatio,
      child: child,
    ),
  );
}

class _StyledBottomSheet extends StatelessWidget {
  const _StyledBottomSheet({
    required this.child,
    this.title,
    this.heightRatio = 0.75,
  });

  final Widget child;
  final String? title;
  final double heightRatio;

  @override
  Widget build(BuildContext context) {
    final maxH = MediaQuery.sizeOf(context).height * heightRatio;

    return DraggableScrollableSheet(
      initialChildSize: 0.9,
      minChildSize: 0.4,
      maxChildSize: 0.95,
      expand: false,
      builder: (context, scrollController) {
        return Container(
          constraints: BoxConstraints(maxHeight: maxH),
          decoration: const BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
          ),
          child: Column(
            children: [
              // Drag handle
              Container(
                margin: const EdgeInsets.only(top: 12, bottom: 4),
                width: 40,
                height: 4,
                decoration: BoxDecoration(
                  color: const Color(0xFFD8E0E8),
                  borderRadius: BorderRadius.circular(2),
                ),
              ),

              // Header
              if (title != null)
                Padding(
                  padding: const EdgeInsets.fromLTRB(20, 12, 16, 4),
                  child: Row(
                    children: [
                      Expanded(
                        child: Text(
                          title!,
                          style: const TextStyle(
                            fontSize: 19,
                            fontWeight: FontWeight.w800,
                            color: kInk,
                          ),
                        ),
                      ),
                      IconButton(
                        onPressed: () {
                          HapticHelper.tap();
                          Navigator.pop(context);
                        },
                        icon: const Icon(Icons.close_rounded, color: kMuted),
                        splashRadius: 22,
                      ),
                    ],
                  ),
                ),

              const Divider(height: 1),

              // Content
              Expanded(
                child: Scrollbar(
                  thumbVisibility: true,
                  controller: scrollController,
                  child: ListView(
                    controller: scrollController,
                    padding: const EdgeInsets.symmetric(
                        horizontal: 20, vertical: 16),
                    children: [
                      if (title == null)
                        Padding(
                          padding: const EdgeInsets.only(bottom: 12),
                          child: Row(
                            mainAxisAlignment: MainAxisAlignment.center,
                            children: [
                              Container(
                                width: 40,
                                height: 4,
                                decoration: BoxDecoration(
                                  color: const Color(0xFFD8E0E8),
                                  borderRadius: BorderRadius.circular(2),
                                ),
                              ),
                            ],
                          ),
                        ),
                      child,
                    ],
                  ),
                ),
              ),
            ],
          ),
        );
      },
    );
  }
}

/// A simple confirm dialog with EduCore styling.
Future<bool> showConfirmDialog({
  required BuildContext context,
  required String title,
  required String message,
  String confirmLabel = 'Confirm',
  String cancelLabel = 'Cancel',
  Color confirmColor = kGold,
}) async {
  HapticHelper.heavy();
  final result = await showDialog<bool>(
    context: context,
    builder: (context) => AlertDialog(
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      title: Text(
        title,
        style: const TextStyle(fontWeight: FontWeight.w800, color: kInk),
      ),
      content: Text(
        message,
        style: const TextStyle(color: kMuted, height: 1.5),
      ),
      actions: [
        TextButton(
          onPressed: () {
            HapticHelper.tap();
            Navigator.pop(context, false);
          },
          child: Text(cancelLabel, style: const TextStyle(color: kMuted)),
        ),
        FilledButton(
          onPressed: () {
            HapticHelper.success();
            Navigator.pop(context, true);
          },
          style: FilledButton.styleFrom(
            backgroundColor: confirmColor,
            foregroundColor: kNavy,
          ),
          child: Text(confirmLabel),
        ),
      ],
    ),
  );
  return result ?? false;
}

/// Styled SnackBar with EduCore theming.
void showStyledSnackBar(
  BuildContext context, {
  required String message,
  bool isError = false,
  bool isSuccess = false,
  Duration duration = const Duration(seconds: 3),
}) {
  if (isError) HapticHelper.error();
  if (isSuccess) HapticHelper.success();

  final bgColor = isError ? kRisk : (isSuccess ? kGood : kNavy);
  final icon = isError
      ? Icons.error_outline_rounded
      : (isSuccess ? Icons.check_circle_outline_rounded : Icons.info_outline);

  ScaffoldMessenger.of(context).hideCurrentSnackBar();
  ScaffoldMessenger.of(context).showSnackBar(
    SnackBar(
      content: Row(
        children: [
          Icon(icon, color: Colors.white, size: 20),
          const SizedBox(width: 10),
          Expanded(
            child: Text(
              message,
              style: const TextStyle(color: Colors.white, fontSize: 13.5),
            ),
          ),
        ],
      ),
      backgroundColor: bgColor,
      behavior: SnackBarBehavior.floating,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      duration: duration,
      margin: const EdgeInsets.all(14),
    ),
  );
}

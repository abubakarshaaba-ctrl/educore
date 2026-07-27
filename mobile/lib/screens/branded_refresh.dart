import 'package:flutter/material.dart';

import '../main.dart';

/// Branded pull-to-refresh wrapper with EduCore styling.
///
/// Usage:
/// ```dart
/// BrandedRefresh(
///   onRefresh: () async => _reload(),
///   child: ListView(...),
/// )
/// ```
class BrandedRefresh extends StatelessWidget {
  const BrandedRefresh({
    super.key,
    required this.onRefresh,
    required this.child,
    this.color = kGold,
  });

  final Future<void> Function() onRefresh;
  final Widget child;
  final Color color;

  @override
  Widget build(BuildContext context) {
    return RefreshIndicator(
      onRefresh: onRefresh,
      color: color,
      backgroundColor: Colors.white,
      strokeWidth: 3,
      displacement: 60,
      child: child,
    );
  }
}

/// A branded "pull to refresh" hint that shows briefly at the top of a list.
class RefreshHint extends StatefulWidget {
  const RefreshHint({super.key});

  @override
  State<RefreshHint> createState() => _RefreshHintState();
}

class _RefreshHintState extends State<RefreshHint>
    with SingleTickerProviderStateMixin {
  late AnimationController _controller;

  @override
  void initState() {
    super.initState();
    _controller = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 800),
    )..forward();

    Future.delayed(const Duration(seconds: 2), () {
      if (mounted) _controller.reverse();
    });
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return FadeTransition(
      opacity: _controller,
      child: Container(
        padding: const EdgeInsets.symmetric(vertical: 8),
        alignment: Alignment.center,
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            SizedBox(
              width: 14,
              height: 14,
              child: CircularProgressIndicator(
                strokeWidth: 2,
                color: kGold.withValues(alpha: 0.6),
              ),
            ),
            const SizedBox(width: 8),
            const Text(
              'Refreshing…',
              style: TextStyle(
                color: Color(0xFF667085),
                fontSize: 12,
                fontWeight: FontWeight.w500,
              ),
            ),
          ],
        ),
      ),
    );
  }
}

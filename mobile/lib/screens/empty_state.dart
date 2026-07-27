import 'package:flutter/material.dart';
import '../main.dart';

/// Improved empty state with subtle animation.
///
/// Usage:
/// ```dart
/// const EmptyState(
///   icon: Icons.inbox_outlined,
///   title: 'No items yet',
///   subtitle: 'Items will appear here once created.',
///   actionLabel: 'Create item',
///   onAction: () { ... },
/// )
/// ```
class EmptyState extends StatefulWidget {
  const EmptyState({
    super.key,
    required this.icon,
    required this.title,
    this.subtitle,
    this.actionLabel,
    this.onAction,
    this.iconColor,
  });

  final IconData icon;
  final String title;
  final String? subtitle;
  final String? actionLabel;
  final VoidCallback? onAction;
  final Color? iconColor;

  @override
  State<EmptyState> createState() => _EmptyStateState();
}

class _EmptyStateState extends State<EmptyState>
    with SingleTickerProviderStateMixin {
  late AnimationController _controller;
  late Animation<double> _bounce;

  @override
  void initState() {
    super.initState();
    _controller = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 1200),
    );
    _bounce = Tween<double>(begin: 0, end: 1).animate(
      CurvedAnimation(parent: _controller, curve: Curves.elasticOut),
    );
    _controller.forward();
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 40),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            ScaleTransition(
              scale: _bounce,
              child: Container(
                width: 80,
                height: 80,
                decoration: BoxDecoration(
                  color: (widget.iconColor ?? kMuted).withValues(alpha: 0.08),
                  shape: BoxShape.circle,
                ),
                child: Icon(
                  widget.icon,
                  size: 36,
                  color: widget.iconColor ?? kMuted.withValues(alpha: 0.6),
                ),
              ),
            ),
            const SizedBox(height: 20),
            Text(
              widget.title,
              textAlign: TextAlign.center,
              style: const TextStyle(
                color: kInk,
                fontSize: 17,
                fontWeight: FontWeight.w700,
              ),
            ),
            if (widget.subtitle != null) ...[
              const SizedBox(height: 8),
              Text(
                widget.subtitle!,
                textAlign: TextAlign.center,
                style: const TextStyle(
                  color: kMuted,
                  fontSize: 13.5,
                  height: 1.5,
                ),
              ),
            ],
            if (widget.actionLabel != null && widget.onAction != null) ...[
              const SizedBox(height: 20),
              FilledButton.icon(
                onPressed: widget.onAction,
                icon: const Icon(Icons.add_rounded, size: 18),
                label: Text(widget.actionLabel!),
                style: FilledButton.styleFrom(
                  minimumSize: const Size.fromHeight(46),
                ),
              ),
            ],
          ],
        ),
      ),
    );
  }
}

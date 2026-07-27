import 'package:flutter/material.dart';

import '../main.dart';
import 'haptic_helper.dart';

/// A card wrapper with press feedback (scale + elevation change).
///
/// Usage:
/// ```dart
/// PressableCard(
///   onTap: () { ... },
///   child: ListTile(...),
/// )
/// ```
class PressableCard extends StatefulWidget {
  const PressableCard({
    super.key,
    required this.child,
    this.onTap,
    this.margin,
    this.padding,
    this.borderRadius = 14,
    this.elevation = 0,
    this.enabled = true,
  });

  final Widget child;
  final VoidCallback? onTap;
  final EdgeInsetsGeometry? margin;
  final EdgeInsetsGeometry? padding;
  final double borderRadius;
  final double elevation;
  final bool enabled;

  @override
  State<PressableCard> createState() => _PressableCardState();
}

class _PressableCardState extends State<PressableCard>
    with SingleTickerProviderStateMixin {
  late AnimationController _controller;
  late Animation<double> _scale;

  @override
  void initState() {
    super.initState();
    _controller = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 120),
      reverseDuration: const Duration(milliseconds: 200),
    );
    _scale = Tween<double>(begin: 1.0, end: 0.97).animate(
      CurvedAnimation(parent: _controller, curve: Curves.easeInOut),
    );
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return AnimatedScale(
      scale: _scale.value,
      duration: const Duration(milliseconds: 120),
      child: GestureDetector(
        onTapDown: widget.enabled && widget.onTap != null
            ? (_) => _controller.forward()
            : null,
        onTapUp: widget.enabled && widget.onTap != null
            ? (_) => _controller.reverse()
            : null,
        onTapCancel: widget.enabled && widget.onTap != null
            ? () => _controller.reverse()
            : null,
        onTap: widget.enabled
            ? () {
                HapticHelper.tap();
                widget.onTap?.call();
              }
            : null,
        child: Card(
          elevation: widget.elevation,
          margin: widget.margin ?? const EdgeInsets.only(bottom: 10),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(widget.borderRadius),
            side: const BorderSide(color: Color(0xFFD8E0E8)),
          ),
          child: widget.padding != null
              ? Padding(padding: widget.padding!, child: widget.child)
              : widget.child,
        ),
      ),
    );
  }
}

/// Pressable list tile for consistent tap feedback in lists.
class PressableListTile extends StatelessWidget {
  const PressableListTile({
    super.key,
    this.leading,
    required this.title,
    this.subtitle,
    this.trailing,
    this.onTap,
    this.titleStyle,
  });

  final Widget? leading;
  final String title;
  final String? subtitle;
  final Widget? trailing;
  final VoidCallback? onTap;
  final TextStyle? titleStyle;

  @override
  Widget build(BuildContext context) {
    return PressableCard(
      onTap: onTap,
      child: ListTile(
        leading: leading,
        title: Text(
          title,
          style: titleStyle ??
              const TextStyle(fontWeight: FontWeight.w700, color: kInk),
        ),
        subtitle: subtitle != null
            ? Text(
                subtitle!,
                maxLines: 2,
                overflow: TextOverflow.ellipsis,
                style: const TextStyle(color: kMuted, fontSize: 12),
              )
            : null,
        trailing: trailing ?? const Icon(Icons.chevron_right, color: kMuted),
      ),
    );
  }
}

/// Pressable icon button with subtle animation.
class AnimatedIconButton extends StatefulWidget {
  const AnimatedIconButton({
    super.key,
    required this.icon,
    required this.onPressed,
    this.color,
    this.size = 24,
  });

  final IconData icon;
  final VoidCallback onPressed;
  final Color? color;
  final double size;

  @override
  State<AnimatedIconButton> createState() => _AnimatedIconButtonState();
}

class _AnimatedIconButtonState extends State<AnimatedIconButton>
    with SingleTickerProviderStateMixin {
  late AnimationController _controller;

  @override
  void initState() {
    super.initState();
    _controller = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 200),
    );
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTapDown: (_) => _controller.forward(),
      onTapUp: (_) {
        _controller.reverse();
        HapticHelper.tap();
        widget.onPressed();
      },
      onTapCancel: () => _controller.reverse(),
      child: AnimatedScale(
        scale: 1.0 + (_controller.value * -0.15),
        duration: const Duration(milliseconds: 100),
        child: Icon(widget.icon, size: widget.size, color: widget.color),
      ),
    );
  }
}

import 'package:flutter/material.dart';

/// Skeleton / shimmer loading placeholder used across data screens.
///
/// Place these inside [FutureBuilder] loading states instead of a plain
/// [CircularProgressIndicator] for a more polished feel.
class SkeletonLoader extends StatefulWidget {
  const SkeletonLoader({
    super.key,
    this.lines = 4,
    this.itemHeight = 14,
    this.itemSpacing = 12,
    this.borderRadius = 8,
    this.padding = const EdgeInsets.all(16),
    this.showAvatar = false,
  });

  final int lines;
  final double itemHeight;
  final double itemSpacing;
  final double borderRadius;
  final EdgeInsets padding;
  final bool showAvatar;

  @override
  State<SkeletonLoader> createState() => _SkeletonLoaderState();
}

class _SkeletonLoaderState extends State<SkeletonLoader>
    with SingleTickerProviderStateMixin {
  late AnimationController _controller;
  late Animation<double> _animation;

  @override
  void initState() {
    super.initState();
    _controller = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 1500),
    )..repeat(reverse: true);

    _animation = Tween<double>(begin: 0.3, end: 0.7).animate(
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
    return AnimatedBuilder(
      animation: _controller,
      builder: (context, _) {
        return Padding(
          padding: widget.padding,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              if (widget.showAvatar) ...[
                Row(
                  children: [
                    _shimmerBox(
                      width: 48,
                      height: 48,
                      isCircle: true,
                      opacity: _animation.value,
                    ),
                    const SizedBox(width: 12),
                    Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        _shimmerBox(
                          width: 140,
                          height: widget.itemHeight,
                          opacity: _animation.value,
                        ),
                        const SizedBox(height: 6),
                        _shimmerBox(
                          width: 90,
                          height: 10,
                          opacity: _animation.value,
                        ),
                      ],
                    ),
                  ],
                ),
                const SizedBox(height: 20),
              ],
              ...List.generate(widget.lines, (index) {
                // Vary widths for visual interest
                final widthFraction = index.isEven ? 1.0 : 0.75;
                return Padding(
                  padding: EdgeInsets.only(bottom: widget.itemSpacing),
                  child: _shimmerBox(
                    width: double.infinity,
                    widthFraction: widthFraction,
                    height: widget.itemHeight,
                    opacity: _animation.value,
                  ),
                );
              }),
            ],
          ),
        );
      },
    );
  }

  Widget _shimmerBox({
    required double width,
    required double height,
    required double opacity,
    double widthFraction = 1.0,
    bool isCircle = false,
  }) {
    return AnimatedOpacity(
      duration: const Duration(milliseconds: 300),
      opacity: opacity,
      child: Container(
        width: isCircle ? width : width * widthFraction,
        height: isCircle ? width : height,
        decoration: BoxDecoration(
          color: const Color(0xFFE2E8F0),
          borderRadius: isCircle
              ? BorderRadius.circular(width / 2)
              : BorderRadius.circular(widget.borderRadius),
        ),
      ),
    );
  }
}

/// Card-style skeleton for list items
class SkeletonCard extends StatelessWidget {
  const SkeletonCard({super.key, this.count = 3});

  final int count;

  @override
  Widget build(BuildContext context) {
    return ListView.builder(
      physics: const NeverScrollableScrollPhysics(),
      padding: const EdgeInsets.all(14),
      itemCount: count,
      itemBuilder: (context, index) => Card(
        margin: const EdgeInsets.only(bottom: 10),
        child: ListTile(
          leading: Container(
            width: 44,
            height: 44,
            decoration: BoxDecoration(
              color: const Color(0xFFE2E8F0),
              borderRadius: BorderRadius.circular(22),
            ),
          ),
          title: Container(
            height: 14,
            width: 160,
            decoration: BoxDecoration(
              color: const Color(0xFFE2E8F0),
              borderRadius: BorderRadius.circular(7),
            ),
          ),
          subtitle: Padding(
            padding: const EdgeInsets.only(top: 6),
            child: Container(
              height: 10,
              width: 100,
              decoration: BoxDecoration(
                color: const Color(0xFFE2E8F0),
                borderRadius: BorderRadius.circular(5),
              ),
            ),
          ),
        ),
      ),
    );
  }
}

/// Dashboard metric skeleton
class SkeletonMetrics extends StatelessWidget {
  const SkeletonMetrics({super.key});

  @override
  Widget build(BuildContext context) {
    return GridView.count(
      crossAxisCount: 2,
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      mainAxisSpacing: 10,
      crossAxisSpacing: 10,
      childAspectRatio: 1.55,
      children: List.generate(4, (index) {
        return Container(
          padding: const EdgeInsets.all(14),
          decoration: BoxDecoration(
            color: const Color(0xFFF1F5F9),
            borderRadius: BorderRadius.circular(15),
            border: Border.all(color: const Color(0xFFE2E8F0)),
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Container(
                width: 22,
                height: 22,
                decoration: BoxDecoration(
                  color: const Color(0xFFE2E8F0),
                  borderRadius: BorderRadius.circular(11),
                ),
              ),
              const Spacer(),
              Container(
                height: 20,
                width: 50,
                decoration: BoxDecoration(
                  color: const Color(0xFFE2E8F0),
                  borderRadius: BorderRadius.circular(10),
                ),
              ),
              const SizedBox(height: 4),
              Container(
                height: 10,
                width: 70,
                decoration: BoxDecoration(
                  color: const Color(0xFFE2E8F0),
                  borderRadius: BorderRadius.circular(5),
                ),
              ),
            ],
          ),
        );
      }),
    );
  }
}

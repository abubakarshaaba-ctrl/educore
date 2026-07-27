import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_svg/flutter_svg.dart';

import '../api_client.dart';
import '../portal_router.dart';
import '../push_service.dart';
import 'login_screen.dart';

class SplashScreen extends StatefulWidget {
  const SplashScreen({super.key});

  @override
  State<SplashScreen> createState() => _SplashScreenState();
}

class _SplashScreenState extends State<SplashScreen>
    with SingleTickerProviderStateMixin {
  late AnimationController _controller;
  late Animation<double> _fadeIn;
  late Animation<double> _scaleUp;
  late Animation<Offset> _slideUp;
  late Animation<double> _shimmer;

  @override
  void initState() {
    super.initState();
    _controller = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 2200),
    );

    _fadeIn = Tween<double>(begin: 0.0, end: 1.0).animate(
      CurvedAnimation(
        parent: _controller,
        curve: const Interval(0.0, 0.5, curve: Curves.easeOut),
      ),
    );

    _scaleUp = Tween<double>(begin: 0.6, end: 1.0).animate(
      CurvedAnimation(
        parent: _controller,
        curve: const Interval(0.0, 0.5, curve: Curves.elasticOut),
      ),
    );

    _slideUp = Tween<Offset>(
      begin: const Offset(0, 0.3),
      end: Offset.zero,
    ).animate(
      CurvedAnimation(
        parent: _controller,
        curve: const Interval(0.25, 0.65, curve: Curves.easeOutCubic),
      ),
    );

    _shimmer = Tween<double>(begin: -1.0, end: 2.0).animate(
      CurvedAnimation(
        parent: _controller,
        curve: const Interval(0.4, 0.9, curve: Curves.easeInOut),
      ),
    );

    _controller.forward();
    _navigateAfterDelay();
  }

  Future<void> _navigateAfterDelay() async {
    await Future.delayed(const Duration(milliseconds: 2600));
    if (!mounted) return;

    await ApiClient.instance.restore();
    final isLoggedIn = ApiClient.instance.isLoggedIn;

    // Register for push notifications if logged in
    if (isLoggedIn) {
      PushService.instance.registerForCurrentUser();
    }

    if (!mounted) return;

    Navigator.of(context).pushReplacement(
      PageRouteBuilder(
        transitionDuration: const Duration(milliseconds: 500),
        pageBuilder: (context, animation, secondaryAnimation) {
          return isLoggedIn ? homeForCurrentSession() : const LoginScreen();
        },
        transitionsBuilder: (context, animation, secondaryAnimation, child) {
          return FadeTransition(
            opacity: CurvedAnimation(
              parent: animation,
              curve: Curves.easeInOut,
            ),
            child: child,
          );
        },
      ),
    );
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    SystemChrome.setSystemUIOverlayStyle(const SystemUiOverlayStyle(
      statusBarColor: Colors.transparent,
      statusBarIconBrightness: Brightness.light,
    ));

    return Scaffold(
      body: Container(
        width: double.infinity,
        height: double.infinity,
        decoration: const BoxDecoration(
          gradient: LinearGradient(
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
            colors: [
              Color(0xFF071E45),
              Color(0xFF0A2A5E),
              Color(0xFF0D3A7A),
            ],
          ),
        ),
        child: Stack(
          children: [
            // Background decorative circles
            Positioned(
              top: -80,
              right: -60,
              child: AnimatedBuilder(
                animation: _controller,
                builder: (context, child) {
                  return Opacity(
                    opacity: _fadeIn.value * 0.15,
                    child: Container(
                      width: 250,
                      height: 250,
                      decoration: const BoxDecoration(
                        shape: BoxShape.circle,
                        color: Color(0xFFD79A21),
                      ),
                    ),
                  );
                },
              ),
            ),
            Positioned(
              bottom: -120,
              left: -80,
              child: AnimatedBuilder(
                animation: _controller,
                builder: (context, child) {
                  return Opacity(
                    opacity: _fadeIn.value * 0.1,
                    child: Container(
                      width: 300,
                      height: 300,
                      decoration: const BoxDecoration(
                        shape: BoxShape.circle,
                        color: Color(0xFFD79A21),
                      ),
                    ),
                  );
                },
              ),
            ),

            // Main content
            Center(
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  // Logo with scale + fade animation
                  AnimatedBuilder(
                    animation: _controller,
                    builder: (context, child) {
                      return Transform.scale(
                        scale: _scaleUp.value,
                        child: Opacity(
                          opacity: _fadeIn.value,
                          child: Container(
                            width: 110,
                            height: 110,
                            decoration: BoxDecoration(
                              color: Colors.white,
                              borderRadius: BorderRadius.circular(28),
                              boxShadow: [
                                BoxShadow(
                                  color: const Color(0xFFD79A21).withValues(alpha: 0.3),
                                  blurRadius: 40,
                                  spreadRadius: 8,
                                ),
                              ],
                            ),
                            child: Center(
                              child: SvgPicture.asset(
                                'assets/icon/educore-icon.svg',
                                width: 70,
                                height: 70,
                              ),
                            ),
                          ),
                        ),
                      );
                    },
                  ),

                  const SizedBox(height: 28),

                  // Brand name with slide-up + fade
                  SlideTransition(
                    position: _slideUp,
                    child: FadeTransition(
                      opacity: _fadeIn,
                      child: ShaderMask(
                        shaderCallback: (bounds) {
                          return LinearGradient(
                            colors: const [
                              Colors.white,
                              Color(0xFFD79A21),
                              Colors.white,
                            ],
                            stops: [
                              0.0,
                              _shimmer.value.clamp(0.0, 1.0),
                              1.0,
                            ],
                          ).createShader(bounds);
                        },
                        child: RichText(
                          text: const TextSpan(
                            style: TextStyle(
                              fontSize: 38,
                              fontWeight: FontWeight.w800,
                              letterSpacing: 1.5,
                            ),
                            children: [
                              TextSpan(
                                text: 'Edu',
                                style: TextStyle(color: Colors.white),
                              ),
                              TextSpan(
                                text: 'Core',
                                style: TextStyle(color: Color(0xFFD79A21)),
                              ),
                            ],
                          ),
                        ),
                      ),
                    ),
                  ),

                  const SizedBox(height: 10),

                  // Tagline with delay
                  AnimatedBuilder(
                    animation: _controller,
                    builder: (context, child) {
                      final taglineOpacity = Curves.easeOut.transform(
                        (_controller.value - 0.4).clamp(0.0, 1.0),
                      );
                      return Opacity(
                        opacity: taglineOpacity,
                        child: const Text(
                          'Unified School Management',
                          style: TextStyle(
                            color: Color(0xFFCFDCF0),
                            fontSize: 15,
                            fontWeight: FontWeight.w500,
                            letterSpacing: 0.5,
                          ),
                        ),
                      );
                    },
                  ),

                  const SizedBox(height: 60),

                  // Loading indicator
                  AnimatedBuilder(
                    animation: _controller,
                    builder: (context, child) {
                      final indicatorOpacity = Curves.easeIn.transform(
                        (_controller.value - 0.6).clamp(0.0, 1.0),
                      );
                      return Opacity(
                        opacity: indicatorOpacity,
                        child: SizedBox(
                          width: 24,
                          height: 24,
                          child: CircularProgressIndicator(
                            strokeWidth: 2.5,
                            color: const Color(0xFFD79A21).withValues(alpha: 0.7),
                          ),
                        ),
                      );
                    },
                  ),
                ],
              ),
            ),

            // Version tag at bottom
            Positioned(
              bottom: 40,
              left: 0,
              right: 0,
              child: AnimatedBuilder(
                animation: _controller,
                builder: (context, child) {
                  final bottomOpacity = Curves.easeOut.transform(
                    (_controller.value - 0.5).clamp(0.0, 1.0),
                  );
                  return Opacity(
                    opacity: bottomOpacity,
                    child: const Text(
                      'v1.0.0',
                      textAlign: TextAlign.center,
                      style: TextStyle(
                        color: Colors.white38,
                        fontSize: 12,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                  );
                },
              ),
            ),
          ],
        ),
      ),
    );
  }
}

import 'package:flutter/material.dart';
import 'package:flutter_svg/flutter_svg.dart';

import '../api_client.dart';
import '../main.dart';
import '../push_service.dart';
import 'home_screen.dart';

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _loginId = TextEditingController();
  final _password = TextEditingController();
  bool _busy = false;
  bool _obscure = true;
  String? _error;

  Future<void> _submit() async {
    if (_loginId.text.trim().isEmpty || _password.text.isEmpty) {
      setState(() => _error = 'Enter your staff ID or email and password.');
      return;
    }
    setState(() {
      _busy = true;
      _error = null;
    });
    try {
      await ApiClient.instance.login(_loginId.text.trim(), _password.text);
      PushService.instance.registerForCurrentUser();
      if (!mounted) return;
      Navigator.of(context).pushReplacement(
        MaterialPageRoute(builder: (_) => const HomeScreen()),
      );
    } catch (e) {
      setState(() => _error = e.toString());
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: kNavy,
      body: SafeArea(
        child: Center(
          child: SingleChildScrollView(
            padding: const EdgeInsets.all(24),
            child: ConstrainedBox(
              constraints: const BoxConstraints(maxWidth: 420),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  SvgPicture.asset(
                    'assets/icon/educore-icon-light.svg',
                    width: 96,
                    height: 96,
                  ),
                  const SizedBox(height: 14),
                  RichText(
                    textAlign: TextAlign.center,
                    text: const TextSpan(
                      style: TextStyle(
                        fontSize: 32,
                        fontWeight: FontWeight.w800,
                        letterSpacing: 1,
                      ),
                      children: [
                        TextSpan(text: 'Edu', style: TextStyle(color: Colors.white)),
                        TextSpan(text: 'Core', style: TextStyle(color: kGold)),
                      ],
                    ),
                  ),
                  const SizedBox(height: 36),
                  Container(
                    padding: const EdgeInsets.all(20),
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(16),
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.stretch,
                      children: [
                        const Text(
                          'Welcome back',
                          style: TextStyle(
                            fontSize: 22,
                            fontWeight: FontWeight.w800,
                            color: kNavy,
                          ),
                        ),
                        const SizedBox(height: 4),
                        const Text(
                          'Sign in with your staff ID or school email.',
                          style: TextStyle(color: kMuted, fontSize: 13),
                        ),
                        const SizedBox(height: 20),
                        if (_error != null) ...[
                          Container(
                            padding: const EdgeInsets.all(10),
                            decoration: BoxDecoration(
                              color: const Color(0xFFFEF3F2),
                              borderRadius: BorderRadius.circular(8),
                              border: Border.all(color: const Color(0xFFFECDCA)),
                            ),
                            child: Text(
                              _error!,
                              style: const TextStyle(color: kRisk, fontSize: 13),
                            ),
                          ),
                          const SizedBox(height: 14),
                        ],
                        TextField(
                          controller: _loginId,
                          keyboardType: TextInputType.emailAddress,
                          autocorrect: false,
                          decoration: const InputDecoration(
                            labelText: 'Staff ID or Email',
                          ),
                        ),
                        const SizedBox(height: 14),
                        TextField(
                          controller: _password,
                          obscureText: _obscure,
                          onSubmitted: (_) => _submit(),
                          decoration: InputDecoration(
                            labelText: 'Password',
                            suffixIcon: IconButton(
                              icon: Icon(
                                  _obscure ? Icons.visibility : Icons.visibility_off),
                              onPressed: () => setState(() => _obscure = !_obscure),
                            ),
                          ),
                        ),
                        const SizedBox(height: 20),
                        FilledButton(
                          onPressed: _busy ? null : _submit,
                          child: _busy
                              ? const SizedBox(
                                  width: 22,
                                  height: 22,
                                  child: CircularProgressIndicator(strokeWidth: 2.5),
                                )
                              : const Text('Sign in'),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 22),
                  const _SupportFooter(),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }
}

class _SupportFooter extends StatelessWidget {
  const _SupportFooter();

  @override
  Widget build(BuildContext context) {
    return const Column(
      children: [
        Wrap(
          alignment: WrapAlignment.center,
          crossAxisAlignment: WrapCrossAlignment.center,
          spacing: 10,
          runSpacing: 4,
          children: [
            Text('07065595768', style: TextStyle(color: Colors.white70, fontSize: 12)),
            Text('|', style: TextStyle(color: Colors.white30, fontSize: 12)),
            Text('WhatsApp: +2347065595768', style: TextStyle(color: Colors.white70, fontSize: 12)),
            Text('|', style: TextStyle(color: Colors.white30, fontSize: 12)),
            Text('support@educoreng.online', style: TextStyle(color: Colors.white70, fontSize: 12)),
          ],
        ),
        SizedBox(height: 8),
        Text(
          'EduCore Education Technology © 2026',
          style: TextStyle(color: Colors.white38, fontSize: 11.5, fontWeight: FontWeight.w600),
        ),
      ],
    );
  }
}

import 'dart:io';
import 'dart:ui' as ui;

import 'package:flutter/material.dart';
import 'package:flutter/rendering.dart';
import 'package:image_picker/image_picker.dart';
import 'package:path_provider/path_provider.dart';
import 'package:qr_flutter/qr_flutter.dart';
import 'package:share_plus/share_plus.dart';

import '../api_client.dart';
import '../main.dart';

class IdCardScreen extends StatefulWidget {
  const IdCardScreen({super.key});

  @override
  State<IdCardScreen> createState() => _IdCardScreenState();
}

class _IdCardScreenState extends State<IdCardScreen> {
  late Future<Map<String, dynamic>> _future;
  final GlobalKey _cardKey = GlobalKey();
  bool _saving = false;
  bool _uploading = false;
  bool _showBack = false;

  @override
  void initState() {
    super.initState();
    _future = ApiClient.instance.get('/id-card');
  }

  Future<void> _uploadPhoto() async {
    final picked = await ImagePicker().pickImage(
      source: ImageSource.gallery,
      maxWidth: 1200,
      imageQuality: 88,
    );
    if (picked == null) return;

    setState(() => _uploading = true);
    try {
      await ApiClient.instance.upload('/id-card/photo', 'photo', picked.path);
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(
        content: Text('Passport photo updated.'),
        backgroundColor: kGood,
      ));
      setState(() => _future = ApiClient.instance.get('/id-card'));
    } catch (error) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('$error'), backgroundColor: kRisk),
        );
      }
    } finally {
      if (mounted) setState(() => _uploading = false);
    }
  }

  Future<void> _shareCard() async {
    setState(() => _saving = true);
    try {
      final boundary =
          _cardKey.currentContext!.findRenderObject() as RenderRepaintBoundary;
      final image = await boundary.toImage(pixelRatio: 3);
      final data = await image.toByteData(format: ui.ImageByteFormat.png);
      final directory = await getTemporaryDirectory();
      final face = _showBack ? 'back' : 'front';
      final file = File('${directory.path}/educore-staff-id-$face.png');
      await file.writeAsBytes(data!.buffer.asUint8List(), flush: true);
      await Share.shareXFiles(
        [XFile(file.path)],
        text: 'My EduCore staff identity card',
      );
    } catch (error) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Could not prepare card: $error')),
        );
      }
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  @override
  Widget build(BuildContext context) => Scaffold(
        appBar: AppBar(title: const Text('Staff ID Card')),
        body: FutureBuilder<Map<String, dynamic>>(
          future: _future,
          builder: (context, snapshot) {
            if (snapshot.connectionState != ConnectionState.done) {
              return const Center(child: CircularProgressIndicator());
            }
            if (snapshot.hasError) {
              return Center(
                child: Padding(
                  padding: const EdgeInsets.all(24),
                  child: Text(
                    snapshot.error.toString(),
                    textAlign: TextAlign.center,
                    style: const TextStyle(color: kMuted),
                  ),
                ),
              );
            }

            final data = snapshot.data!;
            final school = (data['school'] as Map<dynamic, dynamic>)
                .cast<String, dynamic>();

            return ListView(
              padding: const EdgeInsets.fromLTRB(16, 18, 16, 28),
              children: [
                Center(
                  child: RepaintBoundary(
                    key: _cardKey,
                    child: ConstrainedBox(
                      constraints: const BoxConstraints(maxWidth: 360),
                      child: AspectRatio(
                        aspectRatio: 54 / 86,
                        child: _showBack
                            ? _StaffCardBack(data: data, school: school)
                            : _StaffCardFront(data: data, school: school),
                      ),
                    ),
                  ),
                ),
                const SizedBox(height: 12),
                Center(
                  child: SegmentedButton<bool>(
                    segments: const [
                      ButtonSegment(
                          value: false,
                          icon: Icon(Icons.badge_outlined),
                          label: Text('Front')),
                      ButtonSegment(
                          value: true,
                          icon: Icon(Icons.qr_code_2_rounded),
                          label: Text('Back')),
                    ],
                    selected: {_showBack},
                    onSelectionChanged: (selection) =>
                        setState(() => _showBack = selection.first),
                  ),
                ),
                const SizedBox(height: 12),
                OutlinedButton.icon(
                  onPressed: _uploading ? null : _uploadPhoto,
                  icon: _uploading
                      ? const SizedBox(
                          width: 18,
                          height: 18,
                          child: CircularProgressIndicator(strokeWidth: 2.4),
                        )
                      : const Icon(Icons.add_a_photo_outlined),
                  label: Text(
                    _uploading
                        ? 'Uploading…'
                        : data['has_photo'] == true
                            ? 'Change passport photo'
                            : 'Upload passport photo',
                  ),
                ),
                const SizedBox(height: 9),
                FilledButton.icon(
                  onPressed: _saving ? null : _shareCard,
                  icon: const Icon(Icons.ios_share_rounded),
                  label:
                      Text(_saving ? 'Preparing…' : 'Save / Share this side'),
                ),
              ],
            );
          },
        ),
      );
}

class _StaffCardFront extends StatelessWidget {
  const _StaffCardFront({required this.data, required this.school});
  final Map<String, dynamic> data;
  final Map<String, dynamic> school;

  @override
  Widget build(BuildContext context) => _CardShell(
        child: LayoutBuilder(
          builder: (context, constraints) {
            final width = constraints.maxWidth;
            return Stack(
              children: [
                Positioned.fill(
                  child: Container(
                    color: Colors.white,
                    child: CustomPaint(painter: _WatermarkPainter()),
                  ),
                ),
                Positioned(
                  left: 0,
                  right: 0,
                  top: 0,
                  height: constraints.maxHeight * .34,
                  child: ClipPath(
                    clipper: _AngledBottomClipper(),
                    child: Container(
                      padding:
                          EdgeInsets.fromLTRB(22, width * .13, 22, width * .08),
                      decoration: const BoxDecoration(
                        gradient: LinearGradient(
                          colors: [kNavy, Color(0xFF04142F)],
                          begin: Alignment.topLeft,
                          end: Alignment.bottomRight,
                        ),
                      ),
                      child: Column(
                        children: [
                          Text(
                            (school['name']?.toString() ?? 'EduCore School')
                                .toUpperCase(),
                            textAlign: TextAlign.center,
                            maxLines: 2,
                            overflow: TextOverflow.ellipsis,
                            style: TextStyle(
                              color: Colors.white,
                              fontFamily: 'serif',
                              height: 1.02,
                              fontSize: width * .069,
                              fontWeight: FontWeight.w900,
                              letterSpacing: .4,
                            ),
                          ),
                          const SizedBox(height: 5),
                          Text(
                            school['motto']?.toString().isNotEmpty == true
                                ? school['motto'].toString()
                                : 'Excellence through education',
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                            style: TextStyle(
                              color: const Color(0xFFF6D88E),
                              fontSize: width * .027,
                              fontWeight: FontWeight.w700,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                ),
                Positioned(
                  left: 0,
                  right: 0,
                  top: constraints.maxHeight * .302,
                  height: 6,
                  child: Transform.rotate(
                    angle: -.105,
                    child: Container(color: kGold),
                  ),
                ),
                Positioned(
                  top: constraints.maxHeight * .225,
                  left: width * .275,
                  width: width * .45,
                  height: width * .45,
                  child: _Portrait(data: data),
                ),
                Positioned(
                  left: 22,
                  right: 22,
                  top: constraints.maxHeight * .49,
                  bottom: constraints.maxHeight * .095,
                  child: Column(
                    children: [
                      Text(
                        (data['name']?.toString() ?? 'Staff Name')
                            .toUpperCase(),
                        maxLines: 2,
                        textAlign: TextAlign.center,
                        overflow: TextOverflow.ellipsis,
                        style: TextStyle(
                          color: kNavy,
                          fontSize: width * .067,
                          height: 1.0,
                          fontWeight: FontWeight.w900,
                        ),
                      ),
                      const SizedBox(height: 4),
                      Text(
                        (data['role']?.toString() ?? 'Staff').toUpperCase(),
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: TextStyle(
                          color: kGold,
                          fontSize: width * .035,
                          fontWeight: FontWeight.w900,
                          letterSpacing: 1.2,
                        ),
                      ),
                      const SizedBox(height: 7),
                      const _OrnamentRule(),
                      const Spacer(),
                      _IdentityRow(
                        icon: Icons.badge_outlined,
                        label: 'Staff ID',
                        value: data['staff_id']?.toString() ?? '—',
                      ),
                      _IdentityRow(
                        icon: Icons.work_outline_rounded,
                        label: 'Department',
                        value: data['department']?.toString() ??
                            'School Administration',
                      ),
                      _IdentityRow(
                        icon: Icons.calendar_month_outlined,
                        label: 'Date of joining',
                        value: data['date_joined']?.toString() ?? '—',
                      ),
                    ],
                  ),
                ),
                const Positioned(
                  left: 0,
                  right: 0,
                  bottom: 0,
                  height: 48,
                  child: DecoratedBox(
                    decoration: BoxDecoration(
                      color: kNavy,
                      border: Border(top: BorderSide(color: kGold, width: 5)),
                    ),
                    child: Center(
                      child: Text(
                        'S T A F F',
                        style: TextStyle(
                            color: Colors.white,
                            fontWeight: FontWeight.w900,
                            letterSpacing: 4),
                      ),
                    ),
                  ),
                ),
                const _LanyardSlot(),
              ],
            );
          },
        ),
      );
}

class _StaffCardBack extends StatelessWidget {
  const _StaffCardBack({required this.data, required this.school});
  final Map<String, dynamic> data;
  final Map<String, dynamic> school;

  @override
  Widget build(BuildContext context) => _CardShell(
        child: LayoutBuilder(
          builder: (context, constraints) {
            final width = constraints.maxWidth;
            return Stack(
              children: [
                Positioned.fill(
                  child: Container(
                    decoration: const BoxDecoration(
                      gradient: LinearGradient(
                        colors: [Colors.white, Color(0xFFF1F4F8)],
                        begin: Alignment.topLeft,
                        end: Alignment.bottomRight,
                      ),
                    ),
                  ),
                ),
                Positioned(
                  left: 20,
                  right: 20,
                  top: width * .14,
                  child: Column(
                    children: [
                      _SchoolLogo(school: school, size: width * .15),
                      const SizedBox(height: 5),
                      Text(
                        (school['name']?.toString() ?? 'EduCore School')
                            .toUpperCase(),
                        textAlign: TextAlign.center,
                        maxLines: 2,
                        overflow: TextOverflow.ellipsis,
                        style: TextStyle(
                          color: kNavy,
                          fontFamily: 'serif',
                          fontSize: width * .056,
                          height: 1.0,
                          fontWeight: FontWeight.w900,
                        ),
                      ),
                      const SizedBox(height: 8),
                      const _OrnamentRule(),
                      const SizedBox(height: 10),
                      Container(
                        padding: const EdgeInsets.all(8),
                        decoration: BoxDecoration(
                          color: Colors.white,
                          border: Border.all(color: kNavy, width: 3),
                          borderRadius: BorderRadius.circular(12),
                        ),
                        child: QrImageView(
                          data: data['qr_payload']?.toString() ?? '',
                          size: width * .39,
                          padding: EdgeInsets.zero,
                        ),
                      ),
                      const SizedBox(height: 7),
                      Text(
                        'Scan this QR code for',
                        style: TextStyle(
                            color: kNavy,
                            fontSize: width * .03,
                            fontWeight: FontWeight.w700),
                      ),
                      Text(
                        'STAFF ATTENDANCE',
                        style: TextStyle(
                            color: kGold,
                            fontSize: width * .038,
                            fontWeight: FontWeight.w900,
                            letterSpacing: .7),
                      ),
                    ],
                  ),
                ),
                Positioned(
                  left: 0,
                  right: 0,
                  bottom: 0,
                  height: constraints.maxHeight * .315,
                  child: ClipPath(
                    clipper: _ChevronTopClipper(),
                    child: Container(
                      padding: EdgeInsets.fromLTRB(
                          width * .10, width * .12, width * .10, width * .04),
                      decoration: const BoxDecoration(
                        gradient:
                            LinearGradient(colors: [kNavy, Color(0xFF04142F)]),
                      ),
                      child: Column(
                        children: [
                          Text('If found, please return to:',
                              style: TextStyle(
                                  color: const Color(0xFFCBD6E5),
                                  fontSize: width * .026)),
                          Text(
                            school['name']?.toString() ?? 'School office',
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                            style: TextStyle(
                                color: Colors.white,
                                fontSize: width * .034,
                                fontWeight: FontWeight.w900),
                          ),
                          Text(
                            school['address']?.toString() ??
                                'School administration office',
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                            style: TextStyle(
                                color: const Color(0xFFCBD6E5),
                                fontSize: width * .024),
                          ),
                          const Spacer(),
                          _ContactLine(
                              icon: Icons.phone_outlined,
                              value: school['phone']?.toString()),
                          _ContactLine(
                              icon: Icons.email_outlined,
                              value: school['email']?.toString()),
                          _ContactLine(
                              icon: Icons.language_outlined,
                              value: school['website']?.toString()),
                          const Spacer(),
                          Container(height: 1, color: Colors.white24),
                          const SizedBox(height: 5),
                          Text(
                            'This card is the property of the issuing school and must be returned on request or at the end of employment.',
                            textAlign: TextAlign.center,
                            maxLines: 2,
                            overflow: TextOverflow.ellipsis,
                            style: TextStyle(
                                color: const Color(0xFFB8C7DA),
                                fontSize: width * .021,
                                height: 1.25),
                          ),
                        ],
                      ),
                    ),
                  ),
                ),
                const _LanyardSlot(),
              ],
            );
          },
        ),
      );
}

class _CardShell extends StatelessWidget {
  const _CardShell({required this.child});
  final Widget child;

  @override
  Widget build(BuildContext context) => Container(
        clipBehavior: Clip.antiAlias,
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(22),
          border: Border.all(color: const Color(0x26071E45)),
          boxShadow: const [
            BoxShadow(
                color: Color(0x32071E45),
                blurRadius: 28,
                offset: Offset(0, 14)),
          ],
        ),
        child: child,
      );
}

class _Portrait extends StatelessWidget {
  const _Portrait({required this.data});
  final Map<String, dynamic> data;

  @override
  Widget build(BuildContext context) {
    final hasPhoto = data['has_photo'] == true;
    final version = data['photo_version']?.toString() ?? '';
    return Container(
      padding: const EdgeInsets.all(6),
      decoration: BoxDecoration(
        color: Colors.white,
        shape: BoxShape.circle,
        border: Border.all(color: kNavy, width: 3),
        boxShadow: const [
          BoxShadow(
              color: Color(0x30071E45), blurRadius: 12, offset: Offset(0, 5)),
        ],
      ),
      child: ClipOval(
        child: hasPhoto
            ? Image.network(
                ApiClient.instance.url('/id-card/photo-file?v=$version'),
                headers: ApiClient.instance.imageHeaders,
                fit: BoxFit.cover,
                errorBuilder: (_, __, ___) => _PortraitFallback(data: data),
              )
            : _PortraitFallback(data: data),
      ),
    );
  }
}

class _PortraitFallback extends StatelessWidget {
  const _PortraitFallback({required this.data});
  final Map<String, dynamic> data;

  @override
  Widget build(BuildContext context) => Container(
        color: const Color(0xFFD7DCE4),
        alignment: Alignment.center,
        child: Text(
          (data['name']?.toString() ?? 'S').substring(0, 1).toUpperCase(),
          style: const TextStyle(
              color: kNavy, fontSize: 48, fontWeight: FontWeight.w900),
        ),
      );
}

class _IdentityRow extends StatelessWidget {
  const _IdentityRow(
      {required this.icon, required this.label, required this.value});
  final IconData icon;
  final String label;
  final String value;

  @override
  Widget build(BuildContext context) => Padding(
        padding: const EdgeInsets.only(bottom: 7),
        child: Row(
          children: [
            Container(
              width: 31,
              height: 31,
              decoration: BoxDecoration(
                  color: kNavy, borderRadius: BorderRadius.circular(9)),
              child: Icon(icon, color: kGold, size: 17),
            ),
            const SizedBox(width: 10),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(label.toUpperCase(),
                      style: const TextStyle(
                          color: kNavy,
                          fontSize: 8.5,
                          letterSpacing: .7,
                          fontWeight: FontWeight.w900)),
                  Text(value,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(
                          color: kInk,
                          fontSize: 11.5,
                          fontWeight: FontWeight.w700)),
                ],
              ),
            ),
          ],
        ),
      );
}

class _ContactLine extends StatelessWidget {
  const _ContactLine({required this.icon, required this.value});
  final IconData icon;
  final String? value;

  @override
  Widget build(BuildContext context) {
    if ((value ?? '').isEmpty) return const SizedBox.shrink();
    return Padding(
      padding: const EdgeInsets.only(bottom: 2),
      child: Row(
        children: [
          Icon(icon, color: kGold, size: 13),
          const SizedBox(width: 7),
          Expanded(
            child: Text(value!,
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: const TextStyle(color: Colors.white, fontSize: 9.5)),
          ),
        ],
      ),
    );
  }
}

class _SchoolLogo extends StatelessWidget {
  const _SchoolLogo({required this.school, required this.size});
  final Map<String, dynamic> school;
  final double size;

  @override
  Widget build(BuildContext context) {
    final logo = school['logo']?.toString();
    return SizedBox(
      width: size,
      height: size,
      child: logo == null || logo.isEmpty
          ? Image.asset('assets/icon/educore-icon.png',
              errorBuilder: (_, __, ___) =>
                  const Icon(Icons.school_rounded, color: kNavy))
          : Image.network(logo,
              fit: BoxFit.contain,
              errorBuilder: (_, __, ___) =>
                  const Icon(Icons.school_rounded, color: kNavy)),
    );
  }
}

class _OrnamentRule extends StatelessWidget {
  const _OrnamentRule();

  @override
  Widget build(BuildContext context) => Row(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Container(width: 58, height: 1, color: kNavy),
          Container(
              margin: const EdgeInsets.symmetric(horizontal: 6),
              width: 8,
              height: 8,
              decoration:
                  const BoxDecoration(color: kGold, shape: BoxShape.circle)),
          Container(width: 58, height: 1, color: kNavy),
        ],
      );
}

class _LanyardSlot extends StatelessWidget {
  const _LanyardSlot();

  @override
  Widget build(BuildContext context) => Positioned(
        left: 0,
        right: 0,
        top: 13,
        child: Center(
          child: Container(
            width: 76,
            height: 18,
            decoration: BoxDecoration(
              color: const Color(0xFFF8FAFC),
              borderRadius: BorderRadius.circular(12),
              boxShadow: const [
                BoxShadow(
                    color: Color(0x30071E45),
                    blurRadius: 5,
                    offset: Offset(0, 2)),
              ],
            ),
          ),
        ),
      );
}

class _AngledBottomClipper extends CustomClipper<Path> {
  @override
  Path getClip(Size size) => Path()
    ..lineTo(size.width, 0)
    ..lineTo(size.width, size.height * .76)
    ..lineTo(size.width * .64, size.height)
    ..lineTo(0, size.height * .76)
    ..close();

  @override
  bool shouldReclip(covariant CustomClipper<Path> oldClipper) => false;
}

class _ChevronTopClipper extends CustomClipper<Path> {
  @override
  Path getClip(Size size) => Path()
    ..moveTo(0, size.height * .12)
    ..lineTo(size.width * .5, 0)
    ..lineTo(size.width, size.height * .12)
    ..lineTo(size.width, size.height)
    ..lineTo(0, size.height)
    ..close();

  @override
  bool shouldReclip(covariant CustomClipper<Path> oldClipper) => false;
}

class _WatermarkPainter extends CustomPainter {
  @override
  void paint(Canvas canvas, Size size) {
    final paint = Paint()
      ..color = const Color(0x08071E45)
      ..style = PaintingStyle.stroke
      ..strokeWidth = 1;
    for (double offset = -size.height; offset < size.width; offset += 38) {
      canvas.drawLine(
          Offset(offset, 0), Offset(offset + size.height, size.height), paint);
    }
  }

  @override
  bool shouldRepaint(covariant CustomPainter oldDelegate) => false;
}

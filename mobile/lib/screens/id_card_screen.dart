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
    final picker = ImagePicker();
    final picked = await picker.pickImage(
      source: ImageSource.gallery,
      maxWidth: 1000,
      imageQuality: 85,
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
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(e.toString()), backgroundColor: kRisk));
    } finally {
      if (mounted) setState(() => _uploading = false);
    }
  }

  Future<void> _saveCard() async {
    setState(() => _saving = true);
    try {
      final boundary =
          _cardKey.currentContext!.findRenderObject() as RenderRepaintBoundary;
      final image = await boundary.toImage(pixelRatio: 3);
      final data = await image.toByteData(format: ui.ImageByteFormat.png);
      final dir = await getTemporaryDirectory();
      final file = File('${dir.path}/educore-id-card.png');
      await file.writeAsBytes(data!.buffer.asUint8List(), flush: true);
      await Share.shareXFiles([XFile(file.path)],
          text: 'My EduCore staff ID card');
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text('Could not save card: $e')));
      }
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Staff ID Card')),
      body: FutureBuilder<Map<String, dynamic>>(
        future: _future,
        builder: (context, snap) {
          if (snap.connectionState != ConnectionState.done) {
            return const Center(child: CircularProgressIndicator());
          }
          if (snap.hasError) {
            return Center(
                child: Text(snap.error.toString(),
                    style: const TextStyle(color: kMuted)));
          }
          final d = snap.data!;
          final school = (d['school'] as Map).cast<String, dynamic>();
          return SingleChildScrollView(
            padding: const EdgeInsets.all(20),
            child: Column(
              children: [
                RepaintBoundary(
                  key: _cardKey,
                  child: _showBack ? _cardBack(school) : _card(d, school),
                ),
                const SizedBox(height: 10),
                TextButton.icon(
                  onPressed: () => setState(() => _showBack = !_showBack),
                  icon: const Icon(Icons.flip_to_back_rounded, size: 18),
                  label: Text(_showBack ? 'Show front' : 'Show back'),
                ),
                const SizedBox(height: 10),
                SizedBox(
                  width: double.infinity,
                  child: OutlinedButton.icon(
                    style: OutlinedButton.styleFrom(
                      minimumSize: const Size.fromHeight(48),
                      side: const BorderSide(color: kNavy),
                      foregroundColor: kNavy,
                    ),
                    onPressed: _uploading ? null : _uploadPhoto,
                    icon: _uploading
                        ? const SizedBox(
                            width: 18,
                            height: 18,
                            child: CircularProgressIndicator(strokeWidth: 2.5))
                        : const Icon(Icons.add_a_photo_outlined),
                    label: Text(
                      _uploading
                          ? 'Uploading…'
                          : (d['has_photo'] != true
                              ? 'Upload passport photo'
                              : 'Change passport photo'),
                    ),
                  ),
                ),
                const SizedBox(height: 10),
                SizedBox(
                  width: double.infinity,
                  child: FilledButton.icon(
                    onPressed: _saving ? null : _saveCard,
                    icon: const Icon(Icons.download_rounded),
                    label: Text(_saving ? 'Preparing…' : 'Save / Share card'),
                  ),
                ),
              ],
            ),
          );
        },
      ),
    );
  }

  Widget _card(Map<String, dynamic> d, Map<String, dynamic> school) {
    return Container(
      width: double.infinity,
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(18),
        gradient: const LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [Color(0xFF0A2550), kNavy],
        ),
      ),
      child: Column(
        children: [
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
            decoration: const BoxDecoration(
              border: Border(
                  bottom: BorderSide(color: Color(0x33FFFFFF), width: 1)),
            ),
            child: Row(
              children: [
                Container(
                  width: 42,
                  height: 42,
                  padding: const EdgeInsets.all(4),
                  decoration: BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.circular(9),
                  ),
                  child: (school['logo'] != null)
                      ? Image.network(school['logo'] as String,
                          fit: BoxFit.contain,
                          errorBuilder: (_, __, ___) =>
                              const Icon(Icons.school_rounded, color: kNavy))
                      : const Icon(Icons.school_rounded, color: kNavy),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        (school['name'] as String?) ?? 'EduCore',
                        style: const TextStyle(
                            color: Colors.white,
                            fontWeight: FontWeight.w800,
                            fontSize: 15,
                            height: 1.15),
                      ),
                      const Text('STAFF IDENTITY CARD',
                          style: TextStyle(
                              color: kGold,
                              fontSize: 8.5,
                              fontWeight: FontWeight.w700,
                              letterSpacing: 1.5)),
                    ],
                  ),
                ),
              ],
            ),
          ),
          Padding(
            padding: const EdgeInsets.all(20),
            child: Column(
              children: [
                Builder(builder: (context) {
                  final hasPhoto = d['has_photo'] == true;
                  final version = (d['photo_version'] as String?) ?? '';
                  final photoUrl = ApiClient.instance
                      .url('/id-card/photo-file?v=$version');
                  Widget fallback(String label) => Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Text(
                            ((d['name'] as String?) ?? 'S')
                                .substring(0, 1)
                                .toUpperCase(),
                            style: const TextStyle(
                                color: kNavy,
                                fontSize: 40,
                                fontWeight: FontWeight.w800),
                          ),
                          Text(label,
                              style:
                                  const TextStyle(color: kMuted, fontSize: 10)),
                        ],
                      );
                  return Container(
                    width: 104,
                    height: 128,
                    clipBehavior: Clip.antiAlias,
                    decoration: BoxDecoration(
                      borderRadius: BorderRadius.circular(8),
                      color: Colors.white,
                      border: Border.all(color: kGold, width: 3),
                    ),
                    alignment: Alignment.center,
                    child: hasPhoto
                        ? Image.network(
                            photoUrl,
                            headers: ApiClient.instance.imageHeaders,
                            fit: BoxFit.cover,
                            width: 104,
                            height: 128,
                            loadingBuilder: (c, child, progress) =>
                                progress == null
                                    ? child
                                    : const Center(
                                        child: SizedBox(
                                            width: 20,
                                            height: 20,
                                            child: CircularProgressIndicator(
                                                strokeWidth: 2))),
                            errorBuilder: (c, e, s) => fallback('Photo failed'),
                          )
                        : fallback('No photo'),
                  );
                }),
                const SizedBox(height: 12),
                Text(d['name'] as String? ?? '—',
                    style: const TextStyle(
                        color: Colors.white,
                        fontSize: 19,
                        fontWeight: FontWeight.w800)),
                Text(d['role'] as String? ?? 'Staff',
                    style: const TextStyle(color: kGold, fontSize: 13)),
                const SizedBox(height: 16),
                Container(
                  padding: const EdgeInsets.all(10),
                  decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(12)),
                  child: QrImageView(
                    data: d['qr_payload'] as String? ?? '',
                    size: 150,
                    padding: EdgeInsets.zero,
                  ),
                ),
                const SizedBox(height: 12),
                _kv('Staff ID', d['staff_id'] as String? ?? '—'),
                if ((d['phone'] as String?)?.isNotEmpty ?? false)
                  _kv('Phone', d['phone'] as String),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _cardBack(Map<String, dynamic> school) {
    return Container(
      width: double.infinity,
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(18),
        gradient: const LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [Color(0xFF0A2550), kNavy],
        ),
      ),
      padding: const EdgeInsets.all(20),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                width: 40,
                height: 40,
                padding: const EdgeInsets.all(4),
                decoration: BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.circular(9)),
                child: (school['logo'] != null)
                    ? Image.network(school['logo'] as String,
                        fit: BoxFit.contain,
                        errorBuilder: (_, __, ___) =>
                            const Icon(Icons.school_rounded, color: kNavy))
                    : const Icon(Icons.school_rounded, color: kNavy),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Text(
                  (school['name'] as String?) ?? 'School',
                  style: const TextStyle(
                      color: Colors.white,
                      fontWeight: FontWeight.w800,
                      fontSize: 15),
                ),
              ),
            ],
          ),
          const SizedBox(height: 16),
          const Text('CONDITIONS OF USE',
              style: TextStyle(
                  color: kGold,
                  fontSize: 10,
                  fontWeight: FontWeight.w800,
                  letterSpacing: 1.5)),
          const SizedBox(height: 8),
          ...[
            'This card is the property of the issuing school and must be presented on request.',
            'It is non-transferable and valid only for the named staff member.',
            'The QR code is for official attendance and identity verification only.',
            'If found, please return to the school administration office.',
          ].map((t) => Padding(
                padding: const EdgeInsets.only(bottom: 8),
                child: Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Padding(
                      padding: EdgeInsets.only(top: 5, right: 8),
                      child: Icon(Icons.circle, size: 5, color: kGold),
                    ),
                    Expanded(
                      child: Text(t,
                          style: const TextStyle(
                              color: Color(0xCCFFFFFF),
                              fontSize: 12,
                              height: 1.45)),
                    ),
                  ],
                ),
              )),
          const SizedBox(height: 6),
          if ((school['address'] as String?)?.isNotEmpty ?? false)
            Text(school['address'] as String,
                style: const TextStyle(color: Color(0x99FFFFFF), fontSize: 11)),
          const Divider(color: Color(0x33FFFFFF), height: 24),
          Center(
            child: RichText(
              text: const TextSpan(
                style: TextStyle(
                    fontSize: 13,
                    fontWeight: FontWeight.w800,
                    letterSpacing: .5),
                children: [
                  TextSpan(text: 'Edu', style: TextStyle(color: Colors.white)),
                  TextSpan(text: 'Core', style: TextStyle(color: kGold)),
                  TextSpan(
                      text: '  ·  Powered by EduCore',
                      style: TextStyle(
                          color: Color(0x99FFFFFF),
                          fontSize: 10,
                          fontWeight: FontWeight.w500)),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _kv(String k, String v) => Padding(
        padding: const EdgeInsets.only(top: 4),
        child: Row(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Text('$k: ',
                style: const TextStyle(color: Color(0x99FFFFFF), fontSize: 12)),
            Text(v,
                style: const TextStyle(
                    color: Colors.white,
                    fontSize: 12.5,
                    fontWeight: FontWeight.w600)),
          ],
        ),
      );
}

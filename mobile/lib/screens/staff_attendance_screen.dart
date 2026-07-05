import 'package:flutter/material.dart';
import 'package:geolocator/geolocator.dart';
import 'package:mobile_scanner/mobile_scanner.dart';
import 'package:permission_handler/permission_handler.dart';

import '../api_client.dart';
import '../main.dart';

/// The teacher's own clock-in/out: today card, month summary, QR scanner.
class StaffAttendanceScreen extends StatefulWidget {
  const StaffAttendanceScreen({super.key});

  @override
  State<StaffAttendanceScreen> createState() => _StaffAttendanceScreenState();
}

class _StaffAttendanceScreenState extends State<StaffAttendanceScreen> {
  Map<String, dynamic>? _data;
  bool _loading = true;
  bool _busy = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      _data = await ApiClient.instance.get('/staff-attendance');
    } catch (e) {
      _error = e.toString();
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<Position?> _positionIfNeeded() async {
    final geoEnabled =
        (_data?['settings']?['geo_enabled'] as bool?) ?? false;
    if (!geoEnabled) return null;

    var permission = await Geolocator.checkPermission();
    if (permission == LocationPermission.denied) {
      permission = await Geolocator.requestPermission();
    }
    if (permission == LocationPermission.denied ||
        permission == LocationPermission.deniedForever) {
      throw ApiException(
          'Location permission is required to clock in at this school.', 0);
    }
    return Geolocator.getCurrentPosition(
      locationSettings:
          const LocationSettings(accuracy: LocationAccuracy.high),
    );
  }

  Future<void> _scanAndClockIn() async {
    // Explicitly request camera permission so a denied state gives clear
    // feedback instead of a black scanner screen.
    var status = await Permission.camera.status;
    if (!status.isGranted) {
      status = await Permission.camera.request();
    }
    if (!status.isGranted) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(
        content: const Text('Camera permission is needed to scan the QR code.'),
        backgroundColor: kRisk,
        action: status.isPermanentlyDenied
            ? SnackBarAction(
                label: 'Settings',
                textColor: Colors.white,
                onPressed: openAppSettings,
              )
            : null,
      ));
      return;
    }

    final token = await Navigator.of(context).push<String>(
      MaterialPageRoute(builder: (_) => const _QrScanScreen()),
    );
    if (token == null || token.isEmpty || !mounted) return;

    setState(() => _busy = true);
    try {
      final pos = await _positionIfNeeded();
      final res = await ApiClient.instance.post('/staff-attendance/clock-in', {
        'token': token,
        if (pos != null) 'lat': pos.latitude,
        if (pos != null) 'lng': pos.longitude,
      });
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(
        content: Text(res['message'] as String? ?? 'Clocked in.'),
        backgroundColor: kGood,
      ));
      await _load();
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(e.toString()), backgroundColor: kRisk),
      );
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  Future<void> _clockOut() async {
    setState(() => _busy = true);
    try {
      final res =
          await ApiClient.instance.post('/staff-attendance/clock-out', {});
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(
        content: Text(res['message'] as String? ?? 'Clocked out.'),
        backgroundColor: kGood,
      ));
      await _load();
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(e.toString()), backgroundColor: kRisk),
      );
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_loading) return const Center(child: CircularProgressIndicator());
    if (_error != null) {
      return Center(
        child: Padding(
          padding: const EdgeInsets.all(28),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Text(_error!,
                  textAlign: TextAlign.center,
                  style: const TextStyle(color: kMuted)),
              const SizedBox(height: 14),
              FilledButton(onPressed: _load, child: const Text('Retry')),
            ],
          ),
        ),
      );
    }

    final today = _data?['today'] as Map<String, dynamic>?;
    final counts = (_data?['counts'] as Map<String, dynamic>?) ?? {};
    final records = (_data?['records'] as List<dynamic>?) ?? [];

    final clockedIn = today?['clock_in'] != null;
    final clockedOut = today?['clock_out'] != null;

    return RefreshIndicator(
      onRefresh: _load,
      child: ListView(
        padding: const EdgeInsets.all(14),
        children: [
          // ── Today card ────────────────────────────────────────────────
          Container(
            padding: const EdgeInsets.all(18),
            decoration: BoxDecoration(
              color: kNavy,
              borderRadius: BorderRadius.circular(16),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text('TODAY',
                    style: TextStyle(
                        color: kGold,
                        fontSize: 11,
                        fontWeight: FontWeight.w800,
                        letterSpacing: 2)),
                const SizedBox(height: 8),
                Text(
                  !clockedIn
                      ? 'Not clocked in yet'
                      : clockedOut
                          ? 'Done for the day'
                          : 'Clocked in — ${_fmtStatus(today?['status'])}',
                  style: const TextStyle(
                      color: Colors.white,
                      fontSize: 20,
                      fontWeight: FontWeight.w800),
                ),
                if (clockedIn) ...[
                  const SizedBox(height: 4),
                  Text(
                    'In ${today?['clock_in'] ?? ''}'
                    '${clockedOut ? '  ·  Out ${today?['clock_out']}' : ''}',
                    style: const TextStyle(color: Colors.white70, fontSize: 13),
                  ),
                ],
                const SizedBox(height: 16),
                if (!clockedIn)
                  FilledButton.icon(
                    onPressed: _busy ? null : _scanAndClockIn,
                    icon: const Icon(Icons.qr_code_scanner_rounded),
                    label: Text(_busy ? 'Working…' : 'Scan QR to clock in'),
                  )
                else if (!clockedOut)
                  FilledButton.icon(
                    style: FilledButton.styleFrom(
                        backgroundColor: Colors.white, foregroundColor: kNavy),
                    onPressed: _busy ? null : _clockOut,
                    icon: const Icon(Icons.logout_rounded),
                    label: Text(_busy ? 'Working…' : 'Clock out'),
                  ),
              ],
            ),
          ),
          const SizedBox(height: 14),

          // ── Month summary ────────────────────────────────────────────
          Row(
            children: [
              _CountChip(label: 'Early', value: counts['early'], color: kGood),
              _CountChip(
                  label: 'Present', value: counts['present'], color: kNavy),
              _CountChip(
                  label: 'Late',
                  value: counts['late'],
                  color: const Color(0xFF9A6700)),
              _CountChip(label: 'Absent', value: counts['absent'], color: kRisk),
            ],
          ),
          const SizedBox(height: 14),

          // ── History list ─────────────────────────────────────────────
          const Text('This month',
              style: TextStyle(
                  fontWeight: FontWeight.w800, color: kInk, fontSize: 15)),
          const SizedBox(height: 8),
          if (records.isEmpty)
            const Padding(
              padding: EdgeInsets.symmetric(vertical: 30),
              child: Center(
                  child: Text('No attendance records yet this month.',
                      style: TextStyle(color: kMuted))),
            ),
          ...records.map((r) {
            final rec = r as Map<String, dynamic>;
            final color = switch (rec['status'] as String?) {
              'early' => kGood,
              'present' => kNavy,
              'late' => const Color(0xFF9A6700),
              'absent' => kRisk,
              _ => kMuted,
            };
            return Container(
              margin: const EdgeInsets.only(bottom: 8),
              padding:
                  const EdgeInsets.symmetric(horizontal: 14, vertical: 11),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(10),
                border: Border.all(color: const Color(0xFFD8E0E8)),
              ),
              child: Row(
                children: [
                  Container(
                    width: 8,
                    height: 8,
                    decoration:
                        BoxDecoration(color: color, shape: BoxShape.circle),
                  ),
                  const SizedBox(width: 10),
                  Text(rec['date'] as String? ?? '',
                      style: const TextStyle(
                          fontWeight: FontWeight.w600, color: kInk)),
                  const Spacer(),
                  Text(
                    '${rec['clock_in'] ?? '—'} → ${rec['clock_out'] ?? '—'}',
                    style: const TextStyle(color: kMuted, fontSize: 12.5),
                  ),
                  const SizedBox(width: 10),
                  Text(_fmtStatus(rec['status']),
                      style: TextStyle(
                          color: color,
                          fontWeight: FontWeight.w700,
                          fontSize: 12.5)),
                ],
              ),
            );
          }),
        ],
      ),
    );
  }

  String _fmtStatus(dynamic s) {
    final v = (s as String?) ?? '';
    return v.isEmpty ? '—' : v[0].toUpperCase() + v.substring(1);
  }
}

class _CountChip extends StatelessWidget {
  const _CountChip({required this.label, required this.value, required this.color});
  final String label;
  final dynamic value;
  final Color color;

  @override
  Widget build(BuildContext context) {
    return Expanded(
      child: Container(
        margin: const EdgeInsets.symmetric(horizontal: 3),
        padding: const EdgeInsets.symmetric(vertical: 12),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(10),
          border: Border.all(color: const Color(0xFFD8E0E8)),
        ),
        child: Column(
          children: [
            Text('${value ?? 0}',
                style: TextStyle(
                    color: color, fontSize: 18, fontWeight: FontWeight.w800)),
            Text(label,
                style: const TextStyle(color: kMuted, fontSize: 10.5)),
          ],
        ),
      ),
    );
  }
}

/// Full-screen QR scanner; pops with the scanned string.
class _QrScanScreen extends StatefulWidget {
  const _QrScanScreen();

  @override
  State<_QrScanScreen> createState() => _QrScanScreenState();
}

class _QrScanScreenState extends State<_QrScanScreen> {
  bool _handled = false;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Scan the attendance QR')),
      body: Stack(
        children: [
          MobileScanner(
            onDetect: (capture) {
              if (_handled) return;
              final barcodes = capture.barcodes;
              final raw = barcodes.isNotEmpty ? barcodes.first.rawValue : null;
              if (raw != null && raw.isNotEmpty) {
                _handled = true;
                Navigator.of(context).pop(raw);
              }
            },
            errorBuilder: (context, error, child) {
              return Center(
                child: Padding(
                  padding: const EdgeInsets.all(28),
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      const Icon(Icons.no_photography_rounded,
                          color: Colors.white70, size: 48),
                      const SizedBox(height: 14),
                      Text(
                        'Camera unavailable:\n${error.errorCode.name}',
                        textAlign: TextAlign.center,
                        style: const TextStyle(color: Colors.white),
                      ),
                    ],
                  ),
                ),
              );
            },
          ),
          Center(
            child: Container(
              width: 240,
              height: 240,
              decoration: BoxDecoration(
                border: Border.all(color: kGold, width: 3),
                borderRadius: BorderRadius.circular(18),
              ),
            ),
          ),
          Positioned(
            left: 0,
            right: 0,
            bottom: 40,
            child: Text(
              'Point at the school display screen QR\nor your staff ID card',
              textAlign: TextAlign.center,
              style: TextStyle(
                color: Colors.white.withOpacity(.9),
                fontSize: 14,
                shadows: const [Shadow(blurRadius: 6, color: Colors.black)],
              ),
            ),
          ),
        ],
      ),
    );
  }
}

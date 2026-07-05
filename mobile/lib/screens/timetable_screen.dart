import 'package:flutter/material.dart';

import '../api_client.dart';
import '../main.dart';

/// Shows a timetable from one of the timetable endpoints.
/// [endpoint] is '/timetable/mine' or '/timetable/form-class'.
class TimetableScreen extends StatefulWidget {
  const TimetableScreen({super.key, required this.endpoint});

  final String endpoint;

  @override
  State<TimetableScreen> createState() => _TimetableScreenState();
}

class _TimetableScreenState extends State<TimetableScreen> {
  late Future<Map<String, dynamic>> _future;

  @override
  void initState() {
    super.initState();
    _future = ApiClient.instance.get(widget.endpoint);
  }

  bool get _showsTeacher => widget.endpoint.contains('form-class');
  bool get _showsClass => widget.endpoint.contains('mine');

  @override
  Widget build(BuildContext context) {
    return RefreshIndicator(
      onRefresh: () async =>
          setState(() => _future = ApiClient.instance.get(widget.endpoint)),
      child: FutureBuilder<Map<String, dynamic>>(
        future: _future,
        builder: (context, snap) {
          if (snap.connectionState != ConnectionState.done) {
            return const Center(child: CircularProgressIndicator());
          }
          if (snap.hasError) {
            return ListView(children: [
              const SizedBox(height: 120),
              Icon(Icons.event_busy_rounded,
                  size: 50, color: kMuted.withOpacity(.5)),
              const SizedBox(height: 12),
              Text(snap.error.toString(),
                  textAlign: TextAlign.center,
                  style: const TextStyle(color: kMuted)),
            ]);
          }
          final days = (snap.data?['days'] as List<dynamic>?) ?? [];
          if (days.isEmpty) {
            return ListView(children: const [
              SizedBox(height: 120),
              Icon(Icons.event_available_rounded, size: 50, color: kMuted),
              SizedBox(height: 12),
              Center(
                  child: Text('No timetable periods scheduled yet.',
                      style: TextStyle(color: kMuted))),
            ]);
          }
          return ListView(
            padding: const EdgeInsets.all(14),
            children: days.map<Widget>((d) {
              final day = d as Map<String, dynamic>;
              final periods = (day['periods'] as List<dynamic>);
              return Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Padding(
                    padding: const EdgeInsets.only(top: 6, bottom: 8, left: 2),
                    child: Text(
                      day['day'] as String? ?? '',
                      style: const TextStyle(
                          fontSize: 15,
                          fontWeight: FontWeight.w800,
                          color: kNavy),
                    ),
                  ),
                  ...periods.map((p) => _periodTile(p as Map<String, dynamic>)),
                  const SizedBox(height: 10),
                ],
              );
            }).toList(),
          );
        },
      ),
    );
  }

  Widget _periodTile(Map<String, dynamic> p) {
    return Container(
      margin: const EdgeInsets.only(bottom: 8),
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: const Color(0xFFD8E0E8)),
      ),
      child: Row(
        children: [
          Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(p['start'] as String? ?? '',
                  style: const TextStyle(
                      fontWeight: FontWeight.w800, color: kNavy, fontSize: 13)),
              Text(p['end'] as String? ?? '',
                  style: const TextStyle(color: kMuted, fontSize: 11)),
            ],
          ),
          Container(
            width: 3,
            height: 38,
            margin: const EdgeInsets.symmetric(horizontal: 12),
            decoration: BoxDecoration(
                color: kGold, borderRadius: BorderRadius.circular(2)),
          ),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(p['subject'] as String? ?? '—',
                    style: const TextStyle(
                        fontWeight: FontWeight.w700, color: kInk)),
                const SizedBox(height: 2),
                Text(
                  [
                    if (_showsClass && p['class'] != null &&
                        (p['class'] as String).isNotEmpty)
                      p['class'],
                    if (_showsTeacher && p['teacher'] != null)
                      p['teacher'],
                    if (p['venue'] != null &&
                        (p['venue'] as String).isNotEmpty)
                      '📍 ${p['venue']}',
                  ].join('  ·  '),
                  style: const TextStyle(color: kMuted, fontSize: 12),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

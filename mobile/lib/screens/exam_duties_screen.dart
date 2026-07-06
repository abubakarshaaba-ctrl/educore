import 'package:flutter/material.dart';
import 'package:intl/intl.dart';

import '../api_client.dart';
import '../main.dart';

/// Personal exam supervision schedule ("My Exam Duties").
/// Pull-based — visible as soon as the school publishes the plan, whether
/// or not push notifications are set up.
class ExamDutiesScreen extends StatefulWidget {
  const ExamDutiesScreen({super.key});

  @override
  State<ExamDutiesScreen> createState() => _ExamDutiesScreenState();
}

class _ExamDutiesScreenState extends State<ExamDutiesScreen> {
  late Future<List<dynamic>> _future;

  @override
  void initState() {
    super.initState();
    _future = _load();
  }

  Future<List<dynamic>> _load() async {
    final data = await ApiClient.instance.get('/exam-duties');
    return data['duties'] as List<dynamic>;
  }

  String _fmtTime(String? hms) {
    if (hms == null || hms.isEmpty) return '';
    final parts = hms.split(':');
    if (parts.length < 2) return hms;
    final dt = DateTime(2000, 1, 1, int.parse(parts[0]), int.parse(parts[1]));
    return DateFormat('h:mm a').format(dt);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('My Exam Duties')),
      body: RefreshIndicator(
        onRefresh: () async => setState(() => _future = _load()),
        child: FutureBuilder<List<dynamic>>(
          future: _future,
          builder: (context, snap) {
            if (snap.connectionState != ConnectionState.done) {
              return const Center(child: CircularProgressIndicator());
            }
            if (snap.hasError) {
              return ListView(
                padding: const EdgeInsets.all(28),
                children: [
                  const SizedBox(height: 80),
                  Text(snap.error.toString(),
                      textAlign: TextAlign.center,
                      style: const TextStyle(color: kMuted)),
                  const SizedBox(height: 14),
                  Center(
                    child: FilledButton(
                      onPressed: () => setState(() => _future = _load()),
                      child: const Text('Retry'),
                    ),
                  ),
                ],
              );
            }
            final duties = snap.data ?? [];
            if (duties.isEmpty) {
              return ListView(
                children: const [
                  SizedBox(height: 120),
                  Icon(Icons.fact_check_outlined, size: 52, color: kMuted),
                  SizedBox(height: 14),
                  Text(
                    'No exam supervision duties published yet.',
                    textAlign: TextAlign.center,
                    style: TextStyle(color: kMuted),
                  ),
                ],
              );
            }

            final byDate = <String, List<Map<String, dynamic>>>{};
            for (final d in duties) {
              final m = Map<String, dynamic>.from(d as Map);
              byDate.putIfAbsent(m['date'] as String, () => []).add(m);
            }
            final dates = byDate.keys.toList()..sort();

            return ListView.builder(
              padding: const EdgeInsets.all(14),
              itemCount: dates.length,
              itemBuilder: (context, i) {
                final date = dates[i];
                final items = byDate[date]!;
                final parsed = DateTime.parse(date);
                return Padding(
                  padding: const EdgeInsets.only(bottom: 14),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        DateFormat('EEEE, d MMM yyyy').format(parsed),
                        style: const TextStyle(
                            fontWeight: FontWeight.w800, color: kInk, fontSize: 14),
                      ),
                      const SizedBox(height: 8),
                      ...items.map((d) => Container(
                            margin: const EdgeInsets.only(bottom: 8),
                            padding: const EdgeInsets.all(14),
                            decoration: BoxDecoration(
                              color: Colors.white,
                              borderRadius: BorderRadius.circular(12),
                              border: Border.all(color: const Color(0xFFD8E0E8)),
                            ),
                            child: Row(
                              children: [
                                Container(
                                  width: 4,
                                  height: 44,
                                  decoration: BoxDecoration(
                                    color: kGold,
                                    borderRadius: BorderRadius.circular(2),
                                  ),
                                ),
                                const SizedBox(width: 12),
                                Expanded(
                                  child: Column(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      Text(
                                        '${d['session']} · ${_fmtTime(d['start_time'])}–${_fmtTime(d['end_time'])}',
                                        style: const TextStyle(
                                            fontWeight: FontWeight.w700,
                                            color: kInk,
                                            fontSize: 13),
                                      ),
                                      const SizedBox(height: 2),
                                      Text(
                                        '${d['class_level']} — ${d['subject']}',
                                        style: const TextStyle(
                                            color: kMuted, fontSize: 12.5),
                                      ),
                                      if (d['venue'] != null)
                                        Text('Venue: ${d['venue']}',
                                            style: const TextStyle(
                                                color: kMuted, fontSize: 11.5)),
                                    ],
                                  ),
                                ),
                              ],
                            ),
                          )),
                    ],
                  ),
                );
              },
            );
          },
        ),
      ),
    );
  }
}

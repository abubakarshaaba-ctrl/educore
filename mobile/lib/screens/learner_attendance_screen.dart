import 'package:flutter/material.dart';
import 'package:intl/intl.dart';

import '../api_client.dart';
import '../main.dart';

class LearnerAttendanceScreen extends StatefulWidget {
  const LearnerAttendanceScreen({
    super.key,
    required this.endpoint,
    required this.title,
  });

  final String endpoint;
  final String title;

  @override
  State<LearnerAttendanceScreen> createState() =>
      _LearnerAttendanceScreenState();
}

class _LearnerAttendanceScreenState extends State<LearnerAttendanceScreen> {
  late Future<Map<String, dynamic>> _future;
  int? _termId;

  @override
  void initState() {
    super.initState();
    _future = _load();
  }

  Future<Map<String, dynamic>> _load() => ApiClient.instance.get(
        widget.endpoint,
        _termId == null ? null : {'term_id': '$_termId'},
      );

  void _selectTerm(int? termId) {
    if (termId == _termId) return;
    setState(() {
      _termId = termId;
      _future = _load();
    });
  }

  @override
  Widget build(BuildContext context) => Scaffold(
        appBar: AppBar(title: Text(widget.title)),
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
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Text(snapshot.error.toString(),
                          textAlign: TextAlign.center,
                          style: const TextStyle(color: kMuted)),
                      const SizedBox(height: 12),
                      FilledButton(
                          onPressed: () => setState(() => _future = _load()),
                          child: const Text('Retry')),
                    ],
                  ),
                ),
              );
            }

            final data = snapshot.data ?? const <String, dynamic>{};
            _termId ??= data['selected_term_id'] as int?;
            final terms = (data['terms'] as List<dynamic>? ?? const <dynamic>[])
                .cast<Map<String, dynamic>>();
            final records =
                (data['records'] as List<dynamic>? ?? const <dynamic>[])
                    .cast<Map<String, dynamic>>();
            final stats = (data['stats'] as Map<dynamic, dynamic>? ?? const {})
                .cast<String, dynamic>();
            final student =
                (data['student'] as Map<dynamic, dynamic>? ?? const {})
                    .cast<String, dynamic>();

            return RefreshIndicator(
              onRefresh: () async => setState(() => _future = _load()),
              child: ListView(
                padding: const EdgeInsets.all(14),
                children: [
                  Text(student['name']?.toString() ?? 'Attendance record',
                      style: const TextStyle(
                          color: kInk,
                          fontSize: 18,
                          fontWeight: FontWeight.w900)),
                  const SizedBox(height: 10),
                  DropdownButtonFormField<int>(
                    key: ValueKey(_termId),
                    initialValue: terms.any((term) => term['id'] == _termId)
                        ? _termId
                        : null,
                    decoration: const InputDecoration(
                      labelText: 'Academic term',
                      prefixIcon: Icon(Icons.date_range_outlined),
                    ),
                    items: terms
                        .map((term) => DropdownMenuItem<int>(
                              value: term['id'] as int,
                              child: Text(
                                '${term['name'] ?? 'Term'} · ${term['session'] ?? ''}',
                                overflow: TextOverflow.ellipsis,
                              ),
                            ))
                        .toList(),
                    onChanged: _selectTerm,
                  ),
                  const SizedBox(height: 12),
                  Row(
                    children: [
                      _Stat(
                          label: 'Present',
                          value: '${stats['present'] ?? 0}',
                          color: kGood),
                      _Stat(
                          label: 'Absent',
                          value: '${stats['absent'] ?? 0}',
                          color: kRisk),
                      _Stat(
                          label: 'Late',
                          value: '${stats['late'] ?? 0}',
                          color: kGold),
                      _Stat(
                          label: 'Rate',
                          value: '${stats['rate'] ?? 0}%',
                          color: kNavy),
                    ],
                  ),
                  const SizedBox(height: 16),
                  if (records.isEmpty)
                    const Card(
                      child: Padding(
                        padding: EdgeInsets.all(28),
                        child: Text(
                          'No attendance has been recorded for this term.',
                          textAlign: TextAlign.center,
                          style: TextStyle(color: kMuted),
                        ),
                      ),
                    )
                  else
                    ...records.map((record) => _AttendanceRow(record: record)),
                ],
              ),
            );
          },
        ),
      );
}

class _Stat extends StatelessWidget {
  const _Stat({required this.label, required this.value, required this.color});
  final String label;
  final String value;
  final Color color;

  @override
  Widget build(BuildContext context) => Expanded(
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
              Text(value,
                  style: TextStyle(
                      color: color, fontSize: 16, fontWeight: FontWeight.w900)),
              Text(label, style: const TextStyle(color: kMuted, fontSize: 9.5)),
            ],
          ),
        ),
      );
}

class _AttendanceRow extends StatelessWidget {
  const _AttendanceRow({required this.record});
  final Map<String, dynamic> record;

  @override
  Widget build(BuildContext context) {
    final status = record['status']?.toString() ?? 'unknown';
    final color = switch (status) {
      'present' => kGood,
      'late' => kGold,
      'absent' => kRisk,
      _ => kMuted,
    };
    var date = record['attendance_date']?.toString() ?? '';
    try {
      date = DateFormat('EEE, d MMM yyyy').format(DateTime.parse(date));
    } catch (_) {}

    return Card(
      margin: const EdgeInsets.only(bottom: 8),
      child: ListTile(
        leading: CircleAvatar(
          backgroundColor: color.withValues(alpha: .10),
          child: Icon(Icons.calendar_today_outlined, color: color, size: 18),
        ),
        title: Text(date,
            style: const TextStyle(color: kInk, fontWeight: FontWeight.w700)),
        subtitle: (record['remark']?.toString() ?? '').isEmpty
            ? null
            : Text(record['remark'].toString(),
                style: const TextStyle(color: kMuted, fontSize: 11.5)),
        trailing: Text(
          status[0].toUpperCase() + status.substring(1),
          style: TextStyle(color: color, fontWeight: FontWeight.w800),
        ),
      ),
    );
  }
}

import 'package:flutter/material.dart';

import '../main.dart';

class ReportBreakdownScreen extends StatelessWidget {
  const ReportBreakdownScreen({
    super.key,
    required this.report,
    this.studentName,
  });

  final Map<String, dynamic> report;
  final String? studentName;

  @override
  Widget build(BuildContext context) {
    final subjects = (report['subjects'] as List<dynamic>? ?? const <dynamic>[])
        .cast<Map<String, dynamic>>();
    final term = report['term']?.toString() ?? 'Term report';
    final session = report['session']?.toString() ?? '';

    return Scaffold(
      appBar: AppBar(title: const Text('Report breakdown')),
      body: ListView(
        padding: const EdgeInsets.all(14),
        children: [
          Container(
            padding: const EdgeInsets.all(18),
            decoration: BoxDecoration(
              gradient: const LinearGradient(
                colors: [kNavy, Color(0xFF0B326F)],
              ),
              borderRadius: BorderRadius.circular(16),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                if ((studentName ?? '').isNotEmpty)
                  Text(
                    studentName!,
                    style: const TextStyle(
                      color: Color(0xFFCDD9EB),
                      fontSize: 12,
                      fontWeight: FontWeight.w700,
                    ),
                  ),
                Text(
                  '$term${session.isEmpty ? '' : ' · $session'}',
                  style: const TextStyle(
                    color: Colors.white,
                    fontSize: 20,
                    fontWeight: FontWeight.w900,
                  ),
                ),
                const SizedBox(height: 16),
                Row(
                  children: [
                    _Metric(
                        label: 'Average',
                        value: '${number(report['average'])}%'),
                    _Metric(
                      label: 'Position',
                      value:
                          '${report['position'] ?? '—'} / ${report['class_size'] ?? '—'}',
                    ),
                    _Metric(
                      label: 'Subjects',
                      value: '${report['subjects_offered'] ?? subjects.length}',
                    ),
                  ],
                ),
              ],
            ),
          ),
          const SizedBox(height: 14),
          if (subjects.isEmpty)
            const Card(
              child: Padding(
                padding: EdgeInsets.all(24),
                child: Text(
                  'The term summary is available, but no subject scores have been published.',
                  textAlign: TextAlign.center,
                  style: TextStyle(color: kMuted),
                ),
              ),
            )
          else
            ...subjects.map((subject) => _SubjectCard(subject: subject)),
          if ((report['form_tutor_remark']?.toString() ?? '').isNotEmpty)
            _RemarkCard(
              title: 'Form tutor’s remark',
              body: report['form_tutor_remark'].toString(),
            ),
          if ((report['principal_remark']?.toString() ?? '').isNotEmpty)
            _RemarkCard(
              title: 'Principal’s remark',
              body: report['principal_remark'].toString(),
            ),
          const SizedBox(height: 20),
        ],
      ),
    );
  }

  static String number(dynamic value) {
    final parsed = value is num
        ? value.toDouble()
        : double.tryParse(value?.toString() ?? '');
    if (parsed == null) return '0';
    return parsed.truncateToDouble() == parsed
        ? parsed.toStringAsFixed(0)
        : parsed.toStringAsFixed(1);
  }
}

class _Metric extends StatelessWidget {
  const _Metric({required this.label, required this.value});
  final String label;
  final String value;

  @override
  Widget build(BuildContext context) => Expanded(
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(label,
                style:
                    const TextStyle(color: Color(0xFFAFC2DE), fontSize: 10.5)),
            const SizedBox(height: 2),
            Text(value,
                style: const TextStyle(
                    color: Colors.white,
                    fontSize: 17,
                    fontWeight: FontWeight.w900)),
          ],
        ),
      );
}

class _SubjectCard extends StatelessWidget {
  const _SubjectCard({required this.subject});
  final Map<String, dynamic> subject;

  @override
  Widget build(BuildContext context) {
    final assessments =
        (subject['assessments'] as List<dynamic>? ?? const <dynamic>[])
            .cast<Map<String, dynamic>>();
    final passed = subject['is_pass'] == true;

    return Card(
      margin: const EdgeInsets.only(bottom: 10),
      clipBehavior: Clip.antiAlias,
      child: ExpansionTile(
        tilePadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 3),
        childrenPadding: const EdgeInsets.fromLTRB(14, 0, 14, 14),
        leading: CircleAvatar(
          backgroundColor: (passed ? kGood : kRisk).withValues(alpha: .09),
          child: Text(
            subject['grade']?.toString() ?? '—',
            style: TextStyle(
                color: passed ? kGood : kRisk, fontWeight: FontWeight.w900),
          ),
        ),
        title: Text(subject['subject']?.toString() ?? 'Subject',
            style: const TextStyle(color: kInk, fontWeight: FontWeight.w800)),
        subtitle: Text(
          'Total ${ReportBreakdownScreen.number(subject['total'])}% · ${subject['remark'] ?? '—'}',
          style: const TextStyle(color: kMuted, fontSize: 12),
        ),
        children: [
          if (assessments.isEmpty)
            const Align(
              alignment: Alignment.centerLeft,
              child: Text('No assessment components recorded.',
                  style: TextStyle(color: kMuted, fontSize: 12)),
            )
          else
            ...assessments.map(
              (assessment) => Container(
                margin: const EdgeInsets.only(top: 7),
                padding:
                    const EdgeInsets.symmetric(horizontal: 11, vertical: 9),
                decoration: BoxDecoration(
                  color: const Color(0xFFF6F8FB),
                  borderRadius: BorderRadius.circular(9),
                ),
                child: Row(
                  children: [
                    Expanded(
                      child: Text(
                        assessment['name']?.toString() ?? 'Assessment',
                        style: const TextStyle(
                            color: kInk,
                            fontSize: 12.5,
                            fontWeight: FontWeight.w700),
                      ),
                    ),
                    Text(
                      '${ReportBreakdownScreen.number(assessment['score'])}'
                      '${assessment['maximum'] == null ? '' : ' / ${ReportBreakdownScreen.number(assessment['maximum'])}'}',
                      style: const TextStyle(
                          color: kNavy,
                          fontSize: 13,
                          fontWeight: FontWeight.w900),
                    ),
                  ],
                ),
              ),
            ),
          const SizedBox(height: 10),
          Row(
            children: [
              _SmallStat(label: 'Class high', value: subject['class_highest']),
              _SmallStat(
                  label: 'Class average', value: subject['class_average']),
              _SmallStat(label: 'Position', value: subject['position']),
            ],
          ),
        ],
      ),
    );
  }
}

class _SmallStat extends StatelessWidget {
  const _SmallStat({required this.label, required this.value});
  final String label;
  final dynamic value;

  @override
  Widget build(BuildContext context) => Expanded(
        child: Column(
          children: [
            Text(label, style: const TextStyle(color: kMuted, fontSize: 9.5)),
            Text(value?.toString() ?? '—',
                style: const TextStyle(
                    color: kInk, fontSize: 12.5, fontWeight: FontWeight.w800)),
          ],
        ),
      );
}

class _RemarkCard extends StatelessWidget {
  const _RemarkCard({required this.title, required this.body});
  final String title;
  final String body;

  @override
  Widget build(BuildContext context) => Container(
        margin: const EdgeInsets.only(top: 10),
        padding: const EdgeInsets.all(14),
        decoration: BoxDecoration(
          color: const Color(0xFFFFFBEB),
          border: Border.all(color: const Color(0xFFF3DDA9)),
          borderRadius: BorderRadius.circular(12),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(title,
                style: const TextStyle(
                    color: kNavy, fontSize: 12, fontWeight: FontWeight.w800)),
            const SizedBox(height: 4),
            Text(body,
                style:
                    const TextStyle(color: kInk, fontSize: 12.5, height: 1.4)),
          ],
        ),
      );
}

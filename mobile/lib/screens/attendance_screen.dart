import 'package:flutter/material.dart';
import 'package:intl/intl.dart';

import '../api_client.dart';
import '../main.dart';

/// Attendance sheet for one class on one date.
/// Tap a student to cycle status; long-press to add a remark.
class AttendanceScreen extends StatefulWidget {
  const AttendanceScreen({
    super.key,
    required this.classArmId,
    required this.className,
  });

  final int classArmId;
  final String className;

  @override
  State<AttendanceScreen> createState() => _AttendanceScreenState();
}

const _statusCycle = [null, 'present', 'absent', 'late', 'excused'];

const _statusColors = {
  'present': kGood,
  'absent': kRisk,
  'late': Color(0xFF9A6700),
  'excused': Color(0xFF175CD3),
};

const _statusIcons = {
  'present': Icons.check_circle_rounded,
  'absent': Icons.cancel_rounded,
  'late': Icons.schedule_rounded,
  'excused': Icons.medical_services_rounded,
};

class _AttendanceScreenState extends State<AttendanceScreen> {
  DateTime _date = DateTime.now();
  List<Map<String, dynamic>> _students = [];
  bool _loading = true;
  bool _saving = false;
  String? _error;

  String get _dateStr => DateFormat('yyyy-MM-dd').format(_date);

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
      final data = await ApiClient.instance.get(
        '/classes/${widget.classArmId}/attendance',
        {'date': _dateStr},
      );
      _students = (data['students'] as List<dynamic>)
          .map((e) => Map<String, dynamic>.from(e as Map))
          .toList();
    } catch (e) {
      _error = e.toString();
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _pickDate() async {
    final picked = await showDatePicker(
      context: context,
      initialDate: _date,
      firstDate: DateTime.now().subtract(const Duration(days: 60)),
      lastDate: DateTime.now(),
    );
    if (picked != null) {
      setState(() => _date = picked);
      await _load();
    }
  }

  void _cycle(int index) {
    final current = _students[index]['status'] as String?;
    final next = _statusCycle[
        (_statusCycle.indexOf(current) + 1) % _statusCycle.length];
    setState(() => _students[index]['status'] = next);
  }

  void _markAll(String status) {
    setState(() {
      for (final s in _students) {
        s['status'] = status;
      }
    });
  }

  Future<void> _save() async {
    final records = _students
        .where((s) => s['status'] != null)
        .map((s) => {
              'student_id': s['id'],
              'status': s['status'],
              'remark': s['remark'],
            })
        .toList();

    if (records.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Mark at least one student first.')),
      );
      return;
    }

    setState(() => _saving = true);
    try {
      final res = await ApiClient.instance.post(
        '/classes/${widget.classArmId}/attendance',
        {'date': _dateStr, 'records': records},
      );
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(res['message'] as String? ?? 'Saved.'),
          backgroundColor: kGood,
        ),
      );
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(e.toString()), backgroundColor: kRisk),
      );
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final marked = _students.where((s) => s['status'] != null).length;

    return Scaffold(
      appBar: AppBar(
        title: Text(widget.className),
        actions: [
          TextButton.icon(
            onPressed: _pickDate,
            icon: const Icon(Icons.calendar_month, color: kGold, size: 18),
            label: Text(
              DateFormat('d MMM').format(_date),
              style: const TextStyle(color: kGold),
            ),
          ),
        ],
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : _error != null
              ? Center(
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
                )
              : Column(
                  children: [
                    // Quick actions
                    Padding(
                      padding: const EdgeInsets.fromLTRB(14, 12, 14, 4),
                      child: Row(
                        children: [
                          Expanded(
                            child: Text(
                              '$marked of ${_students.length} marked · tap to cycle',
                              style: const TextStyle(color: kMuted, fontSize: 12.5),
                            ),
                          ),
                          TextButton(
                            onPressed: () => _markAll('present'),
                            child: const Text('All present',
                                style: TextStyle(color: kGood, fontSize: 12.5)),
                          ),
                        ],
                      ),
                    ),
                    Expanded(
                      child: ListView.separated(
                        padding: const EdgeInsets.fromLTRB(14, 4, 14, 90),
                        itemCount: _students.length,
                        separatorBuilder: (_, __) => const SizedBox(height: 8),
                        itemBuilder: (context, i) {
                          final s = _students[i];
                          final status = s['status'] as String?;
                          final color = _statusColors[status];
                          return Material(
                            color: Colors.white,
                            borderRadius: BorderRadius.circular(12),
                            child: InkWell(
                              borderRadius: BorderRadius.circular(12),
                              onTap: () => _cycle(i),
                              child: Container(
                                padding: const EdgeInsets.symmetric(
                                    horizontal: 14, vertical: 12),
                                decoration: BoxDecoration(
                                  borderRadius: BorderRadius.circular(12),
                                  border: Border.all(
                                    color: color?.withOpacity(.5) ??
                                        const Color(0xFFD8E0E8),
                                    width: color != null ? 1.6 : 1,
                                  ),
                                ),
                                child: Row(
                                  children: [
                                    Expanded(
                                      child: Column(
                                        crossAxisAlignment:
                                            CrossAxisAlignment.start,
                                        children: [
                                          Text(
                                            s['name'] as String? ?? '—',
                                            style: const TextStyle(
                                                fontWeight: FontWeight.w700,
                                                color: kInk),
                                          ),
                                          Text(
                                            s['admission_number'] as String? ?? '',
                                            style: const TextStyle(
                                                color: kMuted, fontSize: 11.5),
                                          ),
                                        ],
                                      ),
                                    ),
                                    if (status == null)
                                      const Text('Tap to mark',
                                          style: TextStyle(
                                              color: kMuted, fontSize: 12))
                                    else ...[
                                      Icon(_statusIcons[status],
                                          color: color, size: 20),
                                      const SizedBox(width: 6),
                                      Text(
                                        status[0].toUpperCase() +
                                            status.substring(1),
                                        style: TextStyle(
                                            color: color,
                                            fontWeight: FontWeight.w700,
                                            fontSize: 13),
                                      ),
                                    ],
                                  ],
                                ),
                              ),
                            ),
                          );
                        },
                      ),
                    ),
                  ],
                ),
      floatingActionButtonLocation: FloatingActionButtonLocation.centerFloat,
      floatingActionButton: _loading || _error != null
          ? null
          : SizedBox(
              width: MediaQuery.of(context).size.width - 28,
              child: FilledButton.icon(
                onPressed: _saving ? null : _save,
                icon: _saving
                    ? const SizedBox(
                        width: 18,
                        height: 18,
                        child: CircularProgressIndicator(strokeWidth: 2.5))
                    : const Icon(Icons.save_rounded),
                label: Text(_saving ? 'Saving…' : 'Save attendance'),
              ),
            ),
    );
  }
}

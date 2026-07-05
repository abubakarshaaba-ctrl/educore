import 'package:flutter/material.dart';

import '../api_client.dart';
import '../main.dart';

/// Pick a class+subject the teacher is assigned to, then enter scores.
class ScoresScreen extends StatefulWidget {
  const ScoresScreen({super.key});

  @override
  State<ScoresScreen> createState() => _ScoresScreenState();
}

class _ScoresScreenState extends State<ScoresScreen> {
  late Future<List<dynamic>> _future;

  @override
  void initState() {
    super.initState();
    _future = _load();
  }

  Future<List<dynamic>> _load() async {
    final data = await ApiClient.instance.get('/scores/teaching');
    return data['assignments'] as List<dynamic>;
  }

  @override
  Widget build(BuildContext context) {
    return RefreshIndicator(
      onRefresh: () async => setState(() => _future = _load()),
      child: FutureBuilder<List<dynamic>>(
        future: _future,
        builder: (context, snap) {
          if (snap.connectionState != ConnectionState.done) {
            return const Center(child: CircularProgressIndicator());
          }
          if (snap.hasError) {
            return _centerMsg(snap.error.toString());
          }
          final items = snap.data ?? [];
          if (items.isEmpty) {
            return _centerMsg(
                'No subjects assigned to you for the current session.');
          }
          return ListView.separated(
            padding: const EdgeInsets.all(14),
            itemCount: items.length,
            separatorBuilder: (_, __) => const SizedBox(height: 10),
            itemBuilder: (context, i) {
              final a = items[i] as Map<String, dynamic>;
              return Card(
                elevation: 0,
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(12),
                  side: const BorderSide(color: Color(0xFFD8E0E8)),
                ),
                child: ListTile(
                  contentPadding:
                      const EdgeInsets.symmetric(horizontal: 16, vertical: 6),
                  leading: const CircleAvatar(
                    backgroundColor: kNavy,
                    child: Icon(Icons.edit_note_rounded, color: kGold),
                  ),
                  title: Text(a['subject_name'] as String? ?? '—',
                      style: const TextStyle(
                          fontWeight: FontWeight.w700, color: kInk)),
                  subtitle: Text(a['class_name'] as String? ?? '',
                      style: const TextStyle(color: kMuted, fontSize: 12.5)),
                  trailing: const Icon(Icons.chevron_right, color: kMuted),
                  onTap: () => Navigator.of(context).push(MaterialPageRoute(
                    builder: (_) => ScoreSheetScreen(
                      classArmId: a['class_arm_id'] as int,
                      subjectId: a['subject_id'] as int,
                      title:
                          '${a['subject_name']} · ${a['class_name']}',
                    ),
                  )),
                ),
              );
            },
          );
        },
      ),
    );
  }

  Widget _centerMsg(String msg) => ListView(children: [
        const SizedBox(height: 120),
        Icon(Icons.edit_note_rounded, size: 50, color: kMuted.withOpacity(.5)),
        const SizedBox(height: 12),
        Text(msg,
            textAlign: TextAlign.center, style: const TextStyle(color: kMuted)),
      ]);
}

// ── Score entry sheet ───────────────────────────────────────────────────
class ScoreSheetScreen extends StatefulWidget {
  const ScoreSheetScreen({
    super.key,
    required this.classArmId,
    required this.subjectId,
    required this.title,
  });

  final int classArmId;
  final int subjectId;
  final String title;

  @override
  State<ScoreSheetScreen> createState() => _ScoreSheetScreenState();
}

class _ScoreSheetScreenState extends State<ScoreSheetScreen> {
  Map<String, dynamic>? _data;
  bool _loading = true;
  bool _saving = false;
  String? _error;

  // student_id -> assessment_id -> controller
  final Map<int, Map<int, TextEditingController>> _controllers = {};

  @override
  void initState() {
    super.initState();
    _load();
  }

  @override
  void dispose() {
    for (final m in _controllers.values) {
      for (final c in m.values) {
        c.dispose();
      }
    }
    super.dispose();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      _data = await ApiClient.instance.get('/scores/sheet', {
        'class_arm_id': '${widget.classArmId}',
        'subject_id': '${widget.subjectId}',
      });
      final students = _data!['students'] as List<dynamic>;
      final types = _data!['assessment_types'] as List<dynamic>;
      for (final s in students) {
        final sid = s['id'] as int;
        final existing = (s['scores'] as Map).cast<String, dynamic>();
        _controllers[sid] = {};
        for (final t in types) {
          final tid = t['id'] as int;
          final v = existing['$tid'];
          _controllers[sid]![tid] =
              TextEditingController(text: v == null ? '' : '$v');
        }
      }
    } catch (e) {
      _error = e.toString();
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _save() async {
    final scores = <String, Map<String, String>>{};
    _controllers.forEach((sid, types) {
      final row = <String, String>{};
      types.forEach((tid, ctrl) {
        if (ctrl.text.trim().isNotEmpty) row['$tid'] = ctrl.text.trim();
      });
      if (row.isNotEmpty) scores['$sid'] = row;
    });

    if (scores.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Enter at least one score.')));
      return;
    }

    setState(() => _saving = true);
    try {
      final res = await ApiClient.instance.post('/scores/save', {
        'class_arm_id': widget.classArmId,
        'subject_id': widget.subjectId,
        'scores': scores,
      });
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(
        content: Text(res['message'] as String? ?? 'Saved.'),
        backgroundColor: kGood,
      ));
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(e.toString()), backgroundColor: kRisk));
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text(widget.title, overflow: TextOverflow.ellipsis)),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : _error != null
              ? Center(
                  child: Padding(
                    padding: const EdgeInsets.all(28),
                    child: Column(mainAxisSize: MainAxisSize.min, children: [
                      Text(_error!,
                          textAlign: TextAlign.center,
                          style: const TextStyle(color: kMuted)),
                      const SizedBox(height: 14),
                      FilledButton(onPressed: _load, child: const Text('Retry')),
                    ]),
                  ),
                )
              : _buildSheet(),
      floatingActionButtonLocation: FloatingActionButtonLocation.centerFloat,
      floatingActionButton: (_loading || _error != null)
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
                label: Text(_saving ? 'Saving…' : 'Save scores'),
              ),
            ),
    );
  }

  Widget _buildSheet() {
    final students = _data!['students'] as List<dynamic>;
    final types = _data!['assessment_types'] as List<dynamic>;

    if (types.isEmpty) {
      return const Center(
        child: Padding(
          padding: EdgeInsets.all(28),
          child: Text(
            'No assessment types set up for this term yet.\nAsk your admin to add CA/Exam components.',
            textAlign: TextAlign.center,
            style: TextStyle(color: kMuted),
          ),
        ),
      );
    }

    return ListView.separated(
      padding: const EdgeInsets.fromLTRB(12, 12, 12, 90),
      itemCount: students.length,
      separatorBuilder: (_, __) => const SizedBox(height: 8),
      itemBuilder: (context, i) {
        final s = students[i] as Map<String, dynamic>;
        final sid = s['id'] as int;
        return Container(
          padding: const EdgeInsets.all(12),
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(12),
            border: Border.all(color: const Color(0xFFD8E0E8)),
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(s['name'] as String? ?? '—',
                  style: const TextStyle(
                      fontWeight: FontWeight.w700, color: kInk)),
              Text(s['admission_number'] as String? ?? '',
                  style: const TextStyle(color: kMuted, fontSize: 11.5)),
              const SizedBox(height: 10),
              Wrap(
                spacing: 10,
                runSpacing: 8,
                children: types.map<Widget>((t) {
                  final tid = t['id'] as int;
                  final maxV = (t['max'] as num).toStringAsFixed(0);
                  return SizedBox(
                    width: 92,
                    child: TextField(
                      controller: _controllers[sid]![tid],
                      keyboardType: const TextInputType.numberWithOptions(
                          decimal: true),
                      decoration: InputDecoration(
                        isDense: true,
                        labelText: '${t['name']} /$maxV',
                        labelStyle: const TextStyle(fontSize: 11),
                        contentPadding: const EdgeInsets.symmetric(
                            horizontal: 10, vertical: 10),
                      ),
                    ),
                  );
                }).toList(),
              ),
            ],
          ),
        );
      },
    );
  }
}

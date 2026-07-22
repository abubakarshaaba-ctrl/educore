import 'package:flutter/material.dart';

import '../api_client.dart';
import '../main.dart';
import 'login_screen.dart';
import 'staff_attendance_screen.dart';

class AdmissionOfficerScreen extends StatefulWidget {
  const AdmissionOfficerScreen({super.key});

  @override
  State<AdmissionOfficerScreen> createState() => _AdmissionOfficerScreenState();
}

class _AdmissionOfficerScreenState extends State<AdmissionOfficerScreen> {
  String _status = 'all';
  late Future<Map<String, dynamic>> _future = _load();

  Future<Map<String, dynamic>> _load() => ApiClient.instance.get(
        '/admissions',
        _status == 'all' ? null : {'status': _status},
      );

  void _refresh() => setState(() => _future = _load());

  @override
  Widget build(BuildContext context) => Scaffold(
        appBar: AppBar(
          title: const Text('Admissions'),
          actions: [
            IconButton(
              tooltip: 'My attendance',
              onPressed: () => Navigator.push(
                  context,
                  MaterialPageRoute(
                      builder: (_) => const StaffAttendanceScreen())),
              icon: const Icon(Icons.badge_outlined),
            ),
            IconButton(
              tooltip: 'Sign out',
              onPressed: () async {
                await ApiClient.instance.logout();
                if (context.mounted) {
                  Navigator.of(context).pushAndRemoveUntil(
                    MaterialPageRoute(builder: (_) => const LoginScreen()),
                    (_) => false,
                  );
                }
              },
              icon: const Icon(Icons.logout_rounded),
            ),
          ],
        ),
        floatingActionButton: FloatingActionButton.extended(
          onPressed: () => _openCreate(),
          backgroundColor: kGold,
          foregroundColor: kNavy,
          icon: const Icon(Icons.person_add_alt_1_rounded),
          label: const Text('New application'),
        ),
        body: FutureBuilder<Map<String, dynamic>>(
          future: _future,
          builder: (context, snapshot) {
            if (snapshot.connectionState != ConnectionState.done) {
              return const Center(child: CircularProgressIndicator());
            }
            if (snapshot.hasError) {
              return _error(snapshot.error.toString());
            }
            final data = snapshot.data ?? const <String, dynamic>{};
            final stats =
                (data['stats'] as Map?)?.cast<String, dynamic>() ?? {};
            final items = data['admissions'] as List<dynamic>? ?? const [];
            return RefreshIndicator(
              onRefresh: () async => _refresh(),
              child: ListView(
                padding: const EdgeInsets.fromLTRB(14, 14, 14, 90),
                children: [
                  Container(
                    padding: const EdgeInsets.all(18),
                    decoration: BoxDecoration(
                      color: kNavy,
                      borderRadius: BorderRadius.circular(18),
                    ),
                    child: Row(
                      children: [
                        _metric('Pending', stats['pending']),
                        _metric('Shortlisted', stats['shortlisted']),
                        _metric('Admitted', stats['admitted']),
                      ],
                    ),
                  ),
                  const SizedBox(height: 12),
                  SingleChildScrollView(
                    scrollDirection: Axis.horizontal,
                    child: Row(
                      children: [
                        'all',
                        'pending',
                        'shortlisted',
                        'admitted',
                        'rejected'
                      ]
                          .map(
                            (value) => Padding(
                              padding: const EdgeInsets.only(right: 7),
                              child: ChoiceChip(
                                label: Text(_cap(value)),
                                selected: _status == value,
                                onSelected: (_) => setState(() {
                                  _status = value;
                                  _future = _load();
                                }),
                              ),
                            ),
                          )
                          .toList(),
                    ),
                  ),
                  const SizedBox(height: 10),
                  if (items.isEmpty)
                    const Padding(
                      padding: EdgeInsets.all(40),
                      child: Text(
                        'No applications match this filter.',
                        textAlign: TextAlign.center,
                        style: TextStyle(color: kMuted),
                      ),
                    )
                  else
                    ...items.map((raw) {
                      final item = (raw as Map).cast<String, dynamic>();
                      return Card(
                        margin: const EdgeInsets.only(bottom: 10),
                        child: ListTile(
                          leading: const CircleAvatar(
                            backgroundColor: kNavy,
                            child: Icon(Icons.school_outlined, color: kGold),
                          ),
                          title: Text(
                            item['name']?.toString() ?? 'Applicant',
                            style: const TextStyle(fontWeight: FontWeight.w700),
                          ),
                          subtitle: Text(
                            '${item['application_number'] ?? ''}\n${item['class_level'] ?? 'Class not selected'} · ${item['guardian_phone'] ?? ''}',
                          ),
                          isThreeLine: true,
                          trailing: PopupMenuButton<String>(
                            onSelected: (value) =>
                                _changeStatus(item, value, data),
                            itemBuilder: (_) => const [
                              PopupMenuItem(
                                value: 'shortlisted',
                                child: Text('Shortlist'),
                              ),
                              PopupMenuItem(
                                value: 'admitted',
                                child: Text('Admit'),
                              ),
                              PopupMenuItem(
                                value: 'rejected',
                                child: Text('Reject'),
                              ),
                              PopupMenuItem(
                                value: 'withdrawn',
                                child: Text('Withdraw'),
                              ),
                            ],
                          ),
                        ),
                      );
                    }),
                ],
              ),
            );
          },
        ),
      );

  Widget _metric(String label, dynamic value) => Expanded(
        child: Column(
          children: [
            Text(
              '${value ?? 0}',
              style: const TextStyle(
                color: Colors.white,
                fontSize: 22,
                fontWeight: FontWeight.w800,
              ),
            ),
            Text(
              label,
              style: const TextStyle(color: Color(0xFFCFDCF0), fontSize: 10),
            ),
          ],
        ),
      );

  Widget _error(String message) => Center(
        child: Padding(
          padding: const EdgeInsets.all(28),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Text(message, textAlign: TextAlign.center),
              const SizedBox(height: 12),
              FilledButton(onPressed: _refresh, child: const Text('Retry')),
            ],
          ),
        ),
      );

  Future<void> _changeStatus(
    Map<String, dynamic> item,
    String status,
    Map<String, dynamic> data,
  ) async {
    int? armId;
    if (status == 'admitted') {
      final arms = data['class_arms'] as List<dynamic>? ?? const [];
      armId = await showDialog<int>(
        context: context,
        builder: (context) => SimpleDialog(
          title: const Text('Choose enrollment class'),
          children: arms.map((raw) {
            final arm = (raw as Map).cast<String, dynamic>();
            return SimpleDialogOption(
              onPressed: () => Navigator.pop(context, arm['id'] as int),
              child: Text(arm['name']?.toString() ?? 'Class'),
            );
          }).toList(),
        ),
      );
      if (armId == null) return;
    }
    try {
      await ApiClient.instance.patch('/admissions/${item['id']}/status', {
        'status': status,
        if (armId != null) 'class_arm_id': armId,
      });
      if (!mounted) return;
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(SnackBar(content: Text('Application ${_cap(status)}.')));
      _refresh();
    } catch (error) {
      if (mounted)
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(error.toString()), backgroundColor: kRisk),
        );
    }
  }

  Future<void> _openCreate() async {
    final data = await _future;
    if (!mounted) return;
    final created = await showDialog<bool>(
      context: context,
      builder: (_) => _AdmissionForm(
        levels: data['class_levels'] as List<dynamic>? ?? const [],
      ),
    );
    if (created == true) _refresh();
  }

  static String _cap(String value) =>
      value.isEmpty ? value : '${value[0].toUpperCase()}${value.substring(1)}';
}

class _AdmissionForm extends StatefulWidget {
  const _AdmissionForm({required this.levels});
  final List<dynamic> levels;

  @override
  State<_AdmissionForm> createState() => _AdmissionFormState();
}

class _AdmissionFormState extends State<_AdmissionForm> {
  final _form = GlobalKey<FormState>();
  final _first = TextEditingController();
  final _last = TextEditingController();
  final _dob = TextEditingController();
  final _guardian = TextEditingController();
  final _phone = TextEditingController();
  final _email = TextEditingController();
  final _relationship = TextEditingController(text: 'Parent');
  String _gender = 'male';
  int? _levelId;
  bool _saving = false;

  @override
  void dispose() {
    for (final c in [
      _first,
      _last,
      _dob,
      _guardian,
      _phone,
      _email,
      _relationship,
    ]) {
      c.dispose();
    }
    super.dispose();
  }

  @override
  Widget build(BuildContext context) => AlertDialog(
        title: const Text('New application'),
        content: SizedBox(
          width: 500,
          child: Form(
            key: _form,
            child: SingleChildScrollView(
              child: Column(
                children: [
                  _field(_first, 'First name'),
                  _field(_last, 'Last name'),
                  _field(_dob, 'Date of birth (YYYY-MM-DD)'),
                  DropdownButtonFormField<String>(
                    value: _gender,
                    decoration: const InputDecoration(labelText: 'Gender'),
                    items: const [
                      DropdownMenuItem(value: 'male', child: Text('Male')),
                      DropdownMenuItem(value: 'female', child: Text('Female')),
                    ],
                    onChanged: (v) => _gender = v ?? 'male',
                  ),
                  const SizedBox(height: 10),
                  DropdownButtonFormField<int>(
                    decoration: const InputDecoration(
                      labelText: 'Applying for class',
                    ),
                    items: widget.levels.map((raw) {
                      final item = (raw as Map).cast<String, dynamic>();
                      return DropdownMenuItem<int>(
                        value: item['id'] as int,
                        child: Text(item['name']?.toString() ?? 'Class'),
                      );
                    }).toList(),
                    onChanged: (v) => _levelId = v,
                  ),
                  const SizedBox(height: 10),
                  _field(_guardian, 'Guardian name'),
                  _field(_phone, 'Guardian phone'),
                  _field(_email, 'Guardian email', required: false),
                  _field(_relationship, 'Relationship'),
                ],
              ),
            ),
          ),
        ),
        actions: [
          TextButton(
            onPressed: _saving ? null : () => Navigator.pop(context),
            child: const Text('Cancel'),
          ),
          FilledButton(
            onPressed: _saving ? null : _save,
            child: Text(_saving ? 'Saving...' : 'Create'),
          ),
        ],
      );

  Widget _field(
    TextEditingController controller,
    String label, {
    bool required = true,
  }) =>
      Padding(
        padding: const EdgeInsets.only(bottom: 10),
        child: TextFormField(
          controller: controller,
          decoration: InputDecoration(labelText: label),
          validator: required
              ? (v) => (v == null || v.trim().isEmpty) ? 'Required' : null
              : null,
        ),
      );

  Future<void> _save() async {
    if (!_form.currentState!.validate()) return;
    setState(() => _saving = true);
    try {
      await ApiClient.instance.post('/admissions', {
        'first_name': _first.text.trim(),
        'last_name': _last.text.trim(),
        'date_of_birth': _dob.text.trim(),
        'gender': _gender,
        if (_levelId != null) 'applying_for_class_level_id': _levelId,
        'guardian_name': _guardian.text.trim(),
        'guardian_phone': _phone.text.trim(),
        'guardian_email': _email.text.trim(),
        'guardian_relationship': _relationship.text.trim(),
      });
      if (mounted) Navigator.pop(context, true);
    } catch (error) {
      if (mounted)
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(error.toString()), backgroundColor: kRisk),
        );
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }
}

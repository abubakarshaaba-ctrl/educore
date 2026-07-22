import 'package:flutter/material.dart';

import '../api_client.dart';
import '../main.dart';
import 'login_screen.dart';
import 'staff_attendance_screen.dart';

class HealthOfficerScreen extends StatefulWidget {
  const HealthOfficerScreen({super.key});
  @override
  State<HealthOfficerScreen> createState() => _HealthOfficerScreenState();
}

class _HealthOfficerScreenState extends State<HealthOfficerScreen> {
  int _tab = 0;
  String _search = '';
  late Future<Map<String, dynamic>> _future = _load();
  Future<Map<String, dynamic>> _load() =>
      ApiClient.instance.get('/health-officer/dashboard');
  void _refresh() => setState(() => _future = _load());

  @override
  Widget build(BuildContext context) {
    const titles = [
      'Health Overview',
      'Student Records',
      'Clinical Alerts',
      'More'
    ];
    return Scaffold(
      appBar: AppBar(title: Text(titles[_tab]), actions: [
        IconButton(tooltip: 'My attendance', icon: const Icon(Icons.badge_outlined), onPressed: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const StaffAttendanceScreen()))),
        const Padding(
            padding: EdgeInsets.only(right: 10),
            child: Chip(
                avatar: Icon(Icons.health_and_safety_outlined,
                    size: 16, color: kGold),
                label: Text('HEALTH OFFICER',
                    style: TextStyle(
                        color: Colors.white,
                        fontSize: 9.5,
                        fontWeight: FontWeight.w800)),
                backgroundColor: Color(0xFF0A2A5E),
                side: BorderSide(color: Color(0x557CA7DA)),
                visualDensity: VisualDensity.compact))
      ]),
      body: FutureBuilder<Map<String, dynamic>>(
        future: _future,
        builder: (context, snapshot) {
          if (snapshot.connectionState == ConnectionState.waiting) {
            return const Center(child: CircularProgressIndicator());
          }
          if (snapshot.hasError) {
            return _HealthError(error: snapshot.error, retry: _refresh);
          }
          final data = snapshot.data ?? const <String, dynamic>{};
          return IndexedStack(index: _tab, children: [
            _HealthOverview(data: data, refresh: _refresh),
            _StudentRecords(
                data: data,
                search: _search,
                onSearch: (value) => setState(() => _search = value),
                refresh: _refresh),
            _ClinicalAlerts(data: data, refresh: _refresh),
            const _HealthMore(),
          ]);
        },
      ),
      bottomNavigationBar: NavigationBar(
          selectedIndex: _tab,
          onDestinationSelected: (value) => setState(() => _tab = value),
          destinations: const [
            NavigationDestination(
                icon: Icon(Icons.dashboard_outlined),
                selectedIcon: Icon(Icons.dashboard),
                label: 'Overview'),
            NavigationDestination(
                icon: Icon(Icons.folder_shared_outlined),
                selectedIcon: Icon(Icons.folder_shared),
                label: 'Records'),
            NavigationDestination(
                icon: Icon(Icons.warning_amber_outlined),
                selectedIcon: Icon(Icons.warning_amber),
                label: 'Alerts'),
            NavigationDestination(
                icon: Icon(Icons.grid_view_outlined),
                selectedIcon: Icon(Icons.grid_view),
                label: 'More'),
          ]),
    );
  }
}

class _HealthOverview extends StatelessWidget {
  const _HealthOverview({required this.data, required this.refresh});
  final Map<String, dynamic> data;
  final VoidCallback refresh;
  @override
  Widget build(BuildContext context) {
    final metrics = _healthMap(data['metrics']);
    return RefreshIndicator(
        onRefresh: () async => refresh(),
        child: ListView(padding: const EdgeInsets.all(14), children: [
          Container(
              padding: const EdgeInsets.all(20),
              decoration: BoxDecoration(
                  gradient:
                      const LinearGradient(colors: [kNavy, Color(0xFF0A346F)]),
                  borderRadius: BorderRadius.circular(20)),
              child: const Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Icon(Icons.health_and_safety_rounded,
                        color: kGold, size: 32),
                    SizedBox(height: 12),
                    Text('Student wellbeing, handled with care',
                        style: TextStyle(
                            color: Colors.white,
                            fontSize: 21,
                            fontWeight: FontWeight.w800)),
                    SizedBox(height: 5),
                    Text(
                        'Review essential medical information and keep emergency contacts current.',
                        style: TextStyle(color: Color(0xFFCFDCF0))),
                    SizedBox(height: 14),
                    Row(children: [
                      Icon(Icons.lock_outline, color: kGold, size: 16),
                      SizedBox(width: 6),
                      Text('Confidential health records · Restricted access',
                          style: TextStyle(
                              color: Colors.white,
                              fontSize: 11,
                              fontWeight: FontWeight.w700))
                    ])
                  ])),
          const SizedBox(height: 18),
          const _HealthHeading('Health records at a glance'),
          GridView.count(
              shrinkWrap: true,
              physics: const NeverScrollableScrollPhysics(),
              crossAxisCount: 2,
              childAspectRatio: 1.55,
              mainAxisSpacing: 10,
              crossAxisSpacing: 10,
              children: [
                _HealthMetric(
                    icon: Icons.school_outlined,
                    label: 'Active students',
                    value: '${metrics['students'] ?? 0}',
                    color: kNavy),
                _HealthMetric(
                    icon: Icons.folder_shared_outlined,
                    label: 'Health records',
                    value: '${metrics['records'] ?? 0}',
                    color: kGood),
                _HealthMetric(
                    icon: Icons.warning_amber_rounded,
                    label: 'Allergy alerts',
                    value: '${metrics['allergy_alerts'] ?? 0}',
                    color: kRisk),
                _HealthMetric(
                    icon: Icons.medication_outlined,
                    label: 'On medication',
                    value: '${metrics['medication_alerts'] ?? 0}',
                    color: kGold),
              ]),
          const SizedBox(height: 20),
          const _HealthHeading('Clinical priorities'),
          const _HealthTile(
              icon: Icons.emergency_outlined,
              title: 'Emergency-ready information',
              subtitle:
                  'Keep contacts, allergies, chronic conditions and medication details accurate.'),
          const _HealthTile(
              icon: Icons.privacy_tip_outlined,
              title: 'Confidential by design',
              subtitle:
                  'Medical information is available only through the protected health workspace.'),
        ]));
  }
}

class _StudentRecords extends StatelessWidget {
  const _StudentRecords(
      {required this.data,
      required this.search,
      required this.onSearch,
      required this.refresh});
  final Map<String, dynamic> data;
  final String search;
  final ValueChanged<String> onSearch;
  final VoidCallback refresh;
  @override
  Widget build(BuildContext context) {
    final all = data['students'] as List<dynamic>? ?? const [];
    final query = search.toLowerCase().trim();
    final students = all
        .map(_healthMap)
        .where((item) =>
            query.isEmpty ||
            '${item['name']} ${item['admission_number']} ${item['class']}'
                .toLowerCase()
                .contains(query))
        .toList();
    return Column(children: [
      Padding(
          padding: const EdgeInsets.all(14),
          child: TextField(
              onChanged: onSearch,
              decoration: const InputDecoration(
                  prefixIcon: Icon(Icons.search),
                  hintText: 'Search student, ID or class'))),
      Expanded(
          child: students.isEmpty
              ? const _HealthEmpty(
                  icon: Icons.folder_off_outlined,
                  text: 'No matching student records.')
              : ListView.builder(
                  padding: const EdgeInsets.fromLTRB(14, 0, 14, 24),
                  itemCount: students.length,
                  itemBuilder: (_, i) {
                    final student = students[i];
                    return _HealthTile(
                        icon: student['has_record'] == true
                            ? Icons.medical_information_outlined
                            : Icons.note_add_outlined,
                        title: student['name']?.toString() ?? 'Student',
                        subtitle:
                            '${student['admission_number'] ?? 'No ID'} · ${student['class']}',
                        trailing:
                            Row(mainAxisSize: MainAxisSize.min, children: [
                          if (student['allergy_alert'] == true)
                            const Icon(Icons.warning_amber,
                                color: kRisk, size: 19),
                          const Icon(Icons.chevron_right)
                        ]),
                        onTap: () async {
                          await Navigator.push(
                              context,
                              MaterialPageRoute(
                                  builder: (_) => HealthRecordEditor(
                                      studentId: student['id'] as int)));
                          refresh();
                        });
                  }))
    ]);
  }
}

class _ClinicalAlerts extends StatelessWidget {
  const _ClinicalAlerts({required this.data, required this.refresh});
  final Map<String, dynamic> data;
  final VoidCallback refresh;
  @override
  Widget build(BuildContext context) {
    final students = (data['students'] as List<dynamic>? ?? const [])
        .map(_healthMap)
        .where((item) =>
            item['allergy_alert'] == true || item['medication_alert'] == true)
        .toList();
    if (students.isEmpty) {
      return const _HealthEmpty(
          icon: Icons.verified_outlined,
          text: 'No allergy or medication alerts recorded.');
    }
    return ListView.builder(
        padding: const EdgeInsets.all(14),
        itemCount: students.length,
        itemBuilder: (_, i) {
          final item = students[i];
          final alert = [
            if (item['allergy_alert'] == true) 'Allergy alert',
            if (item['medication_alert'] == true) 'Current medication'
          ].join(' · ');
          return _HealthTile(
              icon: Icons.health_and_safety_outlined,
              title: item['name']?.toString() ?? 'Student',
              subtitle: '${item['class']} · $alert',
              trailing: const Icon(Icons.chevron_right),
              onTap: () async {
                await Navigator.push(
                    context,
                    MaterialPageRoute(
                        builder: (_) =>
                            HealthRecordEditor(studentId: item['id'] as int)));
                refresh();
              });
        });
  }
}

class HealthRecordEditor extends StatefulWidget {
  const HealthRecordEditor({super.key, required this.studentId});
  final int studentId;
  @override
  State<HealthRecordEditor> createState() => _HealthRecordEditorState();
}

class _HealthRecordEditorState extends State<HealthRecordEditor> {
  late Future<Map<String, dynamic>> _future =
      ApiClient.instance.get('/health-officer/students/${widget.studentId}');
  final _formKey = GlobalKey<FormState>();
  final Map<String, TextEditingController> _fields = {};
  bool _ready = false;
  bool _saving = false;
  static const _labels = <String, String>{
    'blood_group': 'Blood group',
    'genotype': 'Genotype',
    'allergies': 'Allergies',
    'chronic_conditions': 'Chronic conditions',
    'current_medications': 'Current medications',
    'disability': 'Disability or support needs',
    'emergency_contact_name': 'Emergency contact name',
    'emergency_contact_phone': 'Emergency contact phone',
    'emergency_contact_relationship': 'Relationship',
    'doctor_name': 'Doctor name',
    'doctor_phone': 'Doctor phone',
    'notes': 'Clinical notes',
  };

  void _prepare(Map<String, dynamic> record) {
    if (_ready) return;
    for (final field in _labels.keys) {
      _fields[field] =
          TextEditingController(text: record[field]?.toString() ?? '');
    }
    _ready = true;
  }

  @override
  void dispose() {
    for (final controller in _fields.values) {
      controller.dispose();
    }
    super.dispose();
  }

  Future<void> _save() async {
    if (!_formKey.currentState!.validate()) {
      return;
    }
    setState(() => _saving = true);
    try {
      await ApiClient.instance
          .post('/health-officer/students/${widget.studentId}', {
        for (final entry in _fields.entries)
          entry.key:
              entry.value.text.trim().isEmpty ? null : entry.value.text.trim()
      });
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(content: Text('Health record updated securely.')));
        Navigator.pop(context);
      }
    } catch (error) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text(error.toString()), backgroundColor: kRisk));
      }
    } finally {
      if (mounted) {
        setState(() => _saving = false);
      }
    }
  }

  @override
  Widget build(BuildContext context) => Scaffold(
      appBar: AppBar(title: const Text('Student Health Record')),
      body: FutureBuilder<Map<String, dynamic>>(
          future: _future,
          builder: (context, snapshot) {
            if (snapshot.connectionState == ConnectionState.waiting) {
              return const Center(child: CircularProgressIndicator());
            }
            if (snapshot.hasError) {
              return _HealthError(
                  error: snapshot.error,
                  retry: () => setState(() => _future = ApiClient.instance
                      .get('/health-officer/students/${widget.studentId}')));
            }
            final data = snapshot.data ?? const <String, dynamic>{};
            final student = _healthMap(data['student']);
            final record = _healthMap(data['record']);
            _prepare(record);
            return Form(
                key: _formKey,
                child: ListView(padding: const EdgeInsets.all(16), children: [
                  Container(
                      padding: const EdgeInsets.all(16),
                      decoration: BoxDecoration(
                          color: kNavy,
                          borderRadius: BorderRadius.circular(16)),
                      child: Row(children: [
                        const CircleAvatar(
                            backgroundColor: Color(0x26D79A21),
                            child: Icon(Icons.person, color: kGold)),
                        const SizedBox(width: 12),
                        Expanded(
                            child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                              Text(student['name']?.toString() ?? 'Student',
                                  style: const TextStyle(
                                      color: Colors.white,
                                      fontWeight: FontWeight.w800,
                                      fontSize: 17)),
                              Text(
                                  '${student['admission_number']} · ${student['class']}',
                                  style: const TextStyle(
                                      color: Color(0xFFCFDCF0), fontSize: 12))
                            ]))
                      ])),
                  const SizedBox(height: 18),
                  const _HealthHeading('Clinical information'),
                  ..._labels.entries.map((entry) {
                    final multiline = [
                      'allergies',
                      'chronic_conditions',
                      'current_medications',
                      'disability',
                      'notes'
                    ].contains(entry.key);
                    return Padding(
                        padding: const EdgeInsets.only(bottom: 12),
                        child: TextFormField(
                            controller: _fields[entry.key],
                            maxLines: multiline ? 3 : 1,
                            decoration:
                                InputDecoration(labelText: entry.value)));
                  }),
                  const SizedBox(height: 5),
                  FilledButton.icon(
                      onPressed: _saving ? null : _save,
                      icon: _saving
                          ? const SizedBox.square(
                              dimension: 18,
                              child: CircularProgressIndicator(strokeWidth: 2))
                          : const Icon(Icons.save_outlined),
                      label: Text(
                          _saving ? 'Saving securely…' : 'Save health record'),
                      style: FilledButton.styleFrom(
                          minimumSize: const Size.fromHeight(52)))
                ]));
          }));
}

class _HealthMore extends StatelessWidget {
  const _HealthMore();
  @override
  Widget build(BuildContext context) {
    final user = ApiClient.instance.user ?? const <String, dynamic>{};
    return ListView(padding: const EdgeInsets.all(16), children: [
      const CircleAvatar(
          radius: 35,
          backgroundColor: kNavy,
          child: Icon(Icons.health_and_safety_rounded, color: kGold, size: 32)),
      const SizedBox(height: 12),
      Text(user['name']?.toString() ?? 'Health Officer',
          textAlign: TextAlign.center,
          style: const TextStyle(
              color: kInk, fontSize: 19, fontWeight: FontWeight.w800)),
      const Text('Health Officer · Confidential records only',
          textAlign: TextAlign.center, style: TextStyle(color: kMuted)),
      const SizedBox(height: 25),
      const _HealthTile(
          icon: Icons.privacy_tip_outlined,
          title: 'Medical confidentiality',
          subtitle:
              'Student health information is restricted to authorized health personnel.'),
      const SizedBox(height: 18),
      OutlinedButton.icon(
          onPressed: () async {
            await ApiClient.instance.logout();
            if (context.mounted) {
              Navigator.of(context).pushAndRemoveUntil(
                  MaterialPageRoute(builder: (_) => const LoginScreen()),
                  (_) => false);
            }
          },
          icon: const Icon(Icons.logout),
          label: const Text('Sign out'),
          style: OutlinedButton.styleFrom(
              foregroundColor: kRisk, minimumSize: const Size.fromHeight(50)))
    ]);
  }
}

class _HealthMetric extends StatelessWidget {
  const _HealthMetric(
      {required this.icon,
      required this.label,
      required this.value,
      required this.color});
  final IconData icon;
  final String label;
  final String value;
  final Color color;
  @override
  Widget build(BuildContext context) => Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(15),
          border: Border.all(color: const Color(0xFFD8E0E8))),
      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        Icon(icon, color: color, size: 21),
        const Spacer(),
        Text(value,
            style: const TextStyle(
                color: kInk, fontSize: 19, fontWeight: FontWeight.w800)),
        Text(label,
            maxLines: 1, style: const TextStyle(color: kMuted, fontSize: 10.5))
      ]));
}

class _HealthHeading extends StatelessWidget {
  const _HealthHeading(this.text);
  final String text;
  @override
  Widget build(BuildContext context) => Padding(
      padding: const EdgeInsets.only(bottom: 9),
      child: Text(text,
          style: const TextStyle(
              color: kInk, fontSize: 17, fontWeight: FontWeight.w800)));
}

class _HealthTile extends StatelessWidget {
  const _HealthTile(
      {required this.icon,
      required this.title,
      required this.subtitle,
      this.trailing,
      this.onTap});
  final IconData icon;
  final String title;
  final String subtitle;
  final Widget? trailing;
  final VoidCallback? onTap;
  @override
  Widget build(BuildContext context) => Card(
      margin: const EdgeInsets.only(bottom: 10),
      child: ListTile(
          onTap: onTap,
          leading: CircleAvatar(
              backgroundColor: const Color(0x1816794B),
              child: Icon(icon, color: kGood)),
          title: Text(title,
              style: const TextStyle(color: kInk, fontWeight: FontWeight.w700)),
          subtitle: Text(subtitle,
              maxLines: 2,
              overflow: TextOverflow.ellipsis,
              style: const TextStyle(color: kMuted, fontSize: 12)),
          trailing: trailing));
}

class _HealthEmpty extends StatelessWidget {
  const _HealthEmpty({required this.icon, required this.text});
  final IconData icon;
  final String text;
  @override
  Widget build(BuildContext context) => Center(
          child: Column(mainAxisSize: MainAxisSize.min, children: [
        Icon(icon, color: kMuted, size: 48),
        const SizedBox(height: 10),
        Text(text,
            textAlign: TextAlign.center, style: const TextStyle(color: kMuted))
      ]));
}

class _HealthError extends StatelessWidget {
  const _HealthError({required this.error, required this.retry});
  final Object? error;
  final VoidCallback retry;
  @override
  Widget build(BuildContext context) => Center(
      child: Padding(
          padding: const EdgeInsets.all(25),
          child: Column(mainAxisSize: MainAxisSize.min, children: [
            const Icon(Icons.cloud_off, color: kMuted, size: 45),
            const SizedBox(height: 10),
            Text(error.toString(), textAlign: TextAlign.center),
            const SizedBox(height: 15),
            FilledButton(onPressed: retry, child: const Text('Retry'))
          ])));
}

Map<String, dynamic> _healthMap(dynamic value) => value is Map<String, dynamic>
    ? value
    : Map<String, dynamic>.from(value as Map? ?? const {});

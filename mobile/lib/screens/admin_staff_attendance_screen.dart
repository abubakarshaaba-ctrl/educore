import 'package:flutter/material.dart';

import '../api_client.dart';
import '../main.dart';

class AdminStaffAttendanceScreen extends StatefulWidget {
  const AdminStaffAttendanceScreen({super.key});
  @override
  State<AdminStaffAttendanceScreen> createState() =>
      _AdminStaffAttendanceScreenState();
}

class _AdminStaffAttendanceScreenState extends State<AdminStaffAttendanceScreen>
    with SingleTickerProviderStateMixin {
  late final TabController tabs = TabController(length: 3, vsync: this);
  @override
  void dispose() {
    tabs.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) => Scaffold(
        appBar: AppBar(
          title: const Text('Staff Attendance'),
          bottom: TabBar(controller: tabs, tabs: const [
            Tab(text: 'Overview'),
            Tab(text: 'Reports'),
            Tab(text: 'Settings')
          ]),
        ),
        body: TabBarView(controller: tabs, children: const [
          _AttendanceOverview(),
          _AttendanceReport(),
          _AttendanceSettings()
        ]),
      );
}

class _AttendanceOverview extends StatefulWidget {
  const _AttendanceOverview();
  @override
  State<_AttendanceOverview> createState() => _AttendanceOverviewState();
}

class _AttendanceOverviewState extends State<_AttendanceOverview> {
  late Future<Map<String, dynamic>> future =
      ApiClient.instance.get('/admin/staff-attendance');
  @override
  Widget build(BuildContext context) => FutureBuilder<Map<String, dynamic>>(
        future: future,
        builder: (_, snap) {
          if (snap.connectionState != ConnectionState.done)
            return const Center(child: CircularProgressIndicator());
          if (snap.hasError) return _attendanceError(snap.error);
          final data = snap.data ?? const <String, dynamic>{};
          final summary =
              (data['summary'] as Map?)?.cast<String, dynamic>() ?? const {};
          final rows = data['records'] as List<dynamic>? ?? const [];
          return RefreshIndicator(
            onRefresh: () async => setState(() =>
                future = ApiClient.instance.get('/admin/staff-attendance')),
            child: ListView(padding: const EdgeInsets.all(14), children: [
              GridView.count(
                  crossAxisCount: 2,
                  shrinkWrap: true,
                  physics: const NeverScrollableScrollPhysics(),
                  childAspectRatio: 1.8,
                  mainAxisSpacing: 9,
                  crossAxisSpacing: 9,
                  children: [
                    _attendanceMetric('Eligible', summary['eligible'], kNavy),
                    _attendanceMetric(
                        'Clocked in', summary['clocked_in'], kGood),
                    _attendanceMetric('Late', summary['late'], kGold),
                    _attendanceMetric('Absent', summary['absent'], kRisk),
                  ]),
              const SizedBox(height: 18),
              const Text('Today’s records',
                  style: TextStyle(fontSize: 17, fontWeight: FontWeight.w800)),
              ...rows.map((raw) {
                final row = (raw as Map).cast<String, dynamic>();
                return Card(
                    child: ListTile(
                  leading: const CircleAvatar(
                      backgroundColor: kNavy,
                      child: Icon(Icons.badge, color: kGold)),
                  title: Text(row['staff']?.toString() ?? 'Staff'),
                  subtitle: Text(
                      '${row['staff_id'] ?? ''} · ${row['clock_in'] ?? 'Not clocked in'}'),
                  trailing: Text(row['status']?.toString() ?? '',
                      style: const TextStyle(fontWeight: FontWeight.w700)),
                ));
              }),
            ]),
          );
        },
      );
}

class _AttendanceReport extends StatefulWidget {
  const _AttendanceReport();
  @override
  State<_AttendanceReport> createState() => _AttendanceReportState();
}

class _AttendanceReportState extends State<_AttendanceReport> {
  int month = DateTime.now().month;
  late Future<Map<String, dynamic>> future = _load();
  Future<Map<String, dynamic>> _load() => ApiClient.instance.get(
      '/admin/staff-attendance/report',
      {'month': '$month', 'year': '${DateTime.now().year}'});
  @override
  Widget build(BuildContext context) => FutureBuilder<Map<String, dynamic>>(
        future: future,
        builder: (_, snap) {
          if (snap.connectionState != ConnectionState.done)
            return const Center(child: CircularProgressIndicator());
          if (snap.hasError) return _attendanceError(snap.error);
          final rows = snap.data?['staff'] as List<dynamic>? ?? const [];
          return Column(children: [
            Padding(
                padding: const EdgeInsets.all(12),
                child: DropdownButtonFormField<int>(
                  value: month,
                  decoration: const InputDecoration(labelText: 'Report month'),
                  items: List.generate(
                      12,
                      (index) => DropdownMenuItem(
                          value: index + 1,
                          child:
                              Text('${index + 1} / ${DateTime.now().year}'))),
                  onChanged: (value) => setState(() {
                    month = value!;
                    future = _load();
                  }),
                )),
            Expanded(
                child: ListView.builder(
                    padding: const EdgeInsets.all(14),
                    itemCount: rows.length,
                    itemBuilder: (_, index) {
                      final row = (rows[index] as Map).cast<String, dynamic>();
                      return Card(
                          child: ListTile(
                              title: Text(row['name'].toString()),
                              subtitle: Text(
                                  'Present ${row['present']} · Early ${row['early']} · Late ${row['late']}'),
                              trailing: Text('${row['days']} days',
                                  style: const TextStyle(
                                      fontWeight: FontWeight.w800))));
                    })),
          ]);
        },
      );
}

class _AttendanceSettings extends StatefulWidget {
  const _AttendanceSettings();
  @override
  State<_AttendanceSettings> createState() => _AttendanceSettingsState();
}

class _AttendanceSettingsState extends State<_AttendanceSettings> {
  final resumption = TextEditingController(),
      closing = TextEditingController(),
      grace = TextEditingController();
  final latitude = TextEditingController(),
      longitude = TextEditingController(),
      radius = TextEditingController();
  bool geo = false, loading = true, saving = false;
  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    try {
      final data = await ApiClient.instance.get('/admin/staff-attendance');
      final s = (data['settings'] as Map).cast<String, dynamic>();
      resumption.text = s['resumption_time']?.toString() ?? '08:00';
      closing.text = s['closing_time']?.toString() ?? '15:00';
      grace.text = '${s['grace_minutes'] ?? 15}';
      latitude.text = '${s['geo_lat'] ?? ''}';
      longitude.text = '${s['geo_lng'] ?? ''}';
      radius.text = '${s['geo_radius_meters'] ?? 100}';
      geo = s['geo_enabled'] == true;
    } finally {
      if (mounted) setState(() => loading = false);
    }
  }

  Future<void> _save() async {
    setState(() => saving = true);
    try {
      final response =
          await ApiClient.instance.put('/admin/staff-attendance/settings', {
        'resumption_time': resumption.text,
        'closing_time': closing.text,
        'grace_minutes': int.tryParse(grace.text) ?? 0,
        'geo_enabled': geo,
        'geo_lat': double.tryParse(latitude.text),
        'geo_lng': double.tryParse(longitude.text),
        'geo_radius_meters': int.tryParse(radius.text),
      });
      if (mounted)
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(
            content: Text(response['message'].toString()),
            backgroundColor: kGood));
    } catch (error) {
      if (mounted)
        ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text(error.toString()), backgroundColor: kRisk));
    } finally {
      if (mounted) setState(() => saving = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    if (loading) return const Center(child: CircularProgressIndicator());
    return ListView(padding: const EdgeInsets.all(16), children: [
      _attendanceField(resumption, 'Resumption time (HH:mm)'),
      _attendanceField(grace, 'Grace period (minutes)', number: true),
      _attendanceField(closing, 'Closing time (HH:mm)'),
      SwitchListTile(
          value: geo,
          onChanged: (value) => setState(() => geo = value),
          title: const Text('Enable geo-fencing'),
          subtitle: const Text(
              'Require staff to be within the permitted school radius')),
      if (geo) ...[
        _attendanceField(latitude, 'School latitude', number: true),
        _attendanceField(longitude, 'School longitude', number: true),
        _attendanceField(radius, 'Allowed radius (metres)', number: true)
      ],
      const SizedBox(height: 12),
      FilledButton.icon(
          onPressed: saving ? null : _save,
          icon: const Icon(Icons.save),
          label: Text(saving ? 'Saving…' : 'Save attendance settings')),
    ]);
  }
}

Widget _attendanceField(TextEditingController controller, String label,
        {bool number = false}) =>
    Padding(
        padding: const EdgeInsets.only(bottom: 12),
        child: TextField(
            controller: controller,
            keyboardType: number
                ? const TextInputType.numberWithOptions(decimal: true)
                : TextInputType.text,
            decoration: InputDecoration(labelText: label)));
Widget _attendanceMetric(String label, dynamic value, Color color) => Container(
    padding: const EdgeInsets.all(13),
    decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(13),
        border: Border.all(color: const Color(0xFFD8E0E8))),
    child: Row(children: [
      Icon(Icons.fact_check_outlined, color: color),
      const SizedBox(width: 10),
      Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        Text('$value',
            style: TextStyle(
                fontSize: 20, fontWeight: FontWeight.w800, color: color)),
        Text(label, style: const TextStyle(fontSize: 11, color: kMuted))
      ])
    ]));
Widget _attendanceError(Object? error) => Center(
    child: Padding(
        padding: const EdgeInsets.all(24),
        child: Text(error.toString(),
            textAlign: TextAlign.center,
            style: const TextStyle(color: kRisk))));

import 'package:flutter/material.dart';
import '../api_client.dart';
import '../main.dart';

class AdminManagementScreen extends StatefulWidget {
  const AdminManagementScreen({super.key});
  @override
  State<AdminManagementScreen> createState() => _AdminManagementScreenState();
}

class _AdminManagementScreenState extends State<AdminManagementScreen>
    with SingleTickerProviderStateMixin {
  late final TabController tabs = TabController(length: 4, vsync: this);
  @override
  void dispose() {
    tabs.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) => Scaffold(
        appBar: AppBar(
            title: const Text('Manage School'),
            bottom: TabBar(controller: tabs, isScrollable: true, tabs: const [
              Tab(text: 'Students'),
              Tab(text: 'Staff'),
              Tab(text: 'Classes'),
              Tab(text: 'Subjects')
            ])),
        body: TabBarView(controller: tabs, children: const [
          _EntityList(type: 'students'),
          _EntityList(type: 'staff'),
          _EntityList(type: 'classes'),
          _EntityList(type: 'subjects')
        ]),
      );
}

class _EntityList extends StatefulWidget {
  const _EntityList({required this.type});
  final String type;
  @override
  State<_EntityList> createState() => _EntityListState();
}

class _EntityListState extends State<_EntityList> {
  late Future<List<Map<String, dynamic>>> future = _load();
  Map<String, dynamic> meta = {};
  Future<List<Map<String, dynamic>>> _load() async {
    final management = await ApiClient.instance.get('/admin/management');
    meta = management;
    if (widget.type == 'students' || widget.type == 'staff') {
      final data = await ApiClient.instance.get('/admin/${widget.type}');
      return ((data[widget.type] as List?) ?? const [])
          .map((e) => (e as Map).cast<String, dynamic>())
          .toList();
    }
    return ((management[widget.type] as List?) ?? const [])
        .map((e) => (e as Map).cast<String, dynamic>())
        .toList();
  }

  void refresh() => setState(() => future = _load());
  @override
  Widget build(BuildContext context) => Scaffold(
        body: FutureBuilder<List<Map<String, dynamic>>>(
            future: future,
            builder: (_, snap) {
              if (snap.connectionState != ConnectionState.done)
                return const Center(child: CircularProgressIndicator());
              if (snap.hasError)
                return Center(child: Text(snap.error.toString()));
              final rows = snap.data ?? [];
              return RefreshIndicator(
                  onRefresh: () async => refresh(),
                  child: ListView.builder(
                      padding: const EdgeInsets.fromLTRB(14, 14, 14, 90),
                      itemCount: rows.length,
                      itemBuilder: (_, i) {
                        final row = rows[i];
                        return Card(
                            child: ListTile(
                                leading: CircleAvatar(
                                    backgroundColor: kNavy,
                                    child: Icon(_icon, color: kGold)),
                                title: Text(_title(row),
                                    style: const TextStyle(
                                        fontWeight: FontWeight.w700)),
                                subtitle: Text(_subtitle(row)),
                                trailing: IconButton(
                                    icon: const Icon(Icons.edit_outlined),
                                    onPressed: () => _edit(row))));
                      }));
            }),
        floatingActionButton: FloatingActionButton.extended(
            onPressed: () => _edit(null),
            icon: const Icon(Icons.add),
            label: Text('Add ${_singular}')),
      );
  IconData get _icon => switch (widget.type) {
        'students' => Icons.school_outlined,
        'staff' => Icons.badge_outlined,
        'classes' => Icons.meeting_room_outlined,
        _ => Icons.menu_book_outlined
      };
  String get _singular => switch (widget.type) {
        'students' => 'student',
        'staff' => 'staff',
        'classes' => 'class',
        _ => 'subject'
      };
  String _title(Map<String, dynamic> row) =>
      row['full_name']?.toString() ?? row['name']?.toString() ?? 'Unnamed';
  String _subtitle(Map<String, dynamic> row) => switch (widget.type) {
        'students' =>
          '${row['admission_number'] ?? ''} · ${row['class'] ?? ''}',
        'staff' => '${row['role'] ?? ''} · ${row['staff_id'] ?? ''}',
        'classes' => 'Form tutor: ${row['form_tutor'] ?? 'Not assigned'}',
        _ =>
          '${row['code'] ?? 'No code'} · ${row['is_active'] == false ? 'Inactive' : 'Active'}'
      };

  Future<void> _edit(Map<String, dynamic>? row) async {
    final first = TextEditingController(
        text: widget.type == 'students'
            ? (row?['name']?.toString().split(' ').first ?? '')
            : (row?['name']?.toString() ?? ''));
    final second = TextEditingController(
        text: widget.type == 'students'
            ? (row?['name']?.toString().split(' ').skip(1).join(' ') ?? '')
            : (widget.type == 'staff'
                ? (row?['email']?.toString() ?? '')
                : (widget.type == 'subjects'
                    ? (row?['code']?.toString() ?? '')
                    : '')));
    final password = TextEditingController();
    String gender = row?['gender']?.toString() ?? 'male';
    int? classId = row?['current_class_arm_id'] as int?;
    int? levelId = row?['class_level_id'] as int?;
    int? tutorId = row?['form_tutor_id'] as int?;
    String role = row?['role_key']?.toString() ?? 'teacher';
    final result = await showDialog<Map<String, dynamic>>(
        context: context,
        builder: (ctx) => StatefulBuilder(
            builder: (ctx, setDialog) => AlertDialog(
                    title: Text('${row == null ? 'Add' : 'Edit'} $_singular'),
                    content: SingleChildScrollView(
                        child:
                            Column(mainAxisSize: MainAxisSize.min, children: [
                      TextField(
                          controller: first,
                          decoration: InputDecoration(
                              labelText: widget.type == 'students'
                                  ? 'First name'
                                  : widget.type == 'classes'
                                      ? 'Arm name'
                                      : 'Name')),
                      const SizedBox(height: 10),
                      if (widget.type == 'students') ...[
                        TextField(
                            controller: second,
                            decoration:
                                const InputDecoration(labelText: 'Last name')),
                        const SizedBox(height: 10),
                        DropdownButtonFormField<String>(
                            value: gender,
                            items: ['male', 'female', 'other']
                                .map((v) =>
                                    DropdownMenuItem(value: v, child: Text(v)))
                                .toList(),
                            onChanged: (v) => setDialog(() => gender = v!),
                            decoration:
                                const InputDecoration(labelText: 'Gender')),
                        const SizedBox(height: 10),
                        DropdownButtonFormField<int>(
                            value: classId,
                            items: _options('classes', 'full_name'),
                            onChanged: (v) => setDialog(() => classId = v),
                            decoration:
                                const InputDecoration(labelText: 'Class'))
                      ],
                      if (widget.type == 'staff') ...[
                        TextField(
                            controller: second,
                            keyboardType: TextInputType.emailAddress,
                            decoration:
                                const InputDecoration(labelText: 'Email')),
                        const SizedBox(height: 10),
                        DropdownButtonFormField<String>(
                            value: role,
                            items:
                                ((meta['staff_roles'] as List?) ?? ['teacher'])
                                    .map((v) => DropdownMenuItem(
                                        value: v.toString(),
                                        child: Text(v.toString())))
                                    .toList(),
                            onChanged: (v) => setDialog(() => role = v!),
                            decoration:
                                const InputDecoration(labelText: 'Role')),
                        if (row == null) ...[
                          const SizedBox(height: 10),
                          TextField(
                              controller: password,
                              obscureText: true,
                              decoration: const InputDecoration(
                                  labelText: 'Temporary password'))
                        ]
                      ],
                      if (widget.type == 'classes') ...[
                        DropdownButtonFormField<int>(
                            value: levelId,
                            items: _options('class_levels', 'name'),
                            onChanged: (v) => setDialog(() => levelId = v),
                            decoration: const InputDecoration(
                                labelText: 'Class level')),
                        const SizedBox(height: 10),
                        DropdownButtonFormField<int>(
                            value: tutorId,
                            items: [
                              const DropdownMenuItem<int>(
                                  value: null, child: Text('Not assigned')),
                              ..._options('teachers', 'name')
                            ],
                            onChanged: (v) => setDialog(() => tutorId = v),
                            decoration: const InputDecoration(
                                labelText: 'Form teacher'))
                      ],
                      if (widget.type == 'subjects')
                        TextField(
                            controller: second,
                            decoration:
                                const InputDecoration(labelText: 'Code')),
                    ])),
                    actions: [
                      TextButton(
                          onPressed: () => Navigator.pop(ctx),
                          child: const Text('Cancel')),
                      FilledButton(
                          onPressed: () {
                            final data = <String, dynamic>{};
                            if (widget.type == 'students') {
                              data.addAll({
                                'first_name': first.text.trim(),
                                'last_name': second.text.trim(),
                                'gender': gender,
                                'current_class_arm_id': classId
                              });
                              if (row == null)
                                data.addAll({
                                  'date_of_birth': '2015-01-01',
                                  'admission_date': DateTime.now()
                                      .toIso8601String()
                                      .split('T')
                                      .first
                                });
                            } else if (widget.type == 'staff') {
                              data.addAll({
                                'name': first.text.trim(),
                                'email': second.text.trim(),
                                'role': role
                              });
                              if (row == null) data['password'] = password.text;
                            } else if (widget.type == 'classes') {
                              data.addAll({
                                'name': first.text.trim(),
                                'class_level_id': levelId,
                                'form_tutor_id': tutorId
                              });
                            } else {
                              data.addAll({
                                'name': first.text.trim(),
                                'code': second.text.trim(),
                                'is_active': true
                              });
                            }
                            Navigator.pop(ctx, data);
                          },
                          child: const Text('Save'))
                    ])));
    first.dispose();
    second.dispose();
    password.dispose();
    if (result == null) return;
    try {
      final path = '/admin/${widget.type}${row == null ? '' : '/${row['id']}'}';
      if (row == null)
        await ApiClient.instance.post(path, result);
      else
        await ApiClient.instance.patch(path, result);
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(const SnackBar(
            content: Text('Saved successfully.'), backgroundColor: kGood));
        refresh();
      }
    } catch (e) {
      if (mounted)
        ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text(e.toString()), backgroundColor: kRisk));
    }
  }

  List<DropdownMenuItem<int>> _options(String key, String label) =>
      ((meta[key] as List?) ?? const []).map((e) {
        final m = (e as Map).cast<String, dynamic>();
        return DropdownMenuItem<int>(
            value: m['id'] as int, child: Text(m[label]?.toString() ?? ''));
      }).toList();
}

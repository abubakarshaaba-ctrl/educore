import 'package:flutter/material.dart';

import '../api_client.dart';
import '../main.dart';
import 'login_screen.dart';
import 'admission_officer_screen.dart';
import 'scores_screen.dart';
import 'web_modules_screen.dart';

class AdminHomeScreen extends StatefulWidget {
  const AdminHomeScreen({super.key});

  @override
  State<AdminHomeScreen> createState() => _AdminHomeScreenState();
}

class _AdminHomeScreenState extends State<AdminHomeScreen> {
  int _tab = 0;

  bool _module(String name) {
    final role = ApiClient.instance.user?['role_key']?.toString();
    if (role == 'admin') {
      return true;
    }
    final permissions = ApiClient.instance.permissions;
    if (permissions.any(
      (permission) =>
          permission == name ||
          permission.startsWith('$name.') ||
          permission.endsWith('.$name'),
    )) {
      return true;
    }
    const roleModules = <String, Set<String>>{
      'principal': {'students', 'staff', 'academics', 'fees'},
      'head': {'students', 'staff', 'academics', 'fees'},
      'head_teacher': {'students', 'staff', 'academics', 'fees'},
      'vice_principal': {'students', 'staff', 'academics'},
      'academic_administrator': {'students', 'staff', 'academics'},
    };
    return roleModules[role]?.contains(name) ?? false;
  }

  @override
  Widget build(BuildContext context) {
    final finance = _module('fees');
    final pages = <Widget>[
      const _AdminDashboard(),
      const _PeopleScreen(),
      const _AcademicsScreen(),
      if (finance) const _FinanceScreen(),
      const _AdminMore(),
    ];
    final destinations = <NavigationDestination>[
      const NavigationDestination(
        icon: Icon(Icons.space_dashboard_outlined),
        selectedIcon: Icon(Icons.space_dashboard_rounded),
        label: 'Overview',
      ),
      const NavigationDestination(
        icon: Icon(Icons.groups_outlined),
        selectedIcon: Icon(Icons.groups_rounded),
        label: 'People',
      ),
      const NavigationDestination(
        icon: Icon(Icons.school_outlined),
        selectedIcon: Icon(Icons.school_rounded),
        label: 'Academics',
      ),
      if (finance)
        const NavigationDestination(
          icon: Icon(Icons.account_balance_wallet_outlined),
          selectedIcon: Icon(Icons.account_balance_wallet_rounded),
          label: 'Finance',
        ),
      const NavigationDestination(
        icon: Icon(Icons.grid_view_outlined),
        selectedIcon: Icon(Icons.grid_view_rounded),
        label: 'More',
      ),
    ];
    final titles = <String>[
      'School Overview',
      'People',
      'Academics',
      if (finance) 'Finance',
      'More',
    ];

    return Scaffold(
      appBar: AppBar(
        title: Text(titles[_tab]),
        actions: const [
          Padding(
            padding: EdgeInsets.only(right: 12),
            child: Chip(
              avatar: Icon(
                Icons.admin_panel_settings_outlined,
                size: 16,
                color: kGold,
              ),
              label: Text(
                'ADMIN ACCESS',
                style: TextStyle(
                  color: Colors.white,
                  fontSize: 10,
                  fontWeight: FontWeight.w800,
                  letterSpacing: .5,
                ),
              ),
              backgroundColor: Color(0xFF0A2A5E),
              side: BorderSide(color: Color(0x557CA7DA)),
              visualDensity: VisualDensity.compact,
            ),
          ),
        ],
      ),
      body: IndexedStack(index: _tab, children: pages),
      bottomNavigationBar: NavigationBar(
        selectedIndex: _tab,
        onDestinationSelected: (value) => setState(() => _tab = value),
        destinations: destinations,
      ),
    );
  }
}

class _AdminDashboard extends StatefulWidget {
  const _AdminDashboard();
  @override
  State<_AdminDashboard> createState() => _AdminDashboardState();
}

class _AdminDashboardState extends State<_AdminDashboard> {
  late Future<Map<String, dynamic>> _future = _load();
  Future<Map<String, dynamic>> _load() =>
      ApiClient.instance.get('/admin/dashboard');

  @override
  Widget build(BuildContext context) => _AdminFuture(
        future: _future,
        onRetry: () => setState(() => _future = _load()),
        builder: (data) {
          final admin = _map(data['administrator']);
          final period = _map(data['academic_period']);
          final metrics = _map(data['metrics']);
          final operations = _map(data['operations']);
          final finance = data['finance'] is Map ? _map(data['finance']) : null;
          return RefreshIndicator(
            onRefresh: () async => setState(() => _future = _load()),
            child: ListView(
              padding: const EdgeInsets.fromLTRB(14, 14, 14, 30),
              children: [
                _AdminHero(
                  name: admin['name']?.toString() ?? 'Administrator',
                  role: admin['role']?.toString() ?? 'School Administrator',
                  period: '${period['term']} · ${period['session']}',
                ),
                const SizedBox(height: 18),
                const _Heading('Today at a glance'),
                GridView.count(
                  crossAxisCount: 2,
                  shrinkWrap: true,
                  physics: const NeverScrollableScrollPhysics(),
                  mainAxisSpacing: 10,
                  crossAxisSpacing: 10,
                  childAspectRatio: 1.55,
                  children: [
                    _Metric(
                      icon: Icons.school_outlined,
                      label: 'Students',
                      value: '${metrics['students'] ?? 0}',
                      color: kNavy,
                    ),
                    _Metric(
                      icon: Icons.badge_outlined,
                      label: 'Active staff',
                      value: '${metrics['staff'] ?? 0}',
                      color: kGold,
                    ),
                    _Metric(
                      icon: Icons.fact_check_outlined,
                      label: 'Attendance',
                      value: metrics['attendance_rate'] == null
                          ? 'Not marked'
                          : '${metrics['attendance_rate']}%',
                      color: kGood,
                    ),
                    _Metric(
                      icon: Icons.how_to_reg_outlined,
                      label: 'Admissions pending',
                      value: '${metrics['pending_admissions'] ?? 0}',
                      color: const Color(0xFFC86B16),
                    ),
                  ],
                ),
                if (finance != null) ...[
                  const SizedBox(height: 20),
                  const _Heading('Financial position'),
                  _FinanceSummary(data: finance),
                ],
                const SizedBox(height: 20),
                const _Heading('School operations'),
                Card(
                  child: Column(
                    children: [
                      _InfoRow(
                        icon: Icons.meeting_room_outlined,
                        title: 'Classes',
                        value: '${operations['classes'] ?? 0}',
                      ),
                      const Divider(height: 1),
                      _InfoRow(
                        icon: Icons.menu_book_outlined,
                        title: 'Subjects',
                        value: '${operations['subjects'] ?? 0}',
                      ),
                      const Divider(height: 1),
                      _InfoRow(
                        icon: Icons.co_present_outlined,
                        title: 'Attendance records today',
                        value: '${operations['attendance_marked'] ?? 0}',
                      ),
                    ],
                  ),
                ),
              ],
            ),
          );
        },
      );
}

class _PeopleScreen extends StatefulWidget {
  const _PeopleScreen();
  @override
  State<_PeopleScreen> createState() => _PeopleScreenState();
}

class _PeopleScreenState extends State<_PeopleScreen> {
  bool _students = true;
  late Future<Map<String, dynamic>> _future = _load();
  Future<Map<String, dynamic>> _load() =>
      ApiClient.instance.get(_students ? '/admin/students' : '/admin/staff');

  void _switch(bool students) {
    setState(() {
      _students = students;
      _future = _load();
    });
  }

  @override
  Widget build(BuildContext context) => Column(
        children: [
          Padding(
            padding: const EdgeInsets.all(14),
            child: SegmentedButton<bool>(
              segments: const [
                ButtonSegment(
                  value: true,
                  icon: Icon(Icons.school_outlined),
                  label: Text('Students'),
                ),
                ButtonSegment(
                  value: false,
                  icon: Icon(Icons.badge_outlined),
                  label: Text('Staff'),
                ),
              ],
              selected: {_students},
              onSelectionChanged: (value) => _switch(value.first),
            ),
          ),
          Expanded(
            child: _AdminFuture(
              future: _future,
              onRetry: () => setState(() => _future = _load()),
              builder: (data) {
                final items = (data[_students ? 'students' : 'staff']
                        as List<dynamic>?) ??
                    const [];
                if (items.isEmpty) {
                  return const _Empty(
                    icon: Icons.groups_outlined,
                    text: 'No records available.',
                  );
                }
                return RefreshIndicator(
                  onRefresh: () async => setState(() => _future = _load()),
                  child: ListView.builder(
                    padding: const EdgeInsets.fromLTRB(14, 0, 14, 24),
                    itemCount: items.length,
                    itemBuilder: (_, index) {
                      final item = _map(items[index]);
                      return _AdminTile(
                        icon: _students
                            ? Icons.person_outline
                            : Icons.badge_outlined,
                        title: item['name']?.toString() ?? 'Unnamed',
                        subtitle: _students
                            ? '${item['admission_number'] ?? 'No ID'} · ${item['class'] ?? 'Unassigned'}'
                            : '${item['staff_id'] ?? 'No staff ID'} · ${item['role'] ?? 'Staff'}',
                        trailing: PopupMenuButton<String>(
                          onSelected: (value) => _update(item, value),
                          itemBuilder: (_) => _students
                              ? const [
                                  PopupMenuItem(
                                    value: 'active',
                                    child: Text('Mark active'),
                                  ),
                                  PopupMenuItem(
                                    value: 'inactive',
                                    child: Text('Mark inactive'),
                                  ),
                                  PopupMenuItem(
                                    value: 'transferred',
                                    child: Text('Mark transferred'),
                                  ),
                                ]
                              : [
                                  PopupMenuItem(
                                    value: item['active'] == true
                                        ? 'deactivate'
                                        : 'activate',
                                    child: Text(
                                      item['active'] == true
                                          ? 'Deactivate account'
                                          : 'Activate account',
                                    ),
                                  ),
                                ],
                        ),
                      );
                    },
                  ),
                );
              },
            ),
          ),
        ],
      );

  Future<void> _update(Map<String, dynamic> item, String value) async {
    try {
      if (_students) {
        await ApiClient.instance.patch('/admin/students/${item['id']}', {
          'status': value,
        });
      } else {
        await ApiClient.instance.patch('/admin/staff/${item['id']}', {
          'is_active': value == 'activate',
        });
      }
      if (!mounted) return;
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(const SnackBar(content: Text('Record updated.')));
      setState(() => _future = _load());
    } catch (e) {
      if (mounted)
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(e.toString()), backgroundColor: kRisk),
        );
    }
  }
}

class _AcademicsScreen extends StatefulWidget {
  const _AcademicsScreen();
  @override
  State<_AcademicsScreen> createState() => _AcademicsScreenState();
}

class _AcademicsScreenState extends State<_AcademicsScreen> {
  late Future<Map<String, dynamic>> _future = ApiClient.instance.get(
    '/admin/academics',
  );
  @override
  Widget build(BuildContext context) => _AdminFuture(
        future: _future,
        onRetry: () => setState(
            () => _future = ApiClient.instance.get('/admin/academics')),
        builder: (data) {
          final classes = data['classes'] as List<dynamic>? ?? const [];
          return RefreshIndicator(
            onRefresh: () async => setState(
              () => _future = ApiClient.instance.get('/admin/academics'),
            ),
            child: ListView(
              padding: const EdgeInsets.all(14),
              children: [
                Row(
                  children: [
                    Expanded(
                      child: _Metric(
                        icon: Icons.meeting_room_outlined,
                        label: 'Class arms',
                        value: '${classes.length}',
                        color: kNavy,
                      ),
                    ),
                    const SizedBox(width: 10),
                    Expanded(
                      child: _Metric(
                        icon: Icons.menu_book_outlined,
                        label: 'Subjects',
                        value: '${data['subject_count'] ?? 0}',
                        color: kGold,
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 20),
                const _Heading('Class administration'),
                ...classes.map((raw) {
                  final item = _map(raw);
                  return _AdminTile(
                    icon: Icons.class_outlined,
                    title: item['name']?.toString() ?? 'Class',
                    subtitle:
                        '${item['students'] ?? 0} students · Tutor: ${item['form_tutor'] ?? 'Not assigned'}',
                  );
                }),
              ],
            ),
          );
        },
      );
}

class _FinanceScreen extends StatefulWidget {
  const _FinanceScreen();
  @override
  State<_FinanceScreen> createState() => _FinanceScreenState();
}

class _FinanceScreenState extends State<_FinanceScreen> {
  late Future<Map<String, dynamic>> _future = ApiClient.instance.get(
    '/admin/finance',
  );
  @override
  Widget build(BuildContext context) => _AdminFuture(
        future: _future,
        onRetry: () =>
            setState(() => _future = ApiClient.instance.get('/admin/finance')),
        builder: (data) {
          final invoices = data['invoices'] as List<dynamic>? ?? const [];
          return RefreshIndicator(
            onRefresh: () async => setState(
                () => _future = ApiClient.instance.get('/admin/finance')),
            child: ListView(
              padding: const EdgeInsets.all(14),
              children: [
                _FinanceSummary(data: _map(data['summary'])),
                const SizedBox(height: 20),
                const _Heading('Recent invoices'),
                ...invoices.map((raw) {
                  final item = _map(raw);
                  return _AdminTile(
                    icon: Icons.receipt_long_outlined,
                    title: item['student']?.toString() ?? 'Student invoice',
                    subtitle:
                        '${item['invoice_number']} · ${_money(item['balance'])} outstanding',
                    trailing: _Status(text: '${item['status'] ?? ''}'),
                  );
                }),
              ],
            ),
          );
        },
      );
}

class _AdminMore extends StatelessWidget {
  const _AdminMore();
  @override
  Widget build(BuildContext context) {
    final user = ApiClient.instance.user ?? const <String, dynamic>{};
    final school =
        ApiClient.instance.school?['name']?.toString() ?? 'EduCore School';
    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        const CircleAvatar(
          radius: 35,
          backgroundColor: kNavy,
          child: Icon(
            Icons.admin_panel_settings_rounded,
            color: kGold,
            size: 32,
          ),
        ),
        const SizedBox(height: 12),
        Text(
          user['name']?.toString() ?? 'School Administrator',
          textAlign: TextAlign.center,
          style: const TextStyle(
            color: kInk,
            fontSize: 19,
            fontWeight: FontWeight.w800,
          ),
        ),
        Text(
          '${user['role'] ?? 'Administrator'} · $school',
          textAlign: TextAlign.center,
          style: const TextStyle(color: kMuted),
        ),
        const SizedBox(height: 24),
        _AdminTile(
          icon: Icons.apps_rounded,
          title: 'All school modules',
          subtitle: 'Complete operational functions allowed for this role',
          onTap: () => Navigator.push(
            context,
            MaterialPageRoute(
                builder: (_) =>
                    const WebModulesScreen(title: 'School Operations')),
          ),
        ),
        _AdminTile(
          icon: Icons.how_to_reg_outlined,
          title: 'Manage admissions',
          subtitle: 'Create, shortlist, admit, and reject applications.',
          trailing: const Icon(Icons.chevron_right),
          onTap: () => Navigator.push(
            context,
            MaterialPageRoute(builder: (_) => const AdmissionOfficerScreen()),
          ),
        ),
        _AdminTile(
          icon: Icons.edit_note_rounded,
          title: 'Enter and review scores',
          subtitle: 'Open score sheets for assigned classes and subjects.',
          trailing: const Icon(Icons.chevron_right),
          onTap: () => Navigator.push(
            context,
            MaterialPageRoute(builder: (_) => const ScoresScreen()),
          ),
        ),
        const SizedBox(height: 18),
        OutlinedButton.icon(
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
          label: const Text('Sign out'),
          style: OutlinedButton.styleFrom(
            foregroundColor: kRisk,
            minimumSize: const Size.fromHeight(50),
          ),
        ),
      ],
    );
  }
}

class _AdminHero extends StatelessWidget {
  const _AdminHero({
    required this.name,
    required this.role,
    required this.period,
  });
  final String name;
  final String role;
  final String period;
  @override
  Widget build(BuildContext context) => Container(
        padding: const EdgeInsets.all(20),
        decoration: BoxDecoration(
          gradient: const LinearGradient(colors: [kNavy, Color(0xFF0A346F)]),
          borderRadius: BorderRadius.circular(20),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'Good day, ${name.split(' ').first}',
              style: const TextStyle(
                color: Colors.white,
                fontSize: 23,
                fontWeight: FontWeight.w800,
              ),
            ),
            const SizedBox(height: 5),
            Text(period, style: const TextStyle(color: Color(0xFFCFDCF0))),
            const SizedBox(height: 15),
            Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                const Icon(Icons.shield_outlined, color: kGold, size: 17),
                const SizedBox(width: 6),
                Flexible(
                  child: Text(
                    '$role · Authorized modules only',
                    style: const TextStyle(
                      color: Colors.white,
                      fontSize: 12,
                      fontWeight: FontWeight.w700,
                    ),
                  ),
                ),
              ],
            ),
          ],
        ),
      );
}

class _FinanceSummary extends StatelessWidget {
  const _FinanceSummary({required this.data});
  final Map<String, dynamic> data;
  @override
  Widget build(BuildContext context) => Container(
        padding: const EdgeInsets.all(17),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(17),
          border: Border.all(color: const Color(0xFFD8E0E8)),
        ),
        child: Column(
          children: [
            _MoneyRow(
              label: 'Total billed',
              value: _money(data['billed']),
              color: kInk,
            ),
            const SizedBox(height: 13),
            _MoneyRow(
              label: 'Collected',
              value: _money(data['collected']),
              color: kGood,
            ),
            const Divider(height: 26),
            _MoneyRow(
              label: 'Outstanding',
              value: _money(data['outstanding']),
              color: kRisk,
            ),
          ],
        ),
      );
}

class _MoneyRow extends StatelessWidget {
  const _MoneyRow({
    required this.label,
    required this.value,
    required this.color,
  });
  final String label;
  final String value;
  final Color color;
  @override
  Widget build(BuildContext context) => Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(
            label,
            style: const TextStyle(color: kMuted, fontWeight: FontWeight.w600),
          ),
          Text(
            value,
            style: TextStyle(
              color: color,
              fontWeight: FontWeight.w800,
              fontSize: 17,
            ),
          ),
        ],
      );
}

class _Metric extends StatelessWidget {
  const _Metric({
    required this.icon,
    required this.label,
    required this.value,
    required this.color,
  });
  final IconData icon;
  final String label;
  final String value;
  final Color color;
  @override
  Widget build(BuildContext context) => Container(
        padding: const EdgeInsets.all(14),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(16),
          border: Border.all(color: const Color(0xFFD8E0E8)),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Icon(icon, color: color, size: 22),
            const Spacer(),
            Text(
              value,
              maxLines: 1,
              style: const TextStyle(
                color: kInk,
                fontSize: 20,
                fontWeight: FontWeight.w800,
              ),
            ),
            Text(
              label,
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: const TextStyle(color: kMuted, fontSize: 11),
            ),
          ],
        ),
      );
}

class _InfoRow extends StatelessWidget {
  const _InfoRow({
    required this.icon,
    required this.title,
    required this.value,
  });
  final IconData icon;
  final String title;
  final String value;
  @override
  Widget build(BuildContext context) => ListTile(
        leading: CircleAvatar(
          backgroundColor: const Color(0x14071E45),
          child: Icon(icon, color: kNavy),
        ),
        title: Text(
          title,
          style: const TextStyle(color: kInk, fontWeight: FontWeight.w600),
        ),
        trailing: Text(
          value,
          style: const TextStyle(
            color: kNavy,
            fontSize: 17,
            fontWeight: FontWeight.w800,
          ),
        ),
      );
}

class _AdminTile extends StatelessWidget {
  const _AdminTile({
    required this.icon,
    required this.title,
    required this.subtitle,
    this.trailing,
    this.onTap,
  });
  final IconData icon;
  final String title;
  final String subtitle;
  final Widget? trailing;
  final VoidCallback? onTap;
  @override
  Widget build(BuildContext context) => Card(
        margin: const EdgeInsets.only(bottom: 10),
        child: ListTile(
          leading: CircleAvatar(
            backgroundColor: const Color(0x1FD79A21),
            child: Icon(icon, color: kNavy),
          ),
          title: Text(
            title,
            style: const TextStyle(color: kInk, fontWeight: FontWeight.w700),
          ),
          subtitle: Text(
            subtitle,
            maxLines: 2,
            overflow: TextOverflow.ellipsis,
            style: const TextStyle(color: kMuted, fontSize: 12),
          ),
          trailing: trailing,
          onTap: onTap,
        ),
      );
}

class _Status extends StatelessWidget {
  const _Status({required this.text});
  final String text;
  @override
  Widget build(BuildContext context) => Container(
        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 5),
        decoration: BoxDecoration(
          color: const Color(0x1416794B),
          borderRadius: BorderRadius.circular(20),
        ),
        child: Text(
          text.replaceAll('_', ' '),
          style: const TextStyle(
            color: kGood,
            fontSize: 10,
            fontWeight: FontWeight.w700,
          ),
        ),
      );
}

class _Heading extends StatelessWidget {
  const _Heading(this.text);
  final String text;
  @override
  Widget build(BuildContext context) => Padding(
        padding: const EdgeInsets.only(bottom: 9),
        child: Text(
          text,
          style: const TextStyle(
            color: kInk,
            fontSize: 17,
            fontWeight: FontWeight.w800,
          ),
        ),
      );
}

class _Empty extends StatelessWidget {
  const _Empty({required this.icon, required this.text});
  final IconData icon;
  final String text;
  @override
  Widget build(BuildContext context) => Center(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(icon, color: kMuted, size: 48),
            const SizedBox(height: 10),
            Text(text, style: const TextStyle(color: kMuted)),
          ],
        ),
      );
}

class _AdminFuture extends StatelessWidget {
  const _AdminFuture({
    required this.future,
    required this.onRetry,
    required this.builder,
  });
  final Future<Map<String, dynamic>> future;
  final VoidCallback onRetry;
  final Widget Function(Map<String, dynamic>) builder;
  @override
  Widget build(BuildContext context) => FutureBuilder<Map<String, dynamic>>(
        future: future,
        builder: (context, snapshot) {
          if (snapshot.connectionState == ConnectionState.waiting) {
            return const Center(child: CircularProgressIndicator());
          }
          if (snapshot.hasError) {
            return Center(
              child: Padding(
                padding: const EdgeInsets.all(28),
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    const Icon(Icons.cloud_off_rounded,
                        color: kMuted, size: 46),
                    const SizedBox(height: 12),
                    Text(
                      snapshot.error.toString(),
                      textAlign: TextAlign.center,
                      style: const TextStyle(color: kMuted),
                    ),
                    const SizedBox(height: 16),
                    FilledButton(
                        onPressed: onRetry, child: const Text('Retry')),
                  ],
                ),
              ),
            );
          }
          return builder(snapshot.data ?? const {});
        },
      );
}

Map<String, dynamic> _map(dynamic value) => value is Map<String, dynamic>
    ? value
    : Map<String, dynamic>.from(value as Map? ?? const {});

String _money(dynamic amount) {
  final value = (amount as num?)?.toDouble() ?? double.tryParse('$amount') ?? 0;
  final text = value.toStringAsFixed(2);
  return '₦${text.replaceAllMapped(RegExp(r'\B(?=(\d{3})+(?!\d))'), (match) => ',')}';
}

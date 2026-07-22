import 'package:flutter/material.dart';

import '../api_client.dart';
import '../main.dart';
import 'attendance_screen.dart';
import 'exam_duties_screen.dart';
import 'id_card_screen.dart';
import 'login_screen.dart';
import 'messages_screen.dart';
import 'payslip_screen.dart';
import 'scores_screen.dart';
import 'staff_attendance_screen.dart';
import 'timetable_screen.dart';

class HomeScreen extends StatefulWidget {
  const HomeScreen({super.key});

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  int _tab = 0;

  @override
  void initState() {
    super.initState();
    ApiClient.instance.refreshSession().then((_) {
      if (mounted) setState(() {});
    }).catchError((_) {
      // Cached identity remains usable while individual screens show retry UI.
    });
  }

  bool _allowed(Iterable<String> permissions) {
    final granted = ApiClient.instance.permissions;
    return granted.isEmpty || ApiClient.instance.canAny(permissions);
  }

  @override
  Widget build(BuildContext context) {
    final school = ApiClient.instance.school?['name'] ?? 'EduCore';
    final role = ApiClient.instance.user?['role']?.toString() ?? 'Staff';
    final tabs = <_StaffTab>[
      if (_allowed(['classes.view', 'students.view', 'attendance.view']))
        const _StaffTab('My Classes', 'Classes', Icons.class_outlined,
            Icons.class_, _ClassesTab()),
      if (_allowed(['scores.enter.own']))
        const _StaffTab('Enter Scores', 'Scores', Icons.edit_note_outlined,
            Icons.edit_note, ScoresScreen()),
      if (_allowed(['timetable.view', 'timetable.view.own']))
        const _StaffTab('Timetable', 'Timetable', Icons.calendar_month_outlined,
            Icons.calendar_month, _TimetableTab()),
      const _StaffTab('Clock-in', 'Clock-in', Icons.badge_outlined, Icons.badge,
          StaffAttendanceScreen()),
      const _StaffTab('More', 'More', Icons.grid_view_outlined, Icons.grid_view,
          _MoreTab()),
    ];
    if (_tab >= tabs.length) _tab = 0;

    return Scaffold(
      appBar: AppBar(
        title: Text(tabs[_tab].title),
        actions: [
          Padding(
            padding: const EdgeInsets.only(right: 10),
            child: Tooltip(
              message: 'Role-based access: $role',
              child: Chip(
                avatar: const Icon(Icons.verified_user_outlined,
                    size: 16, color: kGold),
                label: ConstrainedBox(
                  constraints: const BoxConstraints(maxWidth: 130),
                  child: Text(
                    '$role · $school',
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(color: Colors.white, fontSize: 11),
                  ),
                ),
                backgroundColor: const Color(0xFF0A2A5E),
                side: const BorderSide(color: Color(0x557CA7DA)),
                visualDensity: VisualDensity.compact,
              ),
            ),
          ),
        ],
      ),
      body: IndexedStack(
        index: _tab,
        children: tabs.map((tab) => tab.screen).toList(),
      ),
      bottomNavigationBar: NavigationBar(
        selectedIndex: _tab,
        onDestinationSelected: (i) => setState(() => _tab = i),
        destinations: tabs
            .map((tab) => NavigationDestination(
                  icon: Icon(tab.icon),
                  selectedIcon: Icon(tab.selectedIcon),
                  label: tab.label,
                ))
            .toList(),
      ),
    );
  }
}

class _StaffTab {
  const _StaffTab(
      this.title, this.label, this.icon, this.selectedIcon, this.screen);

  final String title;
  final String label;
  final IconData icon;
  final IconData selectedIcon;
  final Widget screen;
}

// ── Classes tab ─────────────────────────────────────────────────────────
class _ClassesTab extends StatefulWidget {
  const _ClassesTab();

  @override
  State<_ClassesTab> createState() => _ClassesTabState();
}

class _ClassesTabState extends State<_ClassesTab> {
  late Future<_StaffHomeData> _future;

  @override
  void initState() {
    super.initState();
    _future = _load();
  }

  Future<_StaffHomeData> _load() async {
    final responses = await Future.wait([
      ApiClient.instance.get('/classes'),
      ApiClient.instance.get('/announcements'),
    ]);
    return _StaffHomeData(
      classes: responses[0]['classes'] as List<dynamic>? ?? const [],
      announcements:
          responses[1]['announcements'] as List<dynamic>? ?? const [],
    );
  }

  @override
  Widget build(BuildContext context) {
    return RefreshIndicator(
      onRefresh: () async => setState(() => _future = _load()),
      child: FutureBuilder<_StaffHomeData>(
        future: _future,
        builder: (context, snap) {
          if (snap.connectionState != ConnectionState.done) {
            return const Center(child: CircularProgressIndicator());
          }
          if (snap.hasError) {
            return _ErrorRetry(
              message: snap.error.toString(),
              onRetry: () => setState(() => _future = _load()),
            );
          }
          final data = snap.data ?? const _StaffHomeData();
          final classes = data.classes;
          if (classes.isEmpty) {
            return const _Empty(
              icon: Icons.class_outlined,
              text:
                  'No classes assigned to you yet.\nAsk your school admin to assign you as a form tutor or subject teacher.',
            );
          }
          final uniqueArms = <int>{};
          var students = 0;
          for (final item in classes) {
            final row = item as Map<String, dynamic>;
            final id = row['id'] as int?;
            if (id != null && uniqueArms.add(id)) {
              students += (row['students_count'] as num?)?.toInt() ?? 0;
            }
          }
          final userName =
              ApiClient.instance.user?['name']?.toString() ?? 'Staff';
          final firstName = userName.trim().split(RegExp(r'\s+')).first;
          final role = ApiClient.instance.user?['role']?.toString() ?? 'Staff';

          return ListView.builder(
            padding: const EdgeInsets.fromLTRB(14, 14, 14, 28),
            itemCount: classes.length + 3,
            itemBuilder: (context, i) {
              if (i == 0) {
                return _WelcomePanel(firstName: firstName, role: role);
              }
              if (i == 1) {
                return Padding(
                  padding: const EdgeInsets.only(top: 14, bottom: 18),
                  child: Row(
                    children: [
                      Expanded(
                        child: _MetricCard(
                          icon: Icons.menu_book_rounded,
                          value: '${uniqueArms.length}',
                          label: 'Classes',
                          color: kNavy,
                        ),
                      ),
                      const SizedBox(width: 10),
                      Expanded(
                        child: _MetricCard(
                          icon: Icons.groups_rounded,
                          value: '$students',
                          label: 'Students',
                          color: kGold,
                        ),
                      ),
                      const SizedBox(width: 10),
                      Expanded(
                        child: _MetricCard(
                          icon: Icons.campaign_outlined,
                          value: '${data.announcements.length}',
                          label: 'Notices',
                          color: kGood,
                        ),
                      ),
                    ],
                  ),
                );
              }
              if (i == 2) {
                return const Padding(
                  padding: EdgeInsets.only(bottom: 10),
                  child: Text('Assigned classes',
                      style: TextStyle(
                          color: kInk,
                          fontSize: 17,
                          fontWeight: FontWeight.w800)),
                );
              }
              final classIndex = i - 3;
              final c = classes[classIndex] as Map<String, dynamic>;
              final isTutor = c['role'] == 'form_tutor';
              return Card(
                elevation: 0,
                margin: const EdgeInsets.only(bottom: 10),
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(12),
                  side: const BorderSide(color: Color(0xFFD8E0E8)),
                ),
                child: ListTile(
                  contentPadding:
                      const EdgeInsets.symmetric(horizontal: 16, vertical: 6),
                  leading: CircleAvatar(
                    backgroundColor: isTutor ? kNavy : kGold.withOpacity(.18),
                    child: Icon(
                      isTutor ? Icons.star_rounded : Icons.menu_book_rounded,
                      color: isTutor ? kGold : kNavy,
                    ),
                  ),
                  title: Text(
                    c['name'] as String? ?? '—',
                    style: const TextStyle(
                        fontWeight: FontWeight.w700, color: kInk),
                  ),
                  subtitle: Text(
                    isTutor
                        ? 'Form tutor · ${c['students_count']} students'
                        : '${(c['subject']?['name']) ?? 'Subject'} · ${c['students_count']} students',
                    style: const TextStyle(color: kMuted, fontSize: 12.5),
                  ),
                  trailing: const Icon(Icons.chevron_right, color: kMuted),
                  onTap: () => Navigator.of(context).push(
                    MaterialPageRoute(
                      builder: (_) => AttendanceScreen(
                        classArmId: c['id'] as int,
                        className: c['name'] as String? ?? 'Class',
                      ),
                    ),
                  ),
                ),
              );
            },
          );
        },
      ),
    );
  }
}

class _StaffHomeData {
  const _StaffHomeData({
    this.classes = const [],
    this.announcements = const [],
  });

  final List<dynamic> classes;
  final List<dynamic> announcements;
}

class _WelcomePanel extends StatelessWidget {
  const _WelcomePanel({required this.firstName, required this.role});

  final String firstName;
  final String role;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          colors: [kNavy, Color(0xFF0A2F69)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(20),
        boxShadow: const [
          BoxShadow(
            color: Color(0x26071E45),
            blurRadius: 22,
            offset: Offset(0, 10),
          ),
        ],
      ),
      child: Row(
        children: [
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text('Good ${_dayPart()}, $firstName',
                    style: const TextStyle(
                        color: Colors.white,
                        fontSize: 21,
                        fontWeight: FontWeight.w800)),
                const SizedBox(height: 6),
                const Text('Everything assigned to you, in one place.',
                    style: TextStyle(color: Color(0xFFCFDCF0), height: 1.4)),
                const SizedBox(height: 14),
                Container(
                  padding:
                      const EdgeInsets.symmetric(horizontal: 10, vertical: 7),
                  decoration: BoxDecoration(
                    color: const Color(0x1FFFFFFF),
                    borderRadius: BorderRadius.circular(30),
                    border: Border.all(color: const Color(0x44FFFFFF)),
                  ),
                  child: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      const Icon(Icons.shield_outlined, color: kGold, size: 16),
                      const SizedBox(width: 6),
                      Flexible(
                        child: Text('$role access',
                            overflow: TextOverflow.ellipsis,
                            style: const TextStyle(
                                color: Colors.white,
                                fontSize: 12,
                                fontWeight: FontWeight.w700)),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(width: 12),
          Container(
            width: 58,
            height: 58,
            decoration:
                const BoxDecoration(color: kGold, shape: BoxShape.circle),
            child: const Icon(Icons.school_rounded, color: kNavy, size: 30),
          ),
        ],
      ),
    );
  }

  static String _dayPart() {
    final hour = DateTime.now().hour;
    if (hour < 12) return 'morning';
    if (hour < 17) return 'afternoon';
    return 'evening';
  }
}

class _MetricCard extends StatelessWidget {
  const _MetricCard({
    required this.icon,
    required this.value,
    required this.label,
    required this.color,
  });

  final IconData icon;
  final String value;
  final String label;
  final Color color;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 14),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(15),
        border: Border.all(color: const Color(0xFFD8E0E8)),
      ),
      child: Column(
        children: [
          Icon(icon, color: color, size: 22),
          const SizedBox(height: 8),
          Text(value,
              style: const TextStyle(
                  color: kInk, fontSize: 20, fontWeight: FontWeight.w800)),
          const SizedBox(height: 2),
          Text(label,
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: const TextStyle(color: kMuted, fontSize: 11)),
        ],
      ),
    );
  }
}

// ── Announcements tab ───────────────────────────────────────────────────
class _AnnouncementsTab extends StatefulWidget {
  const _AnnouncementsTab();

  @override
  State<_AnnouncementsTab> createState() => _AnnouncementsTabState();
}

class _AnnouncementsTabState extends State<_AnnouncementsTab> {
  late Future<List<dynamic>> _future;

  @override
  void initState() {
    super.initState();
    _future = _load();
  }

  Future<List<dynamic>> _load() async {
    final data = await ApiClient.instance.get('/announcements');
    return data['announcements'] as List<dynamic>;
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
            return _ErrorRetry(
              message: snap.error.toString(),
              onRetry: () => setState(() => _future = _load()),
            );
          }
          final items = snap.data ?? [];
          if (items.isEmpty) {
            return const _Empty(
              icon: Icons.campaign_outlined,
              text: 'No announcements right now.',
            );
          }
          return ListView.separated(
            padding: const EdgeInsets.all(14),
            itemCount: items.length,
            separatorBuilder: (_, __) => const SizedBox(height: 10),
            itemBuilder: (context, i) {
              final a = items[i] as Map<String, dynamic>;
              final urgent =
                  a['priority'] == 'high' || a['priority'] == 'urgent';
              return Card(
                elevation: 0,
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(12),
                  side: BorderSide(
                    color: urgent
                        ? const Color(0xFFFECDCA)
                        : const Color(0xFFD8E0E8),
                  ),
                ),
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        children: [
                          if (urgent) ...[
                            const Icon(Icons.priority_high_rounded,
                                color: kRisk, size: 18),
                            const SizedBox(width: 6),
                          ],
                          Expanded(
                            child: Text(
                              a['title'] as String? ?? '',
                              style: const TextStyle(
                                  fontWeight: FontWeight.w700, color: kInk),
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 6),
                      Text(
                        a['body'] as String? ?? '',
                        style: const TextStyle(
                            color: kMuted, fontSize: 13, height: 1.45),
                      ),
                      const SizedBox(height: 8),
                      Text(
                        (a['publish_date'] as String? ?? '').split('T').first,
                        style: const TextStyle(color: kMuted, fontSize: 11),
                      ),
                    ],
                  ),
                ),
              );
            },
          );
        },
      ),
    );
  }
}

// ── Timetable tab (toggle: my subjects / my class) ──────────────────────
class _TimetableTab extends StatefulWidget {
  const _TimetableTab();

  @override
  State<_TimetableTab> createState() => _TimetableTabState();
}

class _TimetableTabState extends State<_TimetableTab> {
  int _mode = 0; // 0 = my subjects, 1 = my class

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        Padding(
          padding: const EdgeInsets.fromLTRB(14, 12, 14, 4),
          child: SegmentedButton<int>(
            segments: const [
              ButtonSegment(
                  value: 0,
                  label: Text('My Subjects'),
                  icon: Icon(Icons.menu_book_rounded, size: 18)),
              ButtonSegment(
                  value: 1,
                  label: Text('My Class'),
                  icon: Icon(Icons.groups_rounded, size: 18)),
            ],
            selected: {_mode},
            onSelectionChanged: (s) => setState(() => _mode = s.first),
            style: ButtonStyle(
              visualDensity: VisualDensity.compact,
            ),
          ),
        ),
        Expanded(
          child: TimetableScreen(
            key: ValueKey(_mode),
            endpoint: _mode == 0 ? '/timetable/mine' : '/timetable/form-class',
          ),
        ),
      ],
    );
  }
}

// ── More tab (profile + quick links) ────────────────────────────────────
class _MoreTab extends StatelessWidget {
  const _MoreTab();

  @override
  Widget build(BuildContext context) {
    final user = ApiClient.instance.user ?? {};
    final school = ApiClient.instance.school ?? {};
    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        Row(
          children: [
            CircleAvatar(
              radius: 30,
              backgroundColor: kNavy,
              child: Text(
                ((user['name'] as String?) ?? 'S')
                    .substring(0, 1)
                    .toUpperCase(),
                style: const TextStyle(
                    color: kGold, fontSize: 24, fontWeight: FontWeight.w800),
              ),
            ),
            const SizedBox(width: 14),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(user['name'] as String? ?? '—',
                      style: const TextStyle(
                          fontSize: 17,
                          fontWeight: FontWeight.w800,
                          color: kInk)),
                  Text('${user['role'] ?? 'staff'} · ${school['name'] ?? ''}',
                      style: const TextStyle(color: kMuted, fontSize: 12.5)),
                ],
              ),
            ),
          ],
        ),
        const SizedBox(height: 22),
        _menuTile(context, Icons.badge_outlined, 'My ID Card',
            'View and share your staff ID', const IdCardScreen()),
        _menuTile(context, Icons.fact_check_outlined, 'My Exam Duties',
            'Your personal supervision schedule', const ExamDutiesScreen()),
        _menuTile(context, Icons.receipt_long_outlined, 'My Payslips',
            'View and download payslips', const PayslipListScreen()),
        _menuTile(context, Icons.forum_outlined, 'Messages',
            'Conversations with the school', const MessagesScreen()),
        _menuTile(context, Icons.campaign_outlined, 'Announcements',
            'School news for staff', const _AnnouncementsScreen()),
        const SizedBox(height: 22),
        OutlinedButton.icon(
          style: OutlinedButton.styleFrom(
            foregroundColor: kRisk,
            side: const BorderSide(color: Color(0xFFFECDCA)),
            minimumSize: const Size.fromHeight(50),
          ),
          icon: const Icon(Icons.logout_rounded),
          label: const Text('Sign out'),
          onPressed: () async {
            await ApiClient.instance.logout();
            if (context.mounted) {
              Navigator.of(context).pushAndRemoveUntil(
                MaterialPageRoute(builder: (_) => const LoginScreen()),
                (_) => false,
              );
            }
          },
        ),
      ],
    );
  }

  Widget _menuTile(BuildContext context, IconData icon, String title,
      String subtitle, Widget screen) {
    return Card(
      elevation: 0,
      margin: const EdgeInsets.only(bottom: 10),
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(12),
        side: const BorderSide(color: Color(0xFFD8E0E8)),
      ),
      child: ListTile(
        leading: CircleAvatar(
          backgroundColor: kGold.withOpacity(.16),
          child: Icon(icon, color: kNavy),
        ),
        title: Text(title,
            style: const TextStyle(fontWeight: FontWeight.w700, color: kInk)),
        subtitle:
            Text(subtitle, style: const TextStyle(color: kMuted, fontSize: 12)),
        trailing: const Icon(Icons.chevron_right, color: kMuted),
        onTap: () => Navigator.of(context)
            .push(MaterialPageRoute(builder: (_) => screen)),
      ),
    );
  }
}

// Announcements as a standalone screen (reuses the list from _AnnouncementsTab)
class _AnnouncementsScreen extends StatelessWidget {
  const _AnnouncementsScreen();

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Announcements')),
      body: const _AnnouncementsTab(),
    );
  }
}

// ── Shared bits ─────────────────────────────────────────────────────────
class _Empty extends StatelessWidget {
  const _Empty({required this.icon, required this.text});
  final IconData icon;
  final String text;

  @override
  Widget build(BuildContext context) {
    return ListView(
      children: [
        const SizedBox(height: 120),
        Icon(icon, size: 52, color: kMuted.withOpacity(.5)),
        const SizedBox(height: 14),
        Text(text,
            textAlign: TextAlign.center,
            style: const TextStyle(color: kMuted, height: 1.5)),
      ],
    );
  }
}

class _ErrorRetry extends StatelessWidget {
  const _ErrorRetry({required this.message, required this.onRetry});
  final String message;
  final VoidCallback onRetry;

  @override
  Widget build(BuildContext context) {
    return ListView(
      padding: const EdgeInsets.all(30),
      children: [
        const SizedBox(height: 90),
        const Icon(Icons.wifi_off_rounded, size: 48, color: kMuted),
        const SizedBox(height: 14),
        Text(message,
            textAlign: TextAlign.center, style: const TextStyle(color: kMuted)),
        const SizedBox(height: 16),
        Center(
          child: FilledButton(onPressed: onRetry, child: const Text('Retry')),
        ),
      ],
    );
  }
}

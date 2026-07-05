import 'package:flutter/material.dart';

import '../api_client.dart';
import '../main.dart';
import 'attendance_screen.dart';
import 'id_card_screen.dart';
import 'login_screen.dart';
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
  Widget build(BuildContext context) {
    final school = ApiClient.instance.school?['name'] ?? 'EduCore';
    return Scaffold(
      appBar: AppBar(
        title: Text(
          const ['My Classes', 'Enter Scores', 'Timetable', 'Clock-in', 'More'][_tab],
        ),
        actions: [
          Padding(
            padding: const EdgeInsets.only(right: 12),
            child: Center(
              child: Text(
                school as String,
                style: const TextStyle(color: kGold, fontSize: 12),
                overflow: TextOverflow.ellipsis,
              ),
            ),
          ),
        ],
      ),
      body: IndexedStack(
        index: _tab,
        children: const [
          _ClassesTab(),
          ScoresScreen(),
          _TimetableTab(),
          StaffAttendanceScreen(),
          _MoreTab(),
        ],
      ),
      bottomNavigationBar: NavigationBar(
        selectedIndex: _tab,
        onDestinationSelected: (i) => setState(() => _tab = i),
        destinations: const [
          NavigationDestination(icon: Icon(Icons.class_outlined), selectedIcon: Icon(Icons.class_), label: 'Classes'),
          NavigationDestination(icon: Icon(Icons.edit_note_outlined), selectedIcon: Icon(Icons.edit_note), label: 'Scores'),
          NavigationDestination(icon: Icon(Icons.calendar_month_outlined), selectedIcon: Icon(Icons.calendar_month), label: 'Timetable'),
          NavigationDestination(icon: Icon(Icons.badge_outlined), selectedIcon: Icon(Icons.badge), label: 'Clock-in'),
          NavigationDestination(icon: Icon(Icons.grid_view_outlined), selectedIcon: Icon(Icons.grid_view), label: 'More'),
        ],
      ),
    );
  }
}

// ── Classes tab ─────────────────────────────────────────────────────────
class _ClassesTab extends StatefulWidget {
  const _ClassesTab();

  @override
  State<_ClassesTab> createState() => _ClassesTabState();
}

class _ClassesTabState extends State<_ClassesTab> {
  late Future<List<dynamic>> _future;

  @override
  void initState() {
    super.initState();
    _future = _load();
  }

  Future<List<dynamic>> _load() async {
    final data = await ApiClient.instance.get('/classes');
    return data['classes'] as List<dynamic>;
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
          final classes = snap.data ?? [];
          if (classes.isEmpty) {
            return const _Empty(
              icon: Icons.class_outlined,
              text: 'No classes assigned to you yet.\nAsk your school admin to assign you as a form tutor or subject teacher.',
            );
          }
          return ListView.separated(
            padding: const EdgeInsets.all(14),
            itemCount: classes.length,
            separatorBuilder: (_, __) => const SizedBox(height: 10),
            itemBuilder: (context, i) {
              final c = classes[i] as Map<String, dynamic>;
              final isTutor = c['role'] == 'form_tutor';
              return Card(
                elevation: 0,
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
                    style: const TextStyle(fontWeight: FontWeight.w700, color: kInk),
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
              final urgent = a['priority'] == 'high' || a['priority'] == 'urgent';
              return Card(
                elevation: 0,
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(12),
                  side: BorderSide(
                    color: urgent ? const Color(0xFFFECDCA) : const Color(0xFFD8E0E8),
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
              ButtonSegment(value: 0, label: Text('My Subjects'), icon: Icon(Icons.menu_book_rounded, size: 18)),
              ButtonSegment(value: 1, label: Text('My Class'), icon: Icon(Icons.groups_rounded, size: 18)),
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
                ((user['name'] as String?) ?? 'S').substring(0, 1).toUpperCase(),
                style: const TextStyle(color: kGold, fontSize: 24, fontWeight: FontWeight.w800),
              ),
            ),
            const SizedBox(width: 14),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(user['name'] as String? ?? '—',
                      style: const TextStyle(fontSize: 17, fontWeight: FontWeight.w800, color: kInk)),
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
        _menuTile(context, Icons.receipt_long_outlined, 'My Payslips',
            'View and download payslips', const PayslipListScreen()),
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
        subtitle: Text(subtitle,
            style: const TextStyle(color: kMuted, fontSize: 12)),
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

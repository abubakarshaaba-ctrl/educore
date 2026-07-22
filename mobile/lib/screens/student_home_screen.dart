import 'package:flutter/material.dart';

import '../api_client.dart';
import '../main.dart';
import 'login_screen.dart';

class StudentHomeScreen extends StatefulWidget {
  const StudentHomeScreen({super.key});

  @override
  State<StudentHomeScreen> createState() => _StudentHomeScreenState();
}

class _StudentHomeScreenState extends State<StudentHomeScreen> {
  int _tab = 0;

  static const _titles = [
    'My Learning',
    'Classes',
    'CBT Exams',
    'Results',
    'More'
  ];

  @override
  Widget build(BuildContext context) {
    final school = ApiClient.instance.school?['name']?.toString() ?? 'EduCore';
    return Scaffold(
      appBar: AppBar(
        title: Text(_titles[_tab]),
        actions: [
          Padding(
            padding: const EdgeInsets.only(right: 10),
            child: Chip(
              avatar: const Icon(Icons.shield_outlined, size: 16, color: kGold),
              label: Text(
                'Student · $school',
                overflow: TextOverflow.ellipsis,
                style: const TextStyle(color: Colors.white, fontSize: 11),
              ),
              backgroundColor: const Color(0xFF0A2A5E),
              side: const BorderSide(color: Color(0x557CA7DA)),
              visualDensity: VisualDensity.compact,
            ),
          ),
        ],
      ),
      body: IndexedStack(
        index: _tab,
        children: const [
          _StudentDashboard(),
          _StudentTimetable(),
          _StudentExams(),
          _StudentResults(),
          _StudentMore(),
        ],
      ),
      bottomNavigationBar: NavigationBar(
        selectedIndex: _tab,
        onDestinationSelected: (value) => setState(() => _tab = value),
        destinations: const [
          NavigationDestination(
              icon: Icon(Icons.home_outlined),
              selectedIcon: Icon(Icons.home),
              label: 'Home'),
          NavigationDestination(
              icon: Icon(Icons.menu_book_outlined),
              selectedIcon: Icon(Icons.menu_book),
              label: 'Classes'),
          NavigationDestination(
              icon: Icon(Icons.quiz_outlined),
              selectedIcon: Icon(Icons.quiz),
              label: 'Exams'),
          NavigationDestination(
              icon: Icon(Icons.bar_chart_outlined),
              selectedIcon: Icon(Icons.bar_chart),
              label: 'Results'),
          NavigationDestination(
              icon: Icon(Icons.grid_view_outlined),
              selectedIcon: Icon(Icons.grid_view),
              label: 'More'),
        ],
      ),
    );
  }
}

class _StudentDashboard extends StatefulWidget {
  const _StudentDashboard();

  @override
  State<_StudentDashboard> createState() => _StudentDashboardState();
}

class _StudentDashboardState extends State<_StudentDashboard> {
  late Future<Map<String, dynamic>> _future;

  @override
  void initState() {
    super.initState();
    _future = ApiClient.instance.get('/student/dashboard');
  }

  @override
  Widget build(BuildContext context) {
    return _StudentFuture(
      future: _future,
      onRetry: () => setState(
          () => _future = ApiClient.instance.get('/student/dashboard')),
      builder: (data) {
        final student = data['student'] as Map<String, dynamic>? ?? const {};
        final attendance =
            data['attendance'] as Map<String, dynamic>? ?? const {};
        final summary = data['summary'] as Map<String, dynamic>?;
        final exams = data['upcoming_exams'] as List<dynamic>? ?? const [];
        final notices = data['announcements'] as List<dynamic>? ?? const [];
        final name = student['name']?.toString() ?? 'Student';
        final firstName = name.trim().split(RegExp(r'\s+')).first;
        final className =
            (student['class'] as Map<String, dynamic>?)?['name']?.toString() ??
                'No class assigned';

        return RefreshIndicator(
          onRefresh: () async => setState(
              () => _future = ApiClient.instance.get('/student/dashboard')),
          child: ListView(
            padding: const EdgeInsets.fromLTRB(14, 14, 14, 28),
            children: [
              _StudentHero(
                  firstName: firstName,
                  className: className,
                  admissionNumber:
                      student['admission_number']?.toString() ?? '—'),
              const SizedBox(height: 16),
              Row(
                children: [
                  Expanded(
                      child: _StudentMetric(
                          icon: Icons.calendar_today_rounded,
                          label: 'Attendance',
                          value: '${attendance['rate'] ?? 0}%',
                          color: kGold)),
                  const SizedBox(width: 10),
                  Expanded(
                      child: _StudentMetric(
                          icon: Icons.auto_graph_rounded,
                          label: 'Latest result',
                          value: summary == null
                              ? '—'
                              : '${summary['average'] ?? 0}%',
                          color: kGood)),
                  const SizedBox(width: 10),
                  Expanded(
                      child: _StudentMetric(
                          icon: Icons.quiz_rounded,
                          label: 'Upcoming CBT',
                          value: '${exams.length}',
                          color: kNavy)),
                ],
              ),
              const SizedBox(height: 22),
              const _SectionTitle('Upcoming CBT exams'),
              if (exams.isEmpty)
                const _StudentEmptyLine('No published examinations right now.')
              else
                ...exams.take(3).map((item) {
                  final exam = item as Map<String, dynamic>;
                  return _InfoTile(
                    icon: Icons.quiz_outlined,
                    title: exam['title']?.toString() ?? 'Examination',
                    subtitle:
                        '${exam['subject'] ?? 'Subject'} · ${exam['duration_minutes'] ?? 0} minutes',
                  );
                }),
              const SizedBox(height: 18),
              const _SectionTitle('School announcements'),
              if (notices.isEmpty)
                const _StudentEmptyLine('No announcements right now.')
              else
                ...notices.take(3).map((item) {
                  final notice = item as Map<String, dynamic>;
                  return _InfoTile(
                    icon: Icons.campaign_outlined,
                    title: notice['title']?.toString() ?? 'Announcement',
                    subtitle: notice['body']?.toString() ?? '',
                  );
                }),
            ],
          ),
        );
      },
    );
  }
}

class _StudentTimetable extends StatelessWidget {
  const _StudentTimetable();

  @override
  Widget build(BuildContext context) {
    return _EndpointList(
      endpoint: '/student/timetable',
      listKey: 'periods',
      emptyText: 'No timetable periods are available.',
      itemBuilder: (item) => _InfoTile(
        icon: Icons.schedule_rounded,
        title: '${item['day'] ?? ''} · ${item['subject'] ?? 'Subject'}',
        subtitle:
            '${item['start_time'] ?? ''} – ${item['end_time'] ?? ''} · ${item['teacher'] ?? 'Teacher'}${item['venue'] == null ? '' : ' · ${item['venue']}'}',
      ),
    );
  }
}

class _StudentExams extends StatelessWidget {
  const _StudentExams();

  @override
  Widget build(BuildContext context) {
    return _EndpointList(
      endpoint: '/student/exams',
      listKey: 'exams',
      emptyText: 'No published CBT examinations.',
      itemBuilder: (item) {
        final attempt = item['attempt'] as Map<String, dynamic>?;
        return _InfoTile(
          icon: Icons.computer_rounded,
          title: item['title']?.toString() ?? 'CBT Examination',
          subtitle:
              '${item['subject'] ?? 'Subject'} · ${item['duration_minutes'] ?? 0} minutes${attempt == null ? '' : ' · ${attempt['status']}'}',
        );
      },
    );
  }
}

class _StudentResults extends StatelessWidget {
  const _StudentResults();

  @override
  Widget build(BuildContext context) {
    return _EndpointList(
      endpoint: '/student/results',
      listKey: 'results',
      emptyText: 'No computed results are available yet.',
      itemBuilder: (item) => _InfoTile(
        icon: Icons.workspace_premium_outlined,
        title: '${item['term'] ?? 'Term'} · ${item['session'] ?? ''}',
        subtitle:
            'Average ${item['average'] ?? 0}% · Position ${item['position'] ?? '—'} of ${item['class_size'] ?? '—'}',
      ),
    );
  }
}

class _StudentMore extends StatelessWidget {
  const _StudentMore();

  @override
  Widget build(BuildContext context) {
    final user = ApiClient.instance.user ?? const <String, dynamic>{};
    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        CircleAvatar(
          radius: 34,
          backgroundColor: kNavy,
          child: Text(
              (user['name']?.toString() ?? 'S').substring(0, 1).toUpperCase(),
              style: const TextStyle(
                  color: kGold, fontSize: 28, fontWeight: FontWeight.w800)),
        ),
        const SizedBox(height: 12),
        Text(user['name']?.toString() ?? 'Student',
            textAlign: TextAlign.center,
            style: const TextStyle(
                color: kInk, fontSize: 19, fontWeight: FontWeight.w800)),
        const Text('Student access · Personal records only',
            textAlign: TextAlign.center, style: TextStyle(color: kMuted)),
        const SizedBox(height: 28),
        const _InfoTile(
            icon: Icons.calendar_month_outlined,
            title: 'Attendance',
            subtitle: 'Your personal attendance record'),
        const _InfoTile(
            icon: Icons.badge_outlined,
            title: 'Student ID',
            subtitle: 'Your EduCore identity'),
        const _InfoTile(
            icon: Icons.notifications_outlined,
            title: 'Announcements',
            subtitle: 'Updates from your school'),
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
          icon: const Icon(Icons.logout_rounded),
          label: const Text('Sign out'),
          style: OutlinedButton.styleFrom(
              foregroundColor: kRisk, minimumSize: const Size.fromHeight(50)),
        ),
      ],
    );
  }
}

class _EndpointList extends StatefulWidget {
  const _EndpointList(
      {required this.endpoint,
      required this.listKey,
      required this.emptyText,
      required this.itemBuilder});
  final String endpoint;
  final String listKey;
  final String emptyText;
  final Widget Function(Map<String, dynamic>) itemBuilder;

  @override
  State<_EndpointList> createState() => _EndpointListState();
}

class _EndpointListState extends State<_EndpointList> {
  late Future<Map<String, dynamic>> _future;

  @override
  void initState() {
    super.initState();
    _future = ApiClient.instance.get(widget.endpoint);
  }

  @override
  Widget build(BuildContext context) {
    return _StudentFuture(
      future: _future,
      onRetry: () =>
          setState(() => _future = ApiClient.instance.get(widget.endpoint)),
      builder: (data) {
        final items = data[widget.listKey] as List<dynamic>? ?? const [];
        return RefreshIndicator(
          onRefresh: () async =>
              setState(() => _future = ApiClient.instance.get(widget.endpoint)),
          child: ListView(
            padding: const EdgeInsets.all(14),
            children: items.isEmpty
                ? [_StudentEmptyLine(widget.emptyText)]
                : items
                    .map((item) =>
                        widget.itemBuilder(item as Map<String, dynamic>))
                    .toList(),
          ),
        );
      },
    );
  }
}

class _StudentFuture extends StatelessWidget {
  const _StudentFuture(
      {required this.future, required this.builder, required this.onRetry});
  final Future<Map<String, dynamic>> future;
  final Widget Function(Map<String, dynamic>) builder;
  final VoidCallback onRetry;

  @override
  Widget build(BuildContext context) {
    return FutureBuilder<Map<String, dynamic>>(
      future: future,
      builder: (context, snapshot) {
        if (snapshot.connectionState != ConnectionState.done) {
          return const Center(child: CircularProgressIndicator());
        }
        if (snapshot.hasError) {
          return Center(
            child: Padding(
              padding: const EdgeInsets.all(28),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  const Icon(Icons.cloud_off_rounded, color: kMuted, size: 46),
                  const SizedBox(height: 12),
                  Text(snapshot.error.toString(),
                      textAlign: TextAlign.center,
                      style: const TextStyle(color: kMuted)),
                  const SizedBox(height: 16),
                  FilledButton(onPressed: onRetry, child: const Text('Retry')),
                ],
              ),
            ),
          );
        }
        return builder(snapshot.data ?? const {});
      },
    );
  }
}

class _StudentHero extends StatelessWidget {
  const _StudentHero(
      {required this.firstName,
      required this.className,
      required this.admissionNumber});
  final String firstName;
  final String className;
  final String admissionNumber;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        gradient: const LinearGradient(colors: [kNavy, Color(0xFF0A2F69)]),
        borderRadius: BorderRadius.circular(20),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text('Hello, $firstName',
              style: const TextStyle(
                  color: Colors.white,
                  fontSize: 24,
                  fontWeight: FontWeight.w800)),
          const SizedBox(height: 6),
          Text('$className · $admissionNumber',
              style: const TextStyle(color: Color(0xFFCFDCF0))),
          const SizedBox(height: 14),
          const Row(mainAxisSize: MainAxisSize.min, children: [
            Icon(Icons.lock_outline, color: kGold, size: 16),
            SizedBox(width: 6),
            Text('Student access · Your records only',
                style: TextStyle(
                    color: Colors.white,
                    fontSize: 12,
                    fontWeight: FontWeight.w700))
          ]),
        ],
      ),
    );
  }
}

class _StudentMetric extends StatelessWidget {
  const _StudentMetric(
      {required this.icon,
      required this.label,
      required this.value,
      required this.color});
  final IconData icon;
  final String label;
  final String value;
  final Color color;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(vertical: 14, horizontal: 8),
      decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(15),
          border: Border.all(color: const Color(0xFFD8E0E8))),
      child: Column(children: [
        Icon(icon, color: color),
        const SizedBox(height: 8),
        Text(value,
            style: const TextStyle(
                color: kInk, fontSize: 19, fontWeight: FontWeight.w800)),
        Text(label,
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
            style: const TextStyle(color: kMuted, fontSize: 10.5))
      ]),
    );
  }
}

class _SectionTitle extends StatelessWidget {
  const _SectionTitle(this.text);
  final String text;
  @override
  Widget build(BuildContext context) => Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: Text(text,
          style: const TextStyle(
              color: kInk, fontSize: 17, fontWeight: FontWeight.w800)));
}

class _InfoTile extends StatelessWidget {
  const _InfoTile(
      {required this.icon, required this.title, required this.subtitle});
  final IconData icon;
  final String title;
  final String subtitle;

  @override
  Widget build(BuildContext context) {
    return Card(
      margin: const EdgeInsets.only(bottom: 10),
      child: ListTile(
        leading: CircleAvatar(
            backgroundColor: const Color(0x1FD79A21),
            child: Icon(icon, color: kNavy)),
        title: Text(title,
            style: const TextStyle(color: kInk, fontWeight: FontWeight.w700)),
        subtitle: Text(subtitle,
            maxLines: 2,
            overflow: TextOverflow.ellipsis,
            style: const TextStyle(color: kMuted, fontSize: 12)),
      ),
    );
  }
}

class _StudentEmptyLine extends StatelessWidget {
  const _StudentEmptyLine(this.text);
  final String text;
  @override
  Widget build(BuildContext context) => Padding(
      padding: const EdgeInsets.symmetric(vertical: 40),
      child: Text(text,
          textAlign: TextAlign.center, style: const TextStyle(color: kMuted)));
}

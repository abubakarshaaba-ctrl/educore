import 'package:flutter/material.dart';

import '../api_client.dart';
import '../main.dart';
import 'login_screen.dart';
import 'staff_attendance_screen.dart';

class TransportOfficerScreen extends StatefulWidget {
  const TransportOfficerScreen({super.key});
  @override
  State<TransportOfficerScreen> createState() => _TransportOfficerScreenState();
}

class _TransportOfficerScreenState extends State<TransportOfficerScreen> {
  int _tab = 0;
  late Future<Map<String, dynamic>> _future = _load();
  Future<Map<String, dynamic>> _load() =>
      ApiClient.instance.get('/transport-officer/dashboard');
  void _refresh() => setState(() => _future = _load());

  @override
  Widget build(BuildContext context) {
    const titles = [
      'Transport Overview',
      'Routes & Manifests',
      'Fleet',
      'Assignments',
      'More'
    ];
    return Scaffold(
      appBar: AppBar(
        title: Text(titles[_tab]),
        actions: [
          IconButton(
              tooltip: 'My attendance',
              icon: const Icon(Icons.badge_outlined),
              onPressed: () => Navigator.push(
                  context,
                  MaterialPageRoute(
                      builder: (_) => const StaffAttendanceScreen()))),
          const Padding(
              padding: EdgeInsets.only(right: 10),
              child: Chip(
                  avatar: Icon(Icons.shield_outlined, size: 16, color: kGold),
                  label: Text('TRANSPORT OFFICER',
                      style: TextStyle(
                          color: Colors.white,
                          fontSize: 9.5,
                          fontWeight: FontWeight.w800)),
                  backgroundColor: Color(0xFF0A2A5E),
                  side: BorderSide(color: Color(0x557CA7DA)),
                  visualDensity: VisualDensity.compact))
        ],
      ),
      body: FutureBuilder<Map<String, dynamic>>(
        future: _future,
        builder: (context, snapshot) {
          if (snapshot.connectionState == ConnectionState.waiting) {
            return const Center(child: CircularProgressIndicator());
          }
          if (snapshot.hasError) {
            return _OfficerError(error: snapshot.error, retry: _refresh);
          }
          final data = snapshot.data ?? const <String, dynamic>{};
          return IndexedStack(index: _tab, children: [
            _TransportOverview(data: data, refresh: _refresh),
            _Routes(data: data),
            _Fleet(data: data),
            _Assignments(data: data, refresh: _refresh),
            const _TransportMore(),
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
                icon: Icon(Icons.route_outlined),
                selectedIcon: Icon(Icons.route),
                label: 'Routes'),
            NavigationDestination(
                icon: Icon(Icons.directions_bus_outlined),
                selectedIcon: Icon(Icons.directions_bus),
                label: 'Fleet'),
            NavigationDestination(
                icon: Icon(Icons.person_pin_circle_outlined),
                selectedIcon: Icon(Icons.person_pin_circle),
                label: 'Assign'),
            NavigationDestination(
                icon: Icon(Icons.grid_view_outlined),
                selectedIcon: Icon(Icons.grid_view),
                label: 'More'),
          ]),
    );
  }
}

class _TransportOverview extends StatelessWidget {
  const _TransportOverview({required this.data, required this.refresh});
  final Map<String, dynamic> data;
  final VoidCallback refresh;
  @override
  Widget build(BuildContext context) {
    final metrics = _map(data['metrics']);
    final routes = data['routes'] as List<dynamic>? ?? const [];
    return RefreshIndicator(
        onRefresh: () async => refresh(),
        child: ListView(padding: const EdgeInsets.all(14), children: [
          const _Hero(
              icon: Icons.directions_bus_rounded,
              title: 'Safe journeys, every school day',
              subtitle:
                  'Monitor routes, fleet capacity and every assigned learner.',
              access: 'Transport operations · Authorized access'),
          const SizedBox(height: 18),
          const _Heading('Operations at a glance'),
          GridView.count(
              shrinkWrap: true,
              physics: const NeverScrollableScrollPhysics(),
              crossAxisCount: 2,
              childAspectRatio: 1.55,
              mainAxisSpacing: 10,
              crossAxisSpacing: 10,
              children: [
                _Metric(
                    icon: Icons.route_outlined,
                    label: 'Routes',
                    value: '${metrics['routes'] ?? 0}',
                    color: kNavy),
                _Metric(
                    icon: Icons.directions_bus_outlined,
                    label: 'Active buses',
                    value: '${metrics['active_buses'] ?? 0}',
                    color: kGood),
                _Metric(
                    icon: Icons.groups_outlined,
                    label: 'Assigned riders',
                    value: '${metrics['assigned_students'] ?? 0}',
                    color: kGold),
                _Metric(
                    icon: Icons.person_add_alt_outlined,
                    label: 'Awaiting route',
                    value: '${metrics['unassigned_students'] ?? 0}',
                    color: kRisk),
              ]),
          const SizedBox(height: 20),
          const _Heading('Route capacity'),
          ...routes.take(5).map((raw) {
            final route = _map(raw);
            final riders = route['riders'] as num? ?? 0;
            final capacity = route['capacity'] as num?;
            return _OfficerTile(
                icon: Icons.alt_route_rounded,
                title: route['name']?.toString() ?? 'Route',
                subtitle: '${route['bus']} · ${route['driver']}',
                trailing: Text(
                    capacity == null ? '$riders riders' : '$riders / $capacity',
                    style: const TextStyle(
                        color: kNavy, fontWeight: FontWeight.w800)));
          }),
        ]));
  }
}

class _Routes extends StatelessWidget {
  const _Routes({required this.data});
  final Map<String, dynamic> data;
  @override
  Widget build(BuildContext context) {
    final routes = data['routes'] as List<dynamic>? ?? const [];
    if (routes.isEmpty) {
      return const _Empty(
          icon: Icons.route_outlined, text: 'No transport routes configured.');
    }
    return ListView.builder(
        padding: const EdgeInsets.all(14),
        itemCount: routes.length,
        itemBuilder: (_, index) {
          final route = _map(routes[index]);
          return _OfficerTile(
              icon: Icons.route_rounded,
              title: route['name']?.toString() ?? 'Route',
              subtitle:
                  '${route['morning_time'] ?? '—'} morning · ${route['evening_time'] ?? '—'} evening\n${route['bus']} · Driver: ${route['driver']}',
              trailing: const Icon(Icons.chevron_right),
              onTap: () => _manifest(context, route));
        });
  }

  Future<void> _manifest(
      BuildContext context, Map<String, dynamic> route) async {
    showModalBottomSheet(
        context: context,
        isScrollControlled: true,
        builder: (_) => SizedBox(
            height: MediaQuery.sizeOf(context).height * .75,
            child: FutureBuilder<Map<String, dynamic>>(
              future: ApiClient.instance
                  .get('/transport-officer/routes/${route['id']}/manifest'),
              builder: (context, snapshot) {
                if (snapshot.connectionState == ConnectionState.waiting) {
                  return const Center(child: CircularProgressIndicator());
                }
                if (snapshot.hasError) {
                  return Center(child: Text(snapshot.error.toString()));
                }
                final items =
                    snapshot.data?['manifest'] as List<dynamic>? ?? const [];
                return Column(children: [
                  Padding(
                      padding: const EdgeInsets.all(18),
                      child: Row(children: [
                        Expanded(
                            child: Text('${route['name']} manifest',
                                style: const TextStyle(
                                    fontSize: 19,
                                    fontWeight: FontWeight.w800))),
                        IconButton(
                            onPressed: () => Navigator.pop(context),
                            icon: const Icon(Icons.close))
                      ])),
                  Expanded(
                      child: items.isEmpty
                          ? const _Empty(
                              icon: Icons.groups_outlined,
                              text: 'No students assigned.')
                          : ListView.builder(
                              padding:
                                  const EdgeInsets.symmetric(horizontal: 14),
                              itemCount: items.length,
                              itemBuilder: (_, i) {
                                final item = _map(items[i]);
                                return _OfficerTile(
                                    icon: Icons.person_outline,
                                    title:
                                        item['name']?.toString() ?? 'Student',
                                    subtitle:
                                        '${item['class']} · ${item['pickup_stop'] ?? 'No pickup stop'} · ${item['direction']}');
                              }))
                ]);
              },
            )));
  }
}

class _Fleet extends StatelessWidget {
  const _Fleet({required this.data});
  final Map<String, dynamic> data;
  @override
  Widget build(BuildContext context) {
    final buses = data['buses'] as List<dynamic>? ?? const [];
    if (buses.isEmpty) {
      return const _Empty(
          icon: Icons.directions_bus_outlined, text: 'No buses configured.');
    }
    return ListView.builder(
        padding: const EdgeInsets.all(14),
        itemCount: buses.length,
        itemBuilder: (_, i) {
          final bus = _map(buses[i]);
          return _OfficerTile(
              icon: Icons.directions_bus_filled_outlined,
              title: bus['plate_number']?.toString() ?? 'Bus',
              subtitle:
                  '${bus['model'] ?? 'Model not specified'} · ${bus['capacity']} seats · ${bus['year'] ?? 'Year unavailable'}',
              trailing:
                  _Badge(text: bus['active'] == true ? 'Active' : 'Inactive'));
        });
  }
}

class _Assignments extends StatelessWidget {
  const _Assignments({required this.data, required this.refresh});
  final Map<String, dynamic> data;
  final VoidCallback refresh;
  @override
  Widget build(BuildContext context) {
    final students = data['unassigned_students'] as List<dynamic>? ?? const [];
    final routes = (data['routes'] as List<dynamic>? ?? const [])
        .map(_map)
        .where((r) => r['active'] == true)
        .toList();
    if (students.isEmpty) {
      return const _Empty(
          icon: Icons.task_alt_rounded,
          text: 'Every active student has a transport assignment.');
    }
    return ListView.builder(
        padding: const EdgeInsets.all(14),
        itemCount: students.length,
        itemBuilder: (_, i) {
          final student = _map(students[i]);
          return _OfficerTile(
              icon: Icons.person_add_alt_outlined,
              title: student['name']?.toString() ?? 'Student',
              subtitle:
                  '${student['admission_number'] ?? 'No ID'} · ${student['class']}',
              trailing: const Icon(Icons.add_circle_outline, color: kNavy),
              onTap: () => _assign(context, student, routes));
        });
  }

  Future<void> _assign(BuildContext context, Map<String, dynamic> student,
      List<Map<String, dynamic>> routes) async {
    if (routes.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(
          content: Text('Create an active route before assigning students.')));
      return;
    }
    var routeId = routes.first['id'] as int;
    var direction = 'both';
    final stop = TextEditingController();
    final saved = await showDialog<bool>(
        context: context,
        builder: (context) => StatefulBuilder(
            builder: (context, setDialog) => AlertDialog(
                    title: Text('Assign ${student['name']}'),
                    content: Column(mainAxisSize: MainAxisSize.min, children: [
                      DropdownButtonFormField<int>(
                          initialValue: routeId,
                          decoration: const InputDecoration(labelText: 'Route'),
                          items: routes
                              .map((r) => DropdownMenuItem(
                                  value: r['id'] as int,
                                  child: Text('${r['name']}')))
                              .toList(),
                          onChanged: (value) =>
                              setDialog(() => routeId = value!)),
                      const SizedBox(height: 12),
                      TextField(
                          controller: stop,
                          decoration:
                              const InputDecoration(labelText: 'Pickup stop')),
                      const SizedBox(height: 12),
                      DropdownButtonFormField<String>(
                          initialValue: direction,
                          decoration:
                              const InputDecoration(labelText: 'Direction'),
                          items: const [
                            DropdownMenuItem(
                                value: 'both',
                                child: Text('Morning and evening')),
                            DropdownMenuItem(
                                value: 'morning', child: Text('Morning only')),
                            DropdownMenuItem(
                                value: 'evening', child: Text('Evening only'))
                          ],
                          onChanged: (value) =>
                              setDialog(() => direction = value!))
                    ]),
                    actions: [
                      TextButton(
                          onPressed: () => Navigator.pop(context, false),
                          child: const Text('Cancel')),
                      FilledButton(
                          onPressed: () async {
                            try {
                              await ApiClient.instance
                                  .post('/transport-officer/assignments', {
                                'student_id': student['id'],
                                'route_id': routeId,
                                'pickup_stop': stop.text.trim(),
                                'direction': direction
                              });
                              if (context.mounted) {
                                Navigator.pop(context, true);
                              }
                            } catch (error) {
                              if (context.mounted) {
                                ScaffoldMessenger.of(context).showSnackBar(
                                    SnackBar(content: Text(error.toString())));
                              }
                            }
                          },
                          child: const Text('Save assignment'))
                    ])));
    stop.dispose();
    if (saved == true) {
      refresh();
    }
  }
}

class _TransportMore extends StatelessWidget {
  const _TransportMore();
  @override
  Widget build(BuildContext context) => const _OfficerProfile(
      icon: Icons.directions_bus_rounded,
      access: 'Transport Officer · Transport operations only');
}

class _OfficerProfile extends StatelessWidget {
  const _OfficerProfile({required this.icon, required this.access});
  final IconData icon;
  final String access;
  @override
  Widget build(BuildContext context) {
    final user = ApiClient.instance.user ?? const <String, dynamic>{};
    return ListView(padding: const EdgeInsets.all(16), children: [
      CircleAvatar(
          radius: 35,
          backgroundColor: kNavy,
          child: Icon(icon, color: kGold, size: 32)),
      const SizedBox(height: 12),
      Text(user['name']?.toString() ?? 'Officer',
          textAlign: TextAlign.center,
          style: const TextStyle(
              color: kInk, fontSize: 19, fontWeight: FontWeight.w800)),
      Text(access,
          textAlign: TextAlign.center, style: const TextStyle(color: kMuted)),
      const SizedBox(height: 25),
      const _OfficerTile(
          icon: Icons.security_outlined,
          title: 'Role-based access',
          subtitle:
              'Only information required for your assigned duties is visible.'),
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

class _Hero extends StatelessWidget {
  const _Hero(
      {required this.icon,
      required this.title,
      required this.subtitle,
      required this.access});
  final IconData icon;
  final String title;
  final String subtitle;
  final String access;
  @override
  Widget build(BuildContext context) => Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
          gradient: const LinearGradient(colors: [kNavy, Color(0xFF0A346F)]),
          borderRadius: BorderRadius.circular(20)),
      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        Icon(icon, color: kGold, size: 30),
        const SizedBox(height: 12),
        Text(title,
            style: const TextStyle(
                color: Colors.white,
                fontSize: 21,
                fontWeight: FontWeight.w800)),
        const SizedBox(height: 5),
        Text(subtitle, style: const TextStyle(color: Color(0xFFCFDCF0))),
        const SizedBox(height: 14),
        Row(children: [
          const Icon(Icons.lock_outline, color: kGold, size: 16),
          const SizedBox(width: 6),
          Text(access,
              style: const TextStyle(
                  color: Colors.white,
                  fontSize: 11,
                  fontWeight: FontWeight.w700))
        ])
      ]));
}

class _Metric extends StatelessWidget {
  const _Metric(
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

class _Heading extends StatelessWidget {
  const _Heading(this.text);
  final String text;
  @override
  Widget build(BuildContext context) => Padding(
      padding: const EdgeInsets.only(bottom: 9),
      child: Text(text,
          style: const TextStyle(
              color: kInk, fontSize: 17, fontWeight: FontWeight.w800)));
}

class _OfficerTile extends StatelessWidget {
  const _OfficerTile(
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
              backgroundColor: const Color(0x18071E45),
              child: Icon(icon, color: kNavy)),
          title: Text(title,
              style: const TextStyle(color: kInk, fontWeight: FontWeight.w700)),
          subtitle: Text(subtitle,
              maxLines: 2,
              overflow: TextOverflow.ellipsis,
              style: const TextStyle(color: kMuted, fontSize: 12)),
          trailing: trailing));
}

class _Badge extends StatelessWidget {
  const _Badge({required this.text});
  final String text;
  @override
  Widget build(BuildContext context) => Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
      decoration: BoxDecoration(
          color: kGood.withValues(alpha: .08),
          borderRadius: BorderRadius.circular(15)),
      child: Text(text,
          style: const TextStyle(
              color: kGood, fontSize: 10, fontWeight: FontWeight.w700)));
}

class _Empty extends StatelessWidget {
  const _Empty({required this.icon, required this.text});
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

class _OfficerError extends StatelessWidget {
  const _OfficerError({required this.error, required this.retry});
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

Map<String, dynamic> _map(dynamic value) => value is Map<String, dynamic>
    ? value
    : Map<String, dynamic>.from(value as Map? ?? const {});

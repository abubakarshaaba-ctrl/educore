import 'package:flutter/material.dart';

import '../api_client.dart';
import '../main.dart';
import 'login_screen.dart';
import 'web_modules_screen.dart';

class ParentHomeScreen extends StatefulWidget {
  const ParentHomeScreen({super.key});

  @override
  State<ParentHomeScreen> createState() => _ParentHomeScreenState();
}

class _ParentHomeScreenState extends State<ParentHomeScreen> {
  int _tab = 0;
  int? _childId;

  String get _childQuery => _childId == null ? '' : '?child_id=$_childId';

  @override
  Widget build(BuildContext context) {
    final school = ApiClient.instance.school?['name']?.toString() ?? 'EduCore';
    const titles = ['My Children', 'Payments', 'Messages', 'Reports', 'More'];
    return Scaffold(
      appBar: AppBar(
        title: Text(titles[_tab]),
        actions: [
          Padding(
            padding: const EdgeInsets.only(right: 10),
            child: Chip(
              avatar: const Icon(Icons.shield_outlined, size: 16, color: kGold),
              label: Text(
                'Parent · $school',
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
        children: [
          _ParentDashboard(
            key: ValueKey('dashboard-$_childId'),
            childId: _childId,
            onChildSelected: (id) => setState(() => _childId = id),
          ),
          _ParentList(
            key: ValueKey('invoices-$_childId'),
            endpoint: '/parent/invoices$_childQuery',
            listKey: 'invoices',
            emptyText: 'No invoices for this child.',
            itemBuilder: (item) => _ParentTile(
              icon: Icons.receipt_long_outlined,
              title: item['number']?.toString() ?? 'School fee invoice',
              subtitle:
                  '${item['term'] ?? ''} · ${_money(item['balance'])} outstanding · ${item['status'] ?? ''}',
              onTap: () => _showInvoice(context, item),
            ),
          ),
          _ParentNotices(key: ValueKey('notices-$_childId'), childId: _childId),
          _ParentList(
            key: ValueKey('results-$_childId'),
            endpoint: '/parent/results$_childQuery',
            listKey: 'results',
            emptyText: 'No published results for this child.',
            itemBuilder: (item) => _ParentTile(
              icon: Icons.workspace_premium_outlined,
              title: '${item['term'] ?? 'Term'} · ${item['session'] ?? ''}',
              subtitle:
                  'Average ${item['average'] ?? 0}% · Position ${item['position'] ?? '—'} of ${item['class_size'] ?? '—'}',
              onTap: () => _showParentResult(context, item),
            ),
          ),
          const _ParentMore(),
        ],
      ),
      bottomNavigationBar: NavigationBar(
        selectedIndex: _tab,
        onDestinationSelected: (value) => setState(() => _tab = value),
        destinations: const [
          NavigationDestination(
            icon: Icon(Icons.home_outlined),
            selectedIcon: Icon(Icons.home),
            label: 'Home',
          ),
          NavigationDestination(
            icon: Icon(Icons.account_balance_wallet_outlined),
            selectedIcon: Icon(Icons.account_balance_wallet),
            label: 'Payments',
          ),
          NavigationDestination(
            icon: Icon(Icons.forum_outlined),
            selectedIcon: Icon(Icons.forum),
            label: 'Messages',
          ),
          NavigationDestination(
            icon: Icon(Icons.description_outlined),
            selectedIcon: Icon(Icons.description),
            label: 'Reports',
          ),
          NavigationDestination(
            icon: Icon(Icons.grid_view_outlined),
            selectedIcon: Icon(Icons.grid_view),
            label: 'More',
          ),
        ],
      ),
    );
  }

  static String _money(dynamic amount) {
    final value =
        (amount as num?)?.toDouble() ?? double.tryParse('$amount') ?? 0;
    return '₦${value.toStringAsFixed(2)}';
  }
}

class _ParentDashboard extends StatefulWidget {
  const _ParentDashboard({
    super.key,
    required this.childId,
    required this.onChildSelected,
  });
  final int? childId;
  final ValueChanged<int> onChildSelected;

  @override
  State<_ParentDashboard> createState() => _ParentDashboardState();
}

class _ParentDashboardState extends State<_ParentDashboard> {
  late Future<Map<String, dynamic>> _future;

  @override
  void initState() {
    super.initState();
    _future = _load();
  }

  Future<Map<String, dynamic>> _load() => ApiClient.instance.get(
    '/parent/dashboard',
    widget.childId == null ? null : {'child_id': '${widget.childId}'},
  );

  @override
  Widget build(BuildContext context) {
    return _ParentFuture(
      future: _future,
      onRetry: () => setState(() => _future = _load()),
      builder: (data) {
        final guardian = data['guardian'] as Map<String, dynamic>? ?? const {};
        final children = data['children'] as List<dynamic>? ?? const [];
        final selected = data['selected_child'] as Map<String, dynamic>?;
        final result = data['result'] as Map<String, dynamic>?;
        final attendance =
            data['attendance'] as Map<String, dynamic>? ?? const {};
        final notices = data['announcements'] as List<dynamic>? ?? const [];
        final selectedId = selected?['id'] as int?;

        return RefreshIndicator(
          onRefresh: () async => setState(() => _future = _load()),
          child: ListView(
            padding: const EdgeInsets.fromLTRB(14, 14, 14, 28),
            children: [
              _ParentHero(name: guardian['name']?.toString() ?? 'Parent'),
              const SizedBox(height: 18),
              const _ParentHeading('My children'),
              SizedBox(
                height: 92,
                child: children.isEmpty
                    ? const Center(
                        child: Text(
                          'No linked children.',
                          style: TextStyle(color: kMuted),
                        ),
                      )
                    : ListView.separated(
                        scrollDirection: Axis.horizontal,
                        itemCount: children.length,
                        separatorBuilder: (_, __) => const SizedBox(width: 10),
                        itemBuilder: (context, index) {
                          final child = children[index] as Map<String, dynamic>;
                          final id = child['id'] as int;
                          final active = id == selectedId;
                          return InkWell(
                            onTap: () => widget.onChildSelected(id),
                            borderRadius: BorderRadius.circular(15),
                            child: Container(
                              width: 190,
                              padding: const EdgeInsets.all(12),
                              decoration: BoxDecoration(
                                color: active
                                    ? const Color(0xFFFFF7E5)
                                    : Colors.white,
                                borderRadius: BorderRadius.circular(15),
                                border: Border.all(
                                  color: active
                                      ? kGold
                                      : const Color(0xFFD8E0E8),
                                  width: active ? 1.5 : 1,
                                ),
                              ),
                              child: Row(
                                children: [
                                  CircleAvatar(
                                    backgroundColor: kNavy,
                                    child: Text(
                                      (child['name']?.toString() ?? 'C')
                                          .substring(0, 1),
                                      style: const TextStyle(
                                        color: kGold,
                                        fontWeight: FontWeight.w800,
                                      ),
                                    ),
                                  ),
                                  const SizedBox(width: 10),
                                  Expanded(
                                    child: Column(
                                      crossAxisAlignment:
                                          CrossAxisAlignment.start,
                                      mainAxisAlignment:
                                          MainAxisAlignment.center,
                                      children: [
                                        Text(
                                          child['name']?.toString() ?? 'Child',
                                          maxLines: 1,
                                          overflow: TextOverflow.ellipsis,
                                          style: const TextStyle(
                                            color: kInk,
                                            fontWeight: FontWeight.w800,
                                          ),
                                        ),
                                        Text(
                                          (child['class']
                                                      as Map<
                                                        String,
                                                        dynamic
                                                      >?)?['name']
                                                  ?.toString() ??
                                              'No class',
                                          style: const TextStyle(
                                            color: kMuted,
                                            fontSize: 12,
                                          ),
                                        ),
                                      ],
                                    ),
                                  ),
                                ],
                              ),
                            ),
                          );
                        },
                      ),
              ),
              const SizedBox(height: 18),
              Row(
                children: [
                  Expanded(
                    child: _ParentMetric(
                      icon: Icons.description_outlined,
                      label: 'Latest result',
                      value: result == null
                          ? '—'
                          : '${result['average'] ?? 0}%',
                      color: kNavy,
                    ),
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    child: _ParentMetric(
                      icon: Icons.fact_check_outlined,
                      label: 'Attendance',
                      value: '${attendance['rate'] ?? 0}%',
                      color: kGood,
                    ),
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    child: _ParentMetric(
                      icon: Icons.account_balance_wallet_outlined,
                      label: 'Fee balance',
                      value: _compactMoney(data['outstanding_balance']),
                      color: kGold,
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 22),
              const _ParentHeading('School announcements'),
              if (notices.isEmpty)
                const Padding(
                  padding: EdgeInsets.all(30),
                  child: Text(
                    'No announcements right now.',
                    textAlign: TextAlign.center,
                    style: TextStyle(color: kMuted),
                  ),
                )
              else
                ...notices.take(4).map((item) {
                  final notice = item as Map<String, dynamic>;
                  return _ParentTile(
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

  static String _compactMoney(dynamic amount) {
    final value =
        (amount as num?)?.toDouble() ?? double.tryParse('$amount') ?? 0;
    if (value >= 1000000) return '₦${(value / 1000000).toStringAsFixed(1)}M';
    if (value >= 1000) return '₦${(value / 1000).toStringAsFixed(0)}K';
    return '₦${value.toStringAsFixed(0)}';
  }
}

class _ParentNotices extends StatelessWidget {
  const _ParentNotices({super.key, required this.childId});
  final int? childId;

  @override
  Widget build(BuildContext context) {
    return _ParentFuture(
      future: ApiClient.instance.get(
        '/parent/dashboard',
        childId == null ? null : {'child_id': '$childId'},
      ),
      onRetry: () {},
      builder: (data) {
        final items = data['announcements'] as List<dynamic>? ?? const [];
        return ListView(
          padding: const EdgeInsets.all(14),
          children: [
            const _ParentHeading('School communication'),
            const Padding(
              padding: EdgeInsets.only(bottom: 14),
              child: Text(
                'Direct parent messaging will appear here when enabled by the school.',
                style: TextStyle(color: kMuted),
              ),
            ),
            ...items.map((item) {
              final notice = item as Map<String, dynamic>;
              return _ParentTile(
                icon: Icons.campaign_outlined,
                title: notice['title']?.toString() ?? 'Announcement',
                subtitle: notice['body']?.toString() ?? '',
              );
            }),
          ],
        );
      },
    );
  }
}

class _ParentList extends StatefulWidget {
  const _ParentList({
    super.key,
    required this.endpoint,
    required this.listKey,
    required this.emptyText,
    required this.itemBuilder,
  });
  final String endpoint;
  final String listKey;
  final String emptyText;
  final Widget Function(Map<String, dynamic>) itemBuilder;

  @override
  State<_ParentList> createState() => _ParentListState();
}

class _ParentListState extends State<_ParentList> {
  late Future<Map<String, dynamic>> _future;
  @override
  void initState() {
    super.initState();
    _future = ApiClient.instance.get(widget.endpoint);
  }

  @override
  Widget build(BuildContext context) => _ParentFuture(
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
              ? [
                  Padding(
                    padding: const EdgeInsets.all(40),
                    child: Text(
                      widget.emptyText,
                      textAlign: TextAlign.center,
                      style: const TextStyle(color: kMuted),
                    ),
                  ),
                ]
              : items
                    .map(
                      (item) =>
                          widget.itemBuilder(item as Map<String, dynamic>),
                    )
                    .toList(),
        ),
      );
    },
  );
}

class _ParentFuture extends StatelessWidget {
  const _ParentFuture({
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
                Text(
                  snapshot.error.toString(),
                  textAlign: TextAlign.center,
                  style: const TextStyle(color: kMuted),
                ),
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

class _ParentMore extends StatelessWidget {
  const _ParentMore();
  @override
  Widget build(BuildContext context) {
    final user = ApiClient.instance.user ?? const <String, dynamic>{};
    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        const CircleAvatar(
          radius: 34,
          backgroundColor: kNavy,
          child: Icon(Icons.family_restroom_rounded, color: kGold, size: 30),
        ),
        const SizedBox(height: 12),
        Text(
          user['name']?.toString() ?? 'Parent / Guardian',
          textAlign: TextAlign.center,
          style: const TextStyle(
            color: kInk,
            fontSize: 19,
            fontWeight: FontWeight.w800,
          ),
        ),
        const Text(
          'Parent access · Linked children only',
          textAlign: TextAlign.center,
          style: TextStyle(color: kMuted),
        ),
        const SizedBox(height: 28),
        const _ParentTile(
          icon: Icons.fact_check_outlined,
          title: 'Attendance',
          subtitle: 'Attendance records for your linked children',
        ),
        const _ParentTile(
          icon: Icons.notifications_outlined,
          title: 'Announcements',
          subtitle: 'School news and important notices',
        ),
        _ParentTile(
          icon: Icons.open_in_browser_rounded,
          title: 'Payments and web portal',
          subtitle: 'Pay fees or access other parent services online',
          onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const WebModulesScreen(title: 'Parent Web Portal'))),
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

class _ParentHero extends StatelessWidget {
  const _ParentHero({required this.name});
  final String name;
  @override
  Widget build(BuildContext context) => Container(
    padding: const EdgeInsets.all(20),
    decoration: BoxDecoration(
      gradient: const LinearGradient(colors: [kNavy, Color(0xFF0A2F69)]),
      borderRadius: BorderRadius.circular(20),
    ),
    child: Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          'Welcome, $name',
          style: const TextStyle(
            color: Colors.white,
            fontSize: 22,
            fontWeight: FontWeight.w800,
          ),
        ),
        const SizedBox(height: 6),
        const Text(
          'Your children’s school life, clearly connected.',
          style: TextStyle(color: Color(0xFFCFDCF0)),
        ),
        const SizedBox(height: 14),
        const Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(Icons.lock_outline, color: kGold, size: 16),
            SizedBox(width: 6),
            Text(
              'Parent access · Linked children only',
              style: TextStyle(
                color: Colors.white,
                fontSize: 12,
                fontWeight: FontWeight.w700,
              ),
            ),
          ],
        ),
      ],
    ),
  );
}

class _ParentMetric extends StatelessWidget {
  const _ParentMetric({
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
    padding: const EdgeInsets.symmetric(vertical: 14, horizontal: 8),
    decoration: BoxDecoration(
      color: Colors.white,
      borderRadius: BorderRadius.circular(15),
      border: Border.all(color: const Color(0xFFD8E0E8)),
    ),
    child: Column(
      children: [
        Icon(icon, color: color),
        const SizedBox(height: 8),
        Text(
          value,
          style: const TextStyle(
            color: kInk,
            fontSize: 18,
            fontWeight: FontWeight.w800,
          ),
        ),
        Text(
          label,
          maxLines: 1,
          overflow: TextOverflow.ellipsis,
          style: const TextStyle(color: kMuted, fontSize: 10.5),
        ),
      ],
    ),
  );
}

class _ParentHeading extends StatelessWidget {
  const _ParentHeading(this.text);
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

class _ParentTile extends StatelessWidget {
  const _ParentTile({
    required this.icon,
    required this.title,
    required this.subtitle,
    this.onTap,
  });
  final IconData icon;
  final String title;
  final String subtitle;
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
      trailing: onTap == null ? null : const Icon(Icons.chevron_right),
      onTap: onTap,
    ),
  );
}

void _showInvoice(BuildContext context, Map<String, dynamic> item) {
  showModalBottomSheet(
    context: context,
    builder: (_) => Padding(
      padding: const EdgeInsets.all(22),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            item['number']?.toString() ?? 'Invoice',
            style: const TextStyle(fontSize: 20, fontWeight: FontWeight.w800),
          ),
          const SizedBox(height: 14),
          _detail('Term', '${item['term'] ?? ''} ${item['session'] ?? ''}'),
          _detail('Total', _parentMoney(item['total_amount'])),
          _detail('Paid', _parentMoney(item['amount_paid'])),
          _detail('Balance', _parentMoney(item['balance'])),
          _detail('Status', '${item['status'] ?? ''}'),
          _detail('Due date', '${item['due_date'] ?? 'Not set'}'),
        ],
      ),
    ),
  );
}

void _showParentResult(BuildContext context, Map<String, dynamic> result) {
  final raw = result['subject_breakdown'];
  final rows = <Map<String, dynamic>>[];
  if (raw is List) {
    rows.addAll(
      raw.whereType<Map>().map((item) => item.cast<String, dynamic>()),
    );
  } else if (raw is Map) {
    for (final entry in raw.entries) {
      rows.add(
        entry.value is Map
            ? {
                'subject': entry.key.toString(),
                ...(entry.value as Map).cast<String, dynamic>(),
              }
            : {'subject': entry.key.toString(), 'total': entry.value},
      );
    }
  }
  showModalBottomSheet(
    context: context,
    isScrollControlled: true,
    builder: (_) => DraggableScrollableSheet(
      expand: false,
      initialChildSize: .78,
      builder: (_, controller) => ListView(
        controller: controller,
        padding: const EdgeInsets.all(20),
        children: [
          Text(
            '${result['term'] ?? 'Term'} · ${result['session'] ?? ''}',
            style: const TextStyle(fontSize: 20, fontWeight: FontWeight.w800),
          ),
          Text(
            'Average ${result['average'] ?? 0}% · Position ${result['position'] ?? '—'} of ${result['class_size'] ?? '—'}',
            style: const TextStyle(color: kMuted),
          ),
          const SizedBox(height: 18),
          const Text(
            'Subject breakdown',
            style: TextStyle(fontSize: 16, fontWeight: FontWeight.w800),
          ),
          if (rows.isEmpty)
            const Padding(
              padding: EdgeInsets.only(top: 12),
              child: Text(
                'No subject-level scores were stored for this report.',
                style: TextStyle(color: kMuted),
              ),
            ),
          ...rows.map(
            (row) => Card(
              child: ListTile(
                title: Text(
                  '${row['subject'] ?? row['subject_name'] ?? row['name'] ?? 'Subject'}',
                  style: const TextStyle(fontWeight: FontWeight.w700),
                ),
                subtitle: Text(_parentScoreParts(row)),
                trailing: Text(
                  '${row['grade'] ?? row['total'] ?? row['score'] ?? ''}',
                  style: const TextStyle(
                    color: kNavy,
                    fontWeight: FontWeight.w800,
                  ),
                ),
              ),
            ),
          ),
          if (result['form_tutor_remark'] != null)
            Text(
              'Form tutor: ${result['form_tutor_remark']}',
              style: const TextStyle(color: kMuted),
            ),
          if (result['principal_remark'] != null)
            Text(
              'Principal: ${result['principal_remark']}',
              style: const TextStyle(color: kMuted),
            ),
        ],
      ),
    ),
  );
}

Widget _detail(String label, String value) => Padding(
  padding: const EdgeInsets.only(bottom: 9),
  child: Row(
    children: [
      Expanded(
        child: Text(label, style: const TextStyle(color: kMuted)),
      ),
      Text(value, style: const TextStyle(fontWeight: FontWeight.w700)),
    ],
  ),
);

String _parentMoney(dynamic amount) {
  final value = (amount as num?)?.toDouble() ?? double.tryParse('$amount') ?? 0;
  return '₦${value.toStringAsFixed(2)}';
}

String _parentScoreParts(Map<String, dynamic> row) {
  final parts = <String>[];
  for (final key in [
    'ca',
    'continuous_assessment',
    'exam',
    'total',
    'score',
    'remark',
  ]) {
    if (row[key] != null) parts.add('${key.replaceAll('_', ' ')}: ${row[key]}');
  }
  return parts.isEmpty ? 'Published result' : parts.join(' · ');
}

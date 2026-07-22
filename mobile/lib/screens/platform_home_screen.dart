import 'package:flutter/material.dart';

import '../api_client.dart';
import '../main.dart';
import 'login_screen.dart';

class PlatformHomeScreen extends StatefulWidget {
  const PlatformHomeScreen({super.key});
  @override
  State<PlatformHomeScreen> createState() => _PlatformHomeScreenState();
}

class _PlatformHomeScreenState extends State<PlatformHomeScreen> {
  int _tab = 0;
  static const _titles = [
    'Platform Overview',
    'Schools',
    'Billing',
    'Plans',
    'Governance'
  ];

  @override
  Widget build(BuildContext context) => Scaffold(
        appBar: AppBar(
          title: Text(_titles[_tab]),
          actions: const [
            Padding(
              padding: EdgeInsets.only(right: 10),
              child: Chip(
                avatar: Icon(Icons.security_rounded, size: 16, color: kGold),
                label: Text('PLATFORM SUPER ADMIN',
                    style: TextStyle(
                        color: Colors.white,
                        fontSize: 9.5,
                        fontWeight: FontWeight.w800,
                        letterSpacing: .35)),
                backgroundColor: Color(0xFF0A2A5E),
                side: BorderSide(color: Color(0x557CA7DA)),
                visualDensity: VisualDensity.compact,
              ),
            ),
          ],
        ),
        body: IndexedStack(index: _tab, children: const [
          _PlatformDashboard(),
          _SchoolsScreen(),
          _BillingScreen(),
          _PlansScreen(),
          _GovernanceScreen(),
        ]),
        bottomNavigationBar: NavigationBar(
          selectedIndex: _tab,
          onDestinationSelected: (value) => setState(() => _tab = value),
          destinations: const [
            NavigationDestination(
                icon: Icon(Icons.monitor_heart_outlined),
                selectedIcon: Icon(Icons.monitor_heart_rounded),
                label: 'Overview'),
            NavigationDestination(
                icon: Icon(Icons.apartment_outlined),
                selectedIcon: Icon(Icons.apartment_rounded),
                label: 'Schools'),
            NavigationDestination(
                icon: Icon(Icons.payments_outlined),
                selectedIcon: Icon(Icons.payments_rounded),
                label: 'Billing'),
            NavigationDestination(
                icon: Icon(Icons.layers_outlined),
                selectedIcon: Icon(Icons.layers_rounded),
                label: 'Plans'),
            NavigationDestination(
                icon: Icon(Icons.admin_panel_settings_outlined),
                selectedIcon: Icon(Icons.admin_panel_settings_rounded),
                label: 'More'),
          ],
        ),
      );
}

class _PlatformDashboard extends StatefulWidget {
  const _PlatformDashboard();
  @override
  State<_PlatformDashboard> createState() => _PlatformDashboardState();
}

class _PlatformDashboardState extends State<_PlatformDashboard> {
  late Future<Map<String, dynamic>> _future = _load();
  Future<Map<String, dynamic>> _load() =>
      ApiClient.instance.get('/platform/dashboard');

  @override
  Widget build(BuildContext context) => _PlatformFuture(
        future: _future,
        retry: () => setState(() => _future = _load()),
        builder: (data) {
          final operator = _asMap(data['operator']);
          final metrics = _asMap(data['metrics']);
          final attention = _asMap(data['attention']);
          final schools = data['recent_schools'] as List<dynamic>? ?? const [];
          return RefreshIndicator(
            onRefresh: () async => setState(() => _future = _load()),
            child: ListView(
                padding: const EdgeInsets.fromLTRB(14, 14, 14, 30),
                children: [
                  _PlatformHero(
                      name: operator['name']?.toString() ?? 'Super Admin'),
                  const SizedBox(height: 18),
                  const _Title('Platform performance'),
                  GridView.count(
                    shrinkWrap: true,
                    physics: const NeverScrollableScrollPhysics(),
                    crossAxisCount: 2,
                    childAspectRatio: 1.5,
                    mainAxisSpacing: 10,
                    crossAxisSpacing: 10,
                    children: [
                      _Kpi(
                          icon: Icons.apartment_outlined,
                          label: 'Schools',
                          value: '${metrics['schools'] ?? 0}',
                          color: kNavy),
                      _Kpi(
                          icon: Icons.verified_outlined,
                          label: 'Active schools',
                          value: '${metrics['active_schools'] ?? 0}',
                          color: kGood),
                      _Kpi(
                          icon: Icons.school_outlined,
                          label: 'Students served',
                          value: _compact(metrics['students']),
                          color: kGold),
                      _Kpi(
                          icon: Icons.groups_outlined,
                          label: 'Platform users',
                          value: _compact(metrics['platform_users']),
                          color: const Color(0xFF1769AA)),
                    ],
                  ),
                  const SizedBox(height: 20),
                  const _Title('Commercial performance'),
                  _RevenueCard(
                      month: _money(metrics['monthly_revenue']),
                      total: _money(metrics['total_revenue'])),
                  const SizedBox(height: 20),
                  const _Title('Requires attention'),
                  Row(children: [
                    Expanded(
                        child: _Attention(
                            label: 'Pending',
                            value: '${attention['pending'] ?? 0}',
                            color: kGold)),
                    const SizedBox(width: 8),
                    Expanded(
                        child: _Attention(
                            label: 'Expiring',
                            value: '${attention['expiring_soon'] ?? 0}',
                            color: const Color(0xFFC86B16))),
                    const SizedBox(width: 8),
                    Expanded(
                        child: _Attention(
                            label: 'Suspended',
                            value: '${attention['suspended'] ?? 0}',
                            color: kRisk)),
                  ]),
                  const SizedBox(height: 20),
                  const _Title('Recently onboarded schools'),
                  ...schools.map((raw) => _SchoolTile(item: _asMap(raw))),
                ]),
          );
        },
      );
}

class _SchoolsScreen extends StatefulWidget {
  const _SchoolsScreen();
  @override
  State<_SchoolsScreen> createState() => _SchoolsScreenState();
}

class _SchoolsScreenState extends State<_SchoolsScreen> {
  String _filter = 'all';
  late Future<Map<String, dynamic>> _future = _load();
  Future<Map<String, dynamic>> _load() => ApiClient.instance
      .get('/platform/tenants', _filter == 'all' ? null : {'status': _filter});

  void _setFilter(String value) => setState(() {
        _filter = value;
        _future = _load();
      });

  @override
  Widget build(BuildContext context) => Column(children: [
        SizedBox(
          height: 62,
          child: ListView(
            scrollDirection: Axis.horizontal,
            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
            children: [
              'all',
              'active',
              'pending',
              'suspended',
              'subscription_expired'
            ]
                .map((value) => Padding(
                      padding: const EdgeInsets.only(right: 7),
                      child: ChoiceChip(
                          label: Text(value == 'subscription_expired'
                              ? 'Expired'
                              : _capital(value)),
                          selected: _filter == value,
                          onSelected: (_) => _setFilter(value)),
                    ))
                .toList(),
          ),
        ),
        Expanded(
            child: _PlatformFuture(
          future: _future,
          retry: () => setState(() => _future = _load()),
          builder: (data) {
            final schools = data['tenants'] as List<dynamic>? ?? const [];
            if (schools.isEmpty) {
              return const _PlatformEmpty(
                  icon: Icons.apartment_outlined,
                  text: 'No schools match this filter.');
            }
            return RefreshIndicator(
              onRefresh: () async => setState(() => _future = _load()),
              child: ListView.builder(
                padding: const EdgeInsets.fromLTRB(14, 4, 14, 24),
                itemCount: schools.length,
                itemBuilder: (_, index) =>
                    _SchoolTile(item: _asMap(schools[index])),
              ),
            );
          },
        )),
      ]);
}

class _BillingScreen extends StatefulWidget {
  const _BillingScreen();
  @override
  State<_BillingScreen> createState() => _BillingScreenState();
}

class _BillingScreenState extends State<_BillingScreen> {
  late Future<Map<String, dynamic>> _future =
      ApiClient.instance.get('/platform/billing');
  @override
  Widget build(BuildContext context) => _PlatformFuture(
        future: _future,
        retry: () => setState(
            () => _future = ApiClient.instance.get('/platform/billing')),
        builder: (data) {
          final summary = _asMap(data['summary']);
          final payments = data['payments'] as List<dynamic>? ?? const [];
          return RefreshIndicator(
            onRefresh: () async => setState(
                () => _future = ApiClient.instance.get('/platform/billing')),
            child: ListView(padding: const EdgeInsets.all(14), children: [
              _RevenueCard(
                  month: _money(summary['this_month']),
                  total: _money(summary['confirmed'])),
              const SizedBox(height: 12),
              _Attention(
                  label: 'Pending confirmation',
                  value: _money(summary['pending']),
                  color: kGold),
              const SizedBox(height: 20),
              const _Title('Recent platform payments'),
              ...payments.map((raw) {
                final item = _asMap(raw);
                return _PlatformTile(
                  icon: Icons.receipt_long_outlined,
                  title: item['school']?.toString() ?? 'School payment',
                  subtitle:
                      '${item['reference'] ?? ''} · ${item['method'] ?? 'Unspecified method'}',
                  trailing: Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      crossAxisAlignment: CrossAxisAlignment.end,
                      children: [
                        Text(_money(item['amount']),
                            style: const TextStyle(
                                color: kInk, fontWeight: FontWeight.w800)),
                        _Badge(text: '${item['status'] ?? ''}'),
                      ]),
                );
              }),
            ]),
          );
        },
      );
}

class _PlansScreen extends StatefulWidget {
  const _PlansScreen();
  @override
  State<_PlansScreen> createState() => _PlansScreenState();
}

class _PlansScreenState extends State<_PlansScreen> {
  late Future<Map<String, dynamic>> _future =
      ApiClient.instance.get('/platform/plans');
  @override
  Widget build(BuildContext context) => _PlatformFuture(
        future: _future,
        retry: () =>
            setState(() => _future = ApiClient.instance.get('/platform/plans')),
        builder: (data) {
          final plans = data['plans'] as List<dynamic>? ?? const [];
          return RefreshIndicator(
            onRefresh: () async => setState(
                () => _future = ApiClient.instance.get('/platform/plans')),
            child: ListView(padding: const EdgeInsets.all(14), children: [
              const _Title('Subscription portfolio'),
              ...plans.map((raw) {
                final plan = _asMap(raw);
                final features = plan['features'] as List<dynamic>? ?? const [];
                return Card(
                  margin: const EdgeInsets.only(bottom: 12),
                  child: Padding(
                    padding: const EdgeInsets.all(17),
                    child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(children: [
                            Expanded(
                                child: Text(plan['name']?.toString() ?? 'Plan',
                                    style: const TextStyle(
                                        color: kInk,
                                        fontSize: 18,
                                        fontWeight: FontWeight.w800))),
                            _Badge(
                                text: plan['active'] == true
                                    ? 'Active'
                                    : 'Inactive')
                          ]),
                          const SizedBox(height: 7),
                          Text(
                              '${_money(plan['monthly_price'])}/month · ${_money(plan['annual_price'])}/year',
                              style: const TextStyle(
                                  color: kNavy, fontWeight: FontWeight.w700)),
                          const SizedBox(height: 10),
                          Text(
                              '${plan['subscribers'] ?? 0} subscribers · Up to ${plan['max_students'] ?? 0} students · ${plan['max_staff'] ?? 0} staff',
                              style:
                                  const TextStyle(color: kMuted, fontSize: 12)),
                          if (features.isNotEmpty) ...[
                            const Divider(height: 24),
                            Wrap(
                                spacing: 6,
                                runSpacing: 6,
                                children: features
                                    .take(5)
                                    .map((feature) => Chip(
                                        label: Text('$feature',
                                            style:
                                                const TextStyle(fontSize: 10)),
                                        visualDensity: VisualDensity.compact))
                                    .toList()),
                          ],
                        ]),
                  ),
                );
              }),
            ]),
          );
        },
      );
}

class _GovernanceScreen extends StatelessWidget {
  const _GovernanceScreen();
  @override
  Widget build(BuildContext context) {
    final user = ApiClient.instance.user ?? const <String, dynamic>{};
    return ListView(padding: const EdgeInsets.all(16), children: [
      const CircleAvatar(
          radius: 36,
          backgroundColor: kNavy,
          child: Icon(Icons.security_rounded, color: kGold, size: 34)),
      const SizedBox(height: 12),
      Text(user['name']?.toString() ?? 'Platform Super Admin',
          textAlign: TextAlign.center,
          style: const TextStyle(
              color: kInk, fontSize: 19, fontWeight: FontWeight.w800)),
      const Text('EduCore Platform · Global privileged access',
          textAlign: TextAlign.center, style: TextStyle(color: kMuted)),
      const SizedBox(height: 25),
      const _PlatformTile(
          icon: Icons.domain_verification_outlined,
          title: 'Tenant governance',
          subtitle:
              'Cross-school platform oversight with strict tenant separation.'),
      const _PlatformTile(
          icon: Icons.policy_outlined,
          title: 'Privileged access',
          subtitle:
              'Restricted exclusively to verified Platform Super Admin accounts.'),
      const _PlatformTile(
          icon: Icons.history_outlined,
          title: 'Audit-ready operations',
          subtitle:
              'Administrative sessions and platform actions remain attributable.'),
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
    ]);
  }
}

class _PlatformHero extends StatelessWidget {
  const _PlatformHero({required this.name});
  final String name;
  @override
  Widget build(BuildContext context) => Container(
        padding: const EdgeInsets.all(20),
        decoration: BoxDecoration(
            gradient: const LinearGradient(colors: [kNavy, Color(0xFF0B3B79)]),
            borderRadius: BorderRadius.circular(20)),
        child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          const Text('Platform command centre',
              style: TextStyle(
                  color: Colors.white,
                  fontSize: 23,
                  fontWeight: FontWeight.w800)),
          const SizedBox(height: 5),
          Text(
              'Welcome, ${name.split(' ').first}. Monitor the EduCore network in real time.',
              style: const TextStyle(color: Color(0xFFCFDCF0))),
          const SizedBox(height: 15),
          const Row(children: [
            Icon(Icons.lock_outline_rounded, color: kGold, size: 17),
            SizedBox(width: 6),
            Text('Global privileged access · Platform scope',
                style: TextStyle(
                    color: Colors.white,
                    fontSize: 12,
                    fontWeight: FontWeight.w700))
          ]),
        ]),
      );
}

class _RevenueCard extends StatelessWidget {
  const _RevenueCard({required this.month, required this.total});
  final String month;
  final String total;
  @override
  Widget build(BuildContext context) => Container(
        padding: const EdgeInsets.all(18),
        decoration: BoxDecoration(
            color: kNavy, borderRadius: BorderRadius.circular(17)),
        child: Row(children: [
          const CircleAvatar(
              backgroundColor: Color(0x26D79A21),
              child: Icon(Icons.trending_up_rounded, color: kGold)),
          const SizedBox(width: 13),
          Expanded(
              child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                const Text('Revenue this month',
                    style: TextStyle(color: Color(0xFFCFDCF0), fontSize: 11)),
                Text(month,
                    style: const TextStyle(
                        color: Colors.white,
                        fontSize: 21,
                        fontWeight: FontWeight.w800))
              ])),
          Column(crossAxisAlignment: CrossAxisAlignment.end, children: [
            const Text('Lifetime',
                style: TextStyle(color: Color(0xFFCFDCF0), fontSize: 10)),
            Text(total,
                style:
                    const TextStyle(color: kGold, fontWeight: FontWeight.w800))
          ]),
        ]),
      );
}

class _SchoolTile extends StatelessWidget {
  const _SchoolTile({required this.item});
  final Map<String, dynamic> item;
  @override
  Widget build(BuildContext context) => _PlatformTile(
        icon: Icons.apartment_rounded,
        title: item['name']?.toString() ?? 'School',
        subtitle:
            '${item['plan'] ?? 'No plan'} · ${item['students'] ?? 0} students · ${item['users'] ?? 0} users',
        trailing: _Badge(text: '${item['status'] ?? ''}'),
      );
}

class _Kpi extends StatelessWidget {
  const _Kpi(
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
            borderRadius: BorderRadius.circular(16),
            border: Border.all(color: const Color(0xFFD8E0E8))),
        child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Icon(icon, color: color, size: 22),
          const Spacer(),
          Text(value,
              style: const TextStyle(
                  color: kInk, fontSize: 20, fontWeight: FontWeight.w800)),
          Text(label,
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: const TextStyle(color: kMuted, fontSize: 11))
        ]),
      );
}

class _Attention extends StatelessWidget {
  const _Attention(
      {required this.label, required this.value, required this.color});
  final String label;
  final String value;
  final Color color;
  @override
  Widget build(BuildContext context) => Container(
        padding: const EdgeInsets.symmetric(horizontal: 11, vertical: 13),
        decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(14),
            border: Border.all(color: const Color(0xFFD8E0E8))),
        child: Column(children: [
          Text(value,
              maxLines: 1,
              style: TextStyle(
                  color: color, fontSize: 18, fontWeight: FontWeight.w800)),
          Text(label,
              maxLines: 1, style: const TextStyle(color: kMuted, fontSize: 10))
        ]),
      );
}

class _PlatformTile extends StatelessWidget {
  const _PlatformTile(
      {required this.icon,
      required this.title,
      required this.subtitle,
      this.trailing});
  final IconData icon;
  final String title;
  final String subtitle;
  final Widget? trailing;
  @override
  Widget build(BuildContext context) => Card(
        margin: const EdgeInsets.only(bottom: 10),
        child: ListTile(
          leading: CircleAvatar(
              backgroundColor: const Color(0x18071E45),
              child: Icon(icon, color: kNavy)),
          title: Text(title,
              style: const TextStyle(color: kInk, fontWeight: FontWeight.w700)),
          subtitle: Text(subtitle,
              maxLines: 2,
              overflow: TextOverflow.ellipsis,
              style: const TextStyle(color: kMuted, fontSize: 12)),
          trailing: trailing,
        ),
      );
}

class _Badge extends StatelessWidget {
  const _Badge({required this.text});
  final String text;
  @override
  Widget build(BuildContext context) {
    final negative = text.contains('suspend') ||
        text.contains('expired') ||
        text.contains('failed');
    final color = negative ? kRisk : kGood;
    return Container(
        padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 4),
        decoration: BoxDecoration(
            color: color.withValues(alpha: .08),
            borderRadius: BorderRadius.circular(15)),
        child: Text(text.replaceAll('_', ' '),
            style: TextStyle(
                color: color, fontSize: 9.5, fontWeight: FontWeight.w700)));
  }
}

class _Title extends StatelessWidget {
  const _Title(this.text);
  final String text;
  @override
  Widget build(BuildContext context) => Padding(
      padding: const EdgeInsets.only(bottom: 9),
      child: Text(text,
          style: const TextStyle(
              color: kInk, fontSize: 17, fontWeight: FontWeight.w800)));
}

class _PlatformEmpty extends StatelessWidget {
  const _PlatformEmpty({required this.icon, required this.text});
  final IconData icon;
  final String text;
  @override
  Widget build(BuildContext context) => Center(
          child: Column(mainAxisSize: MainAxisSize.min, children: [
        Icon(icon, color: kMuted, size: 48),
        const SizedBox(height: 10),
        Text(text, style: const TextStyle(color: kMuted))
      ]));
}

class _PlatformFuture extends StatelessWidget {
  const _PlatformFuture(
      {required this.future, required this.retry, required this.builder});
  final Future<Map<String, dynamic>> future;
  final VoidCallback retry;
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
                    child: Column(mainAxisSize: MainAxisSize.min, children: [
                      const Icon(Icons.cloud_off_rounded,
                          color: kMuted, size: 46),
                      const SizedBox(height: 12),
                      Text(snapshot.error.toString(),
                          textAlign: TextAlign.center,
                          style: const TextStyle(color: kMuted)),
                      const SizedBox(height: 16),
                      FilledButton(onPressed: retry, child: const Text('Retry'))
                    ])));
          }
          return builder(snapshot.data ?? const {});
        },
      );
}

Map<String, dynamic> _asMap(dynamic value) => value is Map<String, dynamic>
    ? value
    : Map<String, dynamic>.from(value as Map? ?? const {});
String _capital(String value) =>
    value.isEmpty ? value : '${value[0].toUpperCase()}${value.substring(1)}';
String _compact(dynamic value) {
  final number = (value as num?)?.toDouble() ?? double.tryParse('$value') ?? 0;
  if (number >= 1000000) return '${(number / 1000000).toStringAsFixed(1)}M';
  if (number >= 1000) return '${(number / 1000).toStringAsFixed(1)}K';
  return number.toStringAsFixed(0);
}

String _money(dynamic value) {
  final number = (value as num?)?.toDouble() ?? double.tryParse('$value') ?? 0;
  return '₦${number.toStringAsFixed(0).replaceAllMapped(RegExp(r'\B(?=(\d{3})+(?!\d))'), (_) => ',')}';
}

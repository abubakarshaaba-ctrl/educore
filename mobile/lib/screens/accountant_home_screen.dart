import 'package:flutter/material.dart';

import '../api_client.dart';
import '../main.dart';
import 'staff_attendance_screen.dart';

class AccountantHomeScreen extends StatefulWidget {
  const AccountantHomeScreen({super.key});
  @override
  State<AccountantHomeScreen> createState() => _AccountantHomeScreenState();
}

class _AccountantHomeScreenState extends State<AccountantHomeScreen> {
  int _tab = 0;
  @override
  Widget build(BuildContext context) {
    const titles = ['Finance', 'Invoices', 'Payroll', 'My Attendance'];
    return Scaffold(
      appBar: AppBar(title: Text(titles[_tab]), actions: const [
        Padding(
            padding: EdgeInsets.only(right: 10),
            child: Chip(
                label: Text('ACCOUNTANT',
                    style: TextStyle(
                        color: Colors.white,
                        fontSize: 10,
                        fontWeight: FontWeight.w800)),
                backgroundColor: Color(0xFF0A2A5E)))
      ]),
      body: IndexedStack(index: _tab, children: const [
        _FinanceOverview(),
        _InvoiceList(),
        _PayrollList(),
        StaffAttendanceScreen()
      ]),
      bottomNavigationBar: NavigationBar(
          selectedIndex: _tab,
          onDestinationSelected: (value) => setState(() => _tab = value),
          destinations: const [
            NavigationDestination(
                icon: Icon(Icons.account_balance_wallet_outlined),
                label: 'Finance'),
            NavigationDestination(
                icon: Icon(Icons.receipt_long_outlined), label: 'Invoices'),
            NavigationDestination(
                icon: Icon(Icons.payments_outlined), label: 'Payroll'),
            NavigationDestination(
                icon: Icon(Icons.badge_outlined), label: 'Attendance'),
          ]),
    );
  }
}

class _FinanceOverview extends StatefulWidget {
  const _FinanceOverview();
  @override
  State<_FinanceOverview> createState() => _FinanceOverviewState();
}

class _FinanceOverviewState extends State<_FinanceOverview> {
  late Future<Map<String, dynamic>> future =
      ApiClient.instance.get('/accountant/dashboard');
  @override
  Widget build(BuildContext context) => FutureBuilder<Map<String, dynamic>>(
      future: future,
      builder: (_, snap) {
        if (snap.connectionState != ConnectionState.done)
          return const Center(child: CircularProgressIndicator());
        if (snap.hasError) return _error(snap.error);
        final s =
            (snap.data?['summary'] as Map?)?.cast<String, dynamic>() ?? {};
        return RefreshIndicator(
            onRefresh: () async => setState(
                () => future = ApiClient.instance.get('/accountant/dashboard')),
            child: ListView(padding: const EdgeInsets.all(16), children: [
              const Text('Financial position',
                  style: TextStyle(
                      fontSize: 20, fontWeight: FontWeight.w800, color: kInk)),
              const SizedBox(height: 14),
              _moneyCard('Total billed', s['billed'], kNavy),
              _moneyCard('Collected', s['collected'], kGood),
              _moneyCard('Outstanding', s['outstanding'], kGold),
              _moneyCard('Expenses', s['expenses'], kRisk),
            ]));
      });
}

class _InvoiceList extends StatefulWidget {
  const _InvoiceList();
  @override
  State<_InvoiceList> createState() => _InvoiceListState();
}

class _InvoiceListState extends State<_InvoiceList> {
  late Future<Map<String, dynamic>> future =
      ApiClient.instance.get('/accountant/dashboard');
  @override
  Widget build(BuildContext context) => FutureBuilder<Map<String, dynamic>>(
      future: future,
      builder: (_, snap) {
        if (snap.connectionState != ConnectionState.done)
          return const Center(child: CircularProgressIndicator());
        if (snap.hasError) return _error(snap.error);
        final rows = snap.data?['invoices'] as List<dynamic>? ?? const [];
        return ListView.builder(
            padding: const EdgeInsets.all(14),
            itemCount: rows.length,
            itemBuilder: (_, i) {
              final row = (rows[i] as Map).cast<String, dynamic>();
              return Card(
                  child: ListTile(
                      title: Text(row['student']?.toString() ?? 'Student'),
                      subtitle: Text('${row['number']} · ${row['status']}'),
                      trailing: Text(_money(row['balance']),
                          style: const TextStyle(
                              fontWeight: FontWeight.w800, color: kGold))));
            });
      });
}

class _PayrollList extends StatefulWidget {
  const _PayrollList();
  @override
  State<_PayrollList> createState() => _PayrollListState();
}

class _PayrollListState extends State<_PayrollList> {
  late Future<Map<String, dynamic>> future =
      ApiClient.instance.get('/accountant/payroll');
  @override
  Widget build(BuildContext context) => FutureBuilder<Map<String, dynamic>>(
      future: future,
      builder: (_, snap) {
        if (snap.connectionState != ConnectionState.done)
          return const Center(child: CircularProgressIndicator());
        if (snap.hasError) return _error(snap.error);
        final rows = snap.data?['periods'] as List<dynamic>? ?? const [];
        if (rows.isEmpty)
          return const Center(
              child: Text('No payroll periods yet.',
                  style: TextStyle(color: kMuted)));
        return ListView.builder(
            padding: const EdgeInsets.all(14),
            itemCount: rows.length,
            itemBuilder: (_, i) {
              final row = (rows[i] as Map).cast<String, dynamic>();
              return Card(
                  child: ListTile(
                      leading: const CircleAvatar(
                          backgroundColor: kNavy,
                          child: Icon(Icons.payments, color: kGold)),
                      title: Text(row['title'].toString()),
                      subtitle: Text(
                          '${row['start']} – ${row['end']} · ${row['status']}'),
                      trailing: Text(_money(row['net']),
                          style:
                              const TextStyle(fontWeight: FontWeight.w800))));
            });
      });
}

Widget _moneyCard(String label, dynamic value, Color color) => Card(
    child: ListTile(
        leading: CircleAvatar(
            backgroundColor: color.withOpacity(.12),
            child: Icon(Icons.account_balance_wallet, color: color)),
        title: Text(label),
        trailing: Text(_money(value),
            style: TextStyle(
                fontSize: 17, fontWeight: FontWeight.w800, color: color))));
String _money(dynamic value) =>
    '₦${((value as num?)?.toDouble() ?? double.tryParse('$value') ?? 0).toStringAsFixed(2)}';
Widget _error(Object? error) => Center(
    child: Padding(
        padding: const EdgeInsets.all(24),
        child: Text(error.toString(),
            textAlign: TextAlign.center,
            style: const TextStyle(color: kRisk))));

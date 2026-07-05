import 'dart:io';

import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import 'package:open_filex/open_filex.dart';
import 'package:path_provider/path_provider.dart';

import '../api_client.dart';
import '../main.dart';

final _money = NumberFormat.currency(symbol: '₦', decimalDigits: 2);

class PayslipListScreen extends StatefulWidget {
  const PayslipListScreen({super.key});

  @override
  State<PayslipListScreen> createState() => _PayslipListScreenState();
}

class _PayslipListScreenState extends State<PayslipListScreen> {
  late Future<List<dynamic>> _future;

  @override
  void initState() {
    super.initState();
    _future = _load();
  }

  Future<List<dynamic>> _load() async {
    final data = await ApiClient.instance.get('/payslips');
    return data['payslips'] as List<dynamic>;
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('My Payslips')),
      body: RefreshIndicator(
        onRefresh: () async => setState(() => _future = _load()),
        child: FutureBuilder<List<dynamic>>(
          future: _future,
          builder: (context, snap) {
            if (snap.connectionState != ConnectionState.done) {
              return const Center(child: CircularProgressIndicator());
            }
            if (snap.hasError) {
              return Center(
                  child: Text(snap.error.toString(),
                      style: const TextStyle(color: kMuted)));
            }
            final items = snap.data ?? [];
            if (items.isEmpty) {
              return ListView(children: const [
                SizedBox(height: 120),
                Icon(Icons.receipt_long_rounded, size: 50, color: kMuted),
                SizedBox(height: 12),
                Center(
                    child: Text('No payslips available yet.',
                        style: TextStyle(color: kMuted))),
              ]);
            }
            return ListView.separated(
              padding: const EdgeInsets.all(14),
              itemCount: items.length,
              separatorBuilder: (_, __) => const SizedBox(height: 10),
              itemBuilder: (context, i) {
                final p = items[i] as Map<String, dynamic>;
                final paid = (p['status'] as String?) == 'paid';
                return Card(
                  elevation: 0,
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(12),
                    side: const BorderSide(color: Color(0xFFD8E0E8)),
                  ),
                  child: ListTile(
                    contentPadding: const EdgeInsets.symmetric(
                        horizontal: 16, vertical: 8),
                    title: Text(p['period_title'] as String? ?? '—',
                        style: const TextStyle(
                            fontWeight: FontWeight.w700, color: kInk)),
                    subtitle: Text(
                      'Net: ${_money.format(p['net_pay'] ?? 0)}',
                      style: const TextStyle(color: kMuted, fontSize: 12.5),
                    ),
                    trailing: Container(
                      padding: const EdgeInsets.symmetric(
                          horizontal: 9, vertical: 4),
                      decoration: BoxDecoration(
                        color: paid
                            ? const Color(0xFFE7F6EF)
                            : const Color(0xFFFFF6E5),
                        borderRadius: BorderRadius.circular(20),
                      ),
                      child: Text(
                        (p['status'] as String? ?? 'pending').toUpperCase(),
                        style: TextStyle(
                            color: paid ? kGood : const Color(0xFF9A6700),
                            fontSize: 10,
                            fontWeight: FontWeight.w700),
                      ),
                    ),
                    onTap: () => Navigator.of(context).push(MaterialPageRoute(
                      builder: (_) => PayslipDetailScreen(
                        itemId: p['id'] as int,
                        title: p['period_title'] as String? ?? 'Payslip',
                      ),
                    )),
                  ),
                );
              },
            );
          },
        ),
      ),
    );
  }
}

// ── Payslip detail ──────────────────────────────────────────────────────
class PayslipDetailScreen extends StatefulWidget {
  const PayslipDetailScreen({super.key, required this.itemId, required this.title});
  final int itemId;
  final String title;

  @override
  State<PayslipDetailScreen> createState() => _PayslipDetailScreenState();
}

class _PayslipDetailScreenState extends State<PayslipDetailScreen> {
  late Future<Map<String, dynamic>> _future;
  bool _downloading = false;

  @override
  void initState() {
    super.initState();
    _future = ApiClient.instance.get('/payslips/${widget.itemId}');
  }

  Future<void> _downloadPdf() async {
    setState(() => _downloading = true);
    try {
      final bytes =
          await ApiClient.instance.download('/payslips/${widget.itemId}/pdf');
      final dir = await getTemporaryDirectory();
      final file = File('${dir.path}/payslip-${widget.itemId}.pdf');
      await file.writeAsBytes(bytes, flush: true);
      await OpenFilex.open(file.path);
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text('Download failed: $e'), backgroundColor: kRisk));
      }
    } finally {
      if (mounted) setState(() => _downloading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text(widget.title, overflow: TextOverflow.ellipsis)),
      body: FutureBuilder<Map<String, dynamic>>(
        future: _future,
        builder: (context, snap) {
          if (snap.connectionState != ConnectionState.done) {
            return const Center(child: CircularProgressIndicator());
          }
          if (snap.hasError) {
            return Center(
                child: Text(snap.error.toString(),
                    style: const TextStyle(color: kMuted)));
          }
          final d = snap.data!;
          final earn = (d['earnings'] as Map).cast<String, dynamic>();
          final ded = (d['deductions'] as Map).cast<String, dynamic>();
          return ListView(
            padding: const EdgeInsets.all(16),
            children: [
              _netCard(d),
              const SizedBox(height: 16),
              _section('Earnings', [
                _row('Basic salary', earn['basic_salary']),
                _row('Housing allowance', earn['housing_allowance']),
                _row('Transport allowance', earn['transport_allowance']),
                _row('Other allowances', earn['other_allowances']),
                _row('Gross pay', earn['gross_pay'], bold: true),
              ]),
              const SizedBox(height: 12),
              _section('Deductions', [
                _row('Tax', ded['tax_deduction']),
                _row('Pension', ded['pension_deduction']),
                _row('Other deductions', ded['other_deductions']),
                _row('Total deductions', ded['total_deductions'], bold: true),
              ]),
              const SizedBox(height: 20),
              FilledButton.icon(
                onPressed: _downloading ? null : _downloadPdf,
                icon: _downloading
                    ? const SizedBox(
                        width: 18,
                        height: 18,
                        child: CircularProgressIndicator(strokeWidth: 2.5))
                    : const Icon(Icons.picture_as_pdf_rounded),
                label: Text(_downloading ? 'Downloading…' : 'Download PDF'),
              ),
            ],
          );
        },
      ),
    );
  }

  Widget _netCard(Map<String, dynamic> d) => Container(
        width: double.infinity,
        padding: const EdgeInsets.all(20),
        decoration: BoxDecoration(
            color: kNavy, borderRadius: BorderRadius.circular(16)),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text('NET PAY',
                style: TextStyle(
                    color: kGold,
                    fontSize: 11,
                    fontWeight: FontWeight.w800,
                    letterSpacing: 2)),
            const SizedBox(height: 6),
            Text(_money.format(d['net_pay'] ?? 0),
                style: const TextStyle(
                    color: Colors.white,
                    fontSize: 28,
                    fontWeight: FontWeight.w800)),
            const SizedBox(height: 4),
            Text(
              'Bank: ${(d['bank'] as Map?)?['name'] ?? '—'}  ·  ${(d['bank'] as Map?)?['account'] ?? ''}',
              style: const TextStyle(color: Colors.white70, fontSize: 12),
            ),
          ],
        ),
      );

  Widget _section(String title, List<Widget> rows) => Container(
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(12),
          border: Border.all(color: const Color(0xFFD8E0E8)),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Container(
              width: double.infinity,
              padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
              decoration: const BoxDecoration(
                color: Color(0xFFEDF3F8),
                borderRadius: BorderRadius.vertical(top: Radius.circular(12)),
              ),
              child: Text(title,
                  style: const TextStyle(
                      fontWeight: FontWeight.w800,
                      color: kNavy,
                      fontSize: 12.5)),
            ),
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 6),
              child: Column(children: rows),
            ),
          ],
        ),
      );

  Widget _row(String label, dynamic value, {bool bold = false}) => Padding(
        padding: const EdgeInsets.symmetric(vertical: 6),
        child: Row(
          children: [
            Expanded(
                child: Text(label,
                    style: TextStyle(
                        color: bold ? kInk : kMuted,
                        fontWeight: bold ? FontWeight.w700 : FontWeight.w400,
                        fontSize: 13.5))),
            Text(_money.format(value ?? 0),
                style: TextStyle(
                    color: kInk,
                    fontWeight: bold ? FontWeight.w800 : FontWeight.w600,
                    fontSize: 13.5)),
          ],
        ),
      );
}

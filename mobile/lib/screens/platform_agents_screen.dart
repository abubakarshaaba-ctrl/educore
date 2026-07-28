import 'package:flutter/material.dart';

import '../api_client.dart';
import '../main.dart';

class PlatformAgentsScreen extends StatefulWidget {
  const PlatformAgentsScreen({super.key});

  @override
  State<PlatformAgentsScreen> createState() => _PlatformAgentsScreenState();
}

class _PlatformAgentsScreenState extends State<PlatformAgentsScreen> {
  late Future<Map<String, dynamic>> _future = _load();

  Future<Map<String, dynamic>> _load() =>
      ApiClient.instance.get('/platform/agents');

  void _reload() => setState(() => _future = _load());

  @override
  Widget build(BuildContext context) => Scaffold(
        appBar: AppBar(title: const Text('Agent management')),
        floatingActionButton: FloatingActionButton.extended(
          onPressed: _addAgent,
          icon: const Icon(Icons.person_add_alt_1_rounded),
          label: const Text('Register agent'),
        ),
        body: FutureBuilder<Map<String, dynamic>>(
          future: _future,
          builder: (context, snapshot) {
            if (snapshot.connectionState == ConnectionState.waiting) {
              return const Center(child: CircularProgressIndicator());
            }
            if (snapshot.hasError) {
              return Center(
                child: FilledButton.icon(
                  onPressed: _reload,
                  icon: const Icon(Icons.refresh),
                  label: Text(snapshot.error.toString()),
                ),
              );
            }
            final agents =
                snapshot.data?['agents'] as List<dynamic>? ?? const [];
            if (agents.isEmpty) {
              return const Center(child: Text('No agents registered yet.'));
            }
            return RefreshIndicator(
              onRefresh: () async => _reload(),
              child: ListView.builder(
                padding: const EdgeInsets.fromLTRB(14, 14, 14, 90),
                itemCount: agents.length,
                itemBuilder: (_, index) {
                  final agent = Map<String, dynamic>.from(agents[index] as Map);
                  final active = agent['active'] == true;
                  return Card(
                    margin: const EdgeInsets.only(bottom: 10),
                    child: ListTile(
                      leading: CircleAvatar(
                        backgroundColor: active
                            ? kGood.withValues(alpha: .12)
                            : kMuted.withValues(alpha: .12),
                        child: Icon(Icons.handshake_outlined,
                            color: active ? kGood : kMuted),
                      ),
                      title: Text(agent['name']?.toString() ?? 'Agent',
                          style: const TextStyle(fontWeight: FontWeight.w800)),
                      subtitle: Text(
                        '${agent['email'] ?? ''}\n${agent['referral_code'] ?? ''} · ${agent['referrals'] ?? 0} referrals · ${agent['commission_rate'] ?? 0}%',
                      ),
                      isThreeLine: true,
                      trailing: Switch(
                        value: active,
                        onChanged: (value) => _setActive(agent, value),
                      ),
                      onTap: () => _editAgent(agent),
                    ),
                  );
                },
              ),
            );
          },
        ),
      );

  Future<void> _addAgent() async {
    final result = await _agentDialog();
    if (result == null) return;
    await _submit(() => ApiClient.instance.post('/platform/agents', result));
  }

  Future<void> _editAgent(Map<String, dynamic> agent) async {
    final result = await _agentDialog(agent);
    if (result == null) return;
    result.remove('email');
    await _submit(() =>
        ApiClient.instance.patch('/platform/agents/${agent['id']}', result));
  }

  Future<void> _setActive(Map<String, dynamic> agent, bool active) => _submit(
        () => ApiClient.instance.patch(
          '/platform/agents/${agent['id']}',
          {'is_active': active},
        ),
      );

  Future<void> _submit(
      Future<Map<String, dynamic>> Function() operation) async {
    try {
      final response = await operation();
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(
        content: Text(response['message']?.toString() ?? 'Agent updated.'),
        backgroundColor: kGood,
      ));
      _reload();
    } catch (error) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(
          content: Text(error.toString()),
          backgroundColor: kRisk,
        ));
      }
    }
  }

  Future<Map<String, dynamic>?> _agentDialog(
      [Map<String, dynamic>? agent]) async {
    final name = TextEditingController(text: agent?['name']?.toString());
    final email = TextEditingController(text: agent?['email']?.toString());
    final phone = TextEditingController(text: agent?['phone']?.toString());
    final state = TextEditingController(text: agent?['state']?.toString());
    final commission = TextEditingController(
        text: agent?['commission_rate']?.toString() ?? '10');
    final result = await showDialog<Map<String, dynamic>>(
      context: context,
      builder: (dialogContext) => AlertDialog(
        title: Text(agent == null ? 'Register agent' : 'Edit agent'),
        content: SingleChildScrollView(
          child: Column(mainAxisSize: MainAxisSize.min, children: [
            TextField(
                controller: name,
                decoration: const InputDecoration(labelText: 'Full name')),
            const SizedBox(height: 9),
            TextField(
                controller: email,
                enabled: agent == null,
                keyboardType: TextInputType.emailAddress,
                decoration: const InputDecoration(labelText: 'Email address')),
            const SizedBox(height: 9),
            TextField(
                controller: phone,
                keyboardType: TextInputType.phone,
                decoration: const InputDecoration(labelText: 'Phone number')),
            const SizedBox(height: 9),
            TextField(
                controller: state,
                decoration: const InputDecoration(labelText: 'State')),
            const SizedBox(height: 9),
            TextField(
                controller: commission,
                keyboardType: TextInputType.number,
                decoration:
                    const InputDecoration(labelText: 'Commission rate (%)')),
          ]),
        ),
        actions: [
          TextButton(
              onPressed: () => Navigator.pop(dialogContext),
              child: const Text('Cancel')),
          FilledButton(
            onPressed: () => Navigator.pop(dialogContext, {
              'name': name.text.trim(),
              'email': email.text.trim(),
              'phone': phone.text.trim(),
              'state': state.text.trim(),
              'commission_rate': double.tryParse(commission.text) ?? 10,
            }),
            child: const Text('Save'),
          ),
        ],
      ),
    );
    name.dispose();
    email.dispose();
    phone.dispose();
    state.dispose();
    commission.dispose();
    return result;
  }
}

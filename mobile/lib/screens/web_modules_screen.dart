import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

import '../api_client.dart';
import '../main.dart';

class WebModulesScreen extends StatefulWidget {
  const WebModulesScreen({super.key, this.title = 'All Modules'});
  final String title;

  @override
  State<WebModulesScreen> createState() => _WebModulesScreenState();
}

class _WebModulesScreenState extends State<WebModulesScreen> {
  late Future<List<dynamic>> _future = _load();

  Future<List<dynamic>> _load() async =>
      (await ApiClient.instance.get('/portal/modules'))['modules']
          as List<dynamic>? ??
      const [];

  Future<void> _open(Map<String, dynamic> module) async {
    try {
      final response = await ApiClient.instance
          .post('/portal/session', {'path': module['path']});
      final uri = Uri.parse(response['url'].toString());
      if (!await launchUrl(uri, mode: LaunchMode.externalApplication)) {
        throw Exception('Unable to open this module.');
      }
    } catch (error) {
      if (mounted)
        ScaffoldMessenger.of(context)
            .showSnackBar(SnackBar(content: Text(error.toString())));
    }
  }

  @override
  Widget build(BuildContext context) => Scaffold(
        appBar: AppBar(title: Text(widget.title)),
        body: FutureBuilder<List<dynamic>>(
          future: _future,
          builder: (context, snapshot) {
            if (snapshot.connectionState != ConnectionState.done)
              return const Center(child: CircularProgressIndicator());
            if (snapshot.hasError)
              return Center(child: Text(snapshot.error.toString()));
            final modules = snapshot.data ?? const [];
            return RefreshIndicator(
              onRefresh: () async => setState(() => _future = _load()),
              child: ListView.builder(
                padding: const EdgeInsets.all(14),
                itemCount: modules.length,
                itemBuilder: (_, index) {
                  final module = modules[index] as Map<String, dynamic>;
                  return Card(
                      child: ListTile(
                    leading: const CircleAvatar(
                        backgroundColor: Color(0x1FD79A21),
                        child: Icon(Icons.apps_rounded, color: kNavy)),
                    title: Text(module['title'].toString(),
                        style: const TextStyle(fontWeight: FontWeight.w700)),
                    subtitle:
                        const Text('Open the complete operational module'),
                    trailing: const Icon(Icons.open_in_new_rounded),
                    onTap: () => _open(module),
                  ));
                },
              ),
            );
          },
        ),
      );
}

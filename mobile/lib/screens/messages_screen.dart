import 'package:flutter/material.dart';
import 'package:intl/intl.dart';

import '../api_client.dart';
import '../main.dart';

class MessagesScreen extends StatefulWidget {
  const MessagesScreen({super.key});

  @override
  State<MessagesScreen> createState() => _MessagesScreenState();
}

class _MessagesScreenState extends State<MessagesScreen> {
  late Future<List<dynamic>> _future;

  @override
  void initState() {
    super.initState();
    _future = _load();
  }

  Future<List<dynamic>> _load() async {
    final data = await ApiClient.instance.get('/messages');
    return data['threads'] as List<dynamic>;
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Messages')),
      body: RefreshIndicator(
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
            final threads = snap.data ?? [];
            if (threads.isEmpty) {
              return ListView(children: const [
                SizedBox(height: 120),
                Icon(Icons.forum_outlined, size: 50, color: kMuted),
                SizedBox(height: 12),
                Center(
                    child: Text('No messages yet.',
                        style: TextStyle(color: kMuted))),
                SizedBox(height: 6),
                Center(
                    child: Text('Messages from the school will appear here.',
                        style: TextStyle(color: kMuted, fontSize: 12.5))),
              ]);
            }
            return ListView.separated(
              padding: const EdgeInsets.all(14),
              itemCount: threads.length,
              separatorBuilder: (_, __) => const SizedBox(height: 10),
              itemBuilder: (context, i) {
                final t = threads[i] as Map<String, dynamic>;
                final unread = (t['unread_count'] as int? ?? 0) > 0;
                final otherName = t['other_name'] as String? ?? 'School';
                return Card(
                  elevation: 0,
                  color: unread ? const Color(0xFFEFF6FF) : Colors.white,
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(12),
                    side: const BorderSide(color: Color(0xFFD8E0E8)),
                  ),
                  child: ListTile(
                    contentPadding: const EdgeInsets.symmetric(
                        horizontal: 16, vertical: 6),
                    leading: CircleAvatar(
                      backgroundColor: kNavy,
                      child: Text(
                        otherName.isNotEmpty ? otherName[0].toUpperCase() : '?',
                        style: const TextStyle(
                            color: Colors.white, fontWeight: FontWeight.w700),
                      ),
                    ),
                    title: Text(t['subject'] as String? ?? '(no subject)',
                        style: const TextStyle(
                            fontWeight: FontWeight.w700, color: kInk),
                        overflow: TextOverflow.ellipsis),
                    subtitle: Text(
                      t['last_message'] as String? ?? 'No replies yet',
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(color: kMuted, fontSize: 12.5),
                    ),
                    trailing: unread
                        ? Container(
                            width: 9,
                            height: 9,
                            decoration: const BoxDecoration(
                                color: kNavy, shape: BoxShape.circle),
                          )
                        : const Icon(Icons.chevron_right, color: kMuted),
                    onTap: () async {
                      await Navigator.of(context).push(MaterialPageRoute(
                        builder: (_) => MessageThreadScreen(
                          threadId: t['id'] as int,
                          subject: t['subject'] as String? ?? 'Messages',
                        ),
                      ));
                      if (mounted) setState(() => _future = _load());
                    },
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

// ── Thread detail ────────────────────────────────────────────────────────
class MessageThreadScreen extends StatefulWidget {
  const MessageThreadScreen({super.key, required this.threadId, required this.subject});
  final int threadId;
  final String subject;

  @override
  State<MessageThreadScreen> createState() => _MessageThreadScreenState();
}

class _MessageThreadScreenState extends State<MessageThreadScreen> {
  late Future<Map<String, dynamic>> _future;
  final _bodyCtrl = TextEditingController();
  bool _sending = false;
  String _status = 'open';

  @override
  void initState() {
    super.initState();
    _future = _load();
  }

  Future<Map<String, dynamic>> _load() async {
    final data = await ApiClient.instance.get('/messages/${widget.threadId}');
    final thread = data['thread'] as Map<String, dynamic>;
    _status = thread['status'] as String? ?? 'open';
    return thread;
  }

  Future<void> _send() async {
    final body = _bodyCtrl.text.trim();
    if (body.isEmpty) return;
    setState(() => _sending = true);
    try {
      await ApiClient.instance.post('/messages/${widget.threadId}/reply', {'body': body});
      _bodyCtrl.clear();
      setState(() => _future = _load());
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text('Send failed: $e'), backgroundColor: kRisk));
      }
    } finally {
      if (mounted) setState(() => _sending = false);
    }
  }

  @override
  void dispose() {
    _bodyCtrl.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text(widget.subject, overflow: TextOverflow.ellipsis)),
      body: Column(
        children: [
          Expanded(
            child: FutureBuilder<Map<String, dynamic>>(
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
                final replies = (snap.data!['replies'] as List).cast<Map<String, dynamic>>();
                if (replies.isEmpty) {
                  return const Center(
                      child: Text('No messages yet.', style: TextStyle(color: kMuted)));
                }
                return ListView.builder(
                  padding: const EdgeInsets.all(14),
                  itemCount: replies.length,
                  itemBuilder: (context, i) {
                    final r = replies[i];
                    final isMe = r['is_me'] == true;
                    return Align(
                      alignment: isMe ? Alignment.centerRight : Alignment.centerLeft,
                      child: Container(
                        margin: const EdgeInsets.only(bottom: 10),
                        constraints: BoxConstraints(
                            maxWidth: MediaQuery.of(context).size.width * 0.78),
                        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
                        decoration: BoxDecoration(
                          color: isMe ? kNavy : const Color(0xFFF1F5F9),
                          borderRadius: BorderRadius.circular(14),
                        ),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            if (!isMe)
                              Padding(
                                padding: const EdgeInsets.only(bottom: 3),
                                child: Text(
                                  r['sender_name'] as String? ?? 'Admin',
                                  style: const TextStyle(
                                      fontSize: 11, fontWeight: FontWeight.w700, color: kGold),
                                ),
                              ),
                            Text(
                              r['body'] as String? ?? '',
                              style: TextStyle(
                                  color: isMe ? Colors.white : kInk, fontSize: 13.5, height: 1.4),
                            ),
                            const SizedBox(height: 4),
                            Text(
                              _formatTime(r['created_at'] as String?),
                              style: TextStyle(
                                  fontSize: 10,
                                  color: isMe ? Colors.white70 : kMuted),
                            ),
                          ],
                        ),
                      ),
                    );
                  },
                );
              },
            ),
          ),
          if (_status == 'open')
            SafeArea(
              top: false,
              child: Container(
                padding: const EdgeInsets.fromLTRB(10, 8, 10, 8),
                decoration: const BoxDecoration(
                  color: Colors.white,
                  border: Border(top: BorderSide(color: Color(0xFFD8E0E8))),
                ),
                child: Row(
                  children: [
                    Expanded(
                      child: TextField(
                        controller: _bodyCtrl,
                        minLines: 1,
                        maxLines: 4,
                        textCapitalization: TextCapitalization.sentences,
                        decoration: InputDecoration(
                          hintText: 'Type your reply…',
                          contentPadding:
                              const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
                          border: OutlineInputBorder(borderRadius: BorderRadius.circular(22)),
                        ),
                      ),
                    ),
                    const SizedBox(width: 8),
                    IconButton.filled(
                      onPressed: _sending ? null : _send,
                      style: IconButton.styleFrom(backgroundColor: kGold),
                      icon: _sending
                          ? const SizedBox(
                              width: 18,
                              height: 18,
                              child: CircularProgressIndicator(
                                  strokeWidth: 2.5, color: kNavy))
                          : const Icon(Icons.send_rounded, color: kNavy),
                    ),
                  ],
                ),
              ),
            )
          else
            Container(
              width: double.infinity,
              padding: const EdgeInsets.all(14),
              color: const Color(0xFFF1F5F9),
              child: const Text('This thread has been closed.',
                  textAlign: TextAlign.center,
                  style: TextStyle(color: kMuted, fontSize: 12.5)),
            ),
        ],
      ),
    );
  }

  String _formatTime(String? iso) {
    if (iso == null) return '';
    try {
      return DateFormat('d MMM, h:mm a').format(DateTime.parse(iso).toLocal());
    } catch (_) {
      return '';
    }
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

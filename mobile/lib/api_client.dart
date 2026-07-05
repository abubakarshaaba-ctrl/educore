import 'dart:convert';

import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';

/// Thin client for the EduCore mobile API (routes/api.php, v1).
class ApiClient {
  ApiClient._();
  static final ApiClient instance = ApiClient._();

  static const String baseUrl = 'https://educoreng.online/api/v1';

  String? _token;
  Map<String, dynamic>? user;
  Map<String, dynamic>? school;

  bool get isLoggedIn => _token != null;

  Future<void> restore() async {
    final prefs = await SharedPreferences.getInstance();
    _token = prefs.getString('token');
    final u = prefs.getString('user');
    final s = prefs.getString('school');
    if (u != null) user = jsonDecode(u) as Map<String, dynamic>;
    if (s != null) school = jsonDecode(s) as Map<String, dynamic>;
  }

  Map<String, String> get _headers => {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        if (_token != null) 'Authorization': 'Bearer $_token',
      };

  Future<Map<String, dynamic>> _decode(http.Response res) async {
    final body = res.body.isEmpty
        ? <String, dynamic>{}
        : jsonDecode(res.body) as Map<String, dynamic>;
    if (res.statusCode >= 200 && res.statusCode < 300) return body;
    throw ApiException(
      (body['message'] as String?) ?? 'Request failed (${res.statusCode})',
      res.statusCode,
    );
  }

  Future<Map<String, dynamic>> get(String path,
      [Map<String, String>? query]) async {
    final uri = Uri.parse('$baseUrl$path').replace(queryParameters: query);
    return _decode(await http.get(uri, headers: _headers));
  }

  Future<Map<String, dynamic>> post(String path, Map<String, dynamic> body) async {
    final uri = Uri.parse('$baseUrl$path');
    return _decode(
        await http.post(uri, headers: _headers, body: jsonEncode(body)));
  }

  Future<void> login(String loginId, String password) async {
    final data = await post('/auth/login', {
      'login_id': loginId,
      'password': password,
      'device': 'flutter-app',
    });
    _token = data['token'] as String;
    user = data['user'] as Map<String, dynamic>;
    school = data['school'] as Map<String, dynamic>;

    final prefs = await SharedPreferences.getInstance();
    await prefs.setString('token', _token!);
    await prefs.setString('user', jsonEncode(user));
    await prefs.setString('school', jsonEncode(school));
  }

  Future<void> logout() async {
    try {
      await post('/auth/logout', {});
    } catch (_) {/* token may already be dead — clear locally regardless */}
    _token = null;
    user = null;
    school = null;
    final prefs = await SharedPreferences.getInstance();
    await prefs.clear();
  }
}

class ApiException implements Exception {
  ApiException(this.message, this.status);
  final String message;
  final int status;

  @override
  String toString() => message;
}

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
  Set<String> permissions = <String>{};

  bool get isLoggedIn => _token != null;

  Future<void> restore() async {
    final prefs = await SharedPreferences.getInstance();
    _token = prefs.getString('token');
    final u = prefs.getString('user');
    final s = prefs.getString('school');
    final p = prefs.getStringList('permissions');
    if (u != null) user = jsonDecode(u) as Map<String, dynamic>;
    if (s != null) school = jsonDecode(s) as Map<String, dynamic>;
    permissions = (p ?? const <String>[]).toSet();
  }

  bool can(String permission) => permissions.contains(permission);

  bool canAny(Iterable<String> requested) => requested.any(can);

  Map<String, String> get _headers => {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
    if (_token != null) 'Authorization': 'Bearer $_token',
  };

  /// Auth headers for loading images via Image.network from the API.
  Map<String, String> get imageHeaders => {
    if (_token != null) 'Authorization': 'Bearer $_token',
  };

  /// Absolute URL for an authenticated API resource (e.g. an image endpoint).
  String url(String path) => '$baseUrl$path';

  Future<Map<String, dynamic>> _decode(http.Response res) async {
    final body = res.body.isEmpty
        ? <String, dynamic>{}
        : jsonDecode(res.body) as Map<String, dynamic>;
    if (res.statusCode >= 200 && res.statusCode < 300) return body;
    if (res.statusCode == 401) await _clearSession();
    throw ApiException(
      (body['message'] as String?) ?? 'Request failed (${res.statusCode})',
      res.statusCode,
    );
  }

  Future<Map<String, dynamic>> get(
    String path, [
    Map<String, String>? query,
  ]) async {
    final uri = Uri.parse('$baseUrl$path').replace(queryParameters: query);
    return _decode(await http.get(uri, headers: _headers));
  }

  Future<Map<String, dynamic>> post(
    String path,
    Map<String, dynamic> body,
  ) async {
    final uri = Uri.parse('$baseUrl$path');
    return _decode(
      await http.post(uri, headers: _headers, body: jsonEncode(body)),
    );
  }

  Future<Map<String, dynamic>> patch(
    String path,
    Map<String, dynamic> body,
  ) async {
    final uri = Uri.parse('$baseUrl$path');
    return _decode(
      await http.patch(uri, headers: _headers, body: jsonEncode(body)),
    );
  }

  /// Upload a single file (multipart) to an authenticated endpoint.
  Future<Map<String, dynamic>> upload(
    String path,
    String field,
    String filePath,
  ) async {
    final uri = Uri.parse('$baseUrl$path');
    final req = http.MultipartRequest('POST', uri)
      ..headers['Accept'] = 'application/json'
      ..headers['Authorization'] = 'Bearer $_token'
      ..files.add(await http.MultipartFile.fromPath(field, filePath));
    final streamed = await req.send();
    final res = await http.Response.fromStream(streamed);
    return _decode(res);
  }

  /// Download raw bytes (e.g. a PDF) from an authenticated endpoint.
  Future<List<int>> download(String path) async {
    final uri = Uri.parse('$baseUrl$path');
    final res = await http.get(
      uri,
      headers: {
        'Accept': 'application/pdf',
        if (_token != null) 'Authorization': 'Bearer $_token',
      },
    );
    if (res.statusCode >= 200 && res.statusCode < 300) return res.bodyBytes;
    throw ApiException('Download failed (${res.statusCode})', res.statusCode);
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
    permissions = ((data['permissions'] as List<dynamic>?) ?? const [])
        .map((value) => value.toString())
        .toSet();

    final prefs = await SharedPreferences.getInstance();
    await prefs.setString('token', _token!);
    await prefs.setString('user', jsonEncode(user));
    await prefs.setString('school', jsonEncode(school));
    await prefs.setStringList('permissions', permissions.toList()..sort());
  }

  Future<void> refreshSession() async {
    final data = await get('/me');
    user = data['user'] as Map<String, dynamic>;
    school = data['school'] as Map<String, dynamic>;
    permissions = ((data['permissions'] as List<dynamic>?) ?? const [])
        .map((value) => value.toString())
        .toSet();

    final prefs = await SharedPreferences.getInstance();
    await prefs.setString('user', jsonEncode(user));
    await prefs.setString('school', jsonEncode(school));
    await prefs.setStringList('permissions', permissions.toList()..sort());
  }

  Future<void> logout() async {
    try {
      await post('/auth/logout', {});
    } catch (_) {
      /* token may already be dead — clear locally regardless */
    }
    await _clearSession();
  }

  Future<void> _clearSession() async {
    _token = null;
    user = null;
    school = null;
    permissions = <String>{};
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove('token');
    await prefs.remove('user');
    await prefs.remove('school');
    await prefs.remove('permissions');
  }
}

class ApiException implements Exception {
  ApiException(this.message, this.status);
  final String message;
  final int status;

  @override
  String toString() => message;
}

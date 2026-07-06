import 'package:firebase_core/firebase_core.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter/foundation.dart';

import 'api_client.dart';

/// Firebase Cloud Messaging wiring.
///
/// Every call here is wrapped so that a school running the app before the
/// Firebase project exists (no google-services.json / FCM server config)
/// simply gets no push notifications — every other feature keeps working.
class PushService {
  PushService._();
  static final PushService instance = PushService._();

  bool _initialized = false;

  Future<void> init() async {
    if (_initialized) return;
    try {
      await Firebase.initializeApp();
      _initialized = true;
    } catch (e) {
      debugPrint('Push: Firebase not configured yet ($e) — push disabled.');
    }
  }

  /// Request permission and register this device's token with the backend.
  /// Call after a successful login (and optionally on app start if already
  /// logged in) so the token is always tied to the current user.
  Future<void> registerForCurrentUser() async {
    if (!_initialized || !ApiClient.instance.isLoggedIn) return;

    try {
      final messaging = FirebaseMessaging.instance;
      await messaging.requestPermission(alert: true, badge: true, sound: true);

      final token = await messaging.getToken();
      if (token != null) {
        await ApiClient.instance.post('/push/register', {
          'token': token,
          'platform': 'android',
        });
      }

      FirebaseMessaging.instance.onTokenRefresh.listen((newToken) {
        ApiClient.instance
            .post('/push/register', {'token': newToken, 'platform': 'android'})
            .catchError((_) => <String, dynamic>{});
      });
    } catch (e) {
      debugPrint('Push: registration failed ($e).');
    }
  }
}

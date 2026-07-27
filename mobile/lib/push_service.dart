import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter/foundation.dart';

import 'api_client.dart';

/// Firebase Cloud Messaging service for EduCore mobile app.
///
/// Handles:
/// - FCM token acquisition and refresh
/// - Sending token to backend for device registration
/// - Foreground message handling
/// - Background message handling (top-level handler)
/// - Notification permission requests
class PushService {
  PushService._();
  static final PushService instance = PushService._();

  final FirebaseMessaging _messaging = FirebaseMessaging.instance;
  String? _currentToken;

  /// Current FCM token (may be null if not yet retrieved).
  String? get token => _currentToken;

  /// Initialize Firebase Messaging.
  ///
  /// Call this early in [main] before [runApp].
  Future<void> init() async {
    try {
      // Request permission for notifications (iOS & Android 13+)
      final settings = await _messaging.requestPermission(
        alert: true,
        badge: true,
        sound: true,
        provisional: false,
      );

      debugPrint('PushService: Permission status = ${settings.authorizationStatus}');

      // Get initial token
      _currentToken = await _messaging.getToken();
      debugPrint('PushService: FCM token = $_currentToken');

      // Listen for token refreshes
      _messaging.onTokenRefresh.listen((newToken) {
        debugPrint('PushService: Token refreshed = $newToken');
        _currentToken = newToken;
        _registerToken(newToken);
      });

      // Handle foreground messages
      FirebaseMessaging.onMessage.listen(_handleForegroundMessage);

      // Handle notification opened app (user tapped notification)
      FirebaseMessaging.onMessageOpenedApp.listen(_handleMessageOpenedApp);

      // Check if app was opened from a terminated state via notification
      final initialMessage = await _messaging.getInitialMessage();
      if (initialMessage != null) {
        debugPrint('PushService: App opened from terminated state via notification');
        _handleMessageOpenedApp(initialMessage);
      }
    } catch (e) {
      debugPrint('PushService: Initialization error: $e');
    }
  }

  /// Register the current user's device token with the backend.
  ///
  /// Should be called after login when we have a valid auth token.
  Future<void> registerForCurrentUser() async {
    if (!ApiClient.instance.isLoggedIn) {
      debugPrint('PushService: Not logged in, skipping registration');
      return;
    }

    try {
      // Ensure we have a token
      _currentToken ??= await _messaging.getToken();
      if (_currentToken == null) {
        debugPrint('PushService: No FCM token available');
        return;
      }

      await _registerToken(_currentToken!);
    } catch (e) {
      debugPrint('PushService: Registration error: $e');
    }
  }

  /// Send the FCM token to the backend for device registration.
  Future<void> _registerToken(String token) async {
    if (!ApiClient.instance.isLoggedIn) {
      debugPrint('PushService: Not logged in, skipping token registration');
      return;
    }

    try {
      final platform = defaultTargetPlatform == TargetPlatform.iOS ? 'ios' : 'android';
      await ApiClient.instance.post('/devices/register', {
        'fcm_token': token,
        'platform': platform,
      });
      debugPrint('PushService: Token registered with backend');
    } catch (e) {
      debugPrint('PushService: Backend registration failed: $e');
    }
  }

  /// Unregister the device token from the backend (call on logout).
  Future<void> unregisterToken() async {
    if (_currentToken == null) return;

    try {
      await ApiClient.instance.post('/devices/unregister', {
        'fcm_token': _currentToken,
      });
      debugPrint('PushService: Token unregistered');
    } catch (e) {
      debugPrint('PushService: Unregister failed: $e');
    }
  }

  /// Handle messages received while the app is in the foreground.
  void _handleForegroundMessage(RemoteMessage message) {
    debugPrint('PushService: Foreground message received: ${message.messageId}');
    debugPrint('PushService: Title: ${message.notification?.title}');
    debugPrint('PushService: Body: ${message.notification?.body}');
    debugPrint('PushService: Data: ${message.data}');

    // You can show an in-app notification banner here
    // For now, we just log it. A future enhancement would be to show
    // a custom snackbar or overlay notification.
  }

  /// Handle when user taps on a notification to open the app.
  void _handleMessageOpenedApp(RemoteMessage message) {
    debugPrint('PushService: Message opened app: ${message.messageId}');
    debugPrint('PushService: Data: ${message.data}');

    // Navigate to the appropriate screen based on notification data
    final type = message.data['type'] as String?;
    final id = message.data['id'] as String?;

    if (type != null) {
      debugPrint('PushService: Should navigate to $type (id: $id)');
      // Navigation will be handled by the app's router based on the data
    }
  }
}

/// Top-level background message handler.
///
/// This MUST be a top-level function (not a method or closure).
@pragma('vm:entry-point')
Future<void> firebaseMessagingBackgroundHandler(RemoteMessage message) async {
  debugPrint('PushService: Background message received: ${message.messageId}');
  debugPrint('PushService: Title: ${message.notification?.title}');
  debugPrint('PushService: Body: ${message.notification?.body}');
  debugPrint('PushService: Data: ${message.data}');

  // Background messages are handled here. You can update local storage,
  // sync data, or perform other background tasks.
}

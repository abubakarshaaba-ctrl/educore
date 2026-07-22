/// Optional push-notification integration point.
///
/// The downloadable 1.0 release keeps this service dormant so the core app can
/// be distributed without Firebase's native Android SDK. The API registration
/// endpoints remain available for a later Play Store build with FCM enabled.
class PushService {
  PushService._();
  static final PushService instance = PushService._();

  Future<void> init() async {}

  Future<void> registerForCurrentUser() async {}
}

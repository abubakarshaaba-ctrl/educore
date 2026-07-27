import 'package:firebase_core/firebase_core.dart' show FirebaseOptions;
import 'package:flutter/foundation.dart'
    show defaultTargetPlatform, TargetPlatform;

/// Default [FirebaseOptions] for use with your Firebase apps.
///
/// To regenerate this file, run `flutterfire configure`.
class DefaultFirebaseOptions {
  static FirebaseOptions get currentPlatform {
    switch (defaultTargetPlatform) {
      case TargetPlatform.android:
        return android;
      case TargetPlatform.iOS:
        return ios;
      default:
        throw UnsupportedError(
          'DefaultFirebaseOptions are not configured for this platform. '
          'You can re-run `flutterfire configure` to generate them.',
        );
    }
  }

  static const FirebaseOptions android = FirebaseOptions(
    apiKey: 'AIzaSyDLMVEczU901dMLmagEvlSFEOtDbFhgCVQ',
    appId: '1:532841786092:android:447919ca1cf9d7348693c9',
    messagingSenderId: '532841786092',
    projectId: 'educore-35d95',
    storageBucket: 'educore-35d95.firebasestorage.app',
  );

  // TODO: Add iOS Firebase options once GoogleService-Info.plist is obtained.
  // Run `flutterfire configure` to generate this, or manually add values
  // from your Firebase iOS app registration.
  static const FirebaseOptions ios = FirebaseOptions(
    apiKey: 'YOUR_IOS_API_KEY',
    appId: 'YOUR_IOS_APP_ID',
    messagingSenderId: '532841786092',
    projectId: 'educore-35d95',
    storageBucket: 'educore-35d95.firebasestorage.app',
    iosBundleId: 'online.educoreng.educoreStaff',
  );
}

# EduCore Staff — Mobile App (Flutter)

Teacher/staff companion app for EduCore. **Not** a webview of the site —
purpose-built mobile screens backed by the JSON API at
`https://educoreng.online/api/v1` (see `educore/routes/api.php`).

## V1 features
- Unified staff sign-in (staff ID or email) — same rules as the web login
- My Classes: form-tutor classes + subject-teacher assignments
- Attendance: tap-to-cycle marking (present / absent / late / excused),
  "All present" shortcut, date picker for backfilling, one-tap save
- Staff clock-in/out: scan the school's display QR (or your ID card),
  geo-fence honoured when the school enables it, month history + counts
- Announcements: staff-targeted school news
- Profile + sign out

## Getting started
1. Install the Flutter SDK: https://docs.flutter.dev/get-started/install/windows
   (add `flutter\bin` to PATH; run `flutter doctor` until it's happy —
   for Android you need Android Studio + an emulator or a real device
   with USB debugging).
2. From this `mobile/` folder, generate the platform scaffolding around
   the existing `lib/` code:

   ```
   flutter create . --org online.educoreng --project-name educore_staff
   flutter pub get
   ```

3. Run it:

   ```
   flutter run
   ```

## Configuration
- API base URL lives in `lib/api_client.dart` (`ApiClient.baseUrl`).
- Tokens are stored with `shared_preferences` and last 90 days
  (see `educore/app/Models/ApiToken.php`).

## Backend counterpart
- `educore/routes/api.php` — v1 endpoints
- `educore/app/Http/Middleware/AuthenticateApiToken.php` — bearer auth
- `educore/app/Http/Controllers/Api/*` — Auth, Teacher, Attendance
- `api_tokens` table migration: `2026_07_05_100001_create_api_tokens_table.php`
  (runs automatically on deploy via `.cpanel.yml` `migrate --force`)

## Permissions (add after `flutter create .`)
- **Camera** (QR clock-in): `android/app/src/main/AndroidManifest.xml` needs
  `<uses-permission android:name="android.permission.CAMERA"/>`;
  iOS `Info.plist` needs `NSCameraUsageDescription`.
- **Location** (geo-fenced schools): add
  `<uses-permission android:name="android.permission.ACCESS_FINE_LOCATION"/>`
  and iOS `NSLocationWhenInUseUsageDescription`.

## Push notifications (Firebase Cloud Messaging)
Push is wired end-to-end but stays dormant until a Firebase project exists —
the app and API both no-op gracefully without it:
1. Create a free Firebase project, add an Android app for
   `online.educoreng.educore_staff`, download `google-services.json` into
   `mobile/android/app/`.
2. Generate a service-account key (Project Settings -> Service Accounts) and
   place it on the server (e.g. `storage/app/firebase-service-account.json`);
   set `FCM_PROJECT_ID` and `FCM_CREDENTIALS_PATH` in `.env`.
3. Rebuild the APK — `android/app/build.gradle.kts` only applies the
   google-services Gradle plugin when the json file is present.

## Roadmap (next phases)
- Report-card remarks for form tutors
- Offline attendance queue with sync

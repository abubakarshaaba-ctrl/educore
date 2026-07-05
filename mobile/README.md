# EduCore Staff — Mobile App (Flutter)

Teacher/staff companion app for EduCore. **Not** a webview of the site —
purpose-built mobile screens backed by the JSON API at
`https://educoreng.online/api/v1` (see `educore/routes/api.php`).

## V1 features
- Unified staff sign-in (staff ID or email) — same rules as the web login
- My Classes: form-tutor classes + subject-teacher assignments
- Attendance: tap-to-cycle marking (present / absent / late / excused),
  "All present" shortcut, date picker for backfilling, one-tap save
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

## Roadmap (next phases)
- Score entry per subject/assessment
- Report-card remarks for form tutors
- Push notifications for announcements
- Offline attendance queue with sync

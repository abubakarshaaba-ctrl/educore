# EduCore — Unified Mobile App (Flutter)

Role-aware companion app for EduCore. **Not** a webview of the site —
purpose-built administrator, staff, student, and parent screens backed by the JSON API at
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

## Teacher/staff RBAC

The staff application is one role-aware experience inside the unified EduCore
mobile client. Login and `GET /api/v1/me` return the authenticated user's
effective permission names. Flutter uses those permissions to build the bottom
navigation, while Laravel remains authoritative and checks protected class,
attendance, and score routes independently.

Teaching data is assignment-scoped: form tutors see their assigned arm and
subject teachers see only their assigned class/subject combinations. A hidden
mobile navigation item is never treated as authorization.

## Student portal

Student accounts use the same login and are routed to a self-service dashboard.
The API resolves the student profile from the authenticated user and never
accepts a client-supplied student id. Dashboard, timetable, results and CBT exam
queries are therefore both tenant-scoped and student-scoped on the server.

## Parent/guardian portal

Parent accounts use the unified login and are routed to a linked-child
dashboard. The API resolves the guardian from the authenticated account and
validates every requested child against the `guardian_student` relationship.
Parents can review child summaries, attendance, results, invoices and school
announcements without gaining access to unrelated student records.

## School administrator portal

School administrators, principals, heads, vice principals, and academic
administrators use the same login and receive a management dashboard matched to
their role. It provides school-wide KPIs, student and staff directories, class
and subject oversight, admissions counts, and daily attendance visibility.
Financial totals and invoice records are returned and displayed only when the
role includes the `fees` module. Every administrator endpoint validates both the
management role and the requested module on the server; Flutter's conditional
navigation is an additional usability layer, not the authorization boundary.

## Platform Super Admin portal

Verified platform super administrators can use the unified login without being
attached to a school tenant. They are routed to a separate cross-tenant command
centre for platform KPIs, school lifecycle monitoring, subscription plans,
revenue, and payment oversight. Platform endpoints reject every non-super-admin
account, while ordinary school accounts remain tenant-scoped. This portal is
operationally separate from the School Administrator portal and never inherits
a selected school's permissions.

## Transport and health officer portals

Transport officers receive an operations workspace for route capacity, buses,
drivers, passenger manifests, and assigning unassigned students to active
routes. Assignment validation confirms both the student and route belong to the
officer's tenant.

Health officers receive a confidential student-health workspace with allergy
and medication alerts, searchable student records, emergency contacts, and
secure record editing. Medical fields are served only by health-specific API
endpoints and every student binding is verified against the authenticated
school. Dedicated `transport.view/manage` and `health.view/manage` permissions
document these responsibilities in RBAC, while the API independently verifies
the officer role.

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

## Push notifications
The direct-download 1.0 release keeps the client push hook dormant and does
not bundle the Firebase native SDK. In-app notifications remain available.
Firebase Cloud Messaging can be enabled in a later store release by adding a
Firebase Android app for `online.educoreng.educore`, restoring the FlutterFire
client packages, and configuring `FCM_PROJECT_ID` and `FCM_CREDENTIALS_PATH`
on the API server.

## Roadmap (next phases)
- Report-card remarks for form tutors
- Offline attendance queue with sync

# EduCore Mobile Overhaul — Phase A: Architecture Stabilization

Branch: `mobile-overhaul`

## Objective

Stabilize mobile routing before visual redesign. The native app must not accidentally fall through to web routes. Every mobile module must have an explicit presentation mode and a validated destination.

## Current architecture confirmed

The Android shell receives module descriptors from Laravel `MobileModuleService`. `AuthorizedShell.kt` handles selected keys natively and sends all remaining keys to `onOpenWebModule(module.path)`. `MainViewModel` asks `/api/v1/portal/session` for a temporary authenticated URL, then `EduCoreFoundationApp.kt` launches that URL through Android `ACTION_VIEW` in the external browser. Laravel `MobilePortalController` consumes the temporary token and redirects to the supplied module path.

This is the direct cause of mixed native/browser behaviour and of 404 pages when a configured module path does not correspond to a valid live web route.

## Current native routing inventory

| Module key(s) | Current mobile behaviour | Phase-A classification | Required direction |
|---|---|---|---|
| `classes`, `students`, `attendance` | Native Classes workspace | NATIVE | Keep native; separate true class workflows from generic module hub |
| `staff-attendance`, `staff-attendance.self` | Native Staff Attendance | NATIVE | Keep native; move out of Classes tab |
| `scores`, `scores.entry` | Native only when permission check passes, otherwise web | ROLE_CONDITIONAL | Prefer native for authorized staff; web fallback should not be generic |
| `timetable`, `student.timetable` | Native Schedule | NATIVE | Keep native |
| `student.exams` | Native CBT | NATIVE | Keep native |
| `cbt`, `cbt-exams`, `examinations` for non-students | Web fallback | WEB_ONLY_FOR_NOW | Explicitly classify until native staff CBT administration is implemented |
| `student.results`, `parent.results` | Native Results | NATIVE | Keep native |
| `results`, `report-cards`, `reports` for staff | Web fallback | WEB_ONLY_FOR_NOW | Convert to native in Phase C |
| `academic-repository` | Native | NATIVE | Keep native |
| `lesson-planner` | Native | NATIVE | Keep native |
| communication keys (`messages`, notifications, calendar variants) | Native communication center | NATIVE | Keep native |
| `fees`, `parent.fees`, `expenses`, `payroll`, `admissions`, `library`, `transport`, `health`, `inventory`, `hostels`, `subjects`, `curriculum`, `academic-cycle` | Native generic Operations workspace | NATIVE_GENERIC | Keep API-backed native, redesign presentation later |
| `staff` | Generic web fallback | WEB_ONLY_FOR_NOW | Convert to native staff directory/management where role permits |
| `analytics` | Generic web fallback | WEB_ONLY_FOR_NOW | Consider native dashboard later |
| `exports` | Generic web fallback | WEB_ONLY_FOR_NOW | Keep web-only if export/download workflow is deliberate |
| `profile` | Generic web fallback | WEB_ONLY_FOR_NOW | Replace with native Profile in Phase C |
| platform/super-admin module keys | Generic web fallback | WEB_ONLY_FOR_NOW | Explicitly mark as web-only until dedicated platform mobile scope is defined |
| any unknown future module key | Generic web fallback | UNSAFE_DEFAULT | Must become UNSUPPORTED, never automatic browser launch |

## Defects identified

1. `else -> onOpenWebModule(module.path)` is an unsafe catch-all. Unknown or stale modules automatically launch the browser.
2. `MobileModuleService` stores hard-coded web paths and assumes they remain valid after Laravel route changes.
3. The backend authorizes a requested path against the module list, but does not verify that the resulting destination resolves to a valid route before issuing a portal session URL.
4. The shell groups modules using substring matching. Because `attendance`, `score`, `report`, `cbt`, etc. are all classified as academics, the `Classes` tab becomes a generic academic module directory rather than a class workspace.
5. `ModuleCard` uses `subtitle = module.key`, exposing technical identifiers such as `staff-attendance.self` to end users.
6. Web fallback is role-dependent for Scores, CBT and Results, producing inconsistent behaviour for different accounts.
7. Fixed-height module cards and generic two-column grids are being used for workflows that should have purpose-built screens.

## Mandatory Phase-A architecture rules

- Every module key must resolve through an explicit mobile routing policy.
- Presentation modes: `NATIVE`, `NATIVE_GENERIC`, `WEB_ONLY`, `ROLE_CONDITIONAL`, `UNSUPPORTED`.
- Unknown keys default to `UNSUPPORTED`.
- Browser launch is allowed only for an explicitly registered `WEB_ONLY` module.
- Laravel must validate a web-only module destination before issuing a temporary portal session.
- Android must show a user-friendly unavailable state instead of launching an unknown route.
- Technical module keys must not be rendered as visible card subtitles.
- Web-only modules must be visibly distinguishable in the UI if retained temporarily.
- All teacher-critical workflows should ultimately become native: classes, attendance, scores, lesson planner, timetable, report cards, CBT, repository, notices/messages, profile.

## Phase-A implementation sequence

1. Introduce an explicit Android mobile-module routing policy.
2. Replace generic browser fallback with explicit routing outcomes.
3. Add backend destination validation for web-only modules.
4. Add tests covering every current `MobileModuleService` key and asserting that no key relies on an implicit fallback.
5. Remove technical module keys from card subtitles.
6. Add telemetry/logging for unsupported module keys and failed portal-session redirects.
7. Verify teacher, school-admin, student, parent and super-admin module inventories independently.

## Exit criteria

Phase A is complete only when:

- no unknown module can open a browser;
- every configured module has an intentional presentation mode;
- stale web routes cannot produce an avoidable 404 from the app;
- the module inventory is covered by tests;
- technical route/module identifiers are not exposed in the UI;
- native vs web behaviour is predictable for every supported role.

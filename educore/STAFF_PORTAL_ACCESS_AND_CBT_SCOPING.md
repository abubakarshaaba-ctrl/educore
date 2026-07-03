# EduCore — Staff Portal Access Control, CBT Scoping, and Two Real Permission Bugs

This delivery covers all 12 items from your screenshots. Two of them — #8 (staff-attendance denied)
and #12 (the archive question) — turned out to be genuine, previously-invisible bugs that affected
**every** non-super-admin user in the system, not just the "Form & Subject Teacher" account you were
testing with. Details below.

Run `php artisan migrate` (five new guarded migrations) and clear caches before testing.

---

## 1. Staff attendance "Access Denied" — real bug, fixed for everyone

Root cause: `'staff-attendance'` was never added to **any** role's permission list — not even
principal or admin-tier roles. Every staff member, regardless of role, was blocked from
`/staff-attendance/my` (their own clock-in history).

Fixed with a proper split:
- **`staff-attendance.self`** (every staff role): view own attendance history, clock in/out, proxy-clock
  a colleague, manage your own PIN, view your own ID card.
- **`staff-attendance`** (full — principal, head, head teacher, vice principal, academic
  administrator): the admin dashboard, settings, reports, manual override, QR display.

The "Staff Attendance" sidebar link was already shown to everyone (by design) — it just silently led
to a 403 for non-admins. That's resolved now.

## 2 & 5. Teachers scoped to their own subject/class — including a real authorization gap

Two separate things here:

- **Score entry "no class loaded"**: the dropdown filtering logic itself was already correct, but I
  found the page now also shows a clear message ("you haven't been assigned to any class/subject
  yet — ask your administrator") when a teacher's `class_arm_subjects` assignment genuinely doesn't
  exist yet, instead of silently showing an empty dropdown. If you still see this after the fix,
  it means that staff member needs to be assigned a subject+class via Classes → Assign Subject.
- **A real gap I found while in there**: `ScoreController::entry()` and `::save()` validated that the
  class/subject existed, but never verified the logged-in teacher was actually assigned to teach
  that class+subject. A subject teacher could previously submit any `class_arm_id`/`subject_id`
  combination directly (bypassing the dropdown) and enter scores for classes they don't teach. Now
  enforced server-side. Same fix applied to the broadsheet (form teachers are now scoped to their own
  class via `form_tutor_id`).

## 3. Route cleanup

Audited the full sidebar — only Dashboard and Profile are intentionally ungated (matches the
middleware's own allowlist); everything else was already properly wrapped in permission checks. The
real gap was staff-attendance (#1 above), now closed. The `/timetable/configure` and
`/timetable/frequency` "Access Denied" screens in your screenshots were from manually-typed URLs, not
the sidebar — the system was correctly blocking admin-only configuration pages from a teacher account.
That's working as intended, not a bug.

## 4. Current session/term auto-selected

Was already working on Score Entry and Timetable. Added the same default to: Skill Ratings, Score
Broadsheet, Score Import (both versions), CBT Exam creation, Gradebook, Report Cards index, and
Report Card Remarks.

## 6. Subject teachers scoped to their own CBT exams — comprehensive

Previously, **any** user with CBT module access could edit, delete, publish, or grade essays for
**any** question bank or exam in the school — a Biology teacher could delete a Mathematics bank.
Scoped throughout `CbtController` (banks, questions, bulk upload, exams, publish/close, results,
essay grading) using the same `class_arm_subjects` assignment table as the score-entry fix. Admin-tier
roles are unaffected.

## 7. Calendar crash — fixed

`resources/views/calendar/index.blade.php` had an `@if(...)` around the delete button with no
matching `@endif`, which is exactly the `ParseError: unexpected token "endforeach"` you saw. Added
the missing `@endif`.

## 8. Notifications — read-only is now actually read-only in the UI

Server-side was already correctly blocking read-only roles from sending (the form would have failed
with a 403 on submit) — but the Compose form was shown to everyone regardless, which is misleading.
Now the Compose card only renders for roles that can actually send; everyone else sees a clear
"Read-only access" notice and can still view recent message activity.

## 9. Staff bank details → payroll, lockable

Added a **Bank & Payroll Details** section to the staff profile page: Bank Name, Account Number,
Account Name, and Tax Identification Number. These write directly into the same `StaffSalarySetting`
record your payroll already uses. **Once a staff member saves it, it locks** — the profile page
switches to a read-only display, and only the accountant (via the existing Payroll → Salary Settings
page, which is unaffected by the lock) can make further changes. The salary-settings table now shows
a small "🔒 self-set" indicator next to anyone who's locked their own details, and gained a TIN column.

## 10. CBT page — found and fixed the actual broken rendering

The "Manage Questions" page wasn't just unstyled — it was genuinely broken. Laravel's default
pagination view assumes Tailwind CSS classes, which this app doesn't load, so the pagination arrows
rendered as giant unstyled raw SVGs (exactly what you saw). Rather than patch this one page, I traced
it to the root cause: **35+ pages across the app use the same broken pagination** (fees, library, sms,
announcements, admissions, and more). Fixed it once, globally — registered a custom, dependency-free
pagination view as Laravel's app-wide default. Every page using `->links()` is fixed by this single
change, not just CBT.

## 11. CBT exams — assign to class level or class arm

Same pattern as the subject-assignment feature from a previous session: the "Assign to" field on
exam creation now offers **whole level (one exam per arm)** or **specific class**, since the database
still requires one exam row per arm. Subject teachers can only assign to levels/arms they actually
teach.

## 12. "Where did you hide staff and student archives?" — found the real story

This wasn't about last session's sidebar cleanup. I traced it further and found something more
significant: **`StudentLifecyclePermissionSeeder` and `StaffLifecyclePermissionSeeder` were correctly
written, but never registered in `DatabaseSeeder.php`.** That means the permissions controlling
Student Archive, Staff Archive, status changes, reinstatement, and work-history — `student.archive.view`,
`staff.archive.view`, and eight related permissions — were **never actually granted to any role**,
including your own Administrator and Principal accounts. Only the platform super-admin could ever see
these pages. This predates last session's sidebar change entirely; the "Student Archive" button on
the Students page has likely never been visible to anyone at your school.

Fixed two ways:
- A new guarded migration applies the missing permission grants directly, so `php artisan migrate`
  fixes your live database immediately without a separate seed command.
- Registered both seeders properly in `DatabaseSeeder.php` for future fresh installs.
- Added a "Staff Archive" button to the Staff index page (Students already had one — it was just
  invisible to everyone due to the permission gap above).

Access after this fix: Admin/Principal/Head/Head Teacher get full archive + status-change + reinstate
access; Vice Principal/Academic Administrator get view access; Accountant gets staff archive view
(useful for final-pay lookups); Admission Officer gets student archive view; Form Teacher gets student
status view.

---

## Verification

All PHP across `app/`, `database/`, `routes/` lints clean (138 changed/new files). All views pass
structural checks (balanced `@if`/`@endif`, `@push`/`@endpush`, form tags; zero `?->` in echoes; zero
raw model references in views). The sandbox can't run your live stack, so please specifically verify:

1. Log in as a Form & Subject Teacher → Staff Attendance → confirm "My Attendance" loads (previously 403).
2. Same account → Score Entry → confirm the empty-state message appears if unassigned, or classes load
   correctly if assigned; try submitting a forged `class_arm_id` via dev tools to confirm the 403.
3. Same account → CBT → confirm only their own subject's banks/exams appear, and that the "Manage
   Questions" page pagination renders correctly (no more giant arrow).
4. Calendar page loads without a 500 error.
5. Notifications page shows the Compose form only for the right roles.
6. Profile page → Bank & Payroll Details → save once → confirm it locks; check Payroll → Salary
   Settings as the accountant → confirm it's still editable there.
7. Students and Staff index pages → confirm both Archive buttons now appear for Admin/Principal.

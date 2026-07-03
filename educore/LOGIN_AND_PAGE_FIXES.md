# EduCore — Login Surfaces + Five Page Fixes

This note covers two things you asked about: the **login routing** (verified — already wired
the way you described) and the **five "off" pages** from the screenshots.

Placement: drop the folder over your project root, then:

```
del /Q storage\framework\views\*.php   &  php artisan route:clear   (Windows)
rm -f storage/framework/views/*.php     ;  php artisan route:clear   (*nix)
```

---

## 1. Login routes — separated by audience (verified in place)

The rewire you asked for is present and correct. Five distinct surfaces, one shared auth core:

| Surface | URL | Route name | Who | Controller |
|---|---|---|---|---|
| Platform gateway | `/login` | `login` | **Super admin only** | `Auth\LoginController` |
| School administration | `/admin/login` | `admin.login` | principal, head, VP, academic admin, admission officer | `Auth\RoleLoginController` (`admin`) |
| School staff | `/staff/login` | `staff.login` | teachers + operational staff (all staff minus admins) | `Auth\RoleLoginController` (`staff`) |
| Students | `/student/login` | `student.login` | students | `Auth\RoleLoginController` (`student`) |
| Parents | `/portal/login` | `portal.parent.login` | parents (separate `ParentPortalAccount` model) | `ParentPortalController` |

How it behaves:

- **Platform `/login` is reserved for super administration.** A valid *school* user who lands
  there is not signed in — they're redirected to their own door with a hint (`schoolSurfaceFor()`),
  and a super admin goes to `super.dashboard`.
- **`RoleLoginController` reuses the proven core** (`LoginUserResolver` → `Hash::check` → active /
  employment checks → `TenantAccessService` → audit log) and adds **surface enforcement**: it
  verifies the authenticated user actually belongs to the surface they used. A staff member who
  tries the admin door (or vice-versa) is bounced to the correct login (`naturalSurfaceRoute()`),
  not signed in on the wrong one.
- **Parents stay on their own model.** They authenticate via `ParentPortalController`
  against `ParentPortalAccount`, not the `users` table — so they're intentionally separate from the
  three User-backed surfaces.
- All POST surfaces carry `throttle:tenant-login`. Every login attempt (success / denied, with the
  reason) is recorded through `AuthAuditLogger`.

Verified: all five route names resolve, the auth controllers lint clean, and every `User` method the
controller relies on (`isSuperAdmin / isStaff / isStudent / isParent / isTenantStaff /
isEmploymentActive / roleKey / is_active`) exists. **No change was needed here.**

> One thing only you can confirm: run `php artisan route:clear` then `php artisan route:list`
> on your machine to be sure the cached route table reflects these — caching is the usual reason a
> new login URL 404s locally.

---

## 2. The five pages

**Image 1 — `/settings/promotion` 500 "Undefined variable $subjects" → fixed.**
`ClassController::promotion()` now passes `$subjects` (the compulsory-subjects checklist the view
renders). This was a latent bug the new `settings.promotion` URL surfaced.

**Image 5 — `/skills` leaking `…rm-grid{grid-template-columns:1fr}}` as text → fixed.**
The `@media` rule sat outside `<style>`; it's now inside the `@push('styles')` block. (Same class of
leak was cleared across `scores/index`, `fees/gateway-settings`, `transport/routes` too.)

**Image 4 — `/billing/subscription` "Expiring soon" on a subscription that expires in 2027 → fixed.**
Root cause: the banner used `subscription_expires_at->diffInDays() < 14`. In Carbon 3 (Laravel 12),
`diffInDays()` is **signed** — a future date returns a *negative* number, which is always `< 14`, so
the banner fired for every active subscription. Replaced with a correct `Tenant::isExpiringSoon()`
helper (`expiry is in the future AND within 14 days`) and only shows the "pay an outstanding invoice"
line when an unpaid invoice actually exists — otherwise it reads "Renew your subscription to keep
uninterrupted access." (The super-admin dashboard's expiry list already used a correct query — left
as-is.)

**Image 3 — onboarding shows "Academic session and term: BLOCKING" next to "Review and activation:
COMPLETE" → fixed.**
Each step computed its status in isolation, so the final activation gate (`accessStep`) read COMPLETE
off the subscription check alone, ignoring that the calendar step was blocking. Added a rule in
`TenantOnboardingService::status()`: the review/activation step cannot be complete while **any**
upstream step is blocking — it now inherits a blocking state with a clear message, and the progress
percentage reflects it honestly.

**Image 2 — `/academic-cycle` Current-State Repair shows "Repair write mode is intentionally not
implemented in Phase 12A" → fixed.**
That's internal phase language in a school-facing screen. The repair tab is read-only **by design**
(it reviews state, it doesn't auto-write), so the fix was to say that plainly and drop the "Phase 12A"
label — in the view, the service `information` message, and the ops console command.

---

## Verification

All touched PHP lints clean; views show 0 nullsafe-in-echo, 0 model refs, balanced `@push/@endpush`.
Because the sandbox can't run your live stack, please smoke-test on your machine: each of the five
login URLs, a far-future subscription (no banner), a near-expiry one (banner with correct wording),
the onboarding page with a missing current term (review step should now read blocking), and the
repair tab copy.

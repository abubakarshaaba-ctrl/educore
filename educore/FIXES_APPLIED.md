# EduCore — Audit Remediation (cumulative manifest)

Every file in this package is a change against the original `educore.zip`. It spans the full audit
remediation (UI / functional / structural / completeness / housekeeping), the three originally-deferred
items, the login-surface separation, and the five page fixes. The login + page work is described in
**LOGIN_AND_PAGE_FIXES.md**; the audit + deferred items are summarised here.

## §2 Functional
- §2.1 Nullsafe `?->` in Blade echoes → `optional()` (81 across 36 views; 0 remain).
- §2.2 Inline Eloquent in Blade → View Composers in `AppServiceProvider`.
- §2.3 Misfiled `<style>` blocks moved from `@push('scripts')` to `@push('styles')`.
- §2.5 `hasTable`/`hasColumn` guards added to three additive migrations.
- §2.9 N+1 in admissions index/export → eager-load `applyingForClassLevel`.

## §3 Structural
- §3.2 (HIGH) `ClassController::storeGrade()` rewritten to real `grading_systems` columns.
- §3.4 `StudentTransferController` now writes lifecycle audit entries.

## §4 Completeness
- §4.1 (HIGH) Three guarded `create` migrations for the staff-attendance tables.
- §4.2 Proxy-OTP TODO wired to `NotificationController::sendSmsViaTermii()` (fails safe).

## §5 Housekeeping
- Removed stray brace-expansion view dir and `routes/web.php.bak`.

## Three previously-deferred items (resolved)
- **§3.3** Promotion **Rules** now canonical under `settings.promotion`; **Engine**
  (preview/run/history) stays under `classes.*`; old `classes.promotion` redirects.
- **§2.7** The two `ParentPortalController`s are *not* duplicates (legacy owns parent auth;
  `Portal\` is the richer login-less section). Kept both, documented with docblocks; no merge.
- **§1.4 / §1.5** Parent pages: deduped identical boilerplate into `parent/partials/base.blade.php`
  and added a shared mobile layer `parent/partials/responsive.blade.php`. Divergent per-page
  components left intact.

**Verification:** all PHP under `app/`, `database/`, `routes/` lints clean; 0 `?->` in echoes;
0 model refs in views; `@push`/`@endpush` balanced; no non-engine `classes.promotion` refs.
Run `php artisan route:clear` and a scratch-DB `migrate` on your side to confirm route caching and
migration ordering end-to-end.

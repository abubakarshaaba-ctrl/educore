# EduCore — Admin Access Lockout, Onboarding Restyle, Plan Selection

Three things from the latest screenshots. Drop the folder over your project root, then:

```
del /Q storage\framework\views\*.php   &  php artisan route:clear   (Windows)
rm -f storage/framework/views/*.php     ;  php artisan route:clear   (*nix)
```

---

## 1. "All pages are not accessible again on the school admin account" — fixed (the important one)

**Cause.** `app/Http/Middleware/EnsureTenantOnboardingComplete.php` re-checks onboarding on
*every* request and hard-redirects to the onboarding flow whenever any step is incomplete. It
exempted only onboarding routes and anything containing `billing` / `subscription` / `renew` /
`support` — which is exactly why you could still reach the onboarding and subscription pages and
nothing else.

The trap: it had **no exemption for schools that are already active**. Greenfield is an active,
operational tenant (its subscription shows Active), but its onboarding now reads incomplete because
the **current term lapsed** ("Academic session and term: BLOCKING"). So the gate locked the whole
ERP behind onboarding again.

**Fix.** Made it a *one-time* activation gate. A tenant only reaches `STATUS_ACTIVE` after the
platform activates it, and activation itself requires onboarding blocking items to be cleared — so
an active school has, by definition, already completed onboarding. The middleware now lets active
tenants straight through (the onboarding status is still shared with the UI so you can nudge them to
fix the lapsed term), and only **pending** (not-yet-activated) tenants are held in onboarding.

```php
if ($tenant->isActive()) {
    return $next($request);   // never re-lock an active school
}
```

Net effect: your Greenfield admin gets all pages back immediately. New schools still must finish
onboarding before they're let in. You'll still see the onboarding nudge until you set a current
term — which you can now do from School Setup without being locked out.

---

## 2. `/onboarding/academic-session` — file identified + restyled

**File:** `resources/views/tenant/onboarding/academic-session.blade.php`
(rendered by `TenantOnboardingController::academicSession`).

It was a bare form — the `.fc` input class it referenced isn't even defined anywhere, so the inputs
fell back to browser defaults. Restyled within the app's own tokens (navy `--midnight`, gold
`--indigo`, `--border`, `--slate`): a clear header with guidance, a note explaining that a current
session **and** term are both required, properly styled/labelled fields with helper hints and focus
states, and an "Existing Sessions" list that now shows each session's terms and a **Current** badge.
Responsive down to mobile. No logic moved into the view; the save flow is unchanged (it already
correctly sets exactly one current session + current term).

---

## 3. `/billing/subscription` — schools can now choose a plan to pay for

**Before:** the page only listed invoices a super admin had generated; a school had no way to pick a
plan, so with "No plan" and "No invoices yet" there was nothing to pay.

**Added a self-service plan picker.** `BillingController::index` now loads the active
`subscription_plans` (Basic / Standard / Premium, with decoded feature lists), and the view shows a
**Choose a plan** section: plan cards with a Monthly/Annual toggle, feature lists, and a
"Subscribe & Pay" button. The school's current plan (if any) is marked and not re-purchasable.

New action `BillingController::selectPlan` (route `billing.select-plan`): validates the chosen plan +
cycle, raises a pending `platform_invoice` for the school's own tenant (reusing an existing unpaid
one for the same plan+cycle so repeated clicks don't stack duplicates), and redirects to the
existing Paystack pay page (`super.billing.pay`, which already permits the tenant owner). If the
gateway isn't configured, the invoice simply appears in the Invoices table to pay later.

> The pay step depends on platform Paystack keys being set in Super Admin → Settings. Without them,
> the invoice is created but payment can't be initiated online (the existing behaviour).

---

## Verification

All touched PHP lints clean; both views pass the structural checks (balanced `@push/@endpush`, no
CSS leak, no `?->` in echoes, no model refs). Because the sandbox can't run your stack, please
smoke-test on your machine:

1. Log in as the Greenfield admin → confirm the dashboard and other pages load (no forced onboarding
   redirect), with a nudge to set the current term.
2. Set a current session + term on the restyled academic-session page → the nudge clears.
3. On `/billing/subscription`, pick a plan (try the Monthly/Annual toggle) → an invoice is created
   and you're taken to pay (or it lands in the Invoices table if Paystack isn't configured).

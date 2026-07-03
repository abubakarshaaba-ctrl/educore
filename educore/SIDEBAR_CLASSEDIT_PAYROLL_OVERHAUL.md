# EduCore — Sidebar/Login Cleanup, Class Editing, and a Full Payroll Overhaul

You sent 14 items. This delivery completes **9 of them** (#1, #2, #7, #8, #9, #10, #11, #12, #13) —
the ones that were either contained UI/bug fixes or, in payroll's case, needed real engineering and a
careful answer rather than a quick patch. **#3, #4, #5, #6, #14 are not in this delivery** — see
"What's not here yet" at the bottom for why and what's needed.

Run `php artisan migrate` (three new guarded migrations) and clear caches before testing.

---

## 1. Agent Portal button moved to the platform login

It now sits with the School Administrator / Staff / Student / Parent buttons on the central `/login`
page, and has been removed from the tenant-branded login and landing pages.

## 2. Student Archive / Staff Archive removed from the sidebar

Both nav items are gone. The underlying archive pages, routes, and reinstate/readmit flows are
untouched — only the sidebar links were removed, per your request.

## 7. Class names and class arms are now editable

Arms were **already** editable (the "Edit Class Details" panel on a class page works correctly) — the
gap was the class **level** name (e.g. renaming "JSS 1" itself). Each level on `/classes/levels` now
has an **Edit** button that reveals an inline form for name, section, and order index.

While in there, I also fixed a real bug: `storeLevel` validated a field called `category` that the
"Add Class Level" form never sends — the form actually sends `section` (a required, non-nullable
database column). That means **every "Add Class Level" submission would have failed with a SQL
error**. Fixed.

## 8. Student attendance not saving — fixed

Root cause: `AttendanceController::sheet()` computed `$currentTermId` but never passed it to the
view (missing from the `compact()` call). The hidden `term_id` field on the attendance form always
rendered empty, which failed the "required" validation on save — silently, because the view never
displayed validation errors. Fixed both: the term ID is now passed correctly, and the page now shows
validation errors (and a clear warning if no term is marked "current") instead of failing silently.

## 9. How tax was being calculated (and what it is now)

**Before:** a flat 7.5% of gross pay — a placeholder, not real Nigerian PAYE.

**Now:** a real, configurable, progressive tax engine that follows the **Nigeria Tax Act 2025**
(effective 1 January 2026):
- 0% on the first ₦800,000/year, rising progressively to 25% above ₦50,000,000/year
- Pension (8%) is now calculated on **Basic + Housing + Transport only**, not total gross (this
  changed too — the old code used full gross, which over-deducted pension)
- The old Consolidated Relief Allowance was abolished and replaced by a **rent relief** (20% of
  annual rent paid, capped at ₦500,000) — optional, off by default, configurable per staff member

**Important — please verify before trusting this for real payroll:** independent sources reported
the exact thresholds *between* the 0% and 25% bands inconsistently at the time this was built (some
said the 18% band ran to ₦8M, others ₦10M, ₦12M, or ₦13M — they only agreed on the endpoints). Rather
than hardcode a disputed number into a system that pays real people, **the bands are stored per-tenant
and fully editable** at Payroll → Tax Bands, seeded with the most-corroborated defaults and a visible
warning to confirm them against your accountant or the official FIRS/NRS schedule.

## 10. Peculiar per-staff deductions (loans, cooperative, school fees)

New: **Payroll → Staff Deductions**. You can now assign a deduction template (loan, cooperative,
school fees, etc. — created in Payroll → Templates) to an individual staff member, with an optional
**custom amount** that overrides the template's default. So two staff can share a "Cooperative"
template but owe different sums, or one staff can have both a loan repayment and a school-fees
deduction running simultaneously. These flow automatically into payroll generation and are itemised
on the payslip (PDF and on-screen).

## 11. Gross pay / Net pay showing ₦0.00 — found two separate causes, fixed both

1. **The real one:** the payslip list and payslip PDF were reading `$item->gross_salary` and
   `$item->net_salary` — fields that **don't exist**. The actual database columns are `gross_pay` and
   `net_pay`. Wrong field name → silently null → always ₦0.00, regardless of what the backend
   calculated correctly. Fixed in both `payslip.blade.php` and `payslip-pdf.blade.php` (6 places).
2. **The secondary cause:** if a staff member's salary was never actually filled in at Payroll →
   Salary Settings (basic salary left at 0), payroll generation now **skips them and tells you by
   name** instead of silently creating a ₦0.00 payslip for them.

## 12. Salary profile — configure once, edit anytime

This page (`Payroll → Salary Settings`) already supported this via `updateOrCreate` — each staff
row saves independently and can be re-edited anytime. Two real gaps closed:
- **Account Name** was validated by the controller but had no input on the form — added.
- **Annual Rent** added (optional) — feeds the new rent-relief calculation in #9.

## 13. Payment gateway setup in Super Admin

This was added in the previous delivery (Super Admin → Settings → Payment Gateways, covering both
Paystack and Monnify). If you haven't pulled that zip in yet, it'll appear once you do. Confirmed the
nav link to Settings is in place and reachable.

---

## What's not here yet (and why)

Five items needed either a dedicated design pass or carry real risk if rushed, so I held them back
rather than ship something half-right:

- **#3 Restyle school setup page** — a visual design pass; tell me if you want a specific direction
  (more like the onboarding pages I restyled previously, or something new) and I'll do it next.
- **#4 Clock-in/out for a friend on the main staff attendance page** — needs to be placed without
  disrupting the existing QR/proxy flow; doing it justice means actually testing the page layout.
- **#5 Live photo capture instead of upload for proxy clock-in** — this is a real camera-capture
  feature (getUserMedia + canvas snapshot), and you flagged earlier that camera access fails over
  HTTP on mobile, so it needs a deliberate fallback path, not a quick swap.
- **#6 Health records with treatment/medication history, visible to parents** — this touches student
  medical data and parent-portal access control. It deserves a proper data-model and permissions
  design rather than being squeezed in alongside everything else.
- **#14 Monnify on the school tenant account** — Monnify is live at the **platform** level (paying
  subscription invoices). Adding it as a **tenant-facing** gateway (e.g. for parents paying school
  fees) is a separate integration against a different part of the codebase — happy to scope it next.

Say the word and I'll pick up wherever you want to go next.

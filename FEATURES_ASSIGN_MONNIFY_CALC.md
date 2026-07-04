# EduCore — Level/Arm Assignment, Grades Cleanup, Monnify, CBT Calculator

Four requests from the latest screenshots. Drop the folder over your project root, then run the
migration (a new one is included) and clear caches:

```
php artisan migrate
del /Q storage\framework\views\*.php   &  php artisan route:clear   (Windows)
rm -f storage/framework/views/*.php     ;  php artisan route:clear   (*nix)
```

---

## 1. Assign a subject to a class level *or* a single class arm

Both assign forms in your screenshots were also **failing validation** ("The subject id field is
required" / "The class arm id field is required") because each form omitted the hidden field its page
implies. Fixed, and the requested level/arm option added:

- **`assignSubject`** now takes a `target` of `arm:{id}` (one class) or `level:{id}` (every arm in
  that level). It still accepts a bare `class_arm_id` for backward compatibility, skips arms that
  already have the subject, and reports how many classes were assigned.
- **Subject page** (`/subjects/{id}`): the "Assign to Class" picker now lists **Whole level (all
  arms)** and **Specific class** options, and carries the hidden `subject_id`.
- **Class page** (`/classes/{id}`): the "Assign Subject" form now carries the hidden `class_arm_id`
  and adds a choice — *This class only* vs *All {level} arms*.

> Note: "assign to level" attaches the subject to every arm that currently exists under that level.
> Arms you add later won't inherit it automatically — re-run the level assignment, or assign the new
> arm directly. (A true level-level binding would need a schema change; this reuses the existing
> `class_arm_subjects` pivot with no migration.)

## 2. "Grades: 9" on the class levels page — removed

That stat was `gradingSystems->count()` — the number of grade **bands** (A/B/C…) in the level's
grading scale. It wasn't useful on the class-levels list, so it's gone. "Arms" and "Promotion Rule"
remain.

## 3. Monnify payment gateway

Monnify now sits alongside Paystack as an online option for subscription invoices.

- **Settings** → Super Admin → Settings has a new **Payment Gateways** section exposing both
  Paystack (public/secret/mode) and Monnify (API key, secret, contract code, Sandbox/Live). Saving
  now upserts, so these keys persist even if they didn't exist before. A guarded migration seeds the
  keys so they show up.
- **Flow** (`monnifyPayInitiate` → Monnify checkout → `monnifyPayCallback`): authenticates with
  Monnify, initialises the transaction, redirects the payer to Monnify's hosted checkout, then
  verifies the transaction on return and credits the invoice. Sandbox vs live is driven by the mode
  setting (`sandbox.monnify.com` vs `api.monnify.com`).
- The paid-and-extend logic is now a shared `creditInvoicePayment()` helper used by both the Paystack
  and Monnify callbacks, so the two gateways stay consistent.
- The pay page shows **Pay with Paystack** and/or **Pay with Monnify** — each only when its keys are
  configured.

> Two caveats worth knowing: (1) Monnify's `redirectUrl` must be reachable from Monnify's servers, so
> the sandbox callback won't complete against `127.0.0.1` — test the redirect/verify on a publicly
> reachable host (or a tunnel). (2) This uses the redirect/verify flow; if you want bank-grade
> reliability, add Monnify's webhook as a later enhancement so payments confirm even if the user
> closes the tab before redirect.

## 4. Scientific calculator on the CBT exam board

`cbt/take.blade.php` now has a **🧮 Calculator** button in the exam top bar that opens an in-page,
draggable scientific calculator — digits, + − × ÷ %, parentheses, sin/cos/tan, ln/log, √, x², xʸ, π,
e, n!, Ans, and a DEG/RAD toggle.

It is deliberately **in-page** (no popup, no new tab) because the exam screen auto-submits on focus
loss — a calculator in a separate window would have ended the student's exam. It's button-driven
(no typing) with a whitelisted, guarded evaluator, so it doesn't interfere with the exam's anti-cheat
key handling.

---

## Verification

All PHP (app/database/routes) lints clean; views pass structural checks (balanced tags, no CSS leak,
no `?->` in echoes, no model refs). The sandbox can't run your stack, so please smoke-test:

1. Subject page → assign to a whole level, confirm it lands on every arm; class page → assign to "all
   arms"; confirm the previous validation errors are gone.
2. Class levels page → "Grades" stat gone.
3. Settings → enter Monnify sandbox keys, save, open an invoice → **Pay with Monnify** → checkout →
   on a publicly reachable host, confirm the callback marks the invoice paid and extends the sub.
4. Start a CBT exam → open the calculator, try `sin(30)` in DEG (=0.5), `√(144)` (=12), `5!` (=120).

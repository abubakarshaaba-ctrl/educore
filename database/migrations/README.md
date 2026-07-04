# Enterprise SMS — Laravel 11 Database Migrations
## Run Order & Module Map

Place all files in `database/migrations/` and run:

```bash
php artisan migrate --seed
```

---

## File Order (Dependency-Safe)

| # | File | Tables Created | Depends On |
|---|------|---------------|------------|
| 1 | `000001_create_tenants_table.php` | `tenants` | — |
| 2 | `000002_create_users_table.php` | `users`, `password_reset_tokens`, `sessions` | `tenants` |
| 3 | `000003_create_academic_foundation_tables.php` | `academic_sessions`, `terms`, `class_levels`, `class_arms` | `tenants`, `users` |
| 4 | `000004_create_students_and_guardians_tables.php` | `guardians`, `students`, `guardian_student`, `student_enrollments` | `tenants`, `users`, `class_arms`, `academic_sessions`, `terms` |
| 5 | `000005_create_subjects_and_timetable_tables.php` | `subjects`, `class_arm_subjects`, `timetable_periods` | `tenants`, `class_arms`, `users`, `academic_sessions` |
| 6 | `000006_create_assessment_and_grading_tables.php` | `assessment_types`, `grading_systems`, `promotion_rules`, `scores`, `termly_summaries` | `tenants`, `terms`, `class_levels`, `students`, `subjects`, `academic_sessions`, `class_arms`, `users` |
| 7 | `000007_create_financial_tables.php` | `school_bank_subaccounts`, `fee_categories`, `fee_structures`, `invoices`, `invoice_items`, `payment_transactions`, `invoice_discounts` | `tenants`, `class_levels`, `terms`, `students`, `academic_sessions`, `users` |
| 8 | `000008_create_cbt_attendance_messaging_tables.php` | `cbt_question_banks`, `cbt_questions`, `cbt_exams`, `cbt_student_sessions`, `attendance_records`, `notification_logs` | `tenants`, `subjects`, `class_levels`, `terms`, `class_arms`, `students`, `users`, `guardians` |

---

## Key Design Decisions

### Multi-Tenancy
- Every table carries `tenant_id` indexed as the first column after `id`
- Pair with `TenantScope` global query scope (see `App\Models\Scopes\TenantScope`)
- All soft-deletes use `softDeletes()` on core entities (tenants, users, students)

### Naming Convention Change
- The SADD names one table `sessions` — renamed to **`academic_sessions`** to avoid collision with Laravel's built-in HTTP sessions table

### Financial Integrity
- `balance` on `invoices` is a **stored generated column** (`total_amount - amount_paid`) — no sync bugs
- Split routing data stored in `payment_transactions.split_breakdown` JSON for audit trails

### CBT Resilience
- `cbt_student_sessions.answers` JSON field mirrors the Alpine.js localStorage payload
- `last_synced_at` tracks Redis heartbeat history
- Final submission writes to `score` and `percentage` columns atomically

### Broadsheet Performance
- `termly_summaries.subject_breakdown` JSON snapshot eliminates N+1 queries during PDF generation
- `computed_at` timestamp flags stale caches needing recomputation

---

## Next Steps

| Module | File to Generate Next |
|--------|-----------------------|
| Multi-Tenancy Middleware | `TenantScope.php`, `IdentifyTenant` middleware |
| Eloquent Models | Base model with `TenantScope` boot |
| Fee Payment Service | `FeePaymentSplitService.php` (Paystack API) |
| Promotion Engine | `StudentPromotionEngine.php` (from SADD §5.3) |
| CBT Engine | `CbtExamController.php` + Alpine.js frontend |

# EduCore Mobile Overhaul Progress — 11 September 2026

This file tracks the remaining native Android overhaul work in execution order.

## Current priority
1. Staff Attendance functional blockers
2. Role/module visibility cleanup
3. Finance/payslip refinement
4. Communication/notices validation
5. UI/branding cleanup
6. Subscription grace countdown enhancement
7. Full build/test/release validation

## Staff Attendance acceptance criteria
- Authorized admins/heads can access Staff Attendance administration.
- My Attendance remains a separate personal workflow.
- QR code loads and reset returns explicit success/error feedback.
- Present-location action reports loading/success/failure states.
- Save Attendance is enabled only when required inputs are valid and returns explicit success/failure feedback.
- Offline attendance can be queued locally and synchronized later without duplicate submission.
- Proxy clock-in is restricted, auditable, and visually distinguished from self clock-in.

## Role/module cleanup acceptance criteria
- CBT is removed from mobile navigation and inaccessible through deep links.
- Report Cards/Results are removed from mobile navigation and inaccessible through deep links.
- Accountant does not see Classes or unrelated academic modules.
- Non-academic staff do not see academic modules unless explicitly permitted.
- UI visibility and API authorization remain aligned.

<?php
namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class BillingController extends Controller
{
    public function index()
    {
        $user   = auth()->user();
        if (!$user->isAdmin() && !$user->isSuperAdmin()) abort(403);

        $tenant = $user->tenant;

        $invoices = DB::table('platform_invoices')
            ->leftJoin('subscription_plans','subscription_plans.id','=','platform_invoices.plan_id')
            ->select('platform_invoices.*','subscription_plans.name as plan_name')
            ->where('platform_invoices.tenant_id', $tenant->id)
            ->orderByDesc('platform_invoices.created_at')
            ->get();

        $totalPaid = DB::table('platform_invoices')
            ->where('tenant_id', $tenant->id)
            ->where('status', 'paid')
            ->sum('amount');

        $gatewayConfigured = (bool) DB::table('platform_settings')
            ->where('key', 'paystack_public_key')
            ->whereNotNull('value')
            ->where('value', '!=', '')
            ->exists();

        $hasOutstandingInvoice = $invoices->contains(fn ($inv) => $inv->status !== 'paid');

        $plans = DB::table('subscription_plans')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map(function ($p) {
                $raw = $p->features ? (json_decode($p->features, true) ?: []) : [];

                // Map feature keys to human-readable labels (new format).
                // If a value is already a label string (old format), pass it through.
                $featureLabels = [
                    'dashboard'=>'Admin Dashboard','students'=>'Student Management',
                    'student_transfer'=>'Student Transfers','student_archive'=>'Student Archive',
                    'staff'=>'Staff Management','staff_archive'=>'Staff Archive',
                    'classes'=>'Classes & Arms','subjects'=>'Subjects',
                    'curriculum'=>'Curriculum / Lesson Plans','academic_cycle'=>'Academic Sessions & Terms',
                    'promotion'=>'Promotion Engine','school_setup'=>'School Setup & Branding',
                    'timetable'=>'Timetable','scores'=>'Score Entry',
                    'report_cards'=>'Report Cards','broadsheet'=>'Broadsheet',
                    'skill_ratings'=>'Skill / Psychomotor Ratings','gradebook'=>'Gradebook',
                    'assessment_types'=>'Assessment Types Config',
                    'student_attendance'=>'Student Attendance','staff_attendance'=>'Staff Attendance',
                    'staff_id_cards'=>'Staff ID Cards & QR Clock-in',
                    'cbt'=>'CBT Exam Engine','cbt_essay'=>'CBT Short Answer / Essay',
                    'cbt_results'=>'CBT Results & Analytics',
                    'fees'=>'Fee Setup & Categories','invoices'=>'Invoices & Payments',
                    'payment_plans'=>'Payment Plans','fee_reminders'=>'Fee Reminders',
                    'online_payments'=>'Online Payments (Paystack/Monnify)',
                    'expenses'=>'Expenses','payroll'=>'Staff Payroll & PAYE',
                    'financial_report'=>'Financial Reports',
                    'messages'=>'Internal Messaging','sms'=>'SMS Notifications',
                    'notifications'=>'System Notifications','announcements'=>'Announcements',
                    'auto_triggers'=>'Auto Triggers',
                    'parent_portal'=>'Parent Portal','student_portal'=>'Student Portal',
                    'library'=>'Library','transport'=>'Transport / Fleet',
                    'health_records'=>'Health Records','calendar'=>'Calendar',
                    'risk_flags'=>'Academic Risk Flags','analytics'=>'Analytics & Reporting',
                    'export_data'=>'Data Export',
                ];

                $p->feature_list = array_map(
                    fn ($k) => $featureLabels[$k] ?? ucwords(str_replace('_', ' ', $k)),
                    $raw
                );
                return $p;
            });

        $currentPlanId = $this->currentPlanId($tenant);

        return view('billing.self-service', compact('tenant', 'invoices', 'totalPaid', 'gatewayConfigured', 'hasOutstandingInvoice', 'plans', 'currentPlanId'));
    }

    /**
     * Self-service: a school admin picks a plan + cycle, which raises a pending platform
     * invoice for their own tenant and sends them straight to the payment page. Reuses any
     * existing unpaid invoice for the same plan+cycle so repeated clicks don't stack duplicates.
     */
    public function selectPlan(\Illuminate\Http\Request $request)
    {
        $user = auth()->user();
        if (!$user->isAdmin() && !$user->isSuperAdmin()) abort(403);

        $tenant = $user->tenant;

        $data = $request->validate([
            'plan_id'       => ['required', 'integer', 'exists:subscription_plans,id'],
            'billing_cycle' => ['required', 'in:monthly,annual'],
        ]);

        $plan = DB::table('subscription_plans')
            ->where('id', $data['plan_id'])
            ->where('is_active', true)
            ->first();

        if (!$plan) {
            return back()->withErrors(['plan' => 'That plan is no longer available.']);
        }

        $existing = DB::table('platform_invoices')
            ->where('tenant_id', $tenant->id)
            ->where('plan_id', $plan->id)
            ->where('billing_cycle', $data['billing_cycle'])
            ->where('status', 'pending')
            ->first();

        if ($existing) {
            return redirect()->route('super.billing.pay', $existing->id);
        }

        $amount = $data['billing_cycle'] === 'annual' ? $plan->annual_price : $plan->monthly_price;
        $ref    = 'INV-' . strtoupper(\Illuminate\Support\Str::random(8));

        $invoiceId = DB::table('platform_invoices')->insertGetId([
            'tenant_id'      => $tenant->id,
            'plan_id'        => $plan->id,
            'invoice_number' => $ref,
            'amount'         => $amount,
            'billing_cycle'  => $data['billing_cycle'],
            'status'         => 'pending',
            'due_date'       => now()->addDays(7)->toDateString(),
            'notes'          => 'Self-service plan selection',
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        return redirect()->route('super.billing.pay', $invoiceId)
            ->with('success', "Invoice {$ref} created for the {$plan->name} plan — complete payment to activate.");
    }

    private function currentPlanId($tenant): ?int
    {
        // Most-recently inserted active subscription with a plan wins.
        // We order by id DESC (not expires_at) so a new paid subscription
        // always beats an old admin-created subscription that had no plan.
        $latestSubscriptionPlanId = DB::table('tenant_subscriptions')
            ->where('tenant_id', $tenant->id)
            ->where('status', 'active')
            ->whereNotNull('plan_id')
            ->orderByDesc('id')
            ->value('plan_id');

        if ($latestSubscriptionPlanId) {
            return (int) $latestSubscriptionPlanId;
        }

        // Fall back to the latest paid invoice that has a plan_id.
        $latestInvoicePlanId = DB::table('platform_invoices')
            ->where('tenant_id', $tenant->id)
            ->where('status', 'paid')
            ->whereNotNull('plan_id')
            ->orderByDesc('paid_at')
            ->orderByDesc('id')
            ->value('plan_id');

        return $latestInvoicePlanId ? (int) $latestInvoicePlanId : null;
    }
}

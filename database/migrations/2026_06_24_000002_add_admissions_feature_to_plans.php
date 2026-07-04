<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('subscription_plans')) return;

        // Add 'admissions' to Standard and Premium plans (not Basic / Free Trial)
        $plansToUpgrade = DB::table('subscription_plans')
            ->whereIn('slug', ['standard', 'premium'])
            ->get();

        foreach ($plansToUpgrade as $plan) {
            $features = $plan->features
                ? (json_decode($plan->features, true) ?: [])
                : [];

            if (!in_array('admissions', $features)) {
                $features[] = 'admissions';
                DB::table('subscription_plans')
                    ->where('id', $plan->id)
                    ->update(['features' => json_encode(array_values($features)), 'updated_at' => now()]);
            }
        }

        // Ensure Basic plan does NOT have staff_attendance, staff_id_cards, payroll,
        // financial_report, sms, auto_triggers, push_notifications, analytics, or risk_flags
        $basic = DB::table('subscription_plans')->where('slug', 'basic')->first();
        if ($basic) {
            $features = $basic->features
                ? (json_decode($basic->features, true) ?: [])
                : [];

            $premiumOnly = [
                'staff_attendance', 'staff_id_cards', 'payroll', 'financial_report',
                'sms', 'auto_triggers', 'push_notifications', 'analytics', 'risk_flags',
                'transport', 'health_records', 'library', 'export_data',
            ];
            $features = array_values(array_filter($features, fn ($f) => !in_array($f, $premiumOnly)));

            DB::table('subscription_plans')
                ->where('id', $basic->id)
                ->update(['features' => json_encode($features), 'updated_at' => now()]);
        }
    }

    public function down(): void {}
};

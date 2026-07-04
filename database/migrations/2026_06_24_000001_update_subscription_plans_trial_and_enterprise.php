<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('subscription_plans')) return;

        // ── 1. Remove staff_attendance from the Basic plan ────────────
        $basic = DB::table('subscription_plans')
            ->where('slug', 'basic')
            ->first();

        if ($basic) {
            $features = $basic->features
                ? (json_decode($basic->features, true) ?: [])
                : [];

            // Remove staff_attendance and related features from Basic
            $remove = ['staff_attendance', 'staff_id_cards', 'payroll', 'financial_report'];
            $features = array_values(array_filter($features, fn ($f) => !in_array($f, $remove)));

            DB::table('subscription_plans')
                ->where('id', $basic->id)
                ->update([
                    'features'   => json_encode($features),
                    'updated_at' => now(),
                ]);
        }

        // ── 2. Deactivate Enterprise plan ─────────────────────────────
        DB::table('subscription_plans')
            ->where('slug', 'enterprise')
            ->update(['is_active' => false, 'updated_at' => now()]);

        // ── 3. Create Free Trial plan if it doesn't exist ─────────────
        $exists = DB::table('subscription_plans')
            ->where('slug', 'free-trial')
            ->exists();

        if (!$exists) {
            $trialFeatures = [
                'dashboard', 'students', 'classes', 'subjects', 'academic_cycle',
                'student_attendance', 'scores', 'report_cards', 'broadsheet',
                'skill_ratings', 'timetable', 'fees', 'calendar', 'profile',
            ];

            DB::table('subscription_plans')->insert([
                'name'          => 'Free Trial',
                'slug'          => 'free-trial',
                'description'   => '30-day trial — core modules, up to 50 students',
                'monthly_price' => 0,
                'annual_price'  => 0,
                'max_students'  => 50,
                'max_staff'     => 10,
                'has_cbt'       => false,
                'has_sms'       => false,
                'has_paystack'  => false,
                'features'      => json_encode($trialFeatures),
                'is_active'     => true,
                'sort_order'    => 0,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);
        }
    }

    public function down(): void {}
};

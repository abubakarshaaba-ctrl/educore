<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('platform_settings') || !Schema::hasColumn('platform_settings', 'key')) {
            return;
        }

        $settings = [
            'platform_name' => ['EduCore', 'string', 'general', 'Platform Name'],
            'support_email' => ['support@educoreng.online', 'string', 'contact', 'Support Email'],
            'support_phone' => ['07065595768', 'string', 'contact', 'Support Phone'],
            'support_whatsapp' => ['+2347065595768', 'string', 'contact', 'Support WhatsApp'],
            'support_website' => ['https://educoreng.online', 'string', 'contact', 'Support Website'],
            'office_address' => ['Abuja, FCT, Nigeria', 'string', 'contact', 'Office Address'],
            'trial_days' => ['30', 'integer', 'billing', 'Initial Subscription Window'],
            'grace_period_days' => ['7', 'integer', 'billing', 'Grace Period'],
            'default_sms_gateway' => ['termii', 'string', 'notifications', 'Default SMS Gateway'],
            'sms_sender_id' => ['EduCore', 'string', 'notifications', 'SMS Sender ID'],
            'maintenance_mode' => ['0', 'boolean', 'system', 'Maintenance Mode'],
        ];

        foreach ($settings as $key => [$value, $type, $group, $label]) {
            $payload = [
                'type' => $type,
                'group' => $group,
                'label' => $label,
                'updated_at' => now(),
            ];

            $existing = DB::table('platform_settings')->where('key', $key)->first();

            if ($existing) {
                // Preserve intentional live configuration. Only replace values
                // that are known remnants of the pre-EduCore platform.
                $isLegacyValue = match ($key) {
                    'platform_name' => blank($existing->value)
                        || str_contains(strtolower((string) $existing->value), 'enterprise sms'),
                    'support_email' => blank($existing->value)
                        || str_contains(strtolower((string) $existing->value), 'enterprisesms'),
                    'trial_days' => ($existing->label ?? null) === 'Free Trial Days',
                    default => false,
                };

                DB::table('platform_settings')->where('key', $key)->update([
                    ...$payload,
                    ...($isLegacyValue ? ['value' => $value] : []),
                ]);
            } else {
                DB::table('platform_settings')->insert([
                    'key' => $key,
                    'value' => $value,
                    ...$payload,
                    'created_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        // Operational settings are retained deliberately. Rolling application
        // code back must not erase support contacts or availability controls.
    }
};

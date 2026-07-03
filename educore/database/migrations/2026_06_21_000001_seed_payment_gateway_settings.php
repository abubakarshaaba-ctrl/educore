<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ensure the payment-gateway settings keys exist so they appear in Super Admin
     * Settings and can be read by the pay flow. Guarded + idempotent: only inserts
     * keys that are missing, so it's safe on databases that already have some of them.
     */
    public function up(): void
    {
        if (!Schema::hasTable('platform_settings')) {
            return;
        }

        $keys = [
            ['key' => 'paystack_public_key',   'label' => 'Paystack Public Key',   'value' => null],
            ['key' => 'paystack_secret_key',   'label' => 'Paystack Secret Key',   'value' => null],
            ['key' => 'paystack_is_live',      'label' => 'Paystack Live Mode',     'value' => '0'],
            ['key' => 'monnify_api_key',       'label' => 'Monnify API Key',        'value' => null],
            ['key' => 'monnify_secret_key',    'label' => 'Monnify Secret Key',     'value' => null],
            ['key' => 'monnify_contract_code', 'label' => 'Monnify Contract Code',  'value' => null],
            ['key' => 'monnify_is_live',       'label' => 'Monnify Live Mode',      'value' => '0'],
        ];

        foreach ($keys as $k) {
            if (DB::table('platform_settings')->where('key', $k['key'])->exists()) {
                continue;
            }
            DB::table('platform_settings')->insert([
                'key'        => $k['key'],
                'value'      => $k['value'],
                'type'       => 'string',
                'group'      => 'payment',
                'label'      => $k['label'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Non-destructive: leave settings in place on rollback.
    }
};

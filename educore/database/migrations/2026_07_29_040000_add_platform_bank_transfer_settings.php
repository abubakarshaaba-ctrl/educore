<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('platform_settings') || !Schema::hasColumn('platform_settings', 'key')) {
            return;
        }

        $now = now();
        foreach ([
            'bank_transfer_bank_name' => 'Bank Transfer Bank Name',
            'bank_transfer_account_name' => 'Bank Transfer Account Name',
            'bank_transfer_account_number' => 'Bank Transfer Account Number',
        ] as $key => $label) {
            DB::table('platform_settings')->updateOrInsert(
                ['key' => $key],
                [
                    'value' => DB::table('platform_settings')->where('key', $key)->value('value'),
                    'type' => 'string',
                    'group' => 'payments',
                    'label' => $label,
                    'updated_at' => $now,
                    'created_at' => DB::table('platform_settings')->where('key', $key)->value('created_at') ?? $now,
                ]
            );
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('platform_settings') && Schema::hasColumn('platform_settings', 'key')) {
            DB::table('platform_settings')->whereIn('key', [
                'bank_transfer_bank_name',
                'bank_transfer_account_name',
                'bank_transfer_account_number',
            ])->delete();
        }
    }
};

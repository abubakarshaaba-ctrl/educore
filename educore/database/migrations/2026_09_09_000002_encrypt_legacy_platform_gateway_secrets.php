<?php

use App\Models\PlatformSetting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('platform_settings')) {
            return;
        }

        $hasType = Schema::hasColumn('platform_settings', 'type');
        $hasGroup = Schema::hasColumn('platform_settings', 'group');
        $hasLabel = Schema::hasColumn('platform_settings', 'label');

        foreach (PlatformSetting::SECRET_KEYS as $key) {
            $row = DB::table('platform_settings')->where('key', $key)->first();
            if (!$row) {
                continue;
            }

            $type = $hasType ? strtolower((string) ($row->type ?? 'string')) : 'string';
            if ($type === 'encrypted') {
                continue;
            }

            $plain = (string) ($row->value ?? '');
            $updates = [
                'value' => $plain === '' ? null : Crypt::encryptString($plain),
                'updated_at' => now(),
            ];
            if ($hasType) {
                $updates['type'] = 'encrypted';
            }
            if ($hasGroup && blank($row->group ?? null)) {
                $updates['group'] = 'payments';
            }
            if ($hasLabel && blank($row->label ?? null)) {
                $updates['label'] = str($key)->headline()->toString();
            }

            DB::table('platform_settings')->where('key', $key)->update($updates);
        }
    }

    public function down(): void
    {
        // Intentionally irreversible. Decrypting secrets back to plaintext would
        // weaken the platform security posture and may expose credentials.
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Contracts\Encryption\DecryptException;

return new class extends Migration
{
    public function up(): void
    {
        $this->encryptColumns('payment_gateway_configs', ['secret_key']);
        $this->encryptColumns('staff_salary_settings', [
            'account_number',
            'tax_identification_number',
            'bvn',
            'nin',
        ]);

        if (Schema::hasTable('platform_settings') && Schema::hasColumn('platform_settings', 'key')) {
            DB::table('platform_settings')
                ->whereIn('key', ['paystack_secret_key', 'flutterwave_secret_key', 'monnify_secret_key'])
                ->orderBy('id')
                ->get()
                ->each(function ($row) {
                    if ($row->value === null || $row->value === '' || $this->isEncrypted($row->value)) {
                        return;
                    }

                    DB::table('platform_settings')
                        ->where('id', $row->id)
                        ->update([
                            'value' => Crypt::encryptString($row->value),
                            'type' => 'encrypted',
                            'updated_at' => now(),
                        ]);
                });
        }
    }

    public function down(): void
    {
        // Encryption is intentionally not reversed.
    }

    private function encryptColumns(string $table, array $columns): void
    {
        if (!Schema::hasTable($table)) {
            return;
        }

        $columns = array_values(array_filter($columns, fn (string $column) => Schema::hasColumn($table, $column)));
        if ($columns === []) {
            return;
        }

        DB::table($table)
            ->orderBy('id')
            ->get()
            ->each(function ($row) use ($table, $columns) {
                $updates = [];

                foreach ($columns as $column) {
                    $value = $row->{$column} ?? null;
                    if ($value === null || $value === '' || $this->isEncrypted($value)) {
                        continue;
                    }

                    $updates[$column] = Crypt::encryptString($value);
                }

                if ($updates !== []) {
                    $updates['updated_at'] = now();
                    DB::table($table)->where('id', $row->id)->update($updates);
                }
            });
    }

    private function isEncrypted(string $value): bool
    {
        try {
            Crypt::decryptString($value);
            return true;
        } catch (DecryptException) {
            return false;
        }
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // Re-encrypt any plaintext secret_key values that were stored before
    // the 'encrypted' cast was added to PaymentGatewayConfig.
    public function up(): void
    {
        DB::table('payment_gateway_configs')->orderBy('id')->each(function ($row) {
            if (!$row->secret_key) return;

            // Detect if already encrypted (Laravel encryption payloads are base64-encoded JSON)
            try {
                Crypt::decryptString($row->secret_key);
                return; // Already encrypted — skip
            } catch (\Illuminate\Contracts\Encryption\DecryptException) {
                // Plaintext — encrypt it now
            }

            DB::table('payment_gateway_configs')
                ->where('id', $row->id)
                ->update(['secret_key' => Crypt::encryptString($row->secret_key)]);
        });
    }

    public function down(): void
    {
        // Decrypt back to plaintext (for rollback only — never do this in production)
        DB::table('payment_gateway_configs')->orderBy('id')->each(function ($row) {
            if (!$row->secret_key) return;
            try {
                $plain = Crypt::decryptString($row->secret_key);
                DB::table('payment_gateway_configs')
                    ->where('id', $row->id)
                    ->update(['secret_key' => $plain]);
            } catch (\Throwable) {
                // Already plaintext
            }
        });
    }
};

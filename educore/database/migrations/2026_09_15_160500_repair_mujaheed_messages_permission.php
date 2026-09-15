<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasTable('staff_permissions')) {
            return;
        }

        $users = User::query()
            ->whereRaw('LOWER(email) = ?', ['mujaheed@gmail.com'])
            ->get();

        foreach ($users as $user) {
            $allowed = User::ROLE_ACCESS[$user->roleKey()] ?? [];
            $roleAllowsMessages = in_array('*', $allowed, true) || in_array('messages', $allowed, true);

            if (! $roleAllowsMessages) {
                continue;
            }

            DB::table('staff_permissions')
                ->where('user_id', $user->id)
                ->where('module', 'messages')
                ->where('type', 'deny')
                ->delete();
        }
    }

    public function down(): void
    {
        // Intentional no-op: this migration removes an erroneous account-specific deny.
    }
};

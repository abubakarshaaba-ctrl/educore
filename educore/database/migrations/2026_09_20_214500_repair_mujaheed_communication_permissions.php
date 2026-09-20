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

            $roleAllowsMessages = in_array('*', $allowed, true)
                || in_array('messages', $allowed, true);

            $roleAllowsNotifications = in_array('*', $allowed, true)
                || in_array('notifications', $allowed, true)
                || in_array('notifications.view', $allowed, true)
                || in_array('notifications.send', $allowed, true);

            $roleAllowsCalendar = in_array('*', $allowed, true)
                || in_array('calendar', $allowed, true)
                || in_array('calendar.view', $allowed, true);

            $modules = collect([
                $roleAllowsMessages ? 'messages' : null,
                ...($roleAllowsNotifications ? ['notifications', 'notifications.view'] : []),
                ...($roleAllowsCalendar ? ['calendar', 'calendar.view'] : []),
            ])->filter()->values()->all();

            if ($modules === []) {
                continue;
            }

            DB::table('staff_permissions')
                ->where('user_id', $user->id)
                ->where('type', 'deny')
                ->whereIn('module', $modules)
                ->delete();

            // User may already have loaded customPermissions earlier in the
            // request/process. Clear it so subsequent access checks re-read
            // the repaired permission state.
            $user->unsetRelation('customPermissions');
        }
    }

    public function down(): void
    {
        // Intentional no-op: this only removes erroneous account-specific
        // deny overrides and must not recreate them on rollback.
    }
};

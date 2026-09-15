<?php

namespace App\Observers;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class UserEmailSecurityObserver
{
    public function saving(User $user): void
    {
        $email = Str::lower(trim((string) $user->email));
        if ($email === '') {
            return;
        }

        $user->email = $email;

        $conflict = User::withTrashed()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->when($user->exists, fn ($query) => $query->whereKeyNot($user->getKey()))
            ->whereNull('deleted_at')
            ->where('is_active', true)
            ->exists();

        if ($conflict) {
            throw ValidationException::withMessages([
                'email' => 'This email address is already attached to an active EduCore account and cannot be used for another tenant or user account.',
            ]);
        }
    }
}

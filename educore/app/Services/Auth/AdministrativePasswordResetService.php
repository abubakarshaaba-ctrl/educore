<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AdministrativePasswordResetService
{
    public function __construct(
        private readonly AccountSessionRevoker $sessions,
        private readonly AuthAuditLogger $audit,
    ) {
    }

    public function reset(User $target, User $actor, string $reason, Request $request): array
    {
        $temporaryPassword = $this->generateTemporaryPassword();

        return DB::transaction(function () use ($target, $actor, $reason, $request, $temporaryPassword) {
            $target->forceFill([
                'password' => $temporaryPassword,
                'must_change_password' => true,
                'password_reset_at' => now(),
                'password_reset_by' => $actor->id,
                'remember_token' => Str::random(60),
            ])->save();

            $revoked = $this->sessions->revoke($target);

            $this->audit->recordForUser(
                $target,
                'auth.password_reset.administrative',
                [
                    'forced_change' => true,
                    'actor_role' => $actor->roleKey(),
                    'api_tokens_revoked' => $revoked['api_tokens_revoked'],
                    'web_sessions_revoked' => $revoked['sessions_revoked'],
                ],
                $request,
                $reason,
                $actor,
            );

            return [
                'temporary_password' => $temporaryPassword,
                'api_tokens_revoked' => $revoked['api_tokens_revoked'],
                'sessions_revoked' => $revoked['sessions_revoked'],
            ];
        });
    }

    private function generateTemporaryPassword(int $length = 16): string
    {
        $pools = [
            'ABCDEFGHJKLMNPQRSTUVWXYZ',
            'abcdefghijkmnopqrstuvwxyz',
            '23456789',
            '!@#$%*+-_=',
        ];

        $characters = [];
        foreach ($pools as $pool) {
            $characters[] = $pool[random_int(0, strlen($pool) - 1)];
        }

        $all = implode('', $pools);
        while (count($characters) < $length) {
            $characters[] = $all[random_int(0, strlen($all) - 1)];
        }

        for ($i = count($characters) - 1; $i > 0; $i--) {
            $j = random_int(0, $i);
            [$characters[$i], $characters[$j]] = [$characters[$j], $characters[$i]];
        }

        return implode('', $characters);
    }
}

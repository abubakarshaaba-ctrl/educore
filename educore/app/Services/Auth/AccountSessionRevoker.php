<?php

namespace App\Services\Auth;

use App\Models\ApiToken;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AccountSessionRevoker
{
    public function revoke(
        User $user,
        ?string $exceptWebSessionId = null,
        ?int $exceptApiTokenId = null
    ): array {
        $apiTokensRevoked = 0;
        if (Schema::hasTable('api_tokens')) {
            $apiQuery = ApiToken::query()->where('user_id', $user->id);
            if ($exceptApiTokenId !== null) {
                $apiQuery->whereKeyNot($exceptApiTokenId);
            }
            $apiTokensRevoked = $apiQuery->delete();
        }

        $webSessionsRevoked = 0;
        if (Schema::hasTable('sessions') && Schema::hasColumn('sessions', 'user_id')) {
            $sessionQuery = DB::table('sessions')->where('user_id', $user->id);
            if ($exceptWebSessionId !== null && $exceptWebSessionId !== '') {
                $sessionQuery->where('id', '!=', $exceptWebSessionId);
            }
            $webSessionsRevoked = $sessionQuery->delete();
        }

        return [
            'api_tokens_revoked' => (int) $apiTokensRevoked,
            'sessions_revoked' => (int) $webSessionsRevoked,
        ];
    }
}

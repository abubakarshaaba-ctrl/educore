<?php

namespace App\Services\Mobile;

use App\Models\MobileIdempotencyKey;
use App\Models\User;
use Closure;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;

class MobileIdempotencyService
{
    public function execute(User $user, string $scope, string $requestId, array $payload, Closure $callback): array
    {
        $hash = hash('sha256', json_encode($this->canonicalize($payload), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        $existing = MobileIdempotencyKey::where('user_id', $user->id)->where('scope', $scope)->where('request_id', $requestId)->first();
        if ($existing) {
            return $this->replay($existing, $hash);
        }

        try {
            $receipt = MobileIdempotencyKey::create([
                'tenant_id' => $user->tenant_id,
                'user_id' => $user->id,
                'scope' => $scope,
                'request_id' => $requestId,
                'request_hash' => $hash,
                'status' => 'processing',
            ]);
        } catch (QueryException) {
            $receipt = MobileIdempotencyKey::where('user_id', $user->id)->where('scope', $scope)->where('request_id', $requestId)->firstOrFail();

            return $this->replay($receipt, $hash);
        }

        try {
            $response = $callback();
            $receipt->update(['status' => 'completed', 'response_json' => json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)]);

            return $response;
        } catch (\Throwable $exception) {
            $receipt->delete();
            throw $exception;
        }
    }

    private function replay(MobileIdempotencyKey $receipt, string $hash): array
    {
        if (! hash_equals($receipt->request_hash, $hash)) {
            throw ValidationException::withMessages(['request_id' => 'This request ID was already used with different data.']);
        }
        abort_unless($receipt->status === 'completed' && $receipt->response_json, 409, 'This request is already being processed.');

        return json_decode($receipt->response_json, true, flags: JSON_THROW_ON_ERROR);
    }

    private function canonicalize(array $payload): array
    {
        ksort($payload);
        foreach ($payload as &$value) {
            if (is_array($value)) {
                $value = $this->canonicalize($value);
            }
        }

        return $payload;
    }
}

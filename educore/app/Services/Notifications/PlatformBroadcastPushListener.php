<?php

namespace App\Services\Notifications;

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Bridges the legacy raw platform_broadcasts insert used by SuperAdminController
 * into the normal FCM delivery pipeline without changing the broadcast storage
 * contract. The listener is registered only for HTTP requests.
 */
class PlatformBroadcastPushListener
{
    public function __construct(private readonly PushNotificationService $push) {}

    public function __invoke(QueryExecuted $query): void
    {
        if (! request()->routeIs('super.broadcasts.store')) {
            return;
        }

        if (! preg_match('/^\s*insert\s+into\s+[`"\[]?platform_broadcasts[`"\]]?\s*\(([^)]+)\)/i', $query->sql, $match)) {
            return;
        }

        try {
            $columns = array_map(
                static fn (string $column): string => trim($column, " \t\n\r\0\x0B`\"[]"),
                explode(',', $match[1]),
            );

            if (count($columns) > count($query->bindings)) {
                return;
            }

            $values = array_combine($columns, array_slice($query->bindings, 0, count($columns)));
            if (! is_array($values) || ! isset($values['title'], $values['body'], $values['target'])) {
                return;
            }

            $broadcast = DB::table('platform_broadcasts')
                ->where('title', (string) $values['title'])
                ->where('body', (string) $values['body'])
                ->where('target', (string) $values['target'])
                ->when(
                    array_key_exists('created_by', $values) && $values['created_by'] !== null,
                    fn ($builder) => $builder->where('created_by', $values['created_by']),
                )
                ->orderByDesc('id')
                ->first();

            if (! $broadcast) {
                return;
            }

            $this->push->notifyPlatformBroadcast(
                (int) $broadcast->id,
                (string) $broadcast->title,
                (string) $broadcast->body,
                (string) $broadcast->target,
            );
        } catch (\Throwable $exception) {
            // Persisting the broadcast must not fail merely because push
            // delivery is temporarily unavailable.
            Log::warning('Platform broadcast push delivery failed.', [
                'error' => $exception->getMessage(),
            ]);
        }
    }
}

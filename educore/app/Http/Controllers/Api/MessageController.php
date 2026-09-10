<?php

namespace App\Http\Controllers\Api;

use App\Models\MessageThread;
use App\Models\MessageThreadReply;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Compatibility facade for installed Android builds.
 *
 * The native client historically called /messages and represented recipients
 * with a numeric student_id. The authoritative school communication model is
 * now handled by SchoolCommunicationApiController. This facade translates the
 * old wire format to purpose-specific school targets without restoring the old
 * student-directory coupling.
 */
class MessageController extends SchoolCommunicationApiController
{
    private const SCHOOL_ADMIN = -1;
    private const ALL_STAFF = -2;
    private const ALL_PARENTS = -3;
    private const PARENT_OFFSET = 1000000000;

    public function recipients(Request $request)
    {
        $response = parent::recipients($request);
        $payload = $response->getData(true);

        $recipients = collect($payload['recipients'] ?? [])->map(function (array $recipient): array {
            $target = (string) ($recipient['target'] ?? '');
            $supporting = (string) ($recipient['supportingText'] ?? 'School communication');

            return [
                'student_id' => $this->encodeTarget($target),
                'name' => (string) ($recipient['name'] ?? 'Recipient'),
                'admission_number' => $supporting,
                'class_name' => null,
                'target' => $target,
                'type' => $recipient['type'] ?? null,
            ];
        })->values();

        return response()->json([
            'contract_version' => 3,
            'recipients' => $recipients,
        ]);
    }

    public function store(Request $request)
    {
        if (! $request->filled('target') && $request->filled('student_id')) {
            $request->merge([
                'target' => $this->decodeTarget((int) $request->input('student_id')),
            ]);
        }

        return parent::store($request);
    }

    public function reply(Request $request, MessageThread $thread)
    {
        $response = parent::reply($request, $thread);
        $payload = $response->getData(true);

        // Older app builds require a top-level reply object. When a broadcast
        // response is correctly redirected to a private admin thread, expose
        // that private thread's first reply as the mutation result as well.
        if (empty($payload['reply']) && ! empty($payload['thread']['replies'])) {
            $payload['reply'] = collect($payload['thread']['replies'])->last();
        }

        return response()->json($payload, $response->getStatusCode());
    }

    public function attachment(Request $request, MessageThreadReply $reply)
    {
        $thread = $reply->thread()->firstOrFail();
        abort_unless((int) $thread->tenant_id === (int) $request->user()?->tenant_id, 404);
        abort_unless($reply->attachment_path && Storage::disk('local')->exists($reply->attachment_path), 404);

        return Storage::disk('local')->download(
            $reply->attachment_path,
            $reply->attachment_name ?: 'attachment',
            ['Content-Type' => $reply->attachment_mime ?: 'application/octet-stream'],
        );
    }

    private function encodeTarget(string $target): int
    {
        return match (true) {
            $target === 'school_admin' => self::SCHOOL_ADMIN,
            $target === 'all_staff' => self::ALL_STAFF,
            $target === 'all_parents' => self::ALL_PARENTS,
            str_starts_with($target, 'staff:') => (int) substr($target, 6),
            str_starts_with($target, 'parent:') => -(self::PARENT_OFFSET + (int) substr($target, 7)),
            default => 0,
        };
    }

    private function decodeTarget(int $clientId): string
    {
        return match (true) {
            $clientId === self::SCHOOL_ADMIN => 'school_admin',
            $clientId === self::ALL_STAFF => 'all_staff',
            $clientId === self::ALL_PARENTS => 'all_parents',
            $clientId > 0 => 'staff:'.$clientId,
            $clientId <= -self::PARENT_OFFSET => 'parent:'.(-$clientId - self::PARENT_OFFSET),
            default => '',
        };
    }
}

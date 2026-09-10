<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\CalendarEvent;
use App\Services\Mobile\MobileCommunicationService;
use App\Services\Notifications\PushNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MobileCommunicationController extends Controller
{
    public function __construct(private readonly MobileCommunicationService $communications) {}

    public function notifications(Request $request)
    {
        $data = $request->validate([
            'status' => ['nullable', 'in:all,read,unread'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        return response()->json($this->communications->notifications(
            $request->user(),
            $data['status'] ?? 'all',
            (int) ($data['per_page'] ?? 20),
        ));
    }

    public function read(Request $request, Announcement $announcement)
    {
        return response()->json(['notification' => $this->communications->markRead($request->user(), $announcement)]);
    }

    public function readAll(Request $request)
    {
        return response()->json([
            'message' => 'Notifications marked as read.',
            'updated' => $this->communications->markAllRead($request->user()),
        ]);
    }

    public function events(Request $request)
    {
        $data = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        return response()->json($this->communications->events($request->user(), $data['from'] ?? null, $data['to'] ?? null));
    }

    public function storeEvent(Request $request)
    {
        $user = $request->user();
        abort_unless($user && $user->tenant_id && $user->canManage('calendar'), 403, 'Calendar management permission required.');

        $data = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:5000'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'type' => ['nullable', 'string', 'max:50'],
            'color' => ['nullable', 'string', 'max:32'],
            'audience' => ['required', 'in:all,staff,parents'],
        ]);

        [$event, $notice] = DB::transaction(function () use ($user, $data): array {
            $event = CalendarEvent::create([
                'tenant_id' => $user->tenant_id,
                'session_id' => null,
                'title' => trim($data['title']),
                'description' => isset($data['description']) ? trim($data['description']) : null,
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'] ?? null,
                'type' => $data['type'] ?? 'event',
                'color' => $data['color'] ?? '#2563EB',
                'is_public' => true,
                'created_by' => $user->id,
            ]);

            $body = collect([
                $event->description,
                'Date: '.$event->start_date->format('d M Y'),
                $event->end_date && ! $event->end_date->isSameDay($event->start_date)
                    ? 'Ends: '.$event->end_date->format('d M Y')
                    : null,
            ])->filter()->implode("\n");

            $notice = Announcement::create([
                'tenant_id' => $user->tenant_id,
                'title' => $event->title,
                'body' => $body,
                'audience' => $data['audience'],
                'priority' => 'normal',
                'publish_date' => today(),
                'expire_date' => $event->end_date ?: $event->start_date,
                'is_published' => true,
                'created_by' => $user->id,
            ]);

            return [$event, $notice];
        });

        app(PushNotificationService::class)->notifyAnnouncementPublished($notice);

        return response()->json([
            'message' => 'Event published as a notice.',
            'event' => [
                'id' => $event->id,
                'title' => $event->title,
                'description' => $event->description,
                'start_date' => $event->start_date?->toDateString(),
                'end_date' => $event->end_date?->toDateString(),
                'type' => $event->type,
                'color' => $event->color,
                'is_public' => (bool) $event->is_public,
                'deep_link' => ['type' => 'calendar_event', 'id' => (string) $event->id],
            ],
            'notice_id' => $notice->id,
        ], 201);
    }
}

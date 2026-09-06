<?php

namespace App\Services\Mobile;

use App\Models\Announcement;
use App\Models\AnnouncementRead;
use App\Models\CalendarEvent;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class MobileCommunicationService
{
    public function notifications(User $user, string $status, int $perPage): array
    {
        $this->authorizeNotifications($user);
        $readIds = AnnouncementRead::query()->where('user_id', $user->id)->pluck('announcement_id');
        $query = $this->visibleAnnouncements($user);

        if ($status === 'read') {
            $query->whereIn('id', $readIds);
        } elseif ($status === 'unread') {
            $query->whereNotIn('id', $readIds);
        }

        /** @var LengthAwarePaginator $page */
        $page = $query->paginate($perPage);
        $readLookup = $readIds->mapWithKeys(fn ($id): array => [(int) $id => true]);

        return [
            'contract_version' => 1,
            'notifications' => collect($page->items())
                ->map(fn (Announcement $announcement): array => $this->notificationItem($announcement, isset($readLookup[$announcement->id])))
                ->values(),
            'unread_count' => $this->visibleAnnouncements($user)->whereNotIn('id', $readIds)->count(),
            'meta' => [
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
            ],
        ];
    }

    public function markRead(User $user, Announcement $announcement): array
    {
        $this->authorizeNotifications($user);
        abort_unless($this->isVisibleTo($announcement, $user), 404);

        AnnouncementRead::updateOrCreate(
            ['tenant_id' => $user->tenant_id, 'announcement_id' => $announcement->id, 'user_id' => $user->id],
            ['read_at' => now()],
        );

        return $this->notificationItem($announcement, true);
    }

    public function markAllRead(User $user): int
    {
        $this->authorizeNotifications($user);
        $now = now();
        $rows = $this->visibleAnnouncements($user)->pluck('id')->map(fn ($id): array => [
            'tenant_id' => $user->tenant_id,
            'announcement_id' => $id,
            'user_id' => $user->id,
            'read_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        if ($rows !== []) {
            AnnouncementRead::upsert($rows, ['tenant_id', 'announcement_id', 'user_id'], ['read_at', 'updated_at']);
        }

        return count($rows);
    }

    public function events(User $user, ?string $from, ?string $to): array
    {
        $this->authorizeCalendar($user);
        $canManage = $user->canManage('calendar');
        $events = CalendarEvent::query()
            ->when(! $canManage, fn ($events) => $events->where('is_public', true))
            ->when($from, fn ($events) => $events->where(function ($dated) use ($from): void {
                $dated->whereDate('end_date', '>=', $from)
                    ->orWhere(function ($nested) use ($from): void {
                        $nested->whereNull('end_date')->whereDate('start_date', '>=', $from);
                    });
            }))
            ->when($to, fn ($events) => $events->whereDate('start_date', '<=', $to))
            ->orderBy('start_date')
            ->limit(250)
            ->get();

        return [
            'contract_version' => 1,
            'events' => $events->map(fn (CalendarEvent $event): array => [
                'id' => $event->id,
                'title' => $event->title,
                'description' => $event->description,
                'start_date' => optional($event->start_date)->format('Y-m-d') ?: (string) $event->start_date,
                'end_date' => optional($event->end_date)->format('Y-m-d') ?: ($event->end_date ? (string) $event->end_date : null),
                'type' => $event->type,
                'color' => $event->color,
                'is_public' => (bool) $event->is_public,
                'deep_link' => ['type' => 'calendar_event', 'id' => (string) $event->id],
            ])->values(),
            'generated_at' => now()->toIso8601String(),
        ];
    }

    private function visibleAnnouncements(User $user)
    {
        return Announcement::query()
            ->where('is_published', true)
            ->whereIn('audience', $this->audiences($user))
            ->whereDate('publish_date', '<=', today())
            ->where(fn ($query) => $query->whereNull('expire_date')->orWhereDate('expire_date', '>=', today()))
            ->orderByRaw("CASE priority WHEN 'urgent' THEN 1 WHEN 'important' THEN 2 ELSE 3 END")
            ->orderByDesc('publish_date')
            ->orderByDesc('id');
    }

    private function isVisibleTo(Announcement $announcement, User $user): bool
    {
        return (int) $announcement->tenant_id === (int) $user->tenant_id
            && $announcement->is_published
            && in_array($announcement->audience, $this->audiences($user), true)
            && $announcement->publish_date?->toDateString() <= today()->toDateString()
            && (! $announcement->expire_date || $announcement->expire_date->toDateString() >= today()->toDateString());
    }

    private function audiences(User $user): array
    {
        return match (true) {
            $user->isStudent() => ['all', 'students'],
            $user->isParent() => ['all', 'parents'],
            $user->isAdmin() || $user->canManage('announcements') => ['all', 'staff', 'admin'],
            default => ['all', 'staff'],
        };
    }

    private function notificationItem(Announcement $announcement, bool $isRead): array
    {
        return [
            'id' => $announcement->id,
            'title' => $announcement->title,
            'body' => $announcement->body,
            'priority' => $announcement->priority,
            'published_at' => optional($announcement->publish_date)->format('Y-m-d') ?: (string) $announcement->publish_date,
            'expires_at' => optional($announcement->expire_date)->format('Y-m-d') ?: ($announcement->expire_date ? (string) $announcement->expire_date : null),
            'is_read' => $isRead,
            'deep_link' => ['type' => 'announcement', 'id' => (string) $announcement->id],
        ];
    }

    private function authorizeNotifications(User $user): void
    {
        abort_unless($user->tenant_id && ($user->isStudent() || $user->isParent() || $user->canAccessModule('notifications') || $user->canAccessModule('announcements')), 403);
    }

    private function authorizeCalendar(User $user): void
    {
        abort_unless($user->tenant_id && ($user->isStudent() || $user->isParent() || $user->canAccessModule('calendar')), 403);
    }
}

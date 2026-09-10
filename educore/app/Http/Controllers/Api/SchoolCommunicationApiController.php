<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MessageThread;
use App\Models\MessageThreadReply;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SchoolCommunicationApiController extends Controller
{
    public function index(Request $request)
    {
        $user = $this->guard($request);
        $userId = (int) $user->id;
        $audiences = $this->audiencesFor($user);

        $query = MessageThread::query()
            ->where('tenant_id', $user->tenant_id)
            ->whereNull('student_id');

        if (! $this->canOversee($user)) {
            $query->where(function ($visible) use ($userId, $audiences): void {
                $visible->where('initiated_by', $userId)
                    ->orWhere('recipient_user_id', $userId)
                    ->orWhereHas('replies', fn ($replies) => $replies->where('sender_id', $userId));
                if ($audiences !== []) {
                    $visible->orWhereIn('audience', $audiences);
                }
            });
        }

        $threads = $query
            ->with(['initiator:id,name,role', 'recipient:id,name,role', 'replies' => fn ($q) => $q->latest()])
            ->latest('updated_at')
            ->paginate(min(max((int) $request->integer('per_page', 50), 1), 100));

        return response()->json([
            'contract_version' => 3,
            'threads' => collect($threads->items())->map(fn (MessageThread $thread) => $this->summary($thread, $user))->values(),
            'unread_total' => collect($threads->items())->sum(fn (MessageThread $thread) => $this->unreadCount($thread, $user)),
            'meta' => [
                'current_page' => $threads->currentPage(),
                'last_page' => $threads->lastPage(),
                'per_page' => $threads->perPage(),
                'total' => $threads->total(),
            ],
        ]);
    }

    public function recipients(Request $request)
    {
        $user = $this->guard($request);
        $recipients = collect();

        if ($this->canOversee($user)) {
            $recipients->push($this->target('all_staff', 'all_staff', 'Entire Staff', 'Broadcast to all active staff in this school'));
            $recipients->push($this->target('all_parents', 'all_parents', 'All Parents', 'Broadcast to all active parent accounts in this school'));

            User::activeStaff($user->tenant_id)
                ->whereKeyNot($user->id)
                ->orderBy('name')
                ->get(['id', 'name', 'role'])
                ->each(fn (User $member) => $recipients->push(
                    $this->target('staff:'.$member->id, 'staff', $member->name, 'Individual staff member')
                ));

            User::query()
                ->where('tenant_id', $user->tenant_id)
                ->where('role', 'parent')
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'role'])
                ->each(fn (User $parent) => $recipients->push(
                    $this->target('parent:'.$parent->id, 'parent', $parent->name, 'Individual parent')
                ));
        } else {
            $recipients->push($this->target('school_admin', 'admin', 'School Administration', 'Private conversation with school administration'));
        }

        return response()->json([
            'contract_version' => 3,
            'recipients' => $recipients->values(),
        ]);
    }

    public function store(Request $request)
    {
        $user = $this->guard($request);
        $data = $request->validate([
            'target' => ['required', 'string', 'max:80'],
            'subject' => ['required', 'string', 'max:150'],
            'body' => ['required', 'string', 'max:10000'],
        ]);

        [$type, $recipientId, $audience] = $this->resolveTarget($user, $data['target']);

        $thread = DB::transaction(function () use ($user, $data, $type, $recipientId, $audience): MessageThread {
            $thread = MessageThread::create([
                'tenant_id' => $user->tenant_id,
                'student_id' => null,
                'conversation_type' => $type,
                'recipient_user_id' => $recipientId,
                'audience' => $audience,
                'subject' => trim($data['subject']),
                'initiated_by' => $user->id,
                'status' => 'open',
            ]);

            MessageThreadReply::create([
                'tenant_id' => $user->tenant_id,
                'thread_id' => $thread->id,
                'sender_id' => $user->id,
                'body' => trim($data['body']),
            ]);
            $thread->touch();

            return $thread;
        });

        $thread->load(['initiator:id,name,role', 'recipient:id,name,role', 'replies.sender:id,name']);

        return response()->json([
            'message' => $audience ? 'Broadcast sent.' : 'Message sent.',
            'thread' => $this->threadPayload($thread, $user),
        ], 201);
    }

    public function show(Request $request, MessageThread $thread)
    {
        $user = $this->guard($request);
        $this->authorizeThread($thread, $user);
        $this->markRead($thread, $user);
        $thread->load(['initiator:id,name,role', 'recipient:id,name,role', 'replies.sender:id,name']);

        return response()->json(['thread' => $this->threadPayload($thread, $user)]);
    }

    public function reply(Request $request, MessageThread $thread)
    {
        $user = $this->guard($request);
        $this->authorizeThread($thread, $user);
        abort_if($thread->status !== 'open', 422, 'This thread has been closed.');
        $data = $request->validate(['body' => ['required', 'string', 'max:10000']]);

        if ($thread->isBroadcast() && (int) $thread->initiated_by !== (int) $user->id) {
            $admin = $this->schoolAdministrator($user);
            $privateThread = DB::transaction(function () use ($thread, $user, $admin, $data): MessageThread {
                $privateThread = MessageThread::create([
                    'tenant_id' => $user->tenant_id,
                    'student_id' => null,
                    'conversation_type' => 'admin',
                    'recipient_user_id' => $admin->id,
                    'audience' => null,
                    'subject' => str_starts_with($thread->subject, 'Re: ') ? $thread->subject : 'Re: '.$thread->subject,
                    'initiated_by' => $user->id,
                    'status' => 'open',
                ]);
                MessageThreadReply::create([
                    'tenant_id' => $user->tenant_id,
                    'thread_id' => $privateThread->id,
                    'sender_id' => $user->id,
                    'body' => trim($data['body']),
                ]);
                $privateThread->touch();
                return $privateThread;
            });

            $privateThread->load(['initiator:id,name,role', 'recipient:id,name,role', 'replies.sender:id,name']);
            return response()->json([
                'message' => 'Your reply was sent privately to School Administration.',
                'redirected_from_broadcast' => true,
                'thread' => $this->threadPayload($privateThread, $user),
            ], 201);
        }

        $reply = MessageThreadReply::create([
            'tenant_id' => $user->tenant_id,
            'thread_id' => $thread->id,
            'sender_id' => $user->id,
            'body' => trim($data['body']),
        ]);
        $thread->touch();

        return response()->json([
            'message' => 'Reply sent.',
            'redirected_from_broadcast' => false,
            'reply' => $this->replyPayload($reply->fresh('sender'), $user),
        ], 201);
    }

    private function resolveTarget(User $user, string $target): array
    {
        if ($target === 'school_admin') {
            abort_unless(! $this->canOversee($user), 422, 'Select a staff member, parent, or broadcast audience.');
            return ['admin', $this->schoolAdministrator($user)->id, null];
        }
        if ($target === 'all_staff' || $target === 'all_parents') {
            abort_unless($this->canOversee($user), 403);
            return [$target, null, $target];
        }
        if (preg_match('/^(staff|parent):(\d+)$/', $target, $match)) {
            abort_unless($this->canOversee($user), 403);
            $type = $match[1];
            $recipient = User::query()
                ->where('tenant_id', $user->tenant_id)
                ->whereKey((int) $match[2])
                ->where('is_active', true)
                ->firstOrFail();
            abort_unless($type === 'staff' ? $recipient->isTenantStaff() : $recipient->isParent(), 422, 'Invalid recipient.');
            return [$type, $recipient->id, null];
        }

        throw ValidationException::withMessages(['target' => 'Select a valid school communication recipient.']);
    }

    private function authorizeThread(MessageThread $thread, User $user): void
    {
        abort_unless((int) $thread->tenant_id === (int) $user->tenant_id && $thread->student_id === null, 404);
        if ($this->canOversee($user)) {
            return;
        }
        $userId = (int) $user->id;
        $audiences = $this->audiencesFor($user);
        $allowed = (int) $thread->initiated_by === $userId
            || (int) $thread->recipient_user_id === $userId
            || $thread->replies()->where('sender_id', $userId)->exists()
            || ($thread->audience && in_array($thread->audience, $audiences, true));
        abort_unless($allowed, 403, 'You are not a participant in this conversation.');
    }

    private function markRead(MessageThread $thread, User $user): void
    {
        if ($thread->isBroadcast() && (int) $thread->initiated_by !== (int) $user->id) {
            DB::table('message_thread_reads')->updateOrInsert(
                ['thread_id' => $thread->id, 'user_id' => $user->id],
                ['tenant_id' => $user->tenant_id, 'read_at' => now(), 'updated_at' => now(), 'created_at' => now()],
            );
            return;
        }

        $thread->replies()->where('sender_id', '!=', $user->id)->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => now()]);
    }

    private function unreadCount(MessageThread $thread, User $user): int
    {
        if ($thread->isBroadcast() && (int) $thread->initiated_by !== (int) $user->id) {
            return DB::table('message_thread_reads')
                ->where('thread_id', $thread->id)
                ->where('user_id', $user->id)
                ->exists() ? 0 : 1;
        }
        return $thread->relationLoaded('replies')
            ? $thread->replies->where('sender_id', '!=', $user->id)->where('is_read', false)->count()
            : $thread->replies()->where('sender_id', '!=', $user->id)->where('is_read', false)->count();
    }

    private function threadPayload(MessageThread $thread, User $user): array
    {
        return [
            ...$this->summary($thread, $user),
            'replies' => $thread->replies->sortBy('created_at')->map(fn (MessageThreadReply $reply) => $this->replyPayload($reply, $user))->values(),
        ];
    }

    private function summary(MessageThread $thread, User $user): array
    {
        $lastReply = $thread->replies->sortByDesc('created_at')->first();
        return [
            'id' => $thread->id,
            'subject' => $thread->subject,
            'status' => $thread->status,
            'type' => $thread->conversation_type ?: ($thread->audience ?: 'school'),
            'student_name' => null,
            'other_name' => $this->otherName($thread, $user),
            'last_message' => $lastReply?->body,
            'unread_count' => $this->unreadCount($thread, $user),
            'updated_at' => $thread->updated_at?->toIso8601String(),
            'deep_link' => ['type' => 'message_thread', 'id' => (string) $thread->id],
        ];
    }

    private function replyPayload(MessageThreadReply $reply, User $user): array
    {
        return [
            'id' => $reply->id,
            'body' => $reply->body,
            'sender_id' => $reply->sender_id,
            'sender_name' => $reply->sender?->name,
            'is_me' => (int) $reply->sender_id === (int) $user->id,
            'created_at' => $reply->created_at?->toIso8601String(),
            'attachment' => null,
        ];
    }

    private function otherName(MessageThread $thread, User $user): string
    {
        if ($thread->audience === 'all_staff') return 'Entire Staff';
        if ($thread->audience === 'all_parents') return 'All Parents';
        if ((int) $thread->initiated_by === (int) $user->id) return $thread->recipient?->name ?? 'School Administration';
        return $thread->initiator?->name ?? 'School Administration';
    }

    private function schoolAdministrator(User $user): User
    {
        $admin = User::query()
            ->where('tenant_id', $user->tenant_id)
            ->where('is_active', true)
            ->whereIn('role', ['admin', 'principal', 'head', 'head_teacher', 'vice_principal', 'academic_administrator'])
            ->orderByRaw("CASE role WHEN 'admin' THEN 1 WHEN 'principal' THEN 2 ELSE 3 END")
            ->orderBy('id')
            ->first();
        abort_unless($admin, 422, 'No school administrator is available for messaging.');
        return $admin;
    }

    private function canOversee(User $user): bool
    {
        return $user->isAdmin()
            || in_array($user->roleKey(), ['principal', 'head', 'head_teacher', 'vice_principal', 'academic_administrator'], true)
            || $user->canManage('messages');
    }

    private function audiencesFor(User $user): array
    {
        if ($user->isParent()) return ['all_parents'];
        if ($user->isTenantStaff()) return ['all_staff'];
        return [];
    }

    private function guard(Request $request): User
    {
        $user = $request->user();
        abort_unless($user && $user->tenant_id && ($user->isParent() || $user->isTenantStaff()), 403);
        if ($user->isTenantStaff()) {
            abort_unless($user->canAccessModule('messages') || $this->canOversee($user), 403);
        }
        return $user;
    }

    private function target(string $target, string $type, string $name, string $supportingText): array
    {
        return compact('target', 'type', 'name', 'supportingText');
    }
}

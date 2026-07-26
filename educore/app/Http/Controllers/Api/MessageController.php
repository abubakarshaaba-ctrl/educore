<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MessageThread;
use App\Models\MessageThreadReply;
use App\Models\User;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    /** Threads this staff member is party to (initiated, or has replied to). */
    public function index(Request $request)
    {
        $userId = $request->user()->id;

        $threads = MessageThread::where(function ($q) use ($userId) {
                $q->where('initiated_by', $userId)
                  ->orWhereHas('replies', fn ($r) => $r->where('sender_id', $userId));
            })
            ->with(['student', 'initiator', 'replies' => fn ($q) => $q->latest()->limit(1)])
            ->latest()
            ->paginate(20);

        $items = collect($threads->items())->map(function (MessageThread $thread) use ($userId) {
            $lastReply = $thread->replies->first();
            $unread = $thread->replies()->where('sender_id', '!=', $userId)->where('is_read', false)->count();

            return [
                'id'           => $thread->id,
                'subject'      => $thread->subject,
                'status'       => $thread->status,
                'type'         => $thread->student_id ? 'student' : 'internal',
                'student_name' => optional($thread->student)->full_name,
                'other_name'   => $thread->initiated_by === $userId
                    ? ($thread->recipient_id ? optional(User::find($thread->recipient_id))->name : 'School')
                    : (optional($thread->initiator)->name ?? 'Admin'),
                'last_message' => optional($lastReply)->body,
                'unread_count' => $unread,
                'updated_at'   => $thread->updated_at?->toIso8601String(),
            ];
        })->values();

        return response()->json([
            'threads'      => $items,
            'unread_total' => MessageThread::whereHas('replies', fn ($q) =>
                $q->where('is_read', false)->where('sender_id', '!=', $userId)
            )->count(),
            'current_page' => $threads->currentPage(),
            'last_page'    => $threads->lastPage(),
        ]);
    }

    /** Full thread with replies; marks incoming replies as read. */
    public function show(Request $request, MessageThread $thread)
    {
        $user = $request->user();
        abort_unless(
            (int) $thread->initiated_by === (int) $user->id
                || $thread->replies()->where('sender_id', $user->id)->exists(),
            403,
            'You are not a participant in this conversation.'
        );

        $thread->load(['student', 'initiator', 'replies.sender']);

        $thread->replies()
            ->where('sender_id', '!=', $user->id)
            ->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => now()]);

        return response()->json([
            'thread' => [
                'id'      => $thread->id,
                'subject' => $thread->subject,
                'status'  => $thread->status,
                'replies' => $thread->replies->map(fn ($r) => [
                    'id'         => $r->id,
                    'body'       => $r->body,
                    'sender_id'  => $r->sender_id,
                    'sender_name'=> optional($r->sender)->name,
                    'is_me'      => (int) $r->sender_id === (int) $user->id,
                    'created_at' => $r->created_at?->toIso8601String(),
                ])->values(),
            ],
        ]);
    }

    /** Post a reply into an existing thread. */
    public function reply(Request $request, MessageThread $thread)
    {
        $user = $request->user();
        abort_unless(
            (int) $thread->initiated_by === (int) $user->id
                || $thread->replies()->where('sender_id', $user->id)->exists(),
            403,
            'You are not a participant in this conversation.'
        );
        abort_if($thread->status !== 'open', 422, 'This thread has been closed.');

        $data = $request->validate(['body' => ['required', 'string']]);

        $reply = MessageThreadReply::create([
            'tenant_id' => $thread->tenant_id,
            'thread_id' => $thread->id,
            'sender_id' => $user->id,
            'body'      => $data['body'],
        ]);

        return response()->json([
            'reply' => [
                'id'          => $reply->id,
                'body'        => $reply->body,
                'sender_id'   => $reply->sender_id,
                'sender_name' => $user->name,
                'is_me'       => true,
                'created_at'  => $reply->created_at?->toIso8601String(),
            ],
        ], 201);
    }
}

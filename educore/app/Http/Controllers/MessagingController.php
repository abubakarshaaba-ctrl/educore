<?php
namespace App\Http\Controllers;

use App\Models\MessageThread;
use App\Models\MessageThreadReply;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MessagingController extends Controller
{
    private function tenantId(): int
    {
        return (int) auth()->user()->tenant_id;
    }

    public function inbox(Request $request)
    {
        $userId = auth()->id();

        // Threads where the current user participated (initiated or replied)
        $threads = MessageThread::where(function ($q) use ($userId) {
                $q->where('initiated_by', $userId)
                  ->orWhereHas('replies', fn($r) => $r->where('sender_id', $userId));
            })
            ->with(['student', 'initiator', 'replies' => fn($q) => $q->latest()->limit(1)])
            ->latest()
            ->paginate(20);

        $unreadCount = MessageThread::whereHas('replies', fn($q) =>
            $q->where('is_read', false)->where('sender_id', '!=', $userId)
        )->count();

        return view('messages.inbox', compact('threads', 'unreadCount'));
    }

    public function compose(Request $request)
    {
        $students = Student::where('status', Student::STATUS_ACTIVE)->orderBy('last_name')->get();
        return view('messages.compose', compact('students'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'student_id' => ['required', Rule::exists('students', 'id')->where(fn ($query) => $query->where('tenant_id', $this->tenantId())->where('status', Student::STATUS_ACTIVE))],
            'subject'    => ['required', 'string', 'max:150'],
            'body'       => ['required', 'string'],
        ]);

        $thread = MessageThread::create([
            'student_id'   => $data['student_id'],
            'subject'      => $data['subject'],
            'initiated_by' => auth()->id(),
        ]);

        MessageThreadReply::create([
            'thread_id' => $thread->id,
            'sender_id' => auth()->id(),
            'body'      => $data['body'],
        ]);

        return redirect()->route('messages.thread', $thread)
            ->with('success', 'Message sent.');
    }

    public function thread(MessageThread $thread)
    {
        $thread->load(['student', 'initiator', 'replies.sender']);

        // Mark all unread replies as read
        $thread->replies()
            ->where('sender_id', '!=', auth()->id())
            ->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => now()]);

        return view('messages.thread', compact('thread'));
    }

    public function reply(Request $request, MessageThread $thread)
    {
        $data = $request->validate(['body' => ['required', 'string']]);

        MessageThreadReply::create([
            'thread_id' => $thread->id,
            'sender_id' => auth()->id(),
            'body'      => $data['body'],
        ]);

        return back()->with('success', 'Reply sent.');
    }

    public function close(MessageThread $thread)
    {
        $thread->update(['status' => 'closed']);
        return back()->with('success', 'Thread closed.');
    }
}

<?php
namespace App\Http\Controllers;

use App\Models\MessageThread;
use App\Models\MessageThreadReply;
use App\Models\Student;
use App\Models\User;
use App\Services\Notifications\PushNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class MessagingController extends Controller
{
    private function tenantId(): int { return (int) auth()->user()->tenant_id; }

    private function canOverseeAllThreads(User $user): bool
    {
        return $user->isAdmin()
            || in_array($user->roleKey(), ['principal','head','head_teacher','vice_principal','academic_administrator'], true)
            || $user->canManage('messages');
    }

    private function broadcastAudiencesFor(User $user): array
    {
        if ($user->isParent()) return ['all_parents'];
        if ($user->isTenantStaff()) return ['all_staff'];
        return [];
    }

    public function inbox(Request $request)
    {
        $user = auth()->user();
        $userId = (int) $user->id;
        $threads = MessageThread::query()
            ->where('tenant_id', $this->tenantId())
            ->when(! $this->canOverseeAllThreads($user), function ($query) use ($userId, $user): void {
                $audiences = $this->broadcastAudiencesFor($user);
                $query->where(function ($visible) use ($userId, $audiences): void {
                    $visible->where('initiated_by', $userId)
                        ->orWhere('recipient_user_id', $userId)
                        ->orWhereHas('replies', fn ($replies) => $replies->where('sender_id', $userId));
                    if ($audiences !== []) $visible->orWhereIn('audience', $audiences);
                });
            })
            ->with(['student','initiator','recipient','replies' => fn ($q) => $q->latest()->limit(1)])
            ->latest('updated_at')
            ->paginate(20);

        collect($threads->items())->each(function (MessageThread $thread) use ($userId): void {
            $thread->setAttribute('is_unread_for_user', $this->threadIsUnreadFor($thread, $userId));
        });
        $unreadCount = collect($threads->items())->where('is_unread_for_user', true)->count();

        return view('messages.inbox', compact('threads','unreadCount'));
    }

    public function compose(Request $request)
    {
        $students = Student::query()->where('tenant_id', $this->tenantId())->where('status', Student::STATUS_ACTIVE)->orderBy('last_name')->get();
        return view('messages.compose', compact('students'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'student_id' => ['required', Rule::exists('students','id')->where(fn ($q) => $q->where('tenant_id',$this->tenantId())->where('status', Student::STATUS_ACTIVE))],
            'subject' => ['required','string','max:150'],
            'body' => ['required','string','max:10000'],
        ]);

        $thread = DB::transaction(function () use ($data): MessageThread {
            $thread = MessageThread::create([
                'tenant_id'=>$this->tenantId(), 'student_id'=>$data['student_id'], 'conversation_type'=>'student',
                'subject'=>trim($data['subject']), 'initiated_by'=>auth()->id(), 'status'=>'open',
            ]);
            MessageThreadReply::create([
                'tenant_id'=>$this->tenantId(), 'thread_id'=>$thread->id, 'sender_id'=>auth()->id(), 'body'=>trim($data['body']),
            ]);
            return $thread;
        });

        app(PushNotificationService::class)->notifyMessageThread($thread, auth()->user(), $data['body']);
        return redirect()->route('messages.thread',$thread)->with('success','Message sent.');
    }

    public function composeInternal(Request $request)
    {
        $user = auth()->user();
        abort_unless($user->tenant_id && ($user->isTenantStaff() || $user->isParent()), 403);
        $staff = collect(); $parents = collect();

        if ($this->canOverseeAllThreads($user)) {
            $staff = User::tenantStaff($this->tenantId())->where('id','!=',$user->id)->where('is_active',true)->orderBy('name')->get(['id','name','role','staff_id']);
            $parents = User::query()->where('tenant_id',$this->tenantId())->where('role','parent')->where('is_active',true)->orderBy('name')->get(['id','name','role','phone']);
        }

        return view('messages.compose-internal', [
            'staff'=>$staff, 'parents'=>$parents, 'canBroadcast'=>$this->canOverseeAllThreads($user),
        ]);
    }

    public function storeInternal(Request $request)
    {
        $user = auth()->user();
        abort_unless($user->tenant_id && ($user->isTenantStaff() || $user->isParent()), 403);
        $allowedTargets = $this->canOverseeAllThreads($user) ? ['all_staff','staff','all_parents','parent'] : ['admin'];
        $data = $request->validate([
            'target_type'=>['required',Rule::in($allowedTargets)], 'recipient_id'=>['nullable','integer'],
            'subject'=>['required','string','max:150'], 'body'=>['required','string','max:10000'],
        ]);

        $recipient = null; $audience = null;
        if (in_array($data['target_type'], ['staff','parent'], true)) {
            abort_unless($request->filled('recipient_id'), 422, 'Select a recipient.');
            $recipient = User::query()->where('tenant_id',$this->tenantId())->whereKey($data['recipient_id'])->where('is_active',true)->firstOrFail();
            abort_unless($data['target_type']==='staff' ? $recipient->isTenantStaff() : $recipient->isParent(), 422, 'Select a valid recipient.');
        } elseif ($data['target_type']==='admin') {
            $recipient = User::query()->where('tenant_id',$this->tenantId())->where('is_active',true)
                ->whereIn('role',['admin','principal','head','head_teacher','vice_principal','academic_administrator'])
                ->orderByRaw("CASE role WHEN 'admin' THEN 1 WHEN 'principal' THEN 2 ELSE 3 END")->orderBy('id')->first();
            abort_unless($recipient, 422, 'No school administrator is available for messaging.');
        } else {
            $audience = $data['target_type'];
        }

        $thread = DB::transaction(function () use ($data,$recipient,$audience,$user): MessageThread {
            $thread = MessageThread::create([
                'tenant_id'=>$this->tenantId(), 'student_id'=>null, 'conversation_type'=>$data['target_type'],
                'recipient_user_id'=>$recipient?->id, 'audience'=>$audience, 'subject'=>trim($data['subject']),
                'initiated_by'=>$user->id, 'status'=>'open',
            ]);
            MessageThreadReply::create([
                'tenant_id'=>$this->tenantId(), 'thread_id'=>$thread->id, 'sender_id'=>$user->id, 'body'=>trim($data['body']),
            ]);
            return $thread;
        });

        return redirect()->route('messages.thread',$thread)->with('success',$audience ? 'Broadcast sent.' : 'Message sent.');
    }

    public function thread(MessageThread $thread)
    {
        $this->authorizeThread($thread);
        $thread->load(['student.currentClassArm.classLevel','initiator','recipient','replies.sender']);
        $userId = (int) auth()->id();
        if ($thread->isBroadcast() && (int) $thread->initiated_by !== $userId) {
            DB::table('message_thread_reads')->updateOrInsert(
                ['thread_id'=>$thread->id,'user_id'=>$userId],
                ['tenant_id'=>$this->tenantId(),'read_at'=>now(),'updated_at'=>now(),'created_at'=>now()],
            );
        } else {
            $thread->replies()->where('sender_id','!=',$userId)->where('is_read',false)->update(['is_read'=>true,'read_at'=>now()]);
        }
        return view('messages.thread',compact('thread'));
    }

    public function reply(Request $request, MessageThread $thread)
    {
        $this->authorizeThread($thread);
        abort_if($thread->status !== 'open', 422, 'This thread has been closed.');
        $data = $request->validate(['body'=>['required','string','max:10000']]);
        $user = auth()->user();

        if ($thread->isBroadcast() && (int) $thread->initiated_by !== (int) $user->id) {
            $private = DB::transaction(function () use ($thread,$data,$user): MessageThread {
                $private = MessageThread::create([
                    'tenant_id'=>$this->tenantId(), 'student_id'=>null, 'conversation_type'=>$user->isParent() ? 'parent' : 'staff',
                    'recipient_user_id'=>$thread->initiated_by, 'audience'=>null, 'subject'=>'Re: '.$thread->subject,
                    'initiated_by'=>$user->id, 'status'=>'open',
                ]);
                MessageThreadReply::create([
                    'tenant_id'=>$this->tenantId(), 'thread_id'=>$private->id, 'sender_id'=>$user->id, 'body'=>trim($data['body']),
                ]);
                return $private;
            });
            return redirect()->route('messages.thread',$private)->with('success','Your reply was sent privately to school administration.');
        }

        MessageThreadReply::create([
            'tenant_id'=>$this->tenantId(), 'thread_id'=>$thread->id, 'sender_id'=>$user->id, 'body'=>trim($data['body']),
        ]);
        $thread->touch();
        if ($thread->student_id) app(PushNotificationService::class)->notifyMessageThread($thread,$user,$data['body']);
        return back()->with('success','Reply sent.');
    }

    public function close(MessageThread $thread)
    {
        $this->authorizeThread($thread);
        abort_unless((int)$thread->initiated_by === (int)auth()->id() || $this->canOverseeAllThreads(auth()->user()), 403);
        $thread->update(['status'=>'closed']);
        return back()->with('success','Thread closed.');
    }

    private function threadIsUnreadFor(MessageThread $thread, int $userId): bool
    {
        if ($thread->isBroadcast() && (int)$thread->initiated_by !== $userId) {
            return ! DB::table('message_thread_reads')->where('thread_id',$thread->id)->where('user_id',$userId)->whereNotNull('read_at')->exists();
        }
        return $thread->replies()->where('sender_id','!=',$userId)->where('is_read',false)->exists();
    }

    private function authorizeThread(MessageThread $thread): void
    {
        $user = auth()->user();
        abort_unless((int)$thread->tenant_id === $this->tenantId(), 404);
        if ($this->canOverseeAllThreads($user)) return;
        $isParticipant = (int)$thread->initiated_by === (int)$user->id
            || (int)$thread->recipient_user_id === (int)$user->id
            || $thread->replies()->where('sender_id',$user->id)->exists();
        $isAudienceRecipient = in_array($thread->audience, $this->broadcastAudiencesFor($user), true);
        abort_unless($isParticipant || $isAudienceRecipient, 403, 'You are not a participant in this conversation.');
    }
}

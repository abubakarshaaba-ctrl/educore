<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Guardian;
use App\Models\MessageThread;
use App\Models\MessageThreadReply;
use App\Models\Student;
use App\Models\User;
use App\Services\Messaging\SchoolMessagingAudienceService;
use App\Services\Mobile\MobileClassAccessService;
use App\Services\Notifications\PushNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class MessageController extends Controller
{
    public function __construct(
        private readonly MobileClassAccessService $classAccess,
        private readonly SchoolMessagingAudienceService $audiences,
    ) {}

    public function index(Request $request)
    {
        $user = $request->user();
        $query = MessageThread::query()->where('tenant_id', $user->tenant_id);
        $this->audiences->scopeVisible($query, $user);
        $threads = $query
            ->with(['student', 'initiator', 'recipient', 'replies' => fn ($replies) => $replies->latest()])
            ->orderByDesc('updated_at')
            ->paginate(min(max((int) $request->integer('per_page', 20), 1), 50));

        return response()->json([
            'contract_version' => 3,
            'threads' => collect($threads->items())->map(fn (MessageThread $thread): array => $this->threadSummary($thread, $user))->values(),
            'unread_total' => collect($threads->items())->sum(fn (MessageThread $thread): int => $this->unreadCount($thread, (int) $user->id)),
            'meta' => [
                'current_page' => $threads->currentPage(), 'last_page' => $threads->lastPage(),
                'per_page' => $threads->perPage(), 'total' => $threads->total(),
            ],
        ]);
    }

    public function recipients(Request $request)
    {
        $user = $request->user();
        abort_unless($user->tenant_id && ($user->isTenantStaff() || $user->isParent() || $user->isStudent()), 403);
        $recipients = collect();

        if ($this->audiences->canOversee($user)) {
            $recipients->push($this->target(-1, 'all_staff', null, 'All Staff', 'Shared group thread'));
            $recipients->push($this->target(-2, 'academic_staff', null, 'Academic Staff', 'Shared academic staff thread'));
            User::tenantStaff((int) $user->tenant_id)->where('id', '!=', $user->id)->where('is_active', true)->orderBy('name')->get()
                ->each(fn (User $staff) => $recipients->push($this->target((int) $staff->id, 'staff', (int) $staff->id, $staff->name, $staff->staff_id ?: 'Private staff message')));
            $recipients->push($this->target(-3, 'all_parents', null, 'All Parents', 'Shared parent group thread'));
            User::query()->where('tenant_id', $user->tenant_id)->where('role', 'parent')->where('is_active', true)->orderBy('name')->get()
                ->each(fn (User $parent) => $recipients->push($this->target((int) $parent->id, 'parent', (int) $parent->id, $parent->name, $parent->phone ?: 'Private parent message')));
        } elseif ($user->isTenantStaff() || $user->isParent()) {
            $recipients->push($this->target(-4, 'admin', null, 'School Administration', 'Private conversation with administration'));
        } else {
            $this->allowedStudents($user)->each(function (Student $student) use ($recipients): void {
                $student->loadMissing('currentClassArm.classLevel');
                $className = trim(($student->currentClassArm?->classLevel?->name ?? '').' '.($student->currentClassArm?->name ?? ''));
                $recipients->push([
                    'id' => (int) $student->id, 'target_type' => 'student', 'recipient_id' => null,
                    'student_id' => (int) $student->id, 'name' => $student->full_name,
                    'supporting' => $student->admission_number, 'admission_number' => $student->admission_number,
                    'class_name' => $className ?: null,
                ]);
            });
        }

        return response()->json(['contract_version' => 3, 'recipients' => $recipients->values()]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'target_type' => ['nullable', 'string', Rule::in(['all_staff','academic_staff','staff','all_parents','parent','admin','student'])],
            'recipient_id' => ['nullable', 'integer'],
            'student_id' => ['nullable', 'integer'],
            'subject' => ['required', 'string', 'max:150'],
            'body' => ['required', 'string', 'max:10000'],
            'attachment' => ['nullable', 'file', 'max:5120', 'mimes:jpg,jpeg,png,pdf,doc,docx,xls,xlsx'],
        ]);
        $targetType = $data['target_type'] ?? ($data['student_id'] ? 'student' : null);
        abort_unless($targetType, 422, 'Choose a recipient.');

        [$recipient, $audience, $studentId] = $this->resolveTarget($user, $targetType, $data['recipient_id'] ?? null, $data['student_id'] ?? null);

        [$thread, $reply] = DB::transaction(function () use ($request, $user, $data, $targetType, $recipient, $audience, $studentId): array {
            $thread = MessageThread::create([
                'tenant_id' => $user->tenant_id,
                'student_id' => $studentId,
                'conversation_type' => $studentId ? 'student' : ($audience ? 'group' : 'private'),
                'recipient_user_id' => $recipient?->id,
                'audience' => $audience,
                'subject' => trim($data['subject']),
                'initiated_by' => $user->id,
                'status' => 'open',
            ]);
            $reply = $this->createReply($request, $thread, $user, $data['body']);
            $thread->touch();
            return [$thread, $reply];
        });

        app(PushNotificationService::class)->notifyMessageThread($thread, $user, $data['body']);
        return response()->json([
            'thread' => $this->threadSummary($thread->fresh(['student','initiator','recipient','replies']), $user),
            'reply' => $this->replyResource($reply->fresh('sender'), $user),
        ], 201);
    }

    public function show(Request $request, MessageThread $thread)
    {
        $user = $request->user();
        $this->audiences->authorize($thread, $user);
        $this->markThreadRead($thread, $user);
        $thread->load(['student','initiator','recipient','replies.sender']);
        return response()->json(['thread' => [
            ...$this->threadSummary($thread, $user),
            'replies' => $thread->replies->map(fn (MessageThreadReply $reply): array => $this->replyResource($reply, $user))->values(),
        ]]);
    }

    public function reply(Request $request, MessageThread $thread)
    {
        $user = $request->user();
        $this->audiences->authorize($thread, $user);
        abort_if($thread->status !== 'open', 422, 'This thread has been closed.');
        $data = $request->validate([
            'body' => ['required','string','max:10000'],
            'attachment' => ['nullable','file','max:5120','mimes:jpg,jpeg,png,pdf,doc,docx,xls,xlsx'],
        ]);
        $reply = DB::transaction(function () use ($request,$thread,$user,$data): MessageThreadReply {
            $reply = $this->createReply($request,$thread,$user,$data['body']);
            $thread->touch(); return $reply;
        });
        app(PushNotificationService::class)->notifyMessageThread($thread,$user,$data['body']);
        return response()->json(['reply'=>$this->replyResource($reply->fresh('sender'),$user)],201);
    }

    public function attachment(Request $request, MessageThreadReply $reply)
    {
        $thread = $reply->thread()->firstOrFail();
        $this->audiences->authorize($thread, $request->user());
        abort_unless($reply->attachment_path && Storage::disk('local')->exists($reply->attachment_path),404);
        return Storage::disk('local')->download($reply->attachment_path,$reply->attachment_name ?: 'attachment',['Content-Type'=>$reply->attachment_mime ?: 'application/octet-stream']);
    }

    private function resolveTarget(User $user, string $type, ?int $recipientId, ?int $studentId): array
    {
        if ($type === 'student') {
            $allowed = $this->allowedStudents($user)->pluck('id');
            abort_unless($studentId && $allowed->contains($studentId), 403, 'That student conversation is unavailable.');
            return [null, null, $studentId];
        }
        if (in_array($type, ['all_staff','academic_staff','all_parents'], true)) {
            abort_unless($this->audiences->canOversee($user),403);
            return [null, $type, null];
        }
        if (in_array($type,['staff','parent'],true)) {
            abort_unless($this->audiences->canOversee($user) && $recipientId,403);
            $recipient = User::query()->where('tenant_id',$user->tenant_id)->whereKey($recipientId)->where('is_active',true)->firstOrFail();
            abort_unless($type === 'staff' ? $recipient->isTenantStaff() : $recipient->isParent(),422,'Invalid recipient.');
            return [$recipient,null,null];
        }
        abort_unless($type === 'admin' && ($user->isTenantStaff() || $user->isParent()),403);
        $admin = User::query()->where('tenant_id',$user->tenant_id)->where('is_active',true)
            ->whereIn('role',['admin','principal','head','head_teacher','vice_principal','academic_administrator'])
            ->orderByRaw("CASE role WHEN 'admin' THEN 1 WHEN 'principal' THEN 2 ELSE 3 END")->first();
        abort_unless($admin,422,'No school administrator is available for messaging.');
        return [$admin,null,null];
    }

    private function createReply(Request $request, MessageThread $thread, User $user, string $body): MessageThreadReply
    {
        $attachment = [];
        if ($file = $request->file('attachment')) {
            $safeOriginal = Str::limit(preg_replace('/[^A-Za-z0-9._ -]/','_',$file->getClientOriginalName()) ?: 'attachment',180,'');
            $storedName = Str::uuid().'.'.($file->guessExtension() ?: 'bin');
            $path = Storage::disk('local')->putFileAs("mobile-message-attachments/{$user->tenant_id}/{$thread->id}",$file,$storedName);
            $attachment = ['attachment_path'=>$path,'attachment_name'=>$safeOriginal,'attachment_mime'=>$file->getMimeType(),'attachment_size'=>$file->getSize()];
        }
        return MessageThreadReply::create(['tenant_id'=>$thread->tenant_id,'thread_id'=>$thread->id,'sender_id'=>$user->id,'body'=>trim($body),...$attachment]);
    }

    private function threadSummary(MessageThread $thread, User $user): array
    {
        $lastReply = $thread->replies->sortByDesc('created_at')->first();
        $other = match ($thread->audience) {
            'all_staff' => 'All Staff', 'academic_staff' => 'Academic Staff', 'all_parents' => 'All Parents',
            default => (int)$thread->initiated_by === (int)$user->id
                ? ($thread->recipient?->name ?? ($thread->student?->full_name ?? 'School'))
                : ($thread->initiator?->name ?? 'School'),
        };
        return [
            'id'=>$thread->id,'subject'=>$thread->subject,'status'=>$thread->status,
            'type'=>$thread->isBroadcast() ? 'group' : ($thread->student_id ? 'student' : 'private'),
            'audience'=>$thread->audience,'student_name'=>$thread->student?->full_name,'other_name'=>$other,
            'last_message'=>$lastReply?->body,'unread_count'=>$this->unreadCount($thread,(int)$user->id),
            'updated_at'=>$thread->updated_at?->toIso8601String(),'deep_link'=>['type'=>'message_thread','id'=>(string)$thread->id],
        ];
    }

    private function replyResource(MessageThreadReply $reply, User $user): array
    {
        return ['id'=>$reply->id,'body'=>$reply->body,'sender_id'=>$reply->sender_id,'sender_name'=>$reply->sender?->name,
            'is_me'=>(int)$reply->sender_id === (int)$user->id,'created_at'=>$reply->created_at?->toIso8601String(),
            'attachment'=>$reply->attachment_path ? ['name'=>$reply->attachment_name,'mime_type'=>$reply->attachment_mime,'size'=>$reply->attachment_size,'download_path'=>"/api/v1/messages/replies/{$reply->id}/attachment"] : null];
    }

    private function unreadCount(MessageThread $thread, int $userId): int
    {
        if ($thread->isBroadcast() && (int)$thread->initiated_by !== $userId) {
            return DB::table('message_thread_reads')->where('thread_id',$thread->id)->where('user_id',$userId)->whereNotNull('read_at')->exists() ? 0 : 1;
        }
        return $thread->relationLoaded('replies')
            ? $thread->replies->where('sender_id','!=',$userId)->where('is_read',false)->count()
            : $thread->replies()->where('sender_id','!=',$userId)->where('is_read',false)->count();
    }

    private function markThreadRead(MessageThread $thread, User $user): void
    {
        if ($thread->isBroadcast() && (int)$thread->initiated_by !== (int)$user->id) {
            DB::table('message_thread_reads')->updateOrInsert(
                ['thread_id'=>$thread->id,'user_id'=>$user->id],
                ['tenant_id'=>$user->tenant_id,'read_at'=>now(),'updated_at'=>now(),'created_at'=>now()]);
            return;
        }
        $thread->replies()->where('sender_id','!=',$user->id)->where('is_read',false)->update(['is_read'=>true,'read_at'=>now()]);
    }

    private function target(int $id, string $type, ?int $recipientId, string $name, string $supporting): array
    {
        return ['id'=>$id,'target_type'=>$type,'recipient_id'=>$recipientId,'student_id'=>null,'name'=>$name,'supporting'=>$supporting,'admission_number'=>$supporting,'class_name'=>null];
    }

    private function allowedStudents(User $user): Collection
    {
        if ($user->isStudent()) return collect([$user->student])->filter();
        if ($user->isParent()) {
            $guardian = Guardian::query()->where('user_id',$user->id)->first();
            return $guardian?->students()->where('students.status',Student::STATUS_ACTIVE)->get() ?? collect();
        }
        abort_unless($user->canAccessModule('messages'),403);
        return $this->classAccess->accessibleClasses($user)->get()
            ->flatMap(fn ($class) => $class->students()->where('status',Student::STATUS_ACTIVE)->get())->unique('id')->values();
    }
}

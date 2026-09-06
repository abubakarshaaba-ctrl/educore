<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Guardian;
use App\Models\MessageThread;
use App\Models\MessageThreadReply;
use App\Models\Student;
use App\Models\User;
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
    public function __construct(private readonly MobileClassAccessService $classAccess) {}

    public function index(Request $request)
    {
        $user = $request->user();
        $userId = (int) $user->id;
        $query = MessageThread::query();

        if (! $this->canOverseeAllThreads($user)) {
            $studentId = $user->student?->id;
            $guardianStudentIds = $this->guardianStudentIds($user);
            $query->where(function ($threads) use ($userId, $studentId, $guardianStudentIds): void {
                $threads->where('initiated_by', $userId)
                    ->orWhereHas('replies', fn ($replies) => $replies->where('sender_id', $userId))
                    ->when($studentId, fn ($scoped) => $scoped->orWhere('student_id', $studentId))
                    ->when($guardianStudentIds->isNotEmpty(), fn ($scoped) => $scoped->orWhereIn('student_id', $guardianStudentIds));
            });
        }

        $threads = $query
            ->with(['student', 'initiator', 'replies' => fn ($replies) => $replies->latest()])
            ->orderByDesc('updated_at')
            ->paginate(min(max((int) $request->integer('per_page', 20), 1), 50));

        return response()->json([
            'contract_version' => 2,
            'threads' => collect($threads->items())->map(fn (MessageThread $thread): array => $this->threadSummary($thread, $user))->values(),
            'unread_total' => collect($threads->items())->sum(fn (MessageThread $thread): int => $this->unreadCount($thread, $userId)),
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
        $user = $request->user();
        abort_unless($user->tenant_id && ($user->isStudent() || $user->isParent() || $user->canAccessModule('messages')), 403);
        $students = $this->allowedStudents($user);
        $students->each(fn (Student $student) => $student->loadMissing('currentClassArm.classLevel'));

        return response()->json([
            'recipients' => $students->map(fn (Student $student): array => [
                'student_id' => $student->id,
                'name' => $student->full_name,
                'admission_number' => $student->admission_number,
                'class_name' => trim(($student->currentClassArm?->classLevel?->name ?? '').' '.($student->currentClassArm?->name ?? '')) ?: null,
            ])->values(),
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $allowedIds = $this->allowedStudents($user)->pluck('id')->all();
        abort_if($allowedIds === [], 403, 'No student conversation is available to this account.');
        $data = $request->validate([
            'student_id' => ['required', 'integer', Rule::in($allowedIds)],
            'subject' => ['required', 'string', 'max:150'],
            'body' => ['required', 'string', 'max:10000'],
            'attachment' => ['nullable', 'file', 'max:5120', 'mimes:jpg,jpeg,png,pdf,doc,docx,xls,xlsx'],
        ]);

        [$thread, $reply] = DB::transaction(function () use ($request, $user, $data): array {
            $thread = MessageThread::create([
                'tenant_id' => $user->tenant_id,
                'student_id' => $data['student_id'],
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
            'thread' => $this->threadSummary($thread->fresh(['student', 'initiator', 'replies']), $user),
            'reply' => $this->replyResource($reply->fresh('sender'), $user),
        ], 201);
    }

    public function show(Request $request, MessageThread $thread)
    {
        $user = $request->user();
        $this->authorizeThread($thread, $user);
        $thread->replies()->where('sender_id', '!=', $user->id)->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => now()]);
        $thread->load(['student', 'initiator', 'replies.sender']);

        return response()->json([
            'thread' => [
                ...$this->threadSummary($thread, $user),
                'replies' => $thread->replies->map(fn (MessageThreadReply $reply): array => $this->replyResource($reply, $user))->values(),
            ],
        ]);
    }

    public function reply(Request $request, MessageThread $thread)
    {
        $user = $request->user();
        $this->authorizeThread($thread, $user);
        abort_if($thread->status !== 'open', 422, 'This thread has been closed.');
        $data = $request->validate([
            'body' => ['required', 'string', 'max:10000'],
            'attachment' => ['nullable', 'file', 'max:5120', 'mimes:jpg,jpeg,png,pdf,doc,docx,xls,xlsx'],
        ]);

        $reply = DB::transaction(function () use ($request, $thread, $user, $data): MessageThreadReply {
            $reply = $this->createReply($request, $thread, $user, $data['body']);
            $thread->touch();

            return $reply;
        });
        app(PushNotificationService::class)->notifyMessageThread($thread, $user, $data['body']);

        return response()->json(['reply' => $this->replyResource($reply->fresh('sender'), $user)], 201);
    }

    public function attachment(Request $request, MessageThreadReply $reply)
    {
        $thread = $reply->thread()->firstOrFail();
        $this->authorizeThread($thread, $request->user());
        abort_unless($reply->attachment_path && Storage::disk('local')->exists($reply->attachment_path), 404);

        return Storage::disk('local')->download(
            $reply->attachment_path,
            $reply->attachment_name ?: 'attachment',
            ['Content-Type' => $reply->attachment_mime ?: 'application/octet-stream'],
        );
    }

    private function createReply(Request $request, MessageThread $thread, User $user, string $body): MessageThreadReply
    {
        $attachment = [];
        if ($file = $request->file('attachment')) {
            $safeOriginal = Str::limit(preg_replace('/[^A-Za-z0-9._ -]/', '_', $file->getClientOriginalName()) ?: 'attachment', 180, '');
            $storedName = Str::uuid().'.'.($file->guessExtension() ?: 'bin');
            $path = Storage::disk('local')->putFileAs("mobile-message-attachments/{$user->tenant_id}/{$thread->id}", $file, $storedName);
            $attachment = [
                'attachment_path' => $path,
                'attachment_name' => $safeOriginal,
                'attachment_mime' => $file->getMimeType(),
                'attachment_size' => $file->getSize(),
            ];
        }

        return MessageThreadReply::create([
            'tenant_id' => $thread->tenant_id,
            'thread_id' => $thread->id,
            'sender_id' => $user->id,
            'body' => trim($body),
            ...$attachment,
        ]);
    }

    private function threadSummary(MessageThread $thread, User $user): array
    {
        $lastReply = $thread->replies->sortByDesc('created_at')->first();

        return [
            'id' => $thread->id,
            'subject' => $thread->subject,
            'status' => $thread->status,
            'type' => 'student',
            'student_name' => $thread->student?->full_name,
            'other_name' => (int) $thread->initiated_by === (int) $user->id ? 'School' : ($thread->initiator?->name ?? 'School'),
            'last_message' => $lastReply?->body,
            'unread_count' => $this->unreadCount($thread, (int) $user->id),
            'updated_at' => $thread->updated_at?->toIso8601String(),
            'deep_link' => ['type' => 'message_thread', 'id' => (string) $thread->id],
        ];
    }

    private function replyResource(MessageThreadReply $reply, User $user): array
    {
        return [
            'id' => $reply->id,
            'body' => $reply->body,
            'sender_id' => $reply->sender_id,
            'sender_name' => $reply->sender?->name,
            'is_me' => (int) $reply->sender_id === (int) $user->id,
            'created_at' => $reply->created_at?->toIso8601String(),
            'attachment' => $reply->attachment_path ? [
                'name' => $reply->attachment_name,
                'mime_type' => $reply->attachment_mime,
                'size' => $reply->attachment_size,
                'download_path' => "/api/v1/messages/replies/{$reply->id}/attachment",
            ] : null,
        ];
    }

    private function unreadCount(MessageThread $thread, int $userId): int
    {
        return $thread->relationLoaded('replies')
            ? $thread->replies->where('sender_id', '!=', $userId)->where('is_read', false)->count()
            : $thread->replies()->where('sender_id', '!=', $userId)->where('is_read', false)->count();
    }

    private function allowedStudents(User $user): Collection
    {
        if ($user->isStudent()) {
            return collect([$user->student])->filter();
        }
        if ($user->isParent()) {
            $guardian = Guardian::query()->where('user_id', $user->id)->first();

            return $guardian?->students()->where('students.status', Student::STATUS_ACTIVE)->get() ?? collect();
        }
        abort_unless($user->canAccessModule('messages'), 403);

        return $this->classAccess->accessibleClasses($user)
            ->get()->flatMap(fn ($class) => $class->students()->where('status', Student::STATUS_ACTIVE)->get())
            ->unique('id')->values();
    }

    private function guardianStudentIds(User $user): Collection
    {
        if (! $user->isParent()) {
            return collect();
        }
        $guardian = Guardian::query()->where('user_id', $user->id)->first();

        return $guardian?->students()->pluck('students.id') ?? collect();
    }

    private function canOverseeAllThreads(User $user): bool
    {
        return $user->isAdmin() || $user->isPrincipal() || $user->isCommunicationOfficer();
    }

    private function authorizeThread(MessageThread $thread, User $user): void
    {
        abort_unless((int) $thread->tenant_id === (int) $user->tenant_id, 404);
        $isConcernedStudent = $user->isStudent() && (int) $user->student?->id === (int) $thread->student_id;
        $isConcernedParent = $user->isParent() && $this->guardianStudentIds($user)->contains($thread->student_id);
        $isParticipant = (int) $thread->initiated_by === (int) $user->id
            || $thread->replies()->where('sender_id', $user->id)->exists();

        abort_unless($isParticipant || $isConcernedStudent || $isConcernedParent || $this->canOverseeAllThreads($user), 403, 'You are not a participant in this conversation.');
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CbtExam;
use App\Models\CbtQuestion;
use App\Models\CbtStudentSession;
use App\Services\Cbt\CbtIntegrityService;
use App\Services\Cbt\CbtStudentAttemptService;
use App\Services\Mobile\MobileIdempotencyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class MobileCbtController extends Controller
{
    public function __construct(
        private readonly CbtStudentAttemptService $attempts,
        private readonly CbtIntegrityService $integrity,
        private readonly MobileIdempotencyService $idempotency,
    ) {}

    public function index(Request $request)
    {
        $user = $request->user();
        $student = $this->attempts->studentFor($user);
        $exams = $this->attempts->availableExams($student)->map(function (CbtExam $exam) use ($student) {
            $preflight = $this->attempts->preflight($exam, $student);

            return $preflight;
        })->values();

        return response()->json(['contract_version' => 1, 'generated_at' => now()->toIso8601String(), 'exams' => $exams]);
    }

    public function preflight(Request $request, CbtExam $exam)
    {
        $student = $this->attempts->studentFor($request->user());

        return response()->json(['contract_version' => 1, 'generated_at' => now()->toIso8601String()] + $this->attempts->preflight($exam, $student));
    }

    public function begin(Request $request, CbtExam $exam)
    {
        $data = $request->validate(['request_id' => ['required', 'uuid'], 'integrity_acknowledged' => ['accepted']]);
        $user = $request->user();
        $student = $this->attempts->studentFor($user);
        $response = $this->idempotency->execute($user, "cbt.exam.{$exam->id}.begin", $data['request_id'], $data, function () use ($exam, $student, $request) {
            $session = $this->attempts->begin($exam, $student, $request->ip(), $request->userAgent());

            return $this->attempts->attemptPayload($session);
        });

        return response()->json(['contract_version' => 1] + $response);
    }

    public function show(Request $request, CbtStudentSession $session)
    {
        $this->guardSession($request, $session);

        return response()->json(['contract_version' => 1] + $this->attempts->attemptPayload($session));
    }

    public function save(Request $request, CbtStudentSession $session)
    {
        $this->guardSession($request, $session);
        $data = $request->validate([
            'request_id' => ['required', 'uuid'], 'version' => ['required', 'string', 'max:80'],
            'answers' => ['nullable', 'array'], 'answers.*' => ['nullable', 'string', 'max:5000'],
            'flagged_questions' => ['nullable', 'array'], 'flagged_questions.*' => ['integer'],
        ]);
        $user = $request->user();
        $response = $this->idempotency->execute($user, "cbt.session.{$session->id}.save", $data['request_id'], $data, function () use ($session, $data) {
            $saved = $this->attempts->save($session, $data['answers'] ?? [], $data['flagged_questions'] ?? [], $data['version']);

            return $this->attempts->attemptPayload($saved);
        });

        return response()->json(['contract_version' => 1] + $response);
    }

    public function integrity(Request $request, CbtStudentSession $session)
    {
        $this->guardSession($request, $session);
        $data = $request->validate([
            'event_uuid' => ['required', 'uuid'],
            'event_type' => ['required', Rule::in(['focus_lost', 'window_blur', 'visibility_hidden', 'fullscreen_exit', 'connectivity_lost', 'connectivity_restored'])],
            'metadata' => ['nullable', 'array', 'max:20'], 'metadata.*' => ['nullable', 'string', 'max:500'],
            'answers' => ['nullable', 'array'], 'answers.*' => ['nullable', 'string', 'max:5000'],
        ]);
        $result = $this->integrity->record(
            $session, $data['event_uuid'], $data['event_type'], $data['metadata'] ?? [],
            $data['answers'] ?? [], [], $request->ip(), $request->userAgent()
        );

        return response()->json(['contract_version' => 1] + $result);
    }

    public function submit(Request $request, CbtStudentSession $session)
    {
        $this->guardSession($request, $session);
        $data = $request->validate([
            'request_id' => ['required', 'uuid'], 'answers' => ['nullable', 'array'],
            'answers.*' => ['nullable', 'string', 'max:5000'],
        ]);
        $user = $request->user();
        $response = $this->idempotency->execute($user, "cbt.session.{$session->id}.submit", $data['request_id'], $data, function () use ($session, $data) {
            $final = $this->attempts->submit($session, $data['answers'] ?? []);

            return ['session' => $this->attempts->attemptPayload($final)['session'], 'result' => $this->attempts->resultPayload($final)];
        });

        return response()->json(['contract_version' => 1] + $response);
    }

    public function image(Request $request, CbtStudentSession $session, CbtQuestion $question)
    {
        $this->guardSession($request, $session);
        abort_unless(in_array($question->id, $session->questionIds(), true) && $question->image_path, 404);
        $path = ltrim($question->image_path, '/');
        abort_unless(Storage::disk('public')->exists($path), 404);

        return Storage::disk('public')->response($path, basename($path));
    }

    private function guardSession(Request $request, CbtStudentSession $session): void
    {
        $student = $this->attempts->studentFor($request->user());
        abort_unless((int) $session->tenant_id === (int) $student->tenant_id && (int) $session->student_id === (int) $student->id, 404);
    }
}

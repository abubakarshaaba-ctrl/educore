<?php

namespace App\Http\Controllers;

use App\Models\CbtExam;
use App\Models\CbtIntegrityEvent;
use App\Models\CbtQuestionScore;
use App\Models\CbtRetakeAuthorization;
use App\Models\CbtSectionAttempt;
use App\Models\CbtStudentSession;
use App\Models\Student;
use App\Models\Scopes\TenantScope;
use App\Services\Cbt\CbtLanAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

/**
 * LAN CBT deployment with versioned package and two-way result/schedule sync.
 *
 * Flow:
 *  1. While online, a staff member exports a self-contained package for one
 *     exam (tenant/term/class/subject/bank/questions/students/users).
 *  2. That package is imported into a second copy of this same app running
 *     locally (e.g. via XAMPP on a laptop with no internet), which becomes
 *     the exam server for the LAN. Students connect over WiFi and take the
 *     exam through the normal CBT screens — no new code needed there.
 *  3. After the exam, the local instance keeps trying (silently, in the
 *     background) to push finished sessions to the cloud's /api/lan/sync
 *     endpoint. Once the laptop regains internet, the push succeeds and
 *     results land back in the real database automatically.
 */
class CbtLanController extends Controller
{
    public const PACKAGE_VERSION = 3;
    public const APPLICATION_RELEASE = '2026.08.21-lan-admission.1';

    /** Tables carried in an export package, in FK-safe import order. */
    private const TABLES = [
        'tenants', 'academic_sessions', 'terms', 'class_levels', 'class_arms',
        'subjects', 'assessment_types', 'cbt_question_banks', 'cbt_questions',
        'users', 'students', 'cbt_exams', 'cbt_exam_class_arms', 'cbt_exam_sections', 'cbt_exam_section_questions',
    ];

    /** Package history keys mapped to their destination tables. */
    private const HISTORY_TABLES = [
        'retake_authorizations' => 'cbt_retake_authorizations',
        'sessions' => 'cbt_student_sessions',
        'section_attempts' => 'cbt_section_attempts',
        'question_scores' => 'cbt_question_scores',
        'integrity_events' => 'cbt_integrity_events',
    ];

    private const EXAM_SNAPSHOT_FIELDS = [
        'status', 'scheduled_start', 'scheduled_end', 'duration_minutes',
        'shuffle_questions', 'shuffle_options', 'malpractice_enabled',
        'focus_loss_policy', 'max_focus_losses', 'require_fullscreen',
        'retake_policy', 'strict_marks_validation', 'updated_at',
    ];

    /** Attempt states that may originate from an exam-day LAN server. */
    private const LAN_SYNCABLE_STATUSES = [
        'submitted', 'graded', 'completed', 'timed_out', 'auto_submitted', 'expired',
    ];

    private function authorize404(): void
    {
        $user = Auth::user();
        abort_unless($user && ($user->isSuperAdmin() || $user->isAdmin()), 403, 'Only administrators can access LAN Mode.');
    }

    private function cloudUrl(): string
    {
        return rtrim(config('cbt_lan.cloud_url', 'https://educoreng.online'), '/');
    }

    // ── Dashboard ───────────────────────────────────────────────────────
    public function dashboard(CbtLanAccessService $lanAccess)
    {
        $this->authorize404();

        // Only admins reach this point (see authorize404), so no per-teacher
        // subject scoping is needed — admins see every exam bank.
        $exams = CbtExam::query()
            ->with('questionBank.subject', 'classArm', 'classArms.classLevel')
            ->latest()
            ->get();

        $pendingCounts = CbtStudentSession::whereIn('cbt_exam_id', $exams->pluck('id'))
            ->whereIn('status', self::LAN_SYNCABLE_STATUSES)
            ->whereNotNull('submitted_at')
            ->whereNull('last_synced_at')
            ->selectRaw('cbt_exam_id, count(*) as c')
            ->groupBy('cbt_exam_id')
            ->pluck('c', 'cbt_exam_id');

        $lanRelease = self::APPLICATION_RELEASE;
        $studentAccessUrl = $lanAccess->isAvailable(request())
            ? route('cbt.lan.student.access')
            : null;

        return view('cbt.lan', compact('exams', 'pendingCounts', 'lanRelease', 'studentAccessUrl'));
    }

    // ── Export package (run on the CLOUD instance, while online) ────────
    public function exportPackage(CbtExam $exam)
    {
        $this->authorize404();
        $exam->load('questionBank', 'classArm', 'classArms.classLevel');
        abort_unless($exam->question_bank_id && $exam->classArm, 404, 'Exam is missing its bank/class link.');

        $tenantId  = $exam->tenant_id;
        $classArms = $exam->classArms->isNotEmpty() ? $exam->classArms : collect([$exam->classArm]);
        $classArmIds = $classArms->pluck('id');
        $bank      = $exam->questionBank;

        $studentIds = DB::table('students')->whereIn('current_class_arm_id', $classArmIds)->pluck('id');
        $userIds    = DB::table('students')->whereIn('current_class_arm_id', $classArmIds)->pluck('user_id')->filter();

        $rows = [];
        $rows['tenants']            = DB::table('tenants')->where('id', $tenantId)->get();
        $rows['terms']              = DB::table('terms')->where('id', $exam->term_id)->get();
        $sessionIds                 = $rows['terms']->pluck('session_id')->filter();
        $rows['academic_sessions']  = Schema::hasTable('academic_sessions') && $sessionIds->isNotEmpty()
            ? DB::table('academic_sessions')->whereIn('id', $sessionIds)->get() : collect();
        $rows['class_levels']       = DB::table('class_levels')->whereIn('id', $classArms->pluck('class_level_id')->unique())->get();
        $rows['class_arms']         = DB::table('class_arms')->whereIn('id', $classArmIds)->get()
            ->map(function ($r) { $r->form_tutor_id = null; return $r; }); // form tutor account isn't exported
        $rows['subjects']           = DB::table('subjects')->where('id', $bank->subject_id)->get();
        $rows['assessment_types']   = $exam->assessment_type_id
            ? DB::table('assessment_types')->where('id', $exam->assessment_type_id)->get() : collect();
        $rows['cbt_question_banks'] = DB::table('cbt_question_banks')->where('id', $bank->id)->get();
        $rows['cbt_questions']      = DB::table('cbt_questions')->where('question_bank_id', $bank->id)->get();
        $rows['users']              = DB::table('users')->whereIn('id', $userIds)->get();
        $rows['students']           = DB::table('students')->whereIn('id', $studentIds)->get();
        $rows['cbt_exams']          = DB::table('cbt_exams')->where('id', $exam->id)->get()->map(function ($row) { $row->created_by = null; return $row; });
        $rows['cbt_exam_class_arms'] = DB::table('cbt_exam_class_arms')->where('cbt_exam_id', $exam->id)->get();
        $rows['cbt_exam_sections']  = DB::table('cbt_exam_sections')->where('cbt_exam_id', $exam->id)->get()->map(function ($row) { $row->created_by = null; return $row; });
        $rows['cbt_exam_section_questions'] = DB::table('cbt_exam_section_questions')->where('cbt_exam_id', $exam->id)->get();

        $historySessions = DB::table('cbt_student_sessions')
            ->where('cbt_exam_id', $exam->id)
            ->whereIn('student_id', $studentIds)
            ->whereIn('status', CbtStudentSession::FINAL_STATUSES)
            ->get()
            ->map(function ($row) {
                $row->marked_by = null;
                $row->last_synced_at = now();
                return $row;
            });
        $historySessionIds = $historySessions->pluck('id');
        $retakeAuthorizations = DB::table('cbt_retake_authorizations')
            ->where('cbt_exam_id', $exam->id)
            ->whereIn('student_id', $studentIds)
            ->get()
            ->map(function ($row) {
                unset($row->authorized_by, $row->revoked_by);
                return $row;
            });
        $history = [
            'retake_authorizations' => $retakeAuthorizations->values(),
            'sessions' => $historySessions->values(),
            'section_attempts' => $historySessionIds->isEmpty() ? collect() : DB::table('cbt_section_attempts')->whereIn('cbt_student_session_id', $historySessionIds)->get(),
            'question_scores' => $historySessionIds->isEmpty() ? collect() : DB::table('cbt_question_scores')->whereIn('cbt_student_session_id', $historySessionIds)->get()->map(function ($row) { $row->scored_by = null; return $row; }),
            'integrity_events' => $historySessionIds->isEmpty() ? collect() : DB::table('cbt_integrity_events')->whereIn('cbt_student_session_id', $historySessionIds)->get(),
        ];

        $exportedAt = now();
        $token = Crypt::encryptString($tenantId . '|' . $exam->id . '|' . $exportedAt->copy()->addDays(90)->timestamp);
        $exam->update(['lan_sync_token' => $token, 'lan_exported_at' => $exportedAt]);
        $rows['cbt_exams']->each(function ($row) use ($token, $exportedAt) {
            $row->lan_sync_token = $token;
            $row->lan_exported_at = $exportedAt;
        });

        $payload = [
            'package_version' => self::PACKAGE_VERSION,
            'required_lan_release' => self::APPLICATION_RELEASE,
            'exam_id'         => $exam->id,
            'tenant_id'       => $tenantId,
            'sync_token'      => $token,
            'exported_at'     => $exportedAt->toIso8601String(),
            'tables'          => collect($rows)->map(fn ($c) => $c->values())->toArray(),
            'history'         => collect($history)->map(fn ($c) => $c->values())->toArray(),
        ];

        $filename = 'lan-exam-' . $exam->id . '-' . now()->format('Ymd-His') . '.json';

        return response(json_encode($payload, JSON_PRETTY_PRINT))
            ->header('Content-Type', 'application/json')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    // ── Import package (run on the LOCAL/LAN instance) ──────────────────
    public function importPackage(Request $request, CbtLanAccessService $lanAccess)
    {
        $this->authorize404();
        $request->validate(['package' => ['required', 'file']]);

        $raw     = file_get_contents($request->file('package')->getRealPath());
        $payload = json_decode($raw, true);

        if (!is_array($payload) || empty($payload['tables']) || empty($payload['exam_id'])) {
            return back()->withErrors(['error' => 'That file is not a valid LAN exam package.']);
        }
        if ((int) ($payload['package_version'] ?? 0) !== self::PACKAGE_VERSION
            || (string) ($payload['required_lan_release'] ?? '') !== self::APPLICATION_RELEASE) {
            return back()->withErrors(['error' => 'This package requires a different EduCore LAN release. Update the LAN installation, then import the package again.']);
        }
        if ($message = $this->packageSchemaError($payload)) {
            return back()->withErrors(['error' => $message]);
        }

        $activateStudentAccess = $lanAccess->isPrivateHost($request);

        DB::transaction(function () use ($payload, $lanAccess, $activateStudentAccess) {
            foreach (self::TABLES as $table) {
                foreach ($payload['tables'][$table] ?? [] as $row) {
                    $row = (array) $row;
                    if (!isset($row['id'])) continue;
                    DB::table($table)->updateOrInsert(['id' => $row['id']], $row);
                }
            }
            $this->importLanHistory((array) ($payload['history'] ?? []), (int) Auth::id());
            DB::table('cbt_exams')->where('id', $payload['exam_id'])->update([
                'lan_sync_token' => $payload['sync_token'],
                'lan_exported_at' => $payload['exported_at'] ?? now(),
            ]);
            if ($activateStudentAccess) {
                $lanAccess->provisionMissingStudentAccounts((int) $payload['exam_id']);
            }
        });

        if ($activateStudentAccess) {
            $lanAccess->activateImportedPackage($payload);
        }

        $exam = CbtExam::find($payload['exam_id']);

        return redirect()->route('cbt.lan')->with('success',
            'Package imported. "' . ($exam->title ?? 'Exam') . '" is ready — students can now log in on this LAN and take it.');
    }

    // ── Push finished sessions to the cloud (run on the LOCAL instance) ──
    public function syncNow(CbtExam $exam)
    {
        $this->authorize404();

        if (!$exam->lan_sync_token) {
            return response()->json(['status' => 'no_token', 'message' => 'This exam was not imported from a LAN package.']);
        }

        $sessions = CbtStudentSession::where('cbt_exam_id', $exam->id)
            ->whereIn('status', self::LAN_SYNCABLE_STATUSES)
            ->whereNotNull('submitted_at')
            ->whereNull('last_synced_at')
            ->get();

        try {
            $resp = Http::timeout(15)->post($this->cloudUrl() . '/api/lan/sync', [
                'token'    => $exam->lan_sync_token,
                'client' => ['package_version' => self::PACKAGE_VERSION, 'application_release' => self::APPLICATION_RELEASE],
                'sessions' => $sessions->map(fn ($s) => [
                    'id'             => $s->id,
                    'student_id'     => $s->student_id,
                    'attempt_number' => $s->attempt_number,
                    'is_authorized_attempt' => $s->is_authorized_attempt,
                    'is_active_result' => $s->is_active_result,
                    'retake_authorization_id' => $s->retake_authorization_id,
                    'integrity_acknowledged_at' => optional($s->integrity_acknowledged_at)->toIso8601String(),
                    'focus_loss_count' => $s->focus_loss_count,
                    'question_order' => $s->question_order,
                    'answers'        => $s->answers,
                    'essay_answers'  => $s->essay_answers,
                    'flagged_questions' => $s->flagged_questions,
                    'started_at'     => optional($s->started_at)->toIso8601String(),
                    'submitted_at'   => optional($s->submitted_at)->toIso8601String(),
                    'score'          => $s->score,
                    'raw_score'      => $s->raw_score,
                    'maximum_score'  => $s->maximum_score,
                    'percentage'     => $s->percentage,
                    'status'         => $s->status,
                    'manual_scores'  => $s->manual_scores,
                    'submission_reason' => $s->submission_reason,
                    'grading_completed_at' => optional($s->grading_completed_at)->toIso8601String(),
                    'updated_at' => optional($s->updated_at)->toIso8601String(),
                    'section_attempts' => DB::table('cbt_section_attempts')->where('cbt_student_session_id', $s->id)->get()->map(fn ($row) => (array) $row)->all(),
                    'question_scores' => DB::table('cbt_question_scores')->where('cbt_student_session_id', $s->id)->get()->map(fn ($row) => (array) $row)->all(),
                    'integrity_events' => DB::table('cbt_integrity_events')->where('cbt_student_session_id', $s->id)->get()->map(fn ($row) => (array) $row)->all(),
                ])->values(),
            ]);
        } catch (\Throwable $e) {
            return response()->json(['status' => 'offline']);
        }

        if (!$resp->successful()) {
            return response()->json(['status' => 'rejected', 'message' => $resp->json('message') ?? 'Cloud rejected the sync.']);
        }

        $accepted = $resp->json('accepted', []);
        if ($accepted) {
            CbtStudentSession::whereIn('id', $accepted)->update(['last_synced_at' => now()]);
        }
        $examRefreshed = $this->applyExamSnapshot($exam, (array) $resp->json('exam', []));
        $this->applyRetakeSnapshots((array) $resp->json('retake_authorizations', []), (int) Auth::id());
        $rejected = (array) $resp->json('rejected', []);

        return response()->json([
            'status' => $rejected ? 'partial' : ($accepted ? 'synced' : ($examRefreshed ? 'refreshed' : 'nothing_to_sync')),
            'count' => count($accepted),
            'rejected' => $rejected,
            'message' => $rejected ? count($rejected).' attempt(s) require attention.' : null,
        ]);
    }

    // ── Receive a sync push (runs on the CLOUD instance, no session auth) ─
    public function apiSync(Request $request)
    {
        $request->validate([
            'token' => ['required', 'string'],
            // An empty list is valid: the LAN machine may only be polling
            // for a reschedule, security setting or retake authorization.
            'sessions' => ['present', 'array'],
            'client' => ['nullable', 'array'],
        ]);

        $client = (array) $request->input('client', []);
        if ((int) ($client['package_version'] ?? 0) !== self::PACKAGE_VERSION
            || (string) ($client['application_release'] ?? '') !== self::APPLICATION_RELEASE) {
            return response()->json([
                'message' => 'The LAN installation is not compatible with this EduCore release. Update it before synchronizing.',
                'server_release' => self::APPLICATION_RELEASE,
            ], 409);
        }

        try {
            $decoded = Crypt::decryptString($request->input('token'));
            [$tenantId, $examId, $expiry] = array_pad(explode('|', $decoded), 3, null);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Invalid sync token.'], 422);
        }

        if (!$tenantId || !$examId || (int) $expiry < now()->timestamp) {
            return response()->json(['message' => 'Sync token expired or malformed.'], 422);
        }

        $exam = CbtExam::withoutGlobalScope(TenantScope::class)
            ->where('id', $examId)->where('tenant_id', $tenantId)->first();

        if (!$exam) {
            return response()->json(['message' => 'Exam not found for this token.'], 404);
        }

        $accepted = [];
        $rejected = [];
        $validSectionIds = DB::table('cbt_exam_sections')->where('cbt_exam_id', $examId)->pluck('id')->map(fn ($id) => (int) $id);
        $validQuestionIds = DB::table('cbt_exam_section_questions')->where('cbt_exam_id', $examId)->pluck('cbt_question_id')->map(fn ($id) => (int) $id);
        $assignedClassArmIds = DB::table('cbt_exam_class_arms')->where('cbt_exam_id', $examId)->pluck('class_arm_id')->map(fn ($id) => (int) $id);
        if ($assignedClassArmIds->isEmpty() && $exam->class_arm_id) $assignedClassArmIds->push((int) $exam->class_arm_id);

        foreach ($request->input('sessions', []) as $incoming) {
            $lanSessionId = (int) ($incoming['id'] ?? 0);
            $studentId = (int) ($incoming['student_id'] ?? 0);
            $attemptNumber = (int) ($incoming['attempt_number'] ?? 1);
            $incomingStatus = (string) ($incoming['status'] ?? '');
            $reject = function (string $reason) use (&$rejected, $lanSessionId): void {
                $rejected[] = ['id' => $lanSessionId, 'reason' => $reason];
            };

            if (!$lanSessionId || !$studentId || $attemptNumber < 1) { $reject('Malformed attempt identity.'); continue; }
            if (!in_array($incomingStatus, self::LAN_SYNCABLE_STATUSES, true) || empty($incoming['submitted_at'])) {
                $reject('Only completed attempts can be synchronized.');
                continue;
            }

            $student = Student::withoutGlobalScope(TenantScope::class)
                ->where('id', $studentId)->where('tenant_id', $tenantId)->first();
            if (!$student || !$assignedClassArmIds->contains((int) $student->current_class_arm_id)) {
                $reject('Student is not assigned to this examination.');
                continue;
            }

            $authorization = null;
            if ($attemptNumber > 1) {
                $authorization = CbtRetakeAuthorization::withoutGlobalScope(TenantScope::class)
                    ->where('tenant_id', $tenantId)->where('cbt_exam_id', $examId)
                    ->where('student_id', $studentId)->where('attempt_number', $attemptNumber)
                    ->whereNull('revoked_at')->first();
                if (!$authorization) { $reject('Retake authorization is missing or revoked.'); continue; }
            }

            $existing = CbtStudentSession::withoutGlobalScope(TenantScope::class)
                ->where('tenant_id', $tenantId)
                ->where('cbt_exam_id', $examId)
                ->where('student_id', $studentId)
                ->where('attempt_number', $attemptNumber)
                ->first();

            if ($existing && in_array($existing->status, ['invalidated', 'cancelled'], true)) {
                $reject('The cloud attempt is locked as '.$existing->status.'.');
                continue;
            }

            $incomingIsGraded = $incomingStatus === 'graded' && !empty($incoming['grading_completed_at']);
            $mayUpdateScores = !$existing || !$existing->isFinal() || ($incomingIsGraded && !$existing->isGraded());

            $data = [
                'tenant_id'          => $tenantId,
                'cbt_exam_id'        => $examId,
                'student_id'         => $studentId,
                'attempt_number'     => $attemptNumber,
                'is_authorized_attempt' => $attemptNumber === 1 || (bool) $authorization,
                'retake_authorization_id' => $authorization?->id,
                'integrity_acknowledged_at' => $incoming['integrity_acknowledged_at'] ?? null,
                'focus_loss_count'   => (int) ($incoming['focus_loss_count'] ?? 0),
                'question_order'     => $incoming['question_order'] ?? [],
                'answers'            => $incoming['answers'] ?? [],
                'essay_answers'      => $incoming['essay_answers'] ?? [],
                'flagged_questions'  => $incoming['flagged_questions'] ?? [],
                'started_at'         => $incoming['started_at'] ?? null,
                'submitted_at'       => $incoming['submitted_at'] ?? null,
                'score'              => $incoming['score'] ?? null,
                'raw_score'          => $incoming['raw_score'] ?? $incoming['score'] ?? null,
                'maximum_score'      => $incoming['maximum_score'] ?? $exam->total_marks,
                'percentage'         => $incoming['percentage'] ?? null,
                'status'             => $incomingStatus,
                'manual_scores'      => $incomingIsGraded ? ($incoming['manual_scores'] ?? []) : null,
                'marked_by'          => null,
                'submission_reason'  => $incoming['submission_reason'] ?? 'lan_sync',
                'grading_completed_at' => $incoming['grading_completed_at'] ?? null,
                'last_synced_at'     => now(),
            ];

            if ($existing && $mayUpdateScores) {
                $existing->update($data);
                $cloudSession = $existing->fresh();
            } elseif (!$existing) {
                $cloudSession = CbtStudentSession::withoutGlobalScope(TenantScope::class)->create($data);
            } else {
                $cloudSession = $existing;
            }

            if ($mayUpdateScores) {
                foreach ((array) ($incoming['section_attempts'] ?? []) as $row) {
                    $row = (array) $row;
                    $sectionId = (int) ($row['cbt_exam_section_id'] ?? 0);
                    if (!$validSectionIds->contains($sectionId)) continue;
                    unset($row['id']);
                    $row['tenant_id'] = $tenantId;
                    $row['cbt_student_session_id'] = $cloudSession->id;
                    CbtSectionAttempt::withoutGlobalScope(TenantScope::class)->updateOrCreate([
                        'cbt_student_session_id' => $cloudSession->id,
                        'cbt_exam_section_id' => $sectionId,
                    ], $row);
                }
                foreach ((array) ($incoming['question_scores'] ?? []) as $row) {
                    $row = (array) $row;
                    $questionId = (int) ($row['cbt_question_id'] ?? 0);
                    if (!$validQuestionIds->contains($questionId)) continue;
                    unset($row['id']);
                    $row['tenant_id'] = $tenantId;
                    $row['cbt_student_session_id'] = $cloudSession->id;
                    $row['scored_by'] = null;
                    CbtQuestionScore::withoutGlobalScope(TenantScope::class)->updateOrCreate([
                        'cbt_student_session_id' => $cloudSession->id,
                        'cbt_question_id' => $questionId,
                    ], $row);
                }
            }

            foreach ((array) ($incoming['integrity_events'] ?? []) as $row) {
                $row = (array) $row;
                if (empty($row['event_uuid'])) continue;
                $owned = CbtIntegrityEvent::withoutGlobalScope(TenantScope::class)->where('event_uuid', $row['event_uuid'])->first();
                if ($owned && (int) $owned->cbt_student_session_id !== (int) $cloudSession->id) continue;
                unset($row['id']);
                $row['tenant_id'] = $tenantId;
                $row['cbt_exam_id'] = (int) $examId;
                $row['student_id'] = $studentId;
                $row['cbt_student_session_id'] = $cloudSession->id;
                CbtIntegrityEvent::withoutGlobalScope(TenantScope::class)->updateOrCreate(['event_uuid' => $row['event_uuid']], $row);
            }

            if ($authorization && !$authorization->used_at) $authorization->update(['used_at' => $incoming['started_at'] ?? now()]);
            if ($cloudSession->grading_completed_at) app(\App\Services\Cbt\CbtResultSyncService::class)->sync($cloudSession);

            $accepted[] = $lanSessionId;
        }

        return response()->json([
            'status' => 'ok',
            'server_release' => self::APPLICATION_RELEASE,
            'accepted' => $accepted,
            'rejected' => $rejected,
            'exam' => $this->examSnapshot($exam->fresh()),
            'retake_authorizations' => $this->retakeSnapshots($exam),
        ]);
    }

    private function packageSchemaError(array $payload): ?string
    {
        if (empty($payload['sync_token']) || (int) ($payload['tenant_id'] ?? 0) < 1) {
            return 'The LAN package identity is incomplete.';
        }
        $examRows = collect($payload['tables']['cbt_exams'] ?? []);
        $examRow = $examRows->first(function ($row) use ($payload) {
            $candidate = (array) $row;
            return (int) ($candidate['id'] ?? 0) === (int) $payload['exam_id'];
        });
        if (!$examRow || (int) ($examRow['tenant_id'] ?? 0) !== (int) $payload['tenant_id']) {
            return 'The LAN package exam identity is inconsistent.';
        }
        if (!hash_equals((string) $payload['sync_token'], (string) ($examRow['lan_sync_token'] ?? ''))) {
            return 'The LAN package synchronization identity is inconsistent.';
        }

        foreach (self::TABLES as $table) {
            if (!array_key_exists($table, (array) $payload['tables'])) {
                return "The LAN package is missing the {$table} dataset.";
            }
            $rows = (array) ($payload['tables'][$table] ?? []);
            if (!$rows) continue;
            if (!Schema::hasTable($table)) return "The LAN database is missing the {$table} table. Update EduCore first.";
            $columns = Schema::getColumnListing($table);
            foreach ($rows as $row) {
                $unknown = array_diff(array_keys((array) $row), $columns);
                if ($unknown) return "The LAN database is outdated for {$table}. Update EduCore first.";
            }
        }
        foreach (self::HISTORY_TABLES as $key => $table) {
            $rows = (array) ($payload['history'][$key] ?? []);
            if (!$rows) continue;
            if (!Schema::hasTable($table)) return "The LAN database is missing the {$table} table. Update EduCore first.";
            $columns = Schema::getColumnListing($table);
            foreach ($rows as $row) {
                $unknown = array_diff(array_keys((array) $row), $columns);
                if ($unknown) return "The LAN database is outdated for {$table}. Update EduCore first.";
            }
        }

        return null;
    }

    private function importLanHistory(array $history, int $localAdminId): void
    {
        $this->applyRetakeSnapshots((array) ($history['retake_authorizations'] ?? []), $localAdminId);
        $sessionMap = [];

        foreach ((array) ($history['sessions'] ?? []) as $row) {
            $row = (array) $row;
            $incomingId = (int) ($row['id'] ?? 0);
            if (!$incomingId) continue;
            $existing = DB::table('cbt_student_sessions')
                ->where('tenant_id', $row['tenant_id'])->where('cbt_exam_id', $row['cbt_exam_id'])
                ->where('student_id', $row['student_id'])->where('attempt_number', $row['attempt_number'])->first();
            // Never replace (or import dependent score rows over) a newer,
            // unsynchronized attempt that already exists on this LAN server.
            if ($existing && $existing->last_synced_at === null) continue;
            $localId = $existing ? (int) $existing->id : $incomingId;
            unset($row['id']);
            $row['marked_by'] = null;
            $row['last_synced_at'] = now();
            DB::table('cbt_student_sessions')->updateOrInsert(['id' => $localId], $row);
            $sessionMap[$incomingId] = $localId;
        }

        foreach ((array) ($history['section_attempts'] ?? []) as $row) {
            $row = (array) $row;
            $sessionId = $sessionMap[(int) ($row['cbt_student_session_id'] ?? 0)] ?? null;
            if (!$sessionId) continue;
            unset($row['id']);
            $row['cbt_student_session_id'] = $sessionId;
            DB::table('cbt_section_attempts')->updateOrInsert([
                'cbt_student_session_id' => $sessionId,
                'cbt_exam_section_id' => $row['cbt_exam_section_id'],
            ], $row);
        }
        foreach ((array) ($history['question_scores'] ?? []) as $row) {
            $row = (array) $row;
            $sessionId = $sessionMap[(int) ($row['cbt_student_session_id'] ?? 0)] ?? null;
            if (!$sessionId) continue;
            unset($row['id']);
            $row['cbt_student_session_id'] = $sessionId;
            $row['scored_by'] = null;
            DB::table('cbt_question_scores')->updateOrInsert([
                'cbt_student_session_id' => $sessionId,
                'cbt_question_id' => $row['cbt_question_id'],
            ], $row);
        }
        foreach ((array) ($history['integrity_events'] ?? []) as $row) {
            $row = (array) $row;
            $sessionId = $sessionMap[(int) ($row['cbt_student_session_id'] ?? 0)] ?? null;
            if (!$sessionId || empty($row['event_uuid'])) continue;
            unset($row['id']);
            $row['cbt_student_session_id'] = $sessionId;
            DB::table('cbt_integrity_events')->updateOrInsert(['event_uuid' => $row['event_uuid']], $row);
        }
    }

    private function applyExamSnapshot(CbtExam $exam, array $snapshot): bool
    {
        if ((int) ($snapshot['id'] ?? 0) !== (int) $exam->id || (int) ($snapshot['tenant_id'] ?? 0) !== (int) $exam->tenant_id) return false;
        $updates = collect($snapshot)->only(self::EXAM_SNAPSHOT_FIELDS)->all();
        if (!$updates) return false;
        $before = $exam->only(array_keys($updates));
        DB::table('cbt_exams')->where('id', $exam->id)->where('tenant_id', $exam->tenant_id)->update($updates);
        $exam->refresh();
        return $before != $exam->only(array_keys($updates));
    }

    private function applyRetakeSnapshots(array $rows, int $localAdminId): void
    {
        $allowed = ['id', 'tenant_id', 'cbt_exam_id', 'student_id', 'attempt_number', 'reason', 'authorized_at', 'used_at', 'revoked_at', 'revocation_reason', 'created_at', 'updated_at'];
        foreach ($rows as $row) {
            $row = collect((array) $row)->only($allowed)->all();
            if (empty($row['id']) || empty($row['cbt_exam_id']) || empty($row['student_id'])) continue;
            $conflict = DB::table('cbt_retake_authorizations')->where('id', $row['id'])->first();
            if ($conflict && ((int) $conflict->cbt_exam_id !== (int) $row['cbt_exam_id'] || (int) $conflict->student_id !== (int) $row['student_id'])) continue;
            // A student may already have opened the authorized retake on the
            // offline server. Do not revert that local consumption merely
            // because the cloud has not received the finished attempt yet.
            if ($conflict && $conflict->used_at && empty($row['used_at'])) $row['used_at'] = $conflict->used_at;
            $row['authorized_by'] = $localAdminId;
            $row['revoked_by'] = null;
            DB::table('cbt_retake_authorizations')->updateOrInsert(['id' => $row['id']], $row);
        }
    }

    private function examSnapshot(CbtExam $exam): array
    {
        return ['id' => $exam->id, 'tenant_id' => $exam->tenant_id]
            + $exam->only(self::EXAM_SNAPSHOT_FIELDS);
    }

    private function retakeSnapshots(CbtExam $exam): array
    {
        return DB::table('cbt_retake_authorizations')->where('cbt_exam_id', $exam->id)->get()->map(function ($row) {
            unset($row->authorized_by, $row->revoked_by);
            return (array) $row;
        })->all();
    }
}

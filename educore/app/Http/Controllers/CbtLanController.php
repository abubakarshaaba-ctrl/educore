<?php

namespace App\Http\Controllers;

use App\Models\CbtExam;
use App\Models\CbtStudentSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

/**
 * LAN CBT deployment (v1 — exam-taking only).
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
    /** Tables carried in an export package, in FK-safe import order. */
    private const TABLES = [
        'tenants', 'academic_sessions', 'terms', 'class_levels', 'class_arms',
        'subjects', 'assessment_types', 'cbt_question_banks', 'cbt_questions',
        'users', 'students', 'cbt_exams',
    ];

    private function authorize404(): void
    {
        abort_unless(Auth::user() && Auth::user()->isStaff(), 403, 'Staff access only.');
    }

    private function cloudUrl(): string
    {
        return rtrim(config('cbt_lan.cloud_url', 'https://educoreng.online'), '/');
    }

    // ── Dashboard ───────────────────────────────────────────────────────
    public function dashboard()
    {
        $this->authorize404();
        $user = Auth::user();

        $exams = CbtExam::query()
            ->when(!$this->fullAccess($user), function ($q) use ($user) {
                $subjectIds = \App\Models\ClassArmSubject::where('teacher_id', $user->id)->pluck('subject_id');
                $q->whereHas('questionBank', fn ($qb) => $qb->whereIn('subject_id', $subjectIds));
            })
            ->with('questionBank.subject', 'classArm')
            ->latest()
            ->get();

        $pendingCounts = CbtStudentSession::whereIn('cbt_exam_id', $exams->pluck('id'))
            ->whereNull('last_synced_at')
            ->selectRaw('cbt_exam_id, count(*) as c')
            ->groupBy('cbt_exam_id')
            ->pluck('c', 'cbt_exam_id');

        return view('cbt.lan', compact('exams', 'pendingCounts'));
    }

    private function fullAccess($user): bool
    {
        if (!$user) return false;
        if ($user->isSuperAdmin() || $user->isAdmin()) return true;
        return !in_array($user->roleKey(), ['subject_teacher', 'teacher', 'form_subject_teacher'], true);
    }

    // ── Export package (run on the CLOUD instance, while online) ────────
    public function exportPackage(CbtExam $exam)
    {
        $this->authorize404();
        $exam->load('questionBank', 'classArm');
        abort_unless($exam->question_bank_id && $exam->classArm, 404, 'Exam is missing its bank/class link.');

        $tenantId  = $exam->tenant_id;
        $classArm  = $exam->classArm;
        $bank      = $exam->questionBank;

        $studentIds = DB::table('students')->where('current_class_arm_id', $classArm->id)->pluck('id');
        $userIds    = DB::table('students')->where('current_class_arm_id', $classArm->id)->pluck('user_id')->filter();

        $rows = [];
        $rows['tenants']            = DB::table('tenants')->where('id', $tenantId)->get();
        $rows['terms']              = DB::table('terms')->where('id', $exam->term_id)->get();
        $sessionIds                 = $rows['terms']->pluck('session_id')->filter();
        $rows['academic_sessions']  = Schema::hasTable('academic_sessions') && $sessionIds->isNotEmpty()
            ? DB::table('academic_sessions')->whereIn('id', $sessionIds)->get() : collect();
        $rows['class_levels']       = DB::table('class_levels')->where('id', $classArm->class_level_id)->get();
        $rows['class_arms']         = DB::table('class_arms')->where('id', $classArm->id)->get()
            ->map(function ($r) { $r->form_tutor_id = null; return $r; }); // form tutor account isn't exported
        $rows['subjects']           = DB::table('subjects')->where('id', $bank->subject_id)->get();
        $rows['assessment_types']   = $exam->assessment_type_id
            ? DB::table('assessment_types')->where('id', $exam->assessment_type_id)->get() : collect();
        $rows['cbt_question_banks'] = DB::table('cbt_question_banks')->where('id', $bank->id)->get();
        $rows['cbt_questions']      = DB::table('cbt_questions')->where('question_bank_id', $bank->id)->get();
        $rows['users']              = DB::table('users')->whereIn('id', $userIds)->get();
        $rows['students']           = DB::table('students')->whereIn('id', $studentIds)->get();
        $rows['cbt_exams']          = DB::table('cbt_exams')->where('id', $exam->id)->get();

        $token = Crypt::encryptString($tenantId . '|' . $exam->id . '|' . now()->addDays(90)->timestamp);
        $exam->update(['lan_sync_token' => $token, 'lan_exported_at' => now()]);

        $payload = [
            'package_version' => 1,
            'exam_id'         => $exam->id,
            'tenant_id'       => $tenantId,
            'sync_token'      => $token,
            'exported_at'     => now()->toIso8601String(),
            'tables'          => collect($rows)->map(fn ($c) => $c->values())->toArray(),
        ];

        $filename = 'lan-exam-' . $exam->id . '-' . now()->format('Ymd-His') . '.json';

        return response(json_encode($payload, JSON_PRETTY_PRINT))
            ->header('Content-Type', 'application/json')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    // ── Import package (run on the LOCAL/LAN instance) ──────────────────
    public function importPackage(Request $request)
    {
        $this->authorize404();
        $request->validate(['package' => ['required', 'file']]);

        $raw     = file_get_contents($request->file('package')->getRealPath());
        $payload = json_decode($raw, true);

        if (!is_array($payload) || empty($payload['tables']) || empty($payload['exam_id'])) {
            return back()->withErrors(['error' => 'That file is not a valid LAN exam package.']);
        }

        DB::transaction(function () use ($payload) {
            foreach (self::TABLES as $table) {
                foreach ($payload['tables'][$table] ?? [] as $row) {
                    $row = (array) $row;
                    if (!isset($row['id'])) continue;
                    DB::table($table)->updateOrInsert(['id' => $row['id']], $row);
                }
            }
        });

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
            ->whereNull('last_synced_at')
            ->get();

        if ($sessions->isEmpty()) {
            return response()->json(['status' => 'nothing_to_sync']);
        }

        try {
            $resp = Http::timeout(8)->post($this->cloudUrl() . '/api/lan/sync', [
                'token'    => $exam->lan_sync_token,
                'sessions' => $sessions->map(fn ($s) => [
                    'id'             => $s->id,
                    'student_id'     => $s->student_id,
                    'question_order' => $s->question_order,
                    'answers'        => $s->answers,
                    'essay_answers'  => $s->essay_answers,
                    'flagged_questions' => $s->flagged_questions,
                    'started_at'     => optional($s->started_at)->toIso8601String(),
                    'submitted_at'   => optional($s->submitted_at)->toIso8601String(),
                    'score'          => $s->score,
                    'percentage'     => $s->percentage,
                    'status'         => $s->status,
                ])->values(),
            ]);
        } catch (\Throwable $e) {
            return response()->json(['status' => 'offline']);
        }

        if (!$resp->successful()) {
            return response()->json(['status' => 'rejected', 'message' => $resp->json('message') ?? 'Cloud rejected the sync.']);
        }

        $accepted = $resp->json('accepted', []);
        CbtStudentSession::whereIn('id', $accepted)->update(['last_synced_at' => now()]);

        return response()->json(['status' => 'synced', 'count' => count($accepted)]);
    }

    // ── Receive a sync push (runs on the CLOUD instance, no session auth) ─
    public function apiSync(Request $request)
    {
        $request->validate(['token' => ['required', 'string'], 'sessions' => ['required', 'array']]);

        try {
            $decoded = Crypt::decryptString($request->input('token'));
            [$tenantId, $examId, $expiry] = array_pad(explode('|', $decoded), 3, null);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Invalid sync token.'], 422);
        }

        if (!$tenantId || !$examId || (int) $expiry < now()->timestamp) {
            return response()->json(['message' => 'Sync token expired or malformed.'], 422);
        }

        $exam = CbtExam::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)
            ->where('id', $examId)->where('tenant_id', $tenantId)->first();

        if (!$exam) {
            return response()->json(['message' => 'Exam not found for this token.'], 404);
        }

        $accepted = [];

        foreach ($request->input('sessions', []) as $incoming) {
            if (empty($incoming['student_id'])) continue;

            $existing = CbtStudentSession::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)
                ->where('tenant_id', $tenantId)
                ->where('cbt_exam_id', $examId)
                ->where('student_id', $incoming['student_id'])
                ->first();

            if ($existing && $existing->isFinal()) {
                // Cloud copy already final — don't clobber it, but tell the
                // LAN instance it's accounted for so it stops retrying.
                $accepted[] = $incoming['id'];
                continue;
            }

            $data = [
                'tenant_id'          => $tenantId,
                'cbt_exam_id'        => $examId,
                'student_id'         => $incoming['student_id'],
                'question_order'     => $incoming['question_order'] ?? [],
                'answers'            => $incoming['answers'] ?? [],
                'essay_answers'      => $incoming['essay_answers'] ?? [],
                'flagged_questions'  => $incoming['flagged_questions'] ?? [],
                'started_at'         => $incoming['started_at'] ?? null,
                'submitted_at'       => $incoming['submitted_at'] ?? null,
                'score'              => $incoming['score'] ?? null,
                'percentage'         => $incoming['percentage'] ?? null,
                'status'             => $incoming['status'] ?? 'submitted',
                'last_synced_at'     => now(),
            ];

            if ($existing) {
                $existing->update($data);
            } else {
                CbtStudentSession::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)->create($data);
            }

            $accepted[] = $incoming['id'];
        }

        return response()->json(['status' => 'ok', 'accepted' => $accepted]);
    }
}

<?php

namespace App\Services\Cbt;

use App\Http\Controllers\CbtLanController;
use App\Models\CbtExam;
use App\Models\CbtStudentSession;
use App\Models\Scopes\TenantScope;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CbtLanAccessService
{
    private const STATE_PATH = 'cbt-lan/installation.json';

    public function isPrivateHost(Request $request): bool
    {
        $host = strtolower(trim($request->getHost(), '[]'));

        if (in_array($host, ['localhost', '127.0.0.1', '::1'], true)
            || str_ends_with($host, '.local')
            || str_ends_with($host, '.test')
            || !str_contains($host, '.')) {
            return true;
        }

        if (!filter_var($host, FILTER_VALIDATE_IP)) {
            return false;
        }

        return filter_var(
            $host,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) === false;
    }

    public function isAvailable(Request $request): bool
    {
        return (bool) config('cbt_lan.admission_number_login', true)
            && $this->isPrivateHost($request)
            && $this->importedExamIds()->isNotEmpty();
    }

    public function activateImportedPackage(array $payload): void
    {
        $examId = (int) ($payload['exam_id'] ?? 0);
        $tenantId = (int) ($payload['tenant_id'] ?? 0);
        if (!$examId || !$tenantId) {
            return;
        }

        $state = $this->state();
        $state['release'] = CbtLanController::APPLICATION_RELEASE;
        $state['packages'][(string) $examId] = [
            'exam_id' => $examId,
            'tenant_id' => $tenantId,
            'imported_at' => now()->toIso8601String(),
        ];

        Storage::disk('local')->put(
            self::STATE_PATH,
            json_encode($state, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR)
        );
    }

    public function provisionMissingStudentAccounts(int $examId): void
    {
        $exam = CbtExam::withoutGlobalScope(TenantScope::class)->find($examId);
        if (!$exam) {
            return;
        }

        $armIds = DB::table('cbt_exam_class_arms')->where('cbt_exam_id', $examId)
            ->pluck('class_arm_id')->map(fn ($id) => (int) $id);
        if ($armIds->isEmpty() && $exam->class_arm_id) {
            $armIds->push((int) $exam->class_arm_id);
        }

        Student::withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $exam->tenant_id)
            ->whereIn('current_class_arm_id', $armIds)
            ->where('status', Student::STATUS_ACTIVE)
            ->whereNull('user_id')
            ->orderBy('id')
            ->each(function (Student $student): void {
                $email = sprintf('lan.%d.%d@offline.educore.local', $student->tenant_id, $student->id);
                $user = User::where('email', $email)->first();

                if (!$user) {
                    // Keep locally generated accounts in a reserved high ID
                    // range so a later cloud package cannot reuse the same
                    // primary key and silently relink this student.
                    $localUserId = 8_000_000_000_000_000_000 + (int) $student->id;
                    if (User::find($localUserId)) {
                        return;
                    }
                    $user = User::forceCreate([
                        'id' => $localUserId,
                        'tenant_id' => $student->tenant_id,
                        'name' => $student->full_name,
                        'email' => $email,
                        'password' => Hash::make(Str::random(64)),
                        'role' => 'student',
                        'is_active' => true,
                    ]);
                }

                if ((int) $user->tenant_id === (int) $student->tenant_id && $user->isStudent()) {
                    $student->forceFill(['user_id' => $user->id])->save();
                }
            });
    }

    /**
     * @return array{student: Student, user: User, exams: Collection<int,CbtExam>, preferred_exam: CbtExam}|null
     */
    public function resolveAdmissionNumber(string $admissionNumber): ?array
    {
        $examIds = $this->importedExamIds();
        if ($examIds->isEmpty()) {
            return null;
        }

        $exams = CbtExam::withoutGlobalScope(TenantScope::class)
            ->whereIn('id', $examIds)
            ->where('status', 'published')
            ->where(function ($query) {
                $query->whereNull('scheduled_end')->orWhere('scheduled_end', '>', now());
            })
            ->with('classArms')
            ->get();

        $students = Student::withoutGlobalScope(TenantScope::class)
            ->whereRaw('LOWER(admission_number) = ?', [mb_strtolower(trim($admissionNumber))])
            ->where('status', Student::STATUS_ACTIVE)
            ->whereIn('tenant_id', $exams->pluck('tenant_id')->unique())
            ->get();

        $matches = $students->map(function (Student $student) use ($exams) {
            $studentExams = $exams->filter(fn (CbtExam $exam) =>
                (int) $exam->tenant_id === (int) $student->tenant_id
                && $exam->isAssignedToClassArm($student->current_class_arm_id)
            )->values();

            return $studentExams->isEmpty() ? null : compact('student', 'studentExams');
        })->filter()->values();

        // Admission numbers can be repeated across schools. Never guess when
        // more than one imported tenant produces a match.
        if ($matches->count() !== 1) {
            return null;
        }

        $student = $matches->first()['student'];
        $eligibleExams = $matches->first()['studentExams'];
        $user = $student->user_id ? User::find($student->user_id) : null;
        if (!$user || !$user->isStudent() || !$user->is_active
            || (int) $user->tenant_id !== (int) $student->tenant_id) {
            return null;
        }

        $inProgressExamId = CbtStudentSession::withoutGlobalScope(TenantScope::class)
            ->where('student_id', $student->id)
            ->whereIn('cbt_exam_id', $eligibleExams->pluck('id'))
            ->where('status', 'in_progress')
            ->latest('started_at')
            ->value('cbt_exam_id');

        $preferred = $eligibleExams->firstWhere('id', $inProgressExamId)
            ?? $eligibleExams->sortBy(fn (CbtExam $exam) => $exam->scheduled_start?->timestamp ?? PHP_INT_MAX)->first();

        return [
            'student' => $student,
            'user' => $user,
            'exams' => $eligibleExams,
            'preferred_exam' => $preferred,
        ];
    }

    /** @return Collection<int,int> */
    public function importedExamIds(): Collection
    {
        $state = $this->state();
        if (($state['release'] ?? '') !== CbtLanController::APPLICATION_RELEASE) {
            return collect();
        }

        return collect($state['packages'] ?? [])
            ->pluck('exam_id')
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();
    }

    private function state(): array
    {
        if (!Storage::disk('local')->exists(self::STATE_PATH)) {
            return ['release' => CbtLanController::APPLICATION_RELEASE, 'packages' => []];
        }

        $decoded = json_decode((string) Storage::disk('local')->get(self::STATE_PATH), true);

        return is_array($decoded) ? $decoded : [
            'release' => CbtLanController::APPLICATION_RELEASE,
            'packages' => [],
        ];
    }
}

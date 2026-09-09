<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\AttendanceRecord;
use App\Models\ClassArm;
use App\Models\Invoice;
use App\Models\Score;
use App\Models\Student;
use App\Models\Term;
use App\Models\TermlySummary;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class MobileAnalyticsController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();
        abort_unless($user, 401);
        abort_if($user->isStudent() || $user->isParent() || $user->isSuperAdmin(), 403, 'School analytics access required.');
        abort_unless($user->canAccessModule('analytics'), 403, 'You do not have access to analytics.');

        $tenantId = (int) $user->tenant_id;
        $session = AcademicSession::query()->where('tenant_id', $tenantId)->where('is_current', true)->first();
        $termIds = Term::query()
            ->where('tenant_id', $tenantId)
            ->when($session, fn ($query) => $query->where('session_id', $session->id))
            ->pluck('id');

        $activeStudents = Student::query()->where('tenant_id', $tenantId)->where('status', Student::STATUS_ACTIVE)->count();
        $classes = ClassArm::query()->where('tenant_id', $tenantId)->count();
        $averageScore = Schema::hasTable('termly_summaries')
            ? TermlySummary::query()->where('tenant_id', $tenantId)->when($termIds->isNotEmpty(), fn ($query) => $query->whereIn('term_id', $termIds))->avg('final_average')
            : null;
        $attendance = Schema::hasTable('attendance_records')
            ? AttendanceRecord::query()->where('tenant_id', $tenantId)->when($termIds->isNotEmpty(), fn ($query) => $query->whereIn('term_id', $termIds))
                ->selectRaw("COUNT(*) as total, SUM(CASE WHEN status IN ('present','late') THEN 1 ELSE 0 END) as present")->first()
            : null;
        $attendanceRate = ($attendance?->total ?? 0) > 0 ? round(((int) $attendance->present / (int) $attendance->total) * 100, 1) : null;

        $metrics = [
            $this->metric('students', 'Active students', (string) $activeStudents, 'number', 'navy'),
            $this->metric('classes', 'Classes', (string) $classes, 'number', 'blue'),
            $this->metric('average', 'School average', $averageScore !== null ? number_format((float) $averageScore, 1).'%' : 'No data', 'percentage', 'success'),
            $this->metric('attendance', 'Attendance', $attendanceRate !== null ? number_format($attendanceRate, 1).'%' : 'No data', 'percentage', 'warning'),
        ];

        $sections = [
            $this->section('classes', 'Class performance', $this->classPerformance($tenantId, $termIds->all())),
            $this->section('subjects', 'Subject performance', $this->subjectPerformance($tenantId, $termIds->all())),
            $this->section('enrollment', 'Enrollment trend', $this->enrollmentTrend($tenantId)),
        ];

        if ($user->canAccessModule('fees') && Schema::hasTable('invoices')) {
            [$financeMetric, $financeSection] = $this->finance($tenantId, $session?->id);
            $metrics[] = $financeMetric;
            $sections[] = $financeSection;
        }

        return response()->json([
            'contract_version' => 1,
            'module' => [
                'key' => 'analytics',
                'title' => 'Analytics',
                'description' => 'School performance, attendance and enrollment intelligence',
                'can_manage' => false,
                'mobile_policy' => 'read_only',
            ],
            'metrics' => $metrics,
            'sections' => $sections,
            'generated_at' => now()->toIso8601String(),
        ]);
    }

    private function classPerformance(int $tenantId, array $termIds): array
    {
        if (! Schema::hasTable('termly_summaries') || ! Schema::hasTable('class_arms') || ! Schema::hasTable('class_levels')) return [];
        return TermlySummary::query()->where('termly_summaries.tenant_id', $tenantId)
            ->when($termIds !== [], fn ($query) => $query->whereIn('termly_summaries.term_id', $termIds))
            ->join('class_arms', fn ($join) => $join->on('class_arms.id', '=', 'termly_summaries.class_arm_id')->where('class_arms.tenant_id', '=', $tenantId))
            ->join('class_levels', fn ($join) => $join->on('class_levels.id', '=', 'class_arms.class_level_id')->where('class_levels.tenant_id', '=', $tenantId))
            ->selectRaw('class_arms.id as class_id, class_levels.name as level_name, class_arms.name as arm_name, AVG(termly_summaries.final_average) as average_score, COUNT(*) as students, SUM(CASE WHEN termly_summaries.subjects_failed = 0 THEN 1 ELSE 0 END) as passed_all')
            ->groupBy('class_arms.id', 'class_levels.name', 'class_arms.name')->orderByDesc('average_score')->limit(50)->get()
            ->map(fn ($row) => $this->record('class-'.$row->class_id, trim($row->level_name.' '.$row->arm_name), number_format((float) $row->average_score, 1).'% average', null, [
                'Students' => (string) $row->students, 'Passed all subjects' => (string) $row->passed_all, 'Average' => number_format((float) $row->average_score, 1).'%',
            ]))->values()->all();
    }

    private function subjectPerformance(int $tenantId, array $termIds): array
    {
        if (! Schema::hasTable('scores') || ! Schema::hasTable('subjects')) return [];
        return Score::query()->where('scores.tenant_id', $tenantId)
            ->when($termIds !== [], fn ($query) => $query->whereIn('scores.term_id', $termIds))
            ->join('subjects', fn ($join) => $join->on('subjects.id', '=', 'scores.subject_id')->where('subjects.tenant_id', '=', $tenantId))
            ->selectRaw('subjects.id as subject_id, subjects.name as subject_name, AVG(scores.score) as average_score, MIN(scores.score) as minimum_score, MAX(scores.score) as maximum_score, COUNT(*) as attempts')
            ->groupBy('subjects.id', 'subjects.name')->orderByDesc('average_score')->limit(60)->get()
            ->map(fn ($row) => $this->record('subject-'.$row->subject_id, $row->subject_name, number_format((float) $row->average_score, 1).'% average', null, [
                'Average' => number_format((float) $row->average_score, 1).'%', 'Lowest' => number_format((float) $row->minimum_score, 1).'%', 'Highest' => number_format((float) $row->maximum_score, 1).'%', 'Score records' => (string) $row->attempts,
            ]))->values()->all();
    }

    private function enrollmentTrend(int $tenantId): array
    {
        return Student::query()->where('tenant_id', $tenantId)->whereBetween('created_at', [now()->startOfYear(), now()->endOfYear()])->get(['id', 'created_at'])
            ->groupBy(fn (Student $student) => $student->created_at?->format('Y-m') ?? 'unknown')->reject(fn ($items, $month) => $month === 'unknown')
            ->map(fn ($items, $month) => ['month' => $month, 'count' => $items->count()])->sortKeys()
            ->map(fn ($row) => $this->record('enrollment-'.$row['month'], \Carbon\Carbon::createFromFormat('Y-m', $row['month'])->format('F Y'), $row['count'].' new student'.($row['count'] === 1 ? '' : 's'), null, ['Enrolled' => (string) $row['count']]))
            ->values()->all();
    }

    private function finance(int $tenantId, ?int $sessionId): array
    {
        $query = Invoice::query()->where('tenant_id', $tenantId)->when($sessionId, fn ($builder) => $builder->where('session_id', $sessionId));
        $billed = (float) (clone $query)->sum('total_amount');
        $collected = (float) (clone $query)->sum('amount_paid');
        $outstanding = max(0, $billed - $collected);
        $rate = $billed > 0 ? round(($collected / $billed) * 100, 1) : null;
        return [
            $this->metric('collection', 'Fee collection', $rate !== null ? number_format($rate, 1).'%' : 'No data', 'percentage', 'success'),
            $this->section('finance', 'Fee overview', [$this->record('finance-current', 'Current session fees', $rate !== null ? number_format($rate, 1).'% collected' : 'No billing data', null, [
                'Billed' => $this->money($billed), 'Collected' => $this->money($collected), 'Outstanding' => $this->money($outstanding),
            ])]),
        ];
    }

    private function metric(string $key, string $label, string $value, string $format, string $tone): array { return compact('key', 'label', 'value', 'format', 'tone'); }
    private function section(string $key, string $title, array $records): array { return compact('key', 'title') + ['count' => count($records), 'records' => $records]; }
    private function record(string $id, string $title, ?string $subtitle, ?string $status, array $fields): array
    {
        return ['id' => $id, 'title' => $title, 'subtitle' => $subtitle, 'status' => $status, 'fields' => collect($fields)->map(fn ($value, $label) => ['label' => (string) $label, 'value' => (string) $value])->values()->all()];
    }
    private function money(float|int|string|null $amount): string { return 'NGN '.number_format((float) $amount, 2); }
}

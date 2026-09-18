<?php

namespace App\Http\Controllers;

use App\Models\ParallelCurriculumClass;
use App\Models\ParallelCurriculumReportPublication;
use App\Models\Student;
use App\Models\Term;
use App\Services\GuardianNotifier;
use App\Services\ParallelCurriculumResultService;
use App\Services\ParallelCurriculumService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ParallelCurriculumResultController extends Controller
{
    public function __construct(
        private readonly ParallelCurriculumService $parallel,
        private readonly ParallelCurriculumResultService $results,
    ) {}

    private function tenantId(): int
    {
        return (int) auth()->user()->tenant_id;
    }

    private function assertAccess(): void
    {
        $user = auth()->user();

        abort_unless(
            $user->isSuperAdmin() || $user->canAccessExactModule('scores'),
            403,
            'You do not have permission to manage parallel curriculum results.'
        );

        abort_unless(
            $this->parallel->enabledForTenant($this->tenantId()),
            404,
            'Parallel Curriculum Integration is not enabled for this school.'
        );
    }

    public function index(Request $request)
    {
        $this->assertAccess();

        $tenantId = $this->tenantId();
        $terms = Term::with('session')->orderByDesc('id')->get();
        $classes = ParallelCurriculumClass::with('curriculum')
            ->where('is_active', true)
            ->whereHas('curriculum', fn ($query) => $query->where('is_active', true))
            ->orderBy('parallel_curriculum_id')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $currentTerm = Term::current()->with('session')->first();
        $termId = (int) ($request->integer('term_id') ?: ($currentTerm?->id ?? $terms->first()?->id ?? 0));
        $classId = (int) ($request->integer('class_id') ?: ($classes->first()?->id ?? 0));

        $term = $termId ? $terms->firstWhere('id', $termId) : null;
        $class = $classId ? $classes->firstWhere('id', $classId) : null;

        abort_if($termId && ! $term, 404, 'The selected term is unavailable.');
        abort_if($classId && ! $class, 404, 'The selected parallel class is unavailable.');

        $report = ($term && $class) ? $this->results->classReport($class, $term) : null;

        return view('parallel-curriculum.results.index', compact(
            'terms',
            'classes',
            'currentTerm',
            'termId',
            'classId',
            'term',
            'class',
            'report'
        ));
    }

    public function student(Request $request, ParallelCurriculumClass $class, Student $student)
    {
        $this->assertAccess();

        $tenantId = $this->tenantId();
        abort_unless((int) $class->tenant_id === $tenantId && (int) $student->tenant_id === $tenantId, 403);

        $termId = $request->integer('term_id');
        $term = $termId
            ? Term::with('session')->findOrFail($termId)
            : Term::current()->with('session')->first();

        abort_unless($term, 422, 'Select an academic term before opening the report.');

        $report = $this->results->studentReport($class, $term, (int) $student->id);
        abort_unless($report, 404, 'This student is not enrolled in the selected parallel class for this academic session.');

        return view('parallel-curriculum.results.student', compact('report'));
    }

    public function pdf(Request $request, ParallelCurriculumClass $class, Student $student)
    {
        $this->assertAccess();

        $tenantId = $this->tenantId();
        abort_unless((int) $class->tenant_id === $tenantId && (int) $student->tenant_id === $tenantId, 403);

        $termId = $request->integer('term_id');
        $term = $termId
            ? Term::with('session')->findOrFail($termId)
            : Term::current()->with('session')->first();

        abort_unless($term, 422, 'Select an academic term before downloading the report.');

        $report = $this->results->studentReport($class, $term, (int) $student->id);
        abort_unless($report, 404, 'This student is not enrolled in the selected parallel class for this academic session.');

        $filename = str($student->admission_number ?: $student->full_name)
            ->slug('_')
            ->append('_', str($class->curriculum?->name)->slug('_'))
            ->append('_result.pdf')
            ->toString();

        return Pdf::loadView('parallel-curriculum.results.pdf', compact('report'))
            ->setPaper('a4', 'portrait')
            ->download($filename);
    }

    public function publish(Request $request)
    {
        $this->assertAccess();

        $tenantId = $this->tenantId();
        $data = $request->validate([
            'class_id' => [
                'required',
                Rule::exists('parallel_curriculum_classes', 'id')->where('tenant_id', $tenantId),
            ],
            'term_id' => [
                'required',
                Rule::exists('terms', 'id')->where('tenant_id', $tenantId),
            ],
        ]);

        $class = ParallelCurriculumClass::with('curriculum')->findOrFail($data['class_id']);
        $term = Term::with('session')->findOrFail($data['term_id']);
        $report = $this->results->classReport($class, $term);

        if (! $this->results->canPublish($report)) {
            $message = match (true) {
                $report['students_count'] < 1 => 'No active students are enrolled in this parallel class for the selected session.',
                $report['subjects_count'] < 1 => 'No active subjects are assigned to this parallel class.',
                $report['components']->isEmpty() => 'The class has no usable Assessment Template.',
                abs((float) $report['component_weight'] - 100.0) > 0.001 => 'The parallel Assessment Template must total exactly 100% before publication.',
                $report['grades']->isEmpty() => 'Configure the programme grading scale before publishing results.',
                ! $report['grading_scale_complete'] => 'The programme grading scale must cover every score from 0 to 100 without gaps.',
                $report['ungraded_subject_results_count'] > 0 => 'One or more completed subject scores do not resolve to a configured grade.',
                default => 'Complete all subject scores for every active student before publishing this parallel result.',
            };

            throw ValidationException::withMessages(['publication' => $message]);
        }

        $publication = ParallelCurriculumReportPublication::where(
                'parallel_curriculum_class_id',
                $class->id
            )
            ->where('term_id', $term->id)
            ->first();

        $wasPublished = $publication?->isPublished() ?? false;

        $publication ??= new ParallelCurriculumReportPublication([
            'tenant_id' => $tenantId,
            'parallel_curriculum_class_id' => $class->id,
            'term_id' => $term->id,
        ]);

        $publication->fill([
            'parallel_curriculum_id' => $class->parallel_curriculum_id,
            'status' => ParallelCurriculumReportPublication::STATUS_PUBLISHED,
            'published_by' => auth()->id(),
            'published_at' => now(),
            'unpublished_at' => null,
        ])->save();

        $notified = 0;
        if (! $wasPublished) {
            $studentIds = $report['results']
                ->pluck('student.id')
                ->map(fn ($id) => (int) $id)
                ->filter()
                ->unique()
                ->values();

            $students = Student::with('guardians')
                ->whereIn('id', $studentIds)
                ->get()
                ->keyBy('id');

            $notifier = app(GuardianNotifier::class);
            $schoolName = auth()->user()->tenant?->name;
            $programmeName = $class->curriculum?->name ?: 'Parallel Curriculum';

            foreach ($report['results'] as $row) {
                $student = $students->get((int) $row['student']->id);
                if (! $student) {
                    continue;
                }

                $guardian = $student->guardians->firstWhere('pivot.is_primary_contact', true)
                    ?? $student->guardians->first();

                if (! $guardian) {
                    continue;
                }

                try {
                    $notifier->send(
                        $guardian,
                        $programmeName.' result published — '.$student->full_name,
                        [
                            ($term->name ?? 'Term').' '.$programmeName.
                                ' result for '.$student->full_name.' is now available.',
                            'Sign in to the parent portal to view or download the published result.',
                        ],
                        smsBody: 'Dear Parent, '.$student->full_name."'s ".
                            ($term->name ?? 'term').' '.$programmeName.
                            ' result is now available on the EduCore parent portal.',
                        actionLabel: 'View Results',
                        actionUrl: route('login'),
                        schoolName: $schoolName,
                    );
                    $notified++;
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::error(
                        "Parallel-result notification failed for student {$student->id}: ".$e->getMessage()
                    );
                }
            }
        }

        $message = 'Parallel curriculum result published. Source scores are now locked.';
        if (! $wasPublished && $notified > 0) {
            $message .= " {$notified} parent/guardian notification(s) were queued/sent.";
        }

        return back()->with('success', $message);
    }

    public function unpublish(Request $request)
    {
        $this->assertAccess();

        $tenantId = $this->tenantId();
        $data = $request->validate([
            'class_id' => [
                'required',
                Rule::exists('parallel_curriculum_classes', 'id')->where('tenant_id', $tenantId),
            ],
            'term_id' => [
                'required',
                Rule::exists('terms', 'id')->where('tenant_id', $tenantId),
            ],
        ]);

        $publication = ParallelCurriculumReportPublication::where(
                'parallel_curriculum_class_id',
                $data['class_id']
            )
            ->where('term_id', $data['term_id'])
            ->first();

        if ($publication) {
            $publication->update([
                'status' => ParallelCurriculumReportPublication::STATUS_DRAFT,
                'unpublished_at' => now(),
            ]);
        }

        return back()->with('success', 'Parallel curriculum result unpublished. Score entry is open again unless the conventional report is published.');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\ClassArmSubject;
use App\Models\ClassLevel;
use App\Models\ExamPeriod;
use App\Models\Term;
use App\Models\User;
use App\Services\Exams\ExamSchedulerService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ExamPeriodController extends Controller
{
    private function tenantId(): int
    {
        return (int) auth()->user()->tenant_id;
    }

    private function guard(): void
    {
        abort_unless(auth()->user()->canManage('exams'), 403, 'Access denied.');
    }

    public function index()
    {
        $this->guard();
        $periods = ExamPeriod::with('term')->latest()->paginate(15);
        return view('exams.index', compact('periods'));
    }

    public function create()
    {
        $this->guard();
        $terms = Term::with('session')->orderByDesc('id')->get();
        $classLevels = ClassLevel::orderBy('order_index')->get();
        return view('exams.create', compact('terms', 'classLevels'));
    }

    public function store(Request $request)
    {
        $this->guard();
        $data = $request->validate([
            'term_id'                  => ['required', Rule::exists('terms', 'id')->where('tenant_id', $this->tenantId())],
            'title'                    => ['required', 'string', 'max:150'],
            'start_date'               => ['required', 'date'],
            'end_date'                 => ['required', 'date', 'after_or_equal:start_date'],
            'excluded_weekdays'        => ['nullable', 'array'],
            'excluded_weekdays.*'      => ['integer', 'min:0', 'max:6'],
            'sessions'                 => ['required', 'array', 'min:1'],
            'sessions.*.name'          => ['required', 'string', 'max:60'],
            'sessions.*.start_time'    => ['required', 'date_format:H:i'],
            'sessions.*.end_time'      => ['required', 'date_format:H:i', 'after:sessions.*.start_time'],
            'class_level_ids'          => ['required', 'array', 'min:1'],
            'class_level_ids.*'        => [Rule::exists('class_levels', 'id')->where('tenant_id', $this->tenantId())],
        ]);

        $period = ExamPeriod::create([
            'term_id'           => $data['term_id'],
            'title'             => $data['title'],
            'start_date'        => $data['start_date'],
            'end_date'          => $data['end_date'],
            'excluded_weekdays' => $data['excluded_weekdays'] ?? [0, 6],
            'created_by'        => auth()->id(),
        ]);

        foreach ($data['sessions'] as $i => $s) {
            $period->examSessions()->create([
                'name'       => $s['name'],
                'start_time' => $s['start_time'],
                'end_time'   => $s['end_time'],
                'sort_order' => $i,
            ]);
        }

        $tid = $this->tenantId();
        $period->classLevels()->sync(collect($data['class_level_ids'])->mapWithKeys(fn ($id) => [$id => ['tenant_id' => $tid]]));

        return redirect()->route('exams.show', $period)->with('success', 'Exam period created. Now generate the timetable.');
    }

    public function edit(ExamPeriod $period)
    {
        $this->guard();
        $period->load(['examSessions', 'classLevels']);
        $terms = Term::with('session')->orderByDesc('id')->get();
        $classLevels = ClassLevel::orderBy('order_index')->get();
        return view('exams.edit', compact('period', 'terms', 'classLevels'));
    }

    public function update(Request $request, ExamPeriod $period)
    {
        $this->guard();
        $data = $request->validate([
            'term_id'                  => ['required', Rule::exists('terms', 'id')->where('tenant_id', $this->tenantId())],
            'title'                    => ['required', 'string', 'max:150'],
            'start_date'               => ['required', 'date'],
            'end_date'                 => ['required', 'date', 'after_or_equal:start_date'],
            'excluded_weekdays'        => ['nullable', 'array'],
            'excluded_weekdays.*'      => ['integer', 'min:0', 'max:6'],
            'sessions'                 => ['required', 'array', 'min:1'],
            'sessions.*.name'          => ['required', 'string', 'max:60'],
            'sessions.*.start_time'    => ['required', 'date_format:H:i'],
            'sessions.*.end_time'      => ['required', 'date_format:H:i', 'after:sessions.*.start_time'],
            'class_level_ids'          => ['required', 'array', 'min:1'],
            'class_level_ids.*'        => [Rule::exists('class_levels', 'id')->where('tenant_id', $this->tenantId())],
        ]);

        $period->update([
            'term_id'           => $data['term_id'],
            'title'             => $data['title'],
            'start_date'        => $data['start_date'],
            'end_date'          => $data['end_date'],
            'excluded_weekdays' => $data['excluded_weekdays'] ?? [0, 6],
        ]);

        // Sessions, class levels, and any generated timetable/supervision are
        // rebuilt from scratch — the schedule depends entirely on these inputs,
        // so a stale plan against the new dates/classes would be meaningless.
        $period->examSessions()->delete();
        foreach ($data['sessions'] as $i => $s) {
            $period->examSessions()->create([
                'name'       => $s['name'],
                'start_time' => $s['start_time'],
                'end_time'   => $s['end_time'],
                'sort_order' => $i,
            ]);
        }

        $tid = $this->tenantId();
        $period->classLevels()->sync(collect($data['class_level_ids'])->mapWithKeys(fn ($id) => [$id => ['tenant_id' => $tid]]));

        $period->entries()->delete(); // cascades to exam_supervisors
        $period->update(['status' => 'draft']);

        return redirect()->route('exams.show', $period)
            ->with('success', 'Exam period updated. The previous timetable and supervision plan were cleared — regenerate them.');
    }

    public function destroy(ExamPeriod $period)
    {
        $this->guard();
        $period->delete();

        return redirect()->route('exams.index')->with('success', 'Exam period deleted.');
    }

    public function unpublish(ExamPeriod $period)
    {
        $this->guard();
        abort_unless($period->status === 'published', 422, 'This exam period is not published.');

        $period->update(['status' => 'supervision_planned']);

        return back()->with('success', 'Unpublished. Staff can no longer see this schedule on the app.');
    }

    public function show(ExamPeriod $period)
    {
        $this->guard();
        $period->load(['term', 'examSessions', 'classLevels', 'staffPool',
            'entries.classLevel', 'entries.subject', 'entries.examSession', 'entries.supervisors.user']);

        $entriesByDate = $period->entries->sortBy(fn ($e) => $e->exam_date->toDateString() . '-' . $e->examSession->sort_order)
            ->groupBy(fn ($e) => $e->exam_date->toDateString());

        $staff = User::activeStaff($this->tenantId())->orderBy('name')->get();

        return view('exams.show', compact('period', 'entriesByDate', 'staff'));
    }

    public function generateTimetable(ExamPeriod $period, ExamSchedulerService $scheduler)
    {
        $this->guard();
        try {
            $result = $scheduler->generateTimetable($period);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        } catch (\Throwable $e) {
            report($e);
            return back()->withErrors(['error' => 'Could not generate the timetable: ' . $e->getMessage()]);
        }

        $msg = "Timetable generated: {$result['placed']} sittings placed.";
        if (!empty($result['unplaced'])) {
            $msg .= ' ' . count($result['unplaced']) . ' subject(s) could not fit in the available slots — add more days/sessions or trim subjects.';
        }

        return back()->with('success', $msg);
    }

    public function saveStaffPool(Request $request, ExamPeriod $period)
    {
        $this->guard();
        $data = $request->validate([
            'user_ids'   => ['required', 'array', 'min:1'],
            'user_ids.*' => [Rule::exists('users', 'id')->where('tenant_id', $this->tenantId())],
        ]);

        $tid = $this->tenantId();
        $period->staffPool()->sync(collect($data['user_ids'])->mapWithKeys(fn ($id) => [$id => ['tenant_id' => $tid]]));

        return back()->with('success', 'Supervision staff pool saved (' . count($data['user_ids']) . ' staff).');
    }

    public function generateSupervision(ExamPeriod $period, ExamSchedulerService $scheduler)
    {
        $this->guard();
        try {
            $result = $scheduler->generateSupervision($period);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        } catch (\Throwable $e) {
            report($e);
            return back()->withErrors(['error' => 'Could not generate the supervision plan: ' . $e->getMessage()]);
        }

        $msg = "Supervision plan generated: {$result['assigned']} sittings assigned.";
        if ($result['unassigned'] > 0) {
            $msg .= " {$result['unassigned']} sitting(s) had no available supervisor — add more staff to the pool.";
        }

        return back()->with('success', $msg);
    }

    public function publish(ExamPeriod $period)
    {
        $this->guard();
        abort_unless($period->status === 'supervision_planned' || $period->status === 'published', 422, 'Generate the supervision plan first.');

        $period->update(['status' => 'published']);

        app(\App\Services\Notifications\PushNotificationService::class)->notifyExamSupervisionPublished($period);

        return back()->with('success', 'Published. Each supervisor can now see their personal schedule on the EduCore app.');
    }
}

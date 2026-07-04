<?php
namespace App\Http\Controllers;

use App\Models\ClassArm;
use App\Models\Subject;
use App\Models\AssessmentType;
use App\Models\Score;
use App\Models\Student;
use App\Models\Term;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GradeBookController extends Controller
{
    public function index(Request $request)
    {
        $classArms = ClassArm::with('classLevel')->get();
        $terms     = Term::with('session')->latest()->get();

        if (!$request->filled('class_arm_id') || !$request->filled('term_id')) {
            return view('gradebook.index', compact('classArms', 'terms'));
        }

        $classArm       = ClassArm::with('classLevel')->findOrFail($request->class_arm_id);
        $term           = Term::with('session')->findOrFail($request->term_id);
        $assessmentTypes= AssessmentType::orderBy('name')->get();
        $subjects       = Subject::whereHas('classArmSubjects', fn($q) =>
                            $q->where('class_arm_id', $classArm->id)
                          )->orderBy('name')->get();
        $students       = Student::where('current_class_arm_id', $classArm->id)
                            ->where('status', Student::STATUS_ACTIVE)->orderBy('last_name')->get();

        // Build grade matrix: student → subject → [at_id => score]
        $scores = Score::where('term_id', $term->id)
            ->whereIn('student_id', $students->pluck('id'))
            ->whereIn('subject_id', $subjects->pluck('id'))
            ->get()
            ->groupBy(fn($s) => $s->student_id.'-'.$s->subject_id)
            ->map(fn($group) => $group->keyBy('assessment_type_id'));

        return view('gradebook.index', compact(
            'classArms','terms','classArm','term',
            'assessmentTypes','subjects','students','scores'
        ));
    }
}

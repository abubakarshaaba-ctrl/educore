<?php
namespace App\Http\Controllers;

use App\Models\ClassArm;
use App\Models\Term;
use App\Models\Score;
use App\Models\Student;
use App\Models\TermlySummary;
use App\Models\AttendanceRecord;
use Illuminate\Http\Request;

class ExportController extends Controller
{
    public function index()
    {
        $classArms = \App\Models\ClassArm::with('classLevel')->get();
        $terms     = \App\Models\Term::with('session')->latest()->get();
        $sessions  = \App\Models\AcademicSession::latest()->get();
        return view('exports.index', compact('classArms', 'terms', 'sessions'));
    }

    // Export broadsheet to CSV
    public function broadsheetCsv(Request $request)
    {
        $request->validate([
            'class_arm_id' => ['required','exists:class_arms,id'],
            'term_id'      => ['required','exists:terms,id'],
        ]);
        $classArm = ClassArm::with('classLevel')->findOrFail($request->class_arm_id);
        $term     = Term::findOrFail($request->term_id);

        $summaries = TermlySummary::where('class_arm_id',$classArm->id)
            ->where('term_id',$term->id)
            ->with('student')
            ->orderBy('position_in_class')
            ->get();

        $filename = 'Broadsheet_'.$classArm->classLevel->name.'_'.$classArm->name.'_'.$term->name.'.csv';
        $headers  = ['Content-Type'=>'text/csv','Content-Disposition'=>'attachment; filename="'.$filename.'"'];

        $callback = function () use ($summaries, $term) {
            $file = fopen('php://output','w');
            fputcsv($file, ['Position','Student Name','Adm No','Total Score','Average','Subjects Offered','Subjects Failed','Status']);
            foreach ($summaries as $s) {
                $avg    = $s->final_average;
                $status = $avg>=75?'Distinction':($avg>=60?'Merit':($avg>=50?'Credit':'Below Average'));
                fputcsv($file, [
                    $s->position_in_class,
                    $s->student->full_name,
                    $s->student->admission_number,
                    $s->total_score,
                    $avg,
                    $s->subjects_offered,
                    $s->subjects_failed,
                    $status,
                ]);
            }
            fclose($file);
        };
        return response()->streamDownload($callback, $filename, $headers);
    }

    // Export student list to CSV
    public function studentsCsv(Request $request)
    {
        $students = Student::with(['currentClassArm.classLevel'])
            ->when($request->class_arm_id, fn($q) => $q->where('current_class_arm_id',$request->class_arm_id))
            ->where('status', Student::STATUS_ACTIVE)
            ->orderBy('last_name')
            ->get();

        $filename = 'Students_'.date('Y-m-d').'.csv';
        $callback = function () use ($students) {
            $file = fopen('php://output','w');
            fputcsv($file, ['Admission No','Last Name','First Name','Gender','DOB','Class','Status']);
            foreach ($students as $s) {
                fputcsv($file, [
                    $s->admission_number, $s->last_name, $s->first_name,
                    $s->gender, $s->date_of_birth,
                    optional($s->currentClassArm?->classLevel)->name.' '.optional($s->currentClassArm)->name,
                    $s->status,
                ]);
            }
            fclose($file);
        };
        return response()->streamDownload($callback, $filename, ['Content-Type'=>'text/csv','Content-Disposition'=>'attachment; filename="'.$filename.'"']);
    }

    // Export fee report to CSV
    public function feesCsv(Request $request)
    {
        $invoices = \App\Models\Invoice::with('student')
            ->when($request->session_id, fn($q) => $q->where('session_id',$request->session_id))
            ->get();

        $filename = 'Fees_Report_'.date('Y-m-d').'.csv';
        $callback = function () use ($invoices) {
            $file = fopen('php://output','w');
            fputcsv($file, ['Invoice No','Student','Amount Billed','Amount Paid','Balance','Status']);
            foreach ($invoices as $inv) {
                fputcsv($file, [
                    $inv->invoice_number,
                    $inv->student?->full_name,
                    $inv->total_amount,
                    $inv->amount_paid,
                    $inv->total_amount - $inv->amount_paid,
                    $inv->status,
                ]);
            }
            fclose($file);
        };
        return response()->streamDownload($callback, $filename, ['Content-Type'=>'text/csv','Content-Disposition'=>'attachment; filename="'.$filename.'"']);
    }
}

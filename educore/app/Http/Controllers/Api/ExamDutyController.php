<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ExamSupervisor;
use Illuminate\Http\Request;

class ExamDutyController extends Controller
{
    /** Personal supervision schedule for the signed-in staff member. */
    public function index(Request $request)
    {
        $duties = ExamSupervisor::with(['entry.classLevel', 'entry.subject', 'entry.examSession', 'entry.examPeriod'])
            ->where('user_id', $request->user()->id)
            ->whereHas('entry.examPeriod', fn ($q) => $q->where('status', 'published'))
            ->get()
            ->sortBy(fn ($s) => $s->entry->exam_date->toDateString() . '-' . $s->entry->examSession->sort_order)
            ->map(fn ($s) => [
                'id'          => $s->id,
                'exam_title'  => $s->entry->examPeriod->title,
                'date'        => $s->entry->exam_date->toDateString(),
                'session'     => $s->entry->examSession->name,
                'start_time'  => $s->entry->examSession->start_time,
                'end_time'    => $s->entry->examSession->end_time,
                'class_level' => $s->entry->classLevel->name,
                'subject'     => $s->entry->subject->name,
                'venue'       => $s->entry->venue,
            ])
            ->values();

        return response()->json(['duties' => $duties]);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\ClassArm;
use App\Models\TimetablePeriod;
use Illuminate\Http\Request;

class TimetableController extends Controller
{
    private const DAYS = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

    /** The teacher's own subject schedule across all classes. */
    public function mine(Request $request)
    {
        $user    = $request->user();
        $session = AcademicSession::current()->first();

        $periods = TimetablePeriod::with(['classArm.classLevel', 'subject'])
            ->where('teacher_id', $user->id)
            ->when($session, fn ($q) => $q->where('session_id', $session->id))
            ->get();

        return response()->json([
            'title' => 'My Teaching Schedule',
            'days'  => $this->groupByDay($periods, withClass: true),
        ]);
    }

    /** Full timetable for the class this user is form tutor of. */
    public function formClass(Request $request)
    {
        $user    = $request->user();
        $session = AcademicSession::current()->first();

        $arm = ClassArm::with('classLevel')
            ->where('form_tutor_id', $user->id)
            ->first();

        if (!$arm) {
            return response()->json(['message' => 'You are not a form tutor of any class.'], 404);
        }

        $periods = TimetablePeriod::with(['subject', 'teacher'])
            ->where('class_arm_id', $arm->id)
            ->when($session, fn ($q) => $q->where('session_id', $session->id))
            ->get();

        return response()->json([
            'title' => trim(optional($arm->classLevel)->name . ' ' . $arm->name) . ' Timetable',
            'days'  => $this->groupByDay($periods, withTeacher: true),
        ]);
    }

    private function groupByDay($periods, bool $withClass = false, bool $withTeacher = false): array
    {
        $byDay = [];
        foreach (self::DAYS as $day) {
            $slots = $periods
                ->filter(fn ($p) => strcasecmp((string) $p->day_of_week, $day) === 0)
                ->sortBy('start_time')
                ->map(function ($p) use ($withClass, $withTeacher) {
                    $row = [
                        'start'   => substr((string) $p->start_time, 0, 5),
                        'end'     => substr((string) $p->end_time, 0, 5),
                        'subject' => optional($p->subject)->name ?? '—',
                        'venue'   => $p->venue,
                    ];
                    if ($withClass) {
                        $row['class'] = trim(optional(optional($p->classArm)->classLevel)->name . ' ' . optional($p->classArm)->name);
                    }
                    if ($withTeacher) {
                        $row['teacher'] = optional($p->teacher)->name ?? '—';
                    }
                    return $row;
                })->values();

            if ($slots->isNotEmpty()) {
                $byDay[] = ['day' => $day, 'periods' => $slots];
            }
        }
        return $byDay;
    }
}

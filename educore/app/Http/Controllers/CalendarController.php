<?php
namespace App\Http\Controllers;

use App\Models\CalendarEvent;
use App\Models\AcademicSession;
use Illuminate\Http\Request;

class CalendarController extends Controller
{
    public function index()
    {
        $sessions = AcademicSession::orderByDesc('is_current')->get();
        $current  = AcademicSession::where('is_current', true)->first();
        $events   = CalendarEvent::when($current, fn($q) => $q->where(function($q2) use ($current) {
            $q2->where('session_id', $current->id)->orWhereNull('session_id');
        }))->orderBy('start_date')->get();
        return view('calendar.index', compact('events', 'sessions', 'current'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'       => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'start_date'  => ['required', 'date'],
            'end_date'    => ['nullable', 'date', 'after_or_equal:start_date'],
            'type'        => ['required', 'in:holiday,exam,pta,event,resumption,closing,other'],
            'color'       => ['nullable', 'string', 'max:20'],
            'session_id'  => ['nullable', 'exists:academic_sessions,id'],
            'is_public'   => ['boolean'],
        ]);
        $data['created_by'] = auth()->id();
        $data['is_public']  = $request->boolean('is_public', true);
        CalendarEvent::create($data);
        return back()->with('success', 'Event added to calendar.');
    }

    public function update(Request $request, CalendarEvent $event)
    {
        $data = $request->validate([
            'title'      => ['required', 'string', 'max:150'],
            'start_date' => ['required', 'date'],
            'end_date'   => ['nullable', 'date'],
            'type'       => ['required', 'string'],
            'color'      => ['nullable', 'string'],
        ]);
        $event->update($data);
        return back()->with('success', 'Event updated.');
    }

    public function destroy(CalendarEvent $event)
    {
        $event->delete();
        return back()->with('success', 'Event removed.');
    }

    public function apiEvents(Request $request)
    {
        $events = CalendarEvent::when($request->session_id, fn($q) => $q->where('session_id', $request->session_id))
            ->get()->map(fn($e) => [
                'id'    => $e->id,
                'title' => $e->title,
                'start' => $e->start_date,
                'end'   => $e->end_date ?? $e->start_date,
                'color' => $e->color ?? '#2563EB',
                'type'  => $e->type,
            ]);
        return response()->json($events);
    }
}

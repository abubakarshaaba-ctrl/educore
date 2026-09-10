<?php
namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\CalendarEvent;
use App\Models\AcademicSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

        DB::transaction(function () use ($data): void {
            $event = CalendarEvent::create($data);

            if ($event->is_public) {
                Announcement::create([
                    'tenant_id' => auth()->user()->tenant_id,
                    'title' => $event->title,
                    'body' => $this->eventNoticeBody($event),
                    'audience' => 'all',
                    'priority' => 'normal',
                    'publish_date' => today(),
                    'expire_date' => $event->end_date ?: $event->start_date,
                    'is_published' => true,
                    'created_by' => auth()->id(),
                ]);
            }
        });

        return back()->with('success', 'Event added to calendar and published as a notice.');
    }

    public function update(Request $request, CalendarEvent $event)
    {
        abort_unless((int) $event->tenant_id === (int) auth()->user()->tenant_id, 404);

        $data = $request->validate([
            'title'      => ['required', 'string', 'max:150'],
            'description'=> ['nullable', 'string'],
            'start_date' => ['required', 'date'],
            'end_date'   => ['nullable', 'date', 'after_or_equal:start_date'],
            'type'       => ['required', 'in:holiday,exam,pta,event,resumption,closing,other'],
            'color'      => ['nullable', 'string', 'max:20'],
            'is_public'  => ['boolean'],
        ]);
        $data['is_public'] = $request->boolean('is_public', $event->is_public);
        $event->update($data);

        return back()->with('success', 'Event updated.');
    }

    public function destroy(CalendarEvent $event)
    {
        abort_unless((int) $event->tenant_id === (int) auth()->user()->tenant_id, 404);
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

    private function eventNoticeBody(CalendarEvent $event): string
    {
        $parts = array_filter([
            $event->description,
            'Date: '.optional($event->start_date)->format('d M Y'),
            $event->end_date && $event->end_date->ne($event->start_date)
                ? 'Ends: '.optional($event->end_date)->format('d M Y')
                : null,
        ]);

        return implode("\n", $parts);
    }
}

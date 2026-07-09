<?php

namespace App\Http\Controllers;

use App\Models\VisitorLog;
use Illuminate\Http\Request;

class VisitorLogController extends Controller
{
    public function index()
    {
        $visitors = VisitorLog::with('recorder')->latest('check_in_at')->paginate(25);
        $checkedIn = VisitorLog::whereNull('check_out_at')->count();

        return view('visitors.index', compact('visitors', 'checkedIn'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'visitor_name' => ['required', 'string', 'max:150'],
            'phone'        => ['nullable', 'string', 'max:30'],
            'purpose'      => ['nullable', 'string', 'max:200'],
            'host_name'    => ['nullable', 'string', 'max:150'],
            'badge_number' => ['nullable', 'string', 'max:40'],
        ]);

        $data['check_in_at'] = now();
        $data['recorded_by'] = auth()->id();

        VisitorLog::create($data);

        return back()->with('success', 'Visitor checked in.');
    }

    public function checkOut(VisitorLog $visitor)
    {
        $visitor->update(['check_out_at' => now()]);
        return back()->with('success', 'Visitor checked out.');
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Services\Mobile\MobileCommunicationService;
use Illuminate\Http\Request;

class MobileCommunicationController extends Controller
{
    public function __construct(private readonly MobileCommunicationService $communications) {}

    public function notifications(Request $request)
    {
        $data = $request->validate([
            'status' => ['nullable', 'in:all,read,unread'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        return response()->json($this->communications->notifications(
            $request->user(),
            $data['status'] ?? 'all',
            (int) ($data['per_page'] ?? 20),
        ));
    }

    public function read(Request $request, Announcement $announcement)
    {
        return response()->json(['notification' => $this->communications->markRead($request->user(), $announcement)]);
    }

    public function readAll(Request $request)
    {
        return response()->json([
            'message' => 'Notifications marked as read.',
            'updated' => $this->communications->markAllRead($request->user()),
        ]);
    }

    public function events(Request $request)
    {
        $data = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        return response()->json($this->communications->events($request->user(), $data['from'] ?? null, $data['to'] ?? null));
    }
}

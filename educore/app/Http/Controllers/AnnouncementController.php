<?php
namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Services\Notifications\ActivityEmailService;
use App\Services\Notifications\PushNotificationService;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $announcements = Announcement::where('is_published', true)
            ->where(fn($q) => $q->whereNull('expire_date')->orWhere('expire_date','>=',today()))
            ->when(! $user?->isAdmin(), fn ($query) => $query->whereNull('platform_broadcast_id'))
            ->orderByDesc('priority')
            ->orderByDesc('publish_date')
            ->paginate(15);

        return view('announcements.index', compact('announcements'));
    }

    public function manage()
    {
        $announcements = Announcement::whereNull('platform_broadcast_id')
            ->latest()
            ->paginate(20);

        return view('announcements.manage', compact('announcements'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'        => ['required','string','max:150'],
            'body'         => ['required','string'],
            'audience'     => ['required','in:all,staff,students,parents,admin'],
            'priority'     => ['required','in:normal,important,urgent'],
            'publish_date' => ['required','date'],
            'expire_date'  => ['nullable','date','after:publish_date'],
            'is_published' => ['boolean'],
        ]);
        $data['created_by']   = auth()->id();
        $data['is_published'] = $request->boolean('is_published', true);
        $announcement = Announcement::create($data);
        if ($announcement->is_published) {
            app(PushNotificationService::class)->notifyAnnouncementPublished($announcement);
            app(ActivityEmailService::class)->notifyAnnouncementPublished($announcement);
        }
        return back()->with('success', 'Announcement published.');
    }

    public function destroy(Announcement $announcement)
    {
        abort_if($announcement->platform_broadcast_id !== null, 403, 'Platform broadcasts are managed by EduCore.');

        $announcement->delete();
        return back()->with('success', 'Announcement deleted.');
    }

    public function toggle(Announcement $announcement)
    {
        abort_if($announcement->platform_broadcast_id !== null, 403, 'Platform broadcasts are managed by EduCore.');

        $announcement->update(['is_published' => !$announcement->is_published]);
        if ($announcement->is_published) {
            app(PushNotificationService::class)->notifyAnnouncementPublished($announcement);
            app(ActivityEmailService::class)->notifyAnnouncementPublished($announcement);
        }
        return back()->with('success', 'Announcement status updated.');
    }
}

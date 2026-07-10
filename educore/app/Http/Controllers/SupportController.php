<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SupportController extends Controller
{
    private function tenantId(): int
    {
        return (int) auth()->user()->tenant_id;
    }

    public function index()
    {
        $tickets = DB::table('platform_support_tickets')
            ->where('tenant_id', $this->tenantId())
            ->orderByDesc('created_at')
            ->get();

        return view('support.index', compact('tickets'));
    }

    public function platformNotices()
    {
        $tenantId = $this->tenantId();
        $tenant   = auth()->user()->tenant;

        // Determine tenant status for filtering ("trial" = on the free
        // pay-per-student tier, i.e. hasn't paid for extra capacity yet)
        $isOnTrial = \App\Services\PricingService::isFree(\App\Services\PricingService::activeStudentCount($tenantId));
        $tenantStatus = $isOnTrial ? 'trial' : ($tenant && $tenant->is_active ? 'active' : 'expired');

        $notices = DB::table('platform_broadcasts')
            ->whereIn('target', ['all', $tenantStatus])
            ->select(
                'platform_broadcasts.*',
                DB::raw("EXISTS(SELECT 1 FROM platform_broadcast_dismissals WHERE broadcast_id = platform_broadcasts.id AND tenant_id = {$tenantId}) as dismissed")
            )
            ->orderByDesc('created_at')
            ->get();

        return view('support.notices', compact('notices'));
    }

    public function dismissBroadcast($id)
    {
        try {
            DB::table('platform_broadcast_dismissals')->insertOrIgnore([
                'broadcast_id' => $id,
                'tenant_id'    => $this->tenantId(),
                'dismissed_at' => now(),
            ]);
        } catch (\Exception $e) {}

        return back();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:150'],
            'body'    => ['required', 'string', 'max:3000'],
        ]);

        DB::table('platform_support_tickets')->insert([
            'tenant_id'  => $this->tenantId(),
            'user_id'    => auth()->id(),
            'subject'    => $data['subject'],
            'body'       => $data['body'],
            'status'     => 'open',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Your support request has been sent to the platform team.');
    }
}

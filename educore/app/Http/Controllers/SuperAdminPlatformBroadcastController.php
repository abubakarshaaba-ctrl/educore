<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SuperAdminPlatformBroadcastController extends Controller
{
    public function store(Request $request)
    {
        $user = $request->user();
        abort_unless($user && $user->isSuperAdmin(), 403, 'Super Admin access required.');

        $data = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'body' => ['required', 'string', 'max:5000'],
            'target' => ['required', 'in:all,trial,active,expired'],
            'audience' => ['required', 'in:staff,admins,all_accounts'],
            'priority' => ['required', 'in:normal,high,urgent'],
            'expires_at' => ['nullable', 'date', 'after:today'],
        ]);

        DB::table('platform_broadcasts')->insert([
            'title' => trim($data['title']),
            'body' => trim($data['body']),
            'target' => $data['target'],
            'audience' => $data['audience'],
            'priority' => $data['priority'],
            'created_by' => $user->id,
            'expires_at' => $data['expires_at'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $audienceLabel = match ($data['audience']) {
            'admins' => 'school administrators',
            'all_accounts' => 'all tenant accounts',
            default => 'tenant staff',
        };

        return back()->with('success', "Platform notice published to {$audienceLabel}.");
    }
}

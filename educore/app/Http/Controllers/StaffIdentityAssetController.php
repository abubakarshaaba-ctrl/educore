<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AuthenticatedIdentityAssetStorage;
use Illuminate\Http\Request;

class StaffIdentityAssetController extends Controller
{
    public function __invoke(Request $request, int $staff, AuthenticatedIdentityAssetStorage $assets)
    {
        $user = $request->user();
        abort_unless($user && $user->tenant_id, 403);

        $member = User::query()
            ->where('tenant_id', $user->tenant_id)
            ->whereKey($staff)
            ->firstOrFail();

        $canRead = (int) $member->id === (int) $user->id
            || $user->isAdmin()
            || $user->canAccessModule('staff');
        abort_unless($canRead, 403, 'Staff identity access required.');

        $path = $assets->resolveAbsolutePath($member->passport_photo);
        if (!$path) {
            abort(404, 'No passport photo on file.');
        }

        return response()->file($path, [
            'Cache-Control' => 'no-store, private',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; sandbox",
        ]);
    }
}

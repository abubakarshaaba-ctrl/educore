<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Services\PlatformTenantRemovalService;
use Illuminate\Http\Request;

class PlatformTenantRemovalController extends Controller
{
    public function __construct(private PlatformTenantRemovalService $removal) {}

    public function destroy(Request $request, Tenant $tenant)
    {
        $user = $request->user();
        abort_unless($user?->isSuperAdmin(), 403, 'Platform Super Admin access required.');

        $data = $request->validate([
            'confirmation' => ['required', 'string', 'max:150'],
            'current_password' => ['required', 'string', 'max:255'],
            'reason' => ['required', 'string', 'min:10', 'max:500'],
        ]);

        $this->removal->remove(
            tenant: $tenant,
            actor: $user,
            confirmation: $data['confirmation'],
            currentPassword: $data['current_password'],
            reason: $data['reason'],
            request: $request,
        );

        $message = 'School removed. Accounts, mobile sessions and push subscriptions were disabled; records remain recoverable for audit.';
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json(['message' => $message]);
        }

        return redirect()->route('super.tenants')->with('success', $message);
    }
}

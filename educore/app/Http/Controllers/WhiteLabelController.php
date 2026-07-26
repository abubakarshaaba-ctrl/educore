<?php
namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Services\TenantHostResolver;
use Illuminate\Http\Request;

class WhiteLabelController extends Controller
{
    public function settings(Tenant $tenant)
    {
        $this->guard();
        return view('super.white-label', compact('tenant'));
    }

    public function save(Request $request, Tenant $tenant, TenantHostResolver $hosts)
    {
        $this->guard();
        $data = $request->validate([
            'custom_domain'   => ['nullable', 'string', 'max:200', function ($attribute, $value, $fail) use ($hosts, $tenant) {
                $normalized = $hosts->validateCustomDomain($value);
                if ($value && !$normalized) {
                    $fail('Enter a valid local custom domain that is not a central EduCore host.');
                    return;
                }

                if ($normalized && Tenant::where('custom_domain', $normalized)->whereKeyNot($tenant->id)->exists()) {
                    $fail('This custom domain is already assigned to another school.');
                }
            }],
        ]);

        if ($data['custom_domain'] ?? null) {
            $data['custom_domain'] = $hosts->validateCustomDomain($data['custom_domain']);
        } else {
            $data['domain_verified'] = false;
        }

        $tenant->update($data);

        if ($data['custom_domain'] ?? null) {
            $tenant->update(['domain_verified' => false]);
        }

        return back()->with('success', 'White-label settings saved.');
    }

    public function verifyDomain(Tenant $tenant, TenantHostResolver $hosts)
    {
        $this->guard();
        $domain = $hosts->validateCustomDomain($tenant->custom_domain);

        if (!$domain) {
            return back()->withErrors(['error' => 'No custom domain set.']);
        }

        $tenant->update([
            'custom_domain' => $domain,
            'domain_verified' => true,
        ]);

        return back()->with('success', 'Local custom domain verified for this PC.');
    }

    private function guard(): void
    {
        abort_unless(auth()->user()?->is_super_admin, 403);
    }
}

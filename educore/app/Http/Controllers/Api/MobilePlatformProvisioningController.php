<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use App\Services\PlatformTenantProvisioningService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MobilePlatformProvisioningController extends Controller
{
    public function store(
        Request $request,
        PlatformTenantProvisioningService $provisioning,
    ): JsonResponse {
        /** @var User|null $actor */
        $actor = $request->user();
        abort_unless($actor, 401);
        abort_unless($actor->isSuperAdmin(), 403, 'Platform Super Admin access required.');

        $request->merge([
            'slug' => Tenant::normalizeSlug($request->input('slug')),
            'subdomain' => $request->filled('subdomain')
                ? Tenant::normalizeSlug($request->input('subdomain'))
                : null,
        ]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'slug' => Tenant::slugRules(),
            'subdomain' => [
                'nullable',
                'string',
                'max:80',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('tenants', 'subdomain'),
            ],
            'email' => ['required', 'email', 'max:180'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:255'],
            'admin_name' => ['required', 'string', 'max:150'],
            'admin_email' => ['required', 'email', 'max:180', Rule::unique('users', 'email')],
            'admin_password' => ['required', 'string', 'min:8', 'max:255'],
            'admin_employment_started_at' => ['required', 'date', 'before_or_equal:today'],
        ]);
        $data['source'] = 'native_platform';

        $result = $provisioning->provision($data, $actor, $request);
        $tenant = $result['tenant'];
        $admin = $result['admin'];

        return response()->json([
            'message' => 'School provisioned successfully. Complete the remaining onboarding steps before normal operations.',
            'tenant' => [
                'id' => (int) $tenant->id,
                'name' => $tenant->name,
                'slug' => $tenant->slug,
                'subdomain' => $tenant->subdomain,
                'status' => $tenant->status,
            ],
            'administrator' => [
                'id' => (int) $admin->id,
                'name' => $admin->name,
                'email' => $admin->email,
            ],
        ], 201);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\SchoolSetting;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MobileSchoolSettingsController extends Controller
{
    private const EXTRA_KEYS = ['website', 'established_year', 'proprietor', 'slogan'];

    public function show(Request $request): JsonResponse
    {
        $user = $this->guard($request);
        $tenant = $user->tenant;
        abort_unless($tenant, 404, 'School account unavailable.');

        return response()->json($this->payload($user, $tenant));
    }

    public function update(Request $request): JsonResponse
    {
        $user = $this->guard($request, manage: true);
        $tenant = $user->tenant;
        abort_unless($tenant, 404, 'School account unavailable.');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'motto' => ['nullable', 'string', 'max:200'],
            'address' => ['nullable', 'string', 'max:300'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:180'],
            'website' => ['nullable', 'url', 'max:255'],
            'established_year' => ['nullable', 'integer', 'min:1800', 'max:'.now()->year],
            'proprietor' => ['nullable', 'string', 'max:150'],
            'slogan' => ['nullable', 'string', 'max:200'],
        ]);

        $before = [
            'name' => $tenant->name,
            'motto' => $tenant->motto,
            'address' => $tenant->address,
            'phone' => $tenant->phone,
            'email' => $tenant->email,
        ];

        DB::transaction(function () use ($tenant, $data, $user, $request, $before): void {
            $tenant->update([
                'name' => trim($data['name']),
                'motto' => $this->nullableText($data['motto'] ?? null),
                'address' => $this->nullableText($data['address'] ?? null),
                'phone' => $this->nullableText($data['phone'] ?? null),
                'email' => $this->nullableText($data['email'] ?? null),
            ]);

            foreach (self::EXTRA_KEYS as $key) {
                $value = $data[$key] ?? null;
                if ($value === null || trim((string) $value) === '') {
                    SchoolSetting::where('tenant_id', $tenant->id)->where('key', $key)->delete();
                    continue;
                }
                SchoolSetting::updateOrCreate(
                    ['tenant_id' => $tenant->id, 'key' => $key],
                    ['value' => trim((string) $value), 'group' => 'general']
                );
            }

            if (Schema::hasTable('audit_logs')) {
                AuditLog::create([
                    'tenant_id' => $tenant->id,
                    'actor_user_id' => $user->id,
                    'auditable_type' => Tenant::class,
                    'auditable_id' => $tenant->id,
                    'action' => 'school.settings.updated.via_mobile',
                    'old_values' => $before,
                    'new_values' => [
                        'name' => $tenant->name,
                        'motto' => $tenant->motto,
                        'address' => $tenant->address,
                        'phone' => $tenant->phone,
                        'email' => $tenant->email,
                        'general_metadata_updated' => true,
                    ],
                    'reason' => null,
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);
            }
        });

        return response()->json([
            'message' => 'School settings updated.',
            ...$this->payload($user, $tenant->fresh()),
        ]);
    }

    private function payload(User $user, Tenant $tenant): array
    {
        $extras = SchoolSetting::where('tenant_id', $tenant->id)
            ->whereIn('key', self::EXTRA_KEYS)
            ->pluck('value', 'key');

        return [
            'contract_version' => 1,
            'capabilities' => ['manage' => $user->canManage('settings')],
            'school' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'motto' => $tenant->motto,
                'address' => $tenant->address,
                'phone' => $tenant->phone,
                'email' => $tenant->email,
                'website' => $extras->get('website'),
                'established_year' => $extras->get('established_year'),
                'proprietor' => $extras->get('proprietor'),
                'slogan' => $extras->get('slogan'),
                'logo_configured' => filled($tenant->logo_path),
                'authorized_signature_configured' => filled($tenant->authorized_signature_path),
            ],
        ];
    }

    private function guard(Request $request, bool $manage = false): User
    {
        /** @var User|null $user */
        $user = $request->user();
        abort_unless($user, 401);
        abort_if($user->isStudent() || $user->isParent() || $user->isSuperAdmin(), 403, 'School staff access required.');
        abort_unless($user->tenant_id, 403, 'School account required.');
        abort_unless(
            $manage ? $user->canManage('settings') : $user->canAccessModule('settings'),
            403,
            $manage ? 'School settings management permission required.' : 'School settings access required.'
        );
        return $user;
    }

    private function nullableText(mixed $value): ?string
    {
        $text = trim((string) ($value ?? ''));
        return $text === '' ? null : $text;
    }
}

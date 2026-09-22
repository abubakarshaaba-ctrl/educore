<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Auth\AccountSessionRevoker;
use App\Services\Auth\AuthAuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    /** Return the authenticated user's self-service profile. */
    public function show(Request $request)
    {
        return response()->json([
            'profile' => $this->profilePayload($request->user()),
        ]);
    }

    /**
     * Update personal/contact details only.
     * Administrative identity fields (tenant, role, staff ID, employment state,
     * permissions, etc.) deliberately remain server/admin controlled.
     */
    public function update(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => [
                'sometimes',
                'required',
                'email:rfc',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'phone' => ['sometimes', 'nullable', 'string', 'max:40'],
            'date_of_birth' => ['sometimes', 'nullable', 'date', 'before_or_equal:today'],
            'gender' => ['sometimes', 'nullable', 'string', 'max:30'],
            'address' => ['sometimes', 'nullable', 'string', 'max:500'],
        ]);

        foreach (['phone', 'date_of_birth', 'gender', 'address'] as $nullableField) {
            if (array_key_exists($nullableField, $data) && $data[$nullableField] === '') {
                $data[$nullableField] = null;
            }
        }

        $user->fill($data)->save();

        return response()->json([
            'message' => 'Profile updated successfully.',
            'profile' => $this->profilePayload($user->fresh()),
        ]);
    }

    /** Change the authenticated user's password after verifying the current one. */
    public function updatePassword(
        Request $request,
        AccountSessionRevoker $sessions,
        AuthAuditLogger $audit
    ) {
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'confirmed', Password::min(8), 'max:128'],
        ]);

        $user = $request->user();

        if (!Hash::check($data['current_password'], $user->password)) {
            return response()->json([
                'message' => 'The current password is incorrect.',
                'errors' => ['current_password' => ['The current password is incorrect.']],
            ], 422);
        }

        $wasAdministrativeRecovery = (bool) $user->must_change_password;

        $user->forceFill([
            'password' => $data['password'],
            'must_change_password' => false,
            'remember_token' => Str::random(60),
        ])->save();

        $currentToken = $request->attributes->get('api_token');
        $revoked = $sessions->revoke($user, null, $currentToken?->id);

        $audit->recordForUser(
            $user,
            $wasAdministrativeRecovery
                ? 'auth.password_change.required_completed'
                : 'auth.password_change.completed',
            [
                'surface' => 'mobile_api',
                'administrative_recovery_completed' => $wasAdministrativeRecovery,
                'other_api_tokens_revoked' => $revoked['api_tokens_revoked'],
                'web_sessions_revoked' => $revoked['sessions_revoked'],
            ],
            $request,
            null,
            $user,
        );

        return response()->json([
            'message' => $wasAdministrativeRecovery
                ? 'Password updated. Your account recovery is complete.'
                : 'Password changed successfully.',
            'must_change_password' => false,
        ]);
    }

    /** Upload or replace the passport/profile photograph. */
    public function uploadPassport(Request $request)
    {
        $request->validate([
            'passport' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);

        $user = $request->user();
        $oldPath = $this->normalisePublicPath($user->passport_photo);

        $path = $request->file('passport')->store('passports', 'public');
        $user->forceFill(['passport_photo' => $path])->save();

        if ($oldPath && $oldPath !== $path && Storage::disk('public')->exists($oldPath)) {
            Storage::disk('public')->delete($oldPath);
        }

        return response()->json([
            'message' => 'Passport photograph updated successfully.',
            'profile' => $this->profilePayload($user->fresh()),
        ]);
    }

    /** Stream the current passport through the authenticated API. */
    public function passportFile(Request $request)
    {
        $path = $this->normalisePublicPath($request->user()->passport_photo);

        if (!$path || !Storage::disk('public')->exists($path)) {
            abort(404, 'No passport photograph is on file.');
        }

        return Storage::disk('public')->response($path, null, [
            'Cache-Control' => 'no-cache, private',
        ]);
    }

    private function profilePayload($user): array
    {
        $path = $this->normalisePublicPath($user->passport_photo);
        $hasPassport = $path && Storage::disk('public')->exists($path);

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'date_of_birth' => optional($user->date_of_birth)->format('Y-m-d'),
            'gender' => $user->gender,
            'address' => $user->address,
            'staff_id' => $user->staff_id,
            'role_key' => $user->roleKey(),
            'role' => $user->roleLabel(),
            'portal' => $user->isSuperAdmin() ? 'platform' : ($user->roleKey() === 'student' ? 'student' : ($user->roleKey() === 'parent' ? 'parent' : 'staff')),
            'has_passport' => (bool) $hasPassport,
            'passport_version' => $hasPassport ? substr(md5((string) $path), 0, 10) : null,
            'passport_url' => $hasPassport ? asset('storage/' . $path) . '?v=' . substr(md5((string) $path), 0, 8) : null,
        ];
    }

    private function normalisePublicPath(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        return preg_replace('#^storage/#', '', ltrim($path, '/'));
    }
}

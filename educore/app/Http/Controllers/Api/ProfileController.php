<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
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
    public function updatePassword(Request $request)
    {
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

        $user->password = $data['password'];
        $user->save();

        return response()->json([
            'message' => 'Password changed successfully.',
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

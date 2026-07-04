<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function edit()
    {
        $user = auth()->user();
        $salarySetting = \App\Models\StaffSalarySetting::where('tenant_id', $user->tenant_id)
            ->where('staff_id', $user->id)
            ->first();
        return view('profile.edit', compact('user', 'salarySetting'));
    }

    public function update(Request $request)
    {
        $user = auth()->user();

        $data = $request->validate([
            'name'          => ['required', 'string', 'max:150'],
            'phone'         => ['nullable', 'string', 'max:30'],
            'date_of_birth' => ['nullable', 'date'],
            'address'       => ['nullable', 'string', 'max:255'],
        ]);

        $user->update($data);

        return back()->with('success', 'Profile updated successfully.');
    }

    /**
     * Staff self-service entry of bank/account/TIN details, used by payroll.
     * Allowed once — after the first save, bank_details_locked is set and these
     * fields become read-only here. Only the accountant (Payroll → Salary
     * Settings, a separate page unaffected by this lock) can change them after that.
     */
    public function updateBankDetails(Request $request)
    {
        $user = auth()->user();

        $setting = \App\Models\StaffSalarySetting::where('tenant_id', $user->tenant_id)
            ->where('staff_id', $user->id)
            ->first();

        if ($setting && $setting->bank_details_locked) {
            return back()->withErrors(['error' => 'Your bank details are locked. Contact the accountant to make changes.']);
        }

        $data = $request->validate([
            'bank_name'                  => ['required', 'string', 'max:100'],
            'account_number'             => ['required', 'string', 'size:10'],
            'account_name'               => ['required', 'string', 'max:150'],
            'tax_identification_number'  => ['nullable', 'string', 'max:30'],
            'bvn'                        => ['nullable', 'string', 'digits:11'],
            'nin'                        => ['nullable', 'string', 'digits:11'],
        ]);

        \App\Models\StaffSalarySetting::updateOrCreate(
            ['tenant_id' => $user->tenant_id, 'staff_id' => $user->id],
            array_merge($data, ['is_active' => true, 'bank_details_locked' => true])
        );

        return back()->with('success', 'Bank and payroll details saved. These are now locked — contact the accountant for any future changes.');
    }

    public function updateEmail(Request $request)
    {
        $user = auth()->user();

        $data = $request->validate([
            'email' => [
                'required', 'email', 'max:180',
                \Illuminate\Validation\Rule::unique('users', 'email')->ignore($user->id),
            ],
            'current_password' => ['required'],
        ]);

        if (!\Illuminate\Support\Facades\Hash::check($data['current_password'], $user->password)) {
            return back()->withErrors(['current_password' => 'Incorrect password. Email not updated.']);
        }

        if ($user->email === $data['email']) {
            return back()->withErrors(['email' => 'That is already your current email address.']);
        }

        $user->update(['email' => $data['email']]);

        return back()->with('success', 'Email address updated to ' . $data['email']);
    }

    public function updatePassword(Request $request)
    {
        $user = auth()->user();

        $data = $request->validate([
            'current_password' => ['required'],
            'password'         => ['required', Password::min(8), 'confirmed'],
        ]);

        if (!Hash::check($data['current_password'], $user->password)) {
            return back()->withErrors(['current_password' => 'Current password is incorrect.']);
        }

        $user->update(['password' => Hash::make($data['password'])]);

        return back()->with('success_pw', 'Password changed successfully.');
    }

    public function updatePhoto(Request $request)
    {
        $request->validate([
            'passport_photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $user = auth()->user();

        // Delete old photo if it exists
        if ($user->passport_photo && Storage::disk('public')->exists($user->passport_photo)) {
            Storage::disk('public')->delete($user->passport_photo);
        }

        $path = $request->file('passport_photo')->store('passports', 'public');
        $user->update(['passport_photo' => $path]);

        return back()->with('success', 'Profile photo updated.');
    }
}

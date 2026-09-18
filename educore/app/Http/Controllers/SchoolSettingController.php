<?php
namespace App\Http\Controllers;

use App\Models\SchoolSetting;
use App\Services\SchoolWeekService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SchoolSettingController extends Controller
{
    public function index(SchoolWeekService $schoolWeek)
    {
        $tenant = auth()->user()->tenant;
        $settings = SchoolSetting::where('tenant_id', $tenant->id)->get()->keyBy('key');
        $schoolOpenDays = $schoolWeek->openDays((int) $tenant->id);
        $schoolDayLabels = SchoolWeekService::DAY_LABELS;

        return view('settings.index', compact('tenant', 'settings', 'schoolOpenDays', 'schoolDayLabels'));
    }

    public function update(Request $request, SchoolWeekService $schoolWeek)
    {
        $tenant = auth()->user()->tenant;
        $currentYear = (int) date('Y');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'motto' => ['nullable', 'string', 'max:200'],
            'proprietor' => ['nullable', 'string', 'max:180'],
            'established_year' => ['nullable', 'integer', 'min:1800', 'max:' . $currentYear],
            'website' => ['nullable', 'url', 'max:255'],
            'address' => ['nullable', 'string', 'max:300'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:255'],
            'school_state' => ['nullable', 'string', 'max:100'],
            'school_lga' => ['nullable', 'string', 'max:120'],
            'school_senatorial_district' => ['nullable', 'string', 'max:160'],
            'emis_code' => ['nullable', 'string', 'max:100'],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
            'authorized_signature' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
            'school_open_days' => ['required', 'array', 'min:1', 'max:7'],
            'school_open_days.*' => ['integer', 'in:1,2,3,4,5,6,7'],
            'parallel_curriculum_enabled' => ['nullable', 'boolean'],
        ]);

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store("logos/{$tenant->id}", 'public');
            $oldPath = $tenant->logo_path;
            $tenant->update(['logo_path' => $path]);

            if ($oldPath && $oldPath !== $path) {
                Storage::disk('public')->delete($oldPath);
            }
        }

        if ($request->hasFile('authorized_signature')) {
            $path = $request->file('authorized_signature')->store("signatures/{$tenant->id}", 'public');
            $oldPath = $tenant->authorized_signature_path;
            $tenant->update(['authorized_signature_path' => $path]);

            if ($oldPath && $oldPath !== $path) {
                Storage::disk('public')->delete($oldPath);
            }
        }

        $tenant->update([
            'name' => $data['name'],
            'motto' => $data['motto'] ?? null,
            'address' => $data['address'] ?? null,
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
        ]);

        $settingGroups = [
            'proprietor' => 'general',
            'established_year' => 'general',
            'website' => 'general',
            'school_state' => 'location',
            'school_lga' => 'location',
            'school_senatorial_district' => 'location',
            'emis_code' => 'registration',
        ];

        foreach ($settingGroups as $key => $group) {
            $value = $data[$key] ?? null;
            if (is_string($value)) {
                $value = trim($value);
                $value = $value === '' ? null : $value;
            }

            SchoolSetting::updateOrCreate(
                ['tenant_id' => $tenant->id, 'key' => $key],
                ['value' => $value, 'group' => $group]
            );
        }

        SchoolSetting::updateOrCreate(
            ['tenant_id' => $tenant->id, 'key' => 'parallel_curriculum_enabled'],
            ['value' => $request->boolean('parallel_curriculum_enabled') ? '1' : '0', 'group' => 'academic']
        );

        $schoolWeek->save((int) $tenant->id, $data['school_open_days']);

        return back()->with('success', 'School settings saved successfully.');
    }
}

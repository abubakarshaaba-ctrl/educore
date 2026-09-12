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
        $tenant   = auth()->user()->tenant;
        $settings = SchoolSetting::where('tenant_id', $tenant->id)->get()->keyBy('key');
        $schoolOpenDays = $schoolWeek->openDays((int) $tenant->id);
        $schoolDayLabels = SchoolWeekService::DAY_LABELS;

        return view('settings.index', compact('tenant', 'settings', 'schoolOpenDays', 'schoolDayLabels'));
    }

    public function update(Request $request, SchoolWeekService $schoolWeek)
    {
        $tenant = auth()->user()->tenant;
        $data   = $request->validate([
            'name'          => ['required', 'string', 'max:150'],
            'motto'         => ['nullable', 'string', 'max:200'],
            'address'       => ['nullable', 'string', 'max:300'],
            'phone'         => ['nullable', 'string', 'max:20'],
            'email'         => ['nullable', 'email'],
            'website'       => ['nullable', 'url'],
            'logo'          => ['nullable', 'image', 'max:2048'],
            'authorized_signature' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
            'school_open_days' => ['nullable', 'array', 'min:1', 'max:7'],
            'school_open_days.*' => ['integer', 'in:1,2,3,4,5,6,7'],
        ]);

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store("logos/{$tenant->id}", 'public');
            if ($tenant->logo_path && $tenant->logo_path !== $path) {
                Storage::disk('public')->delete($tenant->logo_path);
            }
            $tenant->update(['logo_path' => $path]);
        }

        if ($request->hasFile('authorized_signature')) {
            $path = $request->file('authorized_signature')
                ->store("signatures/{$tenant->id}", 'public');

            if ($tenant->authorized_signature_path && $tenant->authorized_signature_path !== $path) {
                Storage::disk('public')->delete($tenant->authorized_signature_path);
            }

            $tenant->update(['authorized_signature_path' => $path]);
        }

        $tenant->update([
            'name'    => $data['name'],
            'motto'   => $data['motto'] ?? null,
            'address' => $data['address'] ?? null,
            'phone'   => $data['phone'] ?? null,
            'email'   => $data['email'] ?? null,
        ]);

        $extras = ['website', 'established_year', 'proprietor', 'slogan'];
        foreach ($extras as $key) {
            if ($request->filled($key)) {
                SchoolSetting::updateOrCreate(
                    ['tenant_id' => $tenant->id, 'key' => $key],
                    ['value' => $request->input($key), 'group' => 'general']
                );
            }
        }

        if (array_key_exists('school_open_days', $data)) {
            $schoolWeek->save((int) $tenant->id, $data['school_open_days']);
        }

        return back()->with('success', 'School settings updated.');
    }
}

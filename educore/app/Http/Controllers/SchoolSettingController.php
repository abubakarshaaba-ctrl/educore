<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SchoolSetting;

class SchoolSettingController extends Controller
{
    public function index()
    {
        $tenant   = auth()->user()->tenant;
        $settings = SchoolSetting::where('tenant_id', $tenant->id)->get()->keyBy('key');
        return view('settings.index', compact('tenant', 'settings'));
    }

    public function update(Request $request)
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
        ]);

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store("logos/{$tenant->id}", 'public');
            // Delete old logo if different
            if ($tenant->logo_path && $tenant->logo_path !== $path) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($tenant->logo_path);
            }
            $tenant->update(['logo_path' => $path]); // store clean relative path e.g. logos/1/abc.png
        }

        $tenant->update([
            'name'          => $data['name'],
            'motto'         => $data['motto'] ?? null,
            'address'       => $data['address'] ?? null,
            'phone'         => $data['phone'] ?? null,
            'email'         => $data['email'] ?? null,
        ]);

        // Save extra settings
        $extras = ['website', 'established_year', 'proprietor', 'slogan'];
        foreach ($extras as $key) {
            if ($request->filled($key)) {
                SchoolSetting::updateOrCreate(
                    ['tenant_id' => $tenant->id, 'key' => $key],
                    ['value' => $request->input($key), 'group' => 'general']
                );
            }
        }

        return back()->with('success', 'School settings updated.');
    }

}

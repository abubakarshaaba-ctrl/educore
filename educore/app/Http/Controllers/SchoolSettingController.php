<?php
namespace App\Http\Controllers;

use App\Models\SchoolSetting;
use App\Services\AuthenticatedIdentityAssetStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SchoolSettingController extends Controller
{
    public function index()
    {
        $tenant   = auth()->user()->tenant;
        $settings = SchoolSetting::where('tenant_id', $tenant->id)->get()->keyBy('key');
        return view('settings.index', compact('tenant', 'settings'));
    }

    public function update(Request $request, AuthenticatedIdentityAssetStorage $assets)
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
        ]);

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store("logos/{$tenant->id}", 'public');
            if ($tenant->logo_path && $tenant->logo_path !== $path) {
                Storage::disk('public')->delete($tenant->logo_path);
            }
            $tenant->update(['logo_path' => $path]);
        }

        if ($request->hasFile('authorized_signature')) {
            $oldPath = $tenant->authorized_signature_path;
            $path = $assets->store($request->file('authorized_signature'), "signatures/{$tenant->id}");
            $tenant->update(['authorized_signature_path' => $path]);

            if ($oldPath && $oldPath !== $path) {
                $assets->delete($oldPath);
            }
        }

        $tenant->update([
            'name'          => $data['name'],
            'motto'         => $data['motto'] ?? null,
            'address'       => $data['address'] ?? null,
            'phone'         => $data['phone'] ?? null,
            'email'         => $data['email'] ?? null,
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

        return back()->with('success', 'School settings updated.');
    }

    /** Authenticated tenant-scoped preview for the authorized signature. */
    public function signatureFile(Request $request, AuthenticatedIdentityAssetStorage $assets)
    {
        $tenant = $request->user()->tenant;
        $path = $assets->resolveAbsolutePath($tenant?->authorized_signature_path);
        if (!$path) {
            abort(404, 'No authorized signature on file.');
        }

        return response()->file($path, [
            'Cache-Control' => 'no-store, private',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; sandbox",
        ]);
    }
}

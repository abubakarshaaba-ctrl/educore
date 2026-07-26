<?php

namespace App\Http\Controllers;

use App\Models\SchoolAsset;
use App\Models\User;
use Illuminate\Http\Request;

class AssetController extends Controller
{
    public function index(Request $request)
    {
        $assets = SchoolAsset::with('assignedTo')
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->q, fn ($q) => $q->where('name', 'like', "%{$request->q}%"))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        $staff = User::activeStaff(auth()->user()->tenant_id)->orderBy('name')->get();

        return view('assets.index', compact('assets', 'staff'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'           => ['required', 'string', 'max:150'],
            'category'       => ['nullable', 'string', 'max:100'],
            'serial_number'  => ['nullable', 'string', 'max:100'],
            'location'       => ['nullable', 'string', 'max:150'],
            'assigned_to'    => ['nullable', 'exists:users,id'],
            'purchase_date'  => ['nullable', 'date'],
            'purchase_cost'  => ['nullable', 'numeric', 'min:0'],
            'condition'      => ['required', 'in:new,good,fair,poor,damaged'],
            'status'         => ['required', 'in:in_use,in_storage,under_repair,disposed'],
            'notes'          => ['nullable', 'string'],
        ]);

        SchoolAsset::create($data);

        return back()->with('success', 'Asset added to inventory.');
    }

    public function update(Request $request, SchoolAsset $asset)
    {
        $data = $request->validate([
            'status'      => ['required', 'in:in_use,in_storage,under_repair,disposed'],
            'condition'   => ['required', 'in:new,good,fair,poor,damaged'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'location'    => ['nullable', 'string', 'max:150'],
        ]);

        $asset->update($data);

        return back()->with('success', 'Asset updated.');
    }

    public function destroy(SchoolAsset $asset)
    {
        $asset->delete();
        return back()->with('success', 'Asset removed.');
    }
}

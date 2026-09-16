<?php

namespace App\Http\Controllers;

use App\Models\AscReturn;
use Illuminate\Http\Request;

class AscDerivedController extends Controller
{
    public function show(Request $request, string $section)
    {
        $section = strtolower($section);
        abort_unless(in_array($section, ['c', 'e'], true), 404);

        $tenant = auth()->user()->tenant;
        $year = $request->integer('year', now()->year);
        $ascReturn = AscReturn::where('tenant_id', $tenant->id)
            ->where('census_year', $year)
            ->first();

        $data = $ascReturn
            ? data_get($ascReturn->auto_data, "official.section_{$section}", [])
            : [];

        return view('asc.derived', compact('tenant', 'year', 'section', 'ascReturn', 'data'));
    }
}

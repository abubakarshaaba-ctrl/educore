<?php

namespace App\Http\Controllers;

use App\Models\AscInfrastructure;
use App\Models\AscReturn;
use App\Models\AscSectionData;
use Illuminate\Http\Request;

class AscExportController extends Controller
{
    public function pdf(Request $request)
    {
        $tenant = auth()->user()->tenant;
        $year = $request->integer('year', now()->year);

        $ascReturn = AscReturn::where('tenant_id', $tenant->id)
            ->where('census_year', $year)
            ->firstOrFail();

        $infrastructure = AscInfrastructure::where('tenant_id', $tenant->id)
            ->where('census_year', $year)
            ->first();

        $manualSections = AscSectionData::where('tenant_id', $tenant->id)
            ->where('census_year', $year)
            ->get()
            ->keyBy('section');

        $data = [
            'tenant' => $tenant,
            'year' => $year,
            'ascReturn' => $ascReturn,
            'infrastructure' => $infrastructure,
            'manualSections' => $manualSections,
            'auto' => $ascReturn->auto_data ?? [],
            'issues' => $ascReturn->completeness['issues'] ?? [],
            'blockingIssues' => $ascReturn->completeness['blocking_issues'] ?? [],
            'reconciliation' => $ascReturn->reconciliation ?? [],
        ];

        $pdf = app('dompdf.wrapper')
            ->loadView('asc.pdf', $data)
            ->setPaper('a4', 'portrait');

        $safeSchool = preg_replace('/[^A-Za-z0-9_-]+/', '-', (string) $tenant->name);
        $filename = trim($safeSchool, '-') . "-ASC-{$year}-" . ($year + 1) . '.pdf';

        return $pdf->download($filename);
    }
}

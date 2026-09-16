<?php

namespace App\Http\Controllers;

use App\Models\AscReturn;
use App\Models\AscSectionData;
use Illuminate\Http\Request;

class AscReviewController extends Controller
{
    public function index(Request $request)
    {
        $tenant = auth()->user()->tenant;
        $year = $request->integer('year', now()->year);

        $ascReturn = AscReturn::where('tenant_id', $tenant->id)
            ->where('census_year', $year)
            ->first();

        $manualRecords = AscSectionData::where('tenant_id', $tenant->id)
            ->where('census_year', $year)
            ->get()
            ->keyBy('section');

        $auto = $ascReturn?->auto_data ?? [];
        $sectionC = (array) data_get($auto, 'official.section_c', []);
        $sectionE = (array) data_get($auto, 'official.section_e', []);

        $sectionCUnsupported = (array) data_get($sectionC, 'unsupported_fields', []);
        $sectionEUnassigned = (array) data_get($sectionE, 'unclassified_or_unassigned_teachers', []);

        $sections = collect(['B', 'C', 'D', 'E', 'F', 'G', 'H'])->map(function (string $section) use (
            $manualRecords,
            $ascReturn,
            $sectionC,
            $sectionE,
            $sectionCUnsupported,
            $sectionEUnassigned,
            $year
        ) {
            if ($section === 'C') {
                $available = !empty($sectionC) && (bool) data_get($sectionC, 'available', true);
                $gapCount = count($sectionCUnsupported);

                return [
                    'section' => 'C',
                    'title' => 'Enrolment',
                    'source' => 'AUTO / DERIVED',
                    'complete' => $available && $gapCount === 0,
                    'status' => !$ascReturn ? 'SYNC REQUIRED' : (!$available ? 'UNAVAILABLE' : ($gapCount ? 'REVIEW REQUIRED' : 'DERIVED')),
                    'detail' => !$ascReturn
                        ? 'Synchronize EduCore records to generate the official enrolment derivations.'
                        : ($gapCount ? $gapCount . ' census field(s) cannot yet be derived reliably and require review.' : 'Derived from the synchronized census snapshot.'),
                    'route' => route('asc.derived.show', ['section' => 'c', 'year' => $year]),
                ];
            }

            if ($section === 'E') {
                $available = !empty($sectionE) && (bool) data_get($sectionE, 'available', true);
                $gapCount = count($sectionEUnassigned);

                return [
                    'section' => 'E',
                    'title' => 'Teachers',
                    'source' => 'AUTO / DERIVED',
                    'complete' => $available && $gapCount === 0,
                    'status' => !$ascReturn ? 'SYNC REQUIRED' : (!$available ? 'UNAVAILABLE' : ($gapCount ? 'REVIEW REQUIRED' : 'DERIVED')),
                    'detail' => !$ascReturn
                        ? 'Synchronize EduCore records to generate teacher qualification and main-teaching-level statistics.'
                        : ($gapCount ? $gapCount . ' teacher(s) require allocation review.' : 'Derived from the synchronized census snapshot.'),
                    'route' => route('asc.derived.show', ['section' => 'e', 'year' => $year]),
                ];
            }

            $record = $manualRecords->get($section);
            $definition = config("asc.sections.{$section}", []);

            return [
                'section' => $section,
                'title' => $definition['title'] ?? "Section {$section}",
                'source' => $definition['source'] ?? 'MANUAL',
                'complete' => (bool) ($record?->is_complete ?? false),
                'status' => $record?->is_complete ? 'COMPLETE' : ($record ? 'IN PROGRESS' : 'NOT STARTED'),
                'detail' => $record?->is_complete
                    ? 'All required fields for this section are complete.'
                    : 'Open this section and complete all required census fields.',
                'route' => route('asc.section.show', ['section' => strtolower($section), 'year' => $year]),
            ];
        });

        $completionCount = $sections->where('complete', true)->count();
        $sectionCompletionPercent = (int) round(($completionCount / max($sections->count(), 1)) * 100);

        return view('asc.review', [
            'tenant' => $tenant,
            'year' => $year,
            'ascReturn' => $ascReturn,
            'sections' => $sections,
            'completionCount' => $completionCount,
            'sectionCompletionPercent' => $sectionCompletionPercent,
            'issues' => $ascReturn?->completeness['issues'] ?? [],
            'blockingIssues' => $ascReturn?->completeness['blocking_issues'] ?? [],
            'reconciliation' => $ascReturn?->reconciliation ?? [],
            'auto' => $auto,
            'sectionCUnsupported' => $sectionCUnsupported,
            'sectionEUnassigned' => $sectionEUnassigned,
        ]);
    }
}

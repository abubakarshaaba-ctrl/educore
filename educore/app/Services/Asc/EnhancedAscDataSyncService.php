<?php

namespace App\Services\Asc;

use App\Models\AcademicSession;
use App\Models\Tenant;
use Carbon\CarbonImmutable;

class EnhancedAscDataSyncService extends AscDataSyncService
{
    public function __construct(private AscOfficialDerivedDataService $officialDerived)
    {
    }

    public function build(Tenant $tenant, int $censusYear, CarbonImmutable $referenceDate): array
    {
        $payload = parent::build($tenant, $censusYear, $referenceDate);

        $session = AcademicSession::where('tenant_id', $tenant->id)
            ->where('is_current', true)
            ->first();

        $official = $this->officialDerived->build($tenant, $session, $referenceDate);
        $payload['auto_data']['official'] = $official;

        $checks = $payload['completeness']['checks'] ?? [];
        $issues = $payload['completeness']['issues'] ?? [];
        $blocking = $payload['completeness']['blocking_issues'] ?? [];

        $sectionCAvailable = (bool) data_get($official, 'section_c.available', true);
        $checks[] = [
            'label' => 'Official Section C — synchronized enrolment derivations',
            'complete' => $sectionCAvailable,
        ];
        if (!$sectionCAvailable) {
            $message = data_get($official, 'section_c.reason', 'Section C synchronized data is unavailable.');
            $issues[] = $message;
            $blocking[] = $message;
        }

        $sectionEAvailable = (bool) data_get($official, 'section_e.available', true);
        $checks[] = [
            'label' => 'Official Section E — teacher qualification/main teaching level',
            'complete' => $sectionEAvailable,
        ];
        if (!$sectionEAvailable) {
            $message = data_get($official, 'section_e.reason', 'Section E synchronized data is unavailable.');
            $issues[] = $message;
            $blocking[] = $message;
        }

        $unassignedTeachers = collect(data_get($official, 'section_e.unclassified_or_unassigned_teachers', []));
        $teacherCoverageComplete = $unassignedTeachers->isEmpty();
        $checks[] = [
            'label' => 'Section E teacher allocation coverage',
            'complete' => $teacherCoverageComplete,
            'missing' => $unassignedTeachers->count(),
        ];
        if (!$teacherCoverageComplete) {
            $message = $unassignedTeachers->count() . ' teaching staff cannot yet be assigned to one main teaching level. Complete their current class/subject allocations and synchronize again.';
            $issues[] = $message;
            $blocking[] = $message;
        }

        foreach ((array) data_get($official, 'section_c.unsupported_fields', []) as $field => $reason) {
            $issues[] = 'Section C manual gap — ' . str_replace('_', ' ', $field) . ': ' . $reason;
        }

        $checks = array_values($checks);
        $issues = array_values(array_unique($issues));
        $blocking = array_values(array_unique($blocking));
        $passed = collect($checks)->where('complete', true)->count();
        $total = max(count($checks), 1);

        $payload['completeness']['checks'] = $checks;
        $payload['completeness']['issues'] = $issues;
        $payload['completeness']['blocking_issues'] = $blocking;
        $payload['completeness']['complete_checks'] = $passed;
        $payload['completeness']['total_checks'] = count($checks);
        $payload['completeness']['score'] = (int) round(($passed / $total) * 100);
        $payload['completeness']['ready_to_finalize'] = count($blocking) === 0
            && (bool) ($payload['completeness']['ready_to_finalize'] ?? false);

        return $payload;
    }
}

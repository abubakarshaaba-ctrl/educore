<?php

namespace App\Services\StudentLifecycle;

use App\Models\AcademicSession;
use App\Models\Term;
use Illuminate\Validation\ValidationException;

trait ResolvesActiveAcademicContext
{
    /**
     * @return array{session: AcademicSession, term: Term}
     */
    private function activeAcademicContext(int $tenantId): array
    {
        $sessions = AcademicSession::where('tenant_id', $tenantId)
            ->where('is_current', true)
            ->get();

        if ($sessions->count() !== 1) {
            throw ValidationException::withMessages([
                'effective_date' => 'Exactly one active academic session must exist before this lifecycle action can be completed.',
            ]);
        }

        $terms = Term::where('tenant_id', $tenantId)
            ->where('session_id', $sessions->first()->id)
            ->where('is_current', true)
            ->get();

        if ($terms->count() !== 1) {
            throw ValidationException::withMessages([
                'effective_date' => 'Exactly one active term must exist before this lifecycle action can be completed.',
            ]);
        }

        return [
            'session' => $sessions->first(),
            'term' => $terms->first(),
        ];
    }
}

<?php

namespace App\Services;

use App\Models\StaffProfileSubmission;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

class StaffIdGenerator
{
    public function generate(): string
    {
        $highest = 1000;

        foreach (User::query()->whereNotNull('staff_id')->pluck('staff_id') as $staffId) {
            $highest = max($highest, $this->numericPart($staffId));
        }

        $submissionTableReady = Schema::hasTable('staff_profile_submissions')
            && Schema::hasColumn('staff_profile_submissions', 'staff_id');

        if ($submissionTableReady) {
            foreach (StaffProfileSubmission::query()->whereNotNull('staff_id')->pluck('staff_id') as $staffId) {
                $highest = max($highest, $this->numericPart($staffId));
            }
        }

        do {
            $highest++;
            $candidate = 'STF' . str_pad((string) $highest, 4, '0', STR_PAD_LEFT);
        } while (
            User::query()->where('staff_id', $candidate)->exists()
            || ($submissionTableReady && StaffProfileSubmission::query()->where('staff_id', $candidate)->exists())
        );

        return $candidate;
    }

    private function numericPart(?string $staffId): int
    {
        if (!$staffId || !preg_match('/^STF(\d+)$/i', trim($staffId), $matches)) {
            return 0;
        }

        return (int) $matches[1];
    }
}

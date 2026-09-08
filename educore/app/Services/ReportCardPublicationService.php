<?php

namespace App\Services;

use App\Models\ReportCardPublication;
use App\Models\Term;
use App\Models\TermlySummary;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class ReportCardPublicationService
{
    public function __construct(
        private readonly LifecycleAuditLogger $auditLogger,
        private readonly GuardianNotifier $guardianNotifier,
    ) {
    }

    /**
     * @return array{publication: ReportCardPublication, guardians_notified: int}
     */
    public function publish(
        int $tenantId,
        int $classArmId,
        int $termId,
        User $actor,
        ?string $note = null,
        ?Request $request = null,
    ): array {
        $computed = TermlySummary::where('tenant_id', $tenantId)
            ->where('class_arm_id', $classArmId)
            ->where('term_id', $termId)
            ->count();
        if ($computed < 1) {
            throw ValidationException::withMessages([
                'class_arm_id' => 'Compute report cards for this class and term before publishing.',
            ]);
        }

        $publication = DB::transaction(function () use (
            $tenantId,
            $classArmId,
            $termId,
            $actor,
            $note,
            $request,
        ): ReportCardPublication {
            $publication = ReportCardPublication::where('tenant_id', $tenantId)
                ->where('class_arm_id', $classArmId)
                ->where('term_id', $termId)
                ->lockForUpdate()
                ->first();

            if ($publication?->isPublished()) {
                throw ValidationException::withMessages([
                    'class_arm_id' => 'These report cards are already published.',
                ]);
            }

            $old = $publication ? [
                'status' => $publication->status,
                'published_at' => $publication->published_at?->toIso8601String(),
                'published_by' => $publication->published_by,
                'note' => $publication->note,
            ] : [];

            if (!$publication) {
                $publication = new ReportCardPublication([
                    'tenant_id' => $tenantId,
                    'class_arm_id' => $classArmId,
                    'term_id' => $termId,
                ]);
            }

            $cleanNote = $note === null ? null : trim($note);
            $publication->forceFill([
                'tenant_id' => $tenantId,
                'class_arm_id' => $classArmId,
                'term_id' => $termId,
                'status' => 'published',
                'published_at' => now(),
                'published_by' => $actor->id,
                'archived_at' => null,
                'note' => $cleanNote === '' ? null : $cleanNote,
            ])->save();

            $this->auditLogger->record(
                $tenantId,
                $actor,
                $publication,
                'report_cards.published',
                $old,
                [
                    'status' => 'published',
                    'class_arm_id' => $classArmId,
                    'term_id' => $termId,
                    'published_by' => $actor->id,
                    'note' => $publication->note,
                ],
                $publication->note,
                $request,
            );

            return $publication->fresh(['publishedBy:id,name']);
        });

        return [
            'publication' => $publication,
            'guardians_notified' => $this->notifyPublishedResults(
                $tenantId,
                $classArmId,
                $termId,
                $actor,
            ),
        ];
    }

    public function unpublish(
        int $tenantId,
        int $classArmId,
        int $termId,
        User $actor,
        ?Request $request = null,
    ): ReportCardPublication {
        return DB::transaction(function () use (
            $tenantId,
            $classArmId,
            $termId,
            $actor,
            $request,
        ): ReportCardPublication {
            $publication = ReportCardPublication::where('tenant_id', $tenantId)
                ->where('class_arm_id', $classArmId)
                ->where('term_id', $termId)
                ->lockForUpdate()
                ->firstOrFail();

            if (!$publication->isPublished()) {
                throw ValidationException::withMessages([
                    'class_arm_id' => 'These report cards are already in draft state.',
                ]);
            }

            $old = [
                'status' => $publication->status,
                'published_at' => $publication->published_at?->toIso8601String(),
                'published_by' => $publication->published_by,
                'note' => $publication->note,
            ];

            $publication->forceFill([
                'status' => 'draft',
                'archived_at' => now(),
            ])->save();

            $this->auditLogger->record(
                $tenantId,
                $actor,
                $publication,
                'report_cards.unpublished',
                $old,
                [
                    'status' => 'draft',
                    'class_arm_id' => $classArmId,
                    'term_id' => $termId,
                    'archived_at' => $publication->archived_at?->toIso8601String(),
                ],
                null,
                $request,
            );

            return $publication->fresh(['publishedBy:id,name']);
        });
    }

    private function notifyPublishedResults(int $tenantId, int $classArmId, int $termId, User $actor): int
    {
        if (!Schema::hasTable('guardians') || !Schema::hasTable('guardian_student')) {
            return 0;
        }

        $term = Term::where('tenant_id', $tenantId)->whereKey($termId)->first();
        $schoolName = $actor->tenant?->name;
        $summaries = TermlySummary::where('tenant_id', $tenantId)
            ->where('class_arm_id', $classArmId)
            ->where('term_id', $termId)
            ->with('student.guardians')
            ->get();

        $notified = 0;
        foreach ($summaries as $summary) {
            $student = $summary->student;
            if (!$student) {
                continue;
            }

            $guardian = $student->guardians->firstWhere('pivot.is_primary_contact', true)
                ?? $student->guardians->first();
            if (!$guardian) {
                continue;
            }

            $this->guardianNotifier->send(
                $guardian,
                'Results published — '.$student->full_name,
                [
                    ($term?->name ?? 'Term').' results for '.$student->full_name.' are now available.',
                    'Sign in to the parent portal to view the full report card.',
                ],
                smsBody: 'Dear Parent, '.$student->full_name.'\'s '.($term?->name ?? 'term').' results are now available on the EduCore parent portal.',
                actionLabel: 'View Results',
                actionUrl: route('login'),
                schoolName: $schoolName,
            );
            $notified++;
        }

        return $notified;
    }
}

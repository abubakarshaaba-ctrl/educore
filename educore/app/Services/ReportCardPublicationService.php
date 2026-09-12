<?php

namespace App\Services;

use App\Models\ReportCardPublication;
use App\Models\Term;
use App\Models\TermlySummary;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
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
        $result = $this->publishMany(
            $tenantId,
            [$classArmId],
            $termId,
            $actor,
            $note,
            $request,
        );

        return [
            'publication' => $result['publications']->firstOrFail(),
            'guardians_notified' => $result['guardians_notified'],
        ];
    }

    /**
     * All selected classes transition in one database transaction. Guardian
     * delivery happens only after that transaction commits successfully.
     *
     * @param array<int, int> $classArmIds
     * @return array{publications: Collection<int, ReportCardPublication>, guardians_notified: int}
     */
    public function publishMany(
        int $tenantId,
        array $classArmIds,
        int $termId,
        User $actor,
        ?string $note = null,
        ?Request $request = null,
    ): array {
        $ids = $this->normalizeClassArmIds($classArmIds);
        $computedCounts = TermlySummary::where('tenant_id', $tenantId)
            ->where('term_id', $termId)
            ->whereIn('class_arm_id', $ids)
            ->selectRaw('class_arm_id, COUNT(*) as aggregate')
            ->groupBy('class_arm_id')
            ->pluck('aggregate', 'class_arm_id');

        $missing = $ids->first(fn (int $classArmId): bool => (int) ($computedCounts[$classArmId] ?? 0) < 1);
        if ($missing !== null) {
            throw ValidationException::withMessages([
                'class_arm_ids' => 'Compute report cards for every selected class before publishing.',
            ]);
        }

        $publications = DB::transaction(function () use (
            $tenantId,
            $ids,
            $termId,
            $actor,
            $note,
            $request,
        ): Collection {
            $existing = ReportCardPublication::where('tenant_id', $tenantId)
                ->where('term_id', $termId)
                ->whereIn('class_arm_id', $ids)
                ->lockForUpdate()
                ->get()
                ->keyBy('class_arm_id');

            if ($existing->contains(fn (ReportCardPublication $item): bool => $item->isPublished())) {
                throw ValidationException::withMessages([
                    'class_arm_ids' => 'One or more selected classes are already published.',
                ]);
            }

            $cleanNote = $note === null ? null : trim($note);
            $changed = collect();
            foreach ($ids as $classArmId) {
                $publication = $existing->get($classArmId);
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
                $changed->push($publication);
            }

            return $changed;
        })->map(fn (ReportCardPublication $item) => $item->fresh(['publishedBy:id,name']));

        $notified = 0;
        foreach ($ids as $classArmId) {
            $notified += $this->notifyPublishedResults($tenantId, $classArmId, $termId, $actor);
        }

        return [
            'publications' => $publications,
            'guardians_notified' => $notified,
        ];
    }

    public function unpublish(
        int $tenantId,
        int $classArmId,
        int $termId,
        User $actor,
        ?Request $request = null,
    ): ReportCardPublication {
        return $this->unpublishMany(
            $tenantId,
            [$classArmId],
            $termId,
            $actor,
            $request,
        )->firstOrFail();
    }

    /**
     * @param array<int, int> $classArmIds
     * @return Collection<int, ReportCardPublication>
     */
    public function unpublishMany(
        int $tenantId,
        array $classArmIds,
        int $termId,
        User $actor,
        ?Request $request = null,
    ): Collection {
        $ids = $this->normalizeClassArmIds($classArmIds);

        return DB::transaction(function () use (
            $tenantId,
            $ids,
            $termId,
            $actor,
            $request,
        ): Collection {
            $publications = ReportCardPublication::where('tenant_id', $tenantId)
                ->where('term_id', $termId)
                ->whereIn('class_arm_id', $ids)
                ->lockForUpdate()
                ->get()
                ->keyBy('class_arm_id');

            if (
                $publications->count() !== $ids->count()
                || $ids->contains(fn (int $classArmId): bool => !$publications->get($classArmId)?->isPublished())
            ) {
                throw ValidationException::withMessages([
                    'class_arm_ids' => 'Every selected class must be published before it can be returned to draft.',
                ]);
            }

            $changed = collect();
            foreach ($ids as $classArmId) {
                /** @var ReportCardPublication $publication */
                $publication = $publications->get($classArmId);
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
                $changed->push($publication);
            }

            return $changed;
        })->map(fn (ReportCardPublication $item) => $item->fresh(['publishedBy:id,name']));
    }

    /** @param array<int, int> $classArmIds */
    private function normalizeClassArmIds(array $classArmIds): Collection
    {
        $ids = collect($classArmIds)
            ->map(fn ($value): int => (int) $value)
            ->filter(fn (int $value): bool => $value > 0)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            throw ValidationException::withMessages([
                'class_arm_ids' => 'Select at least one class.',
            ]);
        }

        return $ids;
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

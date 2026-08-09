<?php

namespace App\Enums;

enum DataMigrationStatus: string
{
    case Uploaded = 'uploaded';
    case Inspecting = 'inspecting';
    case Extracted = 'extracted';
    case Classified = 'classified';
    case Mapping = 'mapping';
    case NeedsReview = 'needs_review';
    case Mapped = 'mapped';
    case Normalising = 'normalising';
    case Validating = 'validating';
    case ReadyForDryRun = 'ready_for_dry_run';
    case DryRunning = 'dry_running';
    case DryRunFailed = 'dry_run_failed';
    case AwaitingApproval = 'awaiting_approval';
    case Approved = 'approved';
    case Queued = 'queued';
    case Importing = 'importing';
    case Verifying = 'verifying';
    case Reconciling = 'reconciling';
    case Completed = 'completed';
    case Partial = 'partial';
    case Failed = 'failed';
    case RollbackRequested = 'rollback_requested';
    case RollingBack = 'rolling_back';
    case RolledBack = 'rolled_back';
    case Cancelled = 'cancelled';

    /** @return list<self> */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Uploaded => [self::Inspecting, self::Cancelled],
            self::Inspecting => [self::Extracted, self::NeedsReview, self::Failed, self::Cancelled],
            self::Extracted => [self::Classified, self::Failed, self::Cancelled],
            self::Classified => [self::Mapping, self::NeedsReview, self::Cancelled],
            self::Mapping => [self::NeedsReview, self::Mapped, self::Cancelled],
            self::NeedsReview => [self::Mapping, self::Mapped, self::Cancelled],
            self::Mapped => [self::Mapping, self::Normalising, self::Cancelled],
            self::Normalising => [self::Validating, self::NeedsReview, self::Failed],
            self::Validating => [self::ReadyForDryRun, self::NeedsReview, self::Failed],
            self::ReadyForDryRun => [self::DryRunning, self::Cancelled],
            self::DryRunning => [self::AwaitingApproval, self::DryRunFailed, self::Failed],
            self::DryRunFailed => [self::Mapping, self::Validating, self::Cancelled],
            self::AwaitingApproval => [self::Approved, self::NeedsReview, self::Cancelled],
            self::Approved => [self::Queued, self::Cancelled],
            self::Queued => [self::Importing, self::Failed, self::Cancelled],
            self::Importing => [self::Verifying, self::Partial, self::Failed],
            self::Verifying => [self::Reconciling, self::Partial, self::Failed],
            self::Reconciling => [self::Completed, self::Partial, self::Failed],
            self::Completed, self::Partial => [self::RollbackRequested],
            self::Failed => [self::Inspecting, self::RollbackRequested],
            self::RollbackRequested => [self::RollingBack, self::Cancelled],
            self::RollingBack => [self::RolledBack, self::Failed],
            self::RolledBack, self::Cancelled => [],
        };
    }

    public function canTransitionTo(self $next): bool
    {
        return in_array($next, $this->allowedTransitions(), true);
    }
}

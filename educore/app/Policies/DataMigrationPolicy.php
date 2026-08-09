<?php

namespace App\Policies;

use App\Models\DataMigration;
use App\Models\User;

class DataMigrationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isMigrationAdmin() || ($user->isAdmin() && (bool) $user->tenant_id);
    }

    public function view(User $user, DataMigration $migration): bool
    {
        return $user->isMigrationAdmin() || ($user->isAdmin() && (int) $user->tenant_id === (int) $migration->tenant_id);
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin() || ($user->isAdmin() && (bool) $user->tenant_id);
    }

    public function uploadSource(User $user, DataMigration $migration): bool
    {
        return $this->view($user, $migration) && in_array($migration->status->value, ['uploaded', 'needs_review'], true);
    }

    public function approve(User $user, DataMigration $migration): bool
    {
        return $user->isSuperAdmin() || ($user->isAdmin() && (int) $user->tenant_id === (int) $migration->tenant_id);
    }

    public function execute(User $user, DataMigration $migration): bool
    {
        return $user->isMigrationAdmin();
    }

    public function rollback(User $user, DataMigration $migration): bool
    {
        return $user->isMigrationAdmin();
    }
}

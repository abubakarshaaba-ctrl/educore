<?php

namespace App\Providers;

use App\Models\AssessmentType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AssessmentConfigurationServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        AssessmentType::addGlobalScope('class_level_configuration', function (Builder $builder): void {
            if (app()->runningInConsole() || ! Schema::hasTable('assessment_type_class_level')) {
                return;
            }

            $request = request();
            $classArmId = (int) $request->input('class_arm_id', 0);
            $termId = (int) $request->input('term_id', 0);

            if ($classArmId <= 0 || $termId <= 0) {
                return;
            }

            $routeName = optional($request->route())->getName();
            $isScoreRoute = in_array($routeName, [
                'scores.entry',
                'scores.save',
                'scores.broadsheet',
                'scores.broadsheet.pdf',
            ], true) || $request->is('api/v1/scores/*');

            if (! $isScoreRoute) {
                return;
            }

            $classLevelId = (int) DB::table('class_arms')
                ->where('id', $classArmId)
                ->value('class_level_id');

            if ($classLevelId <= 0) {
                return;
            }

            $tenantId = (int) optional(auth()->user())->tenant_id;

            $hasScopedConfiguration = DB::table('assessment_types as scoped_at')
                ->join('assessment_type_class_level as scoped_acl', 'scoped_acl.assessment_type_id', '=', 'scoped_at.id')
                ->where('scoped_at.term_id', $termId)
                ->where('scoped_acl.class_level_id', $classLevelId)
                ->when($tenantId > 0, fn ($q) => $q->where('scoped_at.tenant_id', $tenantId))
                ->exists();

            if ($hasScopedConfiguration) {
                $builder->whereExists(function ($query) use ($classLevelId): void {
                    $query->selectRaw('1')
                        ->from('assessment_type_class_level as acl_scope')
                        ->whereColumn('acl_scope.assessment_type_id', 'assessment_types.id')
                        ->where('acl_scope.class_level_id', $classLevelId);
                });

                return;
            }

            // Backward-compatible fallback: existing term-wide assessment types
            // have no pivot rows. They remain active until an administrator creates
            // an explicit configuration for this class level and term.
            $builder->whereNotExists(function ($query): void {
                $query->selectRaw('1')
                    ->from('assessment_type_class_level as acl_default')
                    ->whereColumn('acl_default.assessment_type_id', 'assessment_types.id');
            });
        });
    }
}

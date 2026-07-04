<?php

namespace Database\Seeders;

use App\Models\Scopes\TenantContext;
use App\Models\SkillDefinition;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class SkillDefinitionsSeeder extends Seeder
{
    public function run(): void
    {
        $tenants = Tenant::all();

        foreach ($tenants as $tenant) {
            TenantContext::set($tenant->id);

            // ---------------------------------------------------------------
            // PSYCHOMOTOR SKILLS
            // Core physical/practical competencies rated on Nigerian report cards
            // ---------------------------------------------------------------
            $psychomotor = [
                ['name' => 'Handwriting',          'order_index' => 1],
                ['name' => 'Drawing & Painting',   'order_index' => 2],
                ['name' => 'Sports & Games',       'order_index' => 3],
                ['name' => 'Verbal Fluency',       'order_index' => 4],
                ['name' => 'Handling of Tools',    'order_index' => 5],
            ];

            foreach ($psychomotor as $skill) {
                SkillDefinition::firstOrCreate(
                    ['tenant_id' => $tenant->id, 'name' => $skill['name'], 'category' => 'psychomotor'],
                    ['order_index' => $skill['order_index'], 'is_active' => true]
                );
            }

            // ---------------------------------------------------------------
            // AFFECTIVE SKILLS
            // Behavioural/character traits rated on Nigerian report cards
            // ---------------------------------------------------------------
            $affective = [
                ['name' => 'Punctuality',          'order_index' => 1],
                ['name' => 'Attentiveness',        'order_index' => 2],
                ['name' => 'Neatness',             'order_index' => 3],
                ['name' => 'Honesty',              'order_index' => 4],
                ['name' => 'Relationship with Others', 'order_index' => 5],
            ];

            foreach ($affective as $skill) {
                SkillDefinition::firstOrCreate(
                    ['tenant_id' => $tenant->id, 'name' => $skill['name'], 'category' => 'affective'],
                    ['order_index' => $skill['order_index'], 'is_active' => true]
                );
            }

            TenantContext::clear();

            $this->command->info("✅ Skills seeded for: {$tenant->name}");
        }

        $this->command->line('');
        $this->command->line('Rating Scale: 5=Excellent  4=Very Good  3=Good  2=Fair  1=Poor');
    }
}

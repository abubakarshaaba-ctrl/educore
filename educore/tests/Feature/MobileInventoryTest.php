<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\SchoolAsset;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MobileInventoryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
            $this->markTestSkipped('Mobile inventory tests require sqlite :memory:.');
        }

        foreach (['assets', 'staff_permissions', 'api_tokens', 'users', 'tenants'] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('tenants', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug');
            $table->string('status')->default('active');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->string('name');
            $table->string('staff_id')->nullable();
            $table->string('role')->nullable();
            $table->boolean('is_super_admin')->default(false);
            $table->boolean('is_active')->default(true);
            $table->string('employment_status')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('api_tokens', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->string('device')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        Schema::create('staff_permissions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('user_id');
            $table->string('module', 60);
            $table->string('type')->default('grant');
            $table->unsignedBigInteger('granted_by');
            $table->timestamps();
            $table->unique(['tenant_id', 'user_id', 'module']);
        });

        Schema::create('assets', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('name');
            $table->string('category')->nullable();
            $table->string('serial_number')->nullable();
            $table->string('location')->nullable();
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->date('purchase_date')->nullable();
            $table->decimal('purchase_cost', 12, 2)->nullable();
            $table->string('condition')->default('good');
            $table->string('status')->default('in_storage');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function test_inventory_index_is_tenant_scoped_searchable_and_paginated(): void
    {
        [$tenant, $admin] = $this->school('Inventory School');
        [$foreignTenant] = $this->school('Foreign Inventory School');

        foreach (range(1, 12) as $index) {
            SchoolAsset::create([
                'tenant_id' => $tenant->id,
                'name' => $index === 12 ? 'Target Microscope' : 'Asset '.$index,
                'category' => 'Laboratory',
                'serial_number' => 'INV-'.$index,
                'condition' => 'good',
                'status' => $index % 2 === 0 ? 'in_use' : 'in_storage',
            ]);
        }
        SchoolAsset::create([
            'tenant_id' => $foreignTenant->id,
            'name' => 'Foreign Target Microscope',
            'condition' => 'good',
            'status' => 'in_use',
        ]);

        $token = ApiToken::issue($admin, 'inventory-index');
        $this->withToken($token)
            ->getJson('/api/v1/inventory?per_page=10&page=1')
            ->assertOk()
            ->assertJsonPath('capabilities.manage', true)
            ->assertJsonPath('meta.total', 12)
            ->assertJsonPath('meta.has_more', true)
            ->assertJsonCount(10, 'assets');

        $this->withToken($token)
            ->getJson('/api/v1/inventory?search=Target&status=in_use')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('assets.0.name', 'Target Microscope');
    }

    public function test_admin_can_create_update_and_delete_tenant_asset(): void
    {
        [$tenant, $admin] = $this->school('Inventory Mutation School');
        $staff = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Laboratory Officer',
            'staff_id' => 'STF-001',
            'role' => 'admin_officer',
            'is_active' => true,
            'employment_status' => User::STAFF_STATUS_ACTIVE,
        ]);
        $token = ApiToken::issue($admin, 'inventory-mutation');

        $response = $this->withToken($token)
            ->postJson('/api/v1/inventory', [
                'name' => 'Binocular Microscope',
                'category' => 'Laboratory',
                'serial_number' => 'MIC-001',
                'location' => 'Biology Laboratory',
                'assigned_to' => $staff->id,
                'purchase_cost' => 250000,
                'condition' => 'new',
                'status' => 'in_use',
            ])
            ->assertCreated()
            ->assertJsonPath('asset.assigned_to_name', 'Laboratory Officer');

        $assetId = $response->json('asset.id');
        $this->withToken($token)
            ->patchJson('/api/v1/inventory/'.$assetId, [
                'name' => 'Binocular Microscope',
                'location' => 'Science Store',
                'assigned_to' => null,
                'condition' => 'good',
                'status' => 'in_storage',
            ])
            ->assertOk()
            ->assertJsonPath('asset.location', 'Science Store')
            ->assertJsonPath('asset.status', 'in_storage');

        $this->withToken($token)
            ->deleteJson('/api/v1/inventory/'.$assetId)
            ->assertOk();
        $this->assertDatabaseMissing('assets', ['id' => $assetId]);
    }

    public function test_inventory_rejects_foreign_staff_assignment_and_foreign_asset_mutation(): void
    {
        [$tenant, $admin] = $this->school('Boundary Inventory School');
        [$foreignTenant] = $this->school('Foreign Boundary Inventory School');
        $foreignStaff = User::create([
            'tenant_id' => $foreignTenant->id,
            'name' => 'Foreign Staff',
            'staff_id' => 'FOREIGN-STF',
            'role' => 'admin_officer',
            'is_active' => true,
            'employment_status' => User::STAFF_STATUS_ACTIVE,
        ]);
        $foreignAsset = SchoolAsset::create([
            'tenant_id' => $foreignTenant->id,
            'name' => 'Foreign Asset',
            'condition' => 'good',
            'status' => 'in_use',
        ]);
        $token = ApiToken::issue($admin, 'inventory-boundary');

        $this->withToken($token)
            ->postJson('/api/v1/inventory', [
                'name' => 'Local Asset',
                'assigned_to' => $foreignStaff->id,
                'condition' => 'good',
                'status' => 'in_use',
            ])
            ->assertUnprocessable();

        $this->withToken($token)
            ->patchJson('/api/v1/inventory/'.$foreignAsset->id, [
                'condition' => 'damaged',
                'status' => 'under_repair',
            ])
            ->assertNotFound();

        $this->withToken($token)
            ->deleteJson('/api/v1/inventory/'.$foreignAsset->id)
            ->assertNotFound();
        $this->assertDatabaseHas('assets', ['id' => $foreignAsset->id, 'tenant_id' => $foreignTenant->id]);
        $this->assertSame($tenant->id, $admin->tenant_id);
    }

    public function test_custom_inventory_deny_blocks_native_inventory_access(): void
    {
        [$tenant, $admin] = $this->school('Denied Inventory School');
        DB::table('staff_permissions')->insert([
            'tenant_id' => $tenant->id,
            'user_id' => $admin->id,
            'module' => 'inventory',
            'type' => 'deny',
            'granted_by' => $admin->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->withToken(ApiToken::issue($admin, 'inventory-denied'))
            ->getJson('/api/v1/inventory')
            ->assertForbidden();
    }

    private function school(string $name): array
    {
        $tenant = Tenant::create([
            'name' => $name,
            'slug' => str($name)->slug().'-'.uniqid(),
            'status' => 'active',
        ]);
        $admin = User::create([
            'tenant_id' => $tenant->id,
            'name' => $name.' Admin',
            'role' => 'admin',
            'is_active' => true,
            'employment_status' => User::STAFF_STATUS_ACTIVE,
        ]);

        return [$tenant, $admin];
    }
}

<?php

namespace Tests\Feature;

use App\Services\StaffIdGenerator;
use App\Services\StudentIdGenerator;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class IncrementalIdentityGeneratorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
            $this->markTestSkipped('Incremental identity tests require the isolated sqlite :memory: test database.');
        }

        Schema::dropIfExists('students');
        Schema::dropIfExists('users');
        Schema::dropIfExists('tenants');

        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('role')->nullable();
            $table->string('staff_id')->nullable()->unique();
            $table->string('student_id')->nullable()->unique();
            $table->boolean('is_super_admin')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('admission_number')->nullable();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('status')->default('active');
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['tenant_id', 'admission_number']);
        });
    }

    public function test_staff_ids_increment_from_the_highest_existing_staff_id(): void
    {
        DB::table('tenants')->insert(['id' => 1, 'name' => 'School A', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('users')->insert([
            [
                'tenant_id' => 1,
                'name' => 'Staff One',
                'email' => 'one@example.test',
                'password' => 'x',
                'role' => 'admin',
                'staff_id' => 'STF1001',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tenant_id' => 1,
                'name' => 'Staff Two',
                'email' => 'two@example.test',
                'password' => 'x',
                'role' => 'teacher',
                'staff_id' => 'STF1002',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $this->assertSame('STF1003', app(StaffIdGenerator::class)->generate());
    }

    public function test_student_ids_use_one_incremental_sequence_without_year_resets(): void
    {
        DB::table('tenants')->insert([
            ['id' => 1, 'name' => 'School A', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'School B', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('students')->insert([
            [
                'tenant_id' => 1,
                'admission_number' => 'STU1001',
                'first_name' => 'One',
                'last_name' => 'Student',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tenant_id' => 2,
                'admission_number' => 'STU1002',
                'first_name' => 'Two',
                'last_name' => 'Student',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tenant_id' => 1,
                'admission_number' => 'STU-2026-1005',
                'first_name' => 'Legacy',
                'last_name' => 'Student',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $this->assertSame('STU1006', app(StudentIdGenerator::class)->generate());
    }
}

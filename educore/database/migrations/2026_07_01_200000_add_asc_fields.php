<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add qualification + special needs to existing tables
        Schema::table('users', function (Blueprint $table) {
            $table->string('qualification')->nullable()->after('date_of_birth');
        });

        Schema::table('students', function (Blueprint $table) {
            $table->boolean('has_special_needs')->default(false)->after('status');
            $table->string('special_needs_type')->nullable()->after('has_special_needs');
        });

        // Physical infrastructure — one row per school per academic session
        Schema::create('asc_infrastructure', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('session_id')->nullable();
            $table->integer('census_year')->nullable();

            // Classrooms
            $table->integer('classrooms_permanent')->default(0);
            $table->integer('classrooms_temporary')->default(0);
            $table->integer('classrooms_good_condition')->default(0);
            $table->integer('classrooms_bad_condition')->default(0);

            // Toilets
            $table->integer('toilets_male_pupils')->default(0);
            $table->integer('toilets_female_pupils')->default(0);
            $table->integer('toilets_male_staff')->default(0);
            $table->integer('toilets_female_staff')->default(0);

            // Water & Power
            $table->string('water_source')->nullable(); // pipe/borehole/well/river/none
            $table->string('electricity_source')->nullable(); // NEPA/generator/solar/none

            // Facilities (boolean)
            $table->boolean('has_library')->default(false);
            $table->boolean('has_computer_lab')->default(false);
            $table->integer('computer_count')->default(0);
            $table->boolean('has_science_lab')->default(false);
            $table->boolean('has_sports_facility')->default(false);
            $table->boolean('has_first_aid')->default(false);
            $table->string('fence_type')->nullable(); // full/partial/none

            // School profile (ASC-specific)
            $table->string('school_ownership')->nullable(); // federal/state/lga/private/mission/community
            $table->string('school_type')->nullable(); // day/boarding/mixed
            $table->string('school_lga')->nullable();
            $table->string('school_state')->nullable();
            $table->string('school_senatorial_district')->nullable();
            $table->string('head_teacher_name')->nullable();
            $table->string('head_teacher_qualification')->nullable();
            $table->string('head_teacher_gender')->nullable();

            $table->timestamps();
            $table->unique(['tenant_id', 'census_year']);
        });
    }

    public function down(): void
    {
        Schema::table('users', fn(Blueprint $t) => $t->dropColumn('qualification'));
        Schema::table('students', fn(Blueprint $t) => $t->dropColumn(['has_special_needs', 'special_needs_type']));
        Schema::dropIfExists('asc_infrastructure');
    }
};

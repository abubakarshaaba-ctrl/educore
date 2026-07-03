<?php

namespace App\Models;

class AscInfrastructure extends BaseTenantModel
{
    protected $table = 'asc_infrastructure';

    protected $fillable = [
        'tenant_id', 'session_id', 'census_year',
        'classrooms_permanent', 'classrooms_temporary',
        'classrooms_good_condition', 'classrooms_bad_condition',
        'toilets_male_pupils', 'toilets_female_pupils',
        'toilets_male_staff', 'toilets_female_staff',
        'water_source', 'electricity_source',
        'has_library', 'has_computer_lab', 'computer_count',
        'has_science_lab', 'has_sports_facility', 'has_first_aid',
        'fence_type',
        'school_ownership', 'school_type',
        'school_lga', 'school_state', 'school_senatorial_district',
        'head_teacher_name', 'head_teacher_qualification', 'head_teacher_gender',
    ];

    protected $casts = [
        'has_library'        => 'boolean',
        'has_computer_lab'   => 'boolean',
        'has_science_lab'    => 'boolean',
        'has_sports_facility'=> 'boolean',
        'has_first_aid'      => 'boolean',
    ];

    public function session() { return $this->belongsTo(AcademicSession::class); }
}

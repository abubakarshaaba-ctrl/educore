<?php

return [
    'manual_sections' => ['B', 'D', 'F', 'G', 'H'],

    'sections' => [
        'B' => [
            'title' => 'School Characteristics',
            'source' => 'MANUAL / PROFILE',
            'description' => 'Official Private School Census Section B.',
            'fields' => [
                'year_of_establishment' => ['label' => 'Year of establishment', 'type' => 'number', 'required' => true],
                'pre_primary' => ['label' => 'B.1 Pre-primary', 'type' => 'text', 'required' => true],
                'primary' => ['label' => 'B.2 Primary', 'type' => 'text', 'required' => true],
                'junior_secondary' => ['label' => 'B.3 Junior Secondary School', 'type' => 'text', 'required' => true],
                'senior_secondary' => ['label' => 'B.4 Senior Secondary School', 'type' => 'text', 'required' => true],
                'location' => ['label' => 'B.5 Location', 'type' => 'text', 'required' => true],
                'ownership_status' => ['label' => 'B.6 Ownership Status', 'type' => 'text', 'required' => true],
                'recognition_status' => ['label' => 'B.7 Recognition status', 'type' => 'text', 'required' => true],
                'levels_offered' => ['label' => 'B.8 Levels of education offered', 'type' => 'text', 'required' => true],
                'shift_system' => ['label' => 'B.9 Does the school operate shift system?', 'type' => 'yes_no', 'required' => true],
                'shared_facilities' => ['label' => 'B.10 Does the school share facilities/teachers/premises with another school?', 'type' => 'yes_no', 'required' => true],
                'school_type' => ['label' => 'B.11 Type of School', 'type' => 'text', 'required' => true],
                'association_member' => ['label' => 'B.12 Member of Private Schools Association?', 'type' => 'yes_no', 'required' => true],
                'association_name' => ['label' => 'B.12 Association name (write None if not a member)', 'type' => 'text', 'required' => true],
                'catchment_distance' => ['label' => 'B.13 Average distance from catchment communities', 'type' => 'text', 'required' => true],
                'boarding_male' => ['label' => 'B.14 Boarding pupils/students — Male', 'type' => 'number', 'required' => true],
                'boarding_female' => ['label' => 'B.14 Boarding pupils/students — Female', 'type' => 'number', 'required' => true],
                'sdp_last_year' => ['label' => 'B.15 School Development Plan prepared last school year?', 'type' => 'yes_no', 'required' => true],
                'sbmc_active' => ['label' => 'B.16 SBMC exists and met at least once last year?', 'type' => 'yes_no', 'required' => true],
                'pta_active' => ['label' => 'B.17 PTA/PF/Mothers Association exists and met at least once last year?', 'type' => 'yes_no', 'required' => true],
                'last_inspection_date' => ['label' => 'B.18 Date of last inspection visit', 'type' => 'date', 'required' => true],
                'inspection_visits_count' => ['label' => 'B.18 Number of inspection visits in last academic year', 'type' => 'number', 'required' => true],
                'inspection_authority' => ['label' => 'B.19 Authority of last inspection', 'type' => 'text', 'required' => true],
                'security_guard' => ['label' => 'B.20 Does the school have a security guard?', 'type' => 'yes_no', 'required' => true],
            ],
        ],

        'D' => [
            'title' => 'Classrooms and Facilities',
            'source' => 'MANUAL / PROFILE',
            'description' => 'Official Section D. Infrastructure values are stored per census year.',
            'fields' => [
                'safe_drinking_water' => ['label' => 'D.1 Primary source of safe drinking water', 'type' => 'text', 'required' => true],
                'safe_drinking_water_other' => ['label' => 'D.1 If other, specify', 'type' => 'text', 'required' => false],
                'toilet_units' => [
                    'label' => 'D.2 Number of usable toilet units by type/use', 'type' => 'matrix', 'required' => true,
                    'rows' => ['pit' => 'Pit', 'bucket' => 'Bucket system', 'water_flush' => 'Water flush', 'other' => 'Others'],
                    'columns' => [
                        'pupils_male' => 'Pupils male only', 'pupils_female' => 'Pupils female only', 'pupils_mixed' => 'Pupils mixed',
                        'teachers_male' => 'Teachers male only', 'teachers_female' => 'Teachers female only', 'teachers_mixed' => 'Teachers mixed',
                        'shared_male' => 'Pupils/teachers male', 'shared_female' => 'Pupils/teachers female', 'shared_mixed' => 'Pupils/teachers mixed',
                    ],
                ],
                'facility_condition' => [
                    'label' => 'D.3 Facilities available — usable/not usable', 'type' => 'matrix', 'required' => true,
                    'rows' => [
                        'toilets' => 'Toilets', 'computers' => 'Computers', 'water_sources' => 'Water source(s)', 'laboratories' => 'Laboratories',
                        'classrooms' => 'Classrooms', 'library' => 'Library', 'playgrounds' => 'Play ground(s)', 'wash_hand' => 'Wash hand facility', 'others' => 'Others',
                    ],
                    'columns' => ['usable' => 'Usable', 'not_usable' => 'Not usable'],
                ],
                'shared_facility_types' => [
                    'label' => 'D.4 Shared facilities', 'type' => 'checkboxes', 'required' => false,
                    'options' => [
                        'toilets' => 'Toilets', 'laboratories' => 'Laboratories', 'playgrounds' => 'Play ground(s)', 'computers' => 'Computers',
                        'classrooms' => 'Classrooms', 'wash_hand' => 'Wash hand facility', 'water_sources' => 'Water source(s)', 'library' => 'Library', 'others' => 'Others',
                    ],
                ],
                'power_source' => ['label' => 'D.5 Source of power', 'type' => 'text', 'required' => true],
                'primary_health_facility' => ['label' => 'D.6 Primary health facility', 'type' => 'text', 'required' => true],
                'building_ownership' => ['label' => 'D.7 Ownership status of school building', 'type' => 'text', 'required' => true],
                'building_type' => ['label' => 'D.8 Type of school building', 'type' => 'text', 'required' => true],
                'seating' => [
                    'label' => 'D.9 Seating available by grade', 'type' => 'matrix', 'required' => true,
                    'rows' => [
                        'pre_primary' => 'Pre-primary', 'pry1' => 'PRY1', 'pry2' => 'PRY2', 'pry3' => 'PRY3', 'pry4' => 'PRY4', 'pry5' => 'PRY5', 'pry6' => 'PRY6',
                        'jss1' => 'JSS1', 'jss2' => 'JSS2', 'jss3' => 'JSS3', 'ss1' => 'SS1', 'ss2' => 'SS2', 'ss3' => 'SS3',
                    ],
                    'columns' => ['one' => '1 seater', 'two' => '2 seater', 'three' => '3 seater', 'four' => '4 seater', 'five' => '5 seater', 'six' => '6 seater'],
                ],
            ],
        ],

        'F' => [
            'title' => 'Pupil/Teacher Book',
            'source' => 'MANUAL',
            'description' => 'Official Section F textbook counts by level.',
            'fields' => [
                'pupil_textbooks' => [
                    'label' => 'F.1 Core subject textbooks available to pupils/students', 'type' => 'matrix', 'required' => true,
                    'rows' => ['number' => 'Number'],
                    'columns' => ['pry1'=>'PRY1','pry2'=>'PRY2','pry3'=>'PRY3','pry4'=>'PRY4','pry5'=>'PRY5','pry6'=>'PRY6','jss1'=>'JSS1','jss2'=>'JSS2','jss3'=>'JSS3'],
                ],
                'teacher_textbooks' => [
                    'label' => 'F.2 Core subject teachers’ textbooks available', 'type' => 'matrix', 'required' => true,
                    'rows' => ['number' => 'Number'],
                    'columns' => ['pry1'=>'PRY1','pry2'=>'PRY2','pry3'=>'PRY3','pry4'=>'PRY4','pry5'=>'PRY5','pry6'=>'PRY6','jss1'=>'JSS1','jss2'=>'JSS2','jss3'=>'JSS3'],
                ],
            ],
        ],

        'G' => [
            'title' => 'Family Life HIV/AIDS Education (FLHE)',
            'source' => 'MANUAL',
            'description' => 'Official Section G FLHE indicators.',
            'fields' => [
                'physical_safety_rules' => ['label' => 'G.1 Rules cover physical safety in school', 'type' => 'yes_no', 'required' => true],
                'anti_stigma_rules' => ['label' => 'G.1 Rules cover stigma/discrimination/sexual harassment and abuse', 'type' => 'yes_no', 'required' => true],
                'grievance_procedure' => ['label' => 'G.1 Grievance/disciplinary procedure for breaches', 'type' => 'yes_no', 'required' => true],
                'rules_communicated' => ['label' => 'G.2 Rules/guidelines communicated to stakeholders', 'type' => 'yes_no', 'required' => true],
                'flhe_received' => ['label' => 'G.3 Students received life skills-based FLHE in previous academic year', 'type' => 'yes_no', 'required' => true],
                'generic_life_skills' => ['label' => 'G.4 Generic life skills taught', 'type' => 'yes_no', 'required' => true],
                'reproductive_health' => ['label' => 'G.4 Reproductive health/FLHE taught', 'type' => 'yes_no', 'required' => true],
                'hiv_prevention' => ['label' => 'G.4 HIV transmission and prevention taught', 'type' => 'yes_no', 'required' => true],
                'students_male' => ['label' => 'G.5 Male students who participated in FLHE', 'type' => 'number', 'required' => true],
                'students_female' => ['label' => 'G.5 Female students who participated in FLHE', 'type' => 'number', 'required' => true],
                'parent_orientation_count' => ['label' => 'G.6 Number of parent/guardian FLHE orientation programmes', 'type' => 'number', 'required' => true],
                'orientation_forums' => ['label' => 'G.6 Forums used for orientation', 'type' => 'checkboxes', 'required' => false, 'options' => ['pta'=>'PTA','open_day'=>'Open Day','special_sessions'=>'Special session(s)']],
                'last_orientation_date' => ['label' => 'G.7 Date of last FLHE orientation', 'type' => 'date', 'required' => false],
                'trained_teachers_male' => ['label' => 'G.8 Male teachers formally trained on FLHE', 'type' => 'number', 'required' => true],
                'trained_teachers_female' => ['label' => 'G.8 Female teachers formally trained on FLHE', 'type' => 'number', 'required' => true],
                'trained_and_teaching_male' => ['label' => 'G.9 Trained male teachers who taught FLHE topics', 'type' => 'number', 'required' => true],
                'trained_and_teaching_female' => ['label' => 'G.9 Trained female teachers who taught FLHE topics', 'type' => 'number', 'required' => true],
            ],
        ],

        'H' => [
            'title' => 'Undertaking',
            'source' => 'ATTESTATION',
            'description' => 'Official Section H attestations. Typed names/contacts are stored; physical signatures remain on the submitted form unless digital-signature support is added later.',
            'fields' => [
                'principal_name' => ['label' => 'Head Teacher/Principal — Name', 'type' => 'text', 'required' => true],
                'principal_phone' => ['label' => 'Head Teacher/Principal — Telephone', 'type' => 'text', 'required' => true],
                'principal_date' => ['label' => 'Head Teacher/Principal — Date', 'type' => 'date', 'required' => true],
                'sbmc_name' => ['label' => 'SBMC Chairperson/Member — Name', 'type' => 'text', 'required' => true],
                'sbmc_position' => ['label' => 'SBMC Chairperson/Member — Position', 'type' => 'text', 'required' => true],
                'sbmc_phone' => ['label' => 'SBMC Chairperson/Member — Telephone', 'type' => 'text', 'required' => true],
                'sbmc_date' => ['label' => 'SBMC Chairperson/Member — Date', 'type' => 'date', 'required' => true],
                'supervisor_name' => ['label' => 'Supervisor — Name', 'type' => 'text', 'required' => true],
                'supervisor_position' => ['label' => 'Supervisor — Position', 'type' => 'text', 'required' => true],
                'supervisor_phone' => ['label' => 'Supervisor — Telephone', 'type' => 'text', 'required' => true],
                'supervisor_date' => ['label' => 'Supervisor — Date', 'type' => 'date', 'required' => true],
                'field_coordinator_check' => ['label' => 'Office use — Field coordinator check', 'type' => 'yes_no', 'required' => false],
                'pre_data_entry_check' => ['label' => 'Office use — Pre-data entry check', 'type' => 'yes_no', 'required' => false],
                'data_entry_completed' => ['label' => 'Office use — Data entry completed', 'type' => 'yes_no', 'required' => false],
                'verification_check' => ['label' => 'Office use — Verification check', 'type' => 'yes_no', 'required' => false],
                'office_check_date' => ['label' => 'Office use — Date', 'type' => 'date', 'required' => false],
            ],
        ],
    ],
];

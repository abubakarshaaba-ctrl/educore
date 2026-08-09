<?php

use App\Models\AcademicSession;
use App\Models\AssessmentType;
use App\Models\ClassArm;
use App\Models\ClassLevel;
use App\Models\GradingSystem;
use App\Models\Subject;
use App\Models\Term;

return [
    'entity_order' => ['academic_session', 'term', 'class_level', 'class_arm', 'subject', 'assessment_type', 'grading_system'],
    'entities' => [
        'academic_session' => ['model' => AcademicSession::class, 'identity' => ['name'], 'relationships' => []],
        'term' => ['model' => Term::class, 'identity' => ['name'], 'relationships' => ['session' => 'academic_session'], 'relationship_columns' => ['session' => 'session_id']],
        'class_level' => ['model' => ClassLevel::class, 'identity' => ['name'], 'relationships' => []],
        'class_arm' => ['model' => ClassArm::class, 'identity' => ['class_level', 'name'], 'relationships' => ['class_level' => 'class_level'], 'relationship_columns' => ['class_level' => 'class_level_id']],
        'subject' => ['model' => Subject::class, 'identity' => ['code', 'name'], 'relationships' => []],
        'assessment_type' => ['model' => AssessmentType::class, 'identity' => ['term', 'name'], 'relationships' => ['term' => 'term'], 'relationship_columns' => ['term' => 'term_id']],
        'grading_system' => ['model' => GradingSystem::class, 'identity' => ['class_level', 'grade_letter', 'min_score', 'max_score'], 'relationships' => ['class_level' => 'class_level'], 'relationship_columns' => ['class_level' => 'class_level_id']],
    ],
];

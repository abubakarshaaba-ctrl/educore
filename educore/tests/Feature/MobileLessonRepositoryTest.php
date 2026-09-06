<?php

namespace Tests\Feature;

use App\Contracts\LessonAiProvider;
use App\Models\AcademicSession;
use App\Models\ApiToken;
use App\Models\ClassArm;
use App\Models\ClassArmSubject;
use App\Models\ClassLevel;
use App\Models\CurriculumFragment;
use App\Models\CurriculumSource;
use App\Models\LessonPlan;
use App\Models\Subject;
use App\Models\Tenant;
use App\Models\Term;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class MobileLessonRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_mobile_repository_exposes_only_active_platform_content_to_readers(): void
    {
        Storage::fake('local');
        $school = $this->school('mobile-reader');
        $teacher = $this->user($school['tenant'], 'subject_teacher');
        $accountant = $this->user($school['tenant'], 'accountant');
        $owner = User::factory()->create(['is_super_admin' => true, 'is_active' => true]);
        $visible = $this->source($owner, null, 'Cell Structure', true, 'SS 2', 'First Term', 'Biology');
        $this->source($owner, null, 'Inactive Note', false, 'SS 2', 'First Term', 'Biology');
        $this->source($owner, $school['tenant']->id, 'School-owned Note', true, 'SS 2', 'First Term', 'Biology');

        $token = ApiToken::issue($teacher, 'repository-test');
        $this->withToken($token)->getJson('/api/v1/academic-repository/classes')
            ->assertOk()->assertJsonPath('contract_version', 1)
            ->assertJsonPath('metrics.resources', 1)
            ->assertJsonPath('classes.0.name', 'SS 2');
        $this->withToken($token)->getJson('/api/v1/academic-repository/resources?class=SS%202&term=First%20Term&subject=Biology')
            ->assertOk()->assertJsonCount(1, 'resources')->assertJsonPath('resources.0.id', $visible->id);
        $this->withToken($token)->getJson("/api/v1/academic-repository/resources/{$visible->id}")
            ->assertOk()->assertJsonPath('resource.fragments.0.topic', 'Cell Structure');
        $this->withToken(ApiToken::issue($accountant, 'repository-denied'))
            ->getJson('/api/v1/academic-repository/resources')->assertForbidden();
    }

    public function test_mobile_lesson_plans_are_assignment_scoped_idempotent_and_teacher_published(): void
    {
        $school = $this->school('mobile-planner');
        $teacher = $this->user($school['tenant'], 'subject_teacher');
        $otherTeacher = $this->user($school['tenant'], 'subject_teacher');
        ClassArmSubject::create([
            'tenant_id' => $school['tenant']->id,
            'class_arm_id' => $school['class']->id,
            'subject_id' => $school['subject']->id,
            'teacher_id' => $teacher->id,
            'session_id' => $school['session']->id,
        ]);
        $token = ApiToken::issue($teacher, 'planner-test');
        $requestId = (string) Str::uuid();
        $payload = [
            'request_id' => $requestId,
            'subject_id' => $school['subject']->id,
            'class_level_id' => $school['level']->id,
            'class_arm_id' => $school['class']->id,
            'term_id' => $school['term']->id,
            'curriculum_type' => 'nerdc',
            'curriculum_level_id' => $school['level']->id,
            'delivery_type' => 'regular',
            'topic' => 'Cell Structure and Function',
            'subtopic' => 'Cell membrane, nucleus',
            'week_number' => 2,
            'duration_minutes' => 40,
            'status' => 'draft',
        ];

        $this->withToken($token)->getJson('/api/v1/lesson-plans/options')
            ->assertOk()->assertJsonCount(1, 'assignments')
            ->assertJsonPath('assignments.0.subjects.0.id', $school['subject']->id);
        $created = $this->withToken($token)->postJson('/api/v1/lesson-plans', $payload)
            ->assertCreated()->assertJsonPath('lesson_plan.status', 'draft')->json('lesson_plan');
        $this->withToken($token)->postJson('/api/v1/lesson-plans', $payload)
            ->assertCreated()->assertJsonPath('lesson_plan.id', $created['id']);
        $this->assertSame(1, LessonPlan::where('teacher_id', $teacher->id)->count());

        $this->withToken(ApiToken::issue($otherTeacher, 'other-planner'))
            ->getJson("/api/v1/lesson-plans/{$created['id']}")->assertNotFound();
        $this->withToken($token)->patchJson("/api/v1/lesson-plans/{$created['id']}", [
            'request_id' => (string) Str::uuid(),
            'version' => 'stale-version',
            'topic' => 'Changed topic',
        ])->assertStatus(409);
        $published = $this->withToken($token)->postJson("/api/v1/lesson-plans/{$created['id']}/publish", [
            'request_id' => (string) Str::uuid(),
            'version' => $created['version'],
        ])->assertOk()->assertJsonPath('lesson_plan.status', 'published');
        $this->assertNotNull($published->json('lesson_plan.published_at'));
        $this->assertNull(LessonPlan::findOrFail($created['id'])->approved_at);
        $this->withToken($token)->get("/api/v1/lesson-plans/{$created['id']}/pdf")
            ->assertOk()->assertHeader('content-type', 'application/pdf');
    }

    public function test_teacher_cannot_create_plan_for_an_unassigned_or_cross_tenant_subject(): void
    {
        $school = $this->school('planner-school-a');
        $other = $this->school('planner-school-b');
        $teacher = $this->user($school['tenant'], 'subject_teacher');
        $token = ApiToken::issue($teacher, 'planner-scope');
        $base = [
            'request_id' => (string) Str::uuid(),
            'subject_id' => $school['subject']->id,
            'class_level_id' => $school['level']->id,
            'class_arm_id' => $school['class']->id,
            'term_id' => $school['term']->id,
            'curriculum_type' => 'nerdc',
            'delivery_type' => 'regular',
            'topic' => 'Genetics',
            'duration_minutes' => 40,
            'status' => 'draft',
        ];

        $this->withToken($token)->postJson('/api/v1/lesson-plans', $base)->assertForbidden();
        $this->withToken($token)->postJson('/api/v1/lesson-plans', array_merge($base, [
            'request_id' => (string) Str::uuid(),
            'subject_id' => $other['subject']->id,
        ]))->assertUnprocessable()->assertJsonValidationErrors('subject_id');
    }

    public function test_mobile_generation_reuses_the_existing_grounded_planner_and_keeps_target_class(): void
    {
        $this->app->bind(LessonAiProvider::class, fn () => new class implements LessonAiProvider
        {
            public function name(): string
            {
                return 'groq-test';
            }

            public function model(): string
            {
                return 'test-model';
            }

            public function generateStructured(string $systemPrompt, string $userPrompt, array $schema, int $maxTokens): array
            {
                return ['data' => [
                    'class' => 'SS 2', 'subject' => 'Biology', 'week' => '2', 'lesson' => '1',
                    'topic' => 'Cell Structure', 'sub_topics' => ['Cell membrane'], 'time' => '10:20 am',
                    'duration' => '40 minutes', 'average_age' => '16 years', 'sex' => 'Mixed',
                    'previous_background_knowledge' => 'Students previously learned that living organisms consist of organized structural units called cells.',
                    'behavioural_objectives' => [
                        'Define the cell membrane accurately.',
                        'Identify three functions of the cell membrane.',
                        'Explain selective permeability using a familiar example.',
                    ],
                    'instructional_resources' => ['Cell chart', 'Teacher-prepared membrane diagram'],
                    'introduction' => 'Teacher revises the meaning of a cell through questions, receives responses, corrects misconceptions and links them to the membrane.',
                    'presentation' => [[
                        'step' => 1, 'objective_numbers' => [1, 2, 3], 'title' => 'Cell membrane structure and functions',
                        'teacher_activities' => [
                            'Teacher guides students to define the cell membrane as the thin boundary surrounding the cell contents.',
                            'Teacher aids students to identify protection, transport and communication as important membrane functions.',
                            'Teacher helps students explain selective permeability with a classroom sieve comparison and guided questions.',
                        ],
                    ]],
                    'evaluation' => ['Define the cell membrane.', 'List three functions of the cell membrane.', 'Explain selective permeability.'],
                    'assignment' => 'Draw and label a cell membrane, then state two of its functions.',
                    'references' => [],
                ], 'input_tokens' => 100, 'output_tokens' => 200];
            }
        });

        $school = $this->school('planner-generation');
        $teacher = $this->user($school['tenant'], 'subject_teacher');
        ClassArmSubject::create([
            'tenant_id' => $school['tenant']->id, 'class_arm_id' => $school['class']->id,
            'subject_id' => $school['subject']->id, 'teacher_id' => $teacher->id, 'session_id' => $school['session']->id,
        ]);
        $plan = LessonPlan::create([
            'tenant_id' => $school['tenant']->id, 'teacher_id' => $teacher->id, 'subject_id' => $school['subject']->id,
            'class_level_id' => $school['level']->id, 'class_arm_id' => $school['class']->id, 'term_id' => $school['term']->id,
            'curriculum_type' => 'nerdc', 'delivery_type' => 'regular', 'topic' => 'Cell Structure', 'subtopic' => 'Cell membrane',
            'week_number' => 2, 'duration_minutes' => 40, 'status' => 'draft',
        ]);

        $this->withToken(ApiToken::issue($teacher, 'generate-plan'))->postJson("/api/v1/lesson-plans/{$plan->id}/generate", [
            'request_id' => (string) Str::uuid(),
            'version' => $plan->updated_at->toJSON(),
        ])->assertOk()
            ->assertJsonPath('lesson_plan.class_level.id', $school['level']->id)
            ->assertJsonPath('lesson_plan.ai_generated', true)
            ->assertJsonPath('lesson_plan.sections.4.key', 'presentation')
            ->assertJsonMissing(['key' => 'entry_behaviour']);
    }

    private function school(string $slug): array
    {
        $tenant = Tenant::create(['name' => Str::headline($slug), 'slug' => $slug, 'status' => Tenant::STATUS_ACTIVE]);
        $session = AcademicSession::create(['tenant_id' => $tenant->id, 'name' => '2026/2027', 'is_current' => true]);
        $term = Term::create([
            'tenant_id' => $tenant->id,
            'session_id' => $session->id,
            'name' => 'First Term',
            'start_date' => '2026-09-01',
            'end_date' => '2026-12-18',
            'is_current' => true,
        ]);
        $level = ClassLevel::create([
            'tenant_id' => $tenant->id,
            'name' => 'SS 2',
            'section' => 'senior_secondary',
            'order_index' => 11,
        ]);
        $class = ClassArm::create(['tenant_id' => $tenant->id, 'class_level_id' => $level->id, 'name' => 'Gold']);
        $subject = Subject::create(['tenant_id' => $tenant->id, 'name' => 'Biology', 'code' => 'BIO-'.Str::upper(Str::random(4))]);

        return compact('tenant', 'session', 'term', 'level', 'class', 'subject');
    }

    private function user(Tenant $tenant, string $role): User
    {
        return User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => $role,
            'is_active' => true,
            'employment_status' => User::STAFF_STATUS_ACTIVE,
        ]);
    }

    private function source(User $owner, ?int $tenantId, string $title, bool $active, string $class, string $term, string $subject): CurriculumSource
    {
        $path = 'academic-repository/'.Str::slug($title).'.docx';
        Storage::disk('local')->put($path, 'prepared lesson note');
        $source = CurriculumSource::create([
            'tenant_id' => $tenantId,
            'authority' => 'OTHER',
            'source_type' => 'lesson_note',
            'title' => $title,
            'version' => '2026',
            'original_filename' => $title.'.docx',
            'source_file_path' => $path,
            'mime_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'file_size' => 20,
            'created_by' => $owner->id,
            'extraction_status' => 'extracted',
            'index_status' => 'indexed',
            'is_active' => $active,
            'metadata' => ['class_label' => $class, 'term_label' => $term, 'subject_label' => $subject],
        ]);
        CurriculumFragment::create([
            'curriculum_source_id' => $source->id,
            'topic' => $title,
            'content' => 'Rich, approved instructional content for '.$title.'.',
            'sequence' => 1,
        ]);

        return $source;
    }
}

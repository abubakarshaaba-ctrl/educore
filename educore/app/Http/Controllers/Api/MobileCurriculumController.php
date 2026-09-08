<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AcademicTrack;
use App\Models\ClassLevel;
use App\Models\ClassLevelSubject;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MobileCurriculumController extends Controller
{
    private const SECTIONS = ['primary', 'junior', 'senior', 'general'];
    private const STATUSES = ['compulsory', 'elective', 'optional', 'not_offered'];

    public function index(Request $request)
    {
        $user = $this->guard($request);
        $tenantId = (int) $user->tenant_id;
        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'level_id' => ['nullable', Rule::exists('class_levels', 'id')->where('tenant_id', $tenantId)],
            'track_id' => ['nullable', 'integer'],
            'status' => ['nullable', Rule::in(array_merge(['all'], self::STATUSES))],
        ]);
        $search = trim((string) ($data['q'] ?? ''));
        $levelId = isset($data['level_id']) ? (int) $data['level_id'] : null;
        $trackId = isset($data['track_id']) ? (int) $data['track_id'] : null;
        $status = (string) ($data['status'] ?? 'all');

        if ($trackId !== null) {
            $this->trackForTenant($tenantId, $trackId);
        }

        $tracks = AcademicTrack::query()
            ->forTenant($tenantId)
            ->withCount([
                'classArms' => fn ($query) => $query->where('tenant_id', $tenantId),
                'subjectRules' => fn ($query) => $query->where('tenant_id', $tenantId),
                'studentSubjectSelections' => fn ($query) => $query->where('tenant_id', $tenantId),
            ])
            ->get()
            ->map(fn (AcademicTrack $track): array => $this->trackPayload($track, $tenantId));

        $rules = ClassLevelSubject::query()
            ->where('tenant_id', $tenantId)
            ->with(['classLevel:id,name', 'subject:id,name,code', 'academicTrack:id,tenant_id,name,section'])
            ->when($levelId, fn ($query) => $query->where('class_level_id', $levelId))
            ->when($trackId !== null, fn ($query) => $query->where('academic_track_id', $trackId))
            ->when($status !== 'all', fn ($query) => $query->where('subject_status', $status))
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($nested) use ($search): void {
                    $nested->whereHas('subject', fn ($subject) => $subject
                        ->where(function ($subjectSearch) use ($search): void {
                            $subjectSearch->where('name', 'like', "%{$search}%")
                                ->orWhere('code', 'like', "%{$search}%");
                        }))
                        ->orWhereHas('classLevel', fn ($level) => $level->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('academicTrack', fn ($track) => $track->where('name', 'like', "%{$search}%"))
                        ->orWhere('elective_group', 'like', "%{$search}%");
                });
            })
            ->orderBy('class_level_id')
            ->orderBy('subject_status')
            ->orderBy('subject_id')
            ->get()
            ->map(fn (ClassLevelSubject $rule): array => $this->rulePayload($rule));

        $levels = ClassLevel::where('tenant_id', $tenantId)
            ->orderBy('order_index')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (ClassLevel $level): array => ['id' => $level->id, 'name' => $level->name]);
        $subjects = Subject::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code'])
            ->map(fn (Subject $subject): array => [
                'id' => $subject->id,
                'name' => $subject->name,
                'code' => $subject->code,
            ]);

        return response()->json([
            'contract_version' => 1,
            'capabilities' => ['manage' => $user->canManage('curriculum')],
            'metrics' => [
                'tracks' => $tracks->count(),
                'school_tracks' => $tracks->where('system', false)->count(),
                'rules' => ClassLevelSubject::where('tenant_id', $tenantId)->count(),
                'active_rules' => ClassLevelSubject::where('tenant_id', $tenantId)->where('is_active', true)->count(),
            ],
            'tracks' => $tracks->values(),
            'rules' => $rules->values(),
            'class_levels' => $levels->values(),
            'subjects' => $subjects->values(),
            'status_options' => collect(self::STATUSES)->map(fn (string $key): array => [
                'key' => $key,
                'label' => str($key)->replace('_', ' ')->title()->toString(),
            ])->prepend(['key' => 'all', 'label' => 'All'])->values(),
            'section_options' => collect(self::SECTIONS)->map(fn (string $key): array => [
                'key' => $key,
                'label' => ucfirst($key),
            ])->values(),
            'selected' => [
                'search' => $search,
                'level_id' => $levelId,
                'track_id' => $trackId,
                'status' => $status,
            ],
        ]);
    }

    public function storeTrack(Request $request)
    {
        $user = $this->guard($request, manage: true);
        $tenantId = (int) $user->tenant_id;
        $data = $request->validate([
            'name' => [
                'required', 'string', 'max:80',
                $this->trackNameRule($tenantId),
            ],
            'section' => ['required', Rule::in(self::SECTIONS)],
            'is_active' => ['required', 'boolean'],
        ]);
        $name = trim($data['name']);
        $track = AcademicTrack::create([
            'tenant_id' => $tenantId,
            'name' => $name,
            'slug' => Str::slug($name).'-'.$tenantId,
            'section' => $data['section'],
            'is_active' => (bool) $data['is_active'],
            'sort_order' => ((int) AcademicTrack::where('tenant_id', $tenantId)->max('sort_order')) + 1,
        ]);
        $this->loadTrackCounts($track, $tenantId);

        return response()->json([
            'message' => 'Academic track created.',
            'track' => $this->trackPayload($track, $tenantId),
        ], 201);
    }

    public function updateTrack(Request $request, int $track)
    {
        $user = $this->guard($request, manage: true);
        $tenantId = (int) $user->tenant_id;
        $record = AcademicTrack::where('tenant_id', $tenantId)->findOrFail($track);
        $data = $request->validate([
            'name' => [
                'required', 'string', 'max:80',
                $this->trackNameRule($tenantId, $record->id),
            ],
            'section' => ['required', Rule::in(self::SECTIONS)],
            'is_active' => ['required', 'boolean'],
        ]);
        $name = trim($data['name']);
        $record->update([
            'name' => $name,
            'slug' => Str::slug($name).'-'.$tenantId,
            'section' => $data['section'],
            'is_active' => (bool) $data['is_active'],
        ]);
        $record->refresh();
        $this->loadTrackCounts($record, $tenantId);

        return response()->json([
            'message' => 'Academic track updated.',
            'track' => $this->trackPayload($record, $tenantId),
        ]);
    }

    public function destroyTrack(Request $request, int $track)
    {
        $user = $this->guard($request, manage: true);
        $tenantId = (int) $user->tenant_id;
        $record = AcademicTrack::where('tenant_id', $tenantId)->findOrFail($track);
        $this->loadTrackCounts($record, $tenantId);
        $references = (int) $record->class_arms_count + (int) $record->subject_rules_count + (int) $record->student_subject_selections_count;
        if ($references > 0) {
            throw ValidationException::withMessages([
                'track' => 'This academic track is in use and cannot be deleted. Deactivate it instead.',
            ]);
        }
        $record->delete();

        return response()->json(['message' => 'Academic track deleted.']);
    }

    public function storeRule(Request $request)
    {
        $user = $this->guard($request, manage: true);
        $data = $this->validatedRule($request, $user);
        $exists = ClassLevelSubject::where('tenant_id', $user->tenant_id)
            ->where('class_level_id', $data['class_level_id'])
            ->where('subject_id', $data['subject_id'])
            ->where(function ($query) use ($data): void {
                if ($data['academic_track_id'] === null) {
                    $query->whereNull('academic_track_id');
                } else {
                    $query->where('academic_track_id', $data['academic_track_id']);
                }
            })->exists();
        if ($exists) {
            throw ValidationException::withMessages([
                'subject_id' => 'A curriculum rule already exists for this class level, track and subject.',
            ]);
        }

        $rule = ClassLevelSubject::create($data + [
            'tenant_id' => $user->tenant_id,
            'is_active' => true,
        ]);
        $rule->load(['classLevel:id,name', 'subject:id,name,code', 'academicTrack:id,tenant_id,name,section']);

        return response()->json([
            'message' => 'Curriculum rule created.',
            'rule' => $this->rulePayload($rule),
        ], 201);
    }

    public function updateRule(Request $request, int $rule)
    {
        $user = $this->guard($request, manage: true);
        $record = ClassLevelSubject::where('tenant_id', $user->tenant_id)->findOrFail($rule);
        $data = $request->validate([
            'subject_status' => ['required', Rule::in(self::STATUSES)],
            'elective_group' => ['nullable', 'string', 'max:60'],
            'min_required' => ['nullable', 'integer', 'min:0', 'max:255'],
            'max_allowed' => ['nullable', 'integer', 'min:0', 'max:255'],
            'is_active' => ['required', 'boolean'],
        ]);
        $this->validateLimits($data);
        $record->update([
            ...$data,
            'elective_group' => trim((string) ($data['elective_group'] ?? '')) ?: null,
        ]);
        $record->refresh()->load(['classLevel:id,name', 'subject:id,name,code', 'academicTrack:id,tenant_id,name,section']);

        return response()->json([
            'message' => 'Curriculum rule updated.',
            'rule' => $this->rulePayload($record),
        ]);
    }

    public function destroyRule(Request $request, int $rule)
    {
        $user = $this->guard($request, manage: true);
        $record = ClassLevelSubject::where('tenant_id', $user->tenant_id)->findOrFail($rule);
        $record->delete();

        return response()->json(['message' => 'Curriculum rule removed.']);
    }

    private function validatedRule(Request $request, User $user): array
    {
        $tenantId = (int) $user->tenant_id;
        $data = $request->validate([
            'class_level_id' => ['required', Rule::exists('class_levels', 'id')->where('tenant_id', $tenantId)],
            'academic_track_id' => ['nullable', 'integer'],
            'subject_id' => ['required', Rule::exists('subjects', 'id')->where(fn ($query) => $query
                ->where('tenant_id', $tenantId)->where('is_active', true)->whereNull('deleted_at'))],
            'subject_status' => ['required', Rule::in(self::STATUSES)],
            'elective_group' => ['nullable', 'string', 'max:60'],
            'min_required' => ['nullable', 'integer', 'min:0', 'max:255'],
            'max_allowed' => ['nullable', 'integer', 'min:0', 'max:255'],
        ]);
        if (!empty($data['academic_track_id'])) {
            $this->trackForTenant($tenantId, (int) $data['academic_track_id']);
            $data['academic_track_id'] = (int) $data['academic_track_id'];
        } else {
            $data['academic_track_id'] = null;
        }
        $data['elective_group'] = trim((string) ($data['elective_group'] ?? '')) ?: null;
        $this->validateLimits($data);

        return $data;
    }

    private function validateLimits(array $data): void
    {
        if (isset($data['min_required'], $data['max_allowed']) && $data['min_required'] > $data['max_allowed']) {
            throw ValidationException::withMessages([
                'max_allowed' => 'Maximum allowed cannot be lower than minimum required.',
            ]);
        }
    }

    private function trackForTenant(int $tenantId, int $trackId): AcademicTrack
    {
        return AcademicTrack::query()
            ->whereKey($trackId)
            ->where(function ($query) use ($tenantId): void {
                $query->whereNull('tenant_id')->orWhere('tenant_id', $tenantId);
            })
            ->firstOrFail();
    }

    private function trackNameRule(int $tenantId, ?int $ignoreId = null)
    {
        $rule = Rule::unique('academic_tracks', 'name')->where(fn ($query) => $query
            ->where(function ($scope) use ($tenantId): void {
                $scope->whereNull('tenant_id')->orWhere('tenant_id', $tenantId);
            }));

        return $ignoreId === null ? $rule : $rule->ignore($ignoreId);
    }

    private function loadTrackCounts(AcademicTrack $track, int $tenantId): void
    {
        $track->loadCount([
            'classArms' => fn ($query) => $query->where('tenant_id', $tenantId),
            'subjectRules' => fn ($query) => $query->where('tenant_id', $tenantId),
            'studentSubjectSelections' => fn ($query) => $query->where('tenant_id', $tenantId),
        ]);
    }

    private function trackPayload(AcademicTrack $track, int $tenantId): array
    {
        return [
            'id' => $track->id,
            'name' => $track->name,
            'section' => $track->section,
            'active' => (bool) $track->is_active,
            'system' => $track->tenant_id === null,
            'manageable' => (int) $track->tenant_id === $tenantId,
            'references' => [
                'class_arms' => (int) ($track->class_arms_count ?? 0),
                'rules' => (int) ($track->subject_rules_count ?? 0),
                'student_selections' => (int) ($track->student_subject_selections_count ?? 0),
            ],
        ];
    }

    private function rulePayload(ClassLevelSubject $rule): array
    {
        return [
            'id' => $rule->id,
            'class_level_id' => $rule->class_level_id,
            'class_level' => $rule->classLevel?->name,
            'track_id' => $rule->academic_track_id,
            'track' => $rule->academicTrack?->name ?? 'All tracks',
            'subject_id' => $rule->subject_id,
            'subject' => $rule->subject?->name,
            'subject_code' => $rule->subject?->code,
            'status' => $rule->subject_status,
            'elective_group' => $rule->elective_group,
            'min_required' => $rule->min_required,
            'max_allowed' => $rule->max_allowed,
            'active' => (bool) $rule->is_active,
        ];
    }

    private function guard(Request $request, bool $manage = false): User
    {
        /** @var User|null $user */
        $user = $request->user();
        abort_unless($user, 401);
        abort_if($user->isStudent() || $user->isParent() || $user->isSuperAdmin(), 403, 'School curriculum access required.');
        abort_unless($user->tenant_id, 403, 'School curriculum access required.');
        abort_unless(
            $manage ? $user->canManage('curriculum') : $user->canAccessModule('curriculum'),
            403,
            $manage ? 'Curriculum management permission required.' : 'Curriculum access required.'
        );

        return $user;
    }
}

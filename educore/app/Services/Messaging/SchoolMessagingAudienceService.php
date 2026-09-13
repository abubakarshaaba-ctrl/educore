<?php

namespace App\Services\Messaging;

use App\Models\Guardian;
use App\Models\MessageThread;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class SchoolMessagingAudienceService
{
    private const ACADEMIC_ROLES = [
        'admin', 'principal', 'head', 'head_teacher', 'head_of_school', 'head_of_schools',
        'vice_principal', 'academic_administrator', 'director_of_studies', 'hod',
        'head_of_department', 'teacher', 'subject_teacher', 'class_teacher', 'form_teacher',
        'asst_form_teacher', 'form_subject_teacher',
    ];

    public function canOversee(User $user): bool
    {
        return $user->isAdmin()
            || in_array($user->roleKey(), ['principal', 'head', 'head_teacher', 'vice_principal', 'academic_administrator'], true)
            || $user->canManage('messages');
    }

    public function isAcademicStaff(User $user): bool
    {
        if (! $user->isTenantStaff()) {
            return false;
        }

        return collect($user->getRoleNames())
            ->map(fn ($role): string => strtolower((string) $role))
            ->push(strtolower((string) $user->roleKey()))
            ->unique()
            ->contains(fn (string $role): bool => in_array($role, self::ACADEMIC_ROLES, true));
    }

    public function audiencesFor(User $user): array
    {
        if ($user->isParent()) {
            return ['all_parents'];
        }

        if ($user->isTenantStaff()) {
            $audiences = ['all_staff'];
            if ($this->isAcademicStaff($user)) {
                $audiences[] = 'academic_staff';
            }
            return $audiences;
        }

        return [];
    }

    public function scopeVisible(Builder $query, User $user): Builder
    {
        if ($this->canOversee($user)) {
            return $query;
        }

        $userId = (int) $user->id;
        $audiences = $this->audiencesFor($user);
        $legacyStudentIds = $this->legacyStudentIds($user);

        return $query->where(function (Builder $visible) use ($userId, $audiences, $legacyStudentIds): void {
            $visible->where('initiated_by', $userId)
                ->orWhere('recipient_user_id', $userId)
                ->orWhereHas('replies', fn (Builder $replies) => $replies->where('sender_id', $userId));

            if ($audiences !== []) {
                $visible->orWhereIn('audience', $audiences);
            }

            if ($legacyStudentIds !== []) {
                $visible->orWhereIn('student_id', $legacyStudentIds);
            }
        });
    }

    public function authorize(MessageThread $thread, User $user): void
    {
        abort_unless((int) $thread->tenant_id === (int) $user->tenant_id, 404);

        if ($this->canOversee($user)) {
            return;
        }

        $isParticipant = (int) $thread->initiated_by === (int) $user->id
            || (int) $thread->recipient_user_id === (int) $user->id
            || $thread->replies()->where('sender_id', $user->id)->exists();
        $isAudienceRecipient = in_array($thread->audience, $this->audiencesFor($user), true);
        $isLegacyStudentConversation = $thread->student_id
            && in_array((int) $thread->student_id, $this->legacyStudentIds($user), true);

        abort_unless(
            $isParticipant || $isAudienceRecipient || $isLegacyStudentConversation,
            403,
            'You are not a participant in this conversation.'
        );
    }

    private function legacyStudentIds(User $user): array
    {
        if ($user->isStudent()) {
            return $user->student?->id ? [(int) $user->student->id] : [];
        }

        if ($user->isParent()) {
            $guardian = Guardian::query()->where('user_id', $user->id)->first();
            return $guardian?->students()->pluck('students.id')->map(fn ($id): int => (int) $id)->all() ?? [];
        }

        return [];
    }
}

<?php

namespace App\Services\Admissions;

use App\Models\Admission;
use App\Models\Guardian;
use App\Models\Student;
use App\Models\User;
use App\Services\GuardianNotifier;
use App\Services\PlanLimitService;
use App\Services\TenantUrlGenerator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Session-free admission decision workflow shared by native/API callers.
 *
 * The service deliberately contains no redirect/session behavior. It preserves
 * the existing business rules: paid student capacity is checked before first
 * admission, admitted applicants are enrolled exactly once, a primary guardian
 * is created/linked, and guardian notifications are best-effort.
 */
class AdmissionStatusService
{
    public function transition(User $user, Admission $admission, array $data): Admission
    {
        abort_unless((int) $admission->tenant_id === (int) $user->tenant_id, 404);

        $status = (string) $data['status'];
        $statusChanged = $admission->status !== $status;
        $shouldEnroll = $status === 'admitted' && ! $admission->enrolled_as_student_id;

        if ($shouldEnroll) {
            if ($error = PlanLimitService::checkStudentLimit($user->tenant)) {
                throw ValidationException::withMessages(['limit' => [$error]]);
            }
        }

        DB::transaction(function () use ($user, $admission, $data, $status, $shouldEnroll): void {
            $admission->update([
                'status' => $status,
                'notes' => $data['notes'] ?? $admission->notes,
                'reviewed_by' => $user->id,
                'decision_date' => now()->toDateString(),
            ]);

            if ($shouldEnroll) {
                $this->enrollStudent($user, $admission, $data['class_arm_id'] ?? null);
            }
        });

        $fresh = $admission->fresh(['enrolledStudent']);

        if ($statusChanged) {
            $this->notifyGuardianOfStatus($user, $fresh);
        }

        return $fresh;
    }

    private function enrollStudent(User $user, Admission $admission, ?int $classArmId): void
    {
        $tenant = $user->tenant;

        // DB-agnostic sequence generation. Pulling only STU-prefixed values keeps
        // CI/SQLite and production/MySQL behavior aligned without REGEXP syntax.
        $nextNumber = Student::withoutTenantScope()
            ->where('tenant_id', $tenant->id)
            ->where('admission_number', 'like', 'STU%')
            ->pluck('admission_number')
            ->map(function ($value): int {
                return preg_match('/^STU(\d+)$/', (string) $value, $matches)
                    ? (int) $matches[1]
                    : 0;
            })
            ->max() + 1;
        $admissionNumber = 'STU'.str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);

        $student = Student::create([
            'tenant_id' => $tenant->id,
            'first_name' => $admission->first_name,
            'last_name' => $admission->last_name,
            'middle_name' => $admission->other_names,
            'admission_number' => $admissionNumber,
            'date_of_birth' => $admission->date_of_birth,
            'gender' => $admission->gender,
            'religion' => $admission->religion,
            'state_of_origin' => $admission->state_of_origin,
            'lga_of_origin' => $admission->lga_of_origin,
            'current_class_arm_id' => $classArmId,
            'status' => Student::STATUS_ACTIVE,
            'admission_date' => now(),
        ]);

        $nameParts = explode(' ', trim((string) $admission->guardian_name), 2);
        $guardian = Guardian::create([
            'tenant_id' => $tenant->id,
            'first_name' => $nameParts[0] ?: 'Guardian',
            'last_name' => $nameParts[1] ?? '',
            'phone' => $admission->guardian_phone,
            'email' => $admission->guardian_email,
            'relationship' => $admission->guardian_relationship,
            'occupation' => $admission->guardian_occupation,
            'address' => $admission->guardian_address,
        ]);
        $student->guardians()->attach($guardian->id, [
            'is_primary_contact' => true,
            'tenant_id' => $tenant->id,
        ]);
        $admission->update(['enrolled_as_student_id' => $student->id]);

        try {
            app(GuardianNotifier::class)->send(
                $guardian,
                'Enrollment confirmed — '.$student->full_name,
                [
                    $student->full_name.' has been successfully enrolled at '.$tenant->name.'.',
                    'Admission number: '.$student->admission_number,
                ],
                smsBody: "Dear {$guardian->full_name}, {$student->full_name} has been successfully enrolled at {$tenant->name}. Admission No: {$student->admission_number}.",
                actionLabel: 'Sign In',
                actionUrl: route('login'),
                schoolName: $tenant->name,
                replyToEmail: $tenant->email,
            );
        } catch (\Throwable $error) {
            Log::error('Enrollment guardian notification failed: '.$error->getMessage());
        }
    }

    private function notifyGuardianOfStatus(User $user, Admission $admission): void
    {
        $tenant = $user->tenant;
        $name = trim($admission->first_name.' '.$admission->last_name);
        $line = match ($admission->status) {
            'shortlisted' => "Good news! {$name}'s application has been shortlisted. We will contact you with next steps.",
            'admitted' => "Congratulations! {$name} has been offered admission. You will receive a formal offer letter shortly.",
            'rejected' => "Thank you for applying. After careful review, we will not be proceeding with {$name}'s application at this time.",
            'withdrawn' => "{$name}'s application has been marked as withdrawn as requested.",
            default => "{$name}'s application status has been updated to: ".ucfirst((string) $admission->status).'.',
        };

        $guardian = new Guardian([
            'first_name' => $admission->guardian_name,
            'email' => $admission->guardian_email,
            'phone' => $admission->guardian_phone,
        ]);
        $guardian->tenant_id = $tenant->id;

        try {
            app(GuardianNotifier::class)->send(
                $guardian,
                'Application update — '.$name.' — '.$tenant->name,
                [$line, 'Application number: '.$admission->application_number],
                smsBody: "Dear {$admission->guardian_name}, {$line}",
                actionLabel: 'Track Application',
                actionUrl: app(TenantUrlGenerator::class)->admissionStatus($tenant),
                schoolName: $tenant->name,
                replyToEmail: $tenant->email,
            );
        } catch (\Throwable $error) {
            Log::error('Admission status-change guardian notification failed: '.$error->getMessage());
        }
    }
}

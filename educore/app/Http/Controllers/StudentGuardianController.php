<?php

namespace App\Http\Controllers;

use App\Models\Guardian;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StudentGuardianController extends Controller
{
    public function update(Request $request, Student $student)
    {
        $this->authorize('students.edit');

        $tenantId = (int) auth()->user()->tenant_id;
        abort_unless((int) $student->tenant_id === $tenantId, 404);

        $validated = $request->validate([
            'guardian_ids' => ['nullable', 'array'],
            'guardian_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('guardians', 'id')->where(fn ($query) => $query
                    ->where('tenant_id', $tenantId)
                    ->whereNull('deleted_at')),
            ],
            'primary_guardian_id' => [
                'nullable',
                'integer',
                Rule::exists('guardians', 'id')->where(fn ($query) => $query
                    ->where('tenant_id', $tenantId)
                    ->whereNull('deleted_at')),
            ],
            'new_guardian_first_name' => ['nullable', 'string', 'max:100', 'required_with:new_guardian_last_name,new_guardian_phone,new_guardian_email'],
            'new_guardian_last_name' => ['nullable', 'string', 'max:100', 'required_with:new_guardian_first_name,new_guardian_phone,new_guardian_email'],
            'new_guardian_phone' => ['nullable', 'string', 'max:20', 'required_with:new_guardian_first_name,new_guardian_last_name,new_guardian_email'],
            'new_guardian_email' => ['nullable', 'email', 'max:150'],
            'new_guardian_relationship' => ['nullable', 'in:father,mother,guardian,other', 'required_with:new_guardian_first_name,new_guardian_last_name,new_guardian_phone,new_guardian_email'],
            'new_guardian_occupation' => ['nullable', 'string', 'max:150'],
            'new_guardian_address' => ['nullable', 'string', 'max:255'],
            'new_guardian_primary' => ['nullable', 'boolean'],
        ]);

        $selectedIds = collect($validated['guardian_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        $hasNewGuardian = filled($validated['new_guardian_first_name'] ?? null)
            || filled($validated['new_guardian_last_name'] ?? null)
            || filled($validated['new_guardian_phone'] ?? null)
            || filled($validated['new_guardian_email'] ?? null);

        if ($selectedIds->isEmpty() && ! $hasNewGuardian) {
            throw ValidationException::withMessages([
                'guardian_ids' => 'Link at least one existing parent/guardian or add a new parent/guardian.',
            ]);
        }

        if ($hasNewGuardian) {
            $duplicate = Guardian::query()
                ->where('tenant_id', $tenantId)
                ->where(function ($query) use ($validated) {
                    $phone = trim((string) ($validated['new_guardian_phone'] ?? ''));
                    $email = trim((string) ($validated['new_guardian_email'] ?? ''));

                    if ($phone !== '') {
                        $query->where('phone', $phone);
                    }
                    if ($email !== '') {
                        $phone !== '' ? $query->orWhere('email', $email) : $query->where('email', $email);
                    }
                })
                ->first();

            if ($duplicate) {
                throw ValidationException::withMessages([
                    'new_guardian_phone' => 'A parent/guardian with this phone or email already exists in this school. Select the existing parent instead of creating a duplicate.',
                ]);
            }
        }

        DB::transaction(function () use ($student, $tenantId, $validated, $selectedIds, $hasNewGuardian, $request) {
            $currentPrimaryId = optional(
                $student->guardians()->wherePivot('is_primary_contact', true)->first()
            )->id;

            $newGuardian = null;
            if ($hasNewGuardian) {
                $newGuardian = Guardian::create([
                    'first_name' => trim($validated['new_guardian_first_name']),
                    'last_name' => trim($validated['new_guardian_last_name']),
                    'phone' => trim($validated['new_guardian_phone']),
                    'email' => filled($validated['new_guardian_email'] ?? null)
                        ? trim($validated['new_guardian_email'])
                        : null,
                    'relationship' => $validated['new_guardian_relationship'],
                    'occupation' => $validated['new_guardian_occupation'] ?? null,
                    'address' => $validated['new_guardian_address'] ?? null,
                ]);

                $selectedIds->push((int) $newGuardian->id);
            }

            $selectedIds = $selectedIds->unique()->values();
            $requestedPrimaryId = (int) ($validated['primary_guardian_id'] ?? 0);

            if ($newGuardian && $request->boolean('new_guardian_primary')) {
                $primaryId = (int) $newGuardian->id;
            } elseif ($requestedPrimaryId && $selectedIds->contains($requestedPrimaryId)) {
                $primaryId = $requestedPrimaryId;
            } elseif ($currentPrimaryId && $selectedIds->contains((int) $currentPrimaryId)) {
                $primaryId = (int) $currentPrimaryId;
            } else {
                $primaryId = (int) $selectedIds->first();
            }

            $sync = [];
            foreach ($selectedIds as $guardianId) {
                $sync[(int) $guardianId] = [
                    'tenant_id' => $tenantId,
                    'is_primary_contact' => (int) $guardianId === $primaryId,
                ];
            }

            $student->guardians()->sync($sync);
        });

        return redirect()
            ->route('students.edit', $student)
            ->with('success', 'Student guardians updated successfully.');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Guardian;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class GuardianController extends Controller
{
    private function tenantId(): int
    {
        return (int) auth()->user()->tenant_id;
    }

    private function guardianBelongsToTenant(Guardian $guardian): bool
    {
        return (int) $guardian->tenant_id === $this->tenantId();
    }

    public function edit(Guardian $guardian)
    {
        abort_unless($this->guardianBelongsToTenant($guardian), 404);
        $guardian->load('students');
        return view('guardians.edit', compact('guardian'));
    }

    public function update(Request $request, Guardian $guardian)
    {
        abort_unless($this->guardianBelongsToTenant($guardian), 404);

        $validated = $request->validate([
            'first_name'   => ['required', 'string', 'max:100'],
            'last_name'    => ['required', 'string', 'max:100'],
            'phone'        => ['required', 'string', 'max:20'],
            'email'        => ['nullable', 'email', 'max:150'],
            'relationship' => ['required', 'in:father,mother,guardian,other'],
            'occupation'   => ['nullable', 'string', 'max:150'],
            'address'      => ['nullable', 'string', 'max:500'],
        ]);

        $guardian->update($validated);

        if ($guardian->user && !empty($validated['email']) && $guardian->user->email !== $validated['email']) {
            $guardian->user->update(['email' => $validated['email']]);
        }

        $student = $guardian->students->first();
        if ($student) {
            return redirect()->route('students.show', $student)
                ->with('success', 'Guardian details updated successfully.');
        }

        return back()->with('success', 'Guardian details updated successfully.');
    }

    public function store(Request $request, Student $student)
    {
        abort_unless((int) $student->tenant_id === $this->tenantId(), 404);

        // Bulk guardian-management form used from the registered student's edit page.
        if ($request->has('guardian_ids') || $request->has('primary_guardian_id') || $request->filled('new_guardian_first_name') || $request->filled('new_guardian_last_name') || $request->filled('new_guardian_phone')) {
            return $this->syncStudentGuardians($request, $student);
        }

        // Existing single-guardian form remains supported.
        $validated = $request->validate([
            'first_name'   => ['required', 'string', 'max:100'],
            'last_name'    => ['required', 'string', 'max:100'],
            'phone'        => ['required', 'string', 'max:20'],
            'email'        => ['nullable', 'email', 'max:150'],
            'relationship' => ['required', 'in:father,mother,guardian,other'],
            'occupation'   => ['nullable', 'string', 'max:150'],
            'address'      => ['nullable', 'string', 'max:500'],
            'is_primary'   => ['boolean'],
        ]);

        $isPrimary = $request->boolean('is_primary');

        DB::transaction(function () use ($validated, $student, $isPrimary) {
            if ($isPrimary) {
                DB::table('guardian_student')
                    ->where('student_id', $student->id)
                    ->update(['is_primary_contact' => false]);
            }

            $guardian = Guardian::create([
                'tenant_id'    => $this->tenantId(),
                'first_name'   => $validated['first_name'],
                'last_name'    => $validated['last_name'],
                'phone'        => $validated['phone'],
                'email'        => $validated['email'] ?? null,
                'relationship' => $validated['relationship'],
                'occupation'   => $validated['occupation'] ?? null,
                'address'      => $validated['address'] ?? null,
            ]);

            $student->guardians()->attach($guardian->id, [
                'tenant_id'          => $this->tenantId(),
                'is_primary_contact' => $isPrimary,
            ]);
        });

        return redirect()->route('students.show', $student)
            ->with('success', 'Guardian added successfully.');
    }

    private function syncStudentGuardians(Request $request, Student $student)
    {
        $tenantId = $this->tenantId();

        $validated = $request->validate([
            'guardian_ids' => ['nullable', 'array'],
            'guardian_ids.*' => [
                'integer',
                Rule::exists('guardians', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)->whereNull('deleted_at')),
            ],
            'primary_guardian_id' => ['nullable', 'integer'],
            'new_guardian_first_name' => ['nullable', 'string', 'max:100', 'required_with:new_guardian_last_name,new_guardian_phone,new_guardian_relationship'],
            'new_guardian_last_name' => ['nullable', 'string', 'max:100', 'required_with:new_guardian_first_name,new_guardian_phone,new_guardian_relationship'],
            'new_guardian_phone' => ['nullable', 'string', 'max:20', 'required_with:new_guardian_first_name,new_guardian_last_name,new_guardian_relationship'],
            'new_guardian_email' => ['nullable', 'email', 'max:150'],
            'new_guardian_relationship' => ['nullable', 'in:father,mother,guardian,other', 'required_with:new_guardian_first_name,new_guardian_last_name,new_guardian_phone'],
            'new_guardian_occupation' => ['nullable', 'string', 'max:150'],
            'new_guardian_address' => ['nullable', 'string', 'max:500'],
            'new_guardian_primary' => ['nullable', 'boolean'],
        ]);

        $guardianIds = collect($validated['guardian_ids'] ?? [])->map(fn ($id) => (int) $id)->unique()->values();
        $hasNewGuardian = $request->filled('new_guardian_first_name') || $request->filled('new_guardian_last_name') || $request->filled('new_guardian_phone');

        if ($guardianIds->isEmpty() && !$hasNewGuardian) {
            throw ValidationException::withMessages([
                'guardian_ids' => 'Link at least one existing parent/guardian or add a new parent/guardian.',
            ]);
        }

        DB::transaction(function () use ($request, $validated, $student, $guardianIds, $hasNewGuardian, $tenantId) {
            $newGuardianId = null;

            if ($hasNewGuardian) {
                $newGuardian = Guardian::create([
                    'tenant_id' => $tenantId,
                    'first_name' => $validated['new_guardian_first_name'],
                    'last_name' => $validated['new_guardian_last_name'],
                    'phone' => $validated['new_guardian_phone'],
                    'email' => $validated['new_guardian_email'] ?? null,
                    'relationship' => $validated['new_guardian_relationship'],
                    'occupation' => $validated['new_guardian_occupation'] ?? null,
                    'address' => $validated['new_guardian_address'] ?? null,
                ]);
                $newGuardianId = (int) $newGuardian->id;
                $guardianIds->push($newGuardianId);
            }

            $guardianIds = $guardianIds->unique()->values();

            $requestedPrimary = (int) ($validated['primary_guardian_id'] ?? 0);
            if ($request->boolean('new_guardian_primary') && $newGuardianId) {
                $primaryGuardianId = $newGuardianId;
            } elseif ($requestedPrimary && $guardianIds->contains($requestedPrimary)) {
                $primaryGuardianId = $requestedPrimary;
            } else {
                $currentPrimaryId = (int) optional(
                    $student->guardians()->wherePivot('is_primary_contact', true)->first()
                )->id;
                $primaryGuardianId = $guardianIds->contains($currentPrimaryId)
                    ? $currentPrimaryId
                    : (int) $guardianIds->first();
            }

            $sync = [];
            foreach ($guardianIds as $guardianId) {
                $sync[(int) $guardianId] = [
                    'tenant_id' => $tenantId,
                    'is_primary_contact' => (int) $guardianId === $primaryGuardianId,
                ];
            }

            $student->guardians()->sync($sync);
        });

        return redirect()->route('students.edit', $student)
            ->with('success', 'Parent and guardian links updated successfully.');
    }

    public function setPrimary(Request $request, Student $student, Guardian $guardian)
    {
        abort_unless((int) $student->tenant_id === $this->tenantId(), 404);
        abort_unless($this->guardianBelongsToTenant($guardian), 404);
        abort_unless($student->guardians()->whereKey($guardian->id)->exists(), 404);

        DB::transaction(function () use ($student, $guardian) {
            DB::table('guardian_student')
                ->where('student_id', $student->id)
                ->update(['is_primary_contact' => false]);

            DB::table('guardian_student')
                ->where('student_id', $student->id)
                ->where('guardian_id', $guardian->id)
                ->update(['is_primary_contact' => true]);
        });

        return back()->with('success', "{$guardian->full_name} set as primary contact.");
    }

    public function detach(Student $student, Guardian $guardian)
    {
        abort_unless((int) $student->tenant_id === $this->tenantId(), 404);
        abort_unless($this->guardianBelongsToTenant($guardian), 404);

        if ($student->guardians()->count() <= 1) {
            return back()->withErrors(['guardian' => 'A student must retain at least one parent or guardian.']);
        }

        $wasPrimary = (bool) $student->guardians()->whereKey($guardian->id)->first()?->pivot?->is_primary_contact;
        $student->guardians()->detach($guardian->id);

        if ($wasPrimary) {
            $replacement = $student->guardians()->first();
            if ($replacement) {
                $student->guardians()->updateExistingPivot($replacement->id, ['is_primary_contact' => true]);
            }
        }

        return redirect()->route('students.show', $student)
            ->with('success', 'Guardian unlinked from student.');
    }
}

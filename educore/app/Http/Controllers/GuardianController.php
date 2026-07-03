<?php

namespace App\Http\Controllers;

use App\Models\Guardian;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

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

    // ── Edit form ──────────────────────────────────────────────────────
    public function edit(Guardian $guardian)
    {
        abort_unless($this->guardianBelongsToTenant($guardian), 404);
        $guardian->load('students');
        return view('guardians.edit', compact('guardian'));
    }

    // ── Update ────────────────────────────────────────────────────────
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

        // Also update the linked portal user email if it exists and email changed
        if ($guardian->user && $validated['email'] && $guardian->user->email !== $validated['email']) {
            $guardian->user->update(['email' => $validated['email']]);
        }

        $student = $guardian->students->first();
        if ($student) {
            return redirect()->route('students.show', $student)
                ->with('success', 'Guardian details updated successfully.');
        }

        return back()->with('success', 'Guardian details updated successfully.');
    }

    // ── Add new guardian to a student ─────────────────────────────────
    public function store(Request $request, Student $student)
    {
        abort_unless((int) $student->tenant_id === $this->tenantId(), 404);

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

        // If setting as primary, unset existing primary
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

        return redirect()->route('students.show', $student)
            ->with('success', 'Guardian added successfully.');
    }

    // ── Set primary contact ────────────────────────────────────────────
    public function setPrimary(Request $request, Student $student, Guardian $guardian)
    {
        abort_unless((int) $student->tenant_id === $this->tenantId(), 404);
        abort_unless($this->guardianBelongsToTenant($guardian), 404);

        DB::table('guardian_student')
            ->where('student_id', $student->id)
            ->update(['is_primary_contact' => false]);

        DB::table('guardian_student')
            ->where('student_id', $student->id)
            ->where('guardian_id', $guardian->id)
            ->update(['is_primary_contact' => true]);

        return back()->with('success', "{$guardian->full_name} set as primary contact.");
    }

    // ── Detach guardian from student ───────────────────────────────────
    public function detach(Student $student, Guardian $guardian)
    {
        abort_unless((int) $student->tenant_id === $this->tenantId(), 404);
        abort_unless($this->guardianBelongsToTenant($guardian), 404);

        $student->guardians()->detach($guardian->id);

        // Delete guardian record if they have no other students
        if ($guardian->students()->count() === 0) {
            $guardian->delete();
        }

        return redirect()->route('students.show', $student)
            ->with('success', 'Guardian removed from student.');
    }
}

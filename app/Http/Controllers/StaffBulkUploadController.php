<?php

namespace App\Http\Controllers;

use App\Models\StaffWorkHistory;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class StaffBulkUploadController extends Controller
{
    public function index()
    {
        return view('staff.bulk-upload');
    }

    public function template()
    {
        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="staff_import_template.csv"',
        ];

        $rows = [
            ['name', 'email', 'role', 'phone', 'employment_started_at', 'position_title', 'department_name', 'employment_type', 'appointment_type', 'functional_role', 'grade_level'],
            ['Amina Bello',     'amina.bello@school.ng', 'subject_teacher', '08012345678', '2026-01-15', 'Mathematics Teacher', 'Academics', 'Full-time', 'Initial appointment', 'Teacher', ''],
            ['Emeka Okafor',    'emeka@school.ng',        'accountant', '07098765432', '2026-01-15', 'Accountant', 'Finance', 'Full-time', 'Initial appointment', 'Finance Officer', ''],
            ['Dr Fatima Hassan','fatima@school.ng',        'principal',  '09011223344', '2026-01-15', 'Principal', 'Administration', 'Full-time', 'Initial appointment', 'School Head', ''],
        ];

        $callback = function () use ($rows) {
            $out = fopen('php://output', 'w');
            foreach ($rows as $row) fputcsv($out, $row);
            fclose($out);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,xlsx,xls,txt', 'max:5120'],
        ]);

        $tenantId  = auth()->user()->tenant_id;
        $file      = $request->file('file');
        $rows      = $this->parseCsv($file->getRealPath());
        $imported  = 0;
        $errorList = [];
        $preview   = [];

        $validRoles = User::staffRoleNames();

        foreach ($rows as $i => $row) {
            $rowNum = $i + 2;
            $name   = trim($row['name'] ?? ($row[0] ?? ''));
            $email  = trim(strtolower($row['email'] ?? ($row[1] ?? '')));
            $role   = User::canonicalRole(strtolower(trim($row['role'] ?? ($row[2] ?? 'subject_teacher'))));
            $phone  = trim($row['phone'] ?? ($row[3] ?? ''));
            $startedAtRaw = trim($row['employment_started_at'] ?? ($row[4] ?? ''));
            $positionTitle = trim($row['position_title'] ?? ($row[5] ?? ''));
            $departmentName = trim($row['department_name'] ?? ($row[6] ?? ''));
            $employmentType = trim($row['employment_type'] ?? ($row[7] ?? ''));
            $appointmentType = trim($row['appointment_type'] ?? ($row[8] ?? ''));
            $functionalRole = trim($row['functional_role'] ?? ($row[9] ?? ''));
            $gradeLevel = trim($row['grade_level'] ?? ($row[10] ?? ''));

            if (!$name || !$email) {
                $errorList[] = "Row {$rowNum}: name and email are required";
                $preview[]   = ['name' => $name, 'email' => $email, 'role' => $role, 'status' => 'error', 'note' => 'Missing name or email'];
                continue;
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errorList[] = "Row {$rowNum}: invalid email '{$email}'";
                $preview[]   = ['name' => $name, 'email' => $email, 'role' => $role, 'status' => 'error', 'note' => 'Invalid email'];
                continue;
            }

            if (!in_array($role, $validRoles, true)) $role = 'subject_teacher';

            if (!$startedAtRaw || !$positionTitle) {
                $errorList[] = "Row {$rowNum}: employment_started_at and position_title are required";
                $preview[]   = ['name' => $name, 'email' => $email, 'role' => $role, 'status' => 'error', 'note' => 'Missing employment start date or position title'];
                continue;
            }

            try {
                $startedAt = Carbon::parse($startedAtRaw)->toDateString();
            } catch (\Throwable) {
                $errorList[] = "Row {$rowNum}: invalid employment_started_at '{$startedAtRaw}'";
                $preview[]   = ['name' => $name, 'email' => $email, 'role' => $role, 'status' => 'error', 'note' => 'Invalid employment start date'];
                continue;
            }

            if (Carbon::parse($startedAt)->gt(today())) {
                $errorList[] = "Row {$rowNum}: employment_started_at cannot be in the future";
                $preview[]   = ['name' => $name, 'email' => $email, 'role' => $role, 'status' => 'error', 'note' => 'Future employment start date'];
                continue;
            }

            if (User::where('email', $email)->exists()) {
                $errorList[] = "Row {$rowNum}: email '{$email}' already exists";
                $preview[]   = ['name' => $name, 'email' => $email, 'role' => $role, 'status' => 'error', 'note' => 'Email already exists'];
                continue;
            }

            $tempPassword = Str::random(10);

            $user = DB::transaction(function () use (
                $tenantId,
                $name,
                $email,
                $tempPassword,
                $role,
                $phone,
                $startedAt,
                $positionTitle,
                $departmentName,
                $employmentType,
                $appointmentType,
                $functionalRole,
                $gradeLevel
            ) {
                $user = User::create([
                    'tenant_id' => $tenantId,
                    'name'      => $name,
                    'email'     => $email,
                    'password'  => Hash::make($tempPassword),
                    'role'      => $role,
                    'phone'     => $phone ?: null,
                    'is_active' => true,
                    'employment_status' => User::STAFF_STATUS_ACTIVE,
                    'employment_started_at' => $startedAt,
                    'employment_ended_at' => null,
                    'status_changed_at' => now(),
                ]);
                $user->assignRole($role);

                StaffWorkHistory::create([
                    'tenant_id' => $tenantId,
                    'user_id' => $user->id,
                    'position_title' => $positionTitle,
                    'department_name' => $departmentName ?: null,
                    'employment_type' => $employmentType ?: null,
                    'functional_role' => $functionalRole ?: null,
                    'grade_level' => $gradeLevel ?: null,
                    'appointment_type' => $appointmentType ?: null,
                    'start_date' => $startedAt,
                    'change_type' => StaffWorkHistory::CHANGE_APPOINTMENT,
                    'reason' => 'Initial staff bulk upload.',
                    'recorded_by' => auth()->id(),
                    'approved_by' => auth()->id(),
                    'approved_at' => now(),
                ]);

                return $user;
            });

            $imported++;
            $preview[] = [
                'name'   => $name,
                'email'  => $email,
                'role'   => $role,
                'status' => 'ok',
                'note'   => "Imported. Temp password: {$tempPassword}",
            ];
        }

        return back()
            ->with('success', "{$imported} staff member(s) imported.")
            ->with('imported', $imported)
            ->with('errors_list', $errorList)
            ->with('preview', $preview);
    }

    private function parseCsv(string $path): array
    {
        $rows    = [];
        $headers = null;
        if (($handle = fopen($path, 'r')) !== false) {
            while (($data = fgetcsv($handle)) !== false) {
                if (!$headers) {
                    $headers = array_map('strtolower', array_map('trim', $data));
                    continue;
                }
                if (count($data) >= count($headers)) {
                    $rows[] = array_combine($headers, array_slice($data, 0, count($headers)));
                }
            }
            fclose($handle);
        }
        return $rows;
    }
}

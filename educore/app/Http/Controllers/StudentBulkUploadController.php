<?php

namespace App\Http\Controllers;

use App\Models\ClassArm;
use App\Models\ClassLevel;
use App\Models\Guardian;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StudentBulkUploadController extends Controller
{
    public function index()
    {
        $classArms = ClassArm::with('classLevel')->orderBy('class_level_id')->get();
        return view('students.bulk-upload', compact('classArms'));
    }

    public function template()
    {
        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="student_import_template.csv"',
        ];

        $rows = [
            ['first_name','last_name','middle_name','gender','date_of_birth',
             'admission_number','state_of_origin','lga_of_origin','religion',
             'class_arm','guardian_name','guardian_phone','guardian_relationship'],
            ['Amina','Bello','','female','2010-05-14',
             '','Kano','Kano Municipal','Islam',
             'JSS 1 A','Ibrahim Bello','08012345678','father'],
            ['Emeka','Okafor','Chukwu','male','2009-11-20',
             '','Anambra','Onitsha','Christianity',
             'JSS 2 B','Mrs Okafor','07098765432','mother'],
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

        $file           = $request->file('file');
        $defaultArmId   = $request->input('default_class_arm_id');
        $tenantId       = auth()->user()->tenant_id;

        // Build class arm lookup map (name → id)
        $armMap = ClassArm::with('classLevel')
            ->get()
            ->mapWithKeys(fn($a) => [
                strtolower($a->classLevel->name . ' ' . $a->name) => $a->id
            ])->toArray();

        // Parse CSV/Excel
        $rows = $this->parseFile($file);
        if (empty($rows)) {
            return back()->withErrors(['file' => 'Could not read file. Ensure it is a valid CSV or Excel file.']);
        }

        $imported   = 0;
        $errorList  = [];
        $preview    = [];

        DB::beginTransaction();
        try {
            foreach ($rows as $i => $row) {
                $rowNum = $i + 2; // 1-indexed + header row

                $firstName = trim($row['first_name'] ?? $row[0] ?? '');
                $lastName  = trim($row['last_name']  ?? $row[1] ?? '');

                if (!$firstName || !$lastName) {
                    $errorList[] = "Row {$rowNum}: first_name and last_name are required";
                    $preview[]   = ['first_name'=>$firstName,'last_name'=>$lastName,'class'=>'—','admission_number'=>'—','status'=>'error','note'=>'Missing name'];
                    continue;
                }

                // Resolve class arm
                $classArmId = $defaultArmId ?: null;
                $classLabel = '—';
                if (!empty($row['class_arm'])) {
                    $key = strtolower(trim($row['class_arm']));
                    if (isset($armMap[$key])) {
                        $classArmId = $armMap[$key];
                        $classLabel = $row['class_arm'];
                    } else {
                        $errorList[] = "Row {$rowNum}: class_arm '{$row['class_arm']}' not found — student imported without class";
                    }
                } elseif ($classArmId) {
                    $arm = ClassArm::with('classLevel')->find($classArmId);
                    $classLabel = $arm ? $arm->classLevel->name . ' ' . $arm->name : '—';
                }

                // Generate admission number if not provided
                $admNum = trim($row['admission_number'] ?? '');
                if (!$admNum) {
                    $count  = Student::withoutTenantScope()->count() + $imported + 1;
                    $admNum = 'STU' . str_pad($count, 4, '0', STR_PAD_LEFT);
                }

                // Create student
                $student = Student::create([
                    'first_name'           => $firstName,
                    'last_name'            => $lastName,
                    'middle_name'          => trim($row['middle_name'] ?? '') ?: null,
                    'admission_number'     => $admNum,
                    'gender'               => strtolower(trim($row['gender'] ?? '')) ?: null,
                    'date_of_birth'        => $this->parseDate($row['date_of_birth'] ?? ''),
                    'state_of_origin'      => trim($row['state_of_origin'] ?? '') ?: null,
                    'lga_of_origin'        => trim($row['lga_of_origin'] ?? '') ?: null,
                    'religion'             => trim($row['religion'] ?? '') ?: null,
                    'current_class_arm_id' => $classArmId,
                    'status'               => 'active',
                    'admission_date'       => now()->toDateString(),
                ]);

                // Create guardian if provided
                $guardianName = trim($row['guardian_name'] ?? '');
                $guardianPhone= trim($row['guardian_phone'] ?? '');
                if ($guardianName || $guardianPhone) {
                    $parts = explode(' ', $guardianName, 2);
                    $rel   = trim($row['guardian_relationship'] ?? '');
                    if (!in_array($rel, ['father','mother','guardian','other'])) $rel = 'guardian';

                    $guardian = Guardian::create([
                        'first_name'   => $parts[0] ?? 'Guardian',
                        'last_name'    => $parts[1] ?? '',
                        'phone'        => $guardianPhone ?: '',
                        'relationship' => $rel,
                    ]);
                    $guardian->students()->attach($student->id, ['tenant_id' => $tenantId]);
                }

                $imported++;
                $preview[] = [
                    'first_name'       => $firstName,
                    'last_name'        => $lastName,
                    'class'            => $classLabel,
                    'admission_number' => $admNum,
                    'status'           => 'ok',
                    'note'             => 'Successfully imported',
                ];
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['file' => 'Import failed: ' . $e->getMessage()]);
        }

        return back()
            ->with('success', "{$imported} student(s) imported successfully.")
            ->with('imported', $imported)
            ->with('errors_list', $errorList)
            ->with('preview', $preview);
    }

    private function parseFile($file): array
    {
        $ext  = strtolower($file->getClientOriginalExtension());
        $path = $file->getRealPath();

        if ($ext === 'csv' || $ext === 'txt') {
            return $this->parseCsv($path);
        }

        // For xlsx/xls - try to use PhpSpreadsheet if available, else treat as CSV
        if (class_exists('\PhpOffice\PhpSpreadsheet\IOFactory')) {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($path);
            $sheet       = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);
            $headers     = array_map('strtolower', array_map('trim', array_shift($sheet)));
            return array_map(fn($r) => array_combine($headers, array_pad($r, count($headers), '')), $sheet);
        }

        // Fallback: try parsing as CSV
        return $this->parseCsv($path);
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
                if (count($data) === count($headers)) {
                    $rows[] = array_combine($headers, $data);
                }
            }
            fclose($handle);
        }
        return $rows;
    }

    private function parseDate(string $val): ?string
    {
        if (!$val) return null;
        try {
            return \Carbon\Carbon::parse($val)->toDateString();
        } catch (\Exception $e) {
            return null;
        }
    }
}

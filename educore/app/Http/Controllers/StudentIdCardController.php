<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Support\Facades\Storage;

class StudentIdCardController extends Controller
{
    public function show(Student $student)
    {
        $tenant = auth()->user()->tenant;

        $hasPhoto = $student->passport_photo_path
            && Storage::disk('public')->exists($student->passport_photo_path);

        $logo = null;
        if (!empty($tenant->logo_path)) {
            $logo = asset('storage/' . preg_replace('#^storage/#', '', ltrim($tenant->logo_path, '/')));
        }

        return view('students.id-card', compact('student', 'tenant', 'hasPhoto', 'logo'));
    }
}

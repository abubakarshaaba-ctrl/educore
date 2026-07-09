<?php

namespace App\Http\Controllers;

use App\Models\CertificateIssuance;
use App\Models\Student;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CertificateController extends Controller
{
    public function index(Request $request)
    {
        $issuances = CertificateIssuance::with(['student', 'issuer'])
            ->latest('issued_at')
            ->paginate(25);

        $students = Student::whereIn('status', [Student::STATUS_GRADUATED, Student::STATUS_WITHDRAWN, Student::STATUS_ACTIVE])
            ->orderBy('first_name')
            ->get();

        return view('certificates.index', compact('issuances', 'students'));
    }

    public function generate(Request $request)
    {
        $data = $request->validate([
            'student_id'       => ['required', 'exists:students,id'],
            'certificate_type' => ['required', 'in:leaving_certificate,testimonial,transfer_certificate'],
            'remarks'          => ['nullable', 'string', 'max:1000'],
        ]);

        $student = Student::findOrFail($data['student_id']);
        $tenant  = auth()->user()->tenant;
        $serial  = strtoupper(Str::random(3)) . '-' . now()->format('Ymd') . '-' . strtoupper(Str::random(4));

        $issuance = CertificateIssuance::create([
            'student_id'       => $student->id,
            'certificate_type' => $data['certificate_type'],
            'serial_number'    => $serial,
            'issued_by'        => auth()->id(),
            'issued_at'        => now(),
        ]);

        $pdf = Pdf::loadView('certificates.certificate-pdf', [
            'student'     => $student,
            'tenant'      => $tenant,
            'type'        => $data['certificate_type'],
            'remarks'     => $data['remarks'] ?? null,
            'serial'      => $serial,
            'issuedAt'    => $issuance->issued_at,
        ]);

        $label = str_replace('_', '-', $data['certificate_type']);
        return $pdf->download("{$label}-{$student->admission_number}.pdf");
    }
}

<?php

namespace App\Http\Controllers;

use App\Services\Auth\AuthAuditLogger;
use App\Services\Cbt\CbtLanAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CbtLanStudentAccessController extends Controller
{
    public function show(Request $request, CbtLanAccessService $access)
    {
        abort_unless($access->isAvailable($request), 404);

        if (Auth::check()) {
            return Auth::user()->isStudent()
                ? redirect()->route('student.portal.exams')
                : redirect()->route('dashboard');
        }

        return view('auth.cbt-lan-access');
    }

    public function authenticate(
        Request $request,
        CbtLanAccessService $access,
        AuthAuditLogger $audit
    ) {
        abort_unless($access->isAvailable($request), 404);

        $data = $request->validate([
            'admission_number' => ['required', 'string', 'max:80'],
        ]);
        $admissionNumber = trim($data['admission_number']);
        $resolved = $access->resolveAdmissionNumber($admissionNumber);

        if (!$resolved) {
            return back()
                ->withErrors(['admission_number' => 'Admission number not found for an available LAN examination.'])
                ->withInput(['admission_number' => $admissionNumber]);
        }

        $user = $resolved['user'];
        $student = $resolved['student'];
        $examIds = $resolved['exams']->pluck('id')->map(fn ($id) => (int) $id)->values()->all();

        Auth::login($user, false);
        $request->session()->regenerate();
        $request->session()->put([
            'tenant_id' => $student->tenant_id,
            'tenant_slug' => $user->tenant?->slug,
            'cbt_lan_only' => true,
            'cbt_lan_exam_ids' => $examIds,
            'cbt_lan_student_id' => $student->id,
        ]);
        $request->session()->save();

        $user->forceFill(['last_login_at' => now()])->save();
        $audit->recordForUser($user, 'auth.login.success', [
            'login_surface' => 'cbt_lan_admission_number',
            'exam_ids' => $examIds,
        ], $request);

        return redirect()->route('cbt.exams.start', $resolved['preferred_exam']);
    }
}

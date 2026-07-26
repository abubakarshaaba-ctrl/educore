<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Admission;
use App\Models\ClassArm;
use App\Models\ClassLevel;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdmissionOfficerController extends Controller
{
    public function index(Request $request)
    {
        $this->guard($request);
        $query = Admission::with('applyingForClassLevel:id,name')->latest();
        if ($request->filled('status')) $query->where('status', $request->string('status'));
        if ($request->filled('search')) {
            $search = trim($request->string('search'));
            $query->where(fn ($q) => $q->where('first_name', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%")
                ->orWhere('application_number', 'like', "%{$search}%"));
        }

        return response()->json([
            'stats' => [
                'total' => Admission::count(),
                'pending' => Admission::where('status', 'pending')->count(),
                'shortlisted' => Admission::where('status', 'shortlisted')->count(),
                'admitted' => Admission::where('status', 'admitted')->count(),
                'rejected' => Admission::where('status', 'rejected')->count(),
            ],
            'class_levels' => ClassLevel::orderBy('order_index')->get(['id', 'name']),
            'class_arms' => ClassArm::with('classLevel:id,name')->get()->map(fn ($arm) => [
                'id' => $arm->id, 'name' => trim(($arm->classLevel?->name ?? '').' '.$arm->name),
            ]),
            'admissions' => $query->limit(100)->get()->map(fn (Admission $item) => [
                'id' => $item->id,
                'application_number' => $item->application_number,
                'name' => trim("{$item->first_name} {$item->other_names} {$item->last_name}"),
                'gender' => $item->gender,
                'date_of_birth' => $item->date_of_birth,
                'class_level' => $item->applyingForClassLevel?->name,
                'guardian_name' => $item->guardian_name,
                'guardian_phone' => $item->guardian_phone,
                'guardian_email' => $item->guardian_email,
                'status' => $item->status,
                'notes' => $item->notes,
                'application_date' => $item->application_date,
            ]),
        ]);
    }

    public function store(Request $request)
    {
        $user = $this->guard($request);
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'other_names' => ['nullable', 'string', 'max:80'],
            'date_of_birth' => ['required', 'date'],
            'gender' => ['required', 'in:male,female'],
            'applying_for_class_level_id' => ['nullable', 'integer', 'exists:class_levels,id'],
            'guardian_name' => ['required', 'string', 'max:160'],
            'guardian_phone' => ['required', 'string', 'max:40'],
            'guardian_email' => ['nullable', 'email'],
            'guardian_relationship' => ['required', 'string', 'max:80'],
            'address' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);
        $data += [
            'tenant_id' => $user->tenant_id,
            'application_number' => 'APP-'.date('Y').'-'.strtoupper(Str::random(6)),
            'application_date' => today()->toDateString(),
            'status' => 'pending',
        ];
        $admission = Admission::create($data);
        return response()->json(['message' => 'Admission application created.', 'id' => $admission->id], 201);
    }

    public function updateStatus(Request $request, Admission $admission)
    {
        $user = $this->guard($request);
        abort_unless((int) $admission->tenant_id === (int) $user->tenant_id, 404);
        app(\App\Http\Controllers\AdmissionController::class)->updateStatus($request, $admission);
        return response()->json(['message' => 'Application status updated.', 'status' => $admission->fresh()->status]);
    }

    private function guard(Request $request): User
    {
        $user = $request->user();
        $access = User::ROLE_ACCESS[$user?->roleKey()] ?? [];
        abort_unless($user && (in_array('*', $access, true) || in_array('admissions', $access, true)), 403, 'Admissions access required.');
        return $user;
    }
}

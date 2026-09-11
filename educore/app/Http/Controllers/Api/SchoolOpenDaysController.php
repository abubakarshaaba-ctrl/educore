<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SchoolOpenDaysController extends Controller
{
    private const ALL_WEEK_DAYS = [
        'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday',
    ];

    public function update(Request $request)
    {
        $user = $this->guard($request);
        $data = $request->validate([
            'school_open_days' => ['required', 'array', 'min:1', 'max:7'],
            'school_open_days.*' => ['required', 'string', Rule::in(self::ALL_WEEK_DAYS)],
        ]);

        $selected = array_values(array_intersect(
            self::ALL_WEEK_DAYS,
            array_map(static fn ($day) => strtolower(trim((string) $day)), $data['school_open_days'])
        ));

        abort_if($selected === [], 422, 'Select at least one school opening day.');

        $user->tenant->update(['school_open_days' => $selected]);

        return response()->json([
            'success' => true,
            'message' => 'School opening days saved successfully.',
            'school_open_days' => $user->tenant->fresh()->schoolOpenDays(),
        ]);
    }

    private function guard(Request $request): User
    {
        $user = $request->user();
        abort_unless(
            $user && $user->tenant_id && ($user->isSuperAdmin() || $user->canManage('staff-attendance')),
            403,
            'You do not have permission to change school opening days.'
        );
        abort_unless($user->tenant, 422, 'School account is unavailable.');

        return $user;
    }
}

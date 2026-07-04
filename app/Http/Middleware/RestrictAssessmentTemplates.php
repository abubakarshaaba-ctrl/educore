<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RestrictAssessmentTemplates
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();
        $restrictedRoles = ['form teacher', 'subject teacher', 'assistant form teacher'];

        if ($user && in_array($user->role, $restrictedRoles)) {
            abort(403, 'Unauthorized access: Standard teaching staff are restricted from modifying assessment configuration templates.');
        }

        return $next($request);
    }
}
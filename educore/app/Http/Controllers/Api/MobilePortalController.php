<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Mobile\MobileModuleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class MobilePortalController extends Controller
{
    public function modules(Request $request, MobileModuleService $modules)
    {
        $user = $request->user();
        abort_unless($user, 401);

        return response()->json(['modules' => $modules->forUser($user)]);
    }

    public function createSession(Request $request, MobileModuleService $modules)
    {
        $data = $request->validate(['path' => ['required', 'string', 'max:500', 'regex:/^\/(?!\/)/']]);
        $allowed = collect($modules->forUser($request->user()))->pluck('path');
        $path = $data['path'];

        abort_unless(
            $allowed->contains(fn ($base) => $path === $base || str_starts_with($path, rtrim($base, '/').'/')),
            403,
            'This route is not available to your role.'
        );

        abort_unless(
            $this->routeExistsForRequest($request, $path),
            404,
            'This workspace is not available on the web application.'
        );

        $token = Str::random(64);
        Cache::put('mobile-web-session:'.$token, ['user_id' => $request->user()->id, 'path' => $path], now()->addMinutes(2));

        return response()->json(['url' => URL::temporarySignedRoute('mobile.web-session', now()->addMinutes(2), ['token' => $token])]);
    }

    public function consumeSession(Request $request, string $token)
    {
        $session = Cache::pull('mobile-web-session:'.$token);
        abort_unless($session, 410, 'This mobile session link has expired or was already used.');
        $user = User::find($session['user_id']);
        abort_unless($user && $user->is_active, 403);
        Auth::login($user);
        $request->session()->regenerate();

        return redirect($session['path']);
    }

    private function routeExistsForRequest(Request $request, string $path): bool
    {
        try {
            $probe = Request::create(
                rtrim($request->getSchemeAndHttpHost(), '/').$path,
                'GET'
            );
            Route::getRoutes()->match($probe);

            return true;
        } catch (NotFoundHttpException|MethodNotAllowedHttpException) {
            return false;
        }
    }
}

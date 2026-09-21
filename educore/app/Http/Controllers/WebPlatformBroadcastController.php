<?php

namespace App\Http\Controllers;

use App\Services\Notifications\PlatformBroadcastPublisher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class WebPlatformBroadcastController extends Controller
{
    public function store(
        Request $request,
        PlatformBroadcastPublisher $publisher,
    ): RedirectResponse {
        abort_unless(
            $request->user()?->isSuperAdmin(),
            403,
            'Platform Super Admin access required.',
        );

        $data = $request->validate($publisher->validationRules());

        $publication = $publisher->publish(
            actor: $request->user(),
            data: $data,
            image: $request->file('image'),
        );

        return back()->with(
            'success',
            "Broadcast published to {$publication['tenant_count']} school(s). Push and email delivery is processing.",
        );
    }
}

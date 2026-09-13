<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Api\PlatformBroadcastController;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class WebPlatformBroadcastController extends PlatformBroadcastController
{
    public function store(Request $request): RedirectResponse
    {
        $response = parent::store($request);
        $payload = $response->getData(true);

        return back()->with(
            'success',
            $payload['message'] ?? 'Broadcast sent to schools.'
        );
    }
}

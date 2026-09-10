<?php

namespace Tests\Feature;

use App\Http\Controllers\MessagingController;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class WebSchoolCommunicationContractTest extends TestCase
{
    public function test_required_web_communication_actions_exist(): void
    {
        foreach (['inbox', 'composeInternal', 'storeInternal', 'thread', 'reply'] as $method) {
            $this->assertTrue(method_exists(MessagingController::class, $method), "Missing MessagingController::{$method}");
        }
    }

    public function test_web_routes_cover_messages_events_qr_and_cbt(): void
    {
        $registered = collect(Route::getRoutes())->flatMap(function ($route) {
            return collect($route->methods())->map(fn (string $method): string => $method.' '.$route->uri());
        })->all();

        $expected = [
            'GET messages',
            'GET messages/internal/compose',
            'POST messages/internal',
            'GET calendar',
            'POST calendar',
            'GET staff-attendance/qr',
            'POST staff-attendance/reset-qr',
            'GET cbt/exams',
            'POST cbt/exams',
            'POST cbt/exams/{exam}/publish',
            'POST cbt/exams/{exam}/close',
        ];

        foreach ($expected as $signature) {
            $this->assertContains($signature, $registered, "Missing web route: {$signature}");
        }
    }
}

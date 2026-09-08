<?php

namespace Tests\Feature;

use App\Http\Middleware\BlockPublicDiagnostics;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

class PublicDiagnosticBoundaryTest extends TestCase
{
    public function test_apk_filesystem_debug_endpoint_is_not_publicly_reachable(): void
    {
        $request = Request::create('/download/app/_debug?token=leaked-token', 'GET');
        $middleware = new BlockPublicDiagnostics();

        $this->expectException(NotFoundHttpException::class);
        $middleware->handle($request, fn () => response('should-not-run'));
    }

    public function test_normal_app_download_path_is_not_blocked_by_diagnostic_guard(): void
    {
        $request = Request::create('/download/app', 'GET');
        $middleware = new BlockPublicDiagnostics();
        $response = $middleware->handle($request, fn () => response('allowed'));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('allowed', $response->getContent());
    }
}

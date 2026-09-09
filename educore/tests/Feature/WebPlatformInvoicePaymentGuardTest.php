<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsurePayablePlatformInvoice;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class WebPlatformInvoicePaymentGuardTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
            $this->markTestSkipped('Platform invoice guard tests require sqlite :memory:.');
        }

        Schema::dropIfExists('platform_invoices');
        Schema::create('platform_invoices', function (Blueprint $table): void {
            $table->id();
            $table->string('status');
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('payment_reference')->nullable();
            $table->timestamps();
        });
    }

    public function test_cancelled_invoice_cannot_reach_paystack_initiation_controller(): void
    {
        $invoice = DB::table('platform_invoices')->insertGetId([
            'status' => 'cancelled',
            'amount' => 25000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $called = false;
        $response = $this->runMiddleware(
            routeName: 'super.billing.pay',
            routeParameters: ['invoice' => $invoice],
            query: [],
            nextCalled: $called,
        );

        $this->assertFalse($called);
        $this->assertContains($response->getStatusCode(), [302, 422]);
    }

    public function test_cancelled_invoice_cannot_reach_monnify_callback_controller(): void
    {
        DB::table('platform_invoices')->insert([
            'status' => 'cancelled',
            'amount' => 25000,
            'payment_reference' => 'MON-CANCELLED-001',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $called = false;
        $response = $this->runMiddleware(
            routeName: 'super.billing.pay.monnify.callback',
            routeParameters: [],
            query: ['reference' => 'MON-CANCELLED-001'],
            nextCalled: $called,
        );

        $this->assertFalse($called);
        $this->assertContains($response->getStatusCode(), [302, 422]);
    }

    public function test_paid_callback_remains_idempotently_reachable(): void
    {
        DB::table('platform_invoices')->insert([
            'status' => 'paid',
            'amount' => 25000,
            'payment_reference' => 'PAY-ALREADY-001',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $called = false;
        $response = $this->runMiddleware(
            routeName: 'super.billing.pay.callback',
            routeParameters: [],
            query: ['reference' => 'PAY-ALREADY-001'],
            nextCalled: $called,
        );

        $this->assertTrue($called);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_zero_value_invoice_cannot_start_online_payment(): void
    {
        $invoice = DB::table('platform_invoices')->insertGetId([
            'status' => 'pending',
            'amount' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $called = false;
        $response = $this->runMiddleware(
            routeName: 'super.billing.pay.monnify',
            routeParameters: ['invoice' => $invoice],
            query: [],
            nextCalled: $called,
        );

        $this->assertFalse($called);
        $this->assertContains($response->getStatusCode(), [302, 422]);
    }

    private function runMiddleware(
        string $routeName,
        array $routeParameters,
        array $query,
        bool &$nextCalled,
    ) {
        $request = Request::create('/guard-test', 'GET', $query);
        $route = new Route(['GET'], '/guard-test', fn () => response('route'));
        $route->name($routeName);
        $route->bind($request);
        foreach ($routeParameters as $key => $value) {
            $route->setParameter($key, $value);
        }
        $request->setRouteResolver(fn () => $route);

        return app(EnsurePayablePlatformInvoice::class)->handle(
            $request,
            function () use (&$nextCalled) {
                $nextCalled = true;
                return response('controller reached');
            }
        );
    }
}

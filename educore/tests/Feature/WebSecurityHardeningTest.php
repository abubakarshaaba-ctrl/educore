<?php

namespace Tests\Feature;

use App\Http\Middleware\EnforceTenantFormReferences;
use App\Http\Middleware\ProtectPublicTenantPortal;
use App\Models\AcademicTrack;
use App\Models\Admission;
use App\Models\StudentTransfer;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Tests\TestCase;

class WebSecurityHardeningTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
            $this->markTestSkipped('Web security tests require sqlite :memory:.');
        }

        config([
            'tenancy.central_hosts' => ['educore.test', 'localhost', '127.0.0.1'],
            'tenancy.local_base_domain' => 'educore.test',
            'tenancy.scheme' => 'http',
        ]);

        foreach ([
            'student_subject_selections', 'class_level_subjects', 'class_arms',
            'student_transfers', 'academic_tracks', 'admissions', 'class_levels',
            'users', 'tenants',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('tenants', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('subdomain')->nullable();
            $table->string('custom_domain')->nullable();
            $table->boolean('domain_verified')->default(false);
            $table->string('status')->default('active');
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->string('role')->nullable();
            $table->boolean('is_super_admin')->default(false);
            $table->boolean('is_active')->default(true);
            $table->string('employment_status')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('class_levels', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('admissions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('application_number')->unique();
            $table->string('portal_token')->nullable();
            $table->string('first_name')->default('Ada');
            $table->string('last_name')->default('Applicant');
            $table->string('guardian_phone')->nullable();
            $table->string('status')->default('pending');
            $table->date('application_date')->nullable();
            $table->unsignedBigInteger('applying_for_class_level_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('academic_tracks', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('section')->default('general');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
        Schema::create('class_arms', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('academic_track_id')->nullable();
            $table->string('name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('class_level_subjects', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('academic_track_id')->nullable();
            $table->unsignedBigInteger('class_level_id')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('subject_status')->default('compulsory');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('student_subject_selections', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('academic_track_id')->nullable();
            $table->unsignedBigInteger('student_id')->nullable();
            $table->unsignedBigInteger('class_level_id')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('student_transfers', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('from_tenant_id');
            $table->unsignedBigInteger('to_tenant_id');
            $table->unsignedBigInteger('student_id');
            $table->string('student_name')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();
        });

        RateLimiter::clear('unused');
    }

    public function test_school_route_binding_cannot_resolve_foreign_or_system_academic_tracks(): void
    {
        [$school, $user] = $this->school('Alpha School', 'alpha');
        [$foreign] = $this->school('Beta School', 'beta');
        Auth::setUser($user);

        $own = AcademicTrack::create(['tenant_id' => $school->id, 'name' => 'Own', 'slug' => 'own-alpha']);
        $foreignTrack = AcademicTrack::create(['tenant_id' => $foreign->id, 'name' => 'Foreign', 'slug' => 'foreign-beta']);
        $system = AcademicTrack::create(['tenant_id' => null, 'name' => 'System', 'slug' => 'system']);
        $model = new AcademicTrack();

        $this->assertSame($own->id, $model->resolveRouteBinding($own->id)?->id);
        $this->assertNull($model->resolveRouteBinding($foreignTrack->id));
        $this->assertNull($model->resolveRouteBinding($system->id));
    }

    public function test_referenced_academic_track_cannot_be_deleted(): void
    {
        [$school, $user] = $this->school('Reference School', 'reference');
        Auth::setUser($user);
        $track = AcademicTrack::create(['tenant_id' => $school->id, 'name' => 'Science', 'slug' => 'science-reference']);
        DB::table('class_arms')->insert([
            'tenant_id' => $school->id,
            'academic_track_id' => $track->id,
            'name' => 'A',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->expectException(ValidationException::class);
        $track->delete();
    }

    public function test_admission_success_guard_requires_matching_portal_token(): void
    {
        [$school] = $this->school('Portal School', 'portal-school');
        Admission::withoutTenantScope()->create([
            'tenant_id' => $school->id,
            'application_number' => 'APP-PORTAL-001',
            'portal_token' => 'strong-random-token',
            'guardian_phone' => '07012345678',
            'application_date' => today(),
        ]);
        $middleware = app(ProtectPublicTenantPortal::class);

        $valid = $this->portalRequest('GET', 'http://portal-school.educore.test/apply/success/APP-PORTAL-001?token=strong-random-token', 'tenant.host.apply.success', 'APP-PORTAL-001');
        $this->assertSame(200, $middleware->handle($valid, fn () => response('ok'))->getStatusCode());

        $invalid = $this->portalRequest('GET', 'http://portal-school.educore.test/apply/success/APP-PORTAL-001?token=wrong', 'tenant.host.apply.success', 'APP-PORTAL-001');
        $this->expectException(NotFoundHttpException::class);
        $middleware->handle($invalid, fn () => response('unsafe'));
    }

    public function test_public_tenant_submission_guard_rate_limits_repeated_requests(): void
    {
        $this->school('Rate School', 'rate-school');
        $middleware = app(ProtectPublicTenantPortal::class);

        for ($i = 0; $i < 6; $i++) {
            $request = $this->portalRequest('POST', 'http://rate-school.educore.test/apply/submit', 'tenant.host.apply.submit');
            $request->server->set('REMOTE_ADDR', '203.0.113.10');
            $this->assertSame(200, $middleware->handle($request, fn () => response('ok'))->getStatusCode());
        }

        $this->expectException(TooManyRequestsHttpException::class);
        $blocked = $this->portalRequest('POST', 'http://rate-school.educore.test/apply/submit', 'tenant.host.apply.submit');
        $blocked->server->set('REMOTE_ADDR', '203.0.113.10');
        $middleware->handle($blocked, fn () => response('unsafe'));
    }

    public function test_legacy_admission_form_rejects_foreign_tenant_class_level(): void
    {
        [$school, $user] = $this->school('Form School', 'form-school');
        [$foreign] = $this->school('Foreign Form School', 'foreign-form');
        Auth::setUser($user);
        $foreignLevel = DB::table('class_levels')->insertGetId([
            'tenant_id' => $foreign->id,
            'name' => 'Foreign Level',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $request = Request::create('/admissions', 'POST', ['applying_for_class_level_id' => $foreignLevel]);
        $route = new Route(['POST'], 'admissions', fn () => response('ok'));
        $route->name('admissions.store');
        $request->setRouteResolver(fn () => $route);

        $this->expectException(ValidationException::class);
        app(EnforceTenantFormReferences::class)->handle($request, fn () => response('unsafe'));
    }

    public function test_transfer_route_binding_is_limited_to_sender_or_receiver_school(): void
    {
        [$sender, $senderUser] = $this->school('Sender', 'sender');
        [$receiver, $receiverUser] = $this->school('Receiver', 'receiver');
        [$other, $otherUser] = $this->school('Other', 'other');
        $transfer = StudentTransfer::create([
            'from_tenant_id' => $sender->id,
            'to_tenant_id' => $receiver->id,
            'student_id' => 99,
            'student_name' => 'Test Student',
            'status' => 'pending',
        ]);
        $model = new StudentTransfer();

        Auth::setUser($senderUser);
        $this->assertSame($transfer->id, $model->resolveRouteBinding($transfer->id)?->id);
        Auth::setUser($receiverUser);
        $this->assertSame($transfer->id, $model->resolveRouteBinding($transfer->id)?->id);
        Auth::setUser($otherUser);
        $this->assertNull($model->resolveRouteBinding($transfer->id));
    }

    private function school(string $name, string $slug): array
    {
        $tenant = Tenant::create([
            'name' => $name,
            'slug' => $slug,
            'status' => 'active',
            'domain_verified' => false,
        ]);
        $userId = DB::table('users')->insertGetId([
            'tenant_id' => $tenant->id,
            'name' => $name . ' Admin',
            'email' => $slug . '@example.test',
            'role' => 'admin',
            'is_super_admin' => false,
            'is_active' => true,
            'employment_status' => User::STAFF_STATUS_ACTIVE,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$tenant, User::findOrFail($userId)];
    }

    private function portalRequest(string $method, string $url, string $name, ?string $app = null): Request
    {
        $request = Request::create($url, $method);
        $route = new Route([$method], ltrim(parse_url($url, PHP_URL_PATH), '/'), fn () => response('ok'));
        $route->name($name);
        if ($app !== null) {
            $route->setParameter('app', $app);
        }
        $request->setRouteResolver(fn () => $route);

        return $request;
    }
}

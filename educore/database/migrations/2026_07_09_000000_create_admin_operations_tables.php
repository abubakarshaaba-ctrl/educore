<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // ── Discipline / conduct records ────────────────────────────────
        Schema::create('discipline_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('student_id');
            $table->enum('type', ['merit', 'demerit', 'incident', 'suspension']);
            $table->string('category', 120);
            $table->text('description')->nullable();
            $table->integer('points')->default(0);
            $table->date('occurred_at');
            $table->date('suspension_start')->nullable();
            $table->date('suspension_end')->nullable();
            $table->text('action_taken')->nullable();
            $table->enum('status', ['open', 'resolved'])->default('open');
            $table->unsignedBigInteger('recorded_by')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'student_id']);
        });

        // ── Alumni profiles ──────────────────────────────────────────────
        Schema::create('alumni_profiles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('student_id')->unique();
            $table->string('graduation_year', 9)->nullable();
            $table->string('further_institution', 150)->nullable();
            $table->string('occupation', 120)->nullable();
            $table->string('employer', 150)->nullable();
            $table->string('contact_email', 150)->nullable();
            $table->string('contact_phone', 30)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index('tenant_id');
        });

        // ── Certificate issuance audit log ──────────────────────────────
        Schema::create('certificate_issuances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('student_id');
            $table->enum('certificate_type', ['leaving_certificate', 'testimonial', 'transfer_certificate']);
            $table->string('serial_number', 60)->unique();
            $table->unsignedBigInteger('issued_by')->nullable();
            $table->timestamp('issued_at')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'student_id']);
        });

        // ── Scholarships / bursaries / fee waivers ──────────────────────
        Schema::create('scholarships', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('student_id');
            $table->string('name', 150);
            $table->enum('type', ['percentage', 'fixed_amount', 'full_waiver']);
            $table->decimal('value', 12, 2)->default(0);
            $table->unsignedBigInteger('session_id')->nullable();
            $table->unsignedBigInteger('term_id')->nullable();
            $table->text('reason')->nullable();
            $table->enum('status', ['active', 'expired', 'revoked'])->default('active');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->date('starts_at')->nullable();
            $table->date('ends_at')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'student_id']);
        });

        // ── Procurement: vendors + purchase orders ──────────────────────
        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('name', 150);
            $table->string('contact_person', 120)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('address', 255)->nullable();
            $table->string('category', 100)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index('tenant_id');
        });

        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('vendor_id');
            $table->string('po_number', 40)->unique();
            $table->enum('status', ['draft', 'pending_approval', 'approved', 'received', 'cancelled'])->default('draft');
            $table->decimal('total_amount', 14, 2)->default(0);
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('requested_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->date('expected_delivery_date')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'vendor_id']);
        });

        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('purchase_order_id');
            $table->string('item_name', 200);
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('line_total', 14, 2)->default(0);
            $table->timestamps();
            $table->index('purchase_order_id');
        });

        // ── Asset / inventory management ─────────────────────────────────
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('name', 150);
            $table->string('category', 100)->nullable();
            $table->string('serial_number', 100)->nullable();
            $table->string('location', 150)->nullable();
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->date('purchase_date')->nullable();
            $table->decimal('purchase_cost', 12, 2)->nullable();
            $table->enum('condition', ['new', 'good', 'fair', 'poor', 'damaged'])->default('good');
            $table->enum('status', ['in_use', 'in_storage', 'under_repair', 'disposed'])->default('in_use');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index('tenant_id');
        });

        // ── Visitor / gate-pass log ──────────────────────────────────────
        Schema::create('visitor_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('visitor_name', 150);
            $table->string('phone', 30)->nullable();
            $table->string('purpose', 200)->nullable();
            $table->string('host_name', 150)->nullable();
            $table->string('badge_number', 40)->nullable();
            $table->timestamp('check_in_at');
            $table->timestamp('check_out_at')->nullable();
            $table->unsignedBigInteger('recorded_by')->nullable();
            $table->timestamps();
            $table->index('tenant_id');
        });

        // ── External exam body candidate registration ────────────────────
        Schema::create('exam_body_registrations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('student_id');
            $table->enum('exam_body', ['WAEC', 'NECO', 'NABTEB', 'JAMB']);
            $table->string('exam_year', 9);
            $table->string('registration_number', 60)->nullable();
            $table->unsignedBigInteger('session_id')->nullable();
            $table->json('subjects')->nullable();
            $table->enum('status', ['pending', 'registered', 'completed'])->default('pending');
            $table->unsignedBigInteger('registered_by')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_body_registrations');
        Schema::dropIfExists('visitor_logs');
        Schema::dropIfExists('assets');
        Schema::dropIfExists('purchase_order_items');
        Schema::dropIfExists('purchase_orders');
        Schema::dropIfExists('vendors');
        Schema::dropIfExists('scholarships');
        Schema::dropIfExists('certificate_issuances');
        Schema::dropIfExists('alumni_profiles');
        Schema::dropIfExists('discipline_records');
    }
};

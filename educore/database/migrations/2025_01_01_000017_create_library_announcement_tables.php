<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // ── Library ─────────────────────────────────────────────────
        Schema::create('library_books', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('isbn')->nullable();
            $table->string('title');
            $table->string('author');
            $table->string('publisher')->nullable();
            $table->string('edition')->nullable();
            $table->year('year')->nullable();
            $table->string('category'); // fiction, science, religion, mathematics, etc.
            $table->string('location')->nullable(); // shelf/rack reference
            $table->integer('total_copies')->default(1);
            $table->integer('available_copies')->default(1);
            $table->decimal('purchase_price', 10, 2)->nullable();
            $table->enum('condition', ['excellent', 'good', 'fair', 'poor'])->default('good');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index('tenant_id');
            $table->index(['tenant_id', 'category']);
        });

        Schema::create('library_loans', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('book_id');
            $table->unsignedBigInteger('student_id')->nullable();
            $table->unsignedBigInteger('staff_id')->nullable();
            $table->date('issue_date');
            $table->date('due_date');
            $table->date('return_date')->nullable();
            $table->enum('status', ['issued', 'returned', 'overdue', 'lost'])->default('issued');
            $table->decimal('fine_amount', 8, 2)->default(0);
            $table->boolean('fine_paid')->default(false);
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('issued_by')->nullable();
            $table->timestamps();
            $table->index('tenant_id');
        });

        // ── Announcements ───────────────────────────────────────────
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('title');
            $table->text('body');
            $table->string('audience')->default('all');
            // all, staff, students, parents, admin
            $table->enum('priority', ['normal', 'important', 'urgent'])->default('normal');
            $table->date('publish_date');
            $table->date('expire_date')->nullable();
            $table->boolean('is_published')->default(true);
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
            $table->index('tenant_id');
        });

        // ── Parent Messages (parent portal) ─────────────────────────
        Schema::create('parent_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('from_user_id'); // guardian user or staff
            $table->unsignedBigInteger('to_user_id');
            $table->string('subject');
            $table->text('body');
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->index('tenant_id');
            $table->index(['to_user_id', 'is_read']);
        });

        // ── Parent Portal Users ─────────────────────────────────────
        if (!Schema::hasColumn('users', 'is_parent')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('is_parent')->default(false);
            });
        }

        // ── Tenants: add missing columns ────────────────────────────
        Schema::table('tenants', function (Blueprint $table) {
            if (!Schema::hasColumn('tenants', 'motto'))
                $table->string('motto')->nullable();
            if (!Schema::hasColumn('tenants', 'logo_path'))
                $table->string('logo_path')->nullable();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('parent_messages');
        Schema::dropIfExists('announcements');
        Schema::dropIfExists('library_loans');
        Schema::dropIfExists('library_books');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The new "clock in for a friend" flow takes a live photograph of the colleague
     * being clocked in before scanning their QR code, as evidence the right person
     * is physically present. The photo is stored alongside the proxy request record.
     */
    public function up(): void
    {
        if (Schema::hasTable('staff_proxy_requests') && !Schema::hasColumn('staff_proxy_requests', 'friend_photo_path')) {
            Schema::table('staff_proxy_requests', function (Blueprint $table) {
                $table->string('friend_photo_path')->nullable()->after('qr_token');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('staff_proxy_requests') && Schema::hasColumn('staff_proxy_requests', 'friend_photo_path')) {
            Schema::table('staff_proxy_requests', function (Blueprint $table) {
                $table->dropColumn('friend_photo_path');
            });
        }
    }
};

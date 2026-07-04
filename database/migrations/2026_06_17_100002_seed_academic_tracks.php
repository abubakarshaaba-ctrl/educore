<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $now = now();
        DB::table('academic_tracks')->insertOrIgnore([
            // Junior / General tracks
            ['tenant_id'=>null,'name'=>'Primary',    'slug'=>'primary',    'section'=>'junior','is_active'=>1,'sort_order'=>1,'created_at'=>$now,'updated_at'=>$now],
            ['tenant_id'=>null,'name'=>'General',    'slug'=>'general',    'section'=>'general','is_active'=>1,'sort_order'=>2,'created_at'=>$now,'updated_at'=>$now],
            // Senior tracks (WAEC/NECO/JAMB combinations)
            ['tenant_id'=>null,'name'=>'Science',    'slug'=>'science',    'section'=>'senior', 'is_active'=>1,'sort_order'=>3,'created_at'=>$now,'updated_at'=>$now],
            ['tenant_id'=>null,'name'=>'Humanities', 'slug'=>'humanities', 'section'=>'senior', 'is_active'=>1,'sort_order'=>4,'created_at'=>$now,'updated_at'=>$now],
            ['tenant_id'=>null,'name'=>'Business',   'slug'=>'business',   'section'=>'senior', 'is_active'=>1,'sort_order'=>5,'created_at'=>$now,'updated_at'=>$now],
        ]);
    }

    public function down(): void
    {
        DB::table('academic_tracks')->whereNull('tenant_id')->delete();
    }
};

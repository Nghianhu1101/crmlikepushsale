<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('lead_pipeline_stages')
            ->where('code', 'new')
            ->update(['name' => 'Data mới']);
    }

    public function down(): void
    {
        DB::table('lead_pipeline_stages')
            ->where('code', 'new')
            ->where('name', 'Data mới')
            ->update(['name' => 'New']);
    }
};

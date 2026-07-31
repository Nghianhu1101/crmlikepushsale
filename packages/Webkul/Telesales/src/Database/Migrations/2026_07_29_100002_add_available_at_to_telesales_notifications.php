<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('telesales_notifications', function (Blueprint $table) {
            $table->timestamp('available_at')->nullable()->after('body')->index();
        });
    }

    public function down(): void
    {
        Schema::table('telesales_notifications', function (Blueprint $table) {
            $table->dropIndex(['available_at']);
            $table->dropColumn('available_at');
        });
    }
};

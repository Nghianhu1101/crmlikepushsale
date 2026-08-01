<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('telesales_call_histories', function (Blueprint $table) {
            $table->text('marketing_feedback')->nullable()->after('note');
        });
    }

    public function down(): void
    {
        Schema::table('telesales_call_histories', function (Blueprint $table) {
            $table->dropColumn('marketing_feedback');
        });
    }
};

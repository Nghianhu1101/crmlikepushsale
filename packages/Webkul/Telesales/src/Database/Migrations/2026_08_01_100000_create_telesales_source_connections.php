<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telesales_source_connections', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('channel', 30)->default('api');
            $table->unsignedInteger('source_id')->nullable();
            $table->unsignedInteger('group_id')->nullable();
            $table->unsignedInteger('marketing_owner_id')->nullable();
            $table->string('campaign', 150)->nullable();
            $table->char('token_hash', 64)->unique();
            $table->string('token_hint', 30);
            $table->json('field_mapping')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('received_count')->default(0);
            $table->unsignedBigInteger('duplicate_count')->default(0);
            $table->timestamp('last_received_at')->nullable();
            $table->timestamps();

            $table->foreign('source_id')->references('id')->on('lead_sources')->nullOnDelete();
            $table->foreign('group_id')->references('id')->on('groups')->nullOnDelete();
            $table->foreign('marketing_owner_id')->references('id')->on('users')->nullOnDelete();
            $table->index(['marketing_owner_id', 'is_active'], 'tsc_marketing_active_idx');
        });

        Schema::table('telesales_lead_meta', function (Blueprint $table) {
            $table->unsignedBigInteger('incoming_source_id')->nullable()->after('marketing_group_id');
            $table->foreign('incoming_source_id')->references('id')->on('telesales_source_connections')->nullOnDelete();
            $table->index(['incoming_source_id', 'data_received_at'], 'tlm_source_received_idx');
        });
    }

    public function down(): void
    {
        Schema::table('telesales_lead_meta', function (Blueprint $table) {
            $table->dropForeign(['incoming_source_id']);
            $table->dropIndex('tlm_source_received_idx');
            $table->dropColumn('incoming_source_id');
        });

        Schema::dropIfExists('telesales_source_connections');
    }
};

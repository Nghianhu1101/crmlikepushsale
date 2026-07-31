<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telesales_groups', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('group_id')->unique();
            $table->unsignedInteger('source_id')->nullable();
            $table->string('campaign')->nullable();
            $table->boolean('is_default')->default(false);
            $table->unsignedBigInteger('next_position')->default(0);
            $table->timestamps();

            $table->foreign('group_id')->references('id')->on('groups')->cascadeOnDelete();
            $table->foreign('source_id')->references('id')->on('lead_sources')->nullOnDelete();
        });

        Schema::create('telesales_group_members', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('group_id');
            $table->unsignedInteger('user_id');
            $table->boolean('receives_data')->default(true);
            $table->unsignedInteger('position')->default(0);
            $table->unsignedBigInteger('assigned_count')->default(0);
            $table->timestamp('last_assigned_at')->nullable();
            $table->timestamps();

            $table->unique(['group_id', 'user_id']);
            $table->foreign('group_id')->references('id')->on('groups')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::create('telesales_lead_meta', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('lead_id')->unique();
            $table->string('external_id')->nullable()->unique();
            $table->string('campaign')->nullable();
            $table->string('product_interest')->nullable();
            $table->text('initial_message')->nullable();
            $table->string('allocation_status')->default('unassigned');
            $table->unsignedInteger('group_id')->nullable();
            $table->unsignedInteger('assigned_user_id')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('lead_id')->references('id')->on('leads')->cascadeOnDelete();
            $table->foreign('group_id')->references('id')->on('groups')->nullOnDelete();
            $table->foreign('assigned_user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('telesales_assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('lead_id');
            $table->unsignedInteger('group_id')->nullable();
            $table->unsignedInteger('user_id')->nullable();
            $table->unsignedInteger('source_id')->nullable();
            $table->timestamp('assigned_at');
            $table->timestamps();

            $table->foreign('lead_id')->references('id')->on('leads')->cascadeOnDelete();
            $table->foreign('group_id')->references('id')->on('groups')->nullOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('source_id')->references('id')->on('lead_sources')->nullOnDelete();
        });

        Schema::create('telesales_call_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('lead_id');
            $table->unsignedInteger('user_id');
            $table->string('result');
            $table->text('note')->nullable();
            $table->timestamp('callback_at')->nullable();
            $table->timestamps();

            $table->foreign('lead_id')->references('id')->on('leads')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::create('telesales_notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('lead_id')->nullable();
            $table->string('title');
            $table->text('body');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'read_at']);
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('lead_id')->references('id')->on('leads')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telesales_notifications');
        Schema::dropIfExists('telesales_call_histories');
        Schema::dropIfExists('telesales_assignments');
        Schema::dropIfExists('telesales_lead_meta');
        Schema::dropIfExists('telesales_group_members');
        Schema::dropIfExists('telesales_groups');
    }
};

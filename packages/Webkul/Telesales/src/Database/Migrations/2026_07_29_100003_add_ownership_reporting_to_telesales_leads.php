<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $unknownMarketingGroupId = DB::table('groups')
            ->where('name', 'Chưa xác định Marketing')
            ->value('id');

        if (! $unknownMarketingGroupId) {
            $unknownMarketingGroupId = DB::table('groups')->insertGetId([
                'name' => 'Chưa xác định Marketing',
                'description' => 'Data chưa ánh xạ được tài khoản Marketing.',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Schema::table('telesales_lead_meta', function (Blueprint $table) {
            $table->unsignedInteger('marketing_owner_id')->nullable()->after('created_by');
            $table->unsignedInteger('sales_owner_id')->nullable()->after('marketing_owner_id');
            $table->unsignedInteger('marketing_group_id')->nullable()->after('sales_owner_id');
            $table->string('marketing_external_id')->nullable()->after('external_id');
            $table->timestamp('data_received_at')->nullable()->after('allocation_status');
            $table->timestamp('assigned_at')->nullable()->after('data_received_at');

            $table->foreign('marketing_owner_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('sales_owner_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('marketing_group_id')->references('id')->on('groups')->nullOnDelete();

            $table->index(['marketing_owner_id', 'data_received_at'], 'tlm_marketing_received_idx');
            $table->index(['sales_owner_id', 'data_received_at'], 'tlm_sales_received_idx');
            $table->index(['group_id', 'data_received_at'], 'tlm_group_received_idx');
            $table->index(['campaign', 'data_received_at'], 'tlm_campaign_received_idx');
            $table->index('marketing_external_id', 'tlm_marketing_external_idx');
        });

        DB::table('telesales_lead_meta')->update([
            'sales_owner_id' => DB::raw('assigned_user_id'),
            'marketing_group_id' => $unknownMarketingGroupId,
            'data_received_at' => DB::raw('created_at'),
            'assigned_at' => DB::raw('created_at'),
        ]);

        DB::statement("
            UPDATE telesales_lead_meta lm
            INNER JOIN users u ON u.id = lm.created_by
            INNER JOIN roles r ON r.id = u.role_id
            SET lm.marketing_owner_id = lm.created_by,
                lm.marketing_group_id = NULL
            WHERE LOWER(r.name) LIKE '%marketing%'
        ");

        Schema::create('telesales_marketing_mappings', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id');
            $table->string('marketing_external_id')->nullable()->unique();
            $table->string('campaign')->nullable()->index();
            $table->unsignedInteger('source_id')->nullable()->index();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('source_id')->references('id')->on('lead_sources')->nullOnDelete();
        });

        Schema::create('telesales_ownership_audits', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('lead_id');
            $table->unsignedInteger('actor_id')->nullable();
            $table->string('owner_type');
            $table->unsignedInteger('old_user_id')->nullable();
            $table->unsignedInteger('new_user_id')->nullable();
            $table->string('reason')->nullable();
            $table->timestamp('changed_at');
            $table->timestamps();

            $table->index(['lead_id', 'changed_at']);
            $table->foreign('lead_id')->references('id')->on('leads')->cascadeOnDelete();
            $table->foreign('actor_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('old_user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('new_user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telesales_ownership_audits');
        Schema::dropIfExists('telesales_marketing_mappings');

        Schema::table('telesales_lead_meta', function (Blueprint $table) {
            $table->dropForeign(['marketing_owner_id']);
            $table->dropForeign(['sales_owner_id']);
            $table->dropForeign(['marketing_group_id']);
            $table->dropIndex('tlm_marketing_received_idx');
            $table->dropIndex('tlm_sales_received_idx');
            $table->dropIndex('tlm_group_received_idx');
            $table->dropIndex('tlm_campaign_received_idx');
            $table->dropIndex('tlm_marketing_external_idx');
            $table->dropColumn([
                'marketing_owner_id',
                'sales_owner_id',
                'marketing_group_id',
                'marketing_external_id',
                'data_received_at',
                'assigned_at',
            ]);
        });
    }
};

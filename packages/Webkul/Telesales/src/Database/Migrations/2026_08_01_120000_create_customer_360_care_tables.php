<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telesales_customer_profiles', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('person_id')->unique();
            $table->string('customer_status', 20)->default('new');
            $table->unsignedInteger('successful_order_count')->default(0);
            $table->unsignedInteger('repeat_order_count')->default(0);
            $table->decimal('total_revenue', 18, 0)->default(0);
            $table->unsignedInteger('last_lead_id')->nullable();
            $table->unsignedBigInteger('last_order_id')->nullable();
            $table->unsignedInteger('last_marketing_owner_id')->nullable();
            $table->unsignedInteger('last_sales_owner_id')->nullable();
            $table->unsignedInteger('current_care_owner_id')->nullable();
            $table->timestamp('first_successful_order_at')->nullable();
            $table->timestamp('last_successful_order_at')->nullable();
            $table->timestamps();

            $table->foreign('person_id')->references('id')->on('persons')->cascadeOnDelete();
            $table->foreign('last_lead_id')->references('id')->on('leads')->nullOnDelete();
            $table->foreign('last_order_id')->references('id')->on('telesales_orders')->nullOnDelete();
            $table->foreign('last_marketing_owner_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('last_sales_owner_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('current_care_owner_id')->references('id')->on('users')->nullOnDelete();
            $table->index(['customer_status', 'last_successful_order_at'], 'tcp_status_last_order_idx');
            $table->index(['current_care_owner_id', 'customer_status'], 'tcp_care_status_idx');
        });

        Schema::create('telesales_customer_care_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedInteger('group_id')->nullable();
            $table->unsignedInteger('product_id')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->string('status', 20)->default('active');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();

            $table->foreign('group_id')->references('id')->on('groups')->nullOnDelete();
            $table->foreign('product_id')->references('id')->on('products')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->index(['status', 'starts_at'], 'tccc_status_start_idx');
        });

        Schema::create('telesales_customer_care_cases', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('person_id');
            $table->unsignedInteger('lead_id')->nullable();
            $table->unsignedBigInteger('origin_order_id')->nullable();
            $table->unsignedBigInteger('campaign_id')->nullable();
            $table->unsignedInteger('group_id')->nullable();
            $table->unsignedInteger('marketing_owner_id')->nullable();
            $table->unsignedInteger('sales_owner_id')->nullable();
            $table->unsignedInteger('care_owner_id')->nullable();
            $table->string('case_type', 30)->default('post_sale');
            $table->string('status', 30)->default('pending');
            $table->string('result', 30)->nullable();
            $table->string('product_interest')->nullable();
            $table->text('message')->nullable();
            $table->timestamp('data_received_at')->nullable();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('callback_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->foreign('person_id')->references('id')->on('persons')->cascadeOnDelete();
            $table->foreign('lead_id')->references('id')->on('leads')->nullOnDelete();
            $table->foreign('origin_order_id')->references('id')->on('telesales_orders')->nullOnDelete();
            $table->foreign('campaign_id')->references('id')->on('telesales_customer_care_campaigns')->nullOnDelete();
            $table->foreign('group_id')->references('id')->on('groups')->nullOnDelete();
            $table->foreign('marketing_owner_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('sales_owner_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('care_owner_id')->references('id')->on('users')->nullOnDelete();
            $table->index(['care_owner_id', 'status', 'callback_at'], 'tccc_owner_status_callback_idx');
            $table->index(['person_id', 'status'], 'tccc_person_status_idx');
            $table->index(['campaign_id', 'status'], 'tccc_campaign_status_idx');
        });

        Schema::create('telesales_customer_care_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('care_case_id');
            $table->unsignedInteger('user_id');
            $table->string('result', 30);
            $table->text('note')->nullable();
            $table->text('marketing_feedback')->nullable();
            $table->timestamp('callback_at')->nullable();
            $table->timestamps();

            $table->foreign('care_case_id')->references('id')->on('telesales_customer_care_cases')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->index(['care_case_id', 'created_at'], 'tcch_case_created_idx');
        });

        Schema::table('telesales_groups', function (Blueprint $table) {
            $table->string('department', 30)->default('sales')->after('campaign');
            $table->index(['department', 'is_default'], 'tg_department_default_idx');
        });

        Schema::table('telesales_lead_meta', function (Blueprint $table) {
            $table->string('customer_type', 20)->default('new')->after('incoming_source_id');
            $table->unsignedInteger('customer_care_owner_id')->nullable()->after('sales_owner_id');
            $table->unsignedInteger('customer_care_group_id')->nullable()->after('customer_care_owner_id');
            $table->unsignedBigInteger('customer_care_case_id')->nullable()->after('customer_care_group_id');

            $table->foreign('customer_care_owner_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('customer_care_group_id')->references('id')->on('groups')->nullOnDelete();
            $table->foreign('customer_care_case_id')->references('id')->on('telesales_customer_care_cases')->nullOnDelete();
            $table->index(['customer_type', 'marketing_owner_id', 'data_received_at'], 'tlm_type_marketing_received_idx');
            $table->index(['customer_care_owner_id', 'data_received_at'], 'tlm_care_received_idx');
        });

        Schema::table('telesales_notifications', function (Blueprint $table) {
            $table->unsignedBigInteger('customer_care_case_id')->nullable()->after('lead_id');
            $table->foreign('customer_care_case_id')->references('id')->on('telesales_customer_care_cases')->cascadeOnDelete();
        });

        Schema::table('telesales_orders', function (Blueprint $table) {
            $table->unsignedInteger('customer_care_owner_id')->nullable()->after('marketing_owner_id');
            $table->unsignedBigInteger('customer_care_case_id')->nullable()->after('customer_care_owner_id');
            $table->foreign('customer_care_owner_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('customer_care_case_id')->references('id')->on('telesales_customer_care_cases')->nullOnDelete();
            $table->index(['customer_care_owner_id', 'closed_at'], 'torders_care_closed_idx');
        });

        DB::table('telesales_customer_profiles')->insertUsing(
            ['person_id', 'customer_status', 'created_at', 'updated_at'],
            DB::table('persons')->selectRaw("id, 'new', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP")
        );

        DB::table('telesales_orders')
            ->selectRaw('person_id, COUNT(*) AS order_count, SUM(net_amount) AS revenue, MIN(closed_at) AS first_at, MAX(closed_at) AS last_at, MAX(id) AS last_order_id')
            ->whereIn('status', ['confirmed', 'shipping', 'delivered'])
            ->whereNotIn('delivery_status', ['returned', 'cancelled'])
            ->groupBy('person_id')
            ->orderBy('person_id')
            ->each(function ($aggregate): void {
                DB::table('telesales_customer_profiles')
                    ->where('person_id', $aggregate->person_id)
                    ->update([
                        'customer_status' => 'old',
                        'successful_order_count' => $aggregate->order_count,
                        'repeat_order_count' => max(0, $aggregate->order_count - 1),
                        'total_revenue' => $aggregate->revenue,
                        'last_order_id' => $aggregate->last_order_id,
                        'first_successful_order_at' => $aggregate->first_at,
                        'last_successful_order_at' => $aggregate->last_at,
                        'updated_at' => now(),
                    ]);
            });

        DB::table('leads')
            ->join('telesales_lead_meta', 'telesales_lead_meta.lead_id', '=', 'leads.id')
            ->selectRaw('leads.person_id, MAX(telesales_lead_meta.id) AS meta_id')
            ->whereNotNull('leads.person_id')
            ->groupBy('leads.person_id')
            ->orderBy('leads.person_id')
            ->each(function ($latest): void {
                $meta = DB::table('telesales_lead_meta')->where('id', $latest->meta_id)->first();

                DB::table('telesales_customer_profiles')
                    ->where('person_id', $latest->person_id)
                    ->update([
                        'last_lead_id' => $meta->lead_id,
                        'last_marketing_owner_id' => $meta->marketing_owner_id,
                        'last_sales_owner_id' => $meta->sales_owner_id,
                        'updated_at' => now(),
                    ]);
            });
    }

    public function down(): void
    {
        Schema::table('telesales_orders', function (Blueprint $table) {
            $table->dropForeign(['customer_care_owner_id']);
            $table->dropForeign(['customer_care_case_id']);
            $table->dropIndex('torders_care_closed_idx');
            $table->dropColumn(['customer_care_owner_id', 'customer_care_case_id']);
        });

        Schema::table('telesales_notifications', function (Blueprint $table) {
            $table->dropForeign(['customer_care_case_id']);
            $table->dropColumn('customer_care_case_id');
        });

        Schema::table('telesales_lead_meta', function (Blueprint $table) {
            $table->dropForeign(['customer_care_owner_id']);
            $table->dropForeign(['customer_care_group_id']);
            $table->dropForeign(['customer_care_case_id']);
            $table->dropIndex('tlm_type_marketing_received_idx');
            $table->dropIndex('tlm_care_received_idx');
            $table->dropColumn([
                'customer_type',
                'customer_care_owner_id',
                'customer_care_group_id',
                'customer_care_case_id',
            ]);
        });

        Schema::table('telesales_groups', function (Blueprint $table) {
            $table->dropIndex('tg_department_default_idx');
            $table->dropColumn('department');
        });

        Schema::dropIfExists('telesales_customer_care_histories');
        Schema::dropIfExists('telesales_customer_care_cases');
        Schema::dropIfExists('telesales_customer_care_campaigns');
        Schema::dropIfExists('telesales_customer_profiles');
    }
};

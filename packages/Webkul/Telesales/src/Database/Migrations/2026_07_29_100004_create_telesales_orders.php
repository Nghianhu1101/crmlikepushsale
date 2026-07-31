<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telesales_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->unsignedInteger('lead_id');
            $table->unsignedInteger('person_id');
            $table->unsignedInteger('sales_owner_id')->nullable();
            $table->unsignedInteger('marketing_owner_id')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->string('customer_type')->default('new');
            $table->string('status')->default('draft');
            $table->string('delivery_status')->default('pending');
            $table->decimal('gross_amount', 18, 0)->default(0);
            $table->decimal('discount_amount', 18, 0)->default(0);
            $table->decimal('shipping_fee', 18, 0)->default(0);
            $table->decimal('deposit_amount', 18, 0)->default(0);
            $table->decimal('net_amount', 18, 0)->default(0);
            $table->timestamp('closed_at')->nullable();
            $table->timestamp('revenue_confirmed_at')->nullable();
            $table->timestamps();

            $table->foreign('lead_id')->references('id')->on('leads')->cascadeOnDelete();
            $table->foreign('person_id')->references('id')->on('persons')->cascadeOnDelete();
            $table->foreign('sales_owner_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('marketing_owner_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();

            $table->index(['status', 'revenue_confirmed_at'], 'torders_status_revenue_idx');
            $table->index(['sales_owner_id', 'closed_at'], 'torders_sales_closed_idx');
            $table->index(['marketing_owner_id', 'closed_at'], 'torders_marketing_closed_idx');
            $table->index(['lead_id', 'closed_at'], 'torders_lead_closed_idx');
        });

        Schema::create('telesales_order_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedInteger('product_id')->nullable();
            $table->string('product_name');
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('unit_price', 18, 0)->default(0);
            $table->decimal('line_total', 18, 0)->default(0);
            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('telesales_orders')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('products')->nullOnDelete();
            $table->index(['product_id', 'order_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telesales_order_items');
        Schema::dropIfExists('telesales_orders');
    }
};

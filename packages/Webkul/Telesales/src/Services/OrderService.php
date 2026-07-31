<?php

namespace Webkul\Telesales\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Webkul\Lead\Models\Lead;
use Webkul\Product\Models\Product;
use Webkul\Telesales\Models\LeadMeta;
use Webkul\Telesales\Models\Order;
use Webkul\Telesales\Models\OrderItem;

class OrderService
{
    public function create(Lead $lead, array $data, int $actorId): Order
    {
        return DB::transaction(function () use ($lead, $data, $actorId) {
            $lead = Lead::query()->lockForUpdate()->findOrFail($lead->id);
            $meta = LeadMeta::query()->where('lead_id', $lead->id)->lockForUpdate()->first();
            $product = Product::query()->findOrFail($data['product_id']);

            $quantity = (int) $data['quantity'];
            $unitPrice = (int) $data['unit_price'];
            $grossAmount = $quantity * $unitPrice;
            $discountAmount = min((int) ($data['discount_amount'] ?? 0), $grossAmount);
            $netAmount = $grossAmount - $discountAmount;
            $status = $data['status'] ?? 'confirmed';
            $deliveryStatus = $data['delivery_status'] ?? 'pending';

            if (in_array($deliveryStatus, ['returned', 'cancelled'], true)) {
                $status = 'returned';
            }

            $isRevenueStatus = in_array($status, config('telesales.revenue_statuses'), true);
            $hasPreviousOrder = Order::query()
                ->where('person_id', $lead->person_id)
                ->whereNotIn('status', ['draft', 'returned'])
                ->exists();

            $order = Order::query()->create([
                'order_number' => 'TS-'.now()->format('YmdHis').'-'.Str::upper(Str::random(5)),
                'lead_id' => $lead->id,
                'person_id' => $lead->person_id,
                'sales_owner_id' => $meta?->sales_owner_id ?: $lead->user_id,
                'marketing_owner_id' => $meta?->marketing_owner_id,
                'created_by' => $actorId,
                'customer_type' => $hasPreviousOrder ? 'old' : 'new',
                'status' => $status,
                'delivery_status' => $deliveryStatus,
                'gross_amount' => $grossAmount,
                'discount_amount' => $discountAmount,
                'shipping_fee' => (int) ($data['shipping_fee'] ?? 0),
                'deposit_amount' => (int) ($data['deposit_amount'] ?? 0),
                'net_amount' => $netAmount,
                'closed_at' => $status === 'draft' ? null : now(),
                'revenue_confirmed_at' => $isRevenueStatus ? now() : null,
            ]);

            OrderItem::query()->create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'product_name' => $product->name ?: $product->sku,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'line_total' => $grossAmount,
            ]);

            if (in_array($status, ['closed', ...config('telesales.revenue_statuses')], true)) {
                $wonStage = $lead->pipeline->stages()->where('code', 'won')->first();

                if ($wonStage) {
                    $lead->update([
                        'lead_pipeline_stage_id' => $wonStage->id,
                        'closed_at' => now(),
                    ]);
                }
            }

            return $order->load(['items', 'lead.person', 'salesOwner', 'marketingOwner']);
        }, 3);
    }

    public function updateStatus(Order $order, string $status, string $deliveryStatus): Order
    {
        return DB::transaction(function () use ($order, $status, $deliveryStatus) {
            $order = Order::query()->lockForUpdate()->findOrFail($order->id);
            $status = in_array($deliveryStatus, ['returned', 'cancelled'], true)
                ? 'returned'
                : $status;
            $isRevenueStatus = in_array($status, config('telesales.revenue_statuses'), true);

            $order->update([
                'status' => $status,
                'delivery_status' => $deliveryStatus,
                'closed_at' => $status === 'draft' ? null : ($order->closed_at ?: now()),
                'revenue_confirmed_at' => $isRevenueStatus
                    ? ($order->revenue_confirmed_at ?: now())
                    : $order->revenue_confirmed_at,
            ]);

            return $order->fresh();
        }, 3);
    }
}

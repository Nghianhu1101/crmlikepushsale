<?php

namespace Webkul\Telesales\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Webkul\Lead\Models\Lead;
use Webkul\Telesales\Models\Order;
use Webkul\Telesales\Services\OrderService;
use Webkul\Telesales\Services\TelesalesAccessService;

class OrderController extends Controller
{
    public function __construct(
        protected OrderService $orderService,
        protected TelesalesAccessService $accessService
    ) {}

    public function index(): View
    {
        $user = auth()->guard('user')->user();
        $role = $this->accessService->role($user);
        $query = Order::query()->with(['lead.person', 'items', 'salesOwner', 'marketingOwner', 'careOwner'])->latest();

        if ($role === 'marketing') {
            $query->where('marketing_owner_id', $user->id);
        } elseif ($role === 'sale') {
            $query->where('sales_owner_id', $user->id);
        } elseif ($role === 'customer_care') {
            $query->where('customer_care_owner_id', $user->id);
        }

        if ($status = request('status')) {
            $query->where('status', $status);
        }

        return view('telesales::orders', [
            'orders' => $query->paginate(20)->withQueryString(),
            'role' => $role,
        ]);
    }

    public function store(Lead $lead): RedirectResponse
    {
        $this->authorizeLead($lead);

        $data = request()->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:100000'],
            'unit_price' => ['required', 'integer', 'min:0', 'max:999999999999'],
            'discount_amount' => ['nullable', 'integer', 'min:0', 'max:999999999999'],
            'shipping_fee' => ['nullable', 'integer', 'min:0', 'max:999999999999'],
            'deposit_amount' => ['nullable', 'integer', 'min:0', 'max:999999999999'],
            'status' => ['required', Rule::in(array_keys(config('telesales.order_statuses')))],
            'delivery_status' => ['required', Rule::in(array_keys(config('telesales.delivery_statuses')))],
        ]);

        $order = $this->orderService->create(
            $lead,
            $data,
            auth()->guard('user')->id()
        );

        return redirect()
            ->route('admin.leads.view', $lead->id)
            ->with('success', 'Đã tạo đơn '.$order->order_number.'.');
    }

    public function updateStatus(Order $order): RedirectResponse
    {
        $this->authorizeOrder($order);

        $data = request()->validate([
            'status' => ['required', Rule::in(array_keys(config('telesales.order_statuses')))],
            'delivery_status' => ['required', Rule::in(array_keys(config('telesales.delivery_statuses')))],
        ]);

        $this->orderService->updateStatus($order, $data['status'], $data['delivery_status']);

        return back()->with('success', 'Đã cập nhật trạng thái đơn hàng.');
    }

    private function authorizeLead(Lead $lead): void
    {
        $authorizedIds = bouncer()->getAuthorizedUserIds();

        if ($authorizedIds !== null && ! in_array($lead->user_id, $authorizedIds)) {
            abort(403);
        }
    }

    private function authorizeOrder(Order $order): void
    {
        $user = auth()->guard('user')->user();
        $role = $this->accessService->role($user);

        if ($role === 'admin') {
            return;
        }

        if ($role === 'sale' && $order->sales_owner_id === $user->id) {
            return;
        }

        if ($role === 'customer_care' && $order->customer_care_owner_id === $user->id) {
            return;
        }

        abort(403);
    }
}

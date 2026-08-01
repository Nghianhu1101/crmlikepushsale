<?php

namespace Webkul\Telesales\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Webkul\Product\Models\Product;
use Webkul\Telesales\Models\CustomerCareCase;
use Webkul\Telesales\Services\CustomerCareService;
use Webkul\Telesales\Services\OrderService;
use Webkul\Telesales\Services\TelesalesAccessService;

class CustomerCareCaseController extends Controller
{
    public function __construct(
        protected CustomerCareService $customerCareService,
        protected OrderService $orderService,
        protected TelesalesAccessService $accessService
    ) {}

    public function index(): View
    {
        $user = auth()->guard('user')->user();
        $role = $this->accessService->role($user);
        abort_unless(in_array($role, ['admin', 'customer_care'], true), 403);
        $query = CustomerCareCase::query()->with([
            'person',
            'campaign',
            'marketingOwner',
            'salesOwner',
            'careOwner',
            'histories' => fn ($history) => $history->latest(),
        ]);

        if ($role === 'customer_care') {
            $query->where('care_owner_id', $user->id);
        }

        if ($search = trim((string) request('search'))) {
            $query->whereHas('person', fn (Builder $person) => $person
                ->where('name', 'like', "%{$search}%")
                ->orWhere('normalized_phone', 'like', "%{$search}%"));
        }

        if (request()->filled('status')) {
            $query->where('status', request('status'));
        }

        return view('telesales::customer-care.cases', [
            'cases' => $query->orderByDesc('data_received_at')->paginate(25)->withQueryString(),
            'role' => $role,
        ]);
    }

    public function show(CustomerCareCase $careCase): View
    {
        $this->authorizeCase($careCase);

        return view('telesales::customer-care.show', [
            'careCase' => $careCase->load([
                'person',
                'lead.source',
                'campaign',
                'marketingOwner',
                'salesOwner',
                'careOwner',
                'histories.user',
            ]),
            'products' => Product::query()->orderBy('name')->get(['id', 'name', 'sku', 'price']),
        ]);
    }

    public function outcome(CustomerCareCase $careCase): RedirectResponse
    {
        $this->authorizeCase($careCase);
        $data = request()->validate([
            'result' => ['required', Rule::in(array_keys(config('telesales.call_results')))],
            'note' => ['nullable', 'string', 'max:5000'],
            'marketing_feedback' => ['nullable', 'string', 'max:5000'],
            'callback_at' => ['required_if:result,callback', 'nullable', 'date', 'after:now'],
        ]);
        $this->customerCareService->recordResult(
            $careCase,
            $data,
            auth()->guard('user')->id()
        );

        return back()->with('success', 'Đã lưu kết quả chăm sóc khách hàng cũ.');
    }

    public function order(CustomerCareCase $careCase): RedirectResponse
    {
        $this->authorizeCase($careCase);
        abort_unless($careCase->lead_id, 422, 'Khách hàng chưa có Lead để tạo đơn mua lại.');
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
        $order = $this->orderService->create($careCase->lead, [
            ...$data,
            'customer_care_owner_id' => $careCase->care_owner_id,
            'customer_care_case_id' => $careCase->id,
        ], auth()->guard('user')->id());

        return back()->with('success', 'Đã tạo đơn mua lại '.$order->order_number.'.');
    }

    private function authorizeCase(CustomerCareCase $careCase): void
    {
        $user = auth()->guard('user')->user();
        $role = $this->accessService->role($user);

        abort_unless(
            $role === 'admin'
                || ($role === 'customer_care' && $careCase->care_owner_id === $user->id),
            403
        );
    }
}

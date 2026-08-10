@php
    $meta = \Webkul\Telesales\Models\LeadMeta::query()->where('lead_id', $lead->id)->first();
    $histories = \Webkul\Telesales\Models\CallHistory::query()
        ->with('user')
        ->where('lead_id', $lead->id)
        ->latest()
        ->get();
    $phone = data_get($lead->person?->contact_numbers, '0.value');
    $customerProfile = $lead->person_id
        ? \Webkul\Telesales\Models\CustomerProfile::query()->where('person_id', $lead->person_id)->first()
        : null;
    $telesalesProducts = \Webkul\Product\Models\Product::query()
        ->orderBy('name')
        ->get(['id', 'name', 'sku', 'price']);
    $telesalesOrders = \Webkul\Telesales\Models\Order::query()
        ->with('items')
        ->where('lead_id', $lead->id)
        ->latest()
        ->get();
    $isTelesalesAdmin = auth()->guard('user')->user()?->role?->permission_type === 'all';
    $activeTelesalesUsers = $isTelesalesAdmin
        ? \Webkul\User\Models\User::query()->with('role')->where('status', true)->orderBy('name')->get()
        : collect();
@endphp

<div class="rounded-lg border border-gray-300 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
    <div class="grid gap-4 md:grid-cols-2">
        <div class="space-y-2 text-sm dark:text-white">
            <h2 class="text-lg font-semibold">{{ $lead->person?->name }}</h2>

            @if ($meta?->customer_type === 'old' || $customerProfile?->customer_status === 'old')
                <div
                    class="inline-flex items-center gap-2 text-sm font-bold text-red-500"
                    data-customer-type="old"
                    aria-label="Khách hàng cũ"
                    title="Khách hàng cũ"
                >
                    <span class="text-xl leading-none" role="img" aria-hidden="true">♥</span>
                    Khách hàng cũ
                </div>

                @if ($meta?->customer_care_case_id)
                    <p><span class="font-medium">CSKH phụ trách:</span> {{ $meta?->careOwner?->name ?: 'Chưa phân bổ' }}</p>
                @endif
            @endif

            <p>
                <span class="font-medium">Số điện thoại:</span>
                <a class="text-brandColor" href="tel:{{ $phone }}">{{ $phone }}</a>
            </p>

            <p><span class="font-medium">Nguồn:</span> {{ $lead->source?->name ?: 'Chưa xác định' }}</p>
            <p><span class="font-medium">Sản phẩm:</span> {{ $meta?->product_interest ?: $lead->products->pluck('name')->join(', ') ?: 'Chưa chọn' }}</p>
            <p><span class="font-medium">Data về:</span> {{ $lead->created_at?->format('d/m/Y H:i') }}</p>
            <p><span class="font-medium">Nội dung:</span> {{ $meta?->initial_message ?: $lead->description ?: 'Không có' }}</p>

            @if ($phone)
                <a href="tel:{{ $phone }}" class="primary-button inline-flex">Gọi ngay</a>
            @endif
        </div>

        <x-admin::form :action="route('admin.telesales.outcomes.store', $lead->id)">
            <div class="grid gap-3">
                <x-admin::form.control-group>
                    <x-admin::form.control-group.label class="required">Kết quả cuộc gọi</x-admin::form.control-group.label>
                    <x-admin::form.control-group.control
                        type="select"
                        name="result"
                        rules="required"
                        label="Kết quả cuộc gọi"
                    >
                        @foreach (config('telesales.call_results') as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </x-admin::form.control-group.control>
                </x-admin::form.control-group>

                <x-admin::form.control-group>
                    <x-admin::form.control-group.label>Ghi chú nội bộ Sale</x-admin::form.control-group.label>
                    <x-admin::form.control-group.control
                        type="textarea"
                        name="note"
                        rows="3"
                        label="Ghi chú nội bộ Sale"
                    />
                </x-admin::form.control-group>

                <x-admin::form.control-group>
                    <x-admin::form.control-group.label>Phản hồi cho Marketing</x-admin::form.control-group.label>
                    <x-admin::form.control-group.control
                        type="textarea"
                        name="marketing_feedback"
                        rows="3"
                        label="Phản hồi cho Marketing"
                        placeholder="Thông tin Sale muốn Marketing theo dõi: nhu cầu, tình trạng khách, chất lượng data..."
                    />
                    <p class="mt-1 text-xs text-gray-500">Nội dung này sẽ xuất hiện trong Hồ sơ khách hàng Marketing.</p>
                </x-admin::form.control-group>

                <x-admin::form.control-group>
                    <x-admin::form.control-group.label>Thời gian hẹn gọi lại</x-admin::form.control-group.label>
                    <x-admin::form.control-group.control
                        type="datetime-local"
                        name="callback_at"
                        label="Thời gian hẹn gọi lại"
                    />
                </x-admin::form.control-group>

                <button type="submit" class="primary-button">Lưu kết quả</button>
            </div>
        </x-admin::form>
    </div>

    @if (bouncer()->hasPermission('leads.edit'))
        <div class="mt-5 border-t border-gray-200 pt-4 dark:border-gray-800">
            @if ($isTelesalesAdmin)
                <form
                    method="POST"
                    action="{{ route('admin.telesales.owners.update', $lead->id) }}"
                    class="mb-4 grid gap-3 rounded-lg border border-gray-200 p-3 dark:border-gray-800 md:grid-cols-3"
                >
                    @csrf
                    @method('PUT')
                    <label class="grid gap-1 text-sm dark:text-white">
                        <span class="font-medium">Sale phụ trách</span>
                        <select name="sales_owner_id" class="rounded-md border px-3 py-2 dark:border-gray-800 dark:bg-gray-900">
                            <option value="">Chưa phân bổ</option>
                            @foreach ($activeTelesalesUsers->reject(fn ($user) => str_contains(mb_strtolower((string) $user->role?->name), 'marketing')) as $user)
                                <option value="{{ $user->id }}" @selected($meta?->sales_owner_id === $user->id)>{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="grid gap-1 text-sm dark:text-white">
                        <span class="font-medium">Marketing sở hữu</span>
                        <select name="marketing_owner_id" class="rounded-md border px-3 py-2 dark:border-gray-800 dark:bg-gray-900">
                            <option value="">Chưa xác định</option>
                            @foreach ($activeTelesalesUsers->filter(fn ($user) => str_contains(mb_strtolower((string) $user->role?->name), 'marketing')) as $user)
                                <option value="{{ $user->id }}" @selected($meta?->marketing_owner_id === $user->id)>{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <div class="flex items-end">
                        <button class="secondary-button">Cập nhật phụ trách</button>
                    </div>
                </form>
            @endif

            <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                <div>
                    <h3 class="font-semibold dark:text-white">Đơn hàng và doanh thu</h3>
                    <p class="text-sm text-gray-500">Doanh thu chỉ ghi nhận từ đơn có trạng thái hợp lệ.</p>
                </div>
                <a href="{{ route('admin.telesales.orders.index') }}" class="secondary-button">Xem tất cả đơn</a>
            </div>

            <form
                method="POST"
                action="{{ route('admin.telesales.orders.store', $lead->id) }}"
                class="grid gap-3 rounded-lg bg-gray-50 p-3 dark:bg-gray-950 md:grid-cols-3"
            >
                @csrf

                <label class="grid gap-1 text-sm dark:text-white md:col-span-2">
                    <span class="font-medium">Sản phẩm</span>
                    <select name="product_id" required class="rounded-md border px-3 py-2 dark:border-gray-800 dark:bg-gray-900">
                        <option value="">Chọn sản phẩm</option>
                        @foreach ($telesalesProducts as $product)
                            <option value="{{ $product->id }}">
                                {{ $product->name ?: $product->sku }} · {{ number_format((int) $product->price, 0, ',', '.') }} ₫
                            </option>
                        @endforeach
                    </select>
                </label>

                <label class="grid gap-1 text-sm dark:text-white">
                    <span class="font-medium">Số lượng</span>
                    <input type="number" min="1" name="quantity" value="1" required class="rounded-md border px-3 py-2 dark:border-gray-800 dark:bg-gray-900">
                </label>

                <label class="grid gap-1 text-sm dark:text-white">
                    <span class="font-medium">Đơn giá</span>
                    <input type="number" min="0" name="unit_price" required class="rounded-md border px-3 py-2 dark:border-gray-800 dark:bg-gray-900" placeholder="Ví dụ: 560000">
                </label>

                <label class="grid gap-1 text-sm dark:text-white">
                    <span class="font-medium">Chiết khấu</span>
                    <input type="number" min="0" name="discount_amount" value="0" class="rounded-md border px-3 py-2 dark:border-gray-800 dark:bg-gray-900">
                </label>

                <label class="grid gap-1 text-sm dark:text-white">
                    <span class="font-medium">Phí vận chuyển khách trả</span>
                    <input type="number" min="0" name="shipping_fee" value="0" class="rounded-md border px-3 py-2 dark:border-gray-800 dark:bg-gray-900">
                </label>

                <label class="grid gap-1 text-sm dark:text-white">
                    <span class="font-medium">Tiền đặt cọc</span>
                    <input type="number" min="0" name="deposit_amount" value="0" class="rounded-md border px-3 py-2 dark:border-gray-800 dark:bg-gray-900">
                </label>

                <label class="grid gap-1 text-sm dark:text-white">
                    <span class="font-medium">Trạng thái đơn</span>
                    <select name="status" class="rounded-md border px-3 py-2 dark:border-gray-800 dark:bg-gray-900">
                        @foreach (config('telesales.order_statuses') as $value => $label)
                            <option value="{{ $value }}" @selected($value === 'confirmed')>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="grid gap-1 text-sm dark:text-white">
                    <span class="font-medium">Giao hàng</span>
                    <select name="delivery_status" class="rounded-md border px-3 py-2 dark:border-gray-800 dark:bg-gray-900">
                        @foreach (config('telesales.delivery_statuses') as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                <div class="flex items-end md:col-span-3">
                    <button type="submit" class="primary-button">Tạo đơn hàng</button>
                </div>
            </form>

            <div class="mt-3 grid gap-2">
                @foreach ($telesalesOrders as $order)
                    <div class="flex flex-wrap items-center justify-between gap-2 rounded border border-gray-200 p-3 text-sm dark:border-gray-800 dark:text-white">
                        <div>
                            <strong>{{ $order->order_number }}</strong>
                            <span class="ml-2 text-gray-500">{{ $order->items->pluck('product_name')->join(', ') }}</span>
                        </div>
                        <div>
                            {{ config('telesales.order_statuses.'.$order->status) }}
                            · <strong>{{ number_format((int) $order->net_amount, 0, ',', '.') }} ₫</strong>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="mt-5 border-t border-gray-200 pt-4 dark:border-gray-800">
        <h3 class="mb-3 font-semibold dark:text-white">Lịch sử chăm sóc</h3>

        <div class="grid gap-2">
            @forelse ($histories as $history)
                <div class="rounded border border-gray-200 p-3 text-sm dark:border-gray-800 dark:text-white">
                    <div class="flex flex-wrap justify-between gap-2">
                        <strong>{{ config('telesales.call_results.'.$history->result, $history->result) }}</strong>
                        <span>{{ $history->created_at->format('d/m/Y H:i') }} · {{ $history->user?->name }}</span>
                    </div>
                    @if ($history->note)<p class="mt-1">{{ $history->note }}</p>@endif
                    @if ($history->marketing_feedback)
                        <p class="mt-2 rounded bg-blue-50 p-2 text-blue-700 dark:bg-blue-950 dark:text-blue-200">
                            <strong>Đã chia sẻ Marketing:</strong> {{ $history->marketing_feedback }}
                        </p>
                    @endif
                    @if ($history->callback_at)<p class="mt-1 text-brandColor">Hẹn: {{ $history->callback_at->format('d/m/Y H:i') }}</p>@endif
                </div>
            @empty
                <p class="text-sm text-gray-500">Chưa có lịch sử chăm sóc.</p>
            @endforelse
        </div>
    </div>
</div>

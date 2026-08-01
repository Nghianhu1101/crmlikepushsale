<x-admin::layouts>
    <x-slot:title>
        Data {{ $lead->person?->name }}
    </x-slot>

    @php
        $meta = \Webkul\Telesales\Models\LeadMeta::query()
            ->with(['salesOwner', 'latestCallHistory.user', 'latestMarketingFeedback.user', 'latestOrder.items'])
            ->where('lead_id', $lead->id)
            ->first();
        $latestHistory = $meta?->latestCallHistory;
        $latestFeedback = $meta?->latestMarketingFeedback;
        $latestOrder = $meta?->latestOrder;
        $phone = data_get($lead->person?->contact_numbers, '0.value');
        $displayPhone = $phone && strlen($phone) > 7
            ? substr($phone, 0, 4).'***'.substr($phone, -3)
            : $phone;
    @endphp

    <div class="flex flex-col gap-4">
        <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-gray-300 bg-white px-4 py-3 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div>
                <div class="text-xl font-bold dark:text-white">{{ $lead->person?->name }}</div>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                    Theo dõi Sale phụ trách và phản hồi chăm sóc. Ghi chú nội bộ của Sale vẫn được bảo mật.
                </p>
            </div>

            <a href="{{ route('admin.telesales.created-leads.index') }}" class="secondary-button">Data tôi đã nhập</a>
        </div>

        <div class="grid gap-4 rounded-lg border border-gray-300 bg-white p-4 text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-white md:grid-cols-2">
            <p><span class="font-medium">Số điện thoại:</span> {{ $displayPhone ?: 'Chưa có số' }}</p>
            <p><span class="font-medium">Nguồn:</span> {{ $lead->source?->name ?: 'Chưa xác định' }}</p>
            <p><span class="font-medium">Sản phẩm:</span> {{ $meta?->product_interest ?: 'Chưa chọn' }}</p>
            <p><span class="font-medium">Sale phụ trách:</span> {{ $meta?->salesOwner?->name ?: $lead->user?->name ?: 'Chưa phân bổ' }}</p>
            <p><span class="font-medium">Trạng thái:</span> {{ $lead->stage?->name }}</p>
            <p><span class="font-medium">Data về:</span> {{ $lead->created_at?->format('d/m/Y H:i') }}</p>
            <p class="md:col-span-2"><span class="font-medium">Nội dung khách để lại:</span> {{ $meta?->initial_message ?: 'Không có' }}</p>

            <div class="rounded-lg bg-blue-50 p-3 dark:bg-blue-950 md:col-span-2">
                <p class="font-semibold text-blue-700 dark:text-blue-200">Phản hồi gần nhất từ Sale</p>
                @if ($latestHistory)
                    <p class="mt-1">
                        {{ config('telesales.call_results.'.$latestHistory->result, $latestHistory->result) }}
                        · {{ $latestHistory->user?->name }}
                        · {{ $latestHistory->created_at->format('d/m/Y H:i') }}
                    </p>
                    <p class="mt-2 text-orange-600 dark:text-orange-300">
                        {{ $latestFeedback?->marketing_feedback ?: 'Sale chưa gửi nội dung phản hồi cho Marketing.' }}
                    </p>
                @else
                    <p class="mt-1 text-gray-500">Sale chưa tác nghiệp data này.</p>
                @endif
            </div>

            @if ($latestOrder)
                <div class="rounded-lg bg-green-50 p-3 dark:bg-green-950 md:col-span-2">
                    <p class="font-semibold text-green-700 dark:text-green-200">Đơn hàng {{ $latestOrder->order_number }}</p>
                    <p class="mt-1">
                        {{ $latestOrder->items->pluck('product_name')->join(', ') }}
                        · {{ number_format((int) $latestOrder->net_amount, 0, ',', '.') }} ₫
                        · {{ config('telesales.delivery_statuses.'.$latestOrder->delivery_status, $latestOrder->delivery_status) }}
                    </p>
                </div>
            @endif
        </div>
    </div>
</x-admin::layouts>

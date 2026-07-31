<x-admin::layouts>
    <x-slot:title>
        Đơn hàng telesale
    </x-slot>

    @php($money = fn ($value) => number_format((int) $value, 0, ',', '.').' ₫')

    <div class="flex flex-col gap-4">
        <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-gray-300 bg-white px-4 py-3 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div>
                <h1 class="text-xl font-bold dark:text-white">Đơn hàng telesale</h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">Danh sách đã được giới hạn theo quyền tài khoản.</p>
            </div>
            <a href="{{ route('admin.telesales.rankings.index') }}" class="primary-button">Bảng xếp hạng</a>
        </div>

        <form method="GET" class="flex flex-wrap items-end gap-3 rounded-lg border border-gray-300 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
            <label class="grid gap-1 text-sm dark:text-white">
                <span>Trạng thái</span>
                <select name="status" class="min-w-52 rounded-md border px-3 py-2 dark:border-gray-800 dark:bg-gray-900">
                    <option value="">Tất cả</option>
                    @foreach (config('telesales.order_statuses') as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <button class="primary-button">Lọc</button>
        </form>

        <div class="overflow-x-auto rounded-lg border border-gray-300 bg-white dark:border-gray-800 dark:bg-gray-900">
            <table class="w-full min-w-[1200px] text-left text-sm dark:text-white">
                <thead class="bg-gray-50 dark:bg-gray-950">
                    <tr>
                        @foreach (['Mã đơn', 'Khách hàng', 'Sản phẩm', 'Sale', 'Marketing', 'Tổng gộp', 'Chiết khấu', 'Sau chiết khấu', 'Trạng thái', 'Ngày chốt'] as $heading)
                            <th class="p-3">{{ $heading }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse ($orders as $order)
                        <tr class="border-t border-gray-200 align-top dark:border-gray-800">
                            <td class="p-3">
                                @if ($role === 'marketing')
                                    <span class="font-medium">{{ $order->order_number }}</span>
                                @else
                                    <a href="{{ route('admin.leads.view', $order->lead_id) }}" class="font-medium text-brandColor">{{ $order->order_number }}</a>
                                @endif
                            </td>
                            <td class="p-3">{{ $order->lead?->person?->name }}</td>
                            <td class="p-3">{{ $order->items->pluck('product_name')->join(', ') }}</td>
                            <td class="p-3">{{ $order->salesOwner?->name ?: 'Chưa phân bổ' }}</td>
                            <td class="p-3">{{ $order->marketingOwner?->name ?: 'Chưa xác định' }}</td>
                            <td class="whitespace-nowrap p-3">{{ $money($order->gross_amount) }}</td>
                            <td class="whitespace-nowrap p-3">{{ $money($order->discount_amount) }}</td>
                            <td class="whitespace-nowrap p-3 font-semibold">{{ $money($order->net_amount) }}</td>
                            <td class="p-3">
                                @if ($role !== 'marketing')
                                    <form method="POST" action="{{ route('admin.telesales.orders.status', $order->id) }}" class="grid gap-2">
                                        @csrf
                                        @method('PUT')
                                        <select name="status" class="rounded border px-2 py-1 dark:border-gray-800 dark:bg-gray-900">
                                            @foreach (config('telesales.order_statuses') as $value => $label)
                                                <option value="{{ $value }}" @selected($order->status === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        <select name="delivery_status" class="rounded border px-2 py-1 dark:border-gray-800 dark:bg-gray-900">
                                            @foreach (config('telesales.delivery_statuses') as $value => $label)
                                                <option value="{{ $value }}" @selected($order->delivery_status === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        <button class="secondary-button !px-2 !py-1">Cập nhật</button>
                                    </form>
                                @else
                                    {{ config('telesales.order_statuses.'.$order->status) }}
                                @endif
                            </td>
                            <td class="whitespace-nowrap p-3">{{ $order->closed_at?->format('d/m/Y H:i') ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="p-6 text-center text-gray-500">Chưa có đơn hàng.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $orders->links() }}
    </div>
</x-admin::layouts>

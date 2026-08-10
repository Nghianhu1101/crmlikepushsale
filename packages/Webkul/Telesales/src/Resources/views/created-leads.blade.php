<x-admin::layouts>
    @php
        $isSaleProfile = $role === 'sale';
        $profileTitle = $isSaleProfile ? 'Tác nghiệp Telesale' : 'Hồ sơ khách hàng Marketing';
        $profileDescription = $isSaleProfile
            ? 'Danh sách data được giao cho bạn. Gọi khách, cập nhật kết quả và theo dõi đơn hàng trên cùng một màn hình.'
            : 'Theo dõi data từ lúc Marketing tiếp nhận đến khi Sale chăm sóc và tạo đơn.';
    @endphp

    <x-slot:title>
        {{ $profileTitle }}
    </x-slot>

    @pushOnce('styles')
        <style>
            .marketing-profile-table {
                min-width: 1540px;
                border-collapse: separate;
                border-spacing: 0;
            }

            .marketing-profile-table th {
                min-width: 150px;
                background: #3677d8;
                color: #fff;
                font-size: 12px;
                font-weight: 700;
                line-height: 1.35;
                text-align: center;
                vertical-align: middle;
            }

            .marketing-profile-table th:nth-child(3) {
                min-width: 280px;
            }

            .marketing-profile-table td {
                height: 132px;
                border-top: 1px solid #e5e7eb;
                border-right: 1px solid #e5e7eb;
                padding: 12px;
                vertical-align: top;
            }

            .marketing-profile-table tbody tr:nth-child(even) {
                background: #f8fafc;
            }

            .dark .marketing-profile-table td {
                border-color: #374151;
            }

            .dark .marketing-profile-table tbody tr:nth-child(even) {
                background: #111827;
            }

            .marketing-profile-message {
                max-height: 84px;
                overflow-y: auto;
                white-space: pre-line;
            }

            .marketing-profile-filter-grid {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            @media (min-width: 1280px) {
                .marketing-profile-filter-grid {
                    grid-template-columns: repeat(6, minmax(0, 1fr));
                }

                .marketing-profile-filter-search {
                    grid-column: span 2;
                }

                .marketing-profile-filter-date-start {
                    grid-column-start: 5;
                }
            }

            @media (max-width: 767px) {
                .marketing-profile-filter-grid {
                    grid-template-columns: 1fr;
                }
            }
        </style>
    @endPushOnce

    <div class="flex flex-col gap-4">
        <div class="scroll-reactive-sticky sticky top-[60px] z-[1000] flex flex-wrap items-center justify-between gap-3 rounded-lg border border-gray-300 bg-white px-4 py-3 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div>
                <div class="text-xl font-bold dark:text-white">{{ $profileTitle }}</div>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                    {{ $profileDescription }}
                </p>
            </div>

            <div class="flex items-center gap-2">
                <span class="rounded-full bg-blue-50 px-3 py-1 text-sm font-semibold text-blue-700 dark:bg-blue-950 dark:text-blue-200">
                    {{ $records->total() }} data
                </span>
                @unless ($isSaleProfile)
                    <a href="{{ route('admin.leads.create') }}" class="primary-button">Thêm data</a>
                @endunless
            </div>
        </div>

        <form method="GET" class="rounded-lg border border-gray-300 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
            <div class="marketing-profile-filter-grid gap-3">
                <input
                    type="search"
                    name="search"
                    value="{{ $filters['search'] ?? '' }}"
                    placeholder="Tên, số điện thoại, tin nhắn"
                    class="marketing-profile-filter-search rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-white"
                >

                <select name="source_id" class="rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-white">
                    <option value="">Tất cả nguồn</option>
                    @foreach ($sources as $source)
                        <option value="{{ $source->id }}" @selected((int) ($filters['source_id'] ?? 0) === $source->id)>{{ $source->name }}</option>
                    @endforeach
                </select>

                @unless ($isSaleProfile)
                    <select name="sales_owner_id" class="rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-white">
                        <option value="">Tất cả Sale</option>
                        @foreach ($salesOwners as $sale)
                            <option value="{{ $sale->id }}" @selected((int) ($filters['sales_owner_id'] ?? 0) === $sale->id)>{{ $sale->name }}</option>
                        @endforeach
                    </select>
                @else
                    <div class="flex items-center rounded-md border border-blue-200 bg-blue-50 px-3 py-2 text-sm font-semibold text-blue-700 dark:border-blue-800 dark:bg-blue-950 dark:text-blue-200">
                        Data của tôi · {{ auth()->guard('user')->user()->name }}
                    </div>
                @endunless

                <select name="stage_id" class="rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-white">
                    <option value="">Tất cả trạng thái</option>
                    @foreach ($stages as $stage)
                        <option value="{{ $stage->id }}" @selected((int) ($filters['stage_id'] ?? 0) === $stage->id)>{{ $stage->name }}</option>
                    @endforeach
                </select>

                <div class="flex gap-2">
                    <button class="primary-button flex-1">Tìm kiếm</button>
                    <a href="{{ route('admin.telesales.created-leads.index') }}" class="secondary-button">Xóa lọc</a>
                </div>

                <label class="marketing-profile-filter-date-start grid gap-1 text-xs text-gray-500">
                    <span>Từ ngày</span>
                    <input type="date" name="start_date" value="{{ $filters['start_date'] ?? '' }}" class="rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-white">
                </label>

                <label class="grid gap-1 text-xs text-gray-500">
                    <span>Đến ngày</span>
                    <input type="date" name="end_date" value="{{ $filters['end_date'] ?? '' }}" class="rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-white">
                </label>
            </div>
        </form>

        <div class="overflow-x-auto rounded-lg border border-gray-300 bg-white dark:border-gray-800 dark:bg-gray-900">
            <table class="marketing-profile-table w-full text-left text-sm dark:text-white">
                <thead>
                    <tr>
                        <th class="p-3">Nguồn dữ liệu<br>Ngày data về</th>
                        <th class="p-3">Khách hàng<br>Số điện thoại</th>
                        <th class="p-3">Tin nhắn khách hàng</th>
                        <th class="p-3">Sale phụ trách<br>Ngày nhận data</th>
                        <th class="p-3">Tác nghiệp<br>Ngày cập nhật</th>
                        <th class="p-3">Kết quả<br>Sale triển khai thông tin</th>
                        <th class="p-3">Sản phẩm · Số lượng · Đơn giá</th>
                        <th class="p-3">Thành tiền<br>Đặt cọc</th>
                        <th class="p-3">Trạng thái Lead<br>Trạng thái giao hàng</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($records as $record)
                        @php
                            $lead = $record->lead;
                            $phone = data_get($lead?->person?->contact_numbers, '0.value');
                            $displayPhone = $role === 'marketing' && $phone && strlen($phone) > 7
                                ? substr($phone, 0, 4).'***'.substr($phone, -3)
                                : $phone;
                            $history = $record->latestCallHistory;
                            $feedback = $record->latestMarketingFeedback;
                            $order = $record->latestOrder;
                            $orderItems = $order?->items ?? collect();
                            $isOldCustomer = $record->customer_type === 'old';
                        @endphp

                        <tr>
                            <td>
                                <div class="font-semibold text-blue-700 dark:text-blue-300">{{ $lead?->source?->name ?: 'Chưa xác định' }}</div>
                                @if ($record->campaign)<div class="mt-1 text-xs text-gray-500">{{ $record->campaign }}</div>@endif
                                <div class="mt-2 text-xs text-gray-500">{{ ($record->data_received_at ?: $record->created_at)?->format('d/m/Y H:i') }}</div>
                            </td>

                            <td>
                                <a href="{{ route('admin.leads.view', $record->lead_id) }}" class="font-semibold text-brandColor">
                                    {{ $lead?->person?->name ?: 'Khách chưa đặt tên' }}
                                </a>
                                <div class="mt-2 font-medium">{{ $displayPhone ?: 'Chưa có số' }}</div>
                                @if ($isOldCustomer)
                                    <div
                                        class="mt-2 inline-flex text-xl leading-none text-red-500"
                                        data-customer-type="old"
                                        role="img"
                                        aria-label="Khách hàng cũ"
                                        title="Khách hàng cũ"
                                    >♥</div>
                                @endif
                                @if ($role === 'marketing')
                                    <div class="mt-1 text-xs text-gray-500">Số được ẩn theo quyền Marketing</div>
                                @endif

                                @if ($isSaleProfile && $phone)
                                    <div class="mt-3 flex flex-wrap gap-2">
                                        <a href="tel:{{ $phone }}" class="inline-flex items-center gap-1 rounded-md bg-green-600 px-2.5 py-1.5 text-xs font-semibold text-white hover:bg-green-700">
                                            <span class="icon-call" aria-hidden="true"></span>
                                            Gọi ngay
                                        </a>
                                        <a href="{{ route('admin.leads.view', $record->lead_id) }}" class="inline-flex items-center rounded-md bg-blue-50 px-2.5 py-1.5 text-xs font-semibold text-blue-700 hover:bg-blue-100">
                                            Tác nghiệp
                                        </a>
                                    </div>
                                @endif
                            </td>

                            <td>
                                <div class="marketing-profile-message text-gray-700 dark:text-gray-200">
                                    {{ $record->initial_message ?: $lead?->description ?: 'Khách chưa để lại nội dung.' }}
                                </div>
                            </td>

                            <td>
                                <div class="font-semibold">{{ $record->salesOwner?->name ?: 'Chưa phân bổ' }}</div>
                                <div class="mt-2 text-xs text-gray-500">
                                    {{ $record->assigned_at?->format('d/m/Y H:i') ?: 'Chưa có thời điểm nhận' }}
                                </div>
                            </td>

                            <td>
                                @if ($history)
                                    <div class="font-semibold">{{ $history->user?->name }}</div>
                                    <div class="mt-1">{{ config('telesales.call_results.'.$history->result, $history->result) }}</div>
                                    <div class="mt-2 text-xs text-gray-500">{{ $history->created_at->format('d/m/Y H:i') }}</div>
                                @else
                                    <span class="text-gray-500">Sale chưa tác nghiệp</span>
                                @endif
                            </td>

                            <td>
                                @if ($history)
                                    <div class="font-semibold text-blue-700 dark:text-blue-300">
                                        {{ config('telesales.call_results.'.$history->result, $history->result) }}
                                    </div>

                                    @if ($isSaleProfile)
                                        <div class="marketing-profile-message mt-2 text-gray-700 dark:text-gray-200">
                                            {{ $history->note ?: 'Chưa có ghi chú chăm sóc.' }}
                                        </div>
                                    @else
                                        <div class="marketing-profile-message mt-2 text-orange-600 dark:text-orange-300">
                                            {{ $feedback?->marketing_feedback ?: 'Sale chưa gửi phản hồi cho Marketing.' }}
                                        </div>
                                    @endif

                                    @if (! $isSaleProfile && $feedback)
                                        <div class="mt-1 text-xs text-gray-500">
                                            {{ $feedback->user?->name }} · {{ $feedback->created_at->format('d/m/Y H:i') }}
                                        </div>
                                    @endif

                                    @if ($history->callback_at)
                                        <div class="mt-2 text-xs font-medium text-brandColor">Hẹn gọi: {{ $history->callback_at->format('d/m/Y H:i') }}</div>
                                    @endif
                                @else
                                    <span class="text-gray-500">Chưa có kết quả</span>
                                @endif
                            </td>

                            <td>
                                @if ($orderItems->isNotEmpty())
                                    <div class="grid gap-2">
                                        @foreach ($orderItems as $item)
                                            <div class="border-b border-gray-200 pb-2 last:border-0 dark:border-gray-700">
                                                <div class="font-semibold">{{ $item->product_name }}</div>
                                                <div class="text-xs text-gray-500">
                                                    x{{ $item->quantity }} · {{ number_format((int) $item->unit_price, 0, ',', '.') }} ₫
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="font-medium">{{ $record->product_interest ?: 'Chưa chọn sản phẩm' }}</div>
                                    <div class="mt-1 text-xs text-gray-500">Chưa tạo đơn hàng</div>
                                @endif
                            </td>

                            <td class="text-right">
                                @if ($order)
                                    <div class="font-bold">{{ number_format((int) $order->net_amount, 0, ',', '.') }} ₫</div>
                                    <div class="mt-2 text-xs text-gray-500">Đặt cọc</div>
                                    <div class="font-semibold">{{ number_format((int) $order->deposit_amount, 0, ',', '.') }} ₫</div>
                                @else
                                    <span class="text-gray-500">0 ₫</span>
                                @endif
                            </td>

                            <td>
                                <div class="font-semibold">{{ $lead?->stage?->name ?: 'Chưa xác định' }}</div>
                                @if ($order)
                                    <div class="mt-2 text-xs text-gray-500">{{ $order->order_number }}</div>
                                    <div class="mt-1 font-medium text-green-700 dark:text-green-300">
                                        {{ config('telesales.delivery_statuses.'.$order->delivery_status, $order->delivery_status) }}
                                    </div>
                                @else
                                    <div class="mt-2 text-xs text-gray-500">Chưa có đơn</div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="p-8 text-center text-gray-500">Không có data phù hợp bộ lọc.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $records->links() }}
    </div>
</x-admin::layouts>

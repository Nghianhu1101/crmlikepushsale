<x-admin::layouts>
    <x-slot:title>Khách hàng 360</x-slot>

    @php
        $canSeeFullPhone = $role !== 'marketing';
        $canCreateCampaign = in_array($role, ['admin', 'customer_care'], true);
    @endphp

    <div class="flex flex-col gap-4">
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h1 class="text-xl font-bold text-gray-900 dark:text-white">Khách hàng 360</h1>
                    <p class="mt-1 text-sm text-gray-500">Một hồ sơ duy nhất từ Marketing nhận số, Sale chốt đơn đến bộ phận CSKH chăm sóc mua lại.</p>
                </div>
                @if ($canCreateCampaign)
                    <a href="{{ route('admin.telesales.customer-care.cases.index') }}" class="primary-button">Tác nghiệp CSKH</a>
                @endif
            </div>

            <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-lg bg-slate-50 p-4 dark:bg-gray-950"><div class="text-xs text-gray-500">Tổng hồ sơ</div><div class="mt-1 text-2xl font-bold dark:text-white">{{ $stats['total'] }}</div></div>
                <div class="rounded-lg bg-blue-50 p-4 dark:bg-blue-950"><div class="text-xs text-blue-600">Khách hàng mới</div><div class="mt-1 text-2xl font-bold text-blue-700 dark:text-blue-200">{{ $stats['new'] }}</div></div>
                <div class="rounded-lg border-2 border-orange-300 bg-orange-50 p-4 dark:border-orange-700 dark:bg-orange-950"><div class="text-xs font-semibold uppercase text-orange-600">Khách hàng cũ</div><div class="mt-1 text-2xl font-bold text-orange-700 dark:text-orange-200">{{ $stats['old'] }}</div></div>
                <div class="rounded-lg bg-emerald-50 p-4 dark:bg-emerald-950"><div class="text-xs text-emerald-600">Đang cần chăm sóc</div><div class="mt-1 text-2xl font-bold text-emerald-700 dark:text-emerald-200">{{ $stats['needs_care'] }}</div></div>
            </div>
        </div>

        <form method="GET" class="grid gap-3 rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900 md:grid-cols-3 xl:grid-cols-6">
            <input name="search" value="{{ request('search') }}" placeholder="Tên, phone, mã KH" class="rounded-md border border-gray-300 px-3 py-2 dark:border-gray-700 dark:bg-gray-950">
            <select name="customer_status" class="rounded-md border border-gray-300 px-3 py-2 dark:border-gray-700 dark:bg-gray-950">
                <option value="">Tất cả khách hàng</option>
                <option value="new" @selected(request('customer_status') === 'new')>Khách hàng mới</option>
                <option value="old" @selected(request('customer_status') === 'old')>Khách hàng cũ</option>
            </select>
            @if ($role === 'admin')
                <select name="marketing_owner_id" class="rounded-md border border-gray-300 px-3 py-2 dark:border-gray-700 dark:bg-gray-950"><option value="">Tất cả Marketing</option>@foreach ($marketingUsers as $owner)<option value="{{ $owner->id }}" @selected((int) request('marketing_owner_id') === $owner->id)>{{ $owner->name }}</option>@endforeach</select>
                <select name="sales_owner_id" class="rounded-md border border-gray-300 px-3 py-2 dark:border-gray-700 dark:bg-gray-950"><option value="">Tất cả Sale</option>@foreach ($salesUsers as $owner)<option value="{{ $owner->id }}" @selected((int) request('sales_owner_id') === $owner->id)>{{ $owner->name }}</option>@endforeach</select>
                <select name="care_owner_id" class="rounded-md border border-gray-300 px-3 py-2 dark:border-gray-700 dark:bg-gray-950"><option value="">Tất cả CSKH</option>@foreach ($careUsers as $owner)<option value="{{ $owner->id }}" @selected((int) request('care_owner_id') === $owner->id)>{{ $owner->name }}</option>@endforeach</select>
            @endif
            <input type="number" min="0" name="repeat_min" value="{{ request('repeat_min') }}" placeholder="Số lần mua lại từ" class="rounded-md border border-gray-300 px-3 py-2 dark:border-gray-700 dark:bg-gray-950">
            <input type="date" name="from_date" value="{{ request('from_date') }}" class="rounded-md border border-gray-300 px-3 py-2 dark:border-gray-700 dark:bg-gray-950">
            <input type="date" name="to_date" value="{{ request('to_date') }}" class="rounded-md border border-gray-300 px-3 py-2 dark:border-gray-700 dark:bg-gray-950">
            <div class="flex gap-2"><button class="primary-button flex-1">Tìm kiếm</button><a href="{{ route('admin.telesales.customers.index') }}" class="secondary-button">Xóa lọc</a></div>
        </form>

        @if ($canCreateCampaign)
            <form id="care-campaign-form" method="POST" action="{{ route('admin.telesales.customer-care.campaigns.store') }}" class="grid gap-3 rounded-xl border border-blue-200 bg-blue-50 p-4 dark:border-blue-800 dark:bg-blue-950 md:grid-cols-4">
                @csrf
                <input name="name" required placeholder="Tên chiến dịch chăm sóc" class="rounded-md border border-blue-200 px-3 py-2 dark:border-blue-700 dark:bg-gray-950">
                <select name="group_id" class="rounded-md border border-blue-200 px-3 py-2 dark:border-blue-700 dark:bg-gray-950"><option value="">Nhóm CSKH mặc định</option>@foreach ($careGroups as $group)<option value="{{ $group->id }}">{{ $group->name }}</option>@endforeach</select>
                <input name="description" placeholder="Nội dung/kịch bản chăm sóc" class="rounded-md border border-blue-200 px-3 py-2 dark:border-blue-700 dark:bg-gray-950">
                <button class="primary-button">Tạo từ khách đã chọn</button>
            </form>
        @endif

        <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <table class="min-w-[1500px] w-full text-sm dark:text-white">
                <thead class="bg-[#3677d8] text-white">
                    <tr>
                        @if ($canCreateCampaign)<th class="p-3"><input type="checkbox" onclick="document.querySelectorAll('.customer-select').forEach(item => item.checked = this.checked)"></th>@endif
                        <th class="p-3">Trạng thái</th><th class="p-3">Sale</th><th class="p-3">Marketing lấy số</th><th class="p-3">CSKH</th><th class="p-3">Mã KH</th><th class="p-3">Tên khách hàng</th><th class="p-3">Số điện thoại</th><th class="p-3">Lần mua</th><th class="p-3">Doanh số</th><th class="p-3">Sản phẩm gần nhất</th><th class="p-3">Lời nhắn gần nhất</th><th class="p-3">Cập nhật</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($profiles as $profile)
                        @php
                            $phone = $profile->person?->normalized_phone ?: data_get($profile->person?->contact_numbers, '0.value');
                            $displayPhone = $canSeeFullPhone || ! $phone ? $phone : substr($phone, 0, 4).'***'.substr($phone, -3);
                            $latestCase = $profile->latestCareCase;
                        @endphp
                        <tr class="border-t border-gray-200 align-top dark:border-gray-800">
                            @if ($canCreateCampaign)<td class="p-3">@if ($profile->customer_status === 'old')<input form="care-campaign-form" class="customer-select" type="checkbox" name="person_ids[]" value="{{ $profile->person_id }}">@endif</td>@endif
                            <td class="p-3">@if ($profile->customer_status === 'old')<span class="inline-flex rounded-full bg-orange-100 px-2.5 py-1 font-bold text-orange-700">Khách hàng cũ</span>@else<span class="inline-flex rounded-full bg-blue-100 px-2.5 py-1 font-semibold text-blue-700">Khách hàng mới</span>@endif</td>
                            <td class="p-3 font-medium">{{ $profile->salesOwner?->name ?: 'Chưa có' }}</td>
                            <td class="p-3"><strong>{{ $profile->marketingOwner?->name ?: 'Chưa xác định' }}</strong><div class="mt-1 text-xs text-gray-500">{{ $profile->lastLead?->source?->name }}</div></td>
                            <td class="p-3">{{ $profile->careOwner?->name ?: 'Chưa phân' }}@if ($latestCase)<div class="mt-1 text-xs text-emerald-600">{{ config('telesales.customer_care_statuses.'.$latestCase->status, $latestCase->status) }}</div>@endif</td>
                            <td class="p-3">{{ str_pad((string) $profile->person_id, 10, '0', STR_PAD_LEFT) }}</td>
                            <td class="p-3 font-semibold">{{ $profile->person?->name }}</td>
                            <td class="p-3">@if ($canSeeFullPhone && $phone)<a class="font-semibold text-blue-600" href="tel:{{ $phone }}">{{ $displayPhone }}</a>@else{{ $displayPhone }}@endif</td>
                            <td class="p-3 text-center"><strong>{{ $profile->successful_order_count }}</strong><div class="text-xs text-gray-500">Mua lại: {{ $profile->repeat_order_count }}</div></td>
                            <td class="p-3 text-right font-semibold">{{ number_format($profile->total_revenue, 0, ',', '.') }} ₫</td>
                            <td class="p-3">{{ $profile->lastOrder?->items?->pluck('product_name')->join(', ') ?: $profile->lastLeadMeta?->product_interest ?: 'Chưa có' }}</td>
                            <td class="max-w-xs p-3">{{ $profile->lastLeadMeta?->initial_message ?: 'Không có' }}</td>
                            <td class="p-3">{{ $profile->updated_at?->format('d/m/Y H:i') }}@if ($latestCase && ($role === 'admin' || $latestCase->care_owner_id === auth()->guard('user')->id()))<div class="mt-2"><a class="text-blue-600" href="{{ route('admin.telesales.customer-care.cases.show', $latestCase->id) }}">Mở tác nghiệp</a></div>@endif</td>
                        </tr>
                    @empty
                        <tr><td colspan="13" class="p-8 text-center text-gray-500">Chưa có hồ sơ phù hợp.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $profiles->links() }}
    </div>
</x-admin::layouts>

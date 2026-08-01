<x-admin::layouts>
    <x-slot:title>Chiến dịch chăm sóc khách hàng</x-slot>
    <div class="flex flex-col gap-4">
        <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
            <div><h1 class="text-xl font-bold dark:text-white">Chiến dịch chăm sóc khách hàng cũ</h1><p class="mt-1 text-sm text-gray-500">Chọn tệp khách tại Khách hàng 360 rồi chia đều cho bộ phận CSKH.</p></div>
            <a href="{{ route('admin.telesales.customers.index', ['customer_status' => 'old']) }}" class="primary-button">Chọn khách hàng cũ</a>
        </div>
        <div class="overflow-x-auto rounded-xl border bg-white dark:border-gray-800 dark:bg-gray-900">
            <table class="w-full min-w-[900px] text-sm dark:text-white"><thead class="bg-[#3677d8] text-white"><tr><th class="p-3">Chiến dịch</th><th class="p-3">Nhóm CSKH</th><th class="p-3">Bắt đầu</th><th class="p-3">Tổng ca</th><th class="p-3">Hoàn thành</th><th class="p-3">Trạng thái</th></tr></thead><tbody>
            @forelse ($campaigns as $campaign)<tr class="border-t dark:border-gray-800"><td class="p-3"><strong>{{ $campaign->name }}</strong><div class="text-xs text-gray-500">{{ $campaign->description }}</div></td><td class="p-3">{{ $campaign->group?->name ?: 'Nhóm mặc định' }}</td><td class="p-3">{{ $campaign->starts_at?->format('d/m/Y H:i') }}</td><td class="p-3 text-center">{{ $campaign->cases_count }}</td><td class="p-3 text-center">{{ $campaign->completed_cases_count }}</td><td class="p-3">{{ $campaign->status === 'active' ? 'Đang chạy' : $campaign->status }}</td></tr>@empty<tr><td colspan="6" class="p-8 text-center text-gray-500">Chưa có chiến dịch.</td></tr>@endforelse
            </tbody></table>
        </div>{{ $campaigns->links() }}
    </div>
</x-admin::layouts>

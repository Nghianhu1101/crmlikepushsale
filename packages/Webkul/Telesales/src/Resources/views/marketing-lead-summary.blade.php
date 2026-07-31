<x-admin::layouts>
    <x-slot:title>
        Data {{ $lead->person?->name }}
    </x-slot>

    @php($meta = \Webkul\Telesales\Models\LeadMeta::query()->where('lead_id', $lead->id)->first())

    <div class="flex flex-col gap-4">
        <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-gray-300 bg-white px-4 py-3 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div>
                <div class="text-xl font-bold dark:text-white">{{ $lead->person?->name }}</div>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                    Tóm tắt data do bạn nhập. Ghi chú chăm sóc của sale không hiển thị tại đây.
                </p>
            </div>

            <a href="{{ route('admin.telesales.created-leads.index') }}" class="secondary-button">Data tôi đã nhập</a>
        </div>

        <div class="grid gap-4 rounded-lg border border-gray-300 bg-white p-4 text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-white md:grid-cols-2">
            <p><span class="font-medium">Số điện thoại:</span> {{ data_get($lead->person?->contact_numbers, '0.value') }}</p>
            <p><span class="font-medium">Nguồn:</span> {{ $lead->source?->name ?: 'Chưa xác định' }}</p>
            <p><span class="font-medium">Sản phẩm:</span> {{ $meta?->product_interest ?: 'Chưa chọn' }}</p>
            <p><span class="font-medium">Sale phụ trách:</span> {{ $lead->user?->name ?: 'Chưa phân bổ' }}</p>
            <p><span class="font-medium">Trạng thái:</span> {{ $lead->stage?->name }}</p>
            <p><span class="font-medium">Data về:</span> {{ $lead->created_at?->format('d/m/Y H:i') }}</p>
            <p class="md:col-span-2"><span class="font-medium">Nội dung khách để lại:</span> {{ $meta?->initial_message ?: 'Không có' }}</p>
        </div>
    </div>
</x-admin::layouts>

<x-admin::layouts>
    <x-slot:title>
        Data tôi đã nhập
    </x-slot>

    <div class="flex flex-col gap-4">
        <div class="scroll-reactive-sticky sticky top-[60px] z-[1000] flex items-center justify-between rounded-lg border border-gray-300 bg-white px-4 py-3 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div>
                <div class="text-xl font-bold dark:text-white">Data tôi đã nhập</div>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                    Chỉ hiển thị data do tài khoản hiện tại tạo.
                </p>
            </div>

            <a href="{{ route('admin.leads.create') }}" class="primary-button">Thêm data</a>
        </div>

        <div class="overflow-x-auto rounded-lg border border-gray-300 bg-white dark:border-gray-800 dark:bg-gray-900">
            <table class="w-full min-w-[760px] text-left text-sm dark:text-white">
                <thead class="bg-gray-50 dark:bg-gray-950">
                    <tr>
                        <th class="p-3">Khách hàng</th>
                        <th class="p-3">Số điện thoại</th>
                        <th class="p-3">Nguồn</th>
                        <th class="p-3">Sale phụ trách</th>
                        <th class="p-3">Trạng thái</th>
                        <th class="p-3">Data về</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($records as $record)
                        <tr class="border-t border-gray-200 dark:border-gray-800">
                            <td class="p-3">
                                <a
                                    href="{{ route('admin.leads.view', $record->lead_id) }}"
                                    class="font-medium text-brandColor"
                                >
                                    {{ $record->lead?->person?->name }}
                                </a>
                            </td>
                            <td class="p-3">{{ data_get($record->lead?->person?->contact_numbers, '0.value') }}</td>
                            <td class="p-3">{{ $record->lead?->source?->name ?: 'Chưa xác định' }}</td>
                            <td class="p-3">{{ $record->lead?->user?->name ?: 'Chưa phân bổ' }}</td>
                            <td class="p-3">{{ $record->lead?->stage?->name }}</td>
                            <td class="p-3">{{ $record->lead?->created_at?->format('d/m/Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-4 text-center text-gray-500">Chưa có data nào được nhập.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $records->links() }}
    </div>
</x-admin::layouts>

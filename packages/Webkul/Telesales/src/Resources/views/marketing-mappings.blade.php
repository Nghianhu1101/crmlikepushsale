<x-admin::layouts>
    <x-slot:title>
        Ánh xạ Marketing
    </x-slot>

    <div class="flex flex-col gap-4">
        <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-gray-300 bg-white px-4 py-3 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div>
                <h1 class="text-xl font-bold dark:text-white">Ánh xạ Marketing</h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">Ưu tiên external ID, sau đó campaign và nguồn data.</p>
            </div>
            <a href="{{ route('admin.telesales.groups.index') }}" class="secondary-button">Cấu hình nhóm Sale</a>
        </div>

        <form method="POST" action="{{ route('admin.telesales.marketing-mappings.store') }}" class="rounded-lg border border-gray-300 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
            @csrf
            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                <label class="grid gap-1 text-sm dark:text-white">
                    <span>Tài khoản Marketing</span>
                    <select name="user_id" required class="rounded-md border px-3 py-2 dark:border-gray-800 dark:bg-gray-900">
                        <option value="">Chọn tài khoản</option>
                        @foreach ($marketingUsers as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="grid gap-1 text-sm dark:text-white">
                    <span>Marketing external ID</span>
                    <input name="marketing_external_id" value="{{ old('marketing_external_id') }}" class="rounded-md border px-3 py-2 dark:border-gray-800 dark:bg-gray-900">
                </label>
                <label class="grid gap-1 text-sm dark:text-white">
                    <span>Campaign</span>
                    <input name="campaign" value="{{ old('campaign') }}" class="rounded-md border px-3 py-2 dark:border-gray-800 dark:bg-gray-900">
                </label>
                <label class="grid gap-1 text-sm dark:text-white">
                    <span>Nguồn data</span>
                    <select name="source_id" class="rounded-md border px-3 py-2 dark:border-gray-800 dark:bg-gray-900">
                        <option value="">Không giới hạn</option>
                        @foreach ($sources as $source)
                            <option value="{{ $source->id }}">{{ $source->name }}</option>
                        @endforeach
                    </select>
                </label>
            </div>
            <div class="mt-4 flex justify-end"><button class="primary-button">Thêm ánh xạ</button></div>
        </form>

        <div class="overflow-x-auto rounded-lg border border-gray-300 bg-white dark:border-gray-800 dark:bg-gray-900">
            <table class="w-full min-w-[800px] text-left text-sm dark:text-white">
                <thead class="bg-gray-50 dark:bg-gray-950">
                    <tr><th class="p-3">Marketing</th><th class="p-3">External ID</th><th class="p-3">Campaign</th><th class="p-3">Nguồn ID</th><th class="p-3"></th></tr>
                </thead>
                <tbody>
                    @forelse ($mappings as $mapping)
                        <tr class="border-t border-gray-200 dark:border-gray-800">
                            <td class="p-3">{{ $mapping->user?->name }}</td>
                            <td class="p-3">{{ $mapping->marketing_external_id ?: '—' }}</td>
                            <td class="p-3">{{ $mapping->campaign ?: '—' }}</td>
                            <td class="p-3">{{ $mapping->source_id ?: '—' }}</td>
                            <td class="p-3 text-right">
                                <form method="POST" action="{{ route('admin.telesales.marketing-mappings.destroy', $mapping->id) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button class="text-red-600">Xóa</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="p-4 text-center text-gray-500">Chưa có ánh xạ Marketing.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-admin::layouts>

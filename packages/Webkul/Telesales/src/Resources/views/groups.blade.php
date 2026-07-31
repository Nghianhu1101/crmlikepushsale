<x-admin::layouts>
    <x-slot:title>
        Cấu hình phân data telesale
    </x-slot>

    <div class="flex flex-col gap-4">
        <div class="scroll-reactive-sticky sticky top-[60px] z-[1000] rounded-lg border border-gray-300 bg-white px-4 py-3 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <div class="text-xl font-bold dark:text-white">Cấu hình phân data telesale</div>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                        Thành viên được lấy từ nhóm người dùng Krayin. Bật những sale được nhận data và đặt thứ tự chia vòng.
                    </p>
                </div>
                <a href="{{ route('admin.telesales.marketing-mappings.index') }}" class="secondary-button">Ánh xạ Marketing</a>
            </div>
        </div>

        @forelse ($groups as $group)
            @php($configuration = $configurations->get($group->id))

            <form
                method="POST"
                action="{{ route('admin.telesales.groups.update', $group->id) }}"
                class="rounded-lg border border-gray-300 bg-white p-4 dark:border-gray-800 dark:bg-gray-900"
            >
                @csrf
                @method('PUT')

                <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-semibold dark:text-white">{{ $group->name }}</h2>
                        <p class="text-sm text-gray-500">{{ $group->description }}</p>
                    </div>

                    <label class="flex items-center gap-2 text-sm dark:text-white">
                        <input
                            type="checkbox"
                            name="is_default"
                            value="1"
                            @checked($configuration?->is_default)
                        >
                        Nhóm mặc định
                    </label>
                </div>

                <div class="mb-4 grid gap-4 md:grid-cols-2">
                    <label class="grid gap-1 text-sm dark:text-white">
                        <span>Nguồn data áp dụng</span>
                        <select name="source_id" class="custom-select w-full rounded-md border px-3 py-2 dark:border-gray-800 dark:bg-gray-900">
                            <option value="">Mọi nguồn</option>
                            @foreach ($sources as $source)
                                <option value="{{ $source->id }}" @selected($configuration?->source_id === $source->id)>
                                    {{ $source->name }}
                                </option>
                            @endforeach
                        </select>
                    </label>

                    <label class="grid gap-1 text-sm dark:text-white">
                        <span>Campaign</span>
                        <input
                            type="text"
                            name="campaign"
                            value="{{ $configuration?->campaign }}"
                            class="rounded-md border px-3 py-2 dark:border-gray-800 dark:bg-gray-900"
                            placeholder="Ví dụ: FB_PVK_01"
                        >
                    </label>
                </div>

                <div class="overflow-x-auto rounded border border-gray-200 dark:border-gray-800">
                    <table class="w-full min-w-[520px] text-left text-sm dark:text-white">
                        <thead class="bg-gray-50 dark:bg-gray-950">
                            <tr>
                                <th class="p-3">Sale</th>
                                <th class="p-3">Đang hoạt động</th>
                                <th class="p-3">Nhận data</th>
                                <th class="p-3">Thứ tự</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($group->users as $position => $user)
                                @php($member = $members->get($group->id.'-'.$user->id))
                                <tr class="border-t border-gray-200 dark:border-gray-800">
                                    <td class="p-3">{{ $user->name }}</td>
                                    <td class="p-3">{{ $user->status ? 'Có' : 'Không' }}</td>
                                    <td class="p-3">
                                        <input type="hidden" name="members[{{ $user->id }}][receives_data]" value="0">
                                        <input
                                            type="checkbox"
                                            name="members[{{ $user->id }}][receives_data]"
                                            value="1"
                                            @checked($member?->receives_data)
                                        >
                                    </td>
                                    <td class="p-3">
                                        <input
                                            type="number"
                                            min="0"
                                            name="members[{{ $user->id }}][position]"
                                            value="{{ $member?->position ?? $position }}"
                                            class="w-24 rounded-md border px-3 py-2 dark:border-gray-800 dark:bg-gray-900"
                                        >
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="p-3 text-gray-500">
                                        Nhóm chưa có thành viên. Hãy thêm tại Cài đặt → Nhóm.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4 flex justify-end">
                    <button type="submit" class="primary-button">Lưu cấu hình</button>
                </div>
            </form>
        @empty
            <div class="rounded-lg border border-gray-300 bg-white p-4 text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                Chưa có nhóm người dùng Krayin. Hãy tạo nhóm trước trong Cài đặt → Nhóm.
            </div>
        @endforelse
    </div>
</x-admin::layouts>

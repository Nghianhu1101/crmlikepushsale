<x-admin::layouts>
    <x-slot:title>Quản lý tài khoản theo vai trò</x-slot>

    @php
        $groupDepartments = $groups->mapWithKeys(fn ($configuration) => [
            $configuration->group_id => $configuration->department,
        ]);
        $categoryLabels = [
            'admin' => 'Quản trị toàn hệ thống',
            'marketing' => 'Nhập data và theo dõi nguồn',
            'sale' => 'Nhận data và tác nghiệp telesale',
            'leader' => 'Quản lý data của nhóm Sale',
            'customer_care' => 'Chăm sóc khách hàng cũ',
            'staff' => 'Nhân sự theo quyền của vai trò',
        ];
    @endphp

    <div
        class="flex flex-col gap-4"
        x-data='{
            selectedRole: @js((string) old("role_id", "")),
            categories: @js($roleCategories),
            groupDepartments: @js($groupDepartments),
            category() { return this.categories[this.selectedRole] || null },
            department() {
                if (["sale", "leader"].includes(this.category())) return "sales";
                if (this.category() === "customer_care") return "customer_care";
                return null;
            },
            needsGroup() { return this.department() !== null }
        }'
    >
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h1 class="text-xl font-bold text-gray-900 dark:text-white">Quản lý tài khoản nhân sự</h1>
                    <p class="mt-1 text-sm text-gray-500">Admin tạo tài khoản đúng vai trò và tự động đưa nhân viên vào phòng ban nhận data tương ứng.</p>
                </div>

                <a href="{{ route('admin.settings.users.index') }}" class="secondary-button">Quản lý nâng cao Krayin</a>
            </div>

            <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
                @foreach ($roles as $role)
                    @php($category = $roleCategories[$role->id])
                    <button
                        type="button"
                        class="rounded-lg border p-4 text-left transition hover:border-blue-500 hover:bg-blue-50 dark:border-gray-700 dark:hover:bg-blue-950"
                        :class="selectedRole === '{{ $role->id }}' ? 'border-blue-500 bg-blue-50 ring-2 ring-blue-100 dark:bg-blue-950' : 'border-gray-200'"
                        @click="selectedRole = '{{ $role->id }}'; document.getElementById('account-form').scrollIntoView({ behavior: 'smooth' })"
                    >
                        <div class="font-semibold text-gray-900 dark:text-white">{{ $role->name }}</div>
                        <div class="mt-1 text-xs text-gray-500">{{ $categoryLabels[$category] ?? $role->description }}</div>
                        <div class="mt-3 text-sm font-medium text-blue-600">{{ $role->users_count }} tài khoản</div>
                    </button>
                @endforeach
            </div>
        </div>

        <form
            id="account-form"
            method="POST"
            action="{{ route('admin.telesales.accounts.store') }}"
            class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900"
        >
            @csrf

            <div class="mb-4">
                <h2 class="text-lg font-bold text-gray-900 dark:text-white">Tạo tài khoản mới</h2>
                <p class="text-sm text-gray-500">Mật khẩu được mã hóa; tài khoản Sale và CSKH sẽ tham gia đúng vòng chia data của phòng ban.</p>
            </div>

            @if ($errors->any())
                <div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                    <ul class="list-disc pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <label class="grid gap-1 text-sm text-gray-700 dark:text-gray-200">
                    <span>Họ tên <b class="text-red-500">*</b></span>
                    <input name="name" value="{{ old('name') }}" required class="rounded-md border border-gray-300 px-3 py-2 dark:border-gray-700 dark:bg-gray-950" placeholder="Nguyễn Văn A">
                </label>

                <label class="grid gap-1 text-sm text-gray-700 dark:text-gray-200">
                    <span>Email đăng nhập <b class="text-red-500">*</b></span>
                    <input type="email" name="email" value="{{ old('email') }}" required class="rounded-md border border-gray-300 px-3 py-2 dark:border-gray-700 dark:bg-gray-950" placeholder="sale01@congty.vn">
                </label>

                <label class="grid gap-1 text-sm text-gray-700 dark:text-gray-200">
                    <span>Vai trò <b class="text-red-500">*</b></span>
                    <select name="role_id" x-model="selectedRole" required class="rounded-md border border-gray-300 px-3 py-2 dark:border-gray-700 dark:bg-gray-950">
                        <option value="">Chọn vai trò</option>
                        @foreach ($roles as $role)
                            <option value="{{ $role->id }}">{{ $role->name }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="grid gap-1 text-sm text-gray-700 dark:text-gray-200">
                    <span>Mật khẩu <b class="text-red-500">*</b></span>
                    <input type="password" name="password" required minlength="8" autocomplete="new-password" class="rounded-md border border-gray-300 px-3 py-2 dark:border-gray-700 dark:bg-gray-950" placeholder="Tối thiểu 8 ký tự">
                </label>

                <label class="grid gap-1 text-sm text-gray-700 dark:text-gray-200">
                    <span>Xác nhận mật khẩu <b class="text-red-500">*</b></span>
                    <input type="password" name="password_confirmation" required minlength="8" autocomplete="new-password" class="rounded-md border border-gray-300 px-3 py-2 dark:border-gray-700 dark:bg-gray-950">
                </label>

                <label class="grid gap-1 text-sm text-gray-700 dark:text-gray-200" x-show="needsGroup()" x-cloak>
                    <span>Nhóm làm việc</span>
                    <select name="group_id" class="rounded-md border border-gray-300 px-3 py-2 dark:border-gray-700 dark:bg-gray-950">
                        <option value="">Dùng nhóm mặc định</option>
                        @foreach ($groups as $configuration)
                            <option
                                value="{{ $configuration->group_id }}"
                                @selected((int) old('group_id') === $configuration->group_id)
                                :disabled="department() !== '{{ $configuration->department }}'"
                            >
                                {{ $configuration->group->name }} · {{ $configuration->department === 'sales' ? 'Sale' : 'CSKH' }}{{ $configuration->is_default ? ' · Mặc định' : '' }}
                            </option>
                        @endforeach
                    </select>
                </label>
            </div>

            <div class="mt-4 flex flex-wrap gap-6 rounded-lg bg-gray-50 p-4 text-sm dark:bg-gray-950 dark:text-white">
                <label class="flex items-center gap-2">
                    <input type="hidden" name="status" value="0">
                    <input type="checkbox" name="status" value="1" @checked(old('status', '1') === '1')>
                    Kích hoạt tài khoản ngay
                </label>

                <label class="flex items-center gap-2" x-show="needsGroup()" x-cloak>
                    <input type="hidden" name="receives_data" value="0">
                    <input type="checkbox" name="receives_data" value="1" @checked(old('receives_data', '1') === '1')>
                    Được nhận data tự động
                </label>

                <span class="text-gray-500" x-show="category() === 'marketing'">Marketing được nhập data nhưng không tham gia vòng chia Sale.</span>
                <span class="text-gray-500" x-show="category() === 'admin'">Quản trị viên có quyền truy cập toàn hệ thống.</span>
            </div>

            <div class="mt-5 flex justify-end">
                <button class="primary-button">Tạo tài khoản</button>
            </div>
        </form>

        <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="border-b border-gray-200 p-4 dark:border-gray-800">
                <h2 class="font-bold text-gray-900 dark:text-white">Tài khoản hiện có</h2>
            </div>

            <table class="min-w-[900px] w-full text-sm dark:text-white">
                <thead class="bg-[#3677d8] text-white">
                    <tr>
                        <th class="p-3 text-left">Nhân viên</th>
                        <th class="p-3 text-left">Vai trò</th>
                        <th class="p-3 text-left">Nhóm làm việc</th>
                        <th class="p-3 text-left">Quyền xem</th>
                        <th class="p-3 text-left">Nhận data</th>
                        <th class="p-3 text-left">Trạng thái</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($accounts as $account)
                        @php($member = $members->get($account->id))
                        <tr class="border-b border-gray-100 dark:border-gray-800">
                            <td class="p-3">
                                <div class="font-semibold">{{ $account->name }}</div>
                                <div class="text-xs text-gray-500">{{ $account->email }}</div>
                            </td>
                            <td class="p-3">{{ $account->role?->name ?: 'Chưa có vai trò' }}</td>
                            <td class="p-3">{{ $account->groups->pluck('name')->join(', ') ?: 'Không thuộc nhóm' }}</td>
                            <td class="p-3">{{ ['global' => 'Toàn hệ thống', 'group' => 'Theo nhóm', 'individual' => 'Cá nhân'][$account->view_permission] ?? $account->view_permission }}</td>
                            <td class="p-3">
                                <span class="rounded-full px-2 py-1 text-xs {{ $member?->receives_data ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                                    {{ $member?->receives_data ? 'Đang nhận' : 'Không nhận' }}
                                </span>
                            </td>
                            <td class="p-3">
                                <span class="rounded-full px-2 py-1 text-xs {{ $account->status ? 'bg-blue-100 text-blue-700' : 'bg-red-100 text-red-700' }}">
                                    {{ $account->status ? 'Hoạt động' : 'Đã khóa' }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="p-8 text-center text-gray-500">Chưa có tài khoản.</td></tr>
                    @endforelse
                </tbody>
            </table>

            <div class="p-4">{{ $accounts->links() }}</div>
        </div>
    </div>
</x-admin::layouts>

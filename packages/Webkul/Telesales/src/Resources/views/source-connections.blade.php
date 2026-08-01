<x-admin::layouts>
    <x-slot:title>
        Kết nối nguồn data
    </x-slot>

    @php
        $channelLabels = [
            'website' => ['Website / Landing page', 'icon-global', 'bg-sky-50 text-sky-700'],
            'facebook' => ['Facebook Lead Ads', 'icon-social-facebook', 'bg-blue-50 text-blue-700'],
            'api' => ['API hệ thống khác', 'icon-link', 'bg-violet-50 text-violet-700'],
            'other' => ['Nguồn khác', 'icon-organization', 'bg-gray-100 text-gray-700'],
        ];
    @endphp

    <div class="flex flex-col gap-5">
        <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h1 class="text-xl font-bold text-gray-900 dark:text-white">Kết nối nguồn data</h1>
                    <p class="mt-1 max-w-3xl text-sm text-gray-600 dark:text-gray-300">
                        Website, Facebook hoặc hệ thống ngoài gửi số điện thoại vào một endpoint chung. Data được chuẩn hóa, chống trùng và chia tự động cho Sale theo nhóm đã chọn.
                    </p>
                </div>

                <a href="{{ route('admin.leads.create') }}" class="secondary-button">Nhập data thủ công</a>
            </div>

            <div class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ($channelLabels as $key => [$label, $icon, $colors])
                    <button
                        type="button"
                        onclick="document.getElementById('channel').value='{{ $key }}'; document.getElementById('connection-name').focus();"
                        class="flex items-center gap-3 rounded-lg border border-gray-200 p-4 text-left transition hover:border-blue-400 hover:shadow-sm dark:border-gray-700"
                    >
                        <span class="flex h-10 w-10 items-center justify-center rounded-lg {{ $colors }}">
                            <span class="{{ $icon }} text-xl" aria-hidden="true"></span>
                        </span>
                        <span>
                            <span class="block font-semibold text-gray-900 dark:text-white">{{ $label }}</span>
                            <span class="text-xs text-gray-500">Tạo cấu hình</span>
                        </span>
                    </button>
                @endforeach
            </div>
        </section>

        @if ($revealedToken)
            <section class="rounded-xl border border-amber-300 bg-amber-50 p-5 dark:border-amber-700 dark:bg-amber-950/30">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 class="font-bold text-amber-900 dark:text-amber-100">Token mới: {{ $revealedToken['name'] }}</h2>
                        <p class="mt-1 text-sm text-amber-800 dark:text-amber-200">Token chỉ hiển thị lần này. Hãy sao chép và lưu ở hệ thống gửi data.</p>
                    </div>
                    <button type="button" class="secondary-button" onclick="copySourceValue('source-token')">Sao chép token</button>
                </div>
                <code id="source-token" class="mt-3 block overflow-x-auto rounded-lg bg-gray-950 p-3 text-sm text-green-300">{{ $revealedToken['token'] }}</code>
            </section>
        @endif

        @if ($errors->any())
            <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.telesales.source-connections.store') }}" class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            @csrf
            <div class="mb-4 flex items-center justify-between gap-3">
                <div>
                    <h2 class="font-bold text-gray-900 dark:text-white">Tạo nguồn nhận data</h2>
                    <p class="text-xs text-gray-500">Chỉ cần tên nguồn, loại kết nối và trường chứa số điện thoại.</p>
                </div>
                <span class="rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-700">Thiết lập khoảng 1 phút</span>
            </div>

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <label class="grid gap-1 text-sm text-gray-700 dark:text-gray-200">
                    Tên kết nối <span class="text-red-600">*</span>
                    <input id="connection-name" name="name" required value="{{ old('name') }}" placeholder="VD: Landing Phục Vị Khang" class="rounded-md border border-gray-300 px-3 py-2 dark:border-gray-700 dark:bg-gray-950">
                </label>

                <label class="grid gap-1 text-sm text-gray-700 dark:text-gray-200">
                    Loại nguồn <span class="text-red-600">*</span>
                    <select id="channel" name="channel" required class="rounded-md border border-gray-300 px-3 py-2 dark:border-gray-700 dark:bg-gray-950">
                        @foreach ($channelLabels as $value => [$label])
                            <option value="{{ $value }}" @selected(old('channel', 'website') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="grid gap-1 text-sm text-gray-700 dark:text-gray-200">
                    Nguồn CRM
                    <select name="source_id" class="rounded-md border border-gray-300 px-3 py-2 dark:border-gray-700 dark:bg-gray-950">
                        <option value="">Tự tạo theo loại nguồn</option>
                        @foreach ($sources as $source)
                            <option value="{{ $source->id }}" @selected((int) old('source_id') === $source->id)>{{ $source->name }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="grid gap-1 text-sm text-gray-700 dark:text-gray-200">
                    Hoặc tên nguồn mới
                    <input name="source_name" value="{{ old('source_name') }}" placeholder="VD: Zalo Ads" class="rounded-md border border-gray-300 px-3 py-2 dark:border-gray-700 dark:bg-gray-950">
                </label>

                <label class="grid gap-1 text-sm text-gray-700 dark:text-gray-200">
                    Nhóm Sale nhận data
                    <select name="group_id" class="rounded-md border border-gray-300 px-3 py-2 dark:border-gray-700 dark:bg-gray-950">
                        <option value="">Nhóm mặc định (khuyến nghị)</option>
                        @foreach ($groups as $group)
                            <option value="{{ $group->id }}" @selected((int) old('group_id') === $group->id)>{{ $group->name }}</option>
                        @endforeach
                    </select>
                </label>

                @if ($role === 'admin')
                    <label class="grid gap-1 text-sm text-gray-700 dark:text-gray-200">
                        Marketing phụ trách <span class="text-red-600">*</span>
                        <select name="marketing_owner_id" required class="rounded-md border border-gray-300 px-3 py-2 dark:border-gray-700 dark:bg-gray-950">
                            <option value="">Chọn Marketing</option>
                            @foreach ($marketingUsers as $marketing)
                                <option value="{{ $marketing->id }}" @selected((int) old('marketing_owner_id') === $marketing->id)>{{ $marketing->name }}</option>
                            @endforeach
                        </select>
                    </label>
                @endif

                <label class="grid gap-1 text-sm text-gray-700 dark:text-gray-200">
                    Chiến dịch
                    <input name="campaign" value="{{ old('campaign') }}" placeholder="VD: FB_PVK_01" class="rounded-md border border-gray-300 px-3 py-2 dark:border-gray-700 dark:bg-gray-950">
                </label>
            </div>

            <details class="mt-5 rounded-lg border border-gray-200 dark:border-gray-700">
                <summary class="cursor-pointer px-4 py-3 text-sm font-semibold text-blue-600">Ánh xạ trường dữ liệu ngoài</summary>
                <div class="grid gap-4 border-t border-gray-200 p-4 sm:grid-cols-2 xl:grid-cols-5 dark:border-gray-700">
                    @foreach ([
                        'map_phone' => ['Số điện thoại *', 'phone'],
                        'map_name' => ['Tên khách', 'name'],
                        'map_product' => ['Sản phẩm', 'product'],
                        'map_message' => ['Tin nhắn', 'message'],
                        'map_external_id' => ['Mã data ngoài', 'external_id'],
                    ] as $field => [$label, $default])
                        <label class="grid gap-1 text-xs text-gray-600 dark:text-gray-300">
                            {{ $label }}
                            <input name="{{ $field }}" value="{{ old($field, $default) }}" @required($field === 'map_phone') class="rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-950">
                        </label>
                    @endforeach
                </div>
            </details>

            <div class="mt-5 flex justify-end">
                <button class="primary-button">Tạo kết nối và token</button>
            </div>
        </form>

        <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="font-bold text-gray-900 dark:text-white">Thông tin gửi API</h2>
                    <p class="text-xs text-gray-500">Dùng endpoint này cho website, webhook trung gian hoặc công cụ tự động hóa.</p>
                </div>
                <button type="button" class="secondary-button" onclick="copySourceValue('source-endpoint')">Sao chép endpoint</button>
            </div>
            <code id="source-endpoint" class="mt-3 block overflow-x-auto rounded-lg bg-gray-950 p-3 text-sm text-cyan-300">POST {{ $endpoint }}</code>
            <pre class="mt-3 overflow-x-auto rounded-lg bg-gray-950 p-4 text-xs text-gray-200"><code>Authorization: Bearer TOKEN_CỦA_NGUỒN
Content-Type: application/json

{
  "phone": "0912345678",
  "name": "Nguyễn Văn A",
  "product": "Phục Vị Khang",
  "message": "Tôi cần tư vấn",
  "external_id": "fb-lead-123456"
}</code></pre>
        </section>

        <section>
            <div class="mb-3 flex items-center justify-between">
                <h2 class="text-lg font-bold text-gray-900 dark:text-white">Nguồn đã cấu hình ({{ $connections->count() }})</h2>
            </div>

            <div class="grid gap-4 xl:grid-cols-2">
                @forelse ($connections as $connection)
                    @php($mapping = $connection->field_mapping ?? [])
                    <article class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex min-w-0 items-center gap-3">
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg {{ $channelLabels[$connection->channel][2] ?? 'bg-gray-100 text-gray-700' }}">
                                    <span class="{{ $channelLabels[$connection->channel][1] ?? 'icon-link' }} text-xl"></span>
                                </span>
                                <div class="min-w-0">
                                    <h3 class="truncate font-bold text-gray-900 dark:text-white">{{ $connection->name }}</h3>
                                    <p class="text-xs text-gray-500">{{ $channelLabels[$connection->channel][0] ?? $connection->channel }} · {{ $connection->token_hint }}</p>
                                </div>
                            </div>
                            <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $connection->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                                {{ $connection->is_active ? 'Đang nhận data' : 'Đã tạm dừng' }}
                            </span>
                        </div>

                        <dl class="mt-4 grid grid-cols-2 gap-3 text-sm">
                            <div><dt class="text-xs text-gray-500">Marketing</dt><dd class="font-medium dark:text-white">{{ $connection->marketingOwner?->name ?: '—' }}</dd></div>
                            <div><dt class="text-xs text-gray-500">Nhóm Sale</dt><dd class="font-medium dark:text-white">{{ $connection->group?->name ?: 'Nhóm mặc định' }}</dd></div>
                            <div><dt class="text-xs text-gray-500">Nguồn CRM</dt><dd class="font-medium dark:text-white">{{ $connection->source?->name ?: '—' }}</dd></div>
                            <div><dt class="text-xs text-gray-500">Đã nhận / Trùng</dt><dd class="font-medium dark:text-white">{{ number_format($connection->received_count) }} / {{ number_format($connection->duplicate_count) }}</dd></div>
                        </dl>

                        <p class="mt-3 text-xs text-gray-500">Lần nhận gần nhất: {{ $connection->last_received_at?->format('d/m/Y H:i') ?: 'Chưa nhận data' }}</p>

                        <details class="mt-4 rounded-lg border border-gray-200 dark:border-gray-700">
                            <summary class="cursor-pointer px-3 py-2 text-sm font-semibold text-blue-600">Sửa cấu hình</summary>
                            <form method="POST" action="{{ route('admin.telesales.source-connections.update', $connection->id) }}" class="grid gap-3 border-t border-gray-200 p-3 sm:grid-cols-2 dark:border-gray-700">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="channel" value="{{ $connection->channel }}">
                                <label class="grid gap-1 text-xs text-gray-500">Tên<input name="name" required value="{{ $connection->name }}" class="rounded-md border px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-950"></label>
                                <label class="grid gap-1 text-xs text-gray-500">Nguồn CRM<select name="source_id" class="rounded-md border px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-950">@foreach ($sources as $source)<option value="{{ $source->id }}" @selected($connection->source_id === $source->id)>{{ $source->name }}</option>@endforeach</select></label>
                                <input type="hidden" name="source_name" value="">
                                <label class="grid gap-1 text-xs text-gray-500">Nhóm Sale<select name="group_id" class="rounded-md border px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-950"><option value="">Nhóm mặc định</option>@foreach ($groups as $group)<option value="{{ $group->id }}" @selected($connection->group_id === $group->id)>{{ $group->name }}</option>@endforeach</select></label>
                                @if ($role === 'admin')
                                    <label class="grid gap-1 text-xs text-gray-500">Marketing<select name="marketing_owner_id" required class="rounded-md border px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-950">@foreach ($marketingUsers as $marketing)<option value="{{ $marketing->id }}" @selected($connection->marketing_owner_id === $marketing->id)>{{ $marketing->name }}</option>@endforeach</select></label>
                                @endif
                                <label class="grid gap-1 text-xs text-gray-500">Chiến dịch<input name="campaign" value="{{ $connection->campaign }}" class="rounded-md border px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-950"></label>
                                @foreach (['map_phone' => 'phone', 'map_name' => 'name', 'map_product' => 'product', 'map_message' => 'message', 'map_external_id' => 'external_id'] as $field => $target)
                                    <label class="grid gap-1 text-xs text-gray-500">Trường {{ $target }}<input name="{{ $field }}" value="{{ $mapping[$target] ?? $target }}" @required($target === 'phone') class="rounded-md border px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-950"></label>
                                @endforeach
                                <div class="sm:col-span-2 flex justify-end"><button class="primary-button">Lưu cấu hình</button></div>
                            </form>
                        </details>

                        <div class="mt-4 flex flex-wrap justify-end gap-2">
                            <form method="POST" action="{{ route('admin.telesales.source-connections.toggle', $connection->id) }}">
                                @csrf
                                @method('PATCH')
                                <button class="secondary-button">{{ $connection->is_active ? 'Tạm dừng' : 'Bật nhận data' }}</button>
                            </form>
                            <form method="POST" action="{{ route('admin.telesales.source-connections.regenerate', $connection->id) }}" onsubmit="return confirm('Token cũ sẽ ngừng hoạt động. Tiếp tục?')">
                                @csrf
                                <button class="secondary-button">Cấp lại token</button>
                            </form>
                        </div>
                    </article>
                @empty
                    <div class="col-span-full rounded-xl border border-dashed border-gray-300 bg-white p-10 text-center text-sm text-gray-500 dark:border-gray-700 dark:bg-gray-900">
                        Chưa có nguồn tự động. Hãy tạo kết nối đầu tiên ở biểu mẫu phía trên.
                    </div>
                @endforelse
            </div>
        </section>
    </div>

    @pushOnce('scripts')
        <script>
            function copySourceValue(id) {
                navigator.clipboard.writeText(document.getElementById(id).textContent.trim());
            }
        </script>
    @endPushOnce
</x-admin::layouts>

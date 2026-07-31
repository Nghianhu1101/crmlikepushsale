<x-admin::layouts>
    <x-slot:title>
        Bảng xếp hạng telesale
    </x-slot>

    @php
        $money = fn ($value) => number_format((int) $value, 0, ',', '.').' ₫';
        $filterQuery = collect(request()->query())->except('page')->all();
        $isAdmin = $role === 'admin';
        $perspectiveLabel = $filters['perspective'] === 'marketing' ? 'Marketing' : 'Sale';
        $hasAdvancedFilter = filled($filters['product_id'])
            || filled($filters['source_id'])
            || filled($filters['campaign'])
            || filled($filters['group_id'])
            || filled($filters['sales_owner_id'])
            || filled($filters['marketing_owner_id'])
            || filled($filters['status']);
    @endphp

    @pushOnce('styles')
    <style>
        .ps-ranking-shell {
            --ps-blue: #347cf0;
            --ps-blue-dark: #2168d4;
            --ps-line: #eef1f5;
        }

        .ps-filter-control {
            height: 38px;
            min-width: 180px;
            border: 1px solid #d8dee8;
            border-radius: 3px;
            background: #fff;
            padding: 0 11px;
            color: #374151;
            font-size: 13px;
            outline: none;
        }

        .ps-filter-control:focus {
            border-color: var(--ps-blue);
            box-shadow: 0 0 0 2px rgba(52, 124, 240, .12);
        }

        .ps-filter-main {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .ps-filter-grid {
            display: grid;
            flex: 1;
            grid-template-columns: minmax(0, 1fr);
            gap: 8px;
        }

        .ps-date-range {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 4px;
        }

        .ps-filter-actions {
            display: flex;
            gap: 8px;
        }

        .ps-search-button {
            display: inline-flex;
            height: 38px;
            flex: 1;
            align-items: center;
            justify-content: center;
            gap: 7px;
            border-radius: 3px;
            background: var(--ps-blue);
            padding: 0 16px;
            color: #fff;
            font-size: 13px;
            font-weight: 700;
            transition: background .15s ease;
        }

        .ps-search-button:hover {
            background: var(--ps-blue-dark);
        }

        .ps-reset-button {
            display: inline-flex;
            width: 38px;
            height: 38px;
            flex: 0 0 38px;
            align-items: center;
            justify-content: center;
            border: 1px solid #d8dee8;
            border-radius: 3px;
            background: #fff;
            color: #536074;
            font-size: 18px;
        }

        .ps-desktop-ranking {
            display: none;
        }

        .ps-mobile-ranking {
            display: grid;
        }

        .ps-mobile-rank-index {
            display: flex;
            width: 36px;
            height: 36px;
            flex: 0 0 36px;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
        }

        .ps-mobile-avatar {
            display: flex;
            width: 44px;
            height: 44px;
            flex: 0 0 44px;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            border: 2px solid #dbeafe;
            border-radius: 999px;
            background: #eff6ff;
            color: #2563eb;
            font-weight: 700;
        }

        .ps-rank-stage {
            position: relative;
            height: 555px;
            min-width: 980px;
            overflow: hidden;
            background:
                radial-gradient(circle at 93% 12%, rgba(52, 124, 240, .055), transparent 19%),
                #fff;
        }

        .ps-rank-line {
            position: absolute;
            left: 2%;
            right: 2%;
            bottom: 18%;
            height: 7px;
            border-radius: 999px;
            background: var(--ps-line);
            transform: rotate(-14deg);
            transform-origin: left center;
        }

        .ps-ranker {
            position: absolute;
            z-index: 2;
            width: 148px;
            text-align: center;
            transform: translateX(-50%);
        }

        .ps-avatar-wrap {
            position: relative;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 92px;
            height: 92px;
            border: 5px solid #edf0f4;
            border-radius: 999px;
            background: #fff;
            box-shadow: 0 3px 12px rgba(15, 23, 42, .12);
        }

        .ps-avatar-wrap.rank-1 {
            width: 112px;
            height: 112px;
            border-color: #29a4ec;
            box-shadow: 0 0 0 5px rgba(41, 164, 236, .13), 0 8px 22px rgba(15, 23, 42, .17);
        }

        .ps-avatar-wrap.rank-2 {
            width: 102px;
            height: 102px;
            border-color: #f1c15d;
        }

        .ps-avatar-wrap.rank-3 {
            width: 98px;
            height: 98px;
            border-color: #b8c4d4;
        }

        .ps-avatar {
            display: flex;
            width: 100%;
            height: 100%;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            border-radius: inherit;
            background: linear-gradient(145deg, #dbeafe, #eff6ff 48%, #bfdbfe);
            color: #2563eb;
            font-size: 29px;
            font-weight: 800;
        }

        .ps-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .ps-crown {
            position: absolute;
            top: -43px;
            left: 50%;
            color: #f7b928;
            font-size: 42px;
            line-height: 1;
            filter: drop-shadow(0 3px 2px rgba(180, 120, 0, .2));
            transform: translateX(-50%);
        }

        .ps-rank-number {
            margin-top: 9px;
            color: #253247;
            font-size: 15px;
            font-weight: 800;
        }

        .ps-ranker.rank-1 .ps-rank-number,
        .ps-ranker.rank-1 .ps-rank-name,
        .ps-ranker.rank-1 .ps-rank-revenue {
            color: #2087df;
        }

        .ps-rank-name {
            margin-top: 5px;
            overflow: hidden;
            color: #364152;
            font-size: 12px;
            font-weight: 600;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .ps-rank-revenue {
            margin-top: 3px;
            color: #536074;
            font-size: 11px;
            font-weight: 600;
        }

        .ps-report-table th,
        .ps-report-table td {
            border: 1px solid #d8dee8;
        }

        .ps-report-table thead th {
            border-color: rgba(255, 255, 255, .2);
            background: #477fd2;
            color: #fff;
            text-align: center;
            vertical-align: middle;
        }

        .ps-report-table tbody tr:nth-child(even) {
            background: #f8fafc;
        }

        .ps-report-table tbody tr:hover {
            background: #edf5ff;
        }

        @media (max-width: 1023px) {
            .ps-filter-control {
                width: 100%;
                min-width: 0;
            }
        }

        @media (min-width: 640px) {
            .ps-filter-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (min-width: 1024px) {
            .ps-filter-main {
                flex-direction: row;
                align-items: center;
            }

            .ps-desktop-ranking {
                display: block !important;
            }

            .ps-mobile-ranking {
                display: none !important;
            }
        }

        @media (min-width: 1280px) {
            .ps-filter-grid {
                grid-template-columns: minmax(210px, 1.25fr) minmax(190px, 1fr) minmax(180px, 1fr) auto;
            }

            .ps-search-button {
                flex: 0 0 auto;
            }
        }
    </style>
    @endPushOnce

    <div class="ps-ranking-shell flex flex-col gap-4">
        <form
            method="GET"
            action="{{ route('admin.telesales.rankings.index') }}"
            class="rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900"
        >
            <input type="hidden" name="perspective" value="{{ $filters['perspective'] }}">

            <div class="ps-filter-main px-4 py-3">
                <div class="flex min-w-fit items-center justify-between gap-3 lg:mr-3">
                    <div>
                        <h1 class="text-lg font-bold text-gray-900 dark:text-white">Bảng xếp hạng</h1>
                        <p class="text-xs text-gray-500">Hiệu suất {{ $perspectiveLabel }}</p>
                    </div>

                    @if ($isAdmin)
                        <div class="flex rounded-md bg-gray-100 p-1 dark:bg-gray-800">
                            <a
                                href="{{ route('admin.telesales.rankings.index', array_merge($filterQuery, ['perspective' => 'sale'])) }}"
                                class="rounded px-3 py-1.5 text-xs font-semibold {{ $filters['perspective'] === 'sale' ? 'bg-white text-blue-600 shadow-sm dark:bg-gray-700' : 'text-gray-500' }}"
                            >
                                Sale
                            </a>
                            <a
                                href="{{ route('admin.telesales.rankings.index', array_merge($filterQuery, ['perspective' => 'marketing'])) }}"
                                class="rounded px-3 py-1.5 text-xs font-semibold {{ $filters['perspective'] === 'marketing' ? 'bg-white text-blue-600 shadow-sm dark:bg-gray-700' : 'text-gray-500' }}"
                            >
                                Marketing
                            </a>
                        </div>
                    @endif
                </div>

                <div class="ps-filter-grid">
                    <div class="ps-date-range">
                        <input
                            type="date"
                            name="start_date"
                            value="{{ $filters['start_date'] }}"
                            aria-label="Từ ngày"
                            class="ps-filter-control !min-w-0"
                        >
                        <input
                            type="date"
                            name="end_date"
                            value="{{ $filters['end_date'] }}"
                            aria-label="Đến ngày"
                            class="ps-filter-control !min-w-0"
                        >
                    </div>

                    <select name="revenue_basis" class="ps-filter-control">
                        <option value="net" @selected($filters['revenue_basis'] === 'net')>Doanh thu sau chiết khấu</option>
                        <option value="gross" @selected($filters['revenue_basis'] === 'gross')>Doanh thu trước chiết khấu</option>
                    </select>

                    <select name="customer_type" class="ps-filter-control">
                        <option value="all" @selected($filters['customer_type'] === 'all')>Tất cả khách hàng</option>
                        <option value="new" @selected($filters['customer_type'] === 'new')>Khách hàng mới</option>
                        <option value="old" @selected($filters['customer_type'] === 'old')>Khách hàng cũ</option>
                    </select>

                    <div class="ps-filter-actions">
                        <button
                            type="submit"
                            class="ps-search-button"
                        >
                            <span aria-hidden="true">⌕</span>
                            Tìm kiếm
                        </button>

                        <a
                            href="{{ route('admin.telesales.rankings.index', ['perspective' => $filters['perspective']]) }}"
                            class="ps-reset-button"
                            title="Đặt lại bộ lọc"
                            aria-label="Đặt lại bộ lọc"
                        >
                            ↻
                        </a>
                    </div>
                </div>
            </div>

            <details class="border-t border-gray-100 dark:border-gray-800" @if ($hasAdvancedFilter) open @endif>
                <summary class="cursor-pointer select-none px-4 py-2 text-xs font-semibold text-blue-600">
                    Bộ lọc nâng cao
                </summary>

                <div class="grid grid-cols-1 gap-3 border-t border-gray-100 px-4 py-3 sm:grid-cols-2 lg:grid-cols-4 dark:border-gray-800">
                    <label class="grid gap-1 text-xs text-gray-600 dark:text-gray-300">
                        Căn ngày theo
                        <select name="date_basis" class="ps-filter-control">
                            <option value="order_closed" @selected($filters['date_basis'] === 'order_closed')>Ngày chốt đơn</option>
                            <option value="data_received" @selected($filters['date_basis'] === 'data_received')>Ngày nhận data</option>
                        </select>
                    </label>

                    <label class="grid gap-1 text-xs text-gray-600 dark:text-gray-300">
                        Sản phẩm
                        <select name="product_id" class="ps-filter-control">
                            <option value="">Tất cả sản phẩm</option>
                            @foreach ($products as $product)
                                <option value="{{ $product->id }}" @selected((int) $filters['product_id'] === $product->id)>{{ $product->name }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label class="grid gap-1 text-xs text-gray-600 dark:text-gray-300">
                        Nguồn data
                        <select name="source_id" class="ps-filter-control">
                            <option value="">Tất cả nguồn</option>
                            @foreach ($sources as $source)
                                <option value="{{ $source->id }}" @selected((int) $filters['source_id'] === $source->id)>{{ $source->name }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label class="grid gap-1 text-xs text-gray-600 dark:text-gray-300">
                        Chiến dịch
                        <select name="campaign" class="ps-filter-control">
                            <option value="">Tất cả chiến dịch</option>
                            @foreach ($campaigns as $campaign)
                                <option value="{{ $campaign }}" @selected($filters['campaign'] === $campaign)>{{ $campaign }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label class="grid gap-1 text-xs text-gray-600 dark:text-gray-300">
                        Trạng thái đơn
                        <select name="status" class="ps-filter-control">
                            <option value="">Tất cả trạng thái</option>
                            @foreach (config('telesales.order_statuses') as $value => $label)
                                <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>

                    @if ($isAdmin)
                        <label class="grid gap-1 text-xs text-gray-600 dark:text-gray-300">
                            Nhóm sale
                            <select name="group_id" class="ps-filter-control">
                                <option value="">Tất cả nhóm</option>
                                @foreach ($groups as $group)
                                    <option value="{{ $group->id }}" @selected((int) $filters['group_id'] === $group->id)>{{ $group->name }}</option>
                                @endforeach
                            </select>
                        </label>

                        <label class="grid gap-1 text-xs text-gray-600 dark:text-gray-300">
                            Sale
                            <select name="sales_owner_id" class="ps-filter-control">
                                <option value="">Tất cả sale</option>
                                @foreach ($salesUsers as $sale)
                                    <option value="{{ $sale->id }}" @selected((int) $filters['sales_owner_id'] === $sale->id)>{{ $sale->name }}</option>
                                @endforeach
                            </select>
                        </label>

                        <label class="grid gap-1 text-xs text-gray-600 dark:text-gray-300">
                            Marketing
                            <select name="marketing_owner_id" class="ps-filter-control">
                                <option value="">Tất cả marketing</option>
                                @foreach ($marketingUsers as $marketing)
                                    <option value="{{ $marketing->id }}" @selected((int) $filters['marketing_owner_id'] === $marketing->id)>{{ $marketing->name }}</option>
                                @endforeach
                            </select>
                        </label>
                    @endif
                </div>
            </details>
        </form>

        <section
            class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900"
            aria-labelledby="ranking-title"
        >
            <div class="flex items-center justify-between border-b border-gray-100 px-4 py-3 dark:border-gray-800">
                <div>
                    <h2 id="ranking-title" class="font-bold text-gray-900 dark:text-white">Top 10 {{ $perspectiveLabel }}</h2>
                    <p class="mt-0.5 text-xs text-gray-500">
                        {{ \Carbon\Carbon::parse($filters['start_date'])->format('d/m/Y') }}
                        -
                        {{ \Carbon\Carbon::parse($filters['end_date'])->format('d/m/Y') }}
                    </p>
                </div>

                <div class="text-right">
                    <div class="text-xs text-gray-500">Tổng doanh thu</div>
                    <div class="text-base font-bold text-blue-600">
                        {{ $money($filters['revenue_basis'] === 'gross' ? $summary->gross_revenue : $summary->net_revenue) }}
                    </div>
                </div>
            </div>

            @if ($rankings->isEmpty())
                <div class="flex min-h-[360px] items-center justify-center px-4 text-center text-sm text-gray-500">
                    Chưa có doanh thu trong khoảng thời gian và bộ lọc đã chọn.
                </div>
            @else
                <div class="ps-desktop-ranking overflow-x-auto" data-testid="desktop-ranking">
                    <div class="ps-rank-stage">
                        <div class="ps-rank-line" aria-hidden="true"></div>

                        @foreach ($rankings as $person)
                            @php
                                $left = 6 + (10 - $person->rank) * 9.7;
                                $bottom = 4 + (10 - $person->rank) * 6.05;
                                $initials = collect(preg_split('/\s+/u', trim($person->name)))
                                    ->filter()
                                    ->take(-2)
                                    ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
                                    ->join('');
                                $avatarUrl = $person->image ? Storage::url($person->image) : null;
                            @endphp

                            <article
                                class="ps-ranker rank-{{ $person->rank }}"
                                style="left: {{ $left }}%; bottom: {{ $bottom }}%;"
                                aria-label="Hạng {{ $person->rank }}: {{ $person->name }}"
                            >
                                <div class="ps-avatar-wrap rank-{{ $person->rank }}">
                                    @if ($person->rank === 1)
                                        <span class="ps-crown" aria-hidden="true">♛</span>
                                    @endif

                                    <div class="ps-avatar">
                                        @if ($avatarUrl)
                                            <img src="{{ $avatarUrl }}" alt="{{ $person->name }}">
                                        @else
                                            <span>{{ $initials ?: '?' }}</span>
                                        @endif
                                    </div>
                                </div>

                                <div class="ps-rank-number">{{ $person->rank }}</div>
                                <div class="ps-rank-name" title="{{ $person->name }}">{{ $person->name }}</div>
                                <div class="ps-rank-revenue">{{ $money($person->revenue) }}</div>
                            </article>
                        @endforeach
                    </div>
                </div>

                <div class="ps-mobile-ranking gap-2 p-3" data-testid="mobile-ranking">
                    @foreach ($rankings as $person)
                        @php
                            $initials = collect(preg_split('/\s+/u', trim($person->name)))
                                ->filter()
                                ->take(-2)
                                ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
                                ->join('');
                            $avatarUrl = $person->image ? Storage::url($person->image) : null;
                        @endphp

                        <article class="flex items-center gap-3 rounded-lg border border-gray-200 bg-white p-3 dark:border-gray-800 dark:bg-gray-900">
                            <div class="ps-mobile-rank-index text-sm font-black {{ $person->rank === 1 ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-white' }}">
                                {{ $person->rank }}
                            </div>

                            <div class="ps-mobile-avatar">
                                @if ($avatarUrl)
                                    <img class="h-full w-full object-cover" src="{{ $avatarUrl }}" alt="{{ $person->name }}">
                                @else
                                    {{ $initials ?: '?' }}
                                @endif
                            </div>

                            <div class="min-w-0 flex-1">
                                <div class="truncate text-sm font-semibold text-gray-900 dark:text-white">{{ $person->name }}</div>
                                <div class="mt-0.5 text-xs text-gray-500">{{ $person->order_count }} đơn · {{ $person->conversion_rate }}% chốt</div>
                            </div>

                            <div class="whitespace-nowrap text-sm font-bold text-blue-600">{{ $money($person->revenue) }}</div>
                        </article>
                    @endforeach
                </div>
            @endif
        </section>

        <section class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="flex flex-wrap items-center justify-between gap-2 px-4 py-3">
                <div>
                    <h2 class="font-bold text-gray-900 dark:text-white">Chi tiết hiệu suất</h2>
                    <p class="mt-0.5 text-xs text-gray-500">Số liệu tổng hợp theo {{ mb_strtolower($perspectiveLabel) }}</p>
                </div>

                <a href="{{ route('admin.telesales.orders.index') }}" class="text-xs font-semibold text-blue-600 hover:underline">
                    Xem đơn hàng →
                </a>
            </div>

            <div class="max-w-full overflow-x-auto">
                <table class="ps-report-table w-full min-w-[1480px] border-collapse text-xs text-gray-700 dark:text-gray-200">
                    <thead>
                        <tr>
                            <th rowspan="2" class="w-12 px-2 py-3">STT</th>
                            <th rowspan="2" class="min-w-[190px] px-3 py-3">{{ mb_strtoupper($perspectiveLabel) }}</th>
                            <th colspan="2" class="px-3 py-2">KHÁCH HÀNG MỚI</th>
                            <th colspan="2" class="px-3 py-2">KHÁCH HÀNG CŨ</th>
                            <th colspan="5" class="px-3 py-2">HIỆU SUẤT</th>
                            <th colspan="3" class="px-3 py-2">TỔNG CHUNG</th>
                        </tr>
                        <tr>
                            <th class="min-w-[90px] px-2 py-2">Số khách</th>
                            <th class="min-w-[135px] px-2 py-2">Doanh thu</th>
                            <th class="min-w-[90px] px-2 py-2">Số khách</th>
                            <th class="min-w-[135px] px-2 py-2">Doanh thu</th>
                            <th class="min-w-[80px] px-2 py-2">Liên hệ</th>
                            <th class="min-w-[90px] px-2 py-2">Đã liên hệ</th>
                            <th class="min-w-[85px] px-2 py-2">Chốt đơn</th>
                            <th class="min-w-[80px] px-2 py-2">% chốt</th>
                            <th class="min-w-[70px] px-2 py-2">Số SP</th>
                            <th class="min-w-[145px] px-2 py-2">Doanh thu gộp</th>
                            <th class="min-w-[120px] px-2 py-2">Chiết khấu</th>
                            <th class="min-w-[150px] px-2 py-2">Sau chiết khấu</th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr class="bg-gray-50 font-bold text-gray-900 dark:bg-gray-950 dark:text-white">
                            <td class="px-2 py-2 text-center">0</td>
                            <td class="px-3 py-2 text-center">Tổng</td>
                            <td class="px-2 py-2 text-center">{{ number_format((int) $summary->new_customers, 0, ',', '.') }}</td>
                            <td class="whitespace-nowrap px-3 py-2 text-right">{{ $money($summary->new_customer_revenue) }}</td>
                            <td class="px-2 py-2 text-center">{{ number_format((int) $summary->old_customers, 0, ',', '.') }}</td>
                            <td class="whitespace-nowrap px-3 py-2 text-right">{{ $money($summary->old_customer_revenue) }}</td>
                            <td class="px-2 py-2 text-center">{{ number_format((int) $summary->data_count, 0, ',', '.') }}</td>
                            <td class="px-2 py-2 text-center">{{ number_format((int) $summary->contacted_count, 0, ',', '.') }}</td>
                            <td class="px-2 py-2 text-center">{{ number_format((int) $summary->order_count, 0, ',', '.') }}</td>
                            <td class="px-2 py-2 text-center">{{ $summary->conversion_rate }}%</td>
                            <td class="px-2 py-2 text-center">{{ number_format((int) $summary->product_count, 0, ',', '.') }}</td>
                            <td class="whitespace-nowrap px-3 py-2 text-right">{{ $money($summary->gross_revenue) }}</td>
                            <td class="whitespace-nowrap px-3 py-2 text-right">{{ $money($summary->discount_amount) }}</td>
                            <td class="whitespace-nowrap bg-green-100 px-3 py-2 text-right text-green-800">{{ $money($summary->net_revenue) }}</td>
                        </tr>

                        @forelse ($paginator as $index => $person)
                            <tr>
                                <td class="px-2 py-2 text-center">{{ $paginator->firstItem() + $index }}</td>
                                <td class="px-3 py-2 font-semibold text-gray-900 dark:text-white">{{ $person->name }}</td>
                                <td class="px-2 py-2 text-center">{{ number_format((int) $person->new_customers, 0, ',', '.') }}</td>
                                <td class="whitespace-nowrap px-3 py-2 text-right">{{ $money($person->new_customer_revenue) }}</td>
                                <td class="px-2 py-2 text-center">{{ number_format((int) $person->old_customers, 0, ',', '.') }}</td>
                                <td class="whitespace-nowrap px-3 py-2 text-right">{{ $money($person->old_customer_revenue) }}</td>
                                <td class="px-2 py-2 text-center">{{ number_format((int) $person->data_count, 0, ',', '.') }}</td>
                                <td class="px-2 py-2 text-center">{{ number_format((int) $person->contacted_count, 0, ',', '.') }}</td>
                                <td class="px-2 py-2 text-center">{{ number_format((int) $person->order_count, 0, ',', '.') }}</td>
                                <td class="px-2 py-2 text-center">{{ $person->conversion_rate }}%</td>
                                <td class="px-2 py-2 text-center">{{ number_format((int) $person->product_count, 0, ',', '.') }}</td>
                                <td class="whitespace-nowrap px-3 py-2 text-right">{{ $money($person->gross_revenue) }}</td>
                                <td class="whitespace-nowrap px-3 py-2 text-right">{{ $money($person->discount_amount) }}</td>
                                <td class="whitespace-nowrap bg-green-50 px-3 py-2 text-right font-bold text-green-700">{{ $money($person->net_revenue) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="14" class="px-4 py-8 text-center text-sm text-gray-500">
                                    Chưa có dữ liệu phù hợp với bộ lọc.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($paginator->hasPages())
                <div class="border-t border-gray-100 px-4 py-3 dark:border-gray-800">
                    {{ $paginator->links() }}
                </div>
            @endif
        </section>
    </div>
</x-admin::layouts>

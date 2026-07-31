@php
    $telesalesNotifications = auth()->guard('user')->check()
        ? \Webkul\Telesales\Models\Notification::query()
            ->where('user_id', auth()->guard('user')->id())
            ->where(function ($query) {
                $query->whereNull('available_at')
                    ->orWhere('available_at', '<=', now());
            })
            ->latest()
            ->limit(10)
            ->get()
        : collect();
    $unreadTelesalesNotifications = $telesalesNotifications->whereNull('read_at')->count();
@endphp

@if (auth()->guard('user')->check())
    <x-admin::dropdown position="bottom-right">
        <x-slot:toggle>
            <button class="relative rounded-md p-1.5 text-2xl hover:bg-gray-100 dark:hover:bg-gray-950">
                <span class="icon-notification"></span>
                @if ($unreadTelesalesNotifications)
                    <span class="absolute right-0 top-0 rounded-full bg-red-500 px-1 text-[10px] text-white">
                        {{ $unreadTelesalesNotifications }}
                    </span>
                @endif
            </button>
        </x-slot>

        <x-slot:content class="mt-2 w-80 !p-0">
            <div class="border-b border-gray-200 p-3 font-semibold dark:border-gray-800 dark:text-white">
                Data mới
            </div>

            @forelse ($telesalesNotifications as $notification)
                <a
                    href="{{ route('admin.telesales.notifications.open', $notification->id) }}"
                    class="block border-b border-gray-100 p-3 text-sm hover:bg-gray-50 dark:border-gray-800 dark:text-white dark:hover:bg-gray-950 {{ $notification->read_at ? '' : 'font-semibold' }}"
                >
                    <div>{{ $notification->title }}</div>
                    <div class="mt-1 text-xs text-gray-500">{{ $notification->body }}</div>
                </a>
            @empty
                <p class="p-3 text-sm text-gray-500">Chưa có thông báo.</p>
            @endforelse
        </x-slot>
    </x-admin::dropdown>
@endif

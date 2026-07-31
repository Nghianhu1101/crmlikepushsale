@php
    $workflowUser = auth()->guard('user')->user();
    $workflowRole = app(\Webkul\Telesales\Services\WorkflowNavigationService::class)->role($workflowUser);
    $workflowRoleLabels = [
        'admin' => 'Quản trị',
        'marketing' => 'Marketing',
        'sale' => 'Sale',
        'leader' => 'Trưởng nhóm',
    ];
@endphp

<header class="workflow-topbar sticky top-0 z-[10001] flex min-h-[60px] items-center justify-between gap-2 px-4 py-2 transition-all">
    <div class="flex min-w-0 items-center gap-3">
        <x-admin::layouts.sidebar.mobile />

        <a
            href="{{ route('admin.dashboard.index') }}"
            class="min-w-0 text-white"
        >
            <p class="truncate text-sm font-bold sm:text-base">
                {{ config('telesales.workflow.company_name') }}
            </p>

            <p class="mt-0.5 text-[11px] text-blue-100">
                {{ $workflowRoleLabels[$workflowRole] ?? 'Nhân viên' }}
                · {{ $workflowUser->name }}
            </p>
        </a>
    </div>

    <div class="flex items-center gap-1.5 max-md:hidden">
        @include('admin::components.layouts.header.desktop.mega-search')
        @include('admin::components.layouts.header.quick-creation')
    </div>

    <div class="flex items-center gap-2 text-white">
        @include('telesales::notifications')

        <div class="md:hidden">
            @include('admin::components.layouts.header.mobile.mega-search')
        </div>

        <a
            href="{{ route('admin.help.index') }}"
            class="flex rounded-lg p-1.5 text-2xl text-white transition hover:bg-white/15"
            title="{{ __('admin::app.help.index.title') }}"
        >
            <span class="icon-help" aria-hidden="true"></span>
            <span class="sr-only">{{ __('admin::app.help.index.title') }}</span>
        </a>

        <v-dark>
            <span
                class="{{ request()->cookie('dark_mode') ? 'icon-light' : 'icon-dark' }} cursor-pointer rounded-lg p-1.5 text-2xl text-white transition hover:bg-white/15"
            ></span>
        </v-dark>

        <div class="md:hidden">
            @include('admin::components.layouts.header.quick-creation')
        </div>

        <x-admin::dropdown position="bottom-{{ in_array(app()->getLocale(), ['fa', 'ar']) ? 'left' : 'right' }}">
            <x-slot:toggle>
                @if ($workflowUser->image)
                    <button class="flex h-9 w-9 cursor-pointer overflow-hidden rounded-full border-2 border-white/70 hover:opacity-90">
                        <img
                            src="{{ $workflowUser->image_url }}"
                            class="h-full w-full object-cover"
                            alt="{{ $workflowUser->name }}"
                        />
                    </button>
                @else
                    <button class="flex h-9 w-9 cursor-pointer items-center justify-center rounded-full border-2 border-white/70 bg-white/20 font-semibold text-white">
                        {{ mb_substr($workflowUser->name, 0, 1) }}
                    </button>
                @endif
            </x-slot>

            <x-slot:content class="mt-2 border-t-0 !p-0">
                <div class="border-b border-gray-200 px-5 py-3 dark:border-gray-800">
                    <p class="font-semibold text-gray-900 dark:text-white">
                        {{ $workflowUser->name }}
                    </p>

                    <p class="text-xs text-gray-500">
                        {{ $workflowUser->email }}
                    </p>
                </div>

                <div class="grid gap-1 pb-2.5">
                    <a
                        class="cursor-pointer px-5 py-2 text-base text-gray-800 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-950"
                        href="{{ route('admin.user.account.edit') }}"
                    >
                        @lang('admin::app.layouts.my-account')
                    </a>

                    <x-admin::form
                        method="DELETE"
                        action="{{ route('admin.session.destroy') }}"
                        id="adminLogout"
                    />

                    <a
                        class="cursor-pointer px-5 py-2 text-base text-gray-800 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-950"
                        href="{{ route('admin.session.destroy') }}"
                        onclick="event.preventDefault(); document.getElementById('adminLogout').submit();"
                    >
                        @lang('admin::app.layouts.sign-out')
                    </a>
                </div>
            </x-slot>
        </x-admin::dropdown>
    </div>
</header>

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-dark-template"
    >
        <div class="flex">
            <span
                class="cursor-pointer rounded-lg p-1.5 text-2xl text-white transition hover:bg-white/15"
                :class="[isDarkMode ? 'icon-light' : 'icon-dark']"
                @click="toggle"
            ></span>
        </div>
    </script>

    <script type="module">
        app.component('v-dark', {
            template: '#v-dark-template',

            data() {
                return {
                    isDarkMode: {{ request()->cookie('dark_mode') ?? 0 }},
                };
            },

            methods: {
                toggle() {
                    this.isDarkMode = parseInt(this.isDarkModeCookie()) ? 0 : 1;

                    const expiryDate = new Date();
                    expiryDate.setMonth(expiryDate.getMonth() + 1);

                    document.cookie = 'dark_mode=' + this.isDarkMode + '; path=/; expires=' + expiryDate.toGMTString();
                    document.documentElement.classList.toggle('dark', this.isDarkMode === 1);
                    this.$emitter.emit('change-theme', this.isDarkMode ? 'dark' : 'light');
                },

                isDarkModeCookie() {
                    const cookies = document.cookie.split(';');

                    for (const cookie of cookies) {
                        const [name, value] = cookie.trim().split('=');

                        if (name === 'dark_mode') {
                            return value;
                        }
                    }

                    return 0;
                },
            },
        });
    </script>
@endPushOnce

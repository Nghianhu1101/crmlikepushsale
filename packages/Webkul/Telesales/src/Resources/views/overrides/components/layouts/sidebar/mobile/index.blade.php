<v-workflow-sidebar-drawer>
    <i class="icon-menu cursor-pointer rounded-lg p-1.5 text-2xl text-white transition hover:bg-white/15 lg:hidden"></i>
</v-workflow-sidebar-drawer>

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-workflow-sidebar-drawer-template"
    >
        <x-admin::drawer
            position="left"
            width="320px"
            class="lg:hidden [&>:nth-child(3)]:!m-0 [&>:nth-child(3)]:!rounded-l-none [&>:nth-child(3)]:max-sm:!w-[88%]"
        >
            <x-slot:toggle>
                <i class="icon-menu cursor-pointer rounded-lg p-1.5 text-2xl text-white transition hover:bg-white/15 lg:hidden"></i>
            </x-slot>

            <x-slot:header>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-blue-600">
                        Workflow
                    </p>

                    <p class="mt-1 text-base font-bold text-slate-900 dark:text-white">
                        {{ config('telesales.workflow.company_name') }}
                    </p>
                </div>
            </x-slot>

            <x-slot:content class="!p-0">
                <div class="journal-scroll h-[calc(100vh-88px)] overflow-y-auto px-3 py-4">
                    @include('telesales::workflow.navigation')
                </div>
            </x-slot>
        </x-admin::drawer>
    </script>

    <script type="module">
        app.component('v-workflow-sidebar-drawer', {
            template: '#v-workflow-sidebar-drawer-template',
        });
    </script>
@endPushOnce

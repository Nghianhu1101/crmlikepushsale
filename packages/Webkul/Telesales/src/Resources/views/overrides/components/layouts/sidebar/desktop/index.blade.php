<aside
    ref="sidebar"
    class="workflow-sidebar fixed top-[60px] z-[10002] h-[calc(100vh-60px)] w-[300px] overflow-hidden border-r border-slate-200 bg-white transition-all duration-200 dark:border-gray-800 dark:bg-gray-900 max-lg:hidden"
    @mouseover="handleMouseOver"
    @mouseleave="handleMouseLeave"
>
    <div class="journal-scroll h-full overflow-y-auto overflow-x-hidden py-3">
        @include('telesales::workflow.navigation')
    </div>
</aside>

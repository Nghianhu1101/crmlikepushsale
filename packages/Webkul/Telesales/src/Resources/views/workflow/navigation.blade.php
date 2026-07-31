@php
    $workflowUser = auth()->guard('user')->user();
    $workflowNavigation = app(\Webkul\Telesales\Services\WorkflowNavigationService::class);
    $workflowModules = $workflowNavigation->forUser($workflowUser);
@endphp

<nav
    class="workflow-navigation"
    aria-label="Điều hướng nghiệp vụ"
>
    @foreach ($workflowModules as $module)
        <details
            class="workflow-module {{ $module['active'] ? 'is-active' : '' }}"
            data-workflow-module="{{ $module['key'] }}"
            @if ($module['active']) open @endif
        >
            <summary class="workflow-module-summary">
                <span class="{{ $module['icon'] }} workflow-module-icon"></span>

                <span class="workflow-module-title">
                    {{ $module['number'] }}. {{ $module['label'] }}
                </span>

                <span class="workflow-module-toggle" aria-hidden="true"></span>
            </summary>

            <div class="workflow-children">
                @foreach ($module['children'] as $child)
                    @if ($child['ready'])
                        <a
                            href="{{ $child['url'] }}"
                            class="workflow-child {{ $child['active'] ? 'is-active' : '' }}"
                        >
                            <span class="workflow-child-label">
                                {{ $child['number'] }} {{ $child['label'] }}
                            </span>

                            <span class="icon-right-arrow workflow-child-arrow"></span>
                        </a>
                    @else
                        <div
                            class="workflow-child is-planned"
                            aria-disabled="true"
                            title="Chức năng chưa có backend trong giai đoạn hiện tại"
                        >
                            <span class="workflow-child-label">
                                {{ $child['number'] }} {{ $child['label'] }}
                            </span>

                            <span class="workflow-planned-badge">Chưa có</span>
                        </div>
                    @endif
                @endforeach
            </div>
        </details>
    @endforeach
</nav>

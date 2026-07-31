<style>
    :root {
        --workflow-blue: #2563eb;
        --workflow-blue-dark: #1d4ed8;
        --workflow-blue-soft: #eff6ff;
    }

    .workflow-topbar {
        background: linear-gradient(100deg, var(--workflow-blue-dark), #3478f6);
        border-color: transparent !important;
        color: #fff;
    }

    .workflow-sidebar {
        width: 300px;
        box-shadow: 8px 0 24px rgba(15, 23, 42, .06);
    }

    .group\/container.sidebar-collapsed .workflow-sidebar {
        width: 72px;
        box-shadow: 4px 0 12px rgba(15, 23, 42, .05);
    }

    .workflow-navigation {
        display: grid;
        gap: 4px;
        padding: 0 10px 24px;
    }

    .workflow-module {
        overflow: hidden;
        border-radius: 10px;
    }

    .workflow-module-summary {
        display: grid;
        min-height: 48px;
        cursor: pointer;
        list-style: none;
        grid-template-columns: 28px minmax(0, 1fr) 24px;
        align-items: center;
        gap: 10px;
        padding: 9px 12px;
        color: #334155;
        font-size: 14px;
        font-weight: 700;
        transition: background-color .16s ease, color .16s ease;
    }

    .workflow-module-summary::-webkit-details-marker {
        display: none;
    }

    .workflow-module-summary:hover,
    .workflow-module.is-active > .workflow-module-summary,
    .workflow-module[open] > .workflow-module-summary {
        background: var(--workflow-blue-soft);
        color: var(--workflow-blue-dark);
    }

    .workflow-module-icon {
        font-size: 21px;
        text-align: center;
    }

    .workflow-module-title,
    .workflow-child-label {
        min-width: 0;
    }

    .workflow-module-toggle::before {
        display: block;
        color: var(--workflow-blue);
        content: '+';
        font-size: 22px;
        font-weight: 600;
        line-height: 1;
        text-align: center;
    }

    .workflow-module[open] .workflow-module-toggle::before {
        content: '−';
    }

    .workflow-children {
        display: grid;
        gap: 2px;
        padding: 3px 8px 8px 46px;
    }

    .workflow-child {
        display: flex;
        min-height: 38px;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        border-radius: 8px;
        padding: 8px 10px;
        color: #64748b;
        font-size: 13px;
        line-height: 1.35;
        transition: background-color .16s ease, color .16s ease;
    }

    a.workflow-child:hover,
    .workflow-child.is-active {
        background: #dbeafe;
        color: var(--workflow-blue-dark);
        font-weight: 700;
    }

    .workflow-child-arrow {
        flex: 0 0 auto;
        font-size: 16px;
    }

    .workflow-child.is-planned {
        cursor: not-allowed;
        opacity: .64;
    }

    .workflow-planned-badge {
        flex: 0 0 auto;
        border-radius: 999px;
        background: #e2e8f0;
        padding: 2px 7px;
        color: #475569;
        font-size: 10px;
        font-weight: 700;
    }

    .group\/container.sidebar-collapsed .workflow-navigation {
        padding-inline: 8px;
    }

    .group\/container.sidebar-collapsed .workflow-module-summary {
        grid-template-columns: 1fr;
        padding-inline: 8px;
    }

    .group\/container.sidebar-collapsed .workflow-module-title,
    .group\/container.sidebar-collapsed .workflow-module-toggle,
    .group\/container.sidebar-collapsed .workflow-children {
        display: none;
    }

    .dark .workflow-module-summary {
        color: #cbd5e1;
    }

    .dark .workflow-module-summary:hover,
    .dark .workflow-module.is-active > .workflow-module-summary,
    .dark .workflow-module[open] > .workflow-module-summary {
        background: rgba(37, 99, 235, .18);
        color: #bfdbfe;
    }

    .dark .workflow-child {
        color: #94a3b8;
    }

    .dark a.workflow-child:hover,
    .dark .workflow-child.is-active {
        background: rgba(37, 99, 235, .2);
        color: #bfdbfe;
    }

    @media (max-width: 1023px) {
        .workflow-navigation {
            padding: 0 0 24px;
        }

        .workflow-module-summary {
            min-height: 52px;
            font-size: 15px;
        }

        .workflow-child {
            min-height: 42px;
            font-size: 14px;
        }
    }
</style>

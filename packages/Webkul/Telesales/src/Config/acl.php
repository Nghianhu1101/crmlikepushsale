<?php

return [
    [
        'key' => 'dashboard',
        'name' => 'admin::app.layouts.dashboard',
        'route' => [
            'admin.telesales.rankings.index',
            'admin.telesales.rankings.data',
        ],
        'sort' => 2,
    ],
    [
        'key' => 'leads.edit',
        'name' => 'admin::app.acl.edit',
        'route' => 'admin.telesales.outcomes.store',
        'sort' => 4,
    ],
    [
        'key' => 'leads.create',
        'name' => 'admin::app.acl.create',
        'route' => [
            'admin.telesales.source-connections.index',
            'admin.telesales.source-connections.store',
            'admin.telesales.source-connections.update',
            'admin.telesales.source-connections.toggle',
            'admin.telesales.source-connections.regenerate',
        ],
        'sort' => 5,
    ],
    [
        'key' => 'leads.view',
        'name' => 'admin::app.acl.view',
        'route' => [
            'admin.telesales.notifications.open',
            'admin.telesales.created-leads.index',
            'admin.telesales.customers.index',
            'admin.telesales.customer-care.campaigns.index',
            'admin.telesales.customer-care.cases.index',
            'admin.telesales.customer-care.cases.show',
            'admin.telesales.orders.index',
        ],
        'sort' => 5,
    ],
    [
        'key' => 'leads.edit',
        'name' => 'admin::app.acl.edit',
        'route' => [
            'admin.telesales.orders.store',
            'admin.telesales.orders.status',
            'admin.telesales.owners.update',
            'admin.telesales.customer-care.campaigns.store',
            'admin.telesales.customer-care.cases.outcome',
            'admin.telesales.customer-care.cases.order',
        ],
        'sort' => 6,
    ],
    [
        'key' => 'settings.user.groups',
        'name' => 'admin::app.acl.groups',
        'route' => [
            'admin.telesales.groups.index',
            'admin.telesales.groups.update',
            'admin.telesales.marketing-mappings.index',
            'admin.telesales.marketing-mappings.store',
            'admin.telesales.marketing-mappings.destroy',
        ],
        'sort' => 4,
    ],
];

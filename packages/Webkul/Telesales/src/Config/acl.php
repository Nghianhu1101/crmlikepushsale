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
        'key' => 'leads.view',
        'name' => 'admin::app.acl.view',
        'route' => [
            'admin.telesales.notifications.open',
            'admin.telesales.created-leads.index',
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

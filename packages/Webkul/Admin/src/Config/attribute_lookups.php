<?php

return [
    'leads' => [
        'name' => 'Khách hàng tiềm năng',
        'repository' => 'Webkul\Lead\Repositories\LeadRepository',
        'label_column' => 'title',
    ],

    'lead_sources' => [
        'name' => 'Nguồn khách hàng tiềm năng',
        'repository' => 'Webkul\Lead\Repositories\SourceRepository',
    ],

    'lead_types' => [
        'name' => 'Loại khách hàng tiềm năng',
        'repository' => 'Webkul\Lead\Repositories\TypeRepository',
    ],

    'lead_pipelines' => [
        'name' => 'Quy trình bán hàng',
        'repository' => 'Webkul\Lead\Repositories\PipelineRepository',
    ],

    'lead_pipeline_stages' => [
        'name' => 'Giai đoạn bán hàng',
        'repository' => 'Webkul\Lead\Repositories\StageRepository',
    ],

    'users' => [
        'name' => 'Nhân viên phụ trách',
        'repository' => 'Webkul\User\Repositories\UserRepository',
    ],

    'organizations' => [
        'name' => 'Tổ chức',
        'repository' => 'Webkul\Contact\Repositories\OrganizationRepository',
    ],

    'persons' => [
        'name' => 'Người liên hệ',
        'repository' => 'Webkul\Contact\Repositories\PersonRepository',
    ],

    'warehouses' => [
        'name' => 'Kho hàng',
        'repository' => 'Webkul\Warehouse\Repositories\WarehouseRepository',
    ],

    'locations' => [
        'name' => 'Vị trí',
        'repository' => 'Webkul\Warehouse\Repositories\LocationRepository',
    ],
];

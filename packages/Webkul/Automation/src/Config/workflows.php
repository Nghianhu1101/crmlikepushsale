<?php

return [
    'trigger_entities' => [

        'leads' => [
            'name' => 'Khách hàng tiềm năng',
            'class' => 'Webkul\Automation\Helpers\Entity\Lead',
            'events' => [
                [
                    'event' => 'lead.create.after',
                    'name' => 'Đã tạo',
                ], [
                    'event' => 'lead.update.after',
                    'name' => 'Đã cập nhật',
                ], [
                    'event' => 'lead.delete.before',
                    'name' => 'Đã xóa',
                ],
            ],
        ],

        'activities' => [
            'name' => 'Hoạt động',
            'class' => 'Webkul\Automation\Helpers\Entity\Activity',
            'events' => [
                [
                    'event' => 'activity.create.after',
                    'name' => 'Đã tạo',
                ], [
                    'event' => 'activity.update.after',
                    'name' => 'Đã cập nhật',
                ], [
                    'event' => 'activity.delete.before',
                    'name' => 'Đã xóa',
                ],
            ],
        ],

        'persons' => [
            'name' => 'Người liên hệ',
            'class' => 'Webkul\Automation\Helpers\Entity\Person',
            'events' => [
                [
                    'event' => 'contacts.person.create.after',
                    'name' => 'Đã tạo',
                ], [
                    'event' => 'contacts.person.update.after',
                    'name' => 'Đã cập nhật',
                ], [
                    'event' => 'contacts.person.delete.before',
                    'name' => 'Đã xóa',
                ],
            ],
        ],

        'quotes' => [
            'name' => 'Báo giá',
            'class' => 'Webkul\Automation\Helpers\Entity\Quote',
            'events' => [
                [
                    'event' => 'quote.create.after',
                    'name' => 'Đã tạo',
                ], [
                    'event' => 'quote.update.after',
                    'name' => 'Đã cập nhật',
                ], [
                    'event' => 'quote.delete.before',
                    'name' => 'Đã xóa',
                ],
            ],
        ],
    ],
];

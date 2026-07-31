<?php

return [
    'api_token' => env('TELESALES_API_TOKEN'),

    'demo_password' => env('TELESALES_DEMO_PASSWORD'),

    'revenue_statuses' => [
        'confirmed',
        'shipping',
        'delivered',
    ],

    'order_statuses' => [
        'draft' => 'Đơn nháp',
        'closed' => 'Đã chốt',
        'confirmed' => 'Đã xác nhận',
        'shipping' => 'Đang giao',
        'delivered' => 'Giao thành công',
        'returned' => 'Hoàn/hủy',
    ],

    'delivery_statuses' => [
        'pending' => 'Chờ giao',
        'shipping' => 'Đang giao',
        'delivered' => 'Giao thành công',
        'returned' => 'Hoàn hàng',
        'cancelled' => 'Đã hủy',
    ],

    'call_results' => [
        'not_called' => 'Chưa gọi',
        'no_answer' => 'Không nghe máy',
        'busy' => 'Máy bận',
        'wrong_number' => 'Sai số',
        'duplicate' => 'Trùng số',
        'consulting' => 'Đang tư vấn',
        'callback' => 'Hẹn gọi lại',
        'no_demand' => 'Không có nhu cầu',
        'won' => 'Đã chốt đơn',
    ],
];

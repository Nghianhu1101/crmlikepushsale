<?php

return [
    'importers' => [
        'persons' => [
            'title' => 'Người liên hệ',

            'validation' => [
                'errors' => [
                    'duplicate-email' => 'Email \'%s\' xuất hiện nhiều lần trong tệp nhập.',
                    'duplicate-phone' => 'Số điện thoại \'%s\' xuất hiện nhiều lần trong tệp nhập.',
                    'email-not-found' => 'Không tìm thấy email \'%s\' trong hệ thống.',
                ],
            ],
        ],

        'products' => [
            'title' => 'Sản phẩm',

            'validation' => [
                'errors' => [
                    'sku-not-found' => 'Không tìm thấy sản phẩm có mã SKU đã chỉ định.',
                ],
            ],
        ],

        'leads' => [
            'title' => 'Khách hàng tiềm năng',

            'validation' => [
                'errors' => [
                    'id-not-found' => 'Không tìm thấy ID \'%s\' trong hệ thống.',
                ],
            ],
        ],
    ],

    'validation' => [
        'errors' => [
            'column-empty-headers' => 'Cột số "%s" không có tiêu đề.',
            'column-name-invalid' => 'Tên cột không hợp lệ: "%s".',
            'column-not-found' => 'Không tìm thấy các cột bắt buộc: %s.',
            'column-numbers' => 'Số lượng cột không khớp với số cột trong hàng tiêu đề.',
            'invalid-attribute' => 'Tiêu đề chứa thuộc tính không hợp lệ: "%s".',
            'system' => 'Đã xảy ra lỗi hệ thống ngoài dự kiến.',
            'wrong-quotes' => 'Tệp đang dùng dấu ngoặc kép cong thay cho dấu ngoặc kép thẳng.',
            'already-exists' => ':attribute đã tồn tại.',
        ],
    ],
];

<?php

it('shows the help page to an authenticated admin', function () {
    $admin = getDefaultAdmin();

    test()->actingAs($admin)
        ->get(route('admin.help.index'))
        ->assertOk()
        ->assertSee('Trợ giúp & Tài nguyên')
        ->assertSee('Lưu trữ đám mây')
        ->assertSee('Tiện ích mở rộng')
        ->assertSee('krayincrm.com/cloud-hosting')
        ->assertSee('Vẫn cần trợ giúp?')
        ->assertSee('Diễn đàn cộng đồng')
        ->assertSee('Video hướng dẫn');
});

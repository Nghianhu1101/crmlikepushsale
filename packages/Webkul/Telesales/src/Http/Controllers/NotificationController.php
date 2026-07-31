<?php

namespace Webkul\Telesales\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Webkul\Telesales\Models\Notification;

class NotificationController extends Controller
{
    public function open(int $id): RedirectResponse
    {
        $notification = Notification::query()
            ->where('user_id', auth()->guard('user')->id())
            ->findOrFail($id);

        $notification->update(['read_at' => now()]);

        return redirect()->route('admin.leads.view', $notification->lead_id);
    }
}

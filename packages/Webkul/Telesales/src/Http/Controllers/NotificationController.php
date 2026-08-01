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

        if ($notification->customer_care_case_id) {
            return redirect()->route(
                'admin.telesales.customer-care.cases.show',
                $notification->customer_care_case_id
            );
        }

        return redirect()->route('admin.leads.view', $notification->lead_id);
    }
}

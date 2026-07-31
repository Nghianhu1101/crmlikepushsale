<?php

namespace Webkul\Telesales\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Webkul\Lead\Models\Lead;
use Webkul\Telesales\Services\OwnershipService;

class OwnershipController extends Controller
{
    public function __construct(protected OwnershipService $ownershipService) {}

    public function update(Lead $lead): RedirectResponse
    {
        $user = auth()->guard('user')->user();

        if ($user->role?->permission_type !== 'all') {
            abort(403);
        }

        $data = request()->validate([
            'sales_owner_id' => ['nullable', 'integer', 'exists:users,id'],
            'marketing_owner_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $this->ownershipService->update(
            $lead,
            $data['sales_owner_id'] ?? null,
            $data['marketing_owner_id'] ?? null,
            $user->id
        );

        return back()->with('success', 'Đã cập nhật người phụ trách và ghi nhật ký.');
    }
}

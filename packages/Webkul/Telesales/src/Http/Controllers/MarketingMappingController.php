<?php

namespace Webkul\Telesales\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Webkul\Lead\Models\Source;
use Webkul\Telesales\Models\MarketingMapping;
use Webkul\User\Models\User;

class MarketingMappingController extends Controller
{
    public function index(): View
    {
        return view('telesales::marketing-mappings', [
            'mappings' => MarketingMapping::query()
                ->with(['user'])
                ->latest()
                ->get(),
            'marketingUsers' => User::query()
                ->join('roles', 'roles.id', '=', 'users.role_id')
                ->whereRaw('LOWER(roles.name) LIKE ?', ['%marketing%'])
                ->where('users.status', true)
                ->orderBy('users.name')
                ->get(['users.id', 'users.name']),
            'sources' => Source::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(): RedirectResponse
    {
        $data = request()->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'marketing_external_id' => ['nullable', 'string', 'max:191', 'unique:telesales_marketing_mappings,marketing_external_id'],
            'campaign' => ['nullable', 'string', 'max:150', 'unique:telesales_marketing_mappings,campaign'],
            'source_id' => ['nullable', 'integer', 'exists:lead_sources,id', 'unique:telesales_marketing_mappings,source_id'],
        ]);

        if (
            empty($data['marketing_external_id'])
            && empty($data['campaign'])
            && empty($data['source_id'])
        ) {
            throw ValidationException::withMessages([
                'marketing_external_id' => 'Cần nhập external ID, campaign hoặc nguồn data.',
            ]);
        }

        $marketingUser = User::query()->with('role')->findOrFail($data['user_id']);

        if (! str_contains(mb_strtolower((string) $marketingUser->role?->name), 'marketing')) {
            throw ValidationException::withMessages([
                'user_id' => 'Tài khoản được chọn phải có vai trò Marketing.',
            ]);
        }

        MarketingMapping::query()->create(array_merge($data, ['is_active' => true]));

        return back()->with('success', 'Đã thêm ánh xạ Marketing.');
    }

    public function destroy(MarketingMapping $mapping): RedirectResponse
    {
        $mapping->delete();

        return back()->with('success', 'Đã xóa ánh xạ Marketing.');
    }
}

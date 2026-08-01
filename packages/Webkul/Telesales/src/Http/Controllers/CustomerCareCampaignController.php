<?php

namespace Webkul\Telesales\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Webkul\Contact\Models\Person;
use Webkul\Telesales\Models\CustomerCareCampaign;
use Webkul\Telesales\Models\CustomerProfile;
use Webkul\Telesales\Services\CustomerCareService;
use Webkul\Telesales\Services\TelesalesAccessService;

class CustomerCareCampaignController extends Controller
{
    public function __construct(
        protected CustomerCareService $customerCareService,
        protected TelesalesAccessService $accessService
    ) {}

    public function index(): View
    {
        $this->authorizeAccess();

        return view('telesales::customer-care.campaigns', [
            'campaigns' => CustomerCareCampaign::query()
                ->with(['group', 'product', 'creator'])
                ->withCount([
                    'cases',
                    'cases as completed_cases_count' => fn ($query) => $query
                        ->whereIn('status', ['converted', 'completed']),
                ])
                ->latest()
                ->paginate(20),
        ]);
    }

    public function store(): RedirectResponse
    {
        $this->authorizeAccess();
        $data = request()->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'group_id' => ['nullable', 'integer', 'exists:groups,id'],
            'person_ids' => ['required', 'array', 'min:1', 'max:500'],
            'person_ids.*' => ['required', 'integer', 'distinct', 'exists:persons,id'],
        ]);
        $eligibleIds = CustomerProfile::query()
            ->whereIn('person_id', $data['person_ids'])
            ->where('customer_status', 'old')
            ->pluck('person_id');

        if ($eligibleIds->isEmpty()) {
            return back()->with('warning', 'Không có khách hàng cũ hợp lệ trong danh sách đã chọn.');
        }

        [$campaign, $createdCases] = DB::transaction(function () use ($data, $eligibleIds) {
            $campaign = CustomerCareCampaign::query()->create([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'group_id' => $data['group_id'] ?? null,
                'created_by' => auth()->guard('user')->id(),
                'status' => 'active',
                'starts_at' => now(),
            ]);
            $createdCases = 0;

            Person::query()->whereIn('id', $eligibleIds)->each(function (Person $person) use ($campaign, $data, &$createdCases): void {
                $profile = CustomerProfile::query()->where('person_id', $person->id)->firstOrFail();
                $result = $this->customerCareService->open($person, [
                    'campaign_id' => $campaign->id,
                    'group_id' => $data['group_id'] ?? null,
                    'lead_id' => $profile->last_lead_id,
                    'marketing_owner_id' => $profile->last_marketing_owner_id,
                    'sales_owner_id' => $profile->last_sales_owner_id,
                    'case_type' => 'campaign',
                    'message' => $campaign->description,
                ]);

                if (! $result['duplicate']) {
                    $createdCases++;
                }
            });

            return [$campaign, $createdCases];
        });

        return redirect()
            ->route('admin.telesales.customer-care.campaigns.index')
            ->with('success', "Đã tạo chiến dịch {$campaign->name} với {$createdCases} ca chăm sóc.");
    }

    private function authorizeAccess(): void
    {
        $role = $this->accessService->role(auth()->guard('user')->user());
        abort_unless(in_array($role, ['admin', 'customer_care'], true), 403);
    }
}

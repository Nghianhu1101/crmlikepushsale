<?php

namespace Webkul\Telesales\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Webkul\Lead\Models\Source;
use Webkul\Telesales\Models\GroupMember;
use Webkul\Telesales\Models\TelesalesGroup;
use Webkul\User\Models\Group;

class GroupConfigurationController extends Controller
{
    public function index(): View
    {
        return view('telesales::groups', [
            'groups' => Group::query()
                ->with(['users' => fn ($query) => $query->orderBy('name')])
                ->orderBy('name')
                ->get(),
            'configurations' => TelesalesGroup::query()->get()->keyBy('group_id'),
            'members' => GroupMember::query()->get()->keyBy(
                fn (GroupMember $member) => $member->group_id.'-'.$member->user_id
            ),
            'sources' => Source::query()->orderBy('name')->get(),
        ]);
    }

    public function update(Group $group): RedirectResponse
    {
        $data = request()->validate([
            'source_id' => ['nullable', 'integer', 'exists:lead_sources,id'],
            'campaign' => ['nullable', 'string', 'max:150'],
            'department' => ['required', Rule::in(['sales', 'customer_care'])],
            'is_default' => ['nullable', 'boolean'],
            'members' => ['nullable', 'array'],
            'members.*.receives_data' => ['nullable', 'boolean'],
            'members.*.position' => ['nullable', 'integer', 'min:0', 'max:100000'],
        ]);

        DB::transaction(function () use ($group, $data) {
            $isDefault = request()->boolean('is_default');

            if ($isDefault) {
                TelesalesGroup::query()
                    ->where('department', $data['department'])
                    ->update(['is_default' => false]);
            }

            TelesalesGroup::query()->updateOrCreate(
                ['group_id' => $group->id],
                [
                    'source_id' => $data['source_id'] ?? null,
                    'campaign' => $data['campaign'] ?? null,
                    'department' => $data['department'],
                    'is_default' => $isDefault,
                ]
            );

            $userIds = $group->users()->pluck('users.id');

            GroupMember::query()
                ->where('group_id', $group->id)
                ->when(
                    $userIds->isNotEmpty(),
                    fn ($query) => $query->whereNotIn('user_id', $userIds),
                    fn ($query) => $query
                )
                ->delete();

            foreach ($userIds as $defaultPosition => $userId) {
                $member = data_get($data, 'members.'.$userId, []);

                GroupMember::query()->updateOrCreate(
                    [
                        'group_id' => $group->id,
                        'user_id' => $userId,
                    ],
                    [
                        'receives_data' => (bool) ($member['receives_data'] ?? false),
                        'position' => (int) ($member['position'] ?? $defaultPosition),
                    ]
                );
            }
        });

        return back()->with('success', 'Đã lưu cấu hình phân data cho nhóm '.$group->name.'.');
    }
}

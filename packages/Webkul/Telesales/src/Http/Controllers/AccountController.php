<?php

namespace Webkul\Telesales\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Webkul\Telesales\Http\Requests\StoreAccountRequest;
use Webkul\Telesales\Models\GroupMember;
use Webkul\Telesales\Models\TelesalesGroup;
use Webkul\Telesales\Services\AccountProvisioningService;
use Webkul\Telesales\Services\TelesalesAccessService;
use Webkul\User\Repositories\RoleRepository;
use Webkul\User\Repositories\UserRepository;

class AccountController extends Controller
{
    public function __construct(
        protected AccountProvisioningService $accountProvisioningService,
        protected TelesalesAccessService $accessService,
        protected UserRepository $userRepository,
        protected RoleRepository $roleRepository
    ) {}

    public function index(): View
    {
        $this->authorizeAdmin();

        $roles = $this->roleRepository
            ->scopeQuery(fn ($query) => $query->withCount('users')->orderBy('id'))
            ->all();
        $groups = TelesalesGroup::query()
            ->with('group')
            ->orderBy('department')
            ->orderByDesc('is_default')
            ->get()
            ->filter(fn (TelesalesGroup $configuration) => $configuration->group)
            ->values();
        $accounts = $this->userRepository
            ->scopeQuery(fn ($query) => $query
                ->with(['role', 'groups'])
                ->latest('id'))
            ->paginate(25);
        $members = GroupMember::query()
            ->whereIn('user_id', $accounts->pluck('id'))
            ->get()
            ->keyBy('user_id');

        return view('telesales::accounts.index', [
            'accounts' => $accounts,
            'roles' => $roles,
            'groups' => $groups,
            'members' => $members,
            'roleCategories' => $roles->mapWithKeys(fn ($role) => [
                $role->id => $this->accountProvisioningService->category($role),
            ]),
        ]);
    }

    public function store(StoreAccountRequest $request): RedirectResponse
    {
        $account = $this->accountProvisioningService->create($request->validated());

        return redirect()
            ->route('admin.telesales.accounts.index')
            ->with('success', "Đã tạo tài khoản {$account->name} với vai trò {$account->role->name}.");
    }

    private function authorizeAdmin(): void
    {
        $user = auth()->guard('user')->user();

        abort_unless($user && $this->accessService->isAdmin($user), 403);
    }
}

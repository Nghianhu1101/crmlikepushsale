<?php

namespace Webkul\Telesales\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Webkul\Lead\Models\Source;
use Webkul\Telesales\Http\Requests\SourceConnectionRequest;
use Webkul\Telesales\Models\SourceConnection;
use Webkul\Telesales\Models\TelesalesGroup;
use Webkul\Telesales\Services\TelesalesAccessService;
use Webkul\User\Models\User;

class SourceConnectionController extends Controller
{
    public function __construct(protected TelesalesAccessService $accessService) {}

    public function index(): View
    {
        $user = auth()->guard('user')->user();
        $role = $this->accessService->role($user);

        abort_unless(in_array($role, ['admin', 'marketing'], true), 403);

        return view('telesales::source-connections', [
            'connections' => SourceConnection::query()
                ->with(['source', 'group', 'marketingOwner'])
                ->when($role === 'marketing', fn (Builder $query) => $query->where('marketing_owner_id', $user->id))
                ->latest()
                ->get(),
            'sources' => Source::query()->orderBy('name')->get(['id', 'name']),
            'groups' => TelesalesGroup::query()
                ->where('department', 'sales')
                ->with('group')
                ->get()
                ->pluck('group')
                ->filter()
                ->sortBy('name')
                ->values(),
            'marketingUsers' => $role === 'admin' ? $this->marketingUsers() : collect([$user]),
            'role' => $role,
            'endpoint' => route('api.v1.incoming-leads.store'),
            'revealedToken' => session('source_connection_token'),
        ]);
    }

    public function store(SourceConnectionRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $user = auth()->guard('user')->user();
        $role = $this->accessService->role($user);

        abort_unless(in_array($role, ['admin', 'marketing'], true), 403);

        $ownerId = $role === 'marketing'
            ? $user->id
            : ($data['marketing_owner_id'] ?? null);

        $this->validateMarketingOwner($ownerId);

        $token = $this->makeToken();
        $connection = SourceConnection::query()->create([
            ...$this->connectionData($data),
            'marketing_owner_id' => $ownerId,
            'token_hash' => hash('sha256', $token),
            'token_hint' => $this->tokenHint($token),
            'is_active' => true,
        ]);

        return back()
            ->with('success', 'Đã tạo kết nối nguồn data. Hãy sao chép token trước khi rời trang.')
            ->with('source_connection_token', [
                'id' => $connection->id,
                'name' => $connection->name,
                'token' => $token,
            ]);
    }

    public function update(SourceConnectionRequest $request, SourceConnection $connection): RedirectResponse
    {
        $this->authorizeConnection($connection);
        $data = $request->validated();
        $role = $this->accessService->role(auth()->guard('user')->user());
        $ownerId = $role === 'admin'
            ? ($data['marketing_owner_id'] ?? $connection->marketing_owner_id)
            : $connection->marketing_owner_id;

        $this->validateMarketingOwner($ownerId);

        $connection->update([
            ...$this->connectionData($data),
            'marketing_owner_id' => $ownerId,
        ]);

        return back()->with('success', 'Đã cập nhật cấu hình nguồn data.');
    }

    public function toggle(SourceConnection $connection): RedirectResponse
    {
        $this->authorizeConnection($connection);
        $connection->update(['is_active' => ! $connection->is_active]);

        return back()->with(
            'success',
            $connection->is_active ? 'Đã bật nhận data từ nguồn.' : 'Đã tạm dừng nguồn data.'
        );
    }

    public function regenerate(SourceConnection $connection): RedirectResponse
    {
        $this->authorizeConnection($connection);
        $token = $this->makeToken();

        $connection->update([
            'token_hash' => hash('sha256', $token),
            'token_hint' => $this->tokenHint($token),
        ]);

        return back()
            ->with('success', 'Đã cấp token mới. Token cũ ngừng hoạt động ngay lập tức.')
            ->with('source_connection_token', [
                'id' => $connection->id,
                'name' => $connection->name,
                'token' => $token,
            ]);
    }

    private function connectionData(array $data): array
    {
        $sourceId = $data['source_id'] ?? null;

        if ($sourceName = trim((string) ($data['source_name'] ?? ''))) {
            $sourceId = Source::query()->firstOrCreate(['name' => $sourceName])->id;
        }

        if (! $sourceId) {
            $sourceId = Source::query()->firstOrCreate([
                'name' => match ($data['channel']) {
                    'website' => 'Website',
                    'facebook' => 'Facebook',
                    'api' => 'API',
                    default => 'Nguồn khác',
                },
            ])->id;
        }

        return [
            'name' => $data['name'],
            'channel' => $data['channel'],
            'source_id' => $sourceId,
            'group_id' => $data['group_id'] ?? null,
            'campaign' => $data['campaign'] ?? null,
            'field_mapping' => array_filter([
                'phone' => $data['map_phone'],
                'name' => $data['map_name'] ?? null,
                'product' => $data['map_product'] ?? null,
                'message' => $data['map_message'] ?? null,
                'external_id' => $data['map_external_id'] ?? null,
            ]),
        ];
    }

    private function authorizeConnection(SourceConnection $connection): void
    {
        $user = auth()->guard('user')->user();
        $role = $this->accessService->role($user);

        abort_unless(
            $role === 'admin'
            || ($role === 'marketing' && $connection->marketing_owner_id === $user->id),
            403
        );
    }

    private function validateMarketingOwner(?int $ownerId): void
    {
        $owner = $ownerId ? User::query()->with('role')->find($ownerId) : null;

        if (! $owner || ! str_contains(mb_strtolower((string) $owner->role?->name), 'marketing')) {
            throw ValidationException::withMessages([
                'marketing_owner_id' => 'Hãy chọn một tài khoản có vai trò Marketing.',
            ]);
        }
    }

    private function marketingUsers()
    {
        return User::query()
            ->join('roles', 'roles.id', '=', 'users.role_id')
            ->whereRaw('LOWER(roles.name) LIKE ?', ['%marketing%'])
            ->where('users.status', true)
            ->orderBy('users.name')
            ->get(['users.id', 'users.name']);
    }

    private function makeToken(): string
    {
        return 'tsc_'.Str::random(48);
    }

    private function tokenHint(string $token): string
    {
        return substr($token, 0, 8).'…'.substr($token, -4);
    }
}

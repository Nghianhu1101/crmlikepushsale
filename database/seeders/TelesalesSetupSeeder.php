<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Webkul\Attribute\Repositories\AttributeValueRepository;
use Webkul\Lead\Models\Source;
use Webkul\Product\Models\Product;
use Webkul\Product\Repositories\ProductRepository;
use Webkul\Telesales\Models\GroupMember;
use Webkul\Telesales\Models\TelesalesGroup;
use Webkul\User\Models\Group;
use Webkul\User\Models\Role;
use Webkul\User\Models\User;

class TelesalesSetupSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Nhập trực tiếp', 'Website', 'Facebook', 'Messenger'] as $name) {
            Source::query()->firstOrCreate(['name' => $name]);
        }

        $this->createProducts();

        $this->createRoles();

        Group::query()->firstOrCreate(
            ['name' => 'Chưa xác định Marketing'],
            ['description' => 'Data chưa ánh xạ được tài khoản Marketing.']
        );

        $group = Group::query()->firstOrCreate(
            ['name' => 'Telesale mặc định'],
            ['description' => 'Nhóm nhận data mặc định theo vòng round-robin.']
        );

        $admin = User::query()->find(1);

        if ($admin) {
            $group->users()->syncWithoutDetaching([$admin->id]);

            GroupMember::query()->updateOrCreate(
                ['group_id' => $group->id, 'user_id' => $admin->id],
                ['receives_data' => false, 'position' => 1]
            );
        }

        $hasDefaultGroup = TelesalesGroup::query()
            ->where('department', 'sales')
            ->where('is_default', true)
            ->exists();

        TelesalesGroup::query()->firstOrCreate(
            ['group_id' => $group->id],
            ['department' => 'sales', 'is_default' => ! $hasDefaultGroup]
        );

        $careGroup = Group::query()->firstOrCreate(
            ['name' => 'CSKH mặc định'],
            ['description' => 'Nhóm Chăm sóc khách hàng nhận ca sau bán và khách quay lại.']
        );

        if ($admin) {
            $careGroup->users()->syncWithoutDetaching([$admin->id]);
            GroupMember::query()->updateOrCreate(
                ['group_id' => $careGroup->id, 'user_id' => $admin->id],
                ['receives_data' => false, 'position' => 1]
            );
        }

        $hasDefaultCareGroup = TelesalesGroup::query()
            ->where('department', 'customer_care')
            ->where('is_default', true)
            ->exists();

        TelesalesGroup::query()->firstOrCreate(
            ['group_id' => $careGroup->id],
            ['department' => 'customer_care', 'is_default' => ! $hasDefaultCareGroup]
        );
    }

    private function createProducts(): void
    {
        $products = [
            'CTML' => 'Cao tuân mạch linh',
            'CTML-HALF' => '1/2 cao tuân mạch linh',
            'NTP' => 'Nhân tâm phúc',
            'NTP-HALF' => '1/2 nhân tâm phúc',
        ];

        $productRepository = app(ProductRepository::class);
        $attributeValueRepository = app(AttributeValueRepository::class);

        foreach ($products as $sku => $name) {
            $data = [
                'entity_type' => 'products',
                'sku' => $sku,
                'name' => $name,
                'description' => 'Sản phẩm mặc định cho quy trình telesale.',
                'quantity' => 0,
                'price' => 0,
            ];
            $product = Product::query()->where('sku', $sku)->first();

            if (! $product) {
                $productRepository->create($data);

                continue;
            }

            if ($product->attribute_values()->doesntExist()) {
                $attributeValueRepository->save(array_merge($data, [
                    'entity_id' => $product->id,
                ]));
            }
        }
    }

    private function createRoles(): void
    {
        $roles = [
            'Marketing' => [
                'dashboard',
                'leads',
                'leads.create',
                'leads.create.quick-create',
                'leads.view',
            ],
            'Sale' => [
                'dashboard',
                'leads',
                'leads.view',
                'leads.edit',
            ],
            'Trưởng nhóm sale' => [
                'dashboard',
                'leads',
                'leads.create',
                'leads.create.quick-create',
                'leads.view',
                'leads.edit',
            ],
            'Chăm sóc khách hàng' => [
                'dashboard',
                'leads',
                'leads.view',
                'leads.edit',
            ],
        ];

        foreach ($roles as $name => $permissions) {
            $role = Role::query()->firstOrNew(['name' => $name]);

            if (
                ! $role->exists
                || $role->description === 'Vai trò mặc định cho quy trình telesale.'
            ) {
                $role->fill([
                    'description' => 'Vai trò mặc định cho quy trình telesale.',
                    'permission_type' => 'custom',
                    'permissions' => $permissions,
                ])->save();
            }
        }
    }
}

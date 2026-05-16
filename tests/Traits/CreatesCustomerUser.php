<?php

declare(strict_types=1);

namespace Noerd\Customer\Tests\Traits;

use Noerd\Helpers\TenantHelper;
use Noerd\Models\NoerdUser;
use Noerd\Models\Tenant;
use Noerd\Models\TenantApp;

trait CreatesCustomerUser
{
    protected function withCustomerModule(): NoerdUser
    {
        $user = NoerdUser::factory()->create();
        $tenant = Tenant::factory()->create();
        $user->tenants()->attach($tenant->id);
        TenantHelper::setSelectedTenantId($tenant->id);
        TenantHelper::setSelectedApp('CUSTOMER');

        $app = TenantApp::firstOrCreate(
            ['name' => 'CUSTOMER'],
            [
                'title' => 'Customer',
                'icon' => 'customer::icons.app',
                'route' => 'customer',
                'is_active' => true,
            ],
        );
        $tenant->tenantApps()->syncWithoutDetaching([$app->id]);

        return $user;
    }
}

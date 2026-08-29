<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Noerd\Customer\Models\Customer;
use Noerd\Customer\Tests\Traits\CreatesCustomerUser;
use Noerd\Helpers\TenantHelper;

uses(Tests\TestCase::class);
uses(CreatesCustomerUser::class);
uses(RefreshDatabase::class);

/*
 | Generic list mechanics (pagination, search, sorting, row actions) are proven
 | in the noerd core suite — asserted here is only what the customer module
 | owns: its list resolves rows through the tenant scope.
 */

it('lists only the customers of the current tenant', function (): void {
    $user1 = $this->withCustomerModule();
    $tenant1 = $user1->tenants->first()->id;
    Customer::factory()->count(3)->create(['tenant_id' => $tenant1]);

    $this->actingAs($user1);

    expect(Livewire::test('customer::customers-list')->instance()->listData()['rows'])->toHaveCount(3);

    $user2 = $this->withCustomerModule();
    TenantHelper::clearCache();
    $tenant2 = $user2->tenants->first()->id;
    Customer::factory()->count(2)->create(['tenant_id' => $tenant2]);

    $this->actingAs($user2);

    expect(Livewire::test('customer::customers-list')->instance()->listData()['rows'])->toHaveCount(2);
});

it('handles empty search results correctly', function (): void {
    $user = $this->withCustomerModule();
    $this->actingAs($user);

    Customer::factory()->create([
        'tenant_id' => $user->tenants->first()->id,
        'name' => 'John Doe',
    ]);

    $component = Livewire::test('customer::customers-list')
        ->set('search', 'NonExistentCustomer');

    expect($component->instance()->listData()['rows'])->toHaveCount(0);
});

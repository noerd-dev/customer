<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Noerd\Customer\Support\UserSelectedCustomer;
use Noerd\Customer\Models\Customer;
use Noerd\Models\Tenant;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('applies the selected customer in the quick-menu customer selector', function (): void {
    $tenant = Tenant::factory()->create();

    $customer = Customer::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Peter Pan',
    ]);

    Livewire::test('customer::quick-menu.customer-select-component')
        ->assertSet('customerId', null)
        ->dispatch('customerSelected', $customer->id, 'customerId')
        ->assertSet('customerId', $customer->id)
        ->assertSet('customerName', 'Peter Pan');

    expect(UserSelectedCustomer::get()?->id)->toBe($customer->id);
});

it('clears the selected customer in the quick-menu customer selector', function (): void {
    $tenant = Tenant::factory()->create();

    $customer = Customer::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Peter Pan',
    ]);

    UserSelectedCustomer::set($customer->id);

    Livewire::test('customer::quick-menu.customer-select-component')
        ->assertSet('customerId', $customer->id)
        ->call('clear')
        ->assertSet('customerId', null)
        ->assertDispatched('customerCleared');

    expect(UserSelectedCustomer::get())->toBeNull();
});

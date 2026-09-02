<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Noerd\Customer\Models\Customer;
use Noerd\Customer\Support\UserSelectedCustomer;
use Noerd\Customer\Tests\Traits\CreatesCustomerUser;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class, CreatesCustomerUser::class);

it('applies the selected customer in the quick-menu customer selector', function (): void {
    $user = $this->withCustomerModule();
    $this->actingAs($user);

    $customer = Customer::factory()->create([
        'tenant_id' => $user->tenants->first()->id,
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
    $user = $this->withCustomerModule();
    $this->actingAs($user);

    $customer = Customer::factory()->create([
        'tenant_id' => $user->tenants->first()->id,
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

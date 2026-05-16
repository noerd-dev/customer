<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Noerd\Customer\Models\Customer;
use Noerd\Customer\Tests\Traits\CreatesCustomerUser;

uses(Tests\TestCase::class);
uses(CreatesCustomerUser::class);
uses(RefreshDatabase::class);

$testSettings = [
    'componentName' => 'customer::customers-list',
    'id' => 'customerId',
];

it('displays customers table correctly', function () use ($testSettings): void {
    $user = $this->withCustomerModule();

    $this->actingAs($user);

    // Create some test customers
    Customer::factory()->count(3)->create([
        'tenant_id' => $user->selected_tenant_id,
    ]);

    $component = Livewire::test($testSettings['componentName']);

    $componentData = $component->instance();
    $withData = $componentData->with();

    expect($withData['listConfig']['rows'])->toHaveCount(3);
    expect($withData['listConfig']['listSettings'])->toBeArray();
})->todo();

it('handles pagination correctly with many customers', function () use ($testSettings): void {
    $user = $this->withCustomerModule();

    $this->actingAs($user);

    // Create more customers than the pagination limit (50)
    Customer::factory()->count(75)->create([
        'tenant_id' => $user->selected_tenant_id,
    ]);

    $component = Livewire::test($testSettings['componentName']);

    $componentData = $component->instance();
    $withData = $componentData->with();

    // Should have pagination
    expect($withData['listConfig']['rows']->hasPages())->toBe(true);
    expect($withData['listConfig']['rows']->lastPage())->toBe(2);
    expect($withData['listConfig']['rows']->count())->toBe(50); // First page should have 50 items
    expect($withData['listConfig']['rows']->total())->toBe(75); // Total should be 75
})->todo();

it('filters customers by search term', function () use ($testSettings): void {
    $user = $this->withCustomerModule();

    $this->actingAs($user);

    // Create customers with specific names
    Customer::factory()->create([
        'tenant_id' => $user->selected_tenant_id,
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ]);

    Customer::factory()->create([
        'tenant_id' => $user->selected_tenant_id,
        'name' => 'Jane Smith',
        'email' => 'jane@example.com',
    ]);

    Customer::factory()->create([
        'tenant_id' => $user->selected_tenant_id,
        'name' => 'Bob Johnson',
        'email' => 'bob@example.com',
    ]);

    // Test search by name
    $component = Livewire::test($testSettings['componentName'])
        ->set('search', 'John');

    $componentData = $component->instance();
    $withData = $componentData->with();

    expect($withData['listConfig']['rows'])->toHaveCount(2); // John Doe and Bob Johnson

    // Test search by email
    $component = Livewire::test($testSettings['componentName'])
        ->set('search', 'jane@example.com');

    $componentData = $component->instance();
    $withData = $componentData->with();

    expect($withData['listConfig']['rows'])->toHaveCount(1);
    expect($withData['listConfig']['rows']->first()->name)->toBe('Jane Smith');
})->todo();

it('sorts customers correctly', function () use ($testSettings): void {
    $user = $this->withCustomerModule();

    $this->actingAs($user);

    // Create customers with different names
    $customer1 = Customer::factory()->create([
        'tenant_id' => $user->selected_tenant_id,
        'name' => 'Alice',
    ]);

    $customer2 = Customer::factory()->create([
        'tenant_id' => $user->selected_tenant_id,
        'name' => 'Bob',
    ]);

    $customer3 = Customer::factory()->create([
        'tenant_id' => $user->selected_tenant_id,
        'name' => 'Charlie',
    ]);

    // Test ascending sort by name
    $component = Livewire::test($testSettings['componentName'])
        ->set('sortField', 'name')
        ->set('sortAsc', true);

    $componentData = $component->instance();
    $withData = $componentData->with();

    $names = $withData['listConfig']['rows']->pluck('name')->toArray();
    expect($names)->toBe(['Alice', 'Bob', 'Charlie']);

    // Test descending sort by name
    $component = Livewire::test($testSettings['componentName'])
        ->set('sortField', 'name')
        ->set('sortAsc', false);

    $componentData = $component->instance();
    $withData = $componentData->with();

    $names = $withData['listConfig']['rows']->pluck('name')->toArray();
    expect($names)->toBe(['Charlie', 'Bob', 'Alice']);
})->todo();

it('handles table action correctly', function () use ($testSettings): void {
    $user = $this->withCustomerModule();

    $this->actingAs($user);

    $customer = Customer::factory()->create([
        'tenant_id' => $user->selected_tenant_id,
    ]);

    $component = Livewire::test($testSettings['componentName']);

    // Test table action dispatches correct event
    $component->call('listAction', $customer->id)
        ->assertDispatched('noerdModal');
})->todo();

it('only shows customers for the current tenant', function () use ($testSettings): void {
    $user1 = $this->withCustomerModule();
    $user2 = $this->withCustomerModule();

    // Create customers for different tenants
    Customer::factory()->count(3)->create([
        'tenant_id' => $user1->selected_tenant_id,
    ]);

    Customer::factory()->count(2)->create([
        'tenant_id' => $user2->selected_tenant_id,
    ]);

    // Test as user1
    $this->actingAs($user1);

    $component = Livewire::test($testSettings['componentName']);

    $componentData = $component->instance();
    $withData = $componentData->with();

    expect($withData['listConfig']['rows'])->toHaveCount(3);

    // Test as user2
    $this->actingAs($user2);

    $component = Livewire::test($testSettings['componentName']);

    $componentData = $component->instance();
    $withData = $componentData->with();

    expect($withData['listConfig']['rows'])->toHaveCount(2);
})->todo();

it('handles empty search results correctly', function () use ($testSettings): void {
    $user = $this->withCustomerModule();

    $this->actingAs($user);

    Customer::factory()->create([
        'tenant_id' => $user->selected_tenant_id,
        'name' => 'John Doe',
    ]);

    // Search for something that doesn't exist
    $component = Livewire::test($testSettings['componentName'])
        ->set('search', 'NonExistentCustomer');

    $componentData = $component->instance();
    $withData = $componentData->with();

    expect($withData['listConfig']['rows'])->toHaveCount(0);
});

it('pagination works correctly with search', function () use ($testSettings): void {
    $user = $this->withCustomerModule();

    $this->actingAs($user);

    // Create many customers with similar names
    for ($i = 1; $i <= 60; $i++) {
        Customer::factory()->create([
            'tenant_id' => $user->selected_tenant_id,
            'name' => "Test Customer {$i}",
        ]);
    }

    // Create some customers with different names
    Customer::factory()->count(5)->create([
        'tenant_id' => $user->selected_tenant_id,
        'name' => 'Different Name',
    ]);

    // Search for "Test Customer" - should find 60 customers
    $component = Livewire::test($testSettings['componentName'])
        ->set('search', 'Test Customer');

    $componentData = $component->instance();
    $withData = $componentData->with();

    expect($withData['listConfig']['rows']->hasPages())->toBe(true);
    expect($withData['listConfig']['rows']->total())->toBe(60);
    expect($withData['listConfig']['rows']->count())->toBe(50); // First page
    expect($withData['listConfig']['rows']->lastPage())->toBe(2);
})->todo();

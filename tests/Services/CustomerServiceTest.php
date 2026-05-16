<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Noerd\Customer\Models\Customer;
use Noerd\Customer\Services\CustomerService;
use Noerd\Models\Tenant;

uses(Tests\TestCase::class);
uses(RefreshDatabase::class);

it('creates a new customer when none exists with the given email', function (): void {
    $service = app(CustomerService::class);
    $tenant = Tenant::factory()->create();

    $customer = $service->findOrCreateByEmail($tenant->id, 'test@example.com', [
        'name' => 'Test User',
    ]);

    expect($customer)->toBeInstanceOf(Customer::class);
    expect($customer->email)->toBe('test@example.com');
    expect($customer->name)->toBe('Test User');
    expect($customer->tenant_id)->toBe($tenant->id);

    $this->assertDatabaseCount('customers', 1);
});

it('returns existing customer and updates attributes', function (): void {
    $service = app(CustomerService::class);
    $tenant = Tenant::factory()->create();

    $existing = Customer::create([
        'tenant_id' => $tenant->id,
        'email' => 'test@example.com',
        'name' => 'Old Name',
    ]);

    $customer = $service->findOrCreateByEmail($tenant->id, 'test@example.com', [
        'name' => 'New Name',
    ]);

    expect($customer->id)->toBe($existing->id);
    expect($customer->name)->toBe('New Name');

    $this->assertDatabaseCount('customers', 1);
});

it('keeps customers separate between tenants', function (): void {
    $service = app(CustomerService::class);
    $tenant1 = Tenant::factory()->create();
    $tenant2 = Tenant::factory()->create();

    $customer1 = $service->findOrCreateByEmail($tenant1->id, 'shared@example.com', [
        'name' => 'Tenant 1 Customer',
    ]);

    $customer2 = $service->findOrCreateByEmail($tenant2->id, 'shared@example.com', [
        'name' => 'Tenant 2 Customer',
    ]);

    expect($customer1->id)->not->toBe($customer2->id);
    expect($customer1->tenant_id)->toBe($tenant1->id);
    expect($customer2->tenant_id)->toBe($tenant2->id);

    $this->assertDatabaseCount('customers', 2);
});

it('always creates a new customer without email', function (): void {
    $service = app(CustomerService::class);
    $tenant = Tenant::factory()->create();

    $customer1 = $service->createWithoutEmail($tenant->id, ['name' => 'Walk-in 1']);
    $customer2 = $service->createWithoutEmail($tenant->id, ['name' => 'Walk-in 2']);

    expect($customer1->id)->not->toBe($customer2->id);
    expect($customer1->email)->toBeNull();
    expect($customer2->email)->toBeNull();

    $this->assertDatabaseCount('customers', 2);
});

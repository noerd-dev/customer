<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Noerd\Customer\Models\Customer;
use Noerd\Customer\Models\CustomerAddress;
use Noerd\Customer\Services\CustomerAddressService;
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

it('save updates an existing customer without creating a new record', function (): void {
    $service = app(CustomerService::class);
    $tenant = Tenant::factory()->create();

    $existing = Customer::create([
        'tenant_id' => $tenant->id,
        'email' => 'old@example.com',
        'name' => 'Old Name',
    ]);

    $customer = $service->save($tenant->id, [
        'name' => 'New Name',
        'email' => 'new@example.com',
    ], $existing->id);

    expect($customer->id)->toBe($existing->id);
    expect($customer->name)->toBe('New Name');
    expect($customer->email)->toBe('new@example.com');
    expect($customer->tenant_id)->toBe($tenant->id);

    $this->assertDatabaseCount('customers', 1);
});

it('save persists customer and address with both defaults in one call', function (): void {
    $tenant = Tenant::factory()->create();

    $customer = app(CustomerService::class)->save($tenant->id, [
        'name' => 'Test User',
        'email' => 'test@example.com',
    ], null, [
        'address_line_1' => 'Musterweg 1',
        'postal_code' => '12345',
        'locality' => 'Berlin',
    ]);

    $customer->refresh();
    expect($customer->default_invoice_address_id)->not->toBeNull();
    expect($customer->default_delivery_address_id)->toBe($customer->default_invoice_address_id);
    expect($customer->defaultInvoiceAddress->address_line_1)->toBe('Musterweg 1');
});

it('save without address data creates no address', function (): void {
    $tenant = Tenant::factory()->create();

    $customer = app(CustomerService::class)->save($tenant->id, [
        'name' => 'Test User',
    ], null, [
        'address_line_1' => '',
        'postal_code' => ' ',
    ]);

    expect(CustomerAddress::withoutGlobalScopes()->count())->toBe(0);
    expect($customer->default_invoice_address_id)->toBeNull();
});

it('save normalizes empty default address ids and rejects foreign ones', function (): void {
    $tenant = Tenant::factory()->create();
    $customer = Customer::factory()->create(['tenant_id' => $tenant->id]);
    $other = Customer::factory()->create(['tenant_id' => $tenant->id]);
    $foreign = app(CustomerAddressService::class)->upsertFor($other, ['address_line_1' => 'Foreign Street 1']);

    $service = app(CustomerService::class);

    $service->save($tenant->id, [
        'name' => 'Test',
        'default_invoice_address_id' => '',
        'default_delivery_address_id' => $foreign->id,
    ], $customer->id);

    $customer->refresh();
    expect($customer->default_invoice_address_id)->toBeNull();
    expect($customer->default_delivery_address_id)->toBeNull();
});

it('save ignores the audits key in the payload', function (): void {
    $service = app(CustomerService::class);
    $tenant = Tenant::factory()->create();

    $existing = Customer::create([
        'tenant_id' => $tenant->id,
        'email' => 'test@example.com',
        'name' => 'Old Name',
    ]);

    $customer = $service->save($tenant->id, [
        'name' => 'New Name',
        'email' => 'test@example.com',
        'audits' => [['event' => 'updated']],
    ], $existing->id);

    expect($customer->name)->toBe('New Name');

    $this->assertDatabaseCount('customers', 1);
});

it('save keeps a forged tenant_id in the payload out of the record', function (): void {
    $service = app(CustomerService::class);
    $tenant = Tenant::factory()->create();
    $foreignTenant = Tenant::factory()->create();

    // Create path with an email.
    $created = $service->save($tenant->id, [
        'name' => 'Forged Create',
        'email' => 'forged@example.com',
        'tenant_id' => $foreignTenant->id,
    ]);

    expect($created->tenant_id)->toBe($tenant->id);

    // Create path without an email.
    $walkIn = $service->save($tenant->id, [
        'name' => 'Forged Walk-in',
        'tenant_id' => $foreignTenant->id,
    ]);

    expect($walkIn->tenant_id)->toBe($tenant->id);

    // Update path.
    $updated = $service->save($tenant->id, [
        'name' => 'Forged Update',
        'tenant_id' => $foreignTenant->id,
    ], $created->id);

    expect($updated->fresh()->tenant_id)->toBe($tenant->id);

    expect(Customer::withoutGlobalScopes()->where('tenant_id', $foreignTenant->id)->count())->toBe(0);
});

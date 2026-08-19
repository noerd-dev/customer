<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Noerd\Customer\Models\Customer;
use Noerd\Customer\Models\CustomerAddress;
use Noerd\Customer\Tests\Traits\CreatesCustomerUser;
use Tests\TestCase;

uses(TestCase::class);
uses(CreatesCustomerUser::class);
uses(RefreshDatabase::class);

it('validates the data', function (): void {
    $user = $this->withCustomerModule();
    $this->actingAs($user);

    $component = Livewire::test('customer::customer-address-detail')
        ->set('detailData', [])
        ->call('store');

    $component->assertHasErrors(requiredLayoutFields($component));
});

it('creates an address for the selected customer', function (): void {
    $user = $this->withCustomerModule();
    $this->actingAs($user);

    $customer = Customer::factory()->create(['tenant_id' => $user->selected_tenant_id]);

    Livewire::test('customer::customer-address-detail')
        ->call('customerSelected', $customer->id)
        ->set('detailData.address_line_1', 'Musterweg 1')
        ->set('detailData.postal_code', '12345')
        ->set('detailData.locality', 'Berlin')
        ->set('detailData.country_code', 'DE')
        ->call('store')
        ->assertHasNoErrors();

    $address = CustomerAddress::withoutGlobalScopes()->first();
    expect($address)->not->toBeNull();
    expect($address->customer_id)->toBe($customer->id);
    expect($address->tenant_id)->toBe($user->selected_tenant_id);
    expect($address->address_line_1)->toBe('Musterweg 1');
});

it('stores an empty country selection as null', function (): void {
    $user = $this->withCustomerModule();
    $this->actingAs($user);

    $customer = Customer::factory()->create(['tenant_id' => $user->selected_tenant_id]);

    Livewire::test('customer::customer-address-detail')
        ->call('customerSelected', $customer->id)
        ->set('detailData.address_line_1', 'Musterweg 1')
        ->set('detailData.country_code', '')
        ->call('store')
        ->assertHasNoErrors();

    expect(CustomerAddress::withoutGlobalScopes()->first()->country_code)->toBeNull();
});

it('normalizes invalid country codes to null and valid ones to uppercase', function (): void {
    $user = $this->withCustomerModule();
    $this->actingAs($user);

    $customer = Customer::factory()->create(['tenant_id' => $user->selected_tenant_id]);

    Livewire::test('customer::customer-address-detail')
        ->call('customerSelected', $customer->id)
        ->set('detailData.address_line_1', 'Musterweg 1')
        ->set('detailData.country_code', 'Deutschland')
        ->call('store')
        ->assertHasNoErrors();

    $first = CustomerAddress::withoutGlobalScopes()->first();
    expect($first->country_code)->toBeNull();

    Livewire::test('customer::customer-address-detail', ['modelId' => $first->id])
        ->set('detailData.country_code', 'de')
        ->call('store')
        ->assertHasNoErrors();

    expect($first->refresh()->country_code)->toBe('DE');
});

it('updates an existing address', function (): void {
    $user = $this->withCustomerModule();
    $this->actingAs($user);

    $customer = Customer::factory()->create(['tenant_id' => $user->selected_tenant_id]);
    $address = CustomerAddress::factory()->create([
        'tenant_id' => $user->selected_tenant_id,
        'customer_id' => $customer->id,
    ]);

    Livewire::test('customer::customer-address-detail', ['modelId' => $address->id])
        ->assertSet('modelId', $address->id)
        ->set('detailData.address_line_1', 'Neue Straße 2')
        ->call('store')
        ->assertHasNoErrors();

    expect($address->refresh()->address_line_1)->toBe('Neue Straße 2');
});

it('lists only the addresses of the given customer', function (): void {
    $user = $this->withCustomerModule();
    $this->actingAs($user);

    $customer = Customer::factory()->create(['tenant_id' => $user->selected_tenant_id]);
    $other = Customer::factory()->create(['tenant_id' => $user->selected_tenant_id]);

    CustomerAddress::factory()->create([
        'tenant_id' => $user->selected_tenant_id,
        'customer_id' => $customer->id,
        'address_line_1' => 'Own Street 1',
    ]);
    CustomerAddress::factory()->create([
        'tenant_id' => $user->selected_tenant_id,
        'customer_id' => $other->id,
        'address_line_1' => 'Other Street 9',
    ]);

    Livewire::test('customer::customer-addresses-list', ['customerId' => $customer->id])
        ->assertSee('Own Street 1')
        ->assertDontSee('Other Street 9');
});

it('registers the customerAddressRelation picker type', function (): void {
    $definition = app(\Noerd\Services\RelationFieldRegistry::class)->resolve('customerAddressRelation');

    expect($definition)->not->toBeNull();
    expect($definition->listComponent)->toBe('customer::customer-addresses-list');

    $address = CustomerAddress::factory()->create(['label' => 'Zentrale']);
    expect($definition->resolveTitleForValue($address->id))->toBe('Zentrale');
});

it('scopes the picker list by the host id supplied by the relation field', function (): void {
    $user = $this->withCustomerModule();
    $this->actingAs($user);

    $customer = Customer::factory()->create(['tenant_id' => $user->selected_tenant_id]);
    $other = Customer::factory()->create(['tenant_id' => $user->selected_tenant_id]);

    CustomerAddress::factory()->create([
        'tenant_id' => $user->selected_tenant_id,
        'customer_id' => $customer->id,
        'address_line_1' => 'Own Street 1',
    ]);
    CustomerAddress::factory()->create([
        'tenant_id' => $user->selected_tenant_id,
        'customer_id' => $other->id,
        'address_line_1' => 'Other Street 9',
    ]);

    Livewire::test('customer::customer-addresses-list', ['id' => $customer->id])
        ->assertSee('Own Street 1')
        ->assertDontSee('Other Street 9');
});

it('makes the first address both defaults automatically', function (): void {
    $user = $this->withCustomerModule();
    $this->actingAs($user);

    $customer = Customer::factory()->create(['tenant_id' => $user->selected_tenant_id]);

    Livewire::test('customer::customer-address-detail')
        ->call('customerSelected', $customer->id)
        ->set('detailData.address_line_1', 'Erste Straße 1')
        ->call('store')
        ->assertHasNoErrors();

    $customer->refresh();
    $first = $customer->defaultInvoiceAddress;
    expect($first?->address_line_1)->toBe('Erste Straße 1');
    expect($customer->default_delivery_address_id)->toBe($first->id);

    Livewire::test('customer::customer-address-detail')
        ->call('customerSelected', $customer->id)
        ->set('detailData.address_line_1', 'Zweite Straße 2')
        ->call('store')
        ->assertHasNoErrors();

    $customer->refresh();
    expect($customer->addresses()->count())->toBe(2);
    expect($customer->default_invoice_address_id)->toBe($first->id);
});

it('refreshes the default address options in the customer detail', function (): void {
    $user = $this->withCustomerModule();
    $this->actingAs($user);

    $customer = Customer::factory()->create(['tenant_id' => $user->selected_tenant_id]);
    $address = CustomerAddress::factory()->create([
        'tenant_id' => $user->selected_tenant_id,
        'customer_id' => $customer->id,
        'label' => 'Zentrale',
    ]);

    Livewire::withUrlParams(['customerId' => $customer->id])
        ->test('customer::customer-detail')
        ->set('detailData.default_invoice_address_id', $address->id)
        ->set('detailData.default_delivery_address_id', $address->id)
        ->call('store')
        ->assertHasNoErrors();

    $customer->refresh();
    expect($customer->default_invoice_address_id)->toBe($address->id);
    expect($customer->default_delivery_address_id)->toBe($address->id);
});

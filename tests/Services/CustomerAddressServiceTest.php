<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Noerd\Customer\Models\Customer;
use Noerd\Customer\Models\CustomerAddress;
use Noerd\Customer\Services\CustomerAddressService;
use Noerd\Models\Tenant;

uses(Tests\TestCase::class);
uses(RefreshDatabase::class);

it('creates a new address for the customer', function (): void {
    $tenant = Tenant::factory()->create();
    $customer = Customer::factory()->create(['tenant_id' => $tenant->id]);

    $address = app(CustomerAddressService::class)->upsertFor($customer, [
        'address_line_1' => 'Musterweg 1',
        'postal_code' => '12345',
        'locality' => 'Berlin',
        'country_code' => 'DE',
    ]);

    expect($address->customer_id)->toBe($customer->id);
    expect($address->tenant_id)->toBe($tenant->id);
    expect(CustomerAddress::withoutGlobalScopes()->count())->toBe(1);
});

it('reuses an existing address with the same normalized data', function (): void {
    $tenant = Tenant::factory()->create();
    $customer = Customer::factory()->create(['tenant_id' => $tenant->id]);
    $service = app(CustomerAddressService::class);

    $first = $service->upsertFor($customer, [
        'address_line_1' => 'Musterweg 1',
        'postal_code' => '12345',
        'locality' => 'Berlin',
    ]);

    $second = $service->upsertFor($customer, [
        'address_line_1' => 'musterweg  1',
        'postal_code' => ' 12345 ',
        'locality' => 'BERLIN',
    ]);

    expect($second->id)->toBe($first->id);
    expect(CustomerAddress::withoutGlobalScopes()->count())->toBe(1);
});

it('gives an identical address of another customer its own row', function (): void {
    $tenant = Tenant::factory()->create();
    $customerA = Customer::factory()->create(['tenant_id' => $tenant->id]);
    $customerB = Customer::factory()->create(['tenant_id' => $tenant->id]);
    $service = app(CustomerAddressService::class);

    $data = ['address_line_1' => 'Musterweg 1', 'postal_code' => '12345', 'locality' => 'Berlin'];
    $addressA = $service->upsertFor($customerA, $data);
    $addressB = $service->upsertFor($customerB, $data);

    expect($addressA->id)->not->toBe($addressB->id);
    expect(CustomerAddress::withoutGlobalScopes()->count())->toBe(2);
});

it('sets the default flags on the customer', function (): void {
    $tenant = Tenant::factory()->create();
    $customer = Customer::factory()->create(['tenant_id' => $tenant->id]);

    $address = app(CustomerAddressService::class)->upsertFor($customer, [
        'address_line_1' => 'Musterweg 1',
    ], asInvoiceDefault: true, asDeliveryDefault: true);

    $customer->refresh();
    expect($customer->default_invoice_address_id)->toBe($address->id);
    expect($customer->default_delivery_address_id)->toBe($address->id);
});

it('leaves unflagged defaults untouched', function (): void {
    $tenant = Tenant::factory()->create();
    $customer = Customer::factory()->create(['tenant_id' => $tenant->id]);
    $service = app(CustomerAddressService::class);

    $invoice = $service->upsertFor($customer, ['address_line_1' => 'Billing Street 1'], asInvoiceDefault: true, asDeliveryDefault: true);
    $delivery = $service->upsertFor($customer, ['address_line_1' => 'Delivery Street 2'], asDeliveryDefault: true);

    $customer->refresh();
    expect($customer->default_invoice_address_id)->toBe($invoice->id);
    expect($customer->default_delivery_address_id)->toBe($delivery->id);
});

it('setDefaults rejects addresses of another customer', function (): void {
    $tenant = Tenant::factory()->create();
    $customer = Customer::factory()->create(['tenant_id' => $tenant->id]);
    $other = Customer::factory()->create(['tenant_id' => $tenant->id]);
    $service = app(CustomerAddressService::class);

    $own = $service->upsertFor($customer, ['address_line_1' => 'Own Street 1']);
    $foreign = $service->upsertFor($other, ['address_line_1' => 'Foreign Street 1']);

    $service->setDefaults($customer, $own->id, $foreign->id);

    $customer->refresh();
    expect($customer->default_invoice_address_id)->toBe($own->id);
    expect($customer->default_delivery_address_id)->toBeNull();
});

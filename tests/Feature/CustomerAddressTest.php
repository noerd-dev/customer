<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Noerd\Customer\Models\Customer;
use Noerd\Customer\Models\CustomerAddress;
use Noerd\Models\Tenant;

uses(Tests\TestCase::class);
uses(RefreshDatabase::class);

it('creates addresses with an auto-incrementing integer primary key', function (): void {
    $address = CustomerAddress::factory()->create();

    expect($address->id)->toBeInt();
    expect($address->id)->toBeGreaterThan(0);
});

it('computes the fingerprint on create and recomputes it on update', function (): void {
    $address = CustomerAddress::factory()->create(['address_line_1' => 'Musterweg 1']);

    expect($address->fingerprint)->toBeString();
    expect(mb_strlen($address->fingerprint))->toBe(64);

    $original = $address->fingerprint;
    $address->update(['address_line_1' => 'Musterweg 2']);

    expect($address->fingerprint)->not->toBe($original);
});

it('normalizes case and whitespace in the fingerprint', function (): void {
    $base = [
        'country_code' => 'DE',
        'postal_code' => '12345',
        'locality' => 'Berlin',
        'address_line_1' => 'Musterweg 1',
    ];

    $variant = [
        'country_code' => 'de',
        'postal_code' => ' 12345 ',
        'locality' => 'BERLIN',
        'address_line_1' => "Musterweg   1",
    ];

    expect(CustomerAddress::computeFingerprint($base))
        ->toBe(CustomerAddress::computeFingerprint($variant));
});

it('links customer and addresses through the relations', function (): void {
    $tenant = Tenant::factory()->create();
    $customer = Customer::factory()->create(['tenant_id' => $tenant->id]);
    $address = CustomerAddress::factory()->create([
        'tenant_id' => $tenant->id,
        'customer_id' => $customer->id,
    ]);

    $customer->forceFill([
        'default_invoice_address_id' => $address->id,
        'default_delivery_address_id' => $address->id,
    ])->save();
    $customer->refresh();

    expect($customer->addresses)->toHaveCount(1);
    expect($customer->defaultInvoiceAddress->id)->toBe($address->id);
    expect($customer->defaultDeliveryAddress->id)->toBe($address->id);
    expect($address->customer->id)->toBe($customer->id);
});

it('nulls the default address ids when the address is deleted', function (): void {
    $tenant = Tenant::factory()->create();
    $customer = Customer::factory()->create(['tenant_id' => $tenant->id]);
    $address = CustomerAddress::factory()->create([
        'tenant_id' => $tenant->id,
        'customer_id' => $customer->id,
    ]);

    $customer->forceFill([
        'default_invoice_address_id' => $address->id,
        'default_delivery_address_id' => $address->id,
    ])->save();

    $address->delete();
    $customer->refresh();

    expect($customer->default_invoice_address_id)->toBeNull();
    expect($customer->default_delivery_address_id)->toBeNull();
});

it('deletes the addresses when the customer is force deleted', function (): void {
    $tenant = Tenant::factory()->create();
    $customer = Customer::factory()->create(['tenant_id' => $tenant->id]);
    CustomerAddress::factory()->create([
        'tenant_id' => $tenant->id,
        'customer_id' => $customer->id,
    ]);

    $customer->forceDelete();

    expect(CustomerAddress::withoutGlobalScopes()->count())->toBe(0);
});

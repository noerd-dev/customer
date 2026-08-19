<?php

namespace Noerd\Customer\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Noerd\Customer\Models\Customer;

class CustomerService
{
    private const NON_COLUMN_KEYS = [
        'email',
        'audits',
        'invoiceAddress',
        'addresses',
        'default_invoice_address',
        'default_delivery_address',
    ];

    /**
     * Only assignable through guardDefaultAddressIds() on the update path — the
     * email/create paths must never mass-assign them.
     */
    private const DEFAULT_ADDRESS_KEYS = ['default_invoice_address_id', 'default_delivery_address_id'];

    public function save(int $tenantId, array $detailData, ?int $customerId = null, ?array $address = null): Customer
    {
        $email = $detailData['email'] ?? null;
        $attributes = Arr::except($detailData, self::NON_COLUMN_KEYS);

        return DB::transaction(function () use ($tenantId, $attributes, $email, $customerId, $address): Customer {
            if ($customerId) {
                $customer = Customer::findOrFail($customerId);
                $attributes = $this->guardDefaultAddressIds($customer, $attributes);
                $customer->update(array_merge($attributes, ['email' => $email, 'tenant_id' => $tenantId]));
            } elseif ($email) {
                $customer = $this->findOrCreateByEmail($tenantId, $email, array_merge($attributes, ['tenant_id' => $tenantId]));
            } else {
                $customer = $this->createWithoutEmail($tenantId, $attributes);
            }

            $this->applyAddress($customer, $address);

            return $customer;
        });
    }

    public function findOrCreateByEmail(int $tenantId, string $email, array $attributes = [], ?array $address = null): Customer
    {
        $customer = Customer::withoutGlobalScopes()->updateOrCreate(
            [
                'tenant_id' => $tenantId,
                'email' => $email,
            ],
            Arr::except($attributes, [...self::NON_COLUMN_KEYS, ...self::DEFAULT_ADDRESS_KEYS]),
        );

        $this->applyAddress($customer, $address);

        return $customer;
    }

    public function createWithoutEmail(int $tenantId, array $attributes, ?array $address = null): Customer
    {
        $customer = Customer::withoutGlobalScopes()->create(
            array_merge(Arr::except($attributes, [...self::NON_COLUMN_KEYS, ...self::DEFAULT_ADDRESS_KEYS]), ['tenant_id' => $tenantId]),
        );

        $this->applyAddress($customer, $address);

        return $customer;
    }

    private function applyAddress(Customer $customer, ?array $address): void
    {
        $addressService = app(CustomerAddressService::class);

        if ($address !== null && $addressService->hasAddressData($address)) {
            $addressService->upsertFor($customer, $address, asInvoiceDefault: true, asDeliveryDefault: true);
        }
    }

    /**
     * The default address FKs arrive through the mass-assignable detail payload:
     * normalize '' (select placeholder) to null and drop ids not owned by the
     * customer.
     *
     * @return array<string, mixed>
     */
    private function guardDefaultAddressIds(Customer $customer, array $attributes): array
    {
        foreach (['default_invoice_address_id', 'default_delivery_address_id'] as $key) {
            if (! array_key_exists($key, $attributes)) {
                continue;
            }

            $value = $attributes[$key] ?: null;

            if ($value !== null) {
                $owned = $customer->addresses()->withoutGlobalScopes()
                    ->where('customer_id', $customer->id)
                    ->where('id', $value)
                    ->exists();
                $value = $owned ? $value : null;
            }

            $attributes[$key] = $value;
        }

        return $attributes;
    }
}

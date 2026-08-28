<?php

namespace Noerd\Customer\Services;

use Illuminate\Support\Arr;
use Noerd\Customer\Models\Customer;
use Noerd\Customer\Models\CustomerAddress;

class CustomerAddressService
{
    private const ADDRESS_FIELDS = [
        'label',
        'country_code',
        'administrative_area_code',
        'administrative_area',
        'locality',
        'postal_code',
        'sorting_code',
        'address_line_1',
        'address_line_2',
        'street_name',
        'house_number',
        'latitude',
        'longitude',
        'verified_at',
        'verification_provider',
    ];

    private const CORE_FIELDS = ['address_line_1', 'address_line_2', 'postal_code', 'locality', 'country_code'];

    /**
     * The code column is char(2) and the values come from tenant-maintainable
     * collection entries — anything that is not a two-letter code becomes null
     * instead of a database error.
     */
    public static function normalizeCountryCode(mixed $value): ?string
    {
        $value = mb_strtoupper(mb_trim((string) ($value ?? '')));

        return preg_match('/^[A-Z]{2}$/', $value) === 1 ? $value : null;
    }

    /**
     * Reuse an existing address of THIS customer with the same fingerprint,
     * otherwise create a new one. tenant_id/customer_id always come from the
     * customer — never from the payload.
     */
    public function upsertFor(
        Customer $customer,
        array $data,
        bool $asInvoiceDefault = false,
        bool $asDeliveryDefault = false,
    ): CustomerAddress {
        $data = Arr::only($data, self::ADDRESS_FIELDS);
        $data = $this->normalize($data);
        $data['address_line_1'] = mb_trim((string) ($data['address_line_1'] ?? ''));

        $fingerprint = CustomerAddress::computeFingerprint($data);

        $address = CustomerAddress::withoutGlobalScopes()
            ->where('customer_id', $customer->id)
            ->where('fingerprint', $fingerprint)
            ->first();

        if ($address) {
            $update = Arr::only($data, ['label', 'latitude', 'longitude', 'verified_at', 'verification_provider']);
            $update = array_filter($update, fn(mixed $value): bool => $value !== null);
            if ($update !== []) {
                $address->update($update);
            }
        } else {
            $address = CustomerAddress::withoutGlobalScopes()->create(array_merge($data, [
                'customer_id' => $customer->id,
                'tenant_id' => $customer->tenant_id,
            ]));
        }

        if ($asInvoiceDefault || $asDeliveryDefault) {
            $customer->forceFill(array_filter([
                'default_invoice_address_id' => $asInvoiceDefault ? $address->id : null,
                'default_delivery_address_id' => $asDeliveryDefault ? $address->id : null,
            ]))->save();
        }

        return $address;
    }

    /**
     * Assign default addresses with an ownership guard — an id not belonging to
     * the customer is ignored (set to null).
     */
    public function setDefaults(Customer $customer, ?int $invoiceAddressId, ?int $deliveryAddressId): void
    {
        $customer->forceFill([
            'default_invoice_address_id' => $this->ownedAddressId($customer, $invoiceAddressId),
            'default_delivery_address_id' => $this->ownedAddressId($customer, $deliveryAddressId),
        ])->save();
    }

    /**
     * True when the payload carries at least one non-empty core address field.
     */
    public function hasAddressData(?array $data): bool
    {
        foreach (self::CORE_FIELDS as $field) {
            if (mb_trim((string) ($data[$field] ?? '')) !== '') {
                return true;
            }
        }

        return false;
    }

    private function ownedAddressId(Customer $customer, ?int $addressId): ?int
    {
        if (! $addressId) {
            return null;
        }

        $owned = CustomerAddress::withoutGlobalScopes()
            ->where('customer_id', $customer->id)
            ->where('id', $addressId)
            ->exists();

        return $owned ? $addressId : null;
    }

    /**
     * @return array<string, mixed> empty strings normalized to null
     */
    private function normalize(array $data): array
    {
        $data = array_map(
            fn(mixed $value): mixed => is_string($value) && mb_trim($value) === '' ? null : $value,
            $data,
        );

        if (array_key_exists('country_code', $data)) {
            $data['country_code'] = self::normalizeCountryCode($data['country_code']);
        }

        return $data;
    }
}

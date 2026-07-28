<?php

namespace Noerd\Customer\Services;

use Illuminate\Support\Arr;
use Noerd\Customer\Models\Customer;

class CustomerService
{
    public function save(int $tenantId, array $detailData, ?int $customerId = null): Customer
    {
        $email = $detailData['email'] ?? null;
        $attributes = Arr::except($detailData, ['email', 'audits']);

        if ($customerId) {
            $customer = Customer::findOrFail($customerId);
            $customer->update(array_merge($attributes, ['email' => $email, 'tenant_id' => $tenantId]));

            return $customer;
        }

        if ($email) {
            return $this->findOrCreateByEmail($tenantId, $email, array_merge($attributes, ['tenant_id' => $tenantId]));
        }

        return $this->createWithoutEmail($tenantId, $attributes);
    }

    public function findOrCreateByEmail(int $tenantId, string $email, array $attributes = []): Customer
    {
        return Customer::withoutGlobalScopes()->updateOrCreate(
            [
                'tenant_id' => $tenantId,
                'email' => $email,
            ],
            $attributes,
        );
    }

    public function createWithoutEmail(int $tenantId, array $attributes): Customer
    {
        return Customer::withoutGlobalScopes()->create(
            array_merge($attributes, ['tenant_id' => $tenantId]),
        );
    }
}

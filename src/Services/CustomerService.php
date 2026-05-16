<?php

namespace Noerd\Customer\Services;

use Noerd\Customer\Models\Customer;

class CustomerService
{
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

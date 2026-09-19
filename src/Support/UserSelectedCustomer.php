<?php

namespace Noerd\Customer\Support;

use Noerd\Customer\Models\Customer;
use Noerd\Helpers\TenantHelper;

/**
 * Single source of truth for the customer the current backend user has selected.
 * Persisted via session so the selection survives navigation and stays available
 * to any component acting on it (e.g. the quick-menu indicator or module flows).
 */
class UserSelectedCustomer
{
    private const SESSION_KEY = 'admin.selectedCustomerId';

    public static function getId(): ?int
    {
        $id = session(self::SESSION_KEY);

        return $id ? (int) $id : null;
    }

    /**
     * The selected customer — always re-checked against the tenant the user is
     * working in.
     *
     * The id comes out of the session, and a session may still carry a
     * selection from before a tenant switch (or one a tampered picker event
     * put there). Resolving it unscoped would let downstream flows — a stamp
     * card sale, a booking — act on a customer of another tenant.
     */
    public static function get(): ?Customer
    {
        $id = self::getId();

        if (! $id) {
            return null;
        }

        $customer = Customer::findForTenant($id, TenantHelper::currentTenantId());

        if (! $customer) {
            self::clear();
        }

        return $customer;
    }

    public static function set(int $customerId): void
    {
        session([self::SESSION_KEY => $customerId]);
    }

    public static function clear(): void
    {
        session()->forget(self::SESSION_KEY);
    }
}

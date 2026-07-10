<?php

namespace Noerd\Customer\Support;

use Noerd\Customer\Models\Customer;

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

    public static function get(): ?Customer
    {
        $id = self::getId();

        if (! $id) {
            return null;
        }

        return Customer::withoutGlobalScopes()->find($id);
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

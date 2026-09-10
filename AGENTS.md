# AGENTS.md — noerd/customer

Contributor notes for humans and AI agents working on the Customer module. The rules for
building WITH noerd (lists, details, pages, modals, modules, tests) come from the `noerd/noerd`
Boost guideline and skills; the module-specific rules are in
`resources/boost/guidelines/core.blade.php`. Both are rendered into the host project's agent files
by `php artisan boost:update` (add `noerd/customer` to the `packages` array in `boost.json`).

## What this module is

The business-partner model for the SIMPLE noerd apps (liefertool, liefertool-shop, booking,
booking-members, pos, reservation): a `Customer` with structured `CustomerAddress` records,
default invoice/delivery addresses, an inline invoice-address relation form, a customer picker
relation field, an address-card relation field and the tenant-scoped quick-menu customer selector.
The large apps (accounting, crm) use `noerd/party` instead — the two modules must never reference
each other (no FK, no sync, no composer requirement; enforced by
`tests/Feature/ModuleBoundaryTest.php`).

## Layout

- `app-configs/customer/` — YAML templates (lists/, details/, navigation.yml); the installed copy
  lives in the host's `app-configs/customer/` — change both
- `app-configs/stubs/add_customer_tenant_app.php.stub` — the idempotent tenant-app migration
  published by `noerd:install-customer`
- `resources/views/components/` — Livewire single-file components (`customers-list`,
  `customer-detail`, `customer-addresses-list`, `customer-address-detail`,
  `customer-address-card-field`), Livewire namespace `customer::`; the subfolder `quick-menu/`
  holds the quick-menu customer selector
- `src/Models/` (`Customer`, `CustomerAddress`), `src/Services/` (`CustomerService`,
  `CustomerAddressService`), `src/Support/UserSelectedCustomer.php`, `src/Commands/`,
  `src/Providers/CustomerServiceProvider.php` (relation field types `customerRelation`,
  `customerAddressRelation`, `customerAddressCardRelation`)
- `database/migrations|factories/`, `tests/` (Pest), `resources/lang/de.json`
- Tables are `customers` and `customer_addresses`; the tenant app name is `CUSTOMER` (uppercase)

## Commands

- `php artisan noerd:install-customer` — first installation (asks for the tenant assignment,
  publishes the auditing migration when missing)
- `php artisan noerd:update-customer` — idempotent YAML update, discovered by `noerd:update-all`

## Working on the module

- Tests bind the host `Tests\TestCase` (MySQL, `RefreshDatabase`). From the host project:
  `php artisan test --compact app-modules/customer/tests`. Tests prove mechanics, never the
  current YAML configuration; `tests/Traits/CreatesCustomerUser.php` sets up tenant, user and
  the `CUSTOMER` tenant app.
- `Noerd\Customer\Tests\` stays in the production `autoload` of `composer.json`: a
  path-repository's `autoload-dev` is not loaded by the host, and host-root runs need the trait.
- Format from the host project root with an explicit path: `vendor/bin/pint app-modules/customer`
  (a plain `--dirty` run silently skips submodule files)
- Keep the module independent of other optional modules — above all of `noerd/party`;
  project-specific fields go into `custom_attributes` on `Customer`, never into module code or
  module YAML
- Address persistence always goes through `CustomerAddressService` (fingerprint dedupe, ownership
  guard on the default-address ids); never mass-assign `default_*_address_id` from a payload
- When a feature changes: update the YAML in both places, `resources/lang/de.json`, the tests,
  `resources/boost/guidelines/core.blade.php` and `README.md`
- Releasing: bump `"version"` in `composer.json` to the tag in the tagged commit

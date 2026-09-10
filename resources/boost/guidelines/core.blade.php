@verbatim
## Customer Module

The Customer module is a Noerd tenant app (Composer package `noerd/customer`, namespace
`Noerd\Customer`) providing the business-partner model for SIMPLE apps — liefertool,
liefertool-shop, booking, booking-members, pos, reservation. Large apps (accounting, crm) use the
`party` module instead. The framework rules (lists, details, pages, modals, themes, tests,
translations) come from the `noerd/noerd` guideline — this block only adds what is specific to
this module.

### Customer vs. party — the boundary
- `customer` and `party` must know NOTHING of each other: no `Noerd\Party\` reference in `src/`,
  no `noerd/party` composer requirement, no FK column in either direction, no sync code. An app
  picks ONE of the two. Guarded by `tests/Feature/ModuleBoundaryTest.php` (source scan +
  composer check + `assertModuleDependenciesDeclared()`)
- Consumer apps link a customer to THEIR user through `customers.website_user_id` (plain id
  column, no FK — the website user table belongs to the consumer app):
  `Customer::forWebsiteUser($userId)` scope

### Domain
- `Customer` (table `customers`, `$guarded = ['id']`, `BelongsToTenant`, `Auditable`) — `name`,
  `company_name`, `email` (unique per tenant: `customers_tenant_email_unique`), `phone`,
  `internal_comment`, `custom_attributes` (JSON cast, project-specific fields), `website_user_id`;
  `addresses()` hasMany, `defaultInvoiceAddress()` / `defaultDeliveryAddress()` belongTo
  `CustomerAddress` via `default_invoice_address_id` / `default_delivery_address_id`
- `CustomerAddress` (table `customer_addresses`, `BelongsToTenant`, `Auditable`) — structured
  address (`address_line_1` required, `address_line_2`, `postal_code`, `locality`, `country_code`
  char(2), `administrative_area*`, `street_name`, `house_number`, `latitude`/`longitude`,
  `verified_at`, `label`). `fingerprint` (sha256 over `FINGERPRINT_FIELDS`) is COMPUTED in the
  `saving` hook — never write it by hand; data migrations replicate the field list inline, keep
  both in sync
- `Customer::relationForms()` declares the `invoiceAddress` relation form
  (`DeclaresRelationForms` + `RelationFormDefinition`): detail YAML fields under
  `detailData.invoiceAddress.*` edit the default invoice address inline; persistence runs through
  `CustomerAddressService::upsertFor()` and sets BOTH default FKs. Never hand-roll
  hydrate/persist logic for it in a component
- Services (`src/Services/`): `CustomerService::save()` (update by id, else `findOrCreateByEmail()`
  keyed on `tenant_id` + `email`, else `createWithoutEmail()`; strips non-column keys, guards the
  default-address ids against foreign ownership), `CustomerAddressService` (`upsertFor()` =
  fingerprint dedupe per customer, `setDefaults()` with ownership guard, `hasAddressData()`,
  `normalizeCountryCode()` — anything but a two-letter code becomes null). `tenant_id` /
  `customer_id` of an address always come from the customer, never from the payload
- `Noerd\Customer\Support\UserSelectedCustomer` — session-backed "currently selected customer" of
  the backend user (`getId()`, `get()`, `set()`, `clear()`); the single source of truth for flows
  that act on a picked customer

### Structure
- Livewire components (`resources/views/components/`, namespace `customer::`; the provider
  registers the namespace AND `Livewire::addLocation`): `customers-list` (slim list, custom
  `listData()` only eager-loads the default addresses), `customer-detail` (`$detailPrimary =
  'customerId'`, `store()` via `CustomerService`), `customer-addresses-list` (narrowed by
  `customerId`, or by the picker-supplied `id` when used as a relation-field picker; empty
  `rendering()` on purpose), `customer-address-detail` (`$detailPrimary = 'customerAddressId'`,
  reacts to `customerSelected`; the customer's FIRST address becomes both defaults),
  `customer-address-card-field` (custom relation renderer extending
  `Noerd\Livewire\RelationFieldComponent`), `quick-menu/customer-select-component` (the
  tenant-scoped quick-menu selector, listens to `customerSelected` / `customerCleared`, persists
  via `UserSelectedCustomer`; declared in the host's `app-configs/quick-menu.yml` with `apps:`)
- Relation field types registered in `CustomerServiceProvider`: `customerRelation`
  (select event `customerSelected`, title `name`), `customerAddressRelation` (generic input) and
  `customerAddressCardRelation` (same picker rendered as an address card via `fieldComponent:`)
- YAML: `app-configs/customer/{lists,details}/` + `navigation.yml` — keep the module copy and the
  installed project copy (`app-configs/customer/…`) in sync
- Routes: `routes/customer-routes.php` — middleware `['noerd']` (no `app-access` gate: the
  screens are shared by every consumer app), names `customers` (list), `customer.detail` and
  `customer.address.detail` (record routes used by route modals)
- Tenant app name is `CUSTOMER` (uppercase) — gates and test traits compare exactly; registered
  idempotently by the stub `app-configs/stubs/add_customer_tenant_app.php.stub` that
  `noerd:install-customer` publishes into the host
- Translations: `resources/lang/de.json` (English keys); factories in `database/factories/`
  (`CustomerFactory`, `CustomerAddressFactory`); migrations in `database/migrations/`

### Commands
- `php artisan noerd:install-customer` — installs YAML configs, registers the tenant app, runs
  migrations and publishes the auditing migration when missing (`PublishesAuditMigration`)
- `php artisan noerd:update-customer` — idempotent update of the YAML configs (picked up by
  `noerd:update-all`)

### Tests
- Pest tests in `tests/`, bound to the host `Tests\TestCase` (`uses(Tests\TestCase::class,
  RefreshDatabase::class)`). Run from the host: `php artisan test --compact
  app-modules/customer/tests`. `Noerd\Customer\Tests\` stays in the production `autoload` — a
  path-repository's `autoload-dev` is not loaded by the host
- `tests/Traits/CreatesCustomerUser.php` provides `withCustomerModule()` (tenant + user +
  `CUSTOMER` tenant app, selected app set)
- Prove mechanics, never the current YAML configuration (see the `noerd-testing` skill)

### Reference implementations
- `customers-list.blade.php` — the slim list (`$listModel` + `$detailRoute`)
- `customer-detail.blade.php` — the slim detail (`$detailModel` + `$detailPrimary`, custom
  `store()` delegating to a service and ending in `storeProcess()`)
- `Customer::relationForms()` — relation forms via `DeclaresRelationForms` with
  `persistWhen` / `persistUsing`
- `customer-address-card-field.blade.php` — a custom relation-field renderer
  (`fieldComponent:` on a `RelationFieldDefinition`)
- `CustomerInstallCommand` / `CustomerUpdateCommand` — the install/update command pair
  (`HasModuleInstallation` + `RequiresNoerdInstallation`, slim update subclass calling
  `runModuleUpdate()`)
- `tests/Feature/ModuleBoundaryTest.php` — the architecture guard pattern for module boundaries
@endverbatim

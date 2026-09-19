<?php

namespace Noerd\Customer\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Noerd\Contracts\DeclaresRelationForms;
use Noerd\Customer\Database\Factories\CustomerFactory;
use Noerd\Customer\Services\CustomerAddressService;
use Noerd\Support\RelationFormDefinition;
use Noerd\Traits\BelongsToTenant;
use OwenIt\Auditing\Contracts\Auditable;

class Customer extends Model implements Auditable, DeclaresRelationForms
{
    use BelongsToTenant;
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    protected $guarded = ['id'];

    protected $casts = [
        'custom_attributes' => 'array',
    ];

    /**
     * Detail YAML fields under `detailData.invoiceAddress.*` edit the default
     * invoice address inline — hydrated and persisted by the framework (see
     * app-modules/noerd/docs/relation-forms.md). Persisting goes through the
     * address service: fingerprint dedupe plus setting BOTH default FKs.
     */
    public static function relationForms(): array
    {
        return [
            'invoiceAddress' => RelationFormDefinition::make(
                relation: 'defaultInvoiceAddress',
                fields: ['address_line_1', 'address_line_2', 'postal_code', 'locality', 'country_code'],
            )
                ->persistWhen(fn(array $data): bool => app(CustomerAddressService::class)->hasAddressData($data))
                ->persistUsing(function (Customer $customer, array $data): void {
                    app(CustomerAddressService::class)->upsertFor(
                        $customer,
                        $data,
                        asInvoiceDefault: true,
                        asDeliveryDefault: true,
                    );
                }),
        ];
    }

    /**
     * Narrow to the customer linked to a user of the consumer app's website
     * guard. The website user table belongs to the consumer application, so the
     * link is a plain id column without a foreign key.
     */
    public function scopeForWebsiteUser(Builder $query, int $userId): Builder
    {
        return $query->where('website_user_id', $userId);
    }

    /**
     * Resolve a customer id that came from OUTSIDE — a picker event, a mount
     * argument, a session value — against a tenant that is known to be right.
     *
     * Livewire events are dispatchable from the browser, and the tenant scope
     * does not apply on public frontends (no signed-in user), so a plain
     * `find()` on such an id reads any customer in the installation. This
     * lifts the scopes deliberately (public frontends need that) and replaces
     * them with the tenant the caller vouches for. A null tenant resolves
     * nothing — there is no "any tenant" answer.
     */
    public static function findForTenant(mixed $customerId, ?int $tenantId): ?self
    {
        if ($tenantId === null || ! is_numeric($customerId)) {
            return null;
        }

        return self::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->find((int) $customerId);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(CustomerAddress::class);
    }

    public function defaultInvoiceAddress(): BelongsTo
    {
        return $this->belongsTo(CustomerAddress::class, 'default_invoice_address_id');
    }

    public function defaultDeliveryAddress(): BelongsTo
    {
        return $this->belongsTo(CustomerAddress::class, 'default_delivery_address_id');
    }

    protected static function newFactory(): CustomerFactory
    {
        return CustomerFactory::new();
    }
}

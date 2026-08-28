<?php

namespace Noerd\Customer\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Noerd\Customer\Database\Factories\CustomerAddressFactory;
use Noerd\Traits\BelongsToTenant;
use OwenIt\Auditing\Contracts\Auditable;

class CustomerAddress extends Model implements Auditable
{
    use BelongsToTenant;
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    /**
     * The address fields feeding the fingerprint, in canonical order. Data
     * migrations replicate this list inline — keep both in sync.
     */
    public const FINGERPRINT_FIELDS = [
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
    ];

    protected $guarded = ['id'];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'verified_at' => 'datetime',
    ];

    /**
     * Canonical address fingerprint: per field collapse Unicode whitespace runs
     * to a single space, trim, lowercase; join the fields with '|'; sha256-hex.
     */
    public static function computeFingerprint(array $attributes): string
    {
        $normalize = fn(mixed $value): string => mb_strtolower(
            mb_trim(preg_replace('/\s+/u', ' ', (string) ($value ?? ''))),
        );

        $parts = array_map(
            fn(string $field): string => $normalize($attributes[$field] ?? null),
            self::FINGERPRINT_FIELDS,
        );

        return hash('sha256', implode('|', $parts));
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    protected static function booted(): void
    {
        static::saving(function (CustomerAddress $address): void {
            $address->fingerprint = self::computeFingerprint($address->getAttributes());
        });
    }

    protected static function newFactory(): CustomerAddressFactory
    {
        return CustomerAddressFactory::new();
    }
}

<?php

use Illuminate\Support\Arr;
use Livewire\Attributes\On;
use Livewire\Component;
use Noerd\Traits\NoerdDetail;
use Noerd\Customer\Models\Customer;
use Noerd\Customer\Models\CustomerAddress;

new class extends Component {
    use NoerdDetail;

    public $detailModel = CustomerAddress::class;

    public ?string $detailPrimary = 'customerAddressId';

    public function mount(): void
    {
        $this->initDetail();
        $this->preselect('customer_id');
    }

    #[On('customerSelected')]
    public function customerSelected($customerId): void
    {
        $customer = Customer::find($customerId);
        if ($customer) {
            $this->detailData['customer_id'] = $customer->id;
            $this->detailData['tenant_id'] = $customer->tenant_id;
        }
    }

    public function store(): void
    {
        $this->validateFromLayout();

        $customer = Customer::findOrFail($this->detailData['customer_id'] ?? null);

        $attributes = Arr::only($this->detailData, [
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
        ]);
        $attributes = array_map(
            fn (mixed $value): mixed => is_string($value) && trim($value) === '' ? null : $value,
            $attributes,
        );
        $attributes['address_line_1'] = trim((string) ($this->detailData['address_line_1'] ?? ''));
        $attributes['country_code'] = \Noerd\Customer\Services\CustomerAddressService::normalizeCountryCode($this->detailData['country_code'] ?? null);

        $address = CustomerAddress::updateOrCreate(
            ['id' => $this->modelId],
            array_merge($attributes, [
                'customer_id' => $customer->id,
                'tenant_id' => $customer->tenant_id,
            ]),
        );

        // The customer's first address becomes both defaults automatically —
        // apps without the default pickers would otherwise never get one.
        if (! $customer->default_invoice_address_id && ! $customer->default_delivery_address_id) {
            $customer->forceFill([
                'default_invoice_address_id' => $address->id,
                'default_delivery_address_id' => $address->id,
            ])->save();
        }

        $this->modelId ??= $address->id;
        $this->finishStore($address);
    }
}; ?>

<x-noerd::page>
    <x-slot:header>
        <x-noerd::modal-title>{{ __('Address') }}</x-noerd::modal-title>
    </x-slot:header>

    <x-noerd::tab-content :layout="$pageLayout" :modelId="$modelId" />

    <x-slot:footer>
        <x-noerd::delete-save-bar :showDelete="isset($modelId)"/>
    </x-slot:footer>
</x-noerd::page>

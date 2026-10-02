<?php

use Livewire\Attributes\On;
use Livewire\Component;
use Noerd\Traits\NoerdDetail;
use Noerd\Customer\Models\Customer;
use Noerd\Customer\Services\CustomerService;

new class extends Component {
    use NoerdDetail;

    public $detailModel = Customer::class;

    public ?string $detailPrimary = 'customerId';

    public function mount(): void
    {
        $this->initDetail();

        if ($this->modelId) {
            $customer = Customer::with('audits')->find($this->modelId);
            if ($customer) {
                $this->detailData = $customer->toArray();
            }
        }

        $this->setPreselect('customer_id', $this->modelId);
    }

    /**
     * The address detail sets the customer's FIRST address as both defaults
     * directly in the database. Adopt those ids for every default the form
     * still holds empty — otherwise the next save of this (still open) form
     * writes its stale null back over them. A default picked in the form wins.
     */
    #[On('detailStored-customer::customer-address-detail')]
    public function addressStored(): void
    {
        if (! $this->modelId) {
            return;
        }

        $customer = Customer::find($this->modelId);
        if (! $customer) {
            return;
        }

        foreach (['default_invoice_address_id', 'default_delivery_address_id'] as $key) {
            if (empty($this->detailData[$key]) && $customer->{$key}) {
                $this->detailData[$key] = $customer->{$key};
            }
        }
    }

    public function store(): void
    {
        $this->validateFromLayout();

        $customer = app(CustomerService::class)->save(
            auth()->user()->selected_tenant_id,
            $this->detailData,
            $this->modelId,
        );

        $this->modelId ??= $customer->id;
        $this->storeProcess($customer);
    }
}; ?>

<x-noerd::page>
    <x-slot:header>
        <x-noerd::modal-title>Kunde</x-noerd::modal-title>
    </x-slot:header>

    <x-noerd::tab-content :layout="$pageLayout" :modelId="$modelId">
        <x-slot:tab2>
            @if($modelId)
                <x-noerd::audit-table :audits="$detailData['audits'] ?? []"/>
            @endif
        </x-slot:tab2>
    </x-noerd::tab-content>

    <x-slot:footer>
        <x-noerd::delete-save-bar :showDelete="isset($modelId)"
                                  :modelId="$modelId ?? null"/>
    </x-slot:footer>
</x-noerd::page>

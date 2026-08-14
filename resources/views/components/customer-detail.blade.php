<?php

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

};
?>

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
                                  :footerComponents="$pageLayout['footerComponents'] ?? []"
                                  :modelId="$modelId ?? null"/>
    </x-slot:footer>
</x-noerd::page>

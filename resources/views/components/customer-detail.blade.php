<?php

use Livewire\Attributes\Url;
use Livewire\Component;
use Noerd\Traits\NoerdDetail;
use Noerd\Customer\Models\Customer;
use Noerd\Customer\Services\CustomerService;

new class extends Component {
    use NoerdDetail;

    public const DETAIL_CLASS = Customer::class;

    #[Url(as: 'customerId', keep: false, except: '')]
    public $modelId = null;

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

        $tenantId = auth()->user()->selected_tenant_id;
        $email = $this->detailData['email'] ?? null;
        $attributes = $this->detailData;
        unset($attributes['email'], $attributes['audits']);

        $customerService = app(CustomerService::class);

        if ($this->modelId) {
            $customer = Customer::find($this->modelId);
            $customer->update(array_merge($attributes, ['email' => $email, 'tenant_id' => $tenantId]));
        } elseif ($email) {
            $attributes['tenant_id'] = $tenantId;
            $customer = $customerService->findOrCreateByEmail($tenantId, $email, $attributes);
        } else {
            $customer = $customerService->createWithoutEmail($tenantId, array_merge($attributes, ['tenant_id' => $tenantId]));
        }

        $this->showSuccessIndicator = true;

        if (! $this->modelId) {
            $this->modelId = $customer->id;
        }
    }

    public function delete(): void
    {
        $customer = Customer::find($this->modelId);
        $customer->delete();
        $this->closeModalProcess($this->getListComponent());
    }
};
?>

<x-noerd::page :disableModal="$disableModal">
    <x-slot:header>
        <x-noerd::modal-title>Kunde</x-noerd::modal-title>
    </x-slot:header>

    <x-noerd::tab-content :layout="$pageLayout" :modelId="$modelId">
        <x-slot:tab1>
        </x-slot:tab1>
        <x-slot:tab2>
            @if($modelId)
                <x-customer::customer-audit :customer="$detailData"/>
            @endif
        </x-slot:tab2>
    </x-noerd::tab-content>

    <x-slot:footer>
        <x-noerd::delete-save-bar :showDelete="isset($modelId)"
            :footerComponents="$pageLayout['footerComponents'] ?? []"
            :modelId="$modelId ?? null"/>
    </x-slot:footer>
</x-noerd::page>

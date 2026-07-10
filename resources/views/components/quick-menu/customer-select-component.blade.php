<?php

use Livewire\Attributes\On;
use Livewire\Component;
use Noerd\Customer\Support\UserSelectedCustomer;
use Noerd\Customer\Models\Customer;

new class extends Component {
    public ?int $customerId = null;

    public string $customerName = '';

    public function mount(): void
    {
        $this->syncFromSession();
    }

    #[On('customerSelected')]
    public function customerSelected(int $customerId): void
    {
        $customer = Customer::withoutGlobalScopes()->find($customerId);

        if (! $customer) {
            return;
        }

        UserSelectedCustomer::set($customer->id);

        $this->customerId = $customer->id;
        $this->customerName = $customer->name ?? '';
    }

    #[On('customerCleared')]
    public function onCustomerCleared(): void
    {
        $this->customerId = null;
        $this->customerName = '';
    }

    public function clear(): void
    {
        UserSelectedCustomer::clear();
        $this->customerId = null;
        $this->customerName = '';
        $this->dispatch('customerCleared');
    }

    private function syncFromSession(): void
    {
        $customer = UserSelectedCustomer::get();

        if ($customer) {
            $this->customerId = $customer->id;
            $this->customerName = $customer->name ?? '';
        } else {
            $this->customerId = null;
            $this->customerName = '';
        }
    }
} ?>

<div class="hidden lg:flex">
    @if($customerId)
        <div class="flex items-center gap-1">
            <x-noerd::button variant="pill"
                             icon="user"
                             @click="$modal('customer::customer-detail', { modelId: {{ $customerId }} })"
                             class="font-medium"
                             title="{{ __('Open customer') }}">
                <span class="max-w-[14rem] truncate">{{ $customerName }}</span>
            </x-noerd::button>
            <x-noerd::button variant="icon" size="sm"
                             icon="x-mark"
                             wire:click="clear"
                             class="text-gray-400 hover:text-red-600"
                             title="{{ __('Clear customer') }}" />
        </div>
    @else
        <x-noerd::button variant="pill"
                         icon="user"
                         @click="$modal('customer::customers-list', {id: null, context: 'customerId', listActionMethod: 'selectAction'})"
                         title="{{ __('Change customer') }}">
            {{ __('Choose customer') }}
        </x-noerd::button>
    @endif
</div>

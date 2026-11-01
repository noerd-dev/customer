<?php

use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Noerd\Customer\Models\CustomerAddress;
use Noerd\Traits\NoerdList;

new class extends Component {
    use NoerdList;

    public $listModel = CustomerAddress::class;
    public ?string $detailRoute = 'customer.address.detail';
    public $detailComponent = 'customer::customer-address-detail';

    public ?int $customerId = null;

    /** The hosting record's id when opened as a relation-field picker. */
    public ?int $id = null;

    public function listData(): array
    {
        $scopeCustomerId = $this->customerId ?? $this->id;

        // A picker without a customer (the customer is not saved yet) must not
        // offer the addresses of every other customer of the tenant: a picked
        // foreign address is dropped on save anyway.
        $isPickerWithoutCustomer = $scopeCustomerId === null && $this->listActionMethod === 'selectAction';

        $query = $this->listQuery($this->listModel)
            ->when($scopeCustomerId, fn(Builder $query) => $query->where('customer_id', $scopeCustomerId))
            ->when($isPickerWithoutCustomer, fn(Builder $query) => $query->whereRaw('1 = 0'));

        $rows = $this->paginateList($query);

        return $this->buildList($rows);
    }
}; ?>

<x-noerd::page>
    <x-noerd::list />
</x-noerd::page>

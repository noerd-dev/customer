<?php

use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Noerd\Traits\NoerdList;
use Noerd\Customer\Models\CustomerAddress;

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

        $rows = $this->listQuery($this->listModel)
            ->when($scopeCustomerId, fn (Builder $query) => $query->where('customer_id', $scopeCustomerId))
            ->when($isPickerWithoutCustomer, fn (Builder $query) => $query->whereRaw('1 = 0'))
            ->paginate($this->perPage);

        return $this->buildList($rows);
    }

    public function rendering(): void
    {
        // Intentionally empty: the generic deep-link handler int-casts the id,
        // which corrupts ULID primary keys ((int)'01J…' === 1). Records are
        // deep-linked through their route modal instead.
    }
}; ?>

<x-noerd::page>
    <x-noerd::list />
</x-noerd::page>

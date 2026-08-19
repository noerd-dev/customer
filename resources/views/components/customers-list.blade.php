<?php

use Livewire\Component;
use Noerd\Traits\NoerdList;
use Noerd\Customer\Models\Customer;

new class extends Component {
    use NoerdList;

    public $listModel = Customer::class;
    public ?string $detailRoute = 'customer.detail';

    public function listData(): array
    {
        // Eager-load the defaults: app YAMLs may render them as relation columns.
        $rows = $this->listQuery($this->listModel)
            ->with(['defaultInvoiceAddress', 'defaultDeliveryAddress'])
            ->paginate($this->perPage);

        return $this->buildList($rows);
    }
};
?>

<x-noerd::page>
    <x-noerd::list/>
</x-noerd::page>
<?php

use Livewire\Component;
use Noerd\Customer\Models\Customer;
use Noerd\Traits\NoerdList;

new class extends Component {
    use NoerdList;

    public $listModel = Customer::class;
    public ?string $detailRoute = 'customer.detail';

    public $detailComponent = 'customer::customer-detail';

    protected array $listWith = ['defaultInvoiceAddress', 'defaultDeliveryAddress'];
};
?>

<x-noerd::page>
    <x-noerd::list/>
</x-noerd::page>
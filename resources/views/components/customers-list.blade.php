<?php

use Livewire\Component;
use Noerd\Traits\NoerdList;
use Noerd\Customer\Models\Customer;

new class extends Component {
    use NoerdList;

    public $listModel = Customer::class;
    public ?string $detailRoute = 'customer.detail';

};
?>

<x-noerd::page>
    <x-noerd::list/>
</x-noerd::page>
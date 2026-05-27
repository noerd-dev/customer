<?php

use Livewire\Component;
use Noerd\Facades\Noerd;
use Noerd\Traits\NoerdList;
use Noerd\Customer\Models\Customer;

new class extends Component {
    use NoerdList;

    public function listAction(mixed $modelId = null, array $relations = []): void
    {
        Noerd::modal('customer::customer-detail', ['modelId' => $modelId, 'relations' => $relations]);
    }

    public function with(): array
    {
        $rows = $this->listQuery(Customer::class)->paginate($this->perPage);

        return [
            'listConfig' => $this->buildList($rows),
        ];
    }

    public function rendering()
    {
        if ((int)request()->customerId) {
            $this->listAction(request()->customerId);
        }

        if (request()->create) {
            $this->listAction();
        }
    }
};
?>

<x-noerd::page :disableModal="$disableModal">
    <x-noerd::list/>
</x-noerd::page>
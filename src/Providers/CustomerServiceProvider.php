<?php

namespace Noerd\Customer\Providers;

use Noerd\Customer\Commands\CustomerInstallCommand;
use Noerd\Customer\Commands\CustomerUpdateCommand;
use Noerd\Customer\Models\Customer;
use Noerd\Customer\Models\CustomerAddress;
use Noerd\Providers\NoerdModuleServiceProvider;
use Noerd\Support\RelationFieldDefinition;

class CustomerServiceProvider extends NoerdModuleServiceProvider
{
    protected string $moduleKey = 'customer';

    protected array $commands = [
        CustomerInstallCommand::class,
        CustomerUpdateCommand::class,
    ];

    protected function relationFields(): array
    {
        $addressTitle = fn(CustomerAddress $address): string => $address->label
            ?: mb_trim(($address->address_line_1 ?? '') . ', ' . ($address->locality ?? ''), ', ');

        return [
            'customerRelation' => RelationFieldDefinition::model(
                listComponent: 'customer::customers-list',
                detailComponent: 'customer::customer-detail',
                modelClass: Customer::class,
                titleResolver: 'name',
            ),
            // Picker for one of the hosting customer's own addresses (the list
            // scopes itself by the picker-supplied host id) — used by the
            // default-invoice/-delivery address fields.
            'customerAddressRelation' => RelationFieldDefinition::model(
                listComponent: 'customer::customer-addresses-list',
                detailComponent: 'customer::customer-address-detail',
                detailRoute: 'customer.address.detail',
                modelClass: CustomerAddress::class,
                titleResolver: $addressTitle,
            ),
            // Same picker relation, rendered as a clickable address card instead of
            // the generic relation input (custom fieldComponent).
            'customerAddressCardRelation' => RelationFieldDefinition::model(
                listComponent: 'customer::customer-addresses-list',
                detailComponent: 'customer::customer-address-detail',
                detailRoute: 'customer.address.detail',
                modelClass: CustomerAddress::class,
                titleResolver: $addressTitle,
                fieldComponent: 'customer::customer-address-card-field',
            ),
        ];
    }
}

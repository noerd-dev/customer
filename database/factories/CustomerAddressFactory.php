<?php

namespace Noerd\Customer\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Noerd\Customer\Models\Customer;
use Noerd\Customer\Models\CustomerAddress;
use Noerd\Models\Tenant;

class CustomerAddressFactory extends Factory
{
    protected $model = CustomerAddress::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'customer_id' => fn (array $attributes) => Customer::factory()->create([
                'tenant_id' => $attributes['tenant_id'],
            ])->id,
            'label' => null,
            'country_code' => 'DE',
            'address_line_1' => $this->faker->streetAddress(),
            'postal_code' => $this->faker->postcode(),
            'locality' => $this->faker->city(),
        ];
    }
}

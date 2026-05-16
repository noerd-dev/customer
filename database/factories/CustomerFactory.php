<?php

namespace Noerd\Customer\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Noerd\Customer\Models\Customer;
use Noerd\Models\Tenant;

class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail(),
            'tenant_id' => Tenant::factory(),
        ];
    }
}

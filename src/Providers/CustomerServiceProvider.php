<?php

namespace Noerd\Customer\Providers;

use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Noerd\Customer\Commands\CustomerInstallCommand;
use Noerd\Customer\Commands\CustomerUpdateCommand;
use Noerd\Customer\Models\Customer;
use Noerd\Services\RelationFieldRegistry;
use Noerd\Support\RelationFieldDefinition;

class CustomerServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'customer');
        Livewire::addNamespace('customer', viewPath: __DIR__ . '/../../resources/views/components');
        Livewire::addLocation(viewPath: __DIR__ . '/../../resources/views/components');
        $this->loadTranslationsFrom(__DIR__ . '/../../resources/lang', 'customer');
        $this->loadJsonTranslationsFrom(__DIR__ . '/../../resources/lang');
        $this->loadRoutesFrom(__DIR__ . '/../../routes/customer-routes.php');

        if ($this->app->runningInConsole()) {
            $this->commands([
                CustomerInstallCommand::class,
                CustomerUpdateCommand::class,
            ]);
        }

        $relationFieldRegistry = $this->app->make(RelationFieldRegistry::class);
        $relationFieldRegistry->register('customerRelation', RelationFieldDefinition::model(
            listComponent: 'customer::customers-list',
            detailComponent: 'customer::customer-detail',
            modelClass: Customer::class,
            titleResolver: 'name',
        ));
    }
}

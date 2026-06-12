<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Noerd\Customer\Models\Customer;
use Noerd\Customer\Tests\Traits\CreatesCustomerUser;
use OwenIt\Auditing\AuditableObserver;

uses(Tests\TestCase::class);
uses(CreatesCustomerUser::class);
uses(RefreshDatabase::class);

beforeEach(function (): void {
    config(['audit.console' => true]);
    Customer::observe(new AuditableObserver());
});

$testSettings = [
    'componentName' => 'customer::customer-detail',
    'listName' => 'customer::customers-list',
    'id' => 'modelId',
    'urlParam' => 'customerId',
];

it('test the route', function (): void {
    $user = $this->withCustomerModule();

    $this->actingAs($user);

    $response = $this->get('/customers');
    $response->assertStatus(200);
});

it('validates the data', function () use ($testSettings): void {
    $user = $this->withCustomerModule();

    $this->actingAs($user);

    $component = Livewire::test($testSettings['componentName'])
        ->set('detailData', [])
        ->call('store');

    $component->assertHasErrors(requiredLayoutFields($component));
});

it('successfully stores the data', function () use ($testSettings): void {
    $user = $this->withCustomerModule();

    $this->actingAs($user);
    $customerName = fake()->word;

    Livewire::test($testSettings['componentName'])
        ->set('detailData', validDetailPayload(Customer::class, ['tenant_id' => $user->selected_tenant_id]))
        ->set('detailData.name', $customerName)
        ->call('store')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('customers', [
        'name' => $customerName,
        'tenant_id' => $user->selected_tenant_id,
    ]);
});

it('it sets and removes the model id in url', function () use ($testSettings): void {
    $user = $this->withCustomerModule();

    $this->actingAs($user);
    $model = Customer::factory()->create(['tenant_id' => $user->selected_tenant_id]);

    Livewire::test($testSettings['listName'])->call('listAction', $model->id)
        ->assertDispatched('noerdModal', modalComponent: $testSettings['componentName']);

    Livewire::withUrlParams(['customerId' => $model->id])
        ->test($testSettings['componentName'])
        ->assertHasNoErrors();
});

it('it opens model when url parameter is set', function () use ($testSettings): void {
    $user = $this->withCustomerModule();

    $this->actingAs($user);
    $model = Customer::factory()->create(['tenant_id' => $user->selected_tenant_id]);

    Livewire::withUrlParams([$testSettings['urlParam'] => $model->id])
        ->test($testSettings['listName'])
        ->assertDispatched('noerdModal');
});

it('it removes url parameter, when modal is closed', function () use ($testSettings): void {
    $user = $this->withCustomerModule();

    $this->actingAs($user);
    $model = Customer::factory()->create(['tenant_id' => $user->selected_tenant_id]);

    Livewire::withUrlParams(['customerId' => $model->id])
        ->test($testSettings['componentName'])
        ->assertSet('modelId', $model->id)
        ->call('closeModalProcess', source: $testSettings['listName'])
        ->assertDispatched('closeTopModal')
        ->assertHasNoErrors();
});

it('it opens and store model', function () use ($testSettings): void {
    $user = $this->withCustomerModule();

    $this->actingAs($user);
    $model = Customer::factory()->create(['tenant_id' => $user->selected_tenant_id]);

    Livewire::withUrlParams(['customerId' => $model->id])
        ->test($testSettings['componentName'])
        ->assertSet('modelId', $model->id)
        ->call('store')
        ->assertHasNoErrors();
});

it('sets a table key for the list', function () use ($testSettings): void {
    $user = $this->withCustomerModule();

    $this->actingAs($user);

    Livewire::test($testSettings['listName'])
        ->assertNotSet('listId', '');
});

it('gracefully handles non-existent model id', function () use ($testSettings): void {
    $user = $this->withCustomerModule();

    $this->actingAs($user);

    Livewire::withUrlParams([$testSettings['urlParam'] => 999999])
        ->test($testSettings['componentName'])
        ->assertSet($testSettings['id'], null)
        ->assertDispatched('closeTopModal')
        ->assertHasNoErrors();
});

it('gracefully handles deleted model', function () use ($testSettings): void {
    $user = $this->withCustomerModule();

    $this->actingAs($user);
    $model = Customer::factory()->create(['tenant_id' => $user->selected_tenant_id]);
    $modelId = $model->id;
    $model->delete();

    Livewire::withUrlParams([$testSettings['urlParam'] => $modelId])
        ->test($testSettings['componentName'])
        ->assertSet($testSettings['id'], null)
        ->assertDispatched('closeTopModal')
        ->assertHasNoErrors();
});

it('creates audit entries when customer is updated', function () use ($testSettings): void {
    $user = $this->withCustomerModule();

    $this->actingAs($user);
    $model = Customer::factory()->create([
        'tenant_id' => $user->selected_tenant_id,
        'name' => 'Original Name',
    ]);

    Livewire::withUrlParams(['customerId' => $model->id])
        ->test($testSettings['componentName'])
        ->assertSet('modelId', $model->id)
        ->set('detailData.name', 'Updated Name')
        ->call('store')
        ->assertHasNoErrors();

    $model->refresh();
    $updateAudit = $model->audits->where('event', 'updated')->last();
    expect($updateAudit)->not->toBeNull();
    expect($updateAudit->new_values)->toHaveKey('name', 'Updated Name');
});

it('loads audits for existing customer', function () use ($testSettings): void {
    $user = $this->withCustomerModule();

    $this->actingAs($user);
    $model = Customer::factory()->create(['tenant_id' => $user->selected_tenant_id]);

    $model->update(['name' => 'Changed']);

    Livewire::withUrlParams(['customerId' => $model->id])
        ->test($testSettings['componentName'])
        ->assertSet('modelId', $model->id)
        ->assertSet('detailData.audits', fn($audits) => count($audits) > 0);
});

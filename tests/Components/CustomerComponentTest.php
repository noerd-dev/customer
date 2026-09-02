<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Noerd\Customer\Models\Customer;
use Noerd\Customer\Tests\Traits\CreatesCustomerUser;
use Noerd\Helpers\TenantHelper;
use OwenIt\Auditing\AuditableObserver;
use Tests\TestCase;

uses(TestCase::class);
uses(CreatesCustomerUser::class);
uses(RefreshDatabase::class);

beforeEach(function (): void {
    config(['audit.console' => true]);
    Customer::observe(new AuditableObserver());

    $this->user = $this->withCustomerModule();
    $this->actingAs($this->user);
    $this->tenantId = $this->user->selected_tenant_id;
});

describe('detail', function (): void {
    it('validates the data', function (): void {
        $component = Livewire::test('customer::customer-detail')
            ->set('detailData', [])
            ->call('store');

        $component->assertHasErrors(requiredLayoutFields($component));
    });

    it('successfully stores the data', function (): void {
        $customerName = fake()->word;

        Livewire::test('customer::customer-detail')
            ->set('detailData', validDetailPayload(Customer::class, ['tenant_id' => $this->tenantId]))
            ->set('detailData.name', $customerName)
            ->call('store')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('customers', [
            'name' => $customerName,
            'tenant_id' => $this->tenantId,
        ]);
    });

    it('it sets and removes the model id in url', function (): void {
        // Which route the list opens is per-installation configuration, so the
        // list is pointed at a synthetic zz route.
        registerTestLivewireRoute('zz-customer/{modelId}', 'customer::customer-detail', 'zz.customer.detail');

        $model = Customer::factory()->create(['tenant_id' => $this->tenantId]);

        Livewire::test('customer::customers-list', ['detailRoute' => 'zz.customer.detail'])
            ->call('listAction', $model->id)
            ->assertDispatched(
                'noerdModal',
                fn(string $event, array $params): bool => ($params['route'] ?? null) === 'zz.customer.detail'
                    && ($params['arguments']['modelId'] ?? null) === $model->id,
            );

        Livewire::withUrlParams(['customerId' => $model->id])
            ->test('customer::customer-detail')
            ->assertHasNoErrors();
    });

    it('it opens model when url parameter is set', function (): void {
        $model = Customer::factory()->create(['tenant_id' => $this->tenantId]);

        Livewire::withUrlParams(['customerId' => $model->id])
            ->test('customer::customers-list')
            ->assertDispatched('noerdModal');
    });

    it('it removes url parameter, when modal is closed', function (): void {
        $model = Customer::factory()->create(['tenant_id' => $this->tenantId]);

        Livewire::withUrlParams(['customerId' => $model->id])
            ->test('customer::customer-detail')
            ->assertSet('modelId', $model->id)
            ->call('closeModalProcess', source: 'customer::customers-list')
            ->assertDispatched('closeTopModal')
            ->assertHasNoErrors();
    });

    it('it opens and store model', function (): void {
        $model = Customer::factory()->create(['tenant_id' => $this->tenantId]);

        Livewire::withUrlParams(['customerId' => $model->id])
            ->test('customer::customer-detail')
            ->assertSet('modelId', $model->id)
            ->call('store')
            ->assertHasNoErrors();
    });

    it('gracefully handles non-existent model id', function (): void {
        Livewire::withUrlParams(['customerId' => 999999])
            ->test('customer::customer-detail')
            ->assertSet('modelId', null)
            ->assertDispatched('closeTopModal')
            ->assertHasNoErrors();
    });

    it('gracefully handles deleted model', function (): void {
        $model = Customer::factory()->create(['tenant_id' => $this->tenantId]);
        $modelId = $model->id;
        $model->delete();

        Livewire::withUrlParams(['customerId' => $modelId])
            ->test('customer::customer-detail')
            ->assertSet('modelId', null)
            ->assertDispatched('closeTopModal')
            ->assertHasNoErrors();
    });

    it('creates audit entries when customer is updated', function (): void {
        $model = Customer::factory()->create([
            'tenant_id' => $this->tenantId,
            'name' => 'Original Name',
        ]);

        Livewire::withUrlParams(['customerId' => $model->id])
            ->test('customer::customer-detail')
            ->assertSet('modelId', $model->id)
            ->set('detailData.name', 'Updated Name')
            ->call('store')
            ->assertHasNoErrors();

        $model->refresh();
        $updateAudit = $model->audits->where('event', 'updated')->last();
        expect($updateAudit)->not->toBeNull();
        expect($updateAudit->new_values)->toHaveKey('name', 'Updated Name');
    });

    it('loads audits for existing customer', function (): void {
        $model = Customer::factory()->create(['tenant_id' => $this->tenantId]);

        $model->update(['name' => 'Changed']);

        Livewire::withUrlParams(['customerId' => $model->id])
            ->test('customer::customer-detail')
            ->assertSet('modelId', $model->id)
            ->assertSet('detailData.audits', fn($audits) => count($audits) > 0);
    });
});

/*
 | Generic list mechanics (pagination, search, sorting, row actions) are proven
 | in the noerd core suite — asserted here is only what the customer module
 | owns: its list resolves rows through the tenant scope.
 */
describe('list', function (): void {
    it('lists only the customers of the current tenant', function (): void {
        Customer::factory()->count(3)->create(['tenant_id' => $this->tenantId]);

        expect(Livewire::test('customer::customers-list')->instance()->listData()['rows'])->toHaveCount(3);

        $user2 = $this->withCustomerModule();
        TenantHelper::clearCache();
        $tenant2 = $user2->tenants->first()->id;
        Customer::factory()->count(2)->create(['tenant_id' => $tenant2]);

        $this->actingAs($user2);

        expect(Livewire::test('customer::customers-list')->instance()->listData()['rows'])->toHaveCount(2);
    });

    it('handles empty search results correctly', function (): void {
        Customer::factory()->create([
            'tenant_id' => $this->tenantId,
            'name' => 'John Doe',
        ]);

        $component = Livewire::test('customer::customers-list')
            ->set('search', 'NonExistentCustomer');

        expect($component->instance()->listData()['rows'])->toHaveCount(0);
    });
});

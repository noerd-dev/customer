<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Noerd\Models\TenantApp;

uses(Tests\TestCase::class, RefreshDatabase::class);

/*
 | The installer writes into base_path() (app-configs, database/migrations) and
 | registers a tenant_apps row, so the real command runs against a throwaway
 | base path on a refreshed database.
 */
beforeEach(function (): void {
    $this->originalBasePath = $this->app->basePath();
    $this->hostPath = storage_path('framework/testing/zz-customer-install-' . getmypid());

    File::deleteDirectory($this->hostPath);
    File::ensureDirectoryExists($this->hostPath . '/config');
    File::ensureDirectoryExists($this->hostPath . '/database/migrations');
    // vendor:publish resolves its auditing target from the REAL base path (the
    // provider booted before this override), so the migration must already look
    // published or the run would write into the host project.
    File::put($this->hostPath . '/database/migrations/2020_01_01_000000_create_audits_table.php', "<?php\n");
    // The module installers refuse to run until noerd itself is installed.
    File::put($this->hostPath . '/config/noerd.php', "<?php\n\nreturn [];\n");

    $this->app->setBasePath($this->hostPath);
});

afterEach(function (): void {
    $this->app->setBasePath($this->originalBasePath);
    File::deleteDirectory($this->hostPath);
});

function runZzCustomerInstall(object $test): Illuminate\Testing\PendingCommand
{
    return $test->artisan('noerd:install-customer', ['--force' => true])
        ->expectsConfirmation('Should Customer be installed as a hidden app (not shown in main navigation)?', 'no')
        ->expectsQuestion('App title', 'Customer')
        ->expectsConfirmation('Would you like to assign the app to tenants now?', 'no')
        ->expectsConfirmation('Would you like to run php artisan migrate now?', 'no')
        ->expectsConfirmation('Would you like to run "npm run build" to compile frontend assets?', 'no');
}

it('registers the tenant app and publishes the customer app-configs', function (): void {
    runZzCustomerInstall($this)->assertExitCode(0);

    $app = TenantApp::where('name', 'CUSTOMER')->first();
    expect($app)->not->toBeNull()
        ->and($app->route)->toBe('customers')
        ->and($app->is_active)->toBeTrue();

    expect(File::exists($this->hostPath . '/app-configs/customer/navigation.yml'))->toBeTrue()
        ->and(File::files($this->hostPath . '/app-configs/customer/lists'))->not->toBeEmpty();
});

it('stays idempotent when the install is run a second time', function (): void {
    runZzCustomerInstall($this)->assertExitCode(0);

    $this->artisan('noerd:install-customer', ['--force' => true])
        ->expectsOutputToContain('is already installed. Running update instead...')
        ->expectsConfirmation('Would you like to assign the app to tenants now?', 'no')
        ->assertExitCode(0);

    expect(TenantApp::where('name', 'CUSTOMER')->count())->toBe(1);
    expect(File::exists($this->hostPath . '/app-configs/customer/navigation.yml'))->toBeTrue();
});

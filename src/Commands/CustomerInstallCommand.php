<?php

namespace Noerd\Customer\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Noerd\Traits\HasModuleInstallation;
use Noerd\Traits\RequiresNoerdInstallation;

class CustomerInstallCommand extends Command
{
    use HasModuleInstallation;
    use RequiresNoerdInstallation;

    protected $signature = 'noerd:install-customer {--force : Overwrite existing files without asking}';

    protected $description = 'Install customer module content and navigation';

    public function handle(): int
    {
        $result = $this->runModuleInstallation();

        if ($result === 0) {
            $this->publishAuditingMigrationIfNeeded();
        }

        return $result;
    }

    protected function getModuleName(): string
    {
        return 'Customer';
    }

    protected function getModuleKey(): string
    {
        return 'customer';
    }

    protected function getDefaultAppTitle(): string
    {
        return 'Customer';
    }

    protected function getAppIcon(): string
    {
        return 'customer::icons.app';
    }

    protected function getAppRoute(): string
    {
        return 'customers';
    }

    protected function getSnippetTitle(): string
    {
        return 'Customer';
    }

    protected function getSourceDir(): string
    {
        return dirname(__DIR__, 2) . '/app-configs/customer';
    }

    private function publishAuditingMigrationIfNeeded(): void
    {
        $migrationsPath = database_path('migrations');
        $existingMigrations = glob($migrationsPath . '/*_create_audits_table.php');

        if (! empty($existingMigrations)) {
            $this->line('<comment>Auditing migration already published.</comment>');

            return;
        }

        $this->line('');
        $this->info('Publishing auditing migration...');

        try {
            $exitCode = Artisan::call('vendor:publish', [
                '--provider' => 'OwenIt\Auditing\AuditingServiceProvider',
                '--tag' => 'migrations',
            ], $this->output);

            if ($exitCode === 0) {
                $this->line('<info>Auditing migration published successfully.</info>');
            }
        } catch (Exception $e) {
            $this->warn('Failed to publish auditing migration: ' . $e->getMessage());
        }
    }
}

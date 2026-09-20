<?php

declare(strict_types=1);

namespace Noerd\Customer\Commands;

use Illuminate\Console\Command;
use Noerd\Traits\HasModuleInstallation;
use Noerd\Traits\PublishesAuditMigration;

class CustomerInstallCommand extends Command
{
    use HasModuleInstallation;
    use PublishesAuditMigration;

    protected $signature = 'noerd:install-customer {--force : Overwrite existing files without asking}';

    protected $description = 'Install customer module content and navigation';

    public function handle(): int
    {
        return $this->runModuleInstallation();
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

    protected function getSourceDir(): string
    {
        return dirname(__DIR__, 2) . '/app-configs/customer';
    }

    /**
     * The model is auditable — the auditing migration is published before the
     * migration prompt, on install and update.
     */
    protected function publishModuleExtras(bool $update): void
    {
        $this->publishAuditingMigrationIfNeeded();
    }
}

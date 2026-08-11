<?php

namespace Noerd\Customer\Commands;

use Illuminate\Console\Command;
use Noerd\Traits\HasModuleInstallation;
use Noerd\Traits\PublishesAuditMigration;
use Noerd\Traits\RequiresNoerdInstallation;

class CustomerInstallCommand extends Command
{
    use HasModuleInstallation;
    use PublishesAuditMigration;
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
}

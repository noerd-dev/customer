<?php

namespace Noerd\Customer\Commands;

class CustomerUpdateCommand extends CustomerInstallCommand
{
    protected $signature = 'noerd:update-customer {--force : Overwrite existing files without asking}';

    protected $description = 'Update Customer YML configuration files';

    public function handle(): int
    {
        return $this->runModuleUpdate();
    }
}

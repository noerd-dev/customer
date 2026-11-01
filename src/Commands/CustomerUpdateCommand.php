<?php

declare(strict_types=1);

namespace Noerd\Customer\Commands;

class CustomerUpdateCommand extends CustomerInstallCommand
{
    protected $signature = 'noerd:update-customer';

    protected $description = 'Update Customer YML configuration files';
}

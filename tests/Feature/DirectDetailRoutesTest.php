<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

uses(Tests\TestCase::class);

it('has direct route for customer-detail', function (): void {
    expect(Route::has('customer.detail'))->toBeTrue();
});

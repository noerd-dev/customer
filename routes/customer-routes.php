<?php

use Illuminate\Support\Facades\Route;

Route::group(['middleware' => ['noerd']], function (): void {
    Route::livewire('customers', 'customer::customers-list')->name('customers');
    Route::livewire('customer/{modelId}', 'customer::customer-detail')->name('customer.detail');
});

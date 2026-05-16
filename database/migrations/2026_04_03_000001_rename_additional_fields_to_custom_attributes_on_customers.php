<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        if (Schema::hasColumn('customers', 'additional_fields') && ! Schema::hasColumn('customers', 'custom_attributes')) {
            Schema::table('customers', function (Blueprint $table): void {
                $table->renameColumn('additional_fields', 'custom_attributes');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('customers', 'custom_attributes') && ! Schema::hasColumn('customers', 'additional_fields')) {
            Schema::table('customers', function (Blueprint $table): void {
                $table->renameColumn('custom_attributes', 'additional_fields');
            });
        }
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 16 characters were too tight for real-world postal codes; the column becomes
 * a regular varchar(255). Fresh installations already create it that way.
 */
return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('customer_addresses')) {
            return;
        }

        Schema::table('customer_addresses', function (Blueprint $table): void {
            $table->string('postal_code')->nullable()->change();
        });
    }

    /**
     * Not reversible: shrinking the column back could truncate stored values.
     */
    public function down(): void {}
};

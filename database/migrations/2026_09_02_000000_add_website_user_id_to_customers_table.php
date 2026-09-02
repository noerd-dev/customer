<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Links a customer record to a user of the consumer app's website guard.
 *
 * Deliberately no foreign key: the website user table is owned by the consumer
 * application, not by this module, and may not exist at migration time.
 */
return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('customers') || Schema::hasColumn('customers', 'website_user_id')) {
            return;
        }

        Schema::table('customers', function (Blueprint $table): void {
            $table->unsignedBigInteger('website_user_id')->nullable()->after('uuid');
            $table->index(['tenant_id', 'website_user_id'], 'customers_tenant_website_user_index');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('customers') || ! Schema::hasColumn('customers', 'website_user_id')) {
            return;
        }

        Schema::table('customers', function (Blueprint $table): void {
            $table->dropIndex('customers_tenant_website_user_index');
            $table->dropColumn('website_user_id');
        });
    }
};

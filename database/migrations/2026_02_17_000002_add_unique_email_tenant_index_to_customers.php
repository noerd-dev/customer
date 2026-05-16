<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            $table->string('email', 255)->nullable()->change();
        });

        // NULL out email for all soft-deleted records to avoid unique constraint violations
        DB::update("UPDATE customers SET email = NULL WHERE deleted_at IS NOT NULL AND email IS NOT NULL");

        Schema::table('customers', function (Blueprint $table): void {
            $table->unique(['tenant_id', 'email'], 'customers_tenant_email_unique');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            $table->dropUnique('customers_tenant_email_unique');
        });

        Schema::table('customers', function (Blueprint $table): void {
            $table->text('email')->nullable()->change();
        });
    }
};

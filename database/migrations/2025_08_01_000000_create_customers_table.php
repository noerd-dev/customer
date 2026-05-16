<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('customers')) {
            return;
        }

        Schema::create('customers', function (Blueprint $table): void {
            $table->id();
            $table->char('uuid', 36)->nullable()->index();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('company_name')->nullable();
            $table->string('name')->nullable();
            $table->string('address')->nullable();
            $table->string('floor')->nullable();
            $table->string('zipcode')->nullable();
            $table->string('city')->nullable();
            $table->string('email', 255)->nullable();
            $table->string('internal_comment')->nullable();
            $table->string('phone')->nullable();
            $table->timestamps();
            $table->string('company')->nullable();
            $table->softDeletes();
            $table->unsignedBigInteger('invoice_template')->nullable();
            $table->json('custom_attributes')->nullable();

            $table->unique(['tenant_id', 'email'], 'customers_tenant_email_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};

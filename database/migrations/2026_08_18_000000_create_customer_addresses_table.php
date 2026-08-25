<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('customer_addresses')) {
            Schema::create('customer_addresses', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
                $table->string('label')->nullable();

                $table->char('country_code', 2)->nullable();

                $table->string('administrative_area_code', 6)->nullable();
                $table->string('administrative_area', 100)->nullable();

                $table->string('locality', 100)->nullable();
                $table->string('postal_code')->nullable();
                $table->string('sorting_code', 16)->nullable();

                $table->string('address_line_1', 200);
                $table->string('address_line_2', 200)->nullable();

                // Derived from address_line_1 where parseable — never authoritative
                $table->string('street_name', 200)->nullable();
                $table->string('house_number', 16)->nullable();

                $table->decimal('latitude', 10, 7)->nullable();
                $table->decimal('longitude', 10, 7)->nullable();

                $table->timestamp('verified_at')->nullable();
                $table->string('verification_provider', 50)->nullable();

                $table->char('fingerprint', 64);

                $table->timestamps();

                $table->index('fingerprint');
                $table->index(['country_code', 'postal_code']);
            });
        }

        if (! Schema::hasColumn('customers', 'default_invoice_address_id')) {
            Schema::table('customers', function (Blueprint $table): void {
                $table->foreignId('default_invoice_address_id')->nullable()
                    ->constrained('customer_addresses')->nullOnDelete();
                $table->foreignId('default_delivery_address_id')->nullable()
                    ->constrained('customer_addresses')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            if (Schema::hasColumn('customers', 'default_invoice_address_id')) {
                $table->dropConstrainedForeignId('default_invoice_address_id');
            }
            if (Schema::hasColumn('customers', 'default_delivery_address_id')) {
                $table->dropConstrainedForeignId('default_delivery_address_id');
            }
        });

        Schema::dropIfExists('customer_addresses');
    }
};

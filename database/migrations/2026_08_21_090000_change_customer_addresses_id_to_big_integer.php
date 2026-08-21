<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Noerd\Customer\Models\CustomerAddress;

/**
 * Converts the ULID primary key of customer_addresses (and the two referencing
 * columns on customers) into an auto-incrementing unsigned big integer, in line
 * with every other table in the framework. Existing rows keep their data; the
 * ids are renumbered in ULID order, so the references are remapped along the
 * way — including the polymorphic audit trail.
 *
 * Fresh installations already create the table with a big integer id, so this
 * migration is a no-op there.
 */
return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('customer_addresses') || ! $this->hasStringId()) {
            return;
        }

        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            throw new RuntimeException(
                'customer_addresses still has a ULID primary key; the conversion is implemented for MySQL/MariaDB only.',
            );
        }

        $this->numberRows();
        $this->remapCustomerDefaults();
        $this->remapAudits();
        $this->promoteNewIdToPrimaryKey();
        $this->restoreCustomerForeignKeys();
    }

    /**
     * Not reversible: the ULIDs are gone once the column is dropped, so a
     * downgrade would have to invent new ones and break every reference.
     */
    public function down(): void {}

    private function hasStringId(): bool
    {
        return in_array(Schema::getColumnType('customer_addresses', 'id'), ['char', 'varchar', 'string'], true);
    }

    /**
     * Assigns 1..n in ULID order, so the numeric ids follow creation order.
     */
    private function numberRows(): void
    {
        DB::statement('ALTER TABLE customer_addresses ADD COLUMN id_bigint BIGINT UNSIGNED NULL');
        DB::statement('SET @noerd_customer_address_seq = 0');
        DB::statement('UPDATE customer_addresses SET id_bigint = (@noerd_customer_address_seq := @noerd_customer_address_seq + 1) ORDER BY id');
    }

    private function remapCustomerDefaults(): void
    {
        if (! Schema::hasTable('customers') || ! Schema::hasColumn('customers', 'default_invoice_address_id')) {
            return;
        }

        foreach ($this->customerForeignKeyNames() as $constraint) {
            DB::statement("ALTER TABLE customers DROP FOREIGN KEY `{$constraint}`");
        }

        DB::statement('ALTER TABLE customers
            ADD COLUMN default_invoice_address_id_bigint BIGINT UNSIGNED NULL,
            ADD COLUMN default_delivery_address_id_bigint BIGINT UNSIGNED NULL');

        foreach (['invoice', 'delivery'] as $kind) {
            DB::statement("UPDATE customers c
                JOIN customer_addresses a ON a.id = c.default_{$kind}_address_id
                SET c.default_{$kind}_address_id_bigint = a.id_bigint");
        }

        DB::statement('ALTER TABLE customers
            DROP COLUMN default_invoice_address_id,
            DROP COLUMN default_delivery_address_id');

        DB::statement('ALTER TABLE customers
            CHANGE default_invoice_address_id_bigint default_invoice_address_id BIGINT UNSIGNED NULL,
            CHANGE default_delivery_address_id_bigint default_delivery_address_id BIGINT UNSIGNED NULL');
    }

    /**
     * The audit trail stores the model key as a string, so the historic ULIDs
     * would point nowhere after the conversion.
     */
    private function remapAudits(): void
    {
        if (! Schema::hasTable('audits')) {
            return;
        }

        DB::update('UPDATE audits au
            JOIN customer_addresses a ON a.id = au.auditable_id
            SET au.auditable_id = a.id_bigint
            WHERE au.auditable_type = ?', [CustomerAddress::class]);
    }

    private function promoteNewIdToPrimaryKey(): void
    {
        DB::statement('ALTER TABLE customer_addresses
            DROP PRIMARY KEY,
            DROP COLUMN id,
            CHANGE id_bigint id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            ADD PRIMARY KEY (id)');
    }

    private function restoreCustomerForeignKeys(): void
    {
        if (! Schema::hasTable('customers') || ! Schema::hasColumn('customers', 'default_invoice_address_id')) {
            return;
        }

        Schema::table('customers', function (Blueprint $table): void {
            $table->foreign('default_invoice_address_id')->references('id')->on('customer_addresses')->nullOnDelete();
            $table->foreign('default_delivery_address_id')->references('id')->on('customer_addresses')->nullOnDelete();
        });
    }

    /**
     * @return array<int, string>
     */
    private function customerForeignKeyNames(): array
    {
        $rows = DB::select(
            'SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND REFERENCED_TABLE_NAME = ?',
            ['customers', 'customer_addresses'],
        );

        return array_map(fn(object $row): string => $row->CONSTRAINT_NAME, $rows);
    }
};

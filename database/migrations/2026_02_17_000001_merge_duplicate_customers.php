<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class () extends Migration {
    public function up(): void
    {
        $groups = DB::select("
            SELECT tenant_id, email, MIN(id) AS keep_id
            FROM customers
            WHERE email IS NOT NULL
              AND email != ''
              AND deleted_at IS NULL
            GROUP BY tenant_id, email
            HAVING COUNT(*) > 1
        ");

        foreach ($groups as $group) {
            $duplicateIds = DB::table('customers')
                ->where('tenant_id', $group->tenant_id)
                ->where('email', $group->email)
                ->where('id', '!=', $group->keep_id)
                ->whereNull('deleted_at')
                ->pluck('id')
                ->all();

            if (empty($duplicateIds)) {
                continue;
            }

            $placeholders = implode(',', array_fill(0, count($duplicateIds), '?'));
            $params = array_merge([$group->keep_id], $duplicateIds);

            DB::update("UPDATE orders SET customer_id = ? WHERE customer_id IN ({$placeholders})", $params);
            DB::update("UPDATE invoices SET customer_id = ? WHERE customer_id IN ({$placeholders})", $params);
            DB::update("UPDATE projects SET customer_id = ? WHERE customer_id IN ({$placeholders})", $params);
            DB::update("UPDATE bookings SET customer_id = ? WHERE customer_id IN ({$placeholders})", $params);
            DB::update("UPDATE guest_accounts SET customer_id = ? WHERE customer_id IN ({$placeholders})", $params);
            DB::update("UPDATE order_confirmations SET customer_id = ? WHERE customer_id IN ({$placeholders})", $params);
            DB::update("UPDATE quotes SET customer_id = ? WHERE customer_id IN ({$placeholders})", $params);

            DB::update(
                "UPDATE customers SET deleted_at = NOW(), email = NULL WHERE id IN ({$placeholders})",
                $duplicateIds,
            );
        }
    }

    public function down(): void
    {
        // Cannot fully restore NULLed emails — only restore deleted_at
    }
};

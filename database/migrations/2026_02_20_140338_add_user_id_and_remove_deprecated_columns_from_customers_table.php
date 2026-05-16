<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            $table->dropColumn(['is_account', 'password', 'staff_number', 'blocked']);
        });
    }

    public function down(): void {}
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        if (! Schema::hasColumn('customers', 'additional_fields')) {
            Schema::table('customers', function (Blueprint $table): void {
                $table->json('additional_fields')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            $table->dropColumn('additional_fields');
        });
    }
};

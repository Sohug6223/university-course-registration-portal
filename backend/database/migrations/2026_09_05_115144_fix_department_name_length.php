<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // SQLite does not support MySQL MODIFY syntax
        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }

        DB::statement(
            "ALTER TABLE departments
             MODIFY name VARCHAR(255) NOT NULL"
        );
    }

    public function down(): void
    {
        // SQLite does not support MySQL MODIFY syntax
        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }

        DB::statement(
            "ALTER TABLE departments
             MODIFY name VARCHAR(100) NOT NULL"
        );
    }
};
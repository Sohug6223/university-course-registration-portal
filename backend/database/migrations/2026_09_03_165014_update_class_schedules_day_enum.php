<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("
            ALTER TABLE class_schedules
            MODIFY day ENUM(
                'Saturday',
                'Sunday',
                'Monday',
                'Tuesday',
                'Wednesday',
                'Thursday',
                'Friday'
            ) NOT NULL
        ");
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("
            ALTER TABLE class_schedules
            MODIFY day ENUM(
                'Saturday',
                'Sunday',
                'Monday',
                'Tuesday'
            ) NOT NULL
        ");
    }
};
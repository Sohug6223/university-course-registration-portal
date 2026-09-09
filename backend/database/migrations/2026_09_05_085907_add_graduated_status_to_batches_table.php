<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('batches', function (Blueprint $table) {
            $table->enum('status', [
                'Active',
                'Inactive',
                'Graduated',
            ])->default('Active')->change();
        });
    }

    public function down(): void
    {
        Schema::table('batches', function (Blueprint $table) {
            $table->enum('status', [
                'Active',
                'Inactive',
            ])->default('Active')->change();
        });
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('batches', function (Blueprint $table) {
            $table->id();

            $table->string('department');
            $table->unsignedInteger('batch_number');
            $table->string('start_year', 4)->nullable();

            $table->enum('current_semester', [
                '1st',
                '2nd',
                '3rd',
                '4th',
                '5th',
                '6th',
                '7th',
                '8th',
            ]);

            $table->enum('status', [
                'Active',
                'Inactive',
            ])->default('Active');

            $table->timestamps();

            $table->unique(
                ['department', 'batch_number'],
                'batches_department_batch_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('batches');
    }
};
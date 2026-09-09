<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /*
      Run the migrations.
     */
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->id();

            $table->string('course_code')->unique();
            $table->string('course_name');

            $table->enum('department', [
                'CSE',
                'EEE',
                'BBA',
                'English',
            ]);

            $table->enum('semester', [
                '1st',
                '2nd',
                '3rd',
                '4th',
                '5th',
                '6th',
                '7th',
                '8th',
            ]);

            $table->decimal('credit', 4, 2);

            $table->enum('course_type', [
                'Theory',
                'Lab',
            ])->default('Theory');

            $table->text('description')->nullable();

            $table->enum('status', [
                'Active',
                'Inactive',
            ])->default('Active');

            $table->timestamps();
        });
    }

    /*
      Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};

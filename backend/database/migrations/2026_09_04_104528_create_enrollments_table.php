<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('student_id')
                ->constrained('students')
                ->cascadeOnDelete();

            $table->foreignId('course_id')
                ->constrained('courses')
                ->cascadeOnDelete();

            $table->foreignId('section_id')
                ->constrained('sections')
                ->cascadeOnDelete();

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

            $table->enum('status', [
                'Active',
                'Completed',
                'Dropped',
            ])->default('Active');

            $table->timestamps();

            $table->unique(
                ['student_id', 'course_id', 'semester'],
                'enrollments_student_course_semester_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enrollments');
    }
};
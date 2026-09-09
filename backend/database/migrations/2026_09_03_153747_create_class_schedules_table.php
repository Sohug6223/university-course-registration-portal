<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_schedules', function (Blueprint $table) {
            $table->id();

            $table->foreignId('section_id')
                ->constrained('sections')
                ->cascadeOnDelete();

            $table->foreignId('course_id')
                ->nullable()
                ->constrained('courses')
                ->cascadeOnDelete();

            $table->string('teacher_name')->nullable();

            $table->enum('day', [
                'Saturday',
                'Sunday',
                'Monday',
                'Tuesday',
                'Wednesday',
                'Thursday',
                'Friday',
            ]);

            $table->time('start_time');
            $table->time('end_time');

            $table->string('room', 50);

            $table->timestamps();

            $table->index(['day', 'start_time', 'end_time']);
            $table->index('teacher_name');
            $table->index('room');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_schedules');
    }
};
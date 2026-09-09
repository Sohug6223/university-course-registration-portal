<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sections', function (Blueprint $table) {
            $table->id();

            $table->foreignId('course_id')
                ->constrained('courses')
                ->cascadeOnDelete();

            $table->string('section_name', 20);

            $table->string('teacher_name')->nullable();

            $table->string('room', 50)->nullable();

            $table->unsignedInteger('capacity')->default(40);

            $table->unsignedInteger('enrolled')->default(0);

            $table->enum('status', [
                'Active',
                'Inactive'
            ])->default('Active');

            $table->text('description')->nullable();

            $table->timestamps();

            $table->unique([
                'course_id',
                'section_name'
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sections');
    }
};
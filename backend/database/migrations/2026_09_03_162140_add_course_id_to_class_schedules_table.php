<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('class_schedules', function (Blueprint $table) {
            $table->foreignId('course_id')
                ->after('section_id')
                ->constrained('courses')
                ->cascadeOnDelete();

            $table->index(['section_id', 'course_id']);
        });
    }

    public function down(): void
    {
        Schema::table('class_schedules', function (Blueprint $table) {
            $table->dropForeign(['course_id']);
            $table->dropIndex(['section_id', 'course_id']);
            $table->dropColumn('course_id');
        });
    }
};


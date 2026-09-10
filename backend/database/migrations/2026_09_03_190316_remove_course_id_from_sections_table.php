<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Remove old unique index that uses course_id
        Schema::table('sections', function (Blueprint $table) {
            $table->dropUnique('sections_course_id_section_name_unique');
        });

        // 2. Remove foreign key and course_id column
        Schema::table('sections', function (Blueprint $table) {
            $table->dropForeign(['course_id']);
            $table->dropColumn('course_id');
        });

        // 3. Add the new unique constraint
        Schema::table('sections', function (Blueprint $table) {
            $table->unique(['batch_id', 'section_name']);
        });
    }

    public function down(): void
    {
        Schema::table('sections', function (Blueprint $table) {
            $table->dropUnique(['batch_id', 'section_name']);
        });

        Schema::table('sections', function (Blueprint $table) {
            $table->foreignId('course_id')
                ->nullable()
                ->after('id')
                ->constrained('courses')
                ->cascadeOnDelete();
        });

        Schema::table('sections', function (Blueprint $table) {
            $table->unique(['course_id', 'section_name']);
        });
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sections', function (Blueprint $table) {
            $table->dropForeign(['course_id']);
            $table->dropColumn('course_id');
        });

        Schema::table('sections', function (Blueprint $table) {
            $table->unique(['batch_id', 'section_name']);
        });
    }

    public function down(): void
    {
        Schema::table('sections', function (Blueprint $table) {
            $table->dropUnique(['batch_id', 'section_name']);

            $table->foreignId('course_id')
                ->nullable()
                ->after('id')
                ->constrained('courses')
                ->cascadeOnDelete();
        });
    }
};
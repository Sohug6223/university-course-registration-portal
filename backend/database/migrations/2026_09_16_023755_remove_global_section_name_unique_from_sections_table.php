<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sections', function (Blueprint $table) {
            $table->dropUnique('sections_course_id_section_name_unique');
        });
    }

    public function down(): void
    {
        Schema::table('sections', function (Blueprint $table) {
            $table->unique('section_name', 'sections_course_id_section_name_unique');
        });
    }
};
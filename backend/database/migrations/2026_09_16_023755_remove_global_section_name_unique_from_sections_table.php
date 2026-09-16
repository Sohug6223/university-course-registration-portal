<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sections')) {
            $indexes = DB::select('SHOW INDEX FROM sections');

            $indexExists = collect($indexes)->contains(function ($index) {
                return $index->Key_name === 'sections_course_id_section_name_unique';
            });

            if ($indexExists) {
                Schema::table('sections', function (Blueprint $table) {
                    $table->dropUnique('sections_course_id_section_name_unique');
                });
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('sections')) {
            $indexes = DB::select('SHOW INDEX FROM sections');

            $indexExists = collect($indexes)->contains(function ($index) {
                return $index->Key_name === 'sections_course_id_section_name_unique';
            });

            if (! $indexExists) {
                Schema::table('sections', function (Blueprint $table) {
                    $table->unique(
                        'section_name',
                        'sections_course_id_section_name_unique'
                    );
                });
            }
        }
    }
};
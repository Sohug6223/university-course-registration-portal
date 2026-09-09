<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        /*
          Add department_id.
         */
        Schema::table('courses', function (Blueprint $table) {
            $table->unsignedBigInteger('department_id')
                ->nullable()
                ->after('course_name');
        });

        /*
          Match existing department codes
          with departments.id.
         */
        DB::statement('
            UPDATE courses
            INNER JOIN departments
                ON courses.department = departments.code
            SET courses.department_id = departments.id
        ');

        /*
          Make sure every existing course
          has a valid department.
         */
        $missingDepartments = DB::table('courses')
            ->whereNull('department_id')
            ->exists();

        if ($missingDepartments) {
            throw new RuntimeException(
                'Some courses have department codes that do not exist in the departments table.'
            );
        }

        /*
          Add foreign key.
         */
        Schema::table('courses', function (Blueprint $table) {
            $table->foreign('department_id')
                ->references('id')
                ->on('departments')
                ->restrictOnDelete();
        });

        /*
          Remove old department column.
         */
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn('department');
        });
    }

    public function down(): void
    {
        /*
          Re-create old department column.
         */
        Schema::table('courses', function (Blueprint $table) {
            $table->string('department', 20)
                ->nullable()
                ->after('course_name');
        });

        /*
          Restore department codes.
         */
        DB::statement('
            UPDATE courses
            INNER JOIN departments
                ON courses.department_id = departments.id
            SET courses.department = departments.code
        ');

        /*
          Remove foreign key and department_id.
         */
        Schema::table('courses', function (Blueprint $table) {
            $table->dropForeign([
                'department_id'
            ]);

            $table->dropColumn('department_id');
        });
    }
};


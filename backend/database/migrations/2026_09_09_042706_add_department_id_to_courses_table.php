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
         * department_id is already part of the current courses schema.
         * So do not add it again.
         */
        if (!Schema::hasColumn('courses', 'department_id')) {
            Schema::table('courses', function (Blueprint $table) {
                $table->unsignedBigInteger('department_id')
                    ->nullable()
                    ->after('course_name');
            });
        }

        /*
         * If the old department column still exists,
         * migrate its values to department_id.
         */
        if (Schema::hasColumn('courses', 'department')) {

            $courses = DB::table('courses')
                ->whereNull('department_id')
                ->get(['id', 'department']);

            foreach ($courses as $course) {
                $departmentId = DB::table('departments')
                    ->where('code', $course->department)
                    ->value('id');

                if ($departmentId) {
                    DB::table('courses')
                        ->where('id', $course->id)
                        ->update([
                            'department_id' => $departmentId
                        ]);
                }
            }

            /*
             * Remove old department column.
             */
            Schema::table('courses', function (Blueprint $table) {
                $table->dropColumn('department');
            });
        }

        /*
         * Add foreign key only if it does not already exist.
         *
         * On a fresh SQLite database, the current schema may already
         * contain the required foreign key.
         */
        if (DB::connection()->getDriverName() !== 'sqlite') {
            try {
                Schema::table('courses', function (Blueprint $table) {
                    $table->foreign('department_id')
                        ->references('id')
                        ->on('departments')
                        ->restrictOnDelete();
                });
            } catch (\Throwable $e) {
                // Foreign key may already exist. Ignore duplicate FK error.
            }
        }
    }

    public function down(): void
    {
        /*
         * Only restore the old department column if it does not exist.
         */
        if (!Schema::hasColumn('courses', 'department')) {
            Schema::table('courses', function (Blueprint $table) {
                $table->string('department', 20)
                    ->nullable()
                    ->after('course_name');
            });

            /*
             * Restore department codes without MySQL-specific JOIN syntax.
             */
            $courses = DB::table('courses')
                ->whereNotNull('department_id')
                ->get(['id', 'department_id']);

            foreach ($courses as $course) {
                $departmentCode = DB::table('departments')
                    ->where('id', $course->department_id)
                    ->value('code');

                if ($departmentCode) {
                    DB::table('courses')
                        ->where('id', $course->id)
                        ->update([
                            'department' => $departmentCode
                        ]);
                }
            }
        }

        /*
         * Remove foreign key and department_id only if appropriate.
         */
        if (Schema::hasColumn('courses', 'department_id')) {
            try {
                Schema::table('courses', function (Blueprint $table) {
                    $table->dropForeign(['department_id']);
                });
            } catch (\Throwable $e) {
                // Foreign key may not exist.
            }
        }
    }
};
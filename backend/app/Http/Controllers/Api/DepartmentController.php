<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\Course;
use App\Models\Department;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class DepartmentController extends Controller
{
    public function index()
    {
        $departments = Department::latest()->get();

        foreach ($departments as $department) {

            $department->student_count = Student::where(
                'department',
                $department->code
            )->count();

            $department->batch_count = Batch::where(
                'department',
                $department->code
            )->count();

            
            $department->course_count = Course::where(
                'department_id',
                $department->id
            )->count();
        }

        return response()->json([
            'success' => true,
            'departments' => $departments,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'max:20',
                'unique:departments,code',
            ],

            'name' => [
                'required',
                'string',
                'max:255',
                'unique:departments,name',
            ],

            'status' => [
                'nullable',
                Rule::in([
                    'Active',
                    'Inactive',
                ]),
            ],
        ]);

        $validated['status'] = $validated['status'] ?? 'Active';

        $department = Department::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Department created successfully.',
            'department' => $department,
        ], 201);
    }

    public function show(Department $department)
    {
        $department->student_count = Student::where(
            'department',
            $department->code
        )->count();

        $department->batch_count = Batch::where(
            'department',
            $department->code
        )->count();

        $department->course_count = Course::where(
            'department_id',
            $department->id
        )->count();

        return response()->json([
            'success' => true,
            'department' => $department,
        ]);
    }

    public function update(Request $request, Department $department)
    {
        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'max:20',
                'unique:departments,code,' . $department->id,
            ],

            'name' => [
                'required',
                'string',
                'max:255',
                'unique:departments,name,' . $department->id,
            ],

            'status' => [
                'required',
                Rule::in([
                    'Active',
                    'Inactive',
                ]),
            ],
        ]);

        DB::transaction(function () use (
            $department,
            $validated
        ) {
            $oldCode = $department->code;
            $newCode = $validated['code'];

            /*
              Students and batches still store department code.
              So update their codes if department code changes.
             */
            if ($oldCode !== $newCode) {

                Student::where(
                    'department',
                    $oldCode
                )->update([
                    'department' => $newCode,
                ]);

                Batch::where(
                    'department',
                    $oldCode
                )->update([
                    'department' => $newCode,
                ]);
            }

            /*
              Courses DO NOT need updating here.
             
              Course stores department_id.
              Department ID remains the same when
              code/name changes.
             */
            $department->update($validated);
        });

        return response()->json([
            'success' => true,
            'message' => 'Department updated successfully.',
            'department' => $department->fresh(),
        ]);
    }

    public function destroy(Department $department)
    {
        $hasStudents = Student::where(
            'department',
            $department->code
        )->exists();

        $hasBatches = Batch::where(
            'department',
            $department->code
        )->exists();

        
        $hasCourses = Course::where(
            'department_id',
            $department->id
        )->exists();

        if (
            $hasStudents ||
            $hasBatches ||
            $hasCourses
        ) {
            return response()->json([
                'success' => false,
                'message' =>
                    'This department cannot be deleted because it has related students, batches, or courses.',
            ], 422);
        }

        $department->delete();

        return response()->json([
            'success' => true,
            'message' => 'Department deleted successfully.',
        ]);
    }
}
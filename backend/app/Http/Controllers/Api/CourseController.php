<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class CourseController extends Controller
{
    /*
     * Display all courses.
     */
    public function index(Request $request)
    {
        // Temporary production debugging
        Log::info('COURSE DEBUG', [
            'db' => config('database.default'),
            'database' => DB::connection()->getDatabaseName(),
            'bba_department_id' => Course::where(
                'course_code',
                'BBA101'
            )->value('department_id'),

            'bba_department' => Course::with('department')
                ->where('course_code', 'BBA101')
                ->first()?->department?->code,
        ]);

        $query = Course::with('department');

        // Search
        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where(
                    'course_code',
                    'like',
                    "%{$search}%"
                )->orWhere(
                    'course_name',
                    'like',
                    "%{$search}%"
                );
            });
        }

        // Department filter
        if ($request->filled('department')) {
            $query->whereHas('department', function ($q) use ($request) {
                $q->where('code', $request->department);
            });
        }

        // Semester filter
        if ($request->filled('semester')) {
            $query->where(
                'semester',
                $request->semester
            );
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where(
                'status',
                $request->status
            );
        }

        $courses = $query
            ->orderBy('department_id')
            ->orderBy('semester')
            ->orderBy('course_code')
            ->get()
            ->map(function ($course) {
                return [
                    'id' => $course->id,
                    'course_code' => $course->course_code,
                    'course_name' => $course->course_name,

                    // Keep frontend API format unchanged.
                    'department' => $course->department?->code,

                    'department_id' => $course->department_id,
                    'semester' => $course->semester,
                    'credit' => $course->credit,
                    'course_type' => $course->course_type,
                    'description' => $course->description,
                    'status' => $course->status,
                ];
            });

        return response()->json([
            'success' => true,

            // Temporary debugging information
            'debug' => [
                'db' => config('database.default'),
                'database' => DB::connection()->getDatabaseName(),

                'bba_department_id' => Course::where(
                    'course_code',
                    'BBA101'
                )->value('department_id'),

                'bba_department' => Course::with('department')
                    ->where('course_code', 'BBA101')
                    ->first()?->department?->code,

                'raw_bba' => DB::table('courses')
                    ->where('course_code', 'BBA101')
                    ->first(),
            ],

            'courses' => $courses,
        ]);
    }

    /*
     * Store a new course.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'course_code' => [
                'required',
                'string',
                'max:50',
                'unique:courses,course_code',
            ],

            'course_name' => [
                'required',
                'string',
                'max:255',
            ],

            /*
             * department is the department CODE
             * received from Vue.
             *
             * Example:
             * CSE
             * Mechanical
             * Textile
             */
            'department' => [
                'required',
                Rule::exists('departments', 'code')
                    ->where(function ($query) {
                        $query->where('status', 'Active');
                    }),
            ],

            'semester' => [
                'required',
                Rule::in([
                    '1st',
                    '2nd',
                    '3rd',
                    '4th',
                    '5th',
                    '6th',
                    '7th',
                    '8th',
                ]),
            ],

            'credit' => [
                'required',
                'numeric',
                'min:0.5',
                'max:10',
            ],

            'course_type' => [
                'required',
                Rule::in([
                    'Theory',
                    'Lab',
                ]),
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'status' => [
                'required',
                Rule::in([
                    'Active',
                    'Inactive',
                ]),
            ],
        ]);

        $departmentId = \App\Models\Department::where(
            'code',
            $validated['department']
        )->value('id');

        $validated['department_id'] = $departmentId;

        unset($validated['department']);

        $course = Course::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Course created successfully.',
            'course' => $course->load('department'),
        ], 201);
    }

    /*
     * Display a specific course.
     */
    public function show(Course $course)
    {
        $course->load('department');

        return response()->json([
            'success' => true,
            'course' => [
                'id' => $course->id,
                'course_code' => $course->course_code,
                'course_name' => $course->course_name,
                'department' => $course->department?->code,
                'department_id' => $course->department_id,
                'semester' => $course->semester,
                'credit' => $course->credit,
                'course_type' => $course->course_type,
                'description' => $course->description,
                'status' => $course->status,
            ],
        ]);
    }

    /*
     * Update a course.
     */
    public function update(Request $request, Course $course)
    {
        $validated = $request->validate([
            'course_code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('courses', 'course_code')
                    ->ignore($course->id),
            ],

            'course_name' => [
                'required',
                'string',
                'max:255',
            ],

            'department' => [
                'required',
                Rule::exists('departments', 'code')
                    ->where(function ($query) {
                        $query->where('status', 'Active');
                    }),
            ],

            'semester' => [
                'required',
                Rule::in([
                    '1st',
                    '2nd',
                    '3rd',
                    '4th',
                    '5th',
                    '6th',
                    '7th',
                    '8th',
                ]),
            ],

            'credit' => [
                'required',
                'numeric',
                'min:0.5',
                'max:10',
            ],

            'course_type' => [
                'required',
                Rule::in([
                    'Theory',
                    'Lab',
                ]),
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'status' => [
                'required',
                Rule::in([
                    'Active',
                    'Inactive',
                ]),
            ],
        ]);

        $departmentId = \App\Models\Department::where(
            'code',
            $validated['department']
        )->value('id');

        $validated['department_id'] = $departmentId;

        unset($validated['department']);

        $course->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Course updated successfully.',
            'course' => $course->fresh()->load('department'),
        ]);
    }

    /*
     * Delete a course.
     */
    public function destroy(Course $course)
    {
        $course->delete();

        return response()->json([
            'success' => true,
            'message' => 'Course deleted successfully.',
        ]);
    }
}

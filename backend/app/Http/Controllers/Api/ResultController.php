<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Result;
use App\Models\Student;
use App\Models\Course;
use App\Models\Enrollment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ResultController extends Controller
{
    /*
    
     Result List / Search
    
    */

    public function index(Request $request)
    {
        $query = Result::with([
            'student.user',
            'student.batch',
            'course',
        ]);

        if ($request->filled('search')) {
            $search = trim($request->search);

            $query->where(function ($q) use ($search) {
                $q->whereHas('student.user', function ($userQuery) use ($search) {
                    $userQuery
                        ->where('user_id', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%");
                });

                $q->orWhereHas('course', function ($courseQuery) use ($search) {
                    $courseQuery
                        ->where('course_code', 'like', "%{$search}%")
                        ->orWhere('course_name', 'like', "%{$search}%");
                });
            });
        }

        if ($request->filled('department')) {
            $query->whereHas('student', function ($studentQuery) use ($request) {
                $studentQuery->where(
                    'department',
                    $request->department
                );
            });
        }

        if ($request->filled('semester')) {
            $query->where(
                'semester',
                $request->semester
            );
        }

        $results = $query
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'success' => true,
            'results' => $results,
        ]);
    }

    /*
    
     Store Result
    
    */

    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_id' => [
                'required',
                'integer',
                'exists:students,id',
            ],

            'course_id' => [
                'required',
                'integer',
                'exists:courses,id',
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

            'marks' => [
                'required',
                'integer',
                'min:0',
                'max:100',
            ],
        ]);

        $student = Student::with('batch')
            ->findOrFail($validated['student_id']);

        $course = Course::findOrFail(
            $validated['course_id']
        );

        /*
         Department Check
        */

      if ($student->department !== $course->department?->code) {
    return response()->json([
        'message' =>
            'The selected course does not belong to the student department.',
    ], 422);
}

        /*
         Course Semester Check
        */

        if ($course->semester !== $validated['semester']) {
            return response()->json([
                'message' =>
                    'The selected course does not belong to the selected semester.',
            ], 422);
        }

        /*
         Duplicate Result Check
        */

        $exists = Result::where(
            'student_id',
            $student->id
        )
            ->where(
                'course_id',
                $course->id
            )
            ->exists();

        if ($exists) {
            return response()->json([
                'message' =>
                    'A result for this student and course already exists.',
            ], 422);
        }

        /*
         Find Corresponding Enrollment
        */

        $enrollment = Enrollment::with('section')
            ->where('student_id', $student->id)
            ->where('course_id', $course->id)
            ->where('semester', $validated['semester'])
            ->first();

        if (!$enrollment) {
            return response()->json([
                'message' =>
                    'No enrollment record exists for this student and course.',
            ], 422);
        }

        /*
         Calculate Grade
        */

        $gradeData = $this->calculateGrade(
            $validated['marks']
        );

        $result = DB::transaction(function () use (
            $validated,
            $gradeData,
            $enrollment
        ) {
            /*
             If enrollment is Active,
             it stops occupying a current seat
            */

            if (
                $enrollment->status === 'Active' &&
                $enrollment->section &&
                $enrollment->section->enrolled > 0
            ) {
                $enrollment->section->decrement(
                    'enrolled'
                );
            }

            /*
             Result creation means course is completed
            */

            if (
                in_array(
                    $enrollment->status,
                    ['Active', 'Completed'],
                    true
                )
            ) {
                $enrollment->update([
                    'status' => 'Completed',
                ]);
            }

            return Result::create([
                'student_id' =>
                    $validated['student_id'],

                'course_id' =>
                    $validated['course_id'],

                'semester' =>
                    $validated['semester'],

                'marks' =>
                    $validated['marks'],

                'grade' =>
                    $gradeData['grade'],

                'grade_point' =>
                    $gradeData['grade_point'],

                'status' =>
                    $gradeData['status'],
            ]);
        });

        $result->load([
            'student.user',
            'student.batch',
            'course',
        ]);

        return response()->json([
            'success' => true,
            'message' =>
                'Result added successfully.',

            'result' => $result,
        ], 201);
    }

    /*
    
     Show Result
    
    */

    public function show(Result $result)
    {
        $result->load([
            'student.user',
            'student.batch',
            'course',
        ]);

        return response()->json([
            'success' => true,
            'result' => $result,
        ]);
    }

    /*
    
     Update Result
    
    */

    public function update(
        Request $request,
        Result $result
    ) {
        $validated = $request->validate([
            'student_id' => [
                'required',
                'integer',
                'exists:students,id',
            ],

            'course_id' => [
                'required',
                'integer',
                'exists:courses,id',
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

            'marks' => [
                'required',
                'integer',
                'min:0',
                'max:100',
            ],
        ]);

        $student = Student::with('batch')
            ->findOrFail(
                $validated['student_id']
            );

        $course = Course::findOrFail(
            $validated['course_id']
        );

        /*
         Department Check
        */

     if ($student->department !== $course->department?->code) {
    return response()->json([
        'message' =>
            'The selected course does not belong to the student department.',
    ], 422);
}

        /*
         Course Semester Check
        */

        if ($course->semester !== $validated['semester']) {
            return response()->json([
                'message' =>
                    'The selected course does not belong to the selected semester.',
            ], 422);
        }

        /*
         Duplicate Result Check
        */

        $duplicate = Result::where(
            'student_id',
            $validated['student_id']
        )
            ->where(
                'course_id',
                $validated['course_id']
            )
            ->where(
                'id',
                '!=',
                $result->id
            )
            ->exists();

        if ($duplicate) {
            return response()->json([
                'message' =>
                    'A result for this student and course already exists.',
            ], 422);
        }

        /*
         Corresponding Enrollment
        */

        $enrollment = Enrollment::with('section')
            ->where(
                'student_id',
                $validated['student_id']
            )
            ->where(
                'course_id',
                $validated['course_id']
            )
            ->where(
                'semester',
                $validated['semester']
            )
            ->first();

        if (!$enrollment) {
            return response()->json([
                'message' =>
                    'No enrollment record exists for this student and course.',
            ], 422);
        }

        /*
         Calculate Grade
        */

        $gradeData = $this->calculateGrade(
            $validated['marks']
        );

        DB::transaction(function () use (
            $result,
            $validated,
            $gradeData,
            $enrollment
        ) {
            /*
             If somehow Active, convert to Completed
             and remove seat.
            */

            if ($enrollment->status === 'Active') {
                if (
                    $enrollment->section &&
                    $enrollment->section->enrolled > 0
                ) {
                    $enrollment->section->decrement(
                        'enrolled'
                    );
                }
            }

            /*
             Result exists => Completed
            */

            $enrollment->update([
                'status' => 'Completed',
            ]);

            /*
             Update Result
            */

            $result->update([
                'student_id' =>
                    $validated['student_id'],

                'course_id' =>
                    $validated['course_id'],

                'semester' =>
                    $validated['semester'],

                'marks' =>
                    $validated['marks'],

                'grade' =>
                    $gradeData['grade'],

                'grade_point' =>
                    $gradeData['grade_point'],

                'status' =>
                    $gradeData['status'],
            ]);
        });

        $result->load([
            'student.user',
            'student.batch',
            'course',
        ]);

        return response()->json([
            'success' => true,
            'message' =>
                'Result updated successfully.',

            'result' => $result,
        ]);
    }

    /*
    
     Delete Result
    
    */

    public function destroy(Result $result)
    {
        DB::transaction(function () use ($result) {

            /*
             Find corresponding enrollment
            */

            $enrollment = Enrollment::with('section')
                ->where(
                    'student_id',
                    $result->student_id
                )
                ->where(
                    'course_id',
                    $result->course_id
                )
                ->where(
                    'semester',
                    $result->semester
                )
                ->first();

            /*
             Delete Result
            */

            $result->delete();

            /*
             Result deleted => enrollment becomes Active again
            */

            if (
                $enrollment &&
                $enrollment->status === 'Completed'
            ) {
                $enrollment->update([
                    'status' => 'Active',
                ]);

                /*
                 Active enrollment occupies a seat again
                */

                if ($enrollment->section) {
                    $enrollment->section->increment(
                        'enrolled'
                    );
                }
            }
        });

        return response()->json([
            'success' => true,
            'message' =>
                'Result deleted successfully.',
        ]);
    }

    
/*

 Student Result Summary

*/

public function studentSummary(string $userId)
{
    /*
     Find Student by Student ID
    */

    $student = Student::with([
        'user',
        'batch',
    ])
        ->whereHas('user', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        })
        ->first();

    /*
     Student does not exist
    */

    if (!$student) {
        return response()->json([
            'success' => false,
            'message' => 'Student not found.',
        ], 404);
    }

    /*
     Find Results
    */

    $results = Result::with('course')
        ->where('student_id', $student->id)
        ->orderBy('semester')
        ->get();

    /*
     Student exists but has no result
    */

    if ($results->isEmpty()) {
        return response()->json([
            'success' => false,
            'message' => 'Result not found.',
        ], 404);
    }

    /*
     Semester Order
    */

    $semesterOrder = [
        '1st' => 1,
        '2nd' => 2,
        '3rd' => 3,
        '4th' => 4,
        '5th' => 5,
        '6th' => 6,
        '7th' => 7,
        '8th' => 8,
    ];

    /*
     Group Results by Semester
    */

    $grouped = $results->groupBy('semester');

    $semesters = [];

    foreach ($grouped as $semester => $semesterResults) {

        $credits = 0;
        $qualityPoints = 0;

        foreach ($semesterResults as $item) {

            $credit = (float) (
                $item->course?->credit ?? 0
            );

            $gradePoint = (float) $item->grade_point;

            $credits += $credit;

            $qualityPoints +=
                $credit * $gradePoint;
        }

        $gpa = $credits > 0
            ? round(
                $qualityPoints / $credits,
                2
            )
            : 0;

        $semesters[] = [
            'semester' => $semester,
            'gpa' => $gpa,
            'credits' => round($credits, 2),
            'results' => $semesterResults->values(),
        ];
    }

    /*
     Sort Semesters
    */

    usort(
        $semesters,
        function ($a, $b) use ($semesterOrder) {
            return (
                $semesterOrder[$a['semester']] ?? 99
            ) <=> (
                $semesterOrder[$b['semester']] ?? 99
            );
        }
    );

    /*
     Calculate CGPA
    */

    $totalCredits = 0;
    $totalQualityPoints = 0;

    foreach ($results as $item) {

        $credit = (float) (
            $item->course?->credit ?? 0
        );

        $gradePoint = (float) $item->grade_point;

        $totalCredits += $credit;

        $totalQualityPoints +=
            $credit * $gradePoint;
    }

    $cgpa = $totalCredits > 0
        ? round(
            $totalQualityPoints / $totalCredits,
            2
        )
        : 0;

    /*
     Final Response
    */

    return response()->json([
        'success' => true,

        'student' => [
            'student_id' =>
                $student->user?->user_id,

            'name' =>
                $student->user?->name,

            'department' =>
                $student->department,

            'batch_number' =>
                $student->batch?->batch_number,
        ],

        'cgpa' =>
            $cgpa,

        'total_credits' =>
            round($totalCredits, 2),

        'semesters' =>
            $semesters,
    ]);
}


    /*
    
     Grade Calculator
    
    */

    private function calculateGrade(
        int $marks
    ): array {
        if ($marks >= 80) {
            return [
                'grade' => 'A+',
                'grade_point' => 4.00,
                'status' => 'Passed',
            ];
        }

        if ($marks >= 75) {
            return [
                'grade' => 'A',
                'grade_point' => 3.75,
                'status' => 'Passed',
            ];
        }

        if ($marks >= 70) {
            return [
                'grade' => 'A-',
                'grade_point' => 3.50,
                'status' => 'Passed',
            ];
        }

        if ($marks >= 65) {
            return [
                'grade' => 'B+',
                'grade_point' => 3.25,
                'status' => 'Passed',
            ];
        }

        if ($marks >= 60) {
            return [
                'grade' => 'B',
                'grade_point' => 3.00,
                'status' => 'Passed',
            ];
        }

        if ($marks >= 55) {
            return [
                'grade' => 'B-',
                'grade_point' => 2.75,
                'status' => 'Passed',
            ];
        }

        if ($marks >= 50) {
            return [
                'grade' => 'C+',
                'grade_point' => 2.50,
                'status' => 'Passed',
            ];
        }

        if ($marks >= 45) {
            return [
                'grade' => 'C',
                'grade_point' => 2.25,
                'status' => 'Passed',
            ];
        }

        if ($marks >= 40) {
            return [
                'grade' => 'D',
                'grade_point' => 2.00,
                'status' => 'Passed',
            ];
        }

        return [
            'grade' => 'F',
            'grade_point' => 0.00,
            'status' => 'Failed',
        ];
    }
}
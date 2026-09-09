<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Result;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ResultController extends Controller
{
    /*
    
     Result List
    
    */

    public function index(Request $request)
    {
        $query = Result::with([
            'student.user',
            'student.batch',
            'course',
        ]);

        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->whereHas('student.user', function ($user) use ($search) {
                    $user->where('user_id', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%");
                })
                ->orWhereHas('course', function ($course) use ($search) {
                    $course->where('course_code', 'like', "%{$search}%")
                        ->orWhere('course_name', 'like', "%{$search}%");
                });
            });
        }

        if ($request->filled('department')) {
            $query->whereHas('course', function ($course) use ($request) {
                $course->where('department', $request->department);
            });
        }

        if ($request->filled('semester')) {
            $query->where('semester', $request->semester);
        }

        if ($request->filled('course_id')) {
            $query->where('course_id', $request->course_id);
        }

        return response()->json([
            'success' => true,
            'results' => $query
                ->orderByDesc('id')
                ->get(),
        ]);
    }

    /*
    
     Student Info For Add Result
    
    */

    public function studentInfo(string $studentId)
    {
        $student = Student::with([
            'user:id,user_id,name,email',
            'batch:id,department,batch_number,start_year,current_semester,status',
            'enrollments.course',
            'enrollments.section',
        ])
        ->whereHas('user', function ($query) use ($studentId) {
            $query->where('user_id', $studentId);
        })
        ->first();

        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Student not found.',
            ], 404);
        }

        $enrollments = $student->enrollments
            ->sortBy(function ($enrollment) {
                return [
                    (int) $enrollment->semester,
                    $enrollment->course?->course_code ?? '',
                ];
            })
            ->values();

        $resultCourseIds = Result::where('student_id', $student->id)
            ->pluck('course_id')
            ->toArray();

        $courses = $enrollments->map(function ($enrollment) use ($resultCourseIds) {
            return [
                'enrollment_id' => $enrollment->id,
                'course_id' => $enrollment->course_id,
                'section_id' => $enrollment->section_id,
                'semester' => $enrollment->semester,
                'enrollment_status' => $enrollment->status,

                'has_result' => in_array(
                    $enrollment->course_id,
                    $resultCourseIds
                ),

                'course' => $enrollment->course,
            ];
        });

        return response()->json([
            'success' => true,

            'student' => [
                'id' => $student->id,
                'student_id' => $student->user->user_id,
                'name' => $student->user->name,
                'email' => $student->user->email,
                'department' => $student->department,
                'batch_number' => $student->batch?->batch_number,
                'current_semester' => $student->batch?->current_semester
                    ?? $student->semester,
            ],

            'enrollments' => $courses,
        ]);
    }

    /*
    
     Add Result
    
    */

    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_id' => [
                'required',
                'exists:students,id',
            ],

            'course_id' => [
                'required',
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

        $student = Student::with('user')->findOrFail(
            $validated['student_id']
        );

        $course = Course::findOrFail(
            $validated['course_id']
        );

        /*
        
         Department validation
        
        */

        if ($student->department !== $course->department) {
            return response()->json([
                'success' => false,
                'message' => 'Selected course does not belong to the student department.',
            ], 422);
        }

        /*
        
         Course semester validation
        
        */

        if ($course->semester !== $validated['semester']) {
            return response()->json([
                'success' => false,
                'message' => 'Selected course does not belong to the selected semester.',
            ], 422);
        }

        /*
        
         Enrollment validation
        
        */

        $enrollment = Enrollment::where('student_id', $student->id)
            ->where('course_id', $course->id)
            ->where('semester', $validated['semester'])
            ->first();

        if (!$enrollment) {
            return response()->json([
                'success' => false,
                'message' => 'Result cannot be added because the student is not enrolled in this course for this semester.',
            ], 422);
        }

        if ($enrollment->status === 'Dropped') {
            return response()->json([
                'success' => false,
                'message' => 'Result cannot be added because the enrollment for this course was dropped.',
            ], 422);
        }

        /*
        
         Existing Result
        
        */

        $exists = Result::where('student_id', $student->id)
            ->where('course_id', $course->id)
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'This student already has a result for this course.',
            ], 422);
        }

        $grade = $this->calculateGrade($validated['marks']);

        $result = DB::transaction(function () use (
            $validated,
            $grade,
            $enrollment
        ) {
            $result = Result::create([
                'student_id' => $validated['student_id'],
                'course_id' => $validated['course_id'],
                'semester' => $validated['semester'],
                'marks' => $validated['marks'],
                'grade' => $grade['grade'],
                'grade_point' => $grade['grade_point'],
                'status' => $grade['status'],
            ]);

            /*
             * Passed result means enrollment is completed.
             * Failed result keeps enrollment Active.
             */
            if ($grade['status'] === 'Passed') {
                if ($enrollment->status === 'Active') {
                    $enrollment->update([
                        'status' => 'Completed',
                    ]);
                }
            }

            return $result;
        });

        return response()->json([
            'success' => true,
            'message' => 'Result added successfully.',

            'result' => $result->load([
                'student.user',
                'student.batch',
                'course',
            ]),
        ], 201);
    }

    /*
    
     Show
    
    */

    public function show(Result $result)
    {
        return response()->json([
            'success' => true,

            'result' => $result->load([
                'student.user',
                'student.batch',
                'course',
            ]),
        ]);
    }

    /*
    
     Update Result
    
    */

    public function update(Request $request, Result $result)
    {
        $validated = $request->validate([
            'student_id' => [
                'required',
                'exists:students,id',
            ],

            'course_id' => [
                'required',
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

        $student = Student::findOrFail(
            $validated['student_id']
        );

        $course = Course::findOrFail(
            $validated['course_id']
        );

        /*
        
         Department
        
        */

        if ($student->department !== $course->department) {
            return response()->json([
                'success' => false,
                'message' => 'Selected course does not belong to the student department.',
            ], 422);
        }

        /*
        
         Semester
        
        */

        if ($course->semester !== $validated['semester']) {
            return response()->json([
                'success' => false,
                'message' => 'Selected course does not belong to the selected semester.',
            ], 422);
        }

        /*
        
         Enrollment
        
        */

        $enrollment = Enrollment::where('student_id', $student->id)
            ->where('course_id', $course->id)
            ->where('semester', $validated['semester'])
            ->first();

        if (!$enrollment) {
            return response()->json([
                'success' => false,
                'message' => 'Result cannot be updated because the student is not enrolled in this course for this semester.',
            ], 422);
        }

        if ($enrollment->status === 'Dropped') {
            return response()->json([
                'success' => false,
                'message' => 'Result cannot be updated because this enrollment was dropped.',
            ], 422);
        }

        /*
        
         Duplicate Result
        
        */

        $duplicate = Result::where('student_id', $student->id)
            ->where('course_id', $course->id)
            ->where('id', '!=', $result->id)
            ->exists();

        if ($duplicate) {
            return response()->json([
                'success' => false,
                'message' => 'This student already has a result for this course.',
            ], 422);
        }

        $oldStatus = $result->status;

        $grade = $this->calculateGrade(
            $validated['marks']
        );

        DB::transaction(function () use (
            $result,
            $validated,
            $grade,
            $enrollment,
            $oldStatus
        ) {
            /*
             * If old result was Passed and its enrollment
             * was Completed, temporarily restore it to Active.
             */
            if (
                $oldStatus === 'Passed' &&
                $enrollment->status === 'Completed'
            ) {
                $enrollment->update([
                    'status' => 'Active',
                ]);
            }

            $result->update([
                'student_id' => $validated['student_id'],
                'course_id' => $validated['course_id'],
                'semester' => $validated['semester'],
                'marks' => $validated['marks'],
                'grade' => $grade['grade'],
                'grade_point' => $grade['grade_point'],
                'status' => $grade['status'],
            ]);

            /*
             * Passed → Completed
             * Failed → Active
             */
            if ($grade['status'] === 'Passed') {
                if ($enrollment->status === 'Active') {
                    $enrollment->update([
                        'status' => 'Completed',
                    ]);
                }
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Result updated successfully.',

            'result' => $result->fresh()->load([
                'student.user',
                'student.batch',
                'course',
            ]),
        ]);
    }

    /*
    
     Delete Result
    
    */

    public function destroy(Result $result)
    {
        DB::transaction(function () use ($result) {
            /*
             * Only restore Completed enrollment.
             * If it was manually Dropped, keep it Dropped.
             */
            if ($result->status === 'Passed') {
                $enrollment = Enrollment::where('student_id', $result->student_id)
                    ->where('course_id', $result->course_id)
                    ->where('semester', $result->semester)
                    ->where('status', 'Completed')
                    ->first();

                if ($enrollment) {
                    $enrollment->update([
                        'status' => 'Active',
                    ]);
                }
            }

            $result->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'Result deleted successfully.',
        ]);
    }

    /*
    
     Student Result Summary
    
    */

    public function studentSummary(string $userId)
    {
        $student = Student::with([
            'user',
            'batch',
            'results.course',
        ])
        ->whereHas('user', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        })
        ->first();

        if (!$student) {
            return response()->json([
                'message' => 'Student not found.',
            ], 404);
        }

        $semesterData = [];

        foreach (
            $student->results->groupBy('semester')
            as $semester => $results
        ) {
            $totalCredits = 0;
            $qualityPoints = 0;

            foreach ($results as $result) {
                $credit = (float) $result->course->credit;
                $gradePoint = (float) $result->grade_point;

                $totalCredits += $credit;
                $qualityPoints += $credit * $gradePoint;
            }

            $gpa = $totalCredits > 0
                ? round($qualityPoints / $totalCredits, 2)
                : 0;

            $semesterData[] = [
                'semester' => $semester,
                'gpa' => $gpa,
                'credits' => round($totalCredits, 2),
                'results' => $results->values(),
            ];
        }

        usort($semesterData, function ($a, $b) {
            return (int) $a['semester'] <=> (int) $b['semester'];
        });

        $totalCredits = 0;
        $totalQualityPoints = 0;

        foreach ($student->results as $result) {
            $credit = (float) $result->course->credit;
            $gradePoint = (float) $result->grade_point;

            $totalCredits += $credit;
            $totalQualityPoints += $credit * $gradePoint;
        }

        $cgpa = $totalCredits > 0
            ? round($totalQualityPoints / $totalCredits, 2)
            : 0;

        return response()->json([
            'success' => true,

            'student' => [
                'id' => $student->id,
                'student_id' => $student->user->user_id,
                'name' => $student->user->name,
                'department' => $student->department,
                'batch_number' => $student->batch?->batch_number,
            ],

            'cgpa' => $cgpa,
            'total_credits' => round($totalCredits, 2),

            'semesters' => $semesterData,
        ]);
    }

    /*
    
     Grade Calculation
    
    */

    private function calculateGrade(int $marks): array
    {
        return match (true) {
            $marks >= 80 => [
                'grade' => 'A+',
                'grade_point' => 4.00,
                'status' => 'Passed',
            ],

            $marks >= 75 => [
                'grade' => 'A',
                'grade_point' => 3.75,
                'status' => 'Passed',
            ],

            $marks >= 70 => [
                'grade' => 'A-',
                'grade_point' => 3.50,
                'status' => 'Passed',
            ],

            $marks >= 65 => [
                'grade' => 'B+',
                'grade_point' => 3.25,
                'status' => 'Passed',
            ],

            $marks >= 60 => [
                'grade' => 'B',
                'grade_point' => 3.00,
                'status' => 'Passed',
            ],

            $marks >= 55 => [
                'grade' => 'B-',
                'grade_point' => 2.75,
                'status' => 'Passed',
            ],

            $marks >= 50 => [
                'grade' => 'C+',
                'grade_point' => 2.50,
                'status' => 'Passed',
            ],

            $marks >= 45 => [
                'grade' => 'C',
                'grade_point' => 2.25,
                'status' => 'Passed',
            ],

            $marks >= 40 => [
                'grade' => 'D',
                'grade_point' => 2.00,
                'status' => 'Passed',
            ],

            default => [
                'grade' => 'F',
                'grade_point' => 0.00,
                'status' => 'Failed',
            ],
        };
    }
}
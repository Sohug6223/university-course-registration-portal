<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\Result;
use App\Models\Student;
use Illuminate\Http\Request;

class MyCoursesController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if (!$user || strtolower($user->role) !== 'student') {
            return response()->json([
                'message' => 'Unauthorized.'
            ], 403);
        }

        $student = Student::with([
            'user',
            'batch',
            'section',
        ])->where('user_id', $user->id)->first();

        if (!$student) {
            return response()->json([
                'message' => 'Student profile not found.'
            ], 404);
        }

        /*
        
         Enrollments
        
        */

        $enrollments = Enrollment::with([
            'course',
            'section',
        ])
        ->where('student_id', $student->id)
        ->orderByDesc('id')
        ->get();

        /*
        
         Results
        
        */

        $results = Result::where('student_id', $student->id)
            ->get()
            ->keyBy('course_id');

        /*
        
         Build Course List
        
        */

        $courses = $enrollments->map(function ($enrollment) use ($results) {

            $result = $results->get($enrollment->course_id);

            $grade = $result?->grade;
            $isFailed = $grade !== null
                && strtoupper(trim($grade)) === 'F';

            if ($isFailed) {
                $displayStatus = 'Failed';
            } elseif ($result && $grade !== null) {
                $displayStatus = 'Completed';
            } else {
                $displayStatus = $enrollment->status;
            }

            return [
                'id' => $enrollment->id,

                'course_id' => $enrollment->course_id,

                'course_code' =>
                    $enrollment->course?->course_code,

                'course_name' =>
                    $enrollment->course?->course_name,

                'credit' =>
                    $enrollment->course?->credit,

                'semester' =>
                    $enrollment->semester,

                'section' =>
                    $enrollment->section?->section_name,

                'status' =>
                    $displayStatus,

                'grade' =>
                    $grade,

                'enrollment_status' =>
                    $enrollment->status,
            ];
        });

        return response()->json([
            'student' => [
                'id' => $student->id,
                'user_id' => $user->user_id,
                'name' => $user->name,
                'department' => $student->department,
                'batch' => $student->batch?->batch_number,
                'section' => $student->section?->section_name
                    ?? $student->section,
                'current_semester' =>
                    $student->batch?->current_semester,
            ],

            'summary' => [
                'active' =>
                    $courses->where('status', 'Active')->count(),

                'completed' =>
                    $courses->where('status', 'Completed')->count(),

                'failed' =>
                    $courses->where('status', 'Failed')->count(),

                'total' =>
                    $courses->count(),
            ],

            'courses' => $courses->values(),
        ]);
    }
}
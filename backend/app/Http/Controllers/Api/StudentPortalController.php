<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\Result;
use Illuminate\Http\Request;

class StudentPortalController extends Controller
{
    public function dashboard(Request $request)
    {
        $user = $request->user();

        // Student profile linked with logged-in user
        $student = $user->student()
            ->with([
                'batch',
                'section',
            ])
            ->first();

        if (!$student) {
            return response()->json([
                'message' => 'Student profile not found.'
            ], 404);
        }

        /*
        
         Current Semester
        
        */

        $currentSemester = $student->batch?->current_semester ?? 'Not Set';

        /*
        
         Active Enrollments
        
        */

        $activeEnrollments = Enrollment::where('student_id', $student->id)
            ->where('status', 'Active')
            ->with('course')
            ->get();

        /*
        
         Enrolled Courses
        
        */

        $enrolledCourses = $activeEnrollments->count();

        /*
         
        Passed / Completed Results
        
        */

        $results = Result::where('student_id', $student->id)
            ->with('course')
            ->get();

        $completedResults = $results->filter(function ($result) {
            return strtoupper(trim($result->grade ?? '')) !== 'F';
        });

        /*
        
         Completed Courses
        
        */

        $completedCourses = $completedResults->count();

        /*

         Completed Credits
        
        */

        $completedCredits = $completedResults->sum(function ($result) {
            return (float) ($result->course?->credit ?? 0);
        });

        /*
        
         Response
        
        */

        return response()->json([
            'student' => [
                'id' => $student->id,
                'user_id' => $user->user_id,
                'name' => $user->name,
                'email' => $user->email,
                'department' => $student->department,
                'batch' => $student->batch?->batch_number,
                'semester' => $student->semester,
                'current_semester' => $currentSemester,
                'section' => $student->section?->section_name,
                'advisor' => $student->batch?->advisor_name,
                'status' => $student->status,
            ],

            'stats' => [
                'enrolledCourses' => $enrolledCourses,
                'completedCourses' => $completedCourses,
                'completedCredits' => $completedCredits,
                'currentSemester' => $currentSemester,
            ],
        ]);
    }
}
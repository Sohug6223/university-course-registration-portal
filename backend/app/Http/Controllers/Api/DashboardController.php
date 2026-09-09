<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Course;
use App\Models\Department;
use App\Models\Enrollment;

class DashboardController extends Controller
{
    public function index()
    {
        /*
        
         Recent Registrations
        
         Show maximum 5 UNIQUE students.
        
         If one student has registered multiple courses recently,
         only that student's latest registration will be shown.
        
        */

        $recentRegistrations = Enrollment::with([
            'student.user',
            'course',
        ])
            ->latest('created_at')
            ->get()
            ->unique('student_id')
            ->take(5)
            ->values()
            ->map(function ($enrollment) {
                return [
                    'id' => $enrollment->id,

                    'student_id' =>
                        $enrollment->student?->user?->user_id,

                    'student_name' =>
                        $enrollment->student?->user?->name ?? 'N/A',

                    'course_code' =>
                        $enrollment->course?->course_code ?? 'N/A',

                    'course_name' =>
                        $enrollment->course?->course_name ?? 'N/A',

                    'semester' =>
                        $enrollment->semester,

                    'status' =>
                        $enrollment->status,

                    'registered_at' =>
                        $enrollment->created_at?->toISOString(),
                ];
            });

        /*
        
         Dashboard Response
        
        */

        return response()->json([
            'success' => true,

            'stats' => [
                'total_students' => Student::count(),
                'total_courses' => Course::count(),
                'total_departments' => Department::count(),
            ],

            'recent_registrations' => $recentRegistrations,
        ]);
    }
}


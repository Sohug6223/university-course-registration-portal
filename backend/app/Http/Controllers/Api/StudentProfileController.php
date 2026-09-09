<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\Result;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StudentProfileController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if (!$user || $user->role !== 'student') {
            return response()->json([
                'message' => 'Unauthorized.'
            ], 403);
        }

        $student = Student::with([
            'user',
            'batch',
            'section'
        ])->where('user_id', $user->id)->first();

        if (!$student) {
            return response()->json([
                'message' => 'Student profile not found.'
            ], 404);
        }

        $results = Result::with('course')
            ->where('student_id', $student->id)
            ->get();

        $totalCourses = $results->count();
        $passedCourses = 0;
        $failedCourses = 0;
        $totalCredits = 0;
        $earnedCredits = 0;
        $qualityPoints = 0;

        foreach ($results as $result) {
            $credit = (float) ($result->course->credit ?? 0);
            $gradePoint = (float) ($result->grade_point ?? 0);

            $totalCredits += $credit;

            $isFailed = strtoupper(trim($result->grade ?? '')) === 'F';

            if ($isFailed) {
                $failedCourses++;
            } else {
                $passedCourses++;
                $earnedCredits += $credit;
                $qualityPoints += $credit * $gradePoint;
            }
        }

        $cgpa = $totalCredits > 0
            ? round($qualityPoints / $totalCredits, 2)
            : 0;

        return response()->json([
            'student' => [
                'id' => $student->id,
                'student_id' => $user->user_id,
                'name' => $user->name,
                'email' => $user->email,

                'department' => $student->department,
                'batch' => $student->batch->batch_number ?? null,
                'semester' => $student->batch->current_semester ?? null,
                'section' => $student->section->section_name ?? null,

                'role' => $user->role,
                'status' => $user->status ?? 'Active',
            ],

            'academic' => [
                'total_courses' => $totalCourses,
                'passed_courses' => $passedCourses,
                'failed_courses' => $failedCourses,
                'total_credits' => round($totalCredits, 2),
                'earned_credits' => round($earnedCredits, 2),
                'cgpa' => $cgpa,
            ]
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();

        if (!$user || $user->role !== 'student') {
            return response()->json([
                'message' => 'Unauthorized.'
            ], 403);
        }

        $student = Student::where('user_id', $user->id)->first();

        if (!$student) {
            return response()->json([
                'message' => 'Student profile not found.'
            ], 404);
        }

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
        ]);

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->save();

        return response()->json([
            'message' => 'Profile updated successfully.',
            'student' => [
                'name' => $user->name,
                'email' => $user->email,
            ],
        ]);
    }
}
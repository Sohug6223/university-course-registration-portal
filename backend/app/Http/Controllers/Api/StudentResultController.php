<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Result;
use App\Models\Student;
use Illuminate\Http\Request;

class StudentResultController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if (!$user || $user->role !== 'student') {
            return response()->json([
                'message' => 'Unauthorized.'
            ], 403);
        }

        $student = Student::with(['user', 'batch', 'section'])
            ->where('user_id', $user->id)
            ->first();

        if (!$student) {
            return response()->json([
                'message' => 'Student profile not found.'
            ], 404);
        }

        $results = Result::with('course')
            ->where('student_id', $student->id)
            ->orderBy('semester')
            ->orderBy('course_id')
            ->get();

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

        $results = $results->sortBy(function ($result) use ($semesterOrder) {
            return $semesterOrder[$result->semester] ?? 99;
        });

        $semesterGroups = $results->groupBy('semester');

        $semesters = [];

        $totalCredits = 0;
        $earnedCredits = 0;
        $totalQualityPoints = 0;
        $passedCourses = 0;
        $failedCourses = 0;

        foreach ($semesterGroups as $semester => $semesterResults) {
            $semesterCredits = 0;
            $semesterQualityPoints = 0;

            $courses = [];

            foreach ($semesterResults as $result) {
                $credit = (float) ($result->course->credit ?? 0);
                $gradePoint = (float) ($result->grade_point ?? 0);

                $isFailed = strtoupper(trim($result->grade ?? '')) === 'F';

                $semesterCredits += $credit;
                $totalCredits += $credit;

                if (!$isFailed) {
                    $earnedCredits += $credit;
                    $semesterQualityPoints += $credit * $gradePoint;
                    $totalQualityPoints += $credit * $gradePoint;
                    $passedCourses++;
                } else {
                    $failedCourses++;
                }

                $courses[] = [
                    'id' => $result->id,
                    'course_code' => $result->course->course_code ?? 'N/A',
                    'course_name' => $result->course->course_name ?? 'N/A',
                    'credit' => $credit,
                    'marks' => $result->marks,
                    'grade' => $result->grade,
                    'grade_point' => $gradePoint,
                    'status' => $isFailed ? 'Failed' : 'Passed',
                ];
            }

            $semesterGpa = $semesterCredits > 0
                ? round($semesterQualityPoints / $semesterCredits, 2)
                : 0;

            $semesters[] = [
                'semester' => $semester,
                'gpa' => $semesterGpa,
                'credits' => $semesterCredits,
                'courses' => $courses,
            ];
        }

        $cgpa = $totalCredits > 0
            ? round($totalQualityPoints / $totalCredits, 2)
            : 0;

        return response()->json([
            'student' => [
                'id' => $student->id,
                'student_id' => $student->user->user_id ?? null,
                'name' => $student->user->name ?? 'N/A',
                'email' => $student->user->email ?? null,
                'department' => $student->department,
                'batch' => $student->batch->batch_number ?? null,
                'semester' => $student->batch->current_semester ?? null,
                'section' => $student->section->section_name ?? null,
            ],

            'summary' => [
                'cgpa' => $cgpa,
                'total_credits' => round($totalCredits, 2),
                'earned_credits' => round($earnedCredits, 2),
                'total_courses' => $results->count(),
                'passed_courses' => $passedCourses,
                'failed_courses' => $failedCourses,
            ],

            'semesters' => $semesters,
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClassSchedule;
use App\Models\Student;
use Illuminate\Http\Request;

class StudentRoutineController extends Controller
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
            'batch',
            'section',
        ])->where('user_id', $user->id)->first();

        if (!$student) {
            return response()->json([
                'message' => 'Student profile not found.'
            ], 404);
        }

        $batch = $student->batch;

        if (!$batch) {
            return response()->json([
                'student' => [
                    'name' => $user->name,
                    'user_id' => $user->user_id,
                    'department' => $student->department,
                    'batch' => null,
                    'section' => null,
                    'semester' => null,
                ],
                'routine' => [],
                'message' => 'Batch is not assigned.'
            ]);
        }

        $section = $student->section;

        if (!$section) {
            return response()->json([
                'student' => [
                    'name' => $user->name,
                    'user_id' => $user->user_id,
                    'department' => $student->department,
                    'batch' => $batch->batch_number,
                    'section' => null,
                    'semester' => $batch->current_semester,
                ],
                'routine' => [],
                'message' => 'Section is not assigned.'
            ]);
        }

        $routine = ClassSchedule::with([
            'course',
            'section',
        ])
        ->where('section_id', $section->id)
        ->whereHas('section', function ($query) use ($batch) {
            $query->where('batch_id', $batch->id);
        })
        ->orderByRaw("
            FIELD(
                day,
                'Saturday',
                'Sunday',
                'Monday',
                'Tuesday',
                'Wednesday',
                'Thursday',
                'Friday'
            )
        ")
        ->orderBy('start_time')
        ->get()
        ->map(function ($schedule) {
            return [
                'id' => $schedule->id,

                'day' => $schedule->day,

                'start_time' => $schedule->start_time,

                'end_time' => $schedule->end_time,

                'room' => $schedule->room,

                'teacher' => $schedule->teacher_name,

                'course_code' =>
                    $schedule->course?->course_code,

                'course_name' =>
                    $schedule->course?->course_name,

                'credit' =>
                    $schedule->course?->credit,

                'section' =>
                    $schedule->section?->section_name,
            ];
        });

        return response()->json([
            'student' => [
                'name' => $user->name,
                'user_id' => $user->user_id,
                'department' => $student->department,
                'batch' => $batch->batch_number,
                'section' => $section->section_name,
                'semester' => $batch->current_semester,
            ],

            'routine' => $routine->values(),

            'message' => $routine->isEmpty()
                ? 'No class schedule has been published yet.'
                : null,
        ]);
    }
}
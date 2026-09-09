<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClassSchedule;
use App\Models\Course;
use App\Models\Section;
use Illuminate\Http\Request;

class ClassScheduleController extends Controller
{
    public function index(Request $request)
    {
        $query = ClassSchedule::with([
            'section.batch',
            'course.department',
        ]);

        if ($request->filled('department')) {
            $query->whereHas('section.batch', function ($q) use ($request) {
                $q->where('department', $request->department);
            });
        }

        if ($request->filled('batch_id')) {
            $query->whereHas('section', function ($q) use ($request) {
                $q->where('batch_id', $request->batch_id);
            });
        }

        if ($request->filled('day')) {
            $query->where('day', $request->day);
        }

        $schedules = $query
            ->orderBy('day')
            ->orderBy('start_time')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $schedules,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'section_id' => 'required|exists:sections,id',
            'course_id' => 'required|exists:courses,id',
            'teacher_name' => 'required|string|max:255',
            'day' => [
                'required',
                'in:Saturday,Sunday,Monday,Tuesday,Wednesday,Thursday,Friday',
            ],
            'start_time' => 'required|date_format:H:i',
            'end_time' => [
                'required',
                'date_format:H:i',
                'after:start_time',
            ],
            'room' => 'required|string|max:255',
        ]);

        $section = Section::with('batch')->findOrFail(
            $validated['section_id']
        );

        $course = Course::with('department')->findOrFail(
            $validated['course_id']
        );

        /*
          Make sure the course has a valid department.
         */
        if (!$course->department) {
            return response()->json([
                'success' => false,
                'message' => 'The selected course does not have a valid department.',
            ], 422);
        }

        /*
          Batch stores department CODE.
          Course stores department_id.
         
         
          compare:
          batch.department
                 with
          course.department.code
         */
        if (
            $section->batch &&
            $section->batch->department !== $course->department->code
        ) {
            return response()->json([
                'success' => false,
                'message' =>
                    'Course department does not match the section batch department.',
            ], 422);
        }

        /*
          Course semester must match batch current semester.
         */
        if (
            $section->batch &&
            $section->batch->current_semester !== $course->semester
        ) {
            return response()->json([
                'success' => false,
                'message' =>
                    'Course semester does not match the batch current semester.',
            ], 422);
        }

        /*
          Same course cannot be scheduled twice
          in the same section on the same day.
         */
        $courseDayConflict = ClassSchedule::where(
                'section_id',
                $validated['section_id']
            )
            ->where(
                'course_id',
                $validated['course_id']
            )
            ->where(
                'day',
                $validated['day']
            )
            ->exists();

        if ($courseDayConflict) {
            return response()->json([
                'success' => false,
                'message' =>
                    "Schedule conflict: This course is already scheduled for Section {$section->section_name} on {$validated['day']}.",
            ], 422);
        }

        /*
          Check overlapping classes for the same section.
         */
        $sectionTimeConflict = ClassSchedule::where(
                'section_id',
                $validated['section_id']
            )
            ->where(
                'day',
                $validated['day']
            )
            ->where(
                'start_time',
                '<',
                $validated['end_time']
            )
            ->where(
                'end_time',
                '>',
                $validated['start_time']
            )
            ->exists();

        if ($sectionTimeConflict) {
            return response()->json([
                'success' => false,
                'message' =>
                    "Schedule conflict: Section {$section->section_name} already has another class during this time on {$validated['day']}.",
            ], 422);
        }

        /*
          Check teacher time conflict.
         */
        $teacherTimeConflict = ClassSchedule::where(
                'day',
                $validated['day']
            )
            ->whereRaw(
                'LOWER(teacher_name) = LOWER(?)',
                [$validated['teacher_name']]
            )
            ->where(
                'start_time',
                '<',
                $validated['end_time']
            )
            ->where(
                'end_time',
                '>',
                $validated['start_time']
            )
            ->exists();

        if ($teacherTimeConflict) {
            return response()->json([
                'success' => false,
                'message' =>
                    "Schedule conflict: Teacher {$validated['teacher_name']} is already assigned during this time on {$validated['day']}.",
            ], 422);
        }

        /*
          Check room time conflict.
         */
        $roomTimeConflict = ClassSchedule::where(
                'day',
                $validated['day']
            )
            ->whereRaw(
                'LOWER(room) = LOWER(?)',
                [$validated['room']]
            )
            ->where(
                'start_time',
                '<',
                $validated['end_time']
            )
            ->where(
                'end_time',
                '>',
                $validated['start_time']
            )
            ->exists();

        if ($roomTimeConflict) {
            return response()->json([
                'success' => false,
                'message' =>
                    "Schedule conflict: Room {$validated['room']} is already occupied during this time on {$validated['day']}.",
            ], 422);
        }

        $schedule = ClassSchedule::create([
            'section_id' => $validated['section_id'],
            'course_id' => $validated['course_id'],
            'teacher_name' => $validated['teacher_name'],
            'day' => $validated['day'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'room' => $validated['room'],
        ]);

        $schedule->load([
            'section.batch',
            'course.department',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Class schedule created successfully.',
            'data' => $schedule,
        ], 201);
    }

    public function show($id)
    {
        $schedule = ClassSchedule::with([
            'section.batch',
            'course.department',
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $schedule,
        ]);
    }

    public function update(Request $request, $id)
    {
        $schedule = ClassSchedule::findOrFail($id);

        $validated = $request->validate([
            'section_id' => 'required|exists:sections,id',
            'course_id' => 'required|exists:courses,id',
            'teacher_name' => 'required|string|max:255',
            'day' => [
                'required',
                'in:Saturday,Sunday,Monday,Tuesday,Wednesday,Thursday,Friday',
            ],
            'start_time' => 'required|date_format:H:i',
            'end_time' => [
                'required',
                'date_format:H:i',
                'after:start_time',
            ],
            'room' => 'required|string|max:255',
        ]);

        $section = Section::with('batch')->findOrFail(
            $validated['section_id']
        );

    
        $course = Course::with('department')->findOrFail(
            $validated['course_id']
        );

        /*
          Make sure course department exists.
         */
        if (!$course->department) {
            return response()->json([
                'success' => false,
                'message' => 'The selected course does not have a valid department.',
            ], 422);
        }

        /*
          Department validation.
         
          Batch:
              department = "CSE"
         
          Course:
              department_id = 1
         
          Course department:
             Department.code = "CSE"
         */
        if (
            $section->batch &&
            $section->batch->department !== $course->department->code
        ) {
            return response()->json([
                'success' => false,
                'message' =>
                    'Course department does not match the section batch department.',
            ], 422);
        }

        /*
          Semester validation.
         */
        if (
            $section->batch &&
            $section->batch->current_semester !== $course->semester
        ) {
            return response()->json([
                'success' => false,
                'message' =>
                    'Course semester does not match the batch current semester.',
            ], 422);
        }

        
        $courseDayConflict = ClassSchedule::where(
                'id',
                '!=',
                $schedule->id
            )
            ->where(
                'section_id',
                $validated['section_id']
            )
            ->where(
                'course_id',
                $validated['course_id']
            )
            ->where(
                'day',
                $validated['day']
            )
            ->exists();

        if ($courseDayConflict) {
            return response()->json([
                'success' => false,
                'message' =>
                    "Schedule conflict: This course is already scheduled for Section {$section->section_name} on {$validated['day']}.",
            ], 422);
        }

        /*
          Section time conflict.
         */
        $sectionTimeConflict = ClassSchedule::where(
                'id',
                '!=',
                $schedule->id
            )
            ->where(
                'section_id',
                $validated['section_id']
            )
            ->where(
                'day',
                $validated['day']
            )
            ->where(
                'start_time',
                '<',
                $validated['end_time']
            )
            ->where(
                'end_time',
                '>',
                $validated['start_time']
            )
            ->exists();

        if ($sectionTimeConflict) {
            return response()->json([
                'success' => false,
                'message' =>
                    "Schedule conflict: Section {$section->section_name} already has another class during this time on {$validated['day']}.",
            ], 422);
        }

        /*
          Teacher time conflict.
         */
        $teacherTimeConflict = ClassSchedule::where(
                'id',
                '!=',
                $schedule->id
            )
            ->where(
                'day',
                $validated['day']
            )
            ->whereRaw(
                'LOWER(teacher_name) = LOWER(?)',
                [$validated['teacher_name']]
            )
            ->where(
                'start_time',
                '<',
                $validated['end_time']
            )
            ->where(
                'end_time',
                '>',
                $validated['start_time']
            )
            ->exists();

        if ($teacherTimeConflict) {
            return response()->json([
                'success' => false,
                'message' =>
                    "Schedule conflict: Teacher {$validated['teacher_name']} is already assigned during this time on {$validated['day']}.",
            ], 422);
        }

        /*
          Room time conflict.
         */
        $roomTimeConflict = ClassSchedule::where(
                'id',
                '!=',
                $schedule->id
            )
            ->where(
                'day',
                $validated['day']
            )
            ->whereRaw(
                'LOWER(room) = LOWER(?)',
                [$validated['room']]
            )
            ->where(
                'start_time',
                '<',
                $validated['end_time']
            )
            ->where(
                'end_time',
                '>',
                $validated['start_time']
            )
            ->exists();

        if ($roomTimeConflict) {
            return response()->json([
                'success' => false,
                'message' =>
                    "Schedule conflict: Room {$validated['room']} is already occupied during this time on {$validated['day']}.",
            ], 422);
        }

        $schedule->update([
            'section_id' => $validated['section_id'],
            'course_id' => $validated['course_id'],
            'teacher_name' => $validated['teacher_name'],
            'day' => $validated['day'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'room' => $validated['room'],
        ]);

        $schedule->load([
            'section.batch',
            'course.department',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Class schedule updated successfully.',
            'data' => $schedule,
        ]);
    }

    public function destroy($id)
    {
        $schedule = ClassSchedule::findOrFail($id);

        $schedule->delete();

        return response()->json([
            'success' => true,
            'message' => 'Schedule deleted successfully.',
        ]);
    }
}
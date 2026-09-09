<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Prerequisite;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PrerequisiteController extends Controller
{
    public function index(Request $request)
    {
        $query = Prerequisite::with([
            'course',
            'prerequisiteCourse',
        ]);

        if ($request->filled('course_id')) {
            $query->where(
                'course_id',
                $request->course_id
            );
        }

        $prerequisites = $query
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'prerequisites' => $prerequisites,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'course_id' => [
                'required',
                'integer',
                'exists:courses,id',
            ],

            'prerequisite_course_id' => [
                'required',
                'integer',
                'exists:courses,id',
                'different:course_id',
            ],
        ]);

        $course = Course::findOrFail(
            $validated['course_id']
        );

        $prerequisiteCourse = Course::findOrFail(
            $validated['prerequisite_course_id']
        );

        if (
            $course->department !==
            $prerequisiteCourse->department
        ) {
            return response()->json([
                'success' => false,
                'message' =>
                    'Prerequisite course must belong to the same department.',
            ], 422);
        }

        $exists = Prerequisite::where([
            'course_id' =>
                $validated['course_id'],

            'prerequisite_course_id' =>
                $validated['prerequisite_course_id'],
        ])->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' =>
                    'This prerequisite already exists.',
            ], 422);
        }

        $prerequisite = Prerequisite::create(
            $validated
        );

        $prerequisite->load([
            'course',
            'prerequisiteCourse',
        ]);

        return response()->json([
            'success' => true,
            'message' =>
                'Prerequisite added successfully.',
            'prerequisite' => $prerequisite,
        ], 201);
    }

    public function show(Prerequisite $prerequisite)
    {
        $prerequisite->load([
            'course',
            'prerequisiteCourse',
        ]);

        return response()->json([
            'success' => true,
            'prerequisite' => $prerequisite,
        ]);
    }

    public function update(
        Request $request,
        Prerequisite $prerequisite
    ) {
        $validated = $request->validate([
            'course_id' => [
                'required',
                'integer',
                'exists:courses,id',
            ],

            'prerequisite_course_id' => [
                'required',
                'integer',
                'exists:courses,id',
                'different:course_id',
            ],
        ]);

        $course = Course::findOrFail(
            $validated['course_id']
        );

        $prerequisiteCourse = Course::findOrFail(
            $validated['prerequisite_course_id']
        );

        if (
            $course->department !==
            $prerequisiteCourse->department
        ) {
            return response()->json([
                'success' => false,
                'message' =>
                    'Prerequisite course must belong to the same department.',
            ], 422);
        }

        $exists = Prerequisite::where(
            'course_id',
            $validated['course_id']
        )
            ->where(
                'prerequisite_course_id',
                $validated['prerequisite_course_id']
            )
            ->where(
                'id',
                '!=',
                $prerequisite->id
            )
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' =>
                    'This prerequisite already exists.',
            ], 422);
        }

        $prerequisite->update($validated);

        $prerequisite->load([
            'course',
            'prerequisiteCourse',
        ]);

        return response()->json([
            'success' => true,
            'message' =>
                'Prerequisite updated successfully.',
            'prerequisite' => $prerequisite,
        ]);
    }

    public function destroy(
        Prerequisite $prerequisite
    ) {
        $prerequisite->delete();

        return response()->json([
            'success' => true,
            'message' =>
                'Prerequisite deleted successfully.',
        ]);
    }
}
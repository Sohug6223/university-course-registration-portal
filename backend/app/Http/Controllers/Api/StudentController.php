<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\Section;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        $query = Student::with([
            'user:id,user_id,name,email',
            'batch:id,department,batch_number,start_year,current_semester,status',
            'section:id,batch_id,section_name,capacity,enrolled,status',
        ])->latest();

        if ($request->filled('department')) {
            $query->where('department', $request->department);
        }

        if ($request->filled('batch_id')) {
            $query->where('batch_id', $request->batch_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return response()->json([
            'success' => true,
            'students' => $query->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => [
                'required',
                'string',
                'max:50',
                'unique:users,user_id',
            ],

            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'email' => [
                'required',
                'email',
                'max:255',
                'unique:users,email',
            ],

            'password' => [
                'required',
                'string',
                'min:6',
            ],

            'department' => [
                'required',
                'string',
                Rule::exists('departments', 'code'),
            ],

            'batch_id' => [
                'required',
                'integer',
                Rule::exists('batches', 'id'),
            ],

            'section_id' => [
                'nullable',
                'integer',
                Rule::exists('sections', 'id'),
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

            'status' => [
                'nullable',
                Rule::in(['Active', 'Inactive']),
            ],
        ]);

        $batch = Batch::findOrFail($validated['batch_id']);

        // Department must match batch department
        if ($batch->department !== $validated['department']) {
            return response()->json([
                'success' => false,
                'message' => 'Selected batch does not belong to the selected department.',
            ], 422);
        }

        // Semester must match batch current semester
        if ($batch->current_semester !== $validated['semester']) {
            return response()->json([
                'success' => false,
                'message' => 'Student semester must match the batch current semester.',
            ], 422);
        }

        $section = null;

        if (!empty($validated['section_id'])) {
            $section = Section::findOrFail($validated['section_id']);

            // Section must belong to selected batch
            if ($section->batch_id != $batch->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Selected section does not belong to the selected batch.',
                ], 422);
            }

            // Check section capacity
            if ($section->enrolled >= $section->capacity) {
                return response()->json([
                    'success' => false,
                    'message' => 'Selected section is already full.',
                ], 422);
            }
        }

        $student = DB::transaction(function () use ($validated, $section) {

            $user = User::create([
                'user_id' => $validated['user_id'],
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => 'student',
            ]);

            $student = Student::create([
                'user_id' => $user->id,
                'department' => $validated['department'],
                'batch_id' => $validated['batch_id'],
                'section_id' => $validated['section_id'] ?? null,
                'semester' => $validated['semester'],
                'status' => $validated['status'] ?? 'Active',
            ]);

            if ($section) {
                $section->increment('enrolled');
            }

            return $student;
        });

        $student->load([
            'user:id,user_id,name,email',
            'batch',
            'section',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Student created successfully.',
            'student' => $student,
        ], 201);
    }

    public function show(Student $student)
    {
        $student->load([
            'user:id,user_id,name,email',
            'batch',
            'section',
        ]);

        return response()->json([
            'success' => true,
            'student' => $student,
        ]);
    }

    public function update(Request $request, Student $student)
    {
        $validated = $request->validate([
            'user_id' => [
                'required',
                'string',
                'max:50',
                Rule::unique('users', 'user_id')->ignore($student->user_id),
            ],

            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($student->user_id),
            ],

            'password' => [
                'nullable',
                'string',
                'min:6',
            ],

            'department' => [
                'required',
                'string',
                Rule::exists('departments', 'code'),
            ],

            'batch_id' => [
                'required',
                'integer',
                Rule::exists('batches', 'id'),
            ],

            'section_id' => [
                'nullable',
                'integer',
                Rule::exists('sections', 'id'),
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

            'status' => [
                'required',
                Rule::in(['Active', 'Inactive']),
            ],
        ]);

        $batch = Batch::findOrFail($validated['batch_id']);

        // Department must match batch department
        if ($batch->department !== $validated['department']) {
            return response()->json([
                'success' => false,
                'message' => 'Selected batch does not belong to the selected department.',
            ], 422);
        }

        // Semester must match batch current semester
        if ($batch->current_semester !== $validated['semester']) {
            return response()->json([
                'success' => false,
                'message' => 'Student semester must match the batch current semester.',
            ], 422);
        }

        $newSection = null;

        if (!empty($validated['section_id'])) {
            $newSection = Section::findOrFail($validated['section_id']);

            // Section must belong to selected batch
            if ($newSection->batch_id != $batch->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Selected section does not belong to the selected batch.',
                ], 422);
            }

            // Check capacity only if student is moving to another section
            if (
                $newSection->id != $student->section_id &&
                $newSection->enrolled >= $newSection->capacity
            ) {
                return response()->json([
                    'success' => false,
                    'message' => 'Selected section is already full.',
                ], 422);
            }
        }

        DB::transaction(function () use ($student, $validated, $newSection) {

            $oldSection = $student->section;

            // Update user information
            $student->user->update([
                'user_id' => $validated['user_id'],
                'name' => $validated['name'],
                'email' => $validated['email'],
            ]);

            // Update password only when provided
            if (!empty($validated['password'])) {
                $student->user->update([
                    'password' => Hash::make($validated['password']),
                ]);
            }

            // Update student information
            $student->update([
                'department' => $validated['department'],
                'batch_id' => $validated['batch_id'],
                'section_id' => $validated['section_id'] ?? null,
                'semester' => $validated['semester'],
                'status' => $validated['status'],
            ]);

            // Remove student from old section
            if (
                $oldSection &&
                (!$newSection || $oldSection->id != $newSection->id)
            ) {
                if ($oldSection->enrolled > 0) {
                    $oldSection->decrement('enrolled');
                }
            }

            // Add student to new section
            if (
                $newSection &&
                (!$oldSection || $oldSection->id != $newSection->id)
            ) {
                $newSection->increment('enrolled');
            }
        });

        $student->load([
            'user:id,user_id,name,email',
            'batch',
            'section',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Student updated successfully.',
            'student' => $student,
        ]);
    }

    public function destroy(Student $student)
    {
        DB::transaction(function () use ($student) {

            $section = $student->section;
            $user = $student->user;

            $student->delete();

            // Decrease section enrollment
            if ($section && $section->enrolled > 0) {
                $section->decrement('enrolled');
            }

            // Delete associated user
            if ($user) {
                $user->delete();
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Student deleted successfully.',
        ]);
    }
}
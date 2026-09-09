<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\Section;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SectionController extends Controller
{
    /*
    
     INDEX
    
    */

    public function index(Request $request)
    {
        $query = Section::with('batch');

        /*
        
         Search
        
        */

        if ($request->filled('search')) {
            $search = trim($request->search);

            $query->where(function ($q) use ($search) {

                $q->where(
                    'section_name',
                    'like',
                    "%{$search}%"
                )

                ->orWhereHas(
                    'batch',
                    function ($batchQuery) use ($search) {

                        $batchQuery
                            ->where(
                                'batch_number',
                                'like',
                                "%{$search}%"
                            )
                            ->orWhere(
                                'department',
                                'like',
                                "%{$search}%"
                            );
                    }
                );
            });
        }

        /*
        
         Batch Filter
        
        */

        if ($request->filled('batch_id')) {
            $query->where(
                'batch_id',
                $request->batch_id
            );
        }

        /*
        
         Department Filter
        
        */

        if ($request->filled('department')) {

            $query->whereHas(
                'batch',
                function ($batchQuery) use ($request) {

                    $batchQuery->where(
                        'department',
                        $request->department
                    );
                }
            );
        }

        /*
        
         Status Filter
        
        */

        if ($request->filled('status')) {
            $query->where(
                'status',
                $request->status
            );
        }

        /*
        
         Get Sections
        
        */

        $sections = $query
            ->orderBy('batch_id')
            ->orderBy('section_name')
            ->get();

        /*
        
         Calculate Unique Active Students
        
        */

        foreach ($sections as $section) {

            $section->enrolled =
                DB::table('enrollments')
                    ->where(
                        'section_id',
                        $section->id
                    )
                    ->where(
                        'status',
                        'Active'
                    )
                    ->distinct()
                    ->count(
                        'student_id'
                    );
        }

        return response()->json([
            'success' => true,
            'sections' => $sections,
        ]);
    }

    /*
    
     STORE
    
    */

    public function store(Request $request)
    {
        $validated = $request->validate([

            'batch_id' => [
                'required',
                'integer',
                Rule::exists('batches', 'id'),
            ],

            'section_name' => [
                'required',
                'string',
                'max:20',
            ],

            'capacity' => [
                'required',
                'integer',
                'min:1',
                'max:1000',
            ],

            'status' => [
                'nullable',
                Rule::in([
                    'Active',
                    'Inactive',
                ]),
            ],

            'description' => [
                'nullable',
                'string',
            ],
        ]);

        $batch = Batch::findOrFail(
            $validated['batch_id']
        );

        $sectionName = strtoupper(
            trim(
                $validated['section_name']
            )
        );

        /*
        
         Duplicate Section
        
        */

        $exists = Section::where(
            'batch_id',
            $validated['batch_id']
        )
            ->whereRaw(
                'UPPER(section_name) = ?',
                [$sectionName]
            )
            ->exists();

        if ($exists) {

            return response()->json([
                'success' => false,
                'message' =>
                    "Section {$sectionName} already exists for this batch.",
            ], 422);
        }

        $section = Section::create([

            'batch_id' =>
                $batch->id,

            'section_name' =>
                $sectionName,

            'capacity' =>
                $validated['capacity'],

            'enrolled' =>
                0,

            'status' =>
                $validated['status'] ?? 'Active',

            'description' =>
                $validated['description'] ?? null,
        ]);

        $section->load('batch');

        return response()->json([
            'success' => true,
            'message' =>
                'Section created successfully.',
            'section' =>
                $section,
        ], 201);
    }

    /*
    
     SHOW
    
    */

    public function show(Section $section)
    {
        $section->load('batch');

        $section->enrolled =
            DB::table('enrollments')
                ->where(
                    'section_id',
                    $section->id
                )
                ->where(
                    'status',
                    'Active'
                )
                ->distinct()
                ->count(
                    'student_id'
                );

        return response()->json([
            'success' => true,
            'section' => $section,
        ]);
    }

    /*
    
     UPDATE
    
    */

    public function update(
        Request $request,
        Section $section
    ) {
        $validated = $request->validate([

            'batch_id' => [
                'required',
                'integer',
                Rule::exists('batches', 'id'),
            ],

            'section_name' => [
                'required',
                'string',
                'max:20',
            ],

            'capacity' => [
                'required',
                'integer',
                'min:1',
                'max:1000',
            ],

            'status' => [
                'required',
                Rule::in([
                    'Active',
                    'Inactive',
                ]),
            ],

            'description' => [
                'nullable',
                'string',
            ],
        ]);

        $batch = Batch::findOrFail(
            $validated['batch_id']
        );

        $sectionName = strtoupper(
            trim(
                $validated['section_name']
            )
        );

        /*
        
         Duplicate Check
        
        */

        $exists = Section::where(
            'batch_id',
            $validated['batch_id']
        )
            ->whereRaw(
                'UPPER(section_name) = ?',
                [$sectionName]
            )
            ->where(
                'id',
                '!=',
                $section->id
            )
            ->exists();

        if ($exists) {

            return response()->json([
                'success' => false,
                'message' =>
                    "Section {$sectionName} already exists for this batch.",
            ], 422);
        }

        /*
        
         Actual Enrolled Students
        
        */

        $enrolled =
            DB::table('enrollments')
                ->where(
                    'section_id',
                    $section->id
                )
                ->where(
                    'status',
                    'Active'
                )
                ->distinct()
                ->count(
                    'student_id'
                );

        $capacity =
            $validated['capacity'];

        /*
        
         Capacity Check
        
        */

        if ($enrolled > $capacity) {

            return response()->json([
                'success' => false,
                'message' =>
                    "Capacity cannot be less than the current enrolled students ({$enrolled}).",
            ], 422);
        }

        /*
        
         Update
        
        */

        $section->update([

            'batch_id' =>
                $batch->id,

            'section_name' =>
                $sectionName,

            'capacity' =>
                $capacity,

            'enrolled' =>
                $enrolled,

            'status' =>
                $validated['status'],

            'description' =>
                $validated['description'] ?? null,
        ]);

        $section->load('batch');

        return response()->json([
            'success' => true,
            'message' =>
                'Section updated successfully.',
            'section' =>
                $section,
        ]);
    }

    /*
    
     DESTROY
    
    */

    public function destroy(Section $section)
    {
        /*
        
         Don't Delete If Students Are Assigned
        
        */

        if ($section->students()->exists()) {

            return response()->json([
                'success' => false,
                'message' =>
                    'This section cannot be deleted because students are assigned to it.',
            ], 422);
        }

        /*
        
         Don't Delete If Enrollment Exists
        
        */

        if (
            DB::table('enrollments')
                ->where(
                    'section_id',
                    $section->id
                )
                ->exists()
        ) {
            return response()->json([
                'success' => false,
                'message' =>
                    'This section cannot be deleted because enrollment records exist.',
            ], 422);
        }

        $section->delete();

        return response()->json([
            'success' => true,
            'message' =>
                'Section deleted successfully.',
        ]);
    }
}
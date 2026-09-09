<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class BatchController extends Controller
{
    private array $semesters = [
        '1st',
        '2nd',
        '3rd',
        '4th',
        '5th',
        '6th',
        '7th',
        '8th',
    ];

    /*
    
    | INDEX
    
    */

    public function index(Request $request)
    {
        $query = Batch::withCount([
            'students',
            'sections',
        ]);

        if ($request->filled('department')) {
            $query->where('department', $request->department);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $batches = $query
            ->orderBy('batch_number', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'batches' => $batches,
        ]);
    }

    /*
    
    | STORE
    
    */

    public function store(Request $request)
    {
        $validated = $request->validate([
            'department' => [
                'required',
                'string',
                Rule::exists('departments', 'code'),
            ],

            'batch_number' => [
                'required',
                'integer',
                'min:1',
            ],

            'start_year' => [
                'nullable',
                'digits:4',
            ],

            'current_semester' => [
                'required',
                Rule::in($this->semesters),
            ],

            'advisor_name' => [
                'nullable',
                'string',
                'max:150',
            ],

            'status' => [
                'nullable',
                Rule::in([
                    'Active',
                    'Inactive',
                ]),
            ],
        ]);

        $exists = Batch::where('department', $validated['department'])
            ->where('batch_number', $validated['batch_number'])
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'This batch already exists for the selected department.',
            ], 422);
        }

        $validated['status'] = $validated['status'] ?? 'Active';

        $batch = Batch::create($validated);

        $batch->loadCount([
            'students',
            'sections',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Batch created successfully.',
            'batch' => $batch,
        ], 201);
    }

    /*
    
    | SHOW
    
    */

    public function show(Batch $batch)
    {
        $batch->loadCount([
            'students',
            'sections',
        ]);

        $batch->load([
            'students.user',
            'sections',
        ]);

        return response()->json([
            'success' => true,
            'batch' => $batch,
        ]);
    }

    /*
    
    | UPDATE
    
    */

    public function update(Request $request, Batch $batch)
    {
        /*
        
        | Graduated batch cannot be edited
        
        */

        if ($batch->status === 'Graduated') {
            return response()->json([
                'success' => false,
                'message' => 'A graduated batch cannot be edited.',
            ], 422);
        }

        $validated = $request->validate([
            'department' => [
                'required',
                'string',
                Rule::exists('departments', 'code'),
            ],

            'batch_number' => [
                'required',
                'integer',
                'min:1',
            ],

            'start_year' => [
                'nullable',
                'digits:4',
            ],

            'current_semester' => [
                'required',
                Rule::in($this->semesters),
            ],

            'advisor_name' => [
                'nullable',
                'string',
                'max:150',
            ],

            'status' => [
                'required',
                Rule::in([
                    'Active',
                    'Inactive',
                ]),
            ],
        ]);

        /*
        
        | Prevent duplicate batch
        
        */

        $exists = Batch::where('department', $validated['department'])
            ->where('batch_number', $validated['batch_number'])
            ->where('id', '!=', $batch->id)
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'This batch already exists for the selected department.',
            ], 422);
        }

        DB::transaction(function () use ($batch, $validated) {
            $oldDepartment = $batch->department;
            $oldSemester = $batch->current_semester;

            $batch->update($validated);

            /*
            
            | Synchronize students
            
            */

            if (
                $oldDepartment !== $validated['department'] ||
                $oldSemester !== $validated['current_semester']
            ) {
                Student::where('batch_id', $batch->id)
                    ->update([
                        'department' => $validated['department'],
                        'semester' => $validated['current_semester'],
                    ]);
            }
        });

        $batch = $batch->fresh();

        $batch->loadCount([
            'students',
            'sections',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Batch updated successfully.',
            'batch' => $batch,
        ]);
    }

    /*
    
    | ADVANCE SEMESTER
    
    */

    public function advanceSemester(Batch $batch)
    {
        /*
        
        | Graduated batch
        
        */

        if ($batch->status === 'Graduated') {
            return response()->json([
                'success' => false,
                'message' => 'This batch has already graduated.',
            ], 422);
        }

        /*
        
        | Inactive batch
        
        */

        if ($batch->status !== 'Active') {
            return response()->json([
                'success' => false,
                'message' => 'An inactive batch cannot be advanced.',
            ], 422);
        }

        /*
        
        | Find current semester
        
        */

        $currentIndex = array_search(
            $batch->current_semester,
            $this->semesters,
            true
        );

        if ($currentIndex === false) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid current semester.',
            ], 422);
        }

        /*
        
        | 8th semester cannot advance
        
        */

        if ($currentIndex === count($this->semesters) - 1) {
            return response()->json([
                'success' => false,
                'message' =>
                'This batch is already in 8th semester. Graduate the batch after completing 8th semester.',
            ], 422);
        }

        $nextSemester = $this->semesters[$currentIndex + 1];

        /*
        
        | Update batch + students together
        
        */

        DB::transaction(function () use ($batch, $nextSemester) {
            $batch->update([
                'current_semester' => $nextSemester,
            ]);

            Student::where('batch_id', $batch->id)
                ->update([
                    'semester' => $nextSemester,
                ]);
        });

        $batch = $batch->fresh();

        $batch->loadCount([
            'students',
            'sections',
        ]);

        return response()->json([
            'success' => true,
            'message' =>
            "Batch {$batch->batch_number} advanced to {$nextSemester} semester.",
            'batch' => $batch,
        ]);
    }

    /*
    
    | GRADUATE
    
    */

    public function graduate(Batch $batch)
    {
        /*
        
        | Already graduated
        
        */

        if ($batch->status === 'Graduated') {
            return response()->json([
                'success' => false,
                'message' => 'This batch has already been graduated.',
            ], 422);
        }

        /*
        
        | Must be 8th semester
        
        */

        if ($batch->current_semester !== '8th') {
            return response()->json([
                'success' => false,
                'message' =>
                'A batch can only be graduated after reaching 8th semester.',
            ], 422);
        }

        /*
        
        | Must be active
        
        */

        if ($batch->status !== 'Active') {
            return response()->json([
                'success' => false,
                'message' => 'Only an active batch can be graduated.',
            ], 422);
        }

        DB::transaction(function () use ($batch) {
            $batch->update([
                'status' => 'Graduated',
            ]);
        });

        $batch = $batch->fresh();

        $batch->loadCount([
            'students',
            'sections',
        ]);

        return response()->json([
            'success' => true,
            'message' =>
            "Batch {$batch->batch_number} has been graduated successfully.",
            'batch' => $batch,
        ]);
    }

    /*

| RESTORE GRADUATED BATCH

*/

    public function restore(Batch $batch)
    {
        /*
    
    | Must be graduated
    
    */

        if ($batch->status !== 'Graduated') {
            return response()->json([
                'success' => false,
                'message' => 'Only a graduated batch can be restored.',
            ], 422);
        }

        /*
    
    | Restore batch + students together
    
    */

        DB::transaction(function () use ($batch) {

            /*
        
        | Restore batch
        
        */

            $batch->update([
                'status' => 'Active',
                'current_semester' => '8th',
            ]);

            /*
        
        | Restore students to 8th semester
        
        */

            Student::where('batch_id', $batch->id)
                ->update([
                    'semester' => '8th',
                ]);
        });

        /*
    
    | Fresh batch data
    
    */

        $batch = $batch->fresh();

        $batch->loadCount([
            'students',
            'sections',
        ]);

        return response()->json([
            'success' => true,
            'message' =>
            "Batch {$batch->batch_number} has been restored successfully.",
            'batch' => $batch,
        ]);
    }

    /*
    
     DESTROY
    
    */

    public function destroy(Batch $batch)
    {
        /*
        
         Graduated batch
        
        */

        if ($batch->status === 'Graduated') {
            return response()->json([
                'success' => false,
                'message' => 'A graduated batch cannot be deleted.',
            ], 422);
        }

        /*
        
         Students check
        
        */

        if ($batch->students()->exists()) {
            return response()->json([
                'success' => false,
                'message' =>
                'This batch cannot be deleted because students are assigned to it.',
            ], 422);
        }

        /*
        
         Sections check
        
        */

        if ($batch->sections()->exists()) {
            return response()->json([
                'success' => false,
                'message' =>
                'This batch cannot be deleted because sections are assigned to it.',
            ], 422);
        }

        $batch->delete();

        return response()->json([
            'success' => true,
            'message' => 'Batch deleted successfully.',
        ]);
    }
}

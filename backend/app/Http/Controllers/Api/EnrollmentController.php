<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Section;
use App\Models\Student;
use App\Models\Result;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class EnrollmentController extends Controller
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
    
     Enrollment List
    
    */

    public function index(Request $request)
    {
        $query = Enrollment::with([
            'student.user',
            'student.batch',
            'student.section',

            /*
            
             Course Department
            
             courses.department is now a relationship.
            */
            'course.department:id,code,name',

            'section',
        ])->latest();

        if ($request->filled('department')) {
            $query->whereHas('student', function ($q) use ($request) {
                $q->where('department', $request->department);
            });
        }

        if ($request->filled('batch_id')) {
            $query->whereHas('student', function ($q) use ($request) {
                $q->where('batch_id', $request->batch_id);
            });
        }

        if ($request->filled('section_id')) {
            $query->where('section_id', $request->section_id);
        }

        if ($request->filled('semester')) {
            $query->where('semester', $request->semester);
        }

        if ($request->filled('course_id')) {
            $query->where('course_id', $request->course_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $enrollments = $query->get();

        $sectionIds = $enrollments
            ->pluck('section_id')
            ->filter()
            ->unique();

        foreach ($sectionIds as $sectionId) {
            $this->syncSectionEnrolled((int) $sectionId);
        }

        $this->attachResultStatus($enrollments);

        return response()->json([
            'success' => true,
            'enrollments' => $enrollments,
        ]);
    }

    /*
    
     Student Info
    
    */

    public function studentInfo(Request $request)
    {
        $validated = $request->validate([
            'student_id' => [
                'required',
                'string',
            ],
        ]);

        $student = Student::with([
            'user',
            'batch',
            'section',
        ])
            ->whereHas('user', function ($query) use ($validated) {
                $query->where(
                    'user_id',
                    $validated['student_id']
                );
            })
            ->first();

        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Student not found.',
            ], 404);
        }

        if (!$student->batch) {
            return response()->json([
                'success' => false,
                'message' => 'Student is not assigned to a batch.',
            ], 422);
        }

        $currentSemester =
            $student->batch->current_semester;

        /*
        
         Admin normal Add Enrollment = NEXT semester
        
        */

        $enrollmentSemester =
            $this->nextSemester($currentSemester);

        if (!$enrollmentSemester) {
            return response()->json([
                'success' => false,
                'message' =>
                    'This student is currently in the 8th semester. No next semester is available.',
            ], 422);
        }

        /*
        
         Find student's department
        
        
         students.department stores department code.
         courses.department_id stores department ID.
        
        */

        $department = Department::where(
            'code',
            $student->department
        )->first();

        if (!$department) {
            return response()->json([
                'success' => false,
                'message' =>
                    'Student department could not be found.',
            ], 422);
        }

        /*
        
         Load courses using department_id
        
        */

        $courses = Course::with([
            'department:id,code,name',
        ])
            ->where(
                'department_id',
                $department->id
            )
            ->where(
                'semester',
                $enrollmentSemester
            )
            ->where(
                'status',
                '!=',
                'Inactive'
            )
            ->orderBy('course_code')
            ->get();

        $sections = Section::where(
            'batch_id',
            $student->batch_id
        )
            ->where(
                'status',
                'Active'
            )
            ->orderBy('section_name')
            ->get();

        foreach ($sections as $section) {
            $section->enrolled =
                $this->getSectionEnrolled(
                    $section->id
                );
        }

        return response()->json([
            'success' => true,

            'student' => [
                'id' => $student->id,
                'user_id' => $student->user->user_id,
                'name' => $student->user->name,
                'email' => $student->user->email ?? null,

                /*
                
                 Keep student department as code
                
                */
                'department' => $student->department,

                'batch' => $student->batch->batch_number,
                'batch_id' => $student->batch_id,
                'current_semester' => $currentSemester,
                'section' => $student->section?->section_name,
            ],

            'enrollment_semester' =>
                $enrollmentSemester,

            'courses' => $courses,

            'sections' => $sections,
        ]);
    }

    /*
    
     Historical Restore Options
    
    */

    public function historicalOptions(Request $request)
    {
        $validated = $request->validate([
            'student_id' => [
                'required',
                'integer',
                'exists:students,id',
            ],

            'semester' => [
                'required',
                Rule::in($this->semesters),
            ],
        ]);

        $student = Student::with([
            'batch',
            'section',
        ])->findOrFail(
            $validated['student_id']
        );

        /*
        
         Find student's department
        
        */

        $department = Department::where(
            'code',
            $student->department
        )->first();

        if (!$department) {
            return response()->json([
                'success' => false,
                'message' =>
                    'Student department could not be found.',
            ], 422);
        }

        /*
        
         Load historical courses using department_id
        
        */

        $courses = Course::with([
            'department:id,code,name',
        ])
            ->where(
                'department_id',
                $department->id
            )
            ->where(
                'semester',
                $validated['semester']
            )
            ->where(
                'status',
                '!=',
                'Inactive'
            )
            ->orderBy('course_code')
            ->get();

        $sections = Section::where(
            'batch_id',
            $student->batch_id
        )
            ->orderBy('section_name')
            ->get();

        $enrolledCourseIds = Enrollment::where(
            'student_id',
            $student->id
        )
            ->where(
                'semester',
                $validated['semester']
            )
            ->pluck('course_id');

        $courses->each(function ($course) use ($enrolledCourseIds) {
            $course->already_enrolled =
                $enrolledCourseIds->contains(
                    $course->id
                );
        });

        return response()->json([
            'success' => true,
            'courses' => $courses,
            'sections' => $sections,
        ]);
    }

    /*
    
     Student Enrollment History
    
    */

    public function studentHistory(Request $request)
    {
        $validated = $request->validate([
            'student_id' => [
                'required',
                'string',
            ],
        ]);

        $student = Student::with([
            'user:id,user_id,name,email',
            'batch:id,department,batch_number,start_year,current_semester,status',
            'section:id,batch_id,section_name,capacity,enrolled,status',
        ])
            ->whereHas('user', function ($query) use ($validated) {
                $query->where(
                    'user_id',
                    $validated['student_id']
                );
            })
            ->first();

        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Student not found.',
            ], 404);
        }

        /*
        
         Enrollment History
        
        */

        $enrollments = Enrollment::with([
            'course' => function ($query) {
                $query->select([
                    'id',
                    'course_code',
                    'course_name',
                    'credit',
                    'department_id',
                    'semester',
                ])->with([
                    'department:id,code,name',
                ]);
            },

            'section:id,batch_id,section_name,capacity,enrolled,status',
        ])
            ->where(
                'student_id',
                $student->id
            )
            ->orderByRaw("
                CASE semester
                    WHEN '1st' THEN 1
                    WHEN '2nd' THEN 2
                    WHEN '3rd' THEN 3
                    WHEN '4th' THEN 4
                    WHEN '5th' THEN 5
                    WHEN '6th' THEN 6
                    WHEN '7th' THEN 7
                    WHEN '8th' THEN 8
                    ELSE 99
                END
            ")
            ->orderBy('course_id')
            ->get();

        $this->attachResultStatus($enrollments);

        /*
        
         Enrollment Keys
        
        */

        $enrollmentKeys = $enrollments
            ->map(function ($enrollment) {
                return
                    $enrollment->course_id .
                    '|' .
                    $enrollment->semester;
            })
            ->flip();

        /*
        
         Results
        
        */

        $results = Result::with([
            'course' => function ($query) {
                $query->select([
                    'id',
                    'course_code',
                    'course_name',
                    'credit',
                    'department_id',
                    'semester',
                ])->with([
                    'department:id,code,name',
                ]);
            },
        ])
            ->where(
                'student_id',
                $student->id
            )
            ->orderByRaw("
                CASE semester
                    WHEN '1st' THEN 1
                    WHEN '2nd' THEN 2
                    WHEN '3rd' THEN 3
                    WHEN '4th' THEN 4
                    WHEN '5th' THEN 5
                    WHEN '6th' THEN 6
                    WHEN '7th' THEN 7
                    WHEN '8th' THEN 8
                    ELSE 99
                END
            ")
            ->orderBy('course_id')
            ->get();

        /*
        
         Find orphan results
        
        */

        $orphanResults = $results
            ->filter(function ($result) use ($enrollmentKeys) {
                $key =
                    $result->course_id .
                    '|' .
                    $result->semester;

                return !$enrollmentKeys->has($key);
            })
            ->values()
            ->map(function ($result) {
                return [
                    'id' => $result->id,
                    'student_id' => $result->student_id,
                    'course_id' => $result->course_id,
                    'semester' => $result->semester,
                    'marks' => $result->marks,
                    'grade' => $result->grade,
                    'course' => $result->course,
                ];
            });

        return response()->json([
            'success' => true,

            'student' => [
                'id' => $student->id,
                'user_id' => $student->user->user_id,
                'name' => $student->user->name,
                'email' => $student->user->email,

                /*
                
                 Student department remains code
                
                */
                'department' => $student->department,

                'batch' => $student->batch,
                'section' => $student->section,

                'current_semester' =>
                    $student->batch?->current_semester,
            ],

            'enrollments' => $enrollments,

            'orphan_results' => $orphanResults,
        ]);
    }

    /*
    
     Create Enrollment
    
    */

    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_id' => [
                'required',
                'exists:students,id',
            ],

            'semester' => [
                'required',
                Rule::in($this->semesters),
            ],

            'courses' => [
                'required',
                'array',
                'min:1',
            ],

            'courses.*.course_id' => [
                'required',
                'integer',
                'exists:courses,id',
            ],

            'courses.*.section_id' => [
                'required',
                'integer',
                'exists:sections,id',
            ],
        ]);

        $student = Student::with('batch')
            ->findOrFail(
                $validated['student_id']
            );

        if (!$student->batch) {
            return response()->json([
                'success' => false,
                'message' =>
                    'Student is not assigned to a batch.',
            ], 422);
        }

        /*
        
         Find Student Department
        
        */

        $department = Department::where(
            'code',
            $student->department
        )->first();

        if (!$department) {
            return response()->json([
                'success' => false,
                'message' =>
                    'Student department could not be found.',
            ], 422);
        }

        $courseIds = collect(
            $validated['courses']
        )->pluck('course_id');

        if ($courseIds->duplicates()->isNotEmpty()) {
            return response()->json([
                'success' => false,
                'message' =>
                    'The same course cannot be selected more than once.',
            ], 422);
        }


        DB::transaction(function () use (
            $validated,
            $student,
            $department
        ) {
            foreach ($validated['courses'] as $item) {

                $course = Course::findOrFail(
                    $item['course_id']
                );

                $section = Section::with('batch')
                    ->lockForUpdate()
                    ->findOrFail(
                        $item['section_id']
                    );

                /*
                
                 Department validation
                
                */

                if (
                    (int) $course->department_id !==
                    (int) $department->id
                ) {
                    abort(
                        422,
                        'Selected course does not belong to the student department.'
                    );
                }

                /*
                
                 Semester validation
                
                */

                if (
                    $course->semester !==
                    $validated['semester']
                ) {
                    abort(
                        422,
                        'Selected course does not belong to the enrollment semester.'
                    );
                }

                /*
                
                 Section batch validation
                
                */

                if (
                    (int) $section->batch_id !==
                    (int) $student->batch_id
                ) {
                    abort(
                        422,
                        'Selected section does not belong to the student batch.'
                    );
                }

                /*
                
                 Existing Enrollment
                
                */

                $existingEnrollment =
                    Enrollment::where(
                        'student_id',
                        $student->id
                    )
                    ->where(
                        'course_id',
                        $course->id
                    )
                    ->where(
                        'semester',
                        $validated['semester']
                    )
                    ->first();

                if ($existingEnrollment) {
                    abort(
                        422,
                        "Student is already enrolled in {$course->course_code}."
                    );
                }

                /*
                
                 Section Capacity
                
                */

                $currentStudents =
                    $this->getSectionEnrolled(
                        $section->id
                    );

                if (
                    $currentStudents >=
                    $section->capacity
                ) {
                    abort(
                        422,
                        "Section {$section->section_name} is full."
                    );
                }

                /*
                
                 Existing Result
                
                */

                $existingResult = Result::where(
                    'student_id',
                    $student->id
                )
                    ->where(
                        'course_id',
                        $course->id
                    )
                    ->where(
                        'semester',
                        $validated['semester']
                    )
                    ->first();

                Enrollment::create([
                    'student_id' =>
                        $student->id,

                    'course_id' =>
                        $course->id,

                    'section_id' =>
                        $section->id,

                    'semester' =>
                        $validated['semester'],

                    'status' =>
                        $existingResult
                            ? 'Completed'
                            : 'Active',
                ]);

                $this->syncSectionEnrolled(
                    $section->id
                );
            }
        });

        return response()->json([
            'success' => true,
            'message' =>
                'Enrollment completed successfully.',
        ], 201);
    }

    /*
    
     Restore Enrollment
    
    */

    public function restore(Request $request)
    {
        $validated = $request->validate([
            'student_id' => [
                'required',
                'integer',
                'exists:students,id',
            ],

            'course_id' => [
                'required',
                'integer',
                'exists:courses,id',
            ],

            'semester' => [
                'required',
                Rule::in($this->semesters),
            ],

            'section_id' => [
                'nullable',
                'integer',
                'exists:sections,id',
            ],

            'status' => [
                'nullable',
                Rule::in([
                    'Active',
                    'Completed',
                    'Dropped',
                ]),
            ],
        ]);

        $student = Student::with([
            'user',
            'batch',
            'section',
        ])->findOrFail(
            $validated['student_id']
        );

        $course = Course::findOrFail(
            $validated['course_id']
        );

        if (!$student->batch) {
            return response()->json([
                'success' => false,
                'message' =>
                    'Student is not assigned to a batch.',
            ], 422);
        }

        /*
        
         Find Student Department
        
        */

        $department = Department::where(
            'code',
            $student->department
        )->first();

        if (!$department) {
            return response()->json([
                'success' => false,
                'message' =>
                    'Student department could not be found.',
            ], 422);
        }

        /*
        
         Course Department Validation
        
        */

        if (
            (int) $course->department_id !==
            (int) $department->id
        ) {
            return response()->json([
                'success' => false,
                'message' =>
                    'This course does not belong to the student department.',
            ], 422);
        }

        /*
        
         Course Semester Validation
        
        */

        if (
            $course->semester !==
            $validated['semester']
        ) {
            return response()->json([
                'success' => false,
                'message' =>
                    'This course does not belong to the selected semester.',
            ], 422);
        }

        /*
        
         Existing Enrollment
        
        */

        $existingEnrollment =
            Enrollment::where(
                'student_id',
                $student->id
            )
            ->where(
                'course_id',
                $course->id
            )
            ->where(
                'semester',
                $validated['semester']
            )
            ->first();

        if ($existingEnrollment) {
            return response()->json([
                'success' => false,
                'message' =>
                    'Enrollment already exists for this course and semester.',
            ], 422);
        }

        /*
        
         Existing Result
        
        */

        $result = Result::where(
            'student_id',
            $student->id
        )
            ->where(
                'course_id',
                $course->id
            )
            ->where(
                'semester',
                $validated['semester']
            )
            ->first();

        /*
        
         Determine Section
        
        */

        $section = null;

        if (!empty($validated['section_id'])) {
            $section = Section::where(
                'id',
                $validated['section_id']
            )
                ->where(
                    'batch_id',
                    $student->batch_id
                )
                ->first();

            if (!$section) {
                return response()->json([
                    'success' => false,
                    'message' =>
                        'Selected section does not belong to the student batch.',
                ], 422);
            }
        }

        if (!$section && $student->section_id) {
            $section = Section::where(
                'id',
                $student->section_id
            )
                ->where(
                    'batch_id',
                    $student->batch_id
                )
                ->first();
        }

        if (!$section) {
            $section = Section::where(
                'batch_id',
                $student->batch_id
            )
                ->orderBy('section_name')
                ->first();
        }

        if (!$section) {
            return response()->json([
                'success' => false,
                'message' =>
                    'No section is available for this student batch.',
            ], 422);
        }

        /*
        
         Determine Status
        
        */

        $status = $result
            ? 'Completed'
            : ($validated['status'] ?? 'Completed');

        /*
        
         Capacity
        
        */

        if ($status === 'Active') {
            $activeStudents =
                $this->getSectionEnrolled(
                    $section->id
                );

            if (
                $activeStudents >=
                $section->capacity
            ) {
                return response()->json([
                    'success' => false,
                    'message' =>
                        "Section {$section->section_name} is full.",
                ], 422);
            }
        }

        /*
        
         Create Historical Enrollment
        
        */

        $enrollment = DB::transaction(
            function () use (
                $student,
                $course,
                $section,
                $validated,
                $status
            ) {
                $enrollment =
                    Enrollment::create([
                        'student_id' =>
                            $student->id,

                        'course_id' =>
                            $course->id,

                        'section_id' =>
                            $section->id,

                        'semester' =>
                            $validated['semester'],

                        'status' =>
                            $status,
                    ]);

                $this->syncSectionEnrolled(
                    $section->id
                );

                return $enrollment;
            }
        );

        /*
        
         Load Response Relationships
        
        */

        $enrollment->load([
            'student.user',
            'student.batch',
            'student.section',

            'course' => function ($query) {
                $query->with([
                    'department:id,code,name',
                ]);
            },

            'section',
        ]);

        /*
        
         Result Status
        
        */

        if ($result) {
            $grade = strtoupper(
                trim((string) $result->grade)
            );

            $enrollment->display_status =
                $grade === 'F'
                    ? 'Failed'
                    : 'Completed';

            $enrollment->result = [
                'id' =>
                    $result->id,

                'marks' =>
                    $result->marks,

                'grade' =>
                    $result->grade,
            ];
        } else {
            $enrollment->display_status =
                $status;

            $enrollment->result = null;
        }

        return response()->json([
            'success' => true,

            'message' =>
                'Historical enrollment restored successfully.',

            'enrollment' =>
                $enrollment,
        ], 201);
    }

    /*
    
     Update Enrollment
    
    */

    public function update(
        Request $request,
        Enrollment $enrollment
    ) {
        $validated = $request->validate([
            'section_id' => [
                'required',
                'integer',
                'exists:sections,id',
            ],

            'status' => [
                'required',
                Rule::in([
                    'Active',
                    'Completed',
                    'Dropped',
                ]),
            ],
        ]);

        $enrollment->load([
            'student',
            'section',
        ]);

        $newSection = Section::findOrFail(
            $validated['section_id']
        );

        $oldSection = $enrollment->section;

        if (
            (int) $newSection->batch_id !==
            (int) $enrollment->student->batch_id
        ) {
            return response()->json([
                'success' => false,
                'message' =>
                    'Selected section does not belong to the student batch.',
            ], 422);
        }

        if (
            $validated['status'] === 'Active'
        ) {
            $newSectionStudents =
                $this->getSectionEnrolled(
                    $newSection->id
                );

            $studentAlreadyInNewSection =
                Enrollment::where(
                    'section_id',
                    $newSection->id
                )
                ->where(
                    'student_id',
                    $enrollment->student_id
                )
                ->where(
                    'status',
                    'Active'
                )
                ->where(
                    'id',
                    '!=',
                    $enrollment->id
                )
                ->exists();

            if (
                !$studentAlreadyInNewSection &&
                $newSectionStudents >=
                $newSection->capacity
            ) {
                return response()->json([
                    'success' => false,
                    'message' =>
                        "Section {$newSection->section_name} is full.",
                ], 422);
            }
        }

        DB::transaction(function () use (
            $enrollment,
            $validated,
            $newSection,
            $oldSection
        ) {
            $enrollment->update([
                'section_id' =>
                    $newSection->id,

                'status' =>
                    $validated['status'],
            ]);

            if ($oldSection) {
                $this->syncSectionEnrolled(
                    $oldSection->id
                );
            }

            $this->syncSectionEnrolled(
                $newSection->id
            );
        });

        /*
        
         Reload Updated Enrollment
        
        */

        $updatedEnrollment =
            $enrollment
                ->fresh()
                ->load([
                    'student.user',
                    'student.batch',
                    'student.section',

                    'course' => function ($query) {
                        $query->with([
                            'department:id,code,name',
                        ]);
                    },

                    'section',
                ]);

        $this->attachResultStatus(
            collect([$updatedEnrollment])
        );

        return response()->json([
            'success' => true,
            'message' =>
                'Enrollment updated successfully.',
            'enrollment' =>
                $updatedEnrollment,
        ]);
    }

    /*
    
     Delete Enrollment
    
    */

    public function destroy(Enrollment $enrollment)
    {
        $resultExists = Result::where(
            'student_id',
            $enrollment->student_id
        )
            ->where(
                'course_id',
                $enrollment->course_id
            )
            ->where(
                'semester',
                $enrollment->semester
            )
            ->exists();

        if ($resultExists) {
            return response()->json([
                'success' => false,
                'message' =>
                    'This enrollment cannot be deleted because a result already exists for this course.',
            ], 422);
        }

        $sectionId =
            $enrollment->section_id;

        DB::transaction(function () use (
            $enrollment
        ) {
            $enrollment->delete();
        });

        if ($sectionId) {
            $this->syncSectionEnrolled(
                $sectionId
            );
        }

        return response()->json([
            'success' => true,
            'message' =>
                'Enrollment deleted successfully.',
        ]);
    }

    /*
    
     Attach Result Status
    
    */

    private function attachResultStatus(
        $enrollments
    ): void {
        if ($enrollments->isEmpty()) {
            return;
        }

        $studentIds = $enrollments
            ->pluck('student_id')
            ->filter()
            ->unique();

        $results = Result::whereIn(
            'student_id',
            $studentIds
        )
            ->get([
                'id',
                'student_id',
                'course_id',
                'semester',
                'marks',
                'grade',
            ]);

        $resultMap = $results->keyBy(
            function ($result) {
                return
                    $result->student_id .
                    '|' .
                    $result->course_id .
                    '|' .
                    $result->semester;
            }
        );

        $enrollments->each(
            function ($enrollment) use (
                $resultMap
            ) {
                $key =
                    $enrollment->student_id .
                    '|' .
                    $enrollment->course_id .
                    '|' .
                    $enrollment->semester;

                $result =
                    $resultMap->get($key);

                if ($result) {
                    $grade = strtoupper(
                        trim((string) $result->grade)
                    );

                    $enrollment->display_status =
                        $grade === 'F'
                            ? 'Failed'
                            : 'Completed';

                    $enrollment->result = [
                        'id' =>
                            $result->id,

                        'marks' =>
                            $result->marks,

                        'grade' =>
                            $result->grade,
                    ];
                } else {
                    $enrollment->display_status =
                        $enrollment->status;

                    $enrollment->result = null;
                }
            }
        );
    }

 /*
    
     Get Unique Active Students In Section
    
    */

    private function getSectionEnrolled(
        int $sectionId
    ): int {
        return Enrollment::where(
            'section_id',
            $sectionId
        )
            ->where(
                'status',
                'Active'
            )
            ->distinct(
                'student_id'
            )
            ->count(
                'student_id'
            );
    }

    /*
    
     Sync Section Enrolled Count
    
    */

    private function syncSectionEnrolled(
        int $sectionId
    ): void {
        $count =
            $this->getSectionEnrolled(
                $sectionId
            );

        Section::where(
            'id',
            $sectionId
        )->update([
            'enrolled' => $count,
        ]);
    }

    /*
    
     Next Semester
    
    */

    private function nextSemester(
        string $semester
    ): ?string {
        return match ($semester) {
            '1st' => '2nd',
            '2nd' => '3rd',
            '3rd' => '4th',
            '4th' => '5th',
            '5th' => '6th',
            '6th' => '7th',
            '7th' => '8th',
            '8th' => null,
            default => null,
        };
    }
}
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Prerequisite;
use App\Models\Result;
use App\Models\Section;
use App\Models\Student;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StudentEnrollmentController extends Controller
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
    
     Course Registration Information
    
    */

    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'student') {
            return response()->json([
                'message' => 'Unauthorized.'
            ], 403);
        }

        $student = $user->student()
            ->with([
                'section',
                'user',
                'batch',
            ])
            ->first();

        if (!$student) {
            return response()->json([
                'message' => 'Student academic information not found.'
            ], 404);
        }

        $batch = $student->batch;

        if (!$batch) {
            return response()->json([
                'message' => 'Student academic information not found.'
            ], 404);
        }

        /*
        
         Find Department
        
        
         Student.department stores the department CODE.
         Courses now use department_id.
        
        */

        $department = Department::where(
            'code',
            $student->department
        )->first();

        if (!$department) {
            return response()->json([
                'message' => 'Student department information not found.'
            ], 404);
        }

        /*
        
         Current semester comes ONLY from Batch
        
        */

        $currentSemester = $batch->current_semester;

        $registrationSemester = $this->nextSemester(
            $currentSemester
        );

        /*
        
         8th Semester
        
        */

        if (!$registrationSemester) {
            return response()->json([
                'student' => $this->studentInfo(
                    $student,
                    $batch
                ),

                'registration_semester' => null,

                'registration_open' => false,

                'courses' => [],

                'sections' => [],

                'enrollments' => [],

                'message' =>
                    'Course registration is closed because you are currently in the 8th semester.'
            ]);
        }

        /*
        
         Courses for NEXT semester
        
        */

        $courses = Course::where(
            'department_id',
            $department->id
        )
            ->where(
                'semester',
                $registrationSemester
            )
            ->where(
                'status',
                '!=',
                'Inactive'
            )
            ->orderBy('course_code')
            ->get();

        $courseIds = $courses->pluck('id');

        /*
        
         Existing enrollments
        
        
         Admin-created enrollments must remain visible.
        
        */

        $existingEnrollments = Enrollment::with([
            'course',
            'section'
        ])
            ->where(
                'student_id',
                $student->id
            )
            ->where(
                'semester',
                $registrationSemester
            )
            ->get();

        $enrolledCourseIds = $existingEnrollments
            ->pluck('course_id')
            ->flip();

        /*
        
         Check CURRENT semester completion
        
        */

        $completion = $this->semesterCompletion(
            $student->id,
            $department->id,
            $currentSemester
        );

        /*
        
         Prerequisites
        
        */

        $prerequisites = Prerequisite::with(
            'prerequisiteCourse'
        )
            ->whereIn(
                'course_id',
                $courseIds
            )
            ->get()
            ->groupBy('course_id');

        $passedCourseIds = Result::where(
            'student_id',
            $student->id
        )
            ->whereNotNull('grade')
            ->whereRaw(
                'UPPER(TRIM(grade)) != ?',
                ['F']
            )
            ->pluck('course_id')
            ->unique()
            ->flip();

        /*
        
         Active sections
        
        */

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

        /*
        
         Section capacity
        
        */

        $sectionCounts = Enrollment::whereIn(
            'section_id',
            $sections->pluck('id')
        )
            ->where(
                'status',
                'Active'
            )
            ->select(
                'section_id',
                DB::raw(
                    'COUNT(DISTINCT student_id) as enrolled'
                )
            )
            ->groupBy('section_id')
            ->pluck(
                'enrolled',
                'section_id'
            );

        $sectionData = $sections
            ->map(function ($section) use ($sectionCounts) {

                $enrolled = (int) (
                    $sectionCounts[$section->id] ?? 0
                );

                return [
                    'id' => $section->id,

                    'section_name' =>
                        $section->section_name,

                    'capacity' =>
                        $section->capacity,

                    'available' =>
                        $enrolled < $section->capacity,
                ];
            })
            ->values();

        /*
        
         Prepare courses
        
        */

        $courseData = $courses
            ->map(function ($course) use (
                $prerequisites,
                $passedCourseIds,
                $enrolledCourseIds,
                $completion
            ) {

                $coursePrerequisites =
                    $prerequisites->get(
                        $course->id,
                        collect()
                    );

                $prereqData = $coursePrerequisites
                    ->map(function ($item) use (
                        $passedCourseIds
                    ) {

                        $prereqCourse =
                            $item->prerequisiteCourse;

                        return [
                            'id' =>
                                $prereqCourse->id,

                            'course_code' =>
                                $prereqCourse->course_code,

                            'course_name' =>
                                $prereqCourse->course_name,

                            'passed' =>
                                $passedCourseIds->has(
                                    $prereqCourse->id
                                ),
                        ];
                    })
                    ->values();

                $failedPrerequisite =
                    $prereqData->first(
                        fn ($item) =>
                            !$item['passed']
                    );

                $alreadyEnrolled =
                    $enrolledCourseIds->has(
                        $course->id
                    );

                /*
                
                 Eligibility
                
                
                 Existing enrollment = always visible.
                
                */

                if ($alreadyEnrolled) {

                    $eligible = true;
                    $reason = null;

                } elseif (!$completion['completed']) {

                    $eligible = false;
                    $reason =
                        $completion['message'];

                } elseif ($failedPrerequisite) {

                    $eligible = false;

                    $reason =
                        'Prerequisite not completed: ' .
                        $failedPrerequisite['course_code'];

                } else {

                    $eligible = true;
                    $reason = null;
                }

                return [
                    'id' => $course->id,

                    'course_code' =>
                        $course->course_code,

                    'course_name' =>
                        $course->course_name,

                    'credit' =>
                        (float) $course->credit,

                    'prerequisites' =>
                        $prereqData,

                    'eligible' =>
                        $eligible,

                    'reason' =>
                        $reason,

                    'already_enrolled' =>
                        $alreadyEnrolled,
                ];
            })
            ->values();

        /*
        
         Registration response
        
        */

        return response()->json([

            'student' => $this->studentInfo(
                $student,
                $batch
            ),

            'registration_semester' =>
                $registrationSemester,

            'registration_open' =>
                $completion['completed'],

            'courses' =>
                $courseData,

            'sections' =>
                $completion['completed']
                    ? $sectionData
                    : [],

            /*
              Keep admin-created enrollments visible even when
              student registration is currently blocked.
             */
            'enrollments' =>
                $existingEnrollments,

            'completion' =>
                $completion,

            'message' =>
                $completion['completed']
                    ? 'You can register for the upcoming semester.'
                    : $completion['message'],
        ]);
    }

    /*
    
     Register Course
    
    */

    public function store(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'student') {
            return response()->json([
                'message' => 'Unauthorized.'
            ], 403);
        }

        $request->validate([
            'course_id' => [
                'required',
                'integer',
                'exists:courses,id'
            ],

            'section_id' => [
                'required',
                'integer',
                'exists:sections,id'
            ],
        ]);

        $student = $user->student()
            ->with('batch')
            ->first();

        if (!$student || !$student->batch) {
            return response()->json([
                'message' =>
                    'Student academic information not found.'
            ], 404);
        }

        /*
        
         Find Department
        
        */

        $department = Department::where(
            'code',
            $student->department
        )->first();

        if (!$department) {
            return response()->json([
                'message' =>
                    'Student department information not found.'
            ], 404);
        }

        /*
        
         Current semester ONLY from Batch
        
        */

        $currentSemester =
            $student->batch->current_semester;

        $registrationSemester =
            $this->nextSemester(
                $currentSemester
            );

        if (!$registrationSemester) {
            return response()->json([
                'message' =>
                    'Course registration is closed.'
            ], 422);
        }

        /*
        
         CURRENT SEMESTER COMPLETION CHECK
        
        */

        $completion = $this->semesterCompletion(
            $student->id,
            $department->id,
            $currentSemester
        );

        if (!$completion['completed']) {
            return response()->json([
                'message' =>
                    $completion['message'],
            ], 422);
        }

        /*
        
         Course validation
        
        */

        $course = Course::with(
            'department'
        )->findOrFail(
            $request->course_id
        );

        /*
        
         Course department + semester validation
        
        
         OLD:
         $course->department !== $student->department
        
         NEW:
         $course->department_id must match student's department ID.
        
        */

        if (
            !$course->department ||
            (int) $course->department_id !==
                (int) $department->id ||
            $course->semester !==
                $registrationSemester
        ) {
            return response()->json([
                'message' =>
                    'This course is not available for your registration semester.'
            ], 422);
        }

        /*
        
         Section validation
        
        */

        $section = Section::where(
            'id',
            $request->section_id
        )
            ->where(
                'batch_id',
                $student->batch_id
            )
            ->where(
                'status',
                'Active'
            )
            ->first();

        if (!$section) {
            return response()->json([
                'message' =>
                    'Invalid section selected.'
            ], 422);
        }

        /*
        
         Transaction
        
        */

        return DB::transaction(function () use (
            $student,
            $course,
            $section,
            $registrationSemester
        ) {

            /*
            
             Lock section
            
            */

            $section = Section::whereKey(
                $section->id
            )
                ->lockForUpdate()
                ->first();

            /*
            
             Duplicate enrollment
            
            */

            $existing = Enrollment::where(
                'student_id',
                $student->id
            )
                ->where(
                    'course_id',
                    $course->id
                )
                ->where(
                    'semester',
                    $registrationSemester
                )
                ->first();

            if ($existing) {
                return response()->json([
                    'message' =>
                        'You are already enrolled in this course.'
                ], 422);
            }

            /*
            
             Section capacity
            
            */

            $activeStudents = Enrollment::where(
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

            if (
                $activeStudents >=
                $section->capacity
            ) {
                return response()->json([
                    'message' =>
                        'This section is full. Please select another section.'
                ], 422);
            }

            /*
            
             Prerequisites
            
            */

            $prerequisiteIds =
                Prerequisite::where(
                    'course_id',
                    $course->id
                )
                    ->pluck(
                        'prerequisite_course_id'
                    );

            if ($prerequisiteIds->isNotEmpty()) {

                $passedIds = Result::where(
                    'student_id',
                    $student->id
                )
                    ->whereIn(
                        'course_id',
                        $prerequisiteIds
                    )
                    ->whereNotNull('grade')
                    ->whereRaw(
                        'UPPER(TRIM(grade)) != ?',
                        ['F']
                    )
                    ->pluck('course_id');

                $missing =
                    $prerequisiteIds->diff(
                        $passedIds
                    );

                if ($missing->isNotEmpty()) {

                    $missingCourse =
                        Course::find(
                            $missing->first()
                        );

                    return response()->json([
                        'message' =>
                            'Prerequisite not completed: ' .
                            (
                                $missingCourse?->course_code
                                ?? 'Required course'
                            )
                    ], 422);
                }
            }

            /*
            
             Create enrollment
            
            */

            $enrollment = Enrollment::create([
                'student_id' =>
                    $student->id,

                'course_id' =>
                    $course->id,

                'section_id' =>
                    $section->id,

                'semester' =>
                    $registrationSemester,

                'status' =>
                    'Active',
            ]);

            /*
            
             Update section enrolled count
            
            */

            $section->enrolled =
                Enrollment::where(
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

            $section->save();

            return response()->json([
                'message' =>
                    'Course registered successfully.',

                'enrollment' =>
                    $enrollment->load([
                        'course',
                        'section'
                    ]),
            ], 201);
        });
    }

    /*
    
     Current Semester Completion
    
    */

    private function semesterCompletion(
        int $studentId,
        int $departmentId,
        string $semester
    ): array {

        /*
        
        | Current semester curriculum
        
        
         Courses are now connected to departments through
         courses.department_id.
        
        */

        $courses = Course::where(
            'department_id',
            $departmentId
        )
            ->where(
                'semester',
                $semester
            )
            ->orderBy(
                'course_code'
            )
            ->get([
                'id',
                'course_code'
            ]);

        /*
        
         If no courses exist in curriculum
        
        */

        if ($courses->isEmpty()) {
            return [
                'completed' => true,

                'total_courses' => 0,

                'passed_courses' => 0,

                'missing_courses' => [],

                'failed_courses' => [],

                'message' =>
                    'Current semester requirements are completed.',
            ];
        }

        $courseIds =
            $courses->pluck('id');

        /*
        
         Passing results
        
        */

        $results = Result::where(
            'student_id',
            $studentId
        )
            ->whereIn(
                'course_id',
                $courseIds
            )
            ->whereNotNull('grade')
            ->get([
                'course_id',
                'grade'
            ]);

        $passedIds = $results
            ->filter(function ($result) {

                return strtoupper(
                    trim($result->grade)
                ) !== 'F';
            })
            ->pluck('course_id')
            ->unique();

        $failedIds = $results
            ->filter(function ($result) {

                return strtoupper(
                    trim($result->grade)
                ) === 'F';
            })
            ->pluck('course_id')
            ->unique();

        $missingIds =
            $courseIds->diff(
                $passedIds->merge(
                    $failedIds
                )
            );

        $failedCourses =
            $courses
                ->whereIn(
                    'id',
                    $failedIds
                )
                ->pluck(
                    'course_code'
                )
                ->values()
                ->all();

        $missingCourses =
            $courses
                ->whereIn(
                    'id',
                    $missingIds
                )
                ->pluck(
                    'course_code'
                )
                ->values()
                ->all();

        $passedCount =
            $passedIds->count();

        $totalCount =
            $courses->count();

        /*
        
         Everything must be passed
        
        */

        $completed =
            $passedCount === $totalCount;

        if ($completed) {

            $message =
                'Current semester completed. You can register for the upcoming semester.';

        } elseif (!empty($failedCourses)) {

            $message =
                'You cannot register for the next semester because you have failed course(s): ' .
                implode(
                    ', ',
                    $failedCourses
                ) .
                '.';

        } else {

            $message =
                'You cannot register for the next semester until all current semester courses have completed passing results.';
        }

        return [
            'completed' =>
                $completed,

            'total_courses' =>
                $totalCount,

            'passed_courses' =>
                $passedCount,

            'missing_courses' =>
                $missingCourses,

            'failed_courses' =>
                $failedCourses,

            'message' =>
                $message,
        ];
    }

    /*
    
     Next Semester
    
    */

    private function nextSemester(
        ?string $semester
    ): ?string {

        $index = array_search(
            $semester,
            $this->semesters,
            true
        );

        if (
            $index === false ||
            $index >=
                count($this->semesters) - 1
        ) {
            return null;
        }

        return $this->semesters[
            $index + 1
        ];
    }

    /*
    
     Student Information
    
    */

    private function studentInfo(
        $student,
        $batch
    ): array {

        return [
            'id' =>
                $student->id,

            'user_id' =>
              $student->user?->user_id ?? '',

            'name' =>
                $student->user?->name ?? '',

            'email' =>
                $student->user?->email ?? '',

            'department' =>
                $student->department,

            'batch' =>
                $batch?->batch_number,

            'section' =>
                $student->section?->section_name,

            'current_semester' =>
                $batch?->current_semester,
        ];
    }
}
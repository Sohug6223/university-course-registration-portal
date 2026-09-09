<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ForgotPasswordController;
use App\Http\Controllers\Api\AdminProfileController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\Api\CourseController;
use App\Http\Controllers\Api\SectionController;
use App\Http\Controllers\Api\ResultController;
use App\Http\Controllers\Api\PrerequisiteController;
use App\Http\Controllers\Api\BatchController;
use App\Http\Controllers\Api\DepartmentController;
use App\Http\Controllers\Api\ClassScheduleController;
use App\Http\Controllers\Api\EnrollmentController;
use App\Http\Controllers\Api\NoticeController;
use App\Http\Controllers\Api\StudentPortalController;
use App\Http\Controllers\Api\StudentEnrollmentController;
use App\Http\Controllers\Api\MyCoursesController;
use App\Http\Controllers\Api\StudentRoutineController;
use App\Http\Controllers\Api\StudentResultController;
use App\Http\Controllers\Api\StudentNoticeController;
use App\Http\Controllers\Api\StudentProfileController;
use App\Http\Controllers\Api\ChangePasswordController;

/*

 Authenticated User

*/

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

/*

 Authentication

*/

Route::post('/login', [
    AuthController::class,
    'login'
]);

Route::post('/forgot-password', [
    ForgotPasswordController::class,
    'sendOtp'
]);

Route::post('/verify-otp', [
    ForgotPasswordController::class,
    'verifyOtp'
]);

Route::post('/reset-password', [
    ForgotPasswordController::class,
    'resetPassword'
]);

/*

 Common Authenticated Routes

*/

Route::middleware('auth:sanctum')->group(function () {

    Route::put('/change-password', [
        ChangePasswordController::class,
        'update'
    ]);
});

/*

 Student Routes

*/

Route::middleware('auth:sanctum')
    ->prefix('student')
    ->group(function () {

        Route::get('/profile', [
            StudentProfileController::class,
            'index'
        ]);

        Route::put('/profile', [
            StudentProfileController::class,
            'update'
        ]);

        Route::get('/dashboard', [
            StudentPortalController::class,
            'dashboard'
        ]);

        Route::get('/registration', [
            StudentEnrollmentController::class,
            'index'
        ]);

        Route::post('/registration', [
            StudentEnrollmentController::class,
            'store'
        ]);

        Route::get('/my-courses', [
            MyCoursesController::class,
            'index'
        ]);

        Route::get('/routine', [
            StudentRoutineController::class,
            'index'
        ]);

        Route::get('/results', [
            StudentResultController::class,
            'index'
        ]);

        Route::get('/notices', [
            StudentNoticeController::class,
            'index'
        ]);
    });

/*

 Admin Routes

*/

Route::middleware([
    'auth:sanctum',
    'admin'
])
    ->prefix('admin')
    ->group(function () {

        // Admin Profile
        Route::get('/profile', [AdminProfileController::class, 'show']);
        Route::put('/profile', [AdminProfileController::class, 'update']);


        /*
        
         Dashboard
        
        */

        Route::get('/dashboard', [
            DashboardController::class,
            'index'
        ]);

        /*
        
         Departments
        
        */

        Route::apiResource(
            'departments',
            DepartmentController::class
        );

        /*
        
         Students
        
        */

        Route::apiResource(
            'students',
            StudentController::class
        );

        /*
        
         Batches
        
        */

        Route::post(
            '/batches/{batch}/advance-semester',
            [
                BatchController::class,
                'advanceSemester'
            ]
        );

        Route::post(
            '/batches/{batch}/graduate',
            [
                BatchController::class,
                'graduate'
            ]
        );
        Route::post(
            '/batches/{batch}/restore',
            [
                BatchController::class,
                'restore'
            ]
        );

        Route::apiResource(
            'batches',
            BatchController::class
        );

        /*
        
         Courses
        
        */

        Route::apiResource(
            'courses',
            CourseController::class
        );

        /*
        
         Sections
        
        */

        Route::apiResource(
            'sections',
            SectionController::class
        );

        /*
        
         Results
        
        */

        Route::apiResource(
            'results',
            ResultController::class
        );

        Route::get(
            '/results/student-info/{studentId}',
            [
                ResultController::class,
                'studentInfo'
            ]
        );

        Route::get(
            '/results/student/{userId}/summary',
            [
                ResultController::class,
                'studentSummary'
            ]
        );

        /*
        
         Prerequisites
        
        */

        Route::apiResource(
            'prerequisites',
            PrerequisiteController::class
        );

        /*
        
         Class Schedules
        
        */

        Route::apiResource(
            'schedules',
            ClassScheduleController::class
        );

        /*
        
         Enrollment - Student Info
        
        */

        Route::get(
            '/enrollments/student-info',
            [
                EnrollmentController::class,
                'studentInfo'
            ]
        );

        /*
        
         Enrollment - Student History
        
        */

        Route::get(
            '/enrollments/student-history',
            [
                EnrollmentController::class,
                'studentHistory'
            ]
        );

        Route::get(
            '/enrollments/historical-options',
            [
                EnrollmentController::class,
                'historicalOptions'
            ]
        );

        /*
        
         Enrollment - Restore
        
        */

        Route::post(
            '/enrollments/restore',
            [
                EnrollmentController::class,
                'restore'
            ]
        );

        /*
        
         Enrollment Resource
        
        */

        Route::apiResource(
            'enrollments',
            EnrollmentController::class
        )->only([
            'index',
            'store',
            'update',
            'destroy'
        ]);

        /*
        
         Notices
        
        */

        Route::apiResource(
            'notices',
            NoticeController::class
        );
    });

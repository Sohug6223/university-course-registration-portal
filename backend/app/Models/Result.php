<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Result extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'course_id',
        'semester',
        'marks',
        'grade',
        'grade_point',
        'status',
    ];

    protected $casts = [
        'student_id' => 'integer',
        'course_id' => 'integer',
        'marks' => 'integer',
        'grade_point' => 'decimal:2',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }
    
}
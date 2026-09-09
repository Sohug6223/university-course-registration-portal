<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_code',
        'course_name',
        'department_id',
        'semester',
        'credit',
        'course_type',
        'description',
        'status',
    ];

    protected $casts = [
        'credit' => 'decimal:2',
    ];

    /*
      Course belongs to a department.
     */
    public function department()
    {
        return $this->belongsTo(
            Department::class,
            'department_id'
        );
    }

    public function sections()
    {
        return $this->hasMany(Section::class);
    }

    public function results()
    {
        return $this->hasMany(Result::class);
    }

    public function prerequisites()
    {
        return $this->hasMany(
            Prerequisite::class,
            'course_id'
        );
    }

    public function prerequisiteFor()
    {
        return $this->hasMany(
            Prerequisite::class,
            'prerequisite_course_id'
        );
    }
}


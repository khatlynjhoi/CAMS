<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Course extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'title',
        'program',
        'course_outcomes',
    ];

    protected $casts = [
        'course_outcomes' => 'array',
    ];
}
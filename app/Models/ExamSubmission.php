<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExamSubmission extends Model
{
    use HasFactory;

    protected $fillable = [
        'cadet_id',
        'cadet_name',
        'course_id',
        'course_code',
        'score',
        'total_items',
        'duration_minutes',
        'breakdown',
        'submitted_at',
    ];

    protected $casts = [
        'breakdown' => 'array',
        'submitted_at' => 'datetime',
    ];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }
}
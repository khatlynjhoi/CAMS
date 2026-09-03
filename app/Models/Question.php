<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    use HasFactory;

    protected $fillable = [
        'program',
        'term',
        'course_id',
        'course_outcome_id',
        'learning_outcome_id',
        'code',
        'type',
        'text',
        'image_url',
        'options',
        'correct_answer',
        'matching_pairs',
        'stcw_standard',
        'bloom_level',
        'status',
        'ai_accepted',
        'retained_ai',
    ];

    protected $casts = [
        'options' => 'array',
        'matching_pairs' => 'array',
        'ai_accepted' => 'boolean',
        'retained_ai' => 'boolean',
    ];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }
}
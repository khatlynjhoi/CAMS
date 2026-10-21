<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class Question extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
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

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'options' => 'array',
        'matching_pairs' => 'array',
        'ai_accepted' => 'boolean',
        'retained_ai' => 'boolean',
    ];

    /**
     * Automatically format question code on assignment.
     */
    public function setCodeAttribute(?string $value): void
    {
        $this->attributes['code'] = $value ? strtoupper(trim($value)) : null;
    }

    /**
     * Get the course associated with this question.
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    /**
     * Get the course outcome associated with this question (mapped via code).
     */
    public function courseOutcome(): BelongsTo
    {
        return $this->belongsTo(CourseOutcome::class, 'course_outcome_id', 'code');
    }

    /**
     * Get the learning outcome associated with this question (mapped via code).
     */
    public function learningOutcome(): BelongsTo
    {
        return $this->belongsTo(LearningOutcome::class, 'learning_outcome_id', 'code');
    }

    /**
     * Scope query to search text, question code, or STCW standard.
     */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where(function ($q) use ($term) {
            $q->where('text', 'like', "%{$term}%")
              ->orWhere('code', 'like', "%{$term}%")
              ->orWhere('stcw_standard', 'like', "%{$term}%");
        });
    }

    /**
     * Scope query to filter by course ID.
     */
    public function scopeByCourse(Builder $query, int|string $courseId): Builder
    {
        return $query->where('course_id', $courseId);
    }

    /**
     * Scope query to filter by Bloom's Taxonomy cognitive level.
     */
    public function scopeByBloomLevel(Builder $query, string $level): Builder
    {
        return $query->where('bloom_level', $level);
    }

    /**
     * Scope query to filter by status (e.g. Approved, Pending, Draft).
     */
    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Scope query to filter by program (e.g. BSMT, BSMARE).
     */
    public function scopeByProgram(Builder $query, string $program): Builder
    {
        return $query->where('program', $program);
    }
}
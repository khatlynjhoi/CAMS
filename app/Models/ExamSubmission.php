<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class ExamSubmission extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'test_id',
        'cadet_id',
        'cadet_name',
        'course_id',
        'course_code',
        'course_title',
        'term',
        'score',
        'total_items',
        'duration_minutes',
        'time_spent',
        'breakdown',
        'submitted_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'score' => 'float',
        'total_items' => 'integer',
        'duration_minutes' => 'integer',
        'time_spent' => 'integer',
        'breakdown' => 'array',
        'submitted_at' => 'datetime',
    ];

    /**
     * Get the course associated with this exam submission.
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Get the user (cadet) associated with this submission if linked by ID.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cadet_id');
    }

    /**
     * Compute calculated score percentage.
     */
    public function getPercentageAttribute(): float
    {
        if ($this->total_items <= 0) {
            return 0.0;
        }

        return round(($this->score / $this->total_items) * 100, 2);
    }

    /**
     * Scope query to search by cadet name, cadet ID, or course code.
     */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where(function ($q) use ($term) {
            $q->where('cadet_name', 'like', "%{$term}%")
              ->orWhere('cadet_id', 'like', "%{$term}%")
              ->orWhere('course_code', 'like', "%{$term}%")
              ->orWhere('course_title', 'like', "%{$term}%");
        });
    }

    /**
     * Scope query to filter submissions by cadet ID.
     */
    public function scopeByCadet(Builder $query, string $cadetId): Builder
    {
        return $query->where('cadet_id', $cadetId);
    }

    /**
     * Scope query to filter submissions by course code.
     */
    public function scopeByCourseCode(Builder $query, string $courseCode): Builder
    {
        return $query->where('course_code', $courseCode);
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Course extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'code',
        'title',
        'program',
        'course_outcomes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'course_outcomes' => 'array',
    ];

    /**
     * Automatically format course code to uppercase when set.
     */
    public function setCodeAttribute(string $value): void
    {
        $this->attributes['code'] = strtoupper(trim($value));
    }

    /**
     * Get all questions tied to this course in the Question Bank.
     */
    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }

    /**
     * Get all exam submissions recorded for this course.
     */
    public function examSubmissions(): HasMany
    {
        return $this->hasMany(ExamSubmission::class);
    }

    /**
     * Scope query to search by course code, title, or program.
     */
    public function scopeSearch(Builder $query, string$term): Builder
    {
        return $query->where(function ($q) use ($term) {
            $q->where('code', 'like', "\%{$term}%")
              ->orWhere('title', 'like', "%{$term}%")
              ->orWhere('program', 'like', "%{$term}%");
        });
    }

    /**
     * Scope query to filter by program.
     */
    public function scopeByProgram(Builder $query, string$program): Builder
    {
        return $query->where('program',$program);
    }
}
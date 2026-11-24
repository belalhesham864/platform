<?php

namespace App\Models;

use Database\Factories\CourseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class courses extends Model
{
    /** @use HasFactory<CourseFactory> */
    use HasFactory;

    protected $table = 'courses';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'category_id',
        'instructor_id',
        'title',
        'slug',
        'description',
        'price',
        'price_after_discount',
        'language',
        'duration',
        'thumbnail',
        'level',
        'number_of_students',
        'number_of_lectures',
        'is_published',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'price_after_discount' => 'decimal:2',
            'is_published' => 'boolean',
        ];
    }

    /**
     * Get the category that owns the course.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(categories::class, 'category_id');
    }

    /**
     * Get the instructor that owns the course.
     */
    public function instructor(): BelongsTo
    {
        return $this->belongsTo(instructors::class, 'instructor_id');
    }

    protected static function newFactory(): CourseFactory
    {
        return CourseFactory::new();
    }
}

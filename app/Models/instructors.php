<?php

namespace App\Models;

use Database\Factories\InstructorFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class instructors extends Authenticatable
{
    /** @use HasFactory<InstructorFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'instructors';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'email',
        'Bio',
        'password',
        'phone',
        'portfolio_picture',
        'small_description',
        'gender',
        'birthday',
        'education',
        'certifications',
        'skills',
        'experience',
        'specializations',
        'achievements',
        'years_of_experience',
        'linkedin_url',
        'facebook_url',
        'twitter_url',
        'instagram_url',
        'youtube_url',
        'is_active',
        'email_verified_at',
        'rank_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'birthday' => 'date',
            'password' => 'hashed',
            'education' => 'array',
            'certifications' => 'array',
            'skills' => 'array',
            'experience' => 'array',
            'specializations' => 'array',
            'achievements' => 'array',
            'is_active' => 'boolean',
        ];
    }

    protected static function newFactory(): InstructorFactory
    {
        return InstructorFactory::new();
    }
}

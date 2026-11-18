<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InstructorResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'email' => $this->email,
            'Bio' => $this->Bio,
            'phone' => $this->phone,
            'portfolio_picture' => $this->portfolio_picture,
            'small_description' => $this->small_description,
            'gender' => $this->gender,
            'birthday' => $this->birthday,
            'education' => $this->education,
            'certifications' => $this->certifications,
            'skills' => $this->skills,
            'experience' => $this->experience,
            'specializations' => $this->specializations,
            'achievements' => $this->achievements,
            'years_of_experience' => $this->years_of_experience,
            'linkedin_url' => $this->linkedin_url,
            'facebook_url' => $this->facebook_url,
            'twitter_url' => $this->twitter_url,
            'instagram_url' => $this->instagram_url,
            'youtube_url' => $this->youtube_url,
            'is_active' => (bool) $this->is_active,
            'rank_id' => $this->rank_id,
            'created_at' => $this->created_at,
        ];
    }
}

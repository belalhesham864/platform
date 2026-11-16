<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('instructors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('email')->unique();
            $table->string('Bio');
            $table->string('password');
            $table->string('phone')->nullable();
            $table->string('portfolio_picture')->nullable();
            $table->string('small_description')->nullable();
            $table->enum('gender', ['male', 'female'])->nullable();
            $table->date('birthday')->nullable();

           $table->json('education')->nullable();
           $table->json('certifications')->nullable();
           $table->json('skills')->nullable();
           $table->json('experience')->nullable();
           $table->json('specializations')->nullable();
           $table->json('achievements')->nullable();

           $table->integer('years_of_experience')->nullable();
           $table->string('linkedin_url')->nullable();
              $table->string('facebook_url')->nullable();
              $table->string('twitter_url')->nullable();
           $table->string('instagram_url')->nullable();
           $table->string('youtube_url')->nullable();
           $table->boolean('is_active')->default(true);
           $table->timestamp('email_verified_at')->nullable();

           $table->foreignId('rank_id')->nullable()->constrained('instructor_ranks')->restrictOnDelete();
           $table->index('is_active');
           $table->index(['rank_id', 'is_active']);


            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('instructors');
    }
};

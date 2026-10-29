<?php

namespace Database\Factories;

use App\Models\CandidateProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CandidateProfile>
 */
class CandidateProfileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->candidate(),
            'summary' => fake()->paragraph(),
            'cv_path' => null,
            'cv_original_name' => null,
        ];
    }

    /**
     * Indicate that the profile has an uploaded CV.
     */
    public function withCv(): static
    {
        return $this->state(fn (array $attributes) => [
            'cv_path' => 'cvs/'.fake()->uuid().'.pdf',
            'cv_original_name' => 'cv.pdf',
        ]);
    }
}

<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Course>
 */
class CourseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->title;

        return [
            'title' => $title,
            'slug' => str($title)->slug(),
            'summary' => fake()->words(3, true),
            'description' => fake()->text(),
        ];
    }
}

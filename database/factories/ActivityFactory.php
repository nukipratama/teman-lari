<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Activity;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Activity>
 */
class ActivityFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'strava_external_id' => fake()->unique()->numberBetween(1_000_000_000, 9_999_999_999),
            'fetched_at' => now(),
            'analyzed_at' => null,
            'detail_fail_count' => 0,
        ];
    }

    public function analyzed(): static
    {
        return $this->state(fn (): array => [
            'analyzed_at' => now(),
        ]);
    }
}

<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Activity;
use App\Models\PersonalRecord;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PersonalRecord>
 */
class PersonalRecordFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Distance PRs: total elapsed seconds. Effort PRs: pace seconds per km.
        $category = fake()->randomElement([
            '1km', '5km', '10km', '15km', 'half_marathon',
            'best_5min', 'best_10min', 'best_20min', 'best_30min',
        ]);

        $valueSec = match ($category) {
            '1km' => fake()->randomFloat(2, 240, 360),
            '5km' => fake()->randomFloat(2, 1500, 2400),
            '10km' => fake()->randomFloat(2, 3300, 5400),
            '15km' => fake()->randomFloat(2, 5400, 8100),
            'half_marathon' => fake()->randomFloat(2, 7800, 11700),
            default => fake()->randomFloat(2, 270, 420), // pace sec/km
        };

        return [
            'user_id' => User::factory(),
            'category' => $category,
            'value_sec' => $valueSec,
            'activity_id' => null,
            'set_at' => fake()->dateTimeBetween('-1 year', 'now'),
        ];
    }

    public function forActivity(Activity $activity): static
    {
        return $this->state(fn (): array => [
            'activity_id' => $activity->id,
            'user_id' => $activity->user_id,
        ]);
    }
}

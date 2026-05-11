<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Activity;
use App\Models\ActivityStream;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ActivityStream>
 */
class ActivityStreamFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'activity_id' => Activity::factory(),
            'data' => [
                'time' => ['data' => [0, 60, 120, 180, 240]],
                'distance' => ['data' => [0, 250, 500, 750, 1000]],
                'heartrate' => ['data' => [120, 150, 155, 158, 160]],
                'velocity_smooth' => ['data' => [0, 2.5, 2.7, 2.8, 2.8]],
                'cadence' => ['data' => [0, 80, 82, 82, 83]],
            ],
        ];
    }
}

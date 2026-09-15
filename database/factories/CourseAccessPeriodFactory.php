<?php

namespace Database\Factories;

use App\Models\CourseAccessPeriod;
use App\Models\Enrollment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CourseAccessPeriod>
 */
class CourseAccessPeriodFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = CourseAccessPeriod::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startsAt = now();
        $expiresAt = (clone $startsAt)->addYear();

        return [
            'enrollment_id' => Enrollment::factory(),
            'order_id' => null,
            'period_type' => 'initial',
            'starts_at' => $startsAt,
            'expires_at' => $expiresAt,
        ];
    }

    /**
     * Indicate that this access period is a renewal.
     */
    public function renewal(): static
    {
        return $this->state(fn (array $attributes) => [
            'period_type' => 'renewal',
        ]);
    }

    /**
     * Indicate that this access period is an admin grant.
     */
    public function adminGrant(): static
    {
        return $this->state(fn (array $attributes) => [
            'period_type' => 'admin_grant',
        ]);
    }

    /**
     * Indicate that this access period is expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'starts_at' => now()->subYear()->subDay(),
            'expires_at' => now()->subDay(),
        ]);
    }
}

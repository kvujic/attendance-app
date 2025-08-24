<?php

namespace Database\Factories;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Attendance>
 */
class AttendanceFactory extends Factory
{
    protected $model = Attendance::class;
    /**
     *
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $date = Carbon::instance($this->faker->dateTimeBetween('2025-04-01', 'now'))->startOfDay();

        $startHour = rand(7, 11);
        $clockIn = $date->copy()->setTime($startHour, 0);
        $clockOut = $clockIn->copy()->addHours(9); // include 1 hour break
        // total breaks : overwritten by BreakTime later

        return [
            'user_id' => User::factory(),
            'date' => $date->toDateString(),
            'clock_in' => $clockIn,
            'clock_out' => $clockOut,
            'total_break_time' => 60,
            'total_work_time' => 8 * 60,
        ];
    }

    
    public function onDate(Carbon $date): self
    {
        return $this->state(function () use ($date) {
            $startHour = rand(7, 11);
            $clockIn = $date->copy()->setTime($startHour, 0);
            $clockOut = $clockIn->copy()->addHours(9);

            return [
                'date' => $date->toDateString(),
                'clock_in' => $clockIn,
                'clock_out' => $clockOut,
                'total_break_time' => 60,
                'total_work_time' => 480,
            ];
        });
    }
}

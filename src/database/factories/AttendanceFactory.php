<?php

namespace Database\Factories;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

class AttendanceFactory extends Factory
{
    protected $model = Attendance::class;

    private const SEED_START_DATE = '2025-01-01';
    private const START_HOUR_MIN = 7;
    private const START_HOUR_MAX = 11;
    private const SHIFT_LENGTH_HOUR = 9;
    private const BREAK_MINUTES = 60;
    private const WORK_MINUTES = 8 * 60;

    public function definition(): array
    {
        $date = Carbon::instance($this->faker->dateTimeBetween(self::SEED_START_DATE, 'now'))->startOfDay();

        [$clockIn, $clockOut] = $this->buildShift($date);

        return [
            'user_id' => User::factory(),
            'date' => $date->toDateString(),
            'clock_in' => $clockIn,
            'clock_out' => $clockOut,
            'total_break_time' => self::BREAK_MINUTES,
            'total_work_time' => self::WORK_MINUTES,
        ];
    }

    public function onDate(Carbon $date): self
    {
        return $this->state(function () use ($date) {
            [$clockIn, $clockOut] = $this->buildShift($date);

            return [
                'date' => $date->toDateString(),
                'clock_in' => $clockIn,
                'clock_out' => $clockOut,
                'total_break_time' => self::BREAK_MINUTES,
                'total_work_time' => self::WORK_MINUTES,
            ];
        });
    }

    public function buildShift(Carbon $date): array
    {
        $startHour = random_int(self::START_HOUR_MIN, self::START_HOUR_MAX);
        $clockIn = $date->copy()->setTime($startHour, 0);
        $clockOut = $clockIn->copy()->addHours(self::SHIFT_LENGTH_HOUR);

        return [$clockIn, $clockOut];
    }
}

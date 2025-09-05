<?php

namespace Database\Factories;

use App\Models\CorrectionBreak;
use App\Models\AttendanceCorrection;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

class CorrectionBreakFactory extends Factory
{
    protected $model = CorrectionBreak::class;

    private const BREAK_START_HOUR_MIN = 12;
    private const BREAK_START_HOUR_MAX = 14;
    private const BREAK_DURATION_MIN = 30;
    private const BREAK_DURATION_MAX = 90;

    public function definition(): array
    {
        $start = Carbon::createFromTime($this->faker->numberBetween(self::BREAK_START_HOUR_MIN, self::BREAK_START_HOUR_MAX), 0);
        $end = (clone $start)->addMinutes($this->faker->numberBetween(self::BREAK_DURATION_MIN, self::BREAK_DURATION_MAX));

        return [
            'attendance_correction_id' => AttendanceCorrection::factory(),
            'requested_break_start' => $start->format('Y-m-d H:i:s'),
            'requested_break_end' => $end->format('Y-m-d H:i:s'),
        ];
    }
}

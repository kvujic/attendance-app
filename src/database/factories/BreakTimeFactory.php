<?php

namespace Database\Factories;

use App\Models\Attendance;
use App\Models\BreakTime;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

class BreakTimeFactory extends Factory
{
    protected $model = BreakTime::class;

    private const BREAK_START_MIN_MINUTES = 120;
    private const BREAK_START_MAX_MINUTES = 240;
    private const BREAK_DURATION_MIN = 30;
    private const BREAK_DURATION_MAX = 60;
    private const FALLBACK_BREAK_MINUTES = 30;

    public function definition(): array
    {
        $attendance = Attendance::factory()->create();

        $in = Carbon::parse($attendance->clock_in);
        $out = Carbon::parse($attendance->clock_out);

        $start = $in->copy()->addMinutes($this->faker->numberBetween(self::BREAK_START_MIN_MINUTES, self::BREAK_START_MAX_MINUTES));
        $end = $start->copy()->addMinutes($this->faker->numberBetween(self::BREAK_DURATION_MIN, self::BREAK_DURATION_MAX));

        if ($end->greaterThan($out)) {
            $end = $out->copy();
            $start= $end->copy()->subMinutes(self::FALLBACK_BREAK_MINUTES);
        }

        return [
            'attendance_id' => $attendance->id,
            'break_start' => $start,
            'break_end' => $end,
        ];
    }
}

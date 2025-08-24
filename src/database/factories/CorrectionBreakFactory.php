<?php

namespace Database\Factories;

use App\Models\CorrectionBreak;
use App\Models\AttendanceCorrection;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CorrectionBreak>
 */
class CorrectionBreakFactory extends Factory
{
    protected $model = CorrectionBreak::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = Carbon::createFromTime($this->faker->numberBetween(12, 14), 0);
        $end = (clone $start)->addMinutes($this->faker->numberBetween(30, 90));

        return [
            'attendance_correction_id' => AttendanceCorrection::factory(),
            'requested_break_start' => $start->format('H:i'),
            'requested_break_end' => $end->format('H:i'),
        ];
    }
}

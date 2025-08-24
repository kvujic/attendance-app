<?php

namespace Database\Factories;

use App\Models\AttendanceCorrection;
use App\Models\Attendance;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AttendanceCorrection>
 */
class AttendanceCorrectionFactory extends Factory
{
    protected $model = AttendanceCorrection::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $clockIn = Carbon::createFromTime($this->faker->numberBetween(8, 10), 0);
        $clockOut = (clone $clockIn)->addHours($this->faker->numberBetween(7, 9));

        return [
            'user_id' => User::factory(),
            'attendance_id' => Attendance::factory(),
            'requested_clock_in' => $clockIn->format('Y-m-d H:i:s'),
            'requested_clock_out' => $clockOut->format('Y-m-d H:i:s'),
            'request_note' => $this->faker->randomElement([
                '退勤を誤って打刻しました',
                '残業分を追加したい',
                '休憩時間の打刻を忘れました',
                '出勤時間を修正してください'
            ]),
            'status' => $this->faker->randomElement(['pending', 'approved']),
        ];
    }
}

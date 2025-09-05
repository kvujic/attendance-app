<?php

namespace Database\Factories;

use App\Models\AttendanceCorrection;
use App\Models\Attendance;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

class AttendanceCorrectionFactory extends Factory
{
    protected $model = AttendanceCorrection::class;

    private const CLOCK_IN_HOUR_MIN = 8;
    private const CLOCK_IN_HOUR_MAX = 10;
    private const SHIFT_LENGTH_MIN_HOUR = 7;
    private const SHIFT_LENGTH_MAX_HOUR = 9;

    public function definition(): array
    {
        $clockIn = Carbon::createFromTime($this->faker->numberBetween(self::CLOCK_IN_HOUR_MIN, self::CLOCK_IN_HOUR_MAX), 0);
        $clockOut = (clone $clockIn)->addHours($this->faker->numberBetween(self::SHIFT_LENGTH_MIN_HOUR, self::SHIFT_LENGTH_MAX_HOUR));

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

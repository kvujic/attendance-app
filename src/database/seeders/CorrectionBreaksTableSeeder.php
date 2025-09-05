<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AttendanceCorrection;
use App\Models\CorrectionBreak;
use Carbon\Carbon;

class CorrectionBreaksTableSeeder extends Seeder
{
    private const COIN_MIN = 0;
    private const COIN_MAX = 1;
    private const CREATE_BREAK_FLAG = 1;
    private const BREAK_OFFSET_HOURS = 2;
    private const BREAK_DURATION_MINUTES = 30;

    public function run(): void
    {
        AttendanceCorrection::all()->each(function ($correction) {
            if (random_int(self::COIN_MIN, self::COIN_MAX) === self::CREATE_BREAK_FLAG) {
                $start = Carbon::parse($correction->requested_clock_in)->addHours(self::BREAK_OFFSET_HOURS);
                $end = $start->copy()->addMinutes(self::BREAK_DURATION_MINUTES);

                CorrectionBreak::create([
                    'attendance_correction_id' => $correction->id,
                    'requested_break_start' =>  $start->format('Y-m-d H:i:s'),
                    'requested_break_end' => $end->format('Y-m-d H:i:s'),
                ]);
            }
        });
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Attendance;
use App\Models\AttendanceCorrection;
use App\Models\CorrectionBreak;
use Carbon\Carbon;

class AttendanceCorrectionsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $attendances = Attendance::inRandomOrder()->take(5)->get();

        foreach ($attendances as $attendance) {
            $correction = AttendanceCorrection::factory()->create([
                'user_id' => $attendance->user_id,
                'attendance_id' => $attendance->id,
            ]);

            CorrectionBreak::factory()
                ->count(rand(1, 2))
                ->create([
                    'attendance_correction_id' => $correction->id
                ]);

            /*
            $date = Carbon::parse($attendance->date)->toDateString();

            $clockIn = Carbon::createFromTime(rand(8, 10), 0, 0);
            $clockOut = (clone $clockIn)->addHours(rand(7, 9));

            $clockInDt = Carbon::parse("{$date} {$clockIn->format('H:i:s')}");
            $clockOutDt = Carbon::parse("{$date} {$clockOut->format('H:i:s')}");

            $correction = AttendanceCorrection::create([
                'user_id' => $attendance->user_id,
                'attendance_id' => $attendance->id,
                'requested_clock_in' => $clockInDt->format('Y-m-d H:i:s'),
                'requested_clock_out' => $clockOutDt->format('Y-m-d H:i:s'),
                'status' => rand(0, 1) ? 'pending' : 'approved',
            ]);

            $breakCount = rand(1, 2);
            $base = Carbon::createFromTime(12, 0, 0);

            for ($i = 0; $i < $breakCount; $i++) {
                $start = (clone $base)->addMinutes($i * 75);
                $end = (clone $start)->addMinutes(rand(30, 60));

                $startDt = Carbon::parse("{$date} {$start->format('H:i:s')}");
                $endDt = Carbon::parse("{$date} {$end->format('H:i:s')}");

                CorrectionBreak::create([
                    'attendance_correction_id' => $correction->id,
                    'requested_break_start' => $startDt->format('Y-m-d H:i:s'),
                    'requested_break_end' => $endDt->format('Y-m-d H:i:s'),
                ]);
            }
            */
        }
    }
}

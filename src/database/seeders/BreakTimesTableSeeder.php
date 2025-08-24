<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Attendance;
use App\Models\BreakTime;
use Carbon\Carbon;

class BreakTimesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Attendance::chunkById(200, function ($attendances) {
            foreach($attendances as $attendance) {
                $attendance->breakTimes()->delete();

                $in = Carbon::parse($attendance->clock_in);
                $out = Carbon::parse($attendance->clock_out);

                $breakTotal = 60;

                if (rand(0, 1) === 1) {
                    // 1 hour
                    $start = $in->copy()->addMinutes(random_int(120, 240));
                    $end = $start->copy()->addMinutes(60);

                    if ($end->gt($out)) {
                        $end = $out->copy();
                        $start = $end->copy()->subMinutes(60);
                    }

                    BreakTime::create([
                        'attendance_id' => $attendance->id,
                        'break_start' => $start,
                        'break_end' => $end,
                    ]);
                } else {
                    // 30min x 2
                    $firstStart = $in->copy()->addMinutes(random_int(120, 180));
                    $firstEnd = $firstStart->copy()->addMinutes(30);
                    if ($firstEnd->gt($out)) {
                        $firstEnd = $out->copy();
                        $firstStart = $firstEnd->copy()->subMinute(30);
                    }

                    BreakTime::create([
                        'attendance_id' => $attendance->id,
                        'break_start' => $firstStart,
                        'break_end' => $firstEnd,
                    ]);

                    $secondStart = $in->copy()->addMinutes(random_int(300, 420));
                    $secondEnd = $secondStart->copy()->addMinutes(30);
                    if ($secondEnd->gt($out)) {
                        $secondEnd = $out->copy();
                        $secondStart = $secondEnd->copy()->subMinutes(30);

                    }

                    if ($secondStart->lt($firstEnd)) {
                        $secondStart  = $firstEnd->copy()->addMinutes(10);
                        $secondEnd = $secondStart->copy()->addMinutes(30);
                        if ($secondEnd->gt($out)) {
                            $secondEnd = $out->copy();
                            $secondStart = $secondEnd->copy()->subMinutes(30);
                        }
                    }

                    BreakTime::create([
                        'attendance_id' => $attendance->id,
                        'break_start' => $secondStart,
                        'break_end' => $secondEnd,
                    ]);
                }

                $attendance->update([
                    'total_break_time' => 60,
                    'total_work_time' => 480,
                ]);
            }
        });
    }
}

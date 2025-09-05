<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Attendance;
use Carbon\Carbon;

class BreakTimesTableSeeder extends Seeder
{
    private const CHUNK_SIZE = 200;

    private const SINGLE_BREAK_START_MIN = 120;
    private const SINGLE_BREAK_START_MAX = 240;
    private const SINGLE_BREAK_DURATION = 60;

    private const FIRST_BREAK_START_MIN = 120;
    private const FIRST_BREAK_START_MAX = 240;
    private const FIRST_BREAK_DURATION = 30;

    private const SECOND_BREAK_START_MIN = 300;
    private const SECOND_BREAK_START_MAX = 420;
    private const SECOND_BREAK_DURATION = 30;

    private const MIN_GAP_BETWEEN_BREAKS = 10;

    private const COIN_MIN = 0;
    private const COIN_MAX = 1;
    private const SINGLE_BREAK_FLAG = 1;

    public function run(): void
    {
        Attendance::chunkById(self::CHUNK_SIZE, function ($attendances) {
            foreach($attendances as $attendance) {
                if (empty($attendance->clock_in) || empty($attendance->clock_out)) {
                    continue;
                }

                $in = Carbon::parse($attendance->clock_in);
                $out = Carbon::parse($attendance->clock_out);
                if ($in->gte($out)) {
                    continue;
                }

                $attendance->breakTimes()->delete();

                if (random_int(self::COIN_MIN, self::COIN_MAX) === self::SINGLE_BREAK_FLAG) {
                    $start = $in->copy()->addMinutes(random_int(self::SINGLE_BREAK_START_MIN, self::SINGLE_BREAK_START_MAX));
                    $end = $start->copy()->addMinutes(self::SINGLE_BREAK_DURATION);

                    if ($end->gt($out)) {
                        $end = $out->copy();
                        $start = $end->copy()->subMinutes(self::SINGLE_BREAK_DURATION);

                        if ($start->lt($in)) {
                            $start = $in->copy();
                        }
                    }

                    $mins = max(0, $start->diffInMinutes(min($end, $out)));
                    if ($mins > 0) {
                        $attendance->breakTimes()->create([
                            'break_start' => $start,
                            'break_end' => $start->copy()->addMinutes($mins),
                        ]);
                    }
                } else {
                    $firstStart = $in->copy()->addMinutes(random_int(self::FIRST_BREAK_START_MIN, self::FIRST_BREAK_START_MAX));
                    $firstEnd = $firstStart->copy()->addMinutes(self::FIRST_BREAK_DURATION);

                    if ($firstEnd->gt($out)) {
                        $firstEnd = $out->copy();
                        $firstStart = $firstEnd->copy()->subMinutes(self::FIRST_BREAK_DURATION);
                        if ($firstStart->lt($in)) {
                            $firstStart = $in->copy();
                        }
                    }

                    $firstMins = max(0, $firstStart->diffInMinutes(min($firstEnd, $out)));
                    if ($firstMins > 0) {
                        $attendance->breakTimes()->create([
                            'break_start' => $firstStart,
                            'break_end' => $firstStart->copy()->addMinutes($firstMins),
                        ]);
                    }

                    $secondStart = $in->copy()->addMinutes(random_int(self::SECOND_BREAK_START_MIN, self::SECOND_BREAK_START_MAX));
                    $secondEnd = $secondStart->copy()->addMinutes(self::SECOND_BREAK_DURATION);

                    if (isset($firstEnd) && $secondStart->lt($firstEnd)) {
                        $secondStart = $firstEnd->copy()->addMinutes(self::MIN_GAP_BETWEEN_BREAKS);
                        $secondEnd = $secondStart->copy()->addMinutes(self::SECOND_BREAK_DURATION);
                    }

                    if ($secondEnd->gt($out)) {
                        $secondEnd = $out->copy();
                        $secondStart = $secondEnd->copy()->subMinutes(self::SECOND_BREAK_DURATION);

                        if (isset($firstEnd) && $secondStart->lt($firstEnd->copy()->addMinutes(self::MIN_GAP_BETWEEN_BREAKS))) {
                            $secondStart = null;
                        }
                        if ($secondStart && $secondStart->lt($in)) {
                            $secondStart = $in->copy();
                        }
                    }

                    if ($secondStart) {
                        $secondMins = max(0, $secondStart->diffInMinutes(min($secondEnd, $out)));
                        if ($secondMins > 0) {
                            $attendance->breakTimes()->create([
                                'break_start' => $secondStart,
                                'break_end' => $secondStart->copy()->addMinutes($secondMins),
                            ]);
                        }
                    }
                }
            }
        });
    }
}
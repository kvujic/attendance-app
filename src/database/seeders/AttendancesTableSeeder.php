<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;

class AttendancesTableSeeder extends Seeder
{

    private const SEED_START_DATE = '2025-01-01';
    private const STAFF_ROLE = 2;
    private const OFF_DAYS_PER_WEEK = 2;

    public function run(): void
    {
        $start = Carbon::parse(self::SEED_START_DATE)->startOfDay();
        $end = now()->startOfDay();

        User::where('role', self::STAFF_ROLE)->each(function ($user) use ($start, $end) {
            for ($weekStart = $start->copy()->startOfWeek(); $weekStart->lte($end); $weekStart->addWeek()) {
                $weekEnd = $weekStart->copy()->endOfWeek();
                if ($weekEnd->gt($end)) $weekEnd = $end;

                $days = [];
                for ($d = $weekStart->copy(); $d->lte($weekEnd); $d->addDay()) {
                    $days[] = $d->copy();
                }

                shuffle($days);
                $offDays = array_slice($days, 0, min(self::OFF_DAYS_PER_WEEK, count($days)));
                $offSet = collect($offDays)->map->toDateString()->flip();

                foreach ($days as $date) {
                    if (isset($offSet[$date->toDateString()])) continue;

                    Attendance::factory()
                        ->onDate($date)
                        ->state(['user_id' => $user->id])
                        ->create();
                }
            }
        });
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;

class AttendancesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $start = Carbon::parse('2025-04-01')->startOfDay();
        $end = now()->startOfDay();

        User::where('role', 2)->each(function ($user) use ($start, $end) {
            for ($weekStart = $start->copy()->startOfWeek(); $weekStart->lte($end); $weekStart->addWeek()) {
                $weekEnd = $weekStart->copy()->endOfWeek();
                if ($weekEnd->gt($end)) $weekEnd = $end;

                $days = [];
                for ($d = $weekStart->copy(); $d->lte($weekEnd); $d->addDay()) {
                    $days[] = $d->copy();
                }

                // pick 2 days off per a week
                shuffle($days);
                $offDays = array_slice($days, 0, min(2, count($days)));
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

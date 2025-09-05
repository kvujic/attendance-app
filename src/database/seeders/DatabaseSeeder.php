<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(UsersTableSeeder::class);
        $this->call(AttendancesTableSeeder::class);
        $this->call(BreakTimesTableSeeder::class);
        $this->call(AttendanceCorrectionsTableSeeder::class);
        $this->call(CorrectionBreaksTableSeeder::class);
    }
}

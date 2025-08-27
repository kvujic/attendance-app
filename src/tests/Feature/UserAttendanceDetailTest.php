<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class UserAttendanceDetailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        $this->user = User::factory()->create();

        $this->attendance = Attendance::factory()->create([
            'user_id' => $this->user->id,
            'date' => Carbon::today()->toDateString(),
            'clock_in' => Carbon::now()->toDateTimeString(),
            'clock_in' => carbon::now()->toDateTimeString(),
        ]);

        $this->breaks = BreakTime::factory()->create([
            'attendance_id' => $attendance->user->id,
            'break_start' => Carbon::now()->toDateTimeString(),
            'break_end' => Carbon::now()->toDateTimeString(),
        ]);
    }

    public function test_logged_in_user_name_is_displayed_on_attendance_detail_page()
    {

    }

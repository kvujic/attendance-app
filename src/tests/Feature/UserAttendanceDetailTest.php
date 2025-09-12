<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserAttendanceDetailTest extends TestCase
{
    use RefreshDatabase;

    private const BASE_DATE = '2025-07-31';

    private User $user;
    private Attendance $attendance;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'name' => 'test user',
            'email' => 'test@example.com',
        ]);

        $this->attendance = Attendance::factory()->create([
            'user_id' => $this->user->id,
            'date' => self::BASE_DATE,
            'clock_in' => $this->dt('09:00'),
            'clock_out' => $this->dt('18:00'),
        ]);

        $base = Carbon::parse($this->attendance->date);
        BreakTime::create([
            'attendance_id' => $this->attendance->id,
            'break_start' => $base->copy()->setTime(12, 0),
            'break_end' => $base->copy()->setTime(12, 45),
        ]);
        BreakTime::create([
            'attendance_id' => $this->attendance->id,
            'break_start' => $base->copy()->setTime(15, 0),
            'break_end' => $base->copy()->setTime(15, 15),
        ]);
    }

    public function test_logged_in_user_name_is_displayed_on_attendance_detail_page()
    {
        $this->actingAs($this->user);

        $response = $this->get(route('attendance.show', ['id' => $this->attendance->id]));

        $response->assertOk();
        $response->assertSee('test user');
    }

    public function test_selected_date_is_displayed_on_attendance_detail_page()
    {
        $this->actingAs($this->user);

        $response = $this->get(route('attendance.show', ['id' => $this->attendance->id]));
        $response->assertOk();

        $date = \Carbon\Carbon::parse($this->attendance->date);

        $response->assertSee('value="'.$date->format('Y年').'"', false);
        $response->assertSee('value="'.$date->format('n月j日').'"', false);
    }

    public function test_clock_in_and_clock_out_times_matches_the_time_stamped_on_attendance_detail_page()
    {
        $this->actingAs($this->user);

        $response = $this->get(route('attendance.show', ['id' => $this->attendance->id]));
        $response->assertOk();

        $expectedIn = Carbon::parse($this->attendance->clock_in)->format('H:i');
        $expectedOut = Carbon::parse($this->attendance->clock_out)->format('H:i');

        $response->assertSee($expectedIn);
        $response->assertSee($expectedOut);
    }

    public function test_break_times_are_matches_the_time_stamped_on_attendance_detail_page()
    {
        $this->actingAs($this->user);

        $response = $this->get(route('attendance.show', ['id' => $this->attendance->id]));
        $response->assertOk();

        $response->assertSee('12:00');
        $response->assertSee('12:45');
        $response->assertSee('15:00');
        $response->assertSee('15:15');
    }

    private function dt(string $hm): string
    {
        return self::BASE_DATE . ' ' . $hm . ':00';
    }
}

<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\ResponseTrait;
use Tests\TestCase;

class AttendanceClockInTest extends TestCase
{
    use RefreshDatabase;

    public function test_clock_in_button_works_properly()
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = $this->get(route('attendance.create'));
        $response->assertOk();
        $response->assertSee('出勤');

        $response= $this->followingRedirects()->post(route('attendance.store'), ['action' => 'clock_in']);
        $response->assertOk();
        $response->assertSee('勤務中');

        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'date' => Carbon::today()->toDateString(),
        ]);

        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('date', Carbon::today())
            ->first();

        $this->assertNotNull($attendance);
        $this->assertNotNull($attendance->clock_in);
    }

    public function test_can_clock_in_only_once_a_day()
    {
        $user = User::factory()->create();

        Attendance::create([
            'user_id' => $user->id,
            'date' => Carbon::today()->toDateString(),
            'clock_in' => Carbon::today()->copy()->setTime(9, 0, 0),
            'clock_out' => Carbon::today()->copy()->setTime(18, 0, 0),
        ]);

        $this->actingAs($user);
        $response = $this->get(route('attendance.create'));

        $response->assertOk();

        $response->assertDontSee('data-testid="clock-in-button"');
    }

    public function test_clock_in_time_is_recorded_in_the_attendance_list()
    {
        Carbon::setTestNow(Carbon::today()->setTime(9, 0, 0));
        $user = User::factory()->create();

        $this->actingAs($user);
        $this->post(route('attendance.store'));

        $response = $this->actingAs($user)->get(route('attendance.list'));
        $response->assertOk();

        $weekdays = ['日', '月', '火', '水', '木', '金', '土'];
        $expected = Carbon::today()->format('m/d') . '(' . $weekdays[Carbon::today()->dayOfWeek] . ')';
        $response->assertSee($expected);
        $response->assertSee('09:00');
    }
}

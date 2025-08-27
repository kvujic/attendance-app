<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class AttendanceClockOutTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::today()->setTime(9, 0, 0));
        $this->user = User::factory()->create();
    }

    public function test_clock_out_button_works_properly()
    {
        Attendance::factory()->create([
            'user_id' => $this->user->id,
            'date' => Carbon::today()->toDateString(),
            'clock_in' => Carbon::now()->toDateTimeString(),
            'clock_out' => null,
        ]);

        $this->actingAs($this->user);

        $response = $this->get(route('attendance.create'));

        $response->assertOk();
        $response->assertSee('退勤');

        Carbon::setTestNow(Carbon::today()->setTime(18, 0, 0));
        $response = $this->post(route('attendance.store'), ['action' => 'clock_out']);
        $response->assertRedirect(route('attendance.create'));

        $response = $this->get(route('attendance.create'));
        $response->assertSee('退勤済');
    }

    public function test_clock_out_time_is_displayed_on_attendance_list()
    {
        $this->actingAs($this->user);

        $response = $this->post(route('attendance.store'), ['action' => 'clock_in'])->assertRedirect();

        Carbon::setTestNow(Carbon::today()->setTime(18, 0, 0));
        $response = $this->post(route('attendance.store'), ['action' => 'clock_out'])->assertRedirect();

        $response = $this->get(route('attendance.list'));
        $response->assertOk();

        $weekdays = ['日', '月', '火', '水', '木', '金', '土'];
        $expected = Carbon::today()->format('m/d') . '(' . $weekdays[Carbon::today()->dayOfWeek] . ')';
        $response->assertSee($expected);
        $response->assertSee('18:00');
    }
}

<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BreakTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Attendance $attendance;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::today()->setTime(9, 0, 0));

        $this->user = User::factory()->create();

        $this->attendance = Attendance::factory()->create([
            'user_id' => $this->user->id,
            'date' => Carbon::today()->toDateString(),
            'clock_in' => Carbon::now()->toDateTimeString(),
            'clock_out' => null,
        ]);
    }

    public function test_break_start_button_works_and_status_changes_to_on_break()
    {
        $this->actingAs($this->user);

        $response = $this->get(route('attendance.create'));

        $response->assertOk();
        $response->assertSee('休憩入');

        $response = $this->followingRedirects()->post(route('attendance.store'), ['action' => 'break_start']);

        $response->assertSee('休憩中');
    }

    public function test_break_multiple_break_sessions_are_supported()
    {
        $this->actingAs($this->user);

        $response = $this->post(route('attendance.store'), ['action' => 'break_start'])->assertRedirect();

        Carbon::setTestNow(now()->addMinutes(15));
        $response = $this->post(route('attendance.store'), ['action' => 'break_end'])->assertRedirect();

        $response = $this->get(route('attendance.create'))->assertOk();
        $response->assertSee('休憩入');

        $finished = BreakTime::where('attendance_id', $this->attendance->id)->whereNotNull('break_end')->count();
        $this->assertSame(1, $finished);
    }

    public function test_break_end_button_works_and_status_return_to_working()
    {
        $this->actingAs($this->user);

        $response = $this->post(route('attendance.store'), ['action' => 'break_start'])->assertRedirect();

        $response = $this->get(route('attendance.create'));
        $response->assertOk();
        $response->assertSee('休憩戻');

        Carbon::setTestNow(now()->addMinute(20));
        $response = $this->followingRedirects()->post(route('attendance.store'), ['action' => 'break_end']);

        $response->assertSee('勤務中');
    }

    public function test_break_user_can_click_break_end_button_multiple_times_per_day()
    {
        $this->actingAs($this->user);

        $response = $this->post(route('attendance.store'), ['action' => 'break_start'])->assertRedirect();
        Carbon::setTestNow(now()->addMinute(10));
        $response = $this->post(route('attendance.store'), ['action' => 'break_end'])->assertRedirect();

        $response = $this->get(route('attendance.create'))->assertOk()->assertSee('休憩入');
        $response = $this->post(route('attendance.store'), ['action' => 'break_start'])->assertRedirect();

        $response = $this->get(route('attendance.create'));
        $response->assertOk();
        $response->assertSee('休憩戻');
    }

    public function test_break_times_are_displayed_on_attendance_list()
    {
        $this->actingAs($this->user);

        Carbon::setTestNow(Carbon::today()->setTime(9, 0));
        $response = $this->post(route('attendance.store'), ['action' => 'clock_in']);

        Carbon::setTestNow(Carbon::today()->setTime(9, 30));
        $response = $this->post(route('attendance.store'), ['action' => 'break_start'])->assertRedirect();

        Carbon::setTestNow(Carbon::today()->setTime(9, 45));
        $response = $this->post(route('attendance.store'), ['action' => 'break_end'])->assertRedirect();

        $response = $this->get(route('attendance.list'));
        $response->assertOk();

        $expected = Carbon::today()->locale('ja')->isoFormat('MM/DD(ddd)');
        $response->assertSee($expected);
        $response->assertSee('0:15');
    }
}

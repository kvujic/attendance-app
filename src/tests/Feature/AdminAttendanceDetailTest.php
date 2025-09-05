<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAttendanceDetailTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $staff;
    protected Attendance $attendance;
    protected BreakTime $break;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::create(2025, 8, 15, 9, 0, 0));

        $this->admin = User::factory()->create([
            'name' => 'admin user',
            'email' => 'admin@example.com',
            'password' => 'password',
            'role' => 1,
        ]);

        $this->staff = User::factory()->create([
            'name' => 'test user',
            'email' => 'testuser@example.com',
            'password' => 'password123',
            'role' => 2,
        ]);

        $this->attendance = Attendance::factory()->create([
            'user_id' => $this->staff->id,
            'date' => Carbon::today()->toDateString(),
            'clock_in' => Carbon::today()->setTime(9, 0, 0)->toDateTimeString(),
            'clock_out' => Carbon::today()->setTime(18, 0, 0)->toDateTimeString(),
        ]);

        $this->break = BreakTime::factory()->create([
            'attendance_id' => $this->attendance->id,
            'break_start' => Carbon::today()->setTime(12, 0, 0)->toDateTimeString(),
            'break_end' => Carbon::today()->setTime(13, 0, 0)->toDateTimeString(),
        ]);
    }

    public function test_selected_attendance_details_are_displayed_for_admin()
    {
        $this->actingAs($this->admin, 'admin');

        $response = $this->get(route('admin.attendance.showDetail', ['id' => $this->attendance->id]));

        $response->assertOk();

        $response->assertSee(e($this->staff->name));
        $date = \Carbon\Carbon::parse($this->attendance->date);
        $response->assertSee('value="' . $date->format('Y年') . '"', false);
        $response->assertSee('value="' . $date->format('n月j日') . '"', false);
        $response->assertSee(Carbon::parse($this->attendance->clock_in)->format('H:i'));
        $response->assertSee(Carbon::parse($this->attendance->clock_out)->format('H:i'));
        $response->assertSee('12:00');
        $response->assertSee('13:00');
    }

    public function test_validation_message_is_displayed_when_clock_in_is_after_clock_out()
    {
        $this->actingAs($this->admin, 'admin');

        $response = $this->from(route('admin.attendance.showDetail', ['id' => $this->attendance->id]))
            ->post(route('admin.attendance.updateAttendance', ['id' => $this->attendance->id]),
            [
                'requested_clock_in' => '19:00',
                'requested_clock_out' => '18:00',
                'request_note' => 'correction',
                'breaks' => [
                    [
                        'requested_break_start' =>  '12:00',
                        'requested_break_end' => '13:00',
                    ],
                ],
            ],
        );

        $response->assertRedirect();
        $response->assertSessionHasErrors([
            'requested_clock_out' => '出勤時間もしくは退勤時間が不適切な値です',
        ]);
    }

    public function test_validation_message_is_displayed_when_break_start_is_after_clock_out()
    {
        $this->actingAs($this->admin, 'admin');

        $response = $this->from(route('admin.attendance.showDetail', ['id' => $this->attendance->id]))
            ->post(route('admin.attendance.updateAttendance', ['id' => $this->attendance->id]),
            [
                'requested_clock_in' => '09:00',
                'requested_clock_out' => '18:00',
                'request_note' => 'correction',
                'breaks' => [
                    [
                        'requested_break_start' => '19:00',
                        'requested_break_end' => '19:30',
                    ],
                ],
            ],
        );

        $response->assertRedirect();
        $response->assertSessionHasErrors([
            'breaks.0.requested_break_start' => '休憩時間が不適切な値です',
        ]);
    }

    public function test_validation_message_displayed_when_break_end_is_after_clock_out()
    {
        $this->actingAs($this->admin, 'admin');

        $response = $this->from(route('admin.attendance.showDetail', ['id' => $this->attendance->id]))
            ->post(route('admin.attendance.updateAttendance', ['id' => $this->attendance->id]),
            [
                'requested_clock_in' => '09:00',
                'requested_clock_out' => '18:00',
                'request_note' => 'correction',
                'breaks' => [
                    [
                        'requested_break_start' => '17:30',
                        'requested_break_end' => '19:00',
                    ],
                ],
            ],
        );

        $response->assertRedirect();
        $response->assertSessionHasErrors([
            'breaks.0.requested_break_end' => '休憩時間もしくは退勤時間が不適切な値です',
        ]);
    }

    public function test_validation_message_is_displayed_when_request_note_is_empty()
    {
        $this->actingAs($this->admin, 'admin');

        $response = $this->from(route('admin.attendance.showDetail', ['id' => $this->attendance->id]))
            ->post(route('admin.attendance.updateAttendance', ['id' => $this->attendance->id]),
            [
                'requested_clock_in' => '09:00',
                'requested_clock_out' => '18:00',
                'request_note' => '',
                'breaks' => [],
            ],
        );

        $response->assertRedirect();
        $response->assertSessionHasErrors([
            'request_note' => '備考を記入してください',
        ]);
    }


}


<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Attendance;
use App\Models\AttendanceCorrection;
use App\Models\CorrectionBreak;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class AdminAttendanceCorrectionTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $userA;
    protected User $userB;
    protected Attendance $attendanceA;
    protected Attendance $attendanceB;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::create(2025, 8, 1, 10, 0, 0));

        $this->admin = User::factory()->create([
            'name' => 'admin user',
            'email' => 'admin@example.com',
            'password' => 'password',
            'role' => 1,
        ]);

        $this->userA = User::factory()->create([
            'name' => 'UserA',
            'email' => 'usera@example.com',
            'password' => 'password123',
            'role' => 2,
        ]);

        $this->userB = User::factory()->create([
            'name' => 'UserB',
            'email' => 'userb@example.com',
            'password' => 'password456',
            'role' => 2,
        ]);

        $this->attendanceA = Attendance::factory()->create([
            'user_id' => $this->userA->id,
            'date' => Carbon::today()->toDateString(),
            'clock_in' => Carbon::today()->setTime(9, 0, 0),
            'clock_out' => Carbon::today()->setTime(18, 0, 0),
        ]);

        $this->attendanceB = Attendance::factory()->create([
            'user_id' => $this->userB->id,
            'date' => Carbon::today()->toDateString(),
            'clock_in' => Carbon::today()->setTime(10, 0, 0),
            'clock_out' => Carbon::today()->setTime(19, 0, 0),
        ]);
    }

    public function test_all_users_pending_requests_are_displayed_in_the_pending_tab()
    {
        AttendanceCorrection::factory()->create([
            'user_id' => $this->userA->id,
            'attendance_id' => $this->attendanceA->id,
            'requested_clock_in' => Carbon::today()->setTime(9, 30, 0),
            'requested_clock_out' => Carbon::today()->setTime(18, 30, 0),
            'request_note' => 'correction for A',
            'status' => 'pending',
        ]);

        AttendanceCorrection::factory()->create([
            'user_id' => $this->userB->id,
            'attendance_id' => $this->attendanceB->id,
            'requested_clock_in' => Carbon::today()->setTime(10, 15, 0),
            'requested_clock_out' => Carbon::today()->setTime(19, 0, 0),
            'request_note' => 'correction for B',
            'status' => 'pending'
        ]);

        $this->actingAs($this->admin,'admin');
        $response = $this->get(route('stamp_correction_request.index', ['tab' => 'pending']));

        $response->assertOk();
        $response->assertSee('UserA');
        $response->assertSee('2025/08/01');
        $response->assertSee('correction for A');

        $response->assertSee('UserB');
        $response->assertSee('2025/08/01');
        $response->assertSee('correction for B');
    }

    public function test_all_users_approved_requests_are_displayed_in_the_approved_tab()
    {
        AttendanceCorrection::factory()->create([
            'user_id' => $this->userA->id,
            'attendance_id' => $this->attendanceA->id,
            'requested_clock_in' => Carbon::today()->setTime(9, 30, 0),
            'requested_clock_out' => Carbon::today()->setTime(18, 30, 0),
            'request_note' => 'correction for A',
            'status' => 'approved',
        ]);

        AttendanceCorrection::factory()->create([
            'user_id' => $this->userB->id,
            'attendance_id' => $this->attendanceB->id,
            'requested_clock_in' => Carbon::today()->setTime(10, 15, 0),
            'requested_clock_out' => Carbon::today()->setTime(19, 0, 0),
            'request_note' => 'correction for B',
            'status' => 'approved',
        ]);

        AttendanceCorrection::factory()->create([
            'user_id' => $this->userA->id,
            'attendance_id' => $this->attendanceA->id,
            'requested_clock_in' => Carbon::today()->setTime(8, 0, 0),
            'requested_clock_out' => Carbon::today()->setTime(17, 0, 0),
            'request_note' => 'correction not approved',
            'status' => 'pending',
        ]);

        $this->actingAs($this->admin);

        $response = $this->get(route('stamp_correction_request.index', ['tab' => 'approved']));
        $response->assertOk();

        $response->assertSee('correction for A');
        $response->assertSee('correction for B');

        $response->assertDontSee('correction not approved');
    }

    public function test_the_details_of_the_correction_request_are_displayed_correctly()
    {
        $correction = AttendanceCorrection::factory()->create([
            'user_id' => $this->userA->id,
            'attendance_id' => $this->attendanceA->id,
            'requested_clock_in' => Carbon::today()->setTime(9, 30, 0),
            'requested_clock_out' => Carbon::today()->setTime(18, 30, 0),
            'request_note' => 'show detail',
            'status' => 'pending',
        ]);

        CorrectionBreak::factory()->create([
            'attendance_correction_id' => $correction->id,
            'requested_break_start' => Carbon::today()->setTime(12, 0, 0),
            'requested_break_end' => Carbon::today()->setTime(13, 0, 0),
        ]);

        $this->actingAs($this->admin, 'admin');

        $response = $this->get(route('admin.attendance.showDetail', ['id' => $correction->attendance->id]));
        $response->assertOk();

        $response->assertSee('UserA');
        $response->assertSee('09:30');
        $response->assertSee('18:30');
        $response->assertSee('show detail');

        $response->assertSee('12:00');
        $response->assertSee('13:00');
    }
}

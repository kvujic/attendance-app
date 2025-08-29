<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Attendance;
use App\Models\AttendanceCorrection;
use App\Models\CorrectionBreak;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\ResponseTrait;
use Tests\TestCase;

class UserAttendanceCorrectionTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Attendance $attendance;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->attendance = Attendance::factory()->create([
            'user_id' => $this->user->id,
            'date' => '2025-08-01',
            'clock_in' => '2025-08-01 09:00:00',
            'clock_out' => '2025-08-01 18:00:00',
        ]);
    }

    public function test_validation_message_is_displayed_when_clock_in_after_clock_out()
    {
        $this->actingAs($this->user);

        $response = $this->from(route('attendance.show', ['id' => $this->attendance->id]))
            ->post(route('attendance.update',['id' => $this->attendance->id]),
                [
                    'requested_clock_in' => '19:00',
                    'requested_clock_out' => '18:00',
                    'request_note' => 'test',
                    'correction_breaks' => [],
                ]
            );

        $response->assertRedirect();
        $response->assertSessionHasErrors([
            'requested_clock_out' => '出勤時間もしくは退勤時間が不適切な値です',
        ]);
    }

    public function test_validation_message_displayed_when_break_start_after_clock_out()
    {
        $this->actingAs($this->user);

        $response = $this->from(route('attendance.show', ['id' => $this->attendance->id]))
            ->post(route('attendance.update', ['id' => $this->attendance->id]),
            [
                'requested_clock_in' => '09:00',
                'requested_clock_out' => '18:00',
                'request_note' => 'test',
                'breaks' => [
                    [
                        'requested_break_start' => '19:00',
                        'requested_break_end' => '19:30'
                    ],
                ],
            ],
        );

        $response->assertRedirect();
        $response->assertSessionHasErrors([
            'breaks.0.requested_break_start' => '休憩時間が不適切な値です',
        ]);
    }

    public function test_validation_message_displayed_when_break_end_after_clock_out()
    {
        $this->actingAs($this->user);

        $response = $this->from(route('attendance.show', ['id' => $this->attendance->id]))
            ->post(route('attendance.update', ['id' => $this->attendance->id]),
            [
                'requested_clock_in' => '09:00',
                'requested_clock_out' => '18:00',
                'request_note' => 'test',
                'breaks' => [
                    [
                        'requested_break_start' => '17:00',
                        'requested_break_end' => '19:00'
                    ],
                ],
            ],
        );

        $response->assertRedirect();
        $response->assertSessionHasErrors([
            'breaks.0.requested_break_end' => '休憩時間もしくは退勤時間が不適切な値です',
        ]);
    }

    public function test_validation_message_displayed_when_note_is_empty()
    {
        $this->actingAs($this->user);

        $response = $this->from(route('attendance.show', ['id' => $this->attendance->id]))
            ->post(route('attendance.update', ['id' => $this->attendance->id]),
            [
                'requested_clock_in' => '09:00',
                'requested_clock_out' => '18:00',
                'request_note' => '',
            ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors([
            'request_note' => '備考を記入してください'
        ]);
    }

    public function test_user_can_submit_correction_and_it_appears_on_admin_screen()
    {
        $this->actingAs($this->user);

        // user requests attendance correction (POST /attendance/{id})
        $payload = [
            'requested_clock_in' => '09:00',
            'requested_clock_out' => '19:00',
            'request_note' => '残業',
            'breaks' => [
                [
                    'requested_break_start' => '12:00',
                    'requested_break_end' => '13:00',
                ],
            ],
        ];

        $response = $this->from('attendance.show', ['id' => $this->attendance->id])
            ->post(route('attendance.update', ['id' => $this->attendance->id]), $payload);

        $response->assertRedirect();

        $this->assertDatabaseHas('attendance_corrections', [
            'attendance_id' => $this->attendance->id,
            'user_id' => $this->user->id,
            'requested_clock_in' => '2025-08-01 09:00:00',
            'requested_clock_out' => '2025-08-01 19:00:00',
            'request_note' => '残業',
            'status' => 'pending',
        ]);

        $correction = AttendanceCorrection::latest('id')->first();
        $this->assertDatabaseHas('correction_breaks', [
            'attendance_correction_id' => $correction->id,
            'requested_break_start' => '2025-08-01 12:00:00',
            'requested_break_end' => '2025-08-01 13:00:00',
        ]);

        // it shows up on admin approval page or not
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'adminuser@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 1,
        ]);

        $this->actingAs($admin, 'admin');

        $response = $this->get(route('stamp_correction_request.showCorrection', ['correction' => $correction->id]));
        $response->assertOk();
        $response->assertSee((string)$correction->id);
        $response->assertSee('残業');

        // admin pending approval page
        $response = $this->get(route('stamp_correction_request.index'));
        $response->assertOk();
        $response->assertSee((string)$correction->id);
        $response->assertSee('承認待ち');
    }

    public function test_all_requests_in_pending_status_are_displayed()
    {
        $this->actingAs($this->user);

        foreach([['09:00', '18:00'], ['09:30', '18:30']] as [$in, $out]) {
            $this->post(route('attendance.update', ['id' => $this->attendance->id]), [
                'requested_clock_in' => $in,
                'requested_clock_out' => $out,
                'request_note' => 'test',
                'breaks' => [],
            ])->assertRedirect();
        }

        $response = $this->get(route('stamp_correction_request.index'));
        $response->assertOk();

        $corrections = AttendanceCorrection::where('user_id', $this->user->id)->where('status', 'pending')->get();
        foreach($corrections as $correction) {
            $response->assertSee((string)$correction->id);
        }
    }

    public function test_all_requests_approved_by_admin_are_displayed_on_approved_page()
    {
        $this->actingAs($this->user);

        $this->post(route('attendance.update', ['id' => $this->attendance->id]), [
            'requested_clock_in' => '09:00',
            'requested_clock_out' => '18:00',
            'request_note' => 'test',
            'breaks' => [],
        ])->assertRedirect();

        $correction = AttendanceCorrection::latest('id')->first();

        // approved by admin
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'adminuser@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 1,
        ]);

        $this->actingAs($admin, 'admin');
        $this->patch(route('stamp_correction_request.approve', ['correction' => $correction->id]))->assertRedirect();

        $this->assertDatabaseHas('attendance_corrections', [
            'id' => $correction->id,
            'status' => 'approved',
        ]);

        $this->actingAs($this->user);
        $response = $this->get(route('stamp_correction_request.index', ['tab' => 'approved']));
        $response->assertOk();
        $response->assertSee((string)$correction->id);
    }

    public function test_detail_button_navigates_to_attendance_detail_page()
    {
        $this->actingAs($this->user);

        $this->post(route('attendance.update', ['id' => $this->attendance->id]), [
            'requested_clock_in' => '09:20',
            'requested_clock_out' => '18:00',
            'request_note' => 'transition test',
            'breaks' => [],
        ])->assertRedirect();

        $correction = AttendanceCorrection::latest('id')->first();

        $response = $this->get(route('stamp_correction_request.index'));
        $response->assertOk();

        $response = $this->get(route('attendance.show', ['id' => $correction->attendance_id]));
        $response->assertOk();

        $date = \Carbon\Carbon::parse($this->attendance->date);
        $response->assertSee('value="' . $date->format('Y年') . '"', false);
        $response->assertSee('value="' . $date->format('n月j日') . '"', false);
    }
}

<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAttendanceListTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $userA;
    private User $userB;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::create(2025, 8, 15, 9, 0, 0));

        $this->admin = User::factory()->admin()->create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        $this->userA = User::factory()->create([
            'name' => 'userA',
            'email' => 'usera@example.com',
            'password' => 'password123',
            'email_verified_at' => Carbon::now(),
        ]);

        $this->userB = User::factory()->create([
            'name' => 'userB',
            'email' => 'userb@example.com',
            'password' => 'password456',
            'email_verified_at' => Carbon::now(),
        ]);

        Attendance::factory()->create([
            'user_id' => $this->userA->id,
            'date' => '2025-08-15',
            'clock_in' => '2025-08-15 09:00:00',
            'clock_out' => '2025-08-15 18:00:00',
        ]);

        Attendance::factory()->create([
            'user_id' => $this->userB->id,
            'date' => '2025-08-15',
            'clock_in' => '2025-08-15 10:00:00',
            'clock_out' => '2025-08-15 19:00:00',
        ]);

        Attendance::factory()->create([
            'user_id' => $this->userA->id,
            'date' => '2025-08-14',
            'clock_in' => '2025-08-14 09:30:00',
            'clock_out' => '2025-08-14 18:30:00',
        ]);

        Attendance::factory()->create([
            'user_id' => $this->userB->id,
            'date' => '2025-08-16',
            'clock_in' => '2025-08-16 8:45:00',
            'clock_out' => '2025-08-16 17:15:00',
        ]);
    }

    public function test_all_users_attendance_of_today_are_listed_correctly()
    {
        $this->actingAs($this->admin, 'admin');

        $response = $this->get(route('admin.attendance.list'));
        $response->assertOk();

        $response->assertSee('2025/08/15');

        $response->assertSee('userA');
        $response->assertSee('09:00');
        $response->assertSee('18:00');

        $response->assertSee('userB');
        $response->assertSee('10:00');
        $response->assertSee('19:00');
    }

    public function test_current_date_is_displayed_on_initial_load()
    {
        $this->actingAs($this->admin, 'admin');

        $response = $this->get(route('admin.attendance.list'));
        $response->assertOk();

        $response->assertSee('2025/08/15');
        $response->assertSee('2025年8月15日');
    }

    public function test_previous_day_attendance_information_are_displayed_when_previous_day_button_is_clicked()
    {
        $this->actingAs($this->admin, 'admin');

        $response = $this->get(route('admin.attendance.list', ['date' => '2025-08-14']));
        $response->assertOk();

        $response->assertSee('2025/08/14');
        $response->assertSee('userA');
        $response->assertSee('09:30');
        $response->assertSee('18:30');
    }

    public function test_next_day_attendance_information_are_displayed_when_next_day_button_is_clicked()
    {
        $this->actingAs($this->admin, 'admin');

        $response = $this->get(route('admin.attendance.list', ['date' => '2025-08-16']));
        $response->assertOk();

        $response->assertSee('2025/08/16');
        $response->assertSee('userB');
        $response->assertSee('08:45');
        $response->assertSee('17:15');
    }
}

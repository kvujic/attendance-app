<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserInformationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $staffA;
    private User $staffB;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::create(2025, 8, 31, 9, 0, 0));

        $this->admin = User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'role' => User::ROLE_ADMIN,
        ]);

        $this->staffA = User::factory()->create([
            'name' => 'StaffA',
            'email' => 'staffa@example.com',
            'role' => User::ROLE_STAFF,
        ]);

        $this->staffB = User::factory()->create([
            'name' => 'StaffB',
            'email' => 'staffb@example.com',
            'role' => User::ROLE_STAFF,
        ]);

        Attendance::factory()->create([
            'user_id' => $this->staffA->id,
            'date' => '2025-07-15',
            'clock_in' => '2025-07-15 09:00:00',
            'clock_out' => '2025-07-15 18:00:00',
        ]);

        Attendance::factory()->create([
            'user_id' => $this->staffA->id,
            'date' => '2025-07-20',
            'clock_in' => '2025-07-20 08:00:00',
            'clock_out' => '2025-07-20 17:00:00',
        ]);

        Attendance::factory()->create([
            'user_id' => $this->staffA->id,
            'date' => '2025-08-31',
            'clock_in' => '2025-08-31 09:00:00',
            'clock_out' => '2025-08-31 18:00:00',
        ]);
    }

    public function test_all_general_users_name_and_email_are_displayed_on_staff_list()
    {
        $this->actingAs($this->admin, 'admin');

        $response = $this->get(route('admin.staff.list'));

        $response->assertOk();
        $response->assertSee('StaffA');
        $response->assertSee('staffa@example.com');
        $response->assertSee('StaffB');
        $response->assertSee('staffb@example.com');

        $response->assertDontSee('admin@example.com');
    }

    public function test_selected_users_attendance_lists_for_month_are_displayed()
    {
        $this->actingAs($this->admin, 'admin');

        $response = $this->get(route('admin.attendance.staff', [
            'id' => $this->staffA->id,
            'month' => '2025-07',
        ]));

        $response->assertOk();
        $response->assertSee('2025/07');
        $response->assertSee('07/15');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('07/20');
        $response->assertSee('08:00');
        $response->assertSee('17:00');
    }

    public function test_previous_month_attendances_information_are_displayed_when_previous_month_button_is_clicked()
    {
        $this->actingAs($this->admin, 'admin');

        $this->get(route('admin.attendance.staff', [
            'id' => $this->staffA->id,
            'month' => '2025-08',
        ]))->assertOk()->assertSee('2025/08');

        $response = $this->get(route('admin.attendance.staff', [
            'id' => $this->staffA->id,
            'month' => '2025-07',
        ]));

        $response->assertOk();
        $response->assertSee('2025/07');
        $response->assertSee('07/15');
        $response->assertSee('07/20');
    }

    public function test_next_month_attendances_information_are_displayed_when_next_month_button_is_clicked()
    {
        $this->actingAs($this->admin, 'admin');

        $this->get(route('admin.attendance.staff', [
            'id' => $this->staffA->id,
            'month' => '2025-07'
        ]))->assertOk()->assertSee('2025/07');

        $response = $this->get(route('admin.attendance.staff', [
            'id' => $this->staffA->id,
            'month' => '2025-08',
        ]));

        $response->assertOk();
        $response->assertSee('2025/08');
        $response->assertSee('08/31');
    }

    public function test_detail_link_navigate_to_attendance_detail_of_that_day()
    {
        $this->actingAs($this->admin, 'admin');

        $attendance = Attendance::where('user_id', $this->staffA->id)
            ->whereDate('date', '2025-08-31')
            ->firstOrFail();

        $response = $this->get(route('admin.attendance.showDetail', ['id' => $attendance->id]));
        $response->assertOk();

        $date = \Carbon\Carbon::parse($attendance->date);
        $response->assertSee('value="' . $date->format('Y年') . '"', false);
        $response->assertSee('value="' . $date->format('n月j日') . '"', false);
        $response->assertSee('09:00');
        $response->assertSee('18:00');
    }
}

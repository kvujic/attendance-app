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

    private function ymd(?Carbon $d = null): string {
        return ($d ?? Carbon::today())->format('Y/m/d');
    }

    private function jpYnj(?Carbon $d = null): string {
        return ($d ?? Carbon::today())->format('Y年n月j日');
    }

    private function hm(int $h, int $m): Carbon {
        return Carbon::today()->setTime($h, $m, 0);
    }

    private function ymdDate(string $ymd): Carbon {
        return Carbon::parse($ymd);
    }

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
            'date' => Carbon::today()->toDateString(),
            'clock_in' => $this->hm(9, 0),
            'clock_out' => $this->hm(18, 0),
        ]);

        Attendance::factory()->create([
            'user_id' => $this->userB->id,
            'date' => Carbon::today()->toDateString(),
            'clock_in' => $this->hm(10, 0),
            'clock_out' => $this->hm(19, 0),
        ]);

        Attendance::factory()->create([
            'user_id' => $this->userA->id,
            'date' => Carbon::yesterday()->toDateString(),
            'clock_in' => Carbon::yesterday()->setTime(9, 30, 0),
            'clock_out' => Carbon::yesterday()->setTime(18, 30, 0),
        ]);

        Attendance::factory()->create([
            'user_id' => $this->userB->id,
            'date' => Carbon::tomorrow()->toDateString(),
            'clock_in' => Carbon::tomorrow()->setTime(8, 45, 0),
            'clock_out' => Carbon::tomorrow()->setTime(17, 15, 0),
        ]);
    }

    public function test_all_users_attendance_of_today_are_listed_correctly()
    {
        $this->actingAs($this->admin, 'admin');

        $response = $this->get(route('admin.attendance.list'));
        $response->assertOk();

        $response->assertSee($this->ymd());

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

        $response->assertSee($this->ymd());
        $response->assertSee($this->jpYnj());
    }

    public function test_previous_day_attendance_information_are_displayed_when_previous_day_button_is_clicked()
    {
        $this->actingAs($this->admin, 'admin');

        $date = Carbon::yesterday();
        $response = $this->get(route('admin.attendance.list', ['date' => $date->toDateString()]));
        $response->assertOk();

        $response->assertSee($this->ymd($date));
        $response->assertSee('userA');
        $response->assertSee('09:30');
        $response->assertSee('18:30');
    }

    public function test_next_day_attendance_information_are_displayed_when_next_day_button_is_clicked()
    {
        $this->actingAs($this->admin, 'admin');

        $date = Carbon::tomorrow();
        $response = $this->get(route('admin.attendance.list', ['date' => $date->toDateString()]));
        $response->assertOk();

        $response->assertSee($this->ymd($date));
        $response->assertSee('userB');
        $response->assertSee('08:45');
        $response->assertSee('17:15');
    }
}

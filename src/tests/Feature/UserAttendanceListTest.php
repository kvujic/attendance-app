<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class UserAttendanceListTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::create(2025, 8, 15, 9, 0, 0, 'Asia/Tokyo'));
        $this->user = User::factory()->create();
    }

    public function test_all_attendance_information_are_displayed()
    {
        $this->actingAs($this->user);

        Attendance::factory()->onDate(Carbon::create(2025, 8, 1))->create(['user_id' => $this->user->id]);
        Attendance::factory()->onDate(Carbon::create(2025, 8, 2))->create(['user_id' => $this->user->id]);

        $response = $this->get(route('attendance.list'));
        $response->assertOk();
        $response->assertSee('08/01');
        $response->assertSee('08/02');
    }

    public function test_current_month_is_displayed_on_attendance_list()
    {
        $this->actingAs($this->user);

        $response = $this->get(route('attendance.list'));
        $response->assertOk();
        $response->assertSee(Carbon::now()->format('Y/m'));
    }

    public function test_previous_month_attendances_information_are_displayed_when_previous_month_button_is_clicked()
    {
        Attendance::factory()->create([
            'user_id' => $this->user->id,
            'date' => '2025-07-31',
            'clock_in' => '2025-07-31 09:00:00',
            'clock_out' => '2025-07-31 18:00:00',
        ]);

        $this->actingAs($this->user);
        $response = $this->get(route('attendance.list', ['month' => '2025-07']));

        $response->assertOk();
        $response->assertSee('2025/07');
        $response->assertSee('07/31');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
    }

    public function test_next_month_attendance_information_are_displayed_when_next_month_button_is_clicked()
    {
        Attendance::factory()->create([
            'user_id' => $this->user->id,
            'date' => '2025-09-01',
            'clock_in' => '2025-09-01 09:00:00',
            'clock_out' => '2025-09-01 18:00:00',
        ]);

        $this->actingAs($this->user);
        $response = $this->get(route('attendance.list', ['month' => '2025-09']));

        $response->assertOk();
        $response->assertSee('2025/09');
        $response->assertSee('09/01');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
    }

    public function test_can_navigate_to_attendance_detail()
    {
        $this->actingAs($this->user);
        $attendance = Attendance::factory()->onDate(carbon::create(2025, 8, 1))->create(['user_id' => $this->user->id]);

        $response = $this->get(route('attendance.show', $attendance->id));
        $response->assertOk();
        $response->assertSee('2025-08-01');
        $response->assertSee(Carbon::parse($attendance->clock_in)->format('H:i'));
    }
}

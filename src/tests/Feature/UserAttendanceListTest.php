<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserAttendanceListTest extends TestCase
{
    use RefreshDatabase;

    private const BASE_NOW = '2025-08-15 09:00:00';

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse(self::BASE_NOW, 'Asia/Tokyo'));
        $this->user = User::factory()->create();
    }

    public function test_all_attendance_information_are_displayed()
    {
        $this->actingAs($this->user);

        Attendance::factory()->onDate($this->d('2025-08-01'))->create(['user_id' => $this->user->id]);
        Attendance::factory()->onDate($this->d('2025-08-02'))->create(['user_id' => $this->user->id]);

        $response = $this->get(route('attendance.list'));
        $response->assertOk();
        $response->assertSee($this->md($this->d('2025-08-01')));
        $response->assertSee($this->md($this->d('2025-08-02')));
    }

    public function test_current_month_is_displayed_on_attendance_list()
    {
        $this->actingAs($this->user);

        $response = $this->get(route('attendance.list'));
        $response->assertOk();
        $response->assertSee($this->ym(Carbon::now()));
    }

    public function test_previous_month_attendances_information_are_displayed_when_previous_month_button_is_clicked()
    {
        $day = $this->d('2025-07-31');
        Attendance::factory()->create([
            'user_id' => $this->user->id,
            'date' => $day->toDateString(),
            'clock_in' => $day->copy()->setTime(9, 0)->toDateTimeString(),
            'clock_out' => $day->copy()->setTime(18, 0)->toDateTimeString(),
        ]);

        $this->actingAs($this->user);
        $response = $this->get(route('attendance.list', ['month' => '2025-07']));

        $response->assertOk();
        $response->assertSee($this->ym($day));
        $response->assertSee($this->md($day));
        $response->assertSee('09:00');
        $response->assertSee('18:00');
    }

    public function test_next_month_attendance_information_are_displayed_when_next_month_button_is_clicked()
    {
        $day = $this->d('2025-09-01');
        Attendance::factory()->create([
            'user_id' => $this->user->id,
            'date' => $day->toDateString(),
            'clock_in' => $day->copy()->setTime(9, 0)->toDateTimeString(),
            'clock_out' => $day->copy()->setTime(18, 0)->toDateTimeString(),
        ]);

        $this->actingAs($this->user);
        $response = $this->get(route('attendance.list', ['month' => '2025-09']));

        $response->assertOk();
        $response->assertSee($this->ym($day));
        $response->assertSee($this->md($day));
        $response->assertSee('09:00');
        $response->assertSee('18:00');
    }

    public function test_can_navigate_to_attendance_detail()
    {
        $this->actingAs($this->user);

        $day = $this->d('2025-08-01');
        $attendance = Attendance::factory()->onDate($day)->create(['user_id' => $this->user->id]);

        $response = $this->get(route('attendance.show', $attendance->id));
        $response->assertOk();
        $response->assertSee($day->toDateString());
        $response->assertSee(Carbon::parse($attendance->clock_in)->format('H:i'));
    }

    private function d(string $ymd): Carbon
    {
        return Carbon::parse($ymd, 'Asia/Tokyo');
    }

    private function ym(Carbon $d): string
    {
        return $d->format('Y/m');
    }

    private function md(Carbon $d): string
    {
        return $d->format('m/d');
    }
}

<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;
use Livewire\Livewire;
use App\Livewire\AttendanceList;
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

    public function test_previous_month_attendances_are_displayed_via_livewire()
    {
        Attendance::factory()->onDate(Carbon::create(2025, 7, 31))->create(['user_id' => $this->user->id]);

        Livewire::actingAs($this->user)
            ->test(AttendanceList::class)
            ->call('previousMonth')
            ->assertSee('2025/07')
            ->assertSee('07/31');
    }

    public function test_next_month_attendances_are_displayed_via_livewire()
    {
        Attendance::factory()->onDate(Carbon::create(2025, 9, 1))->create(['user_id' => $this->user->id]);

        Livewire::actingAs($this->user)
            ->test(AttendanceList::class)
            ->call('nextMonth')
            ->assertSee('2025/09')
            ->assertSee('09/01');
    }

    public function test_can_navigate_to_attendance_detail_via_livewire()
    {
        $attendance = Attendance::factory()->onDate(carbon::create(2025, 8, 1))->create(['user_id' => $this->user->id]);

        Livewire::actingAs($this->user)
            ->test(AttendanceList::class)
            ->assertSee('/attendance/' . $attendance->id)
            ->assertSee('詳細');
    }
}

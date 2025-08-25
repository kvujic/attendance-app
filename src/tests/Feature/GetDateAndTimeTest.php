<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;

class GetDateAndTimeTest extends TestCase
{
    use RefreshDatabase;

    public function test_current_datetime_is_displayed_on_attendance_record_screen(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $now = Carbon::now();
        $weekdays = ['日', '月', '火', '水', '木', '金', '土'];

        $expectedDate = $now->format("Y年n月j日") . '(' . $weekdays[$now->dayOfWeek] . ')';
        $expectedTime = $now->format('H:i');

        $response = $this->get('/attendance');

        $response->assertStatus(200);
        $response->assertSee($expectedDate);
        $response->assertSee($expectedTime);
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Attendance;
use App\Models\AttendanceCorrection;
use Carbon\Carbon;
use Illuminate\Support\Arr;

class AttendanceCorrectionsTableSeeder extends Seeder
{

    private const SAMPLE_COUNT = 8;
    private const OFFSET_IN_CHOICES = [5, 10, 15, 20];
    private const OFFSET_OUT_CHOICES = [0, 5, 10];
    private const OFFSET_SIGNS = [1, -1];
    private const MIN_OUT_EXTENSION = 5;

    public function run(): void
    {
        $attendances = Attendance::query()
            ->whereNotNull('clock_in')
            ->whereNotNull('clock_out')
            ->inRandomOrder()
            ->take(self::SAMPLE_COUNT)
            ->get();

        foreach ($attendances as $attendance) {
            if ($attendance->attendanceCorrections()->exists()) {
                continue;
            }

            $in = Carbon::parse($attendance->clock_in);
            $out = Carbon::parse($attendance->clock_out);

            $offsetIn = Arr::random(self::OFFSET_IN_CHOICES) * Arr::random(self::OFFSET_SIGNS);
            $offsetOut = Arr::random(self::OFFSET_OUT_CHOICES) * Arr::random(self::OFFSET_SIGNS);

            $requestIn = $in->copy()->addMinutes($offsetIn);
            $requestOut = $out->copy()->addMinutes($offsetOut);

            if ($requestIn->gte($requestOut)) {
                $requestIn = $in->copy();
                $requestOut = $out->copy()->addMinutes(max(self::MIN_OUT_EXTENSION, abs($offsetOut)));
                $offsetIn = 0;
                $offsetOut = $requestOut->diffInMinutes($out);
            }

            $reasonsInLate   = ['交通遅延のため遅れてしまいました', '入館時に混雑して遅れてしまいました', '体調不良により出勤が遅れました'];
            $reasonsInEarly  = ['出勤打刻を早めに押してしまいました'];
            $reasonsOutLate  = ['会議が長引いたため退勤が遅れました', '引継ぎ対応のため残業しました', '顧客対応で遅くなりました'];
            $reasonsOutEarly = ['退勤を誤って打刻しました'];

            $candidateIn = null;
            if ($offsetIn !== 0) {
                $candidateIn = $offsetIn > 0
                    ? Arr::random($reasonsInLate)
                    : Arr::random($reasonsInEarly);
            }

            $candidateOut = null;
            if ($offsetOut !== 0) {
                $candidateOut = $offsetOut > 0
                    ? Arr::random($reasonsOutLate)
                    : Arr::random($reasonsOutEarly);
            }

            if ($candidateIn && $candidateOut) {
                $requestNote = (abs($offsetIn) >= abs($offsetOut)) ? $candidateIn : $candidateOut;
            } elseif ($candidateIn) {
                $requestNote = $candidateIn;
            } elseif ($candidateOut) {
                $requestNote = $candidateOut;
            } else {
                $requestNote = '休憩時間を変更しました';
            }

            $status = Arr::random(['pending', 'approved']);

            AttendanceCorrection::factory()->create([
                'user_id' => $attendance->user->id,
                'attendance_id' => $attendance->id,
                'requested_clock_in' => $requestIn,
                'requested_clock_out' => $requestOut,
                'status' => $status,
                'request_note' => $requestNote,
            ]);
        }
    }
}



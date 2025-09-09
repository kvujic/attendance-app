<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\AttendanceRequest;
use App\Models\Attendance;
use App\Models\AttendanceCorrection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CorrectionController extends Controller
{

    public function update(AttendanceRequest $request, $id)
    {
        $validated = $request->validated();

        if ($id !== 'new') {
            $attendance = Attendance::where('id', $id)
                ->where('user_id', Auth::id())
                ->firstOrFail();
            $targetDate = Carbon::parse($attendance->date)->toDateString();
        } else {
            $targetDate = Carbon::parse($request->input('date'))->toDateString();
            $attendance = Attendance::firstOrCreate(
                ['user_id' => Auth::id(), 'date' => $targetDate],
                ['clock_in' => null, 'clock_out' => null]
            );
        }

        DB::transaction(function () use ($attendance, $validated, $targetDate) {
            $toDateTime = function (?string $hm) use ($targetDate) {
                if (!$hm) return null;
                return Carbon::createFromFormat('Y-m-d H:i', "{$targetDate} {$hm}")
                    ->setSecond(0)->format('Y-m-d H:i:s');
            };

            $requestIn = $toDateTime($validated['requested_clock_in'] ?? null);
            $requestOut = $toDateTime($validated['requested_clock_out'] ?? null);

            $correction = AttendanceCorrection::create([
                'user_id' => Auth::id(),
                'attendance_id' => $attendance->id,
                'requested_clock_in' => $requestIn,
                'requested_clock_out' => $requestOut,
                'request_note' => $validated['request_note'] ?? null,
                'status' => 'pending'
            ]);

            foreach (($validated['breaks'] ?? []) as $b) {
                $s = $b['requested_break_start'] ?? null;
                $e = $b['requested_break_end'] ?? null;
                if ($s && $e) {
                    $start = $s ? Carbon::createFromFormat('Y-m-d H:i', "{$targetDate} {$s}")->setSecond(0)->format('Y-m-d H:i:s') : null;
                    $end = $e ? Carbon::createFromFormat('Y-m-d H:i', "{$targetDate} {$e}")->setSecond(0)->format('Y-m-d H:i:s') : null;
                    $correction->correctionBreaks()->create([
                        'requested_break_start' => $start,
                        'requested_break_end' => $end,
                    ]);
                }
            }
        });

        return redirect()->route('attendance.show', ['id' => 'new', 'date' => $targetDate]);
    }

    public function index(Request $request) {

        $userId = auth()->id();
        $tab = $request->input('tab', 'pending');

        $base = AttendanceCorrection::with(['user', 'attendance'])
            ->where('user_id', $userId)
            ->orderByDesc('created_at');

        if ($tab === 'approved') {
            $corrections = (clone $base)->where('status', 'approved')->get();
            $readOnly = true;
        } else {
            $corrections = (clone $base)->where('status', 'pending')->get();
            $readOnly = false;
        }

        return view('user.user_correction_list', [
            'corrections' => $corrections,
            'tab' => $tab,
            'readOnly' => $readOnly,
        ]);
    }

}


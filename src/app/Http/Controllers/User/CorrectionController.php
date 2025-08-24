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

            if (Carbon::parse($attendance->date)->isFuture()) {
                return back()->withErrors([
                    'date' => '未来日の勤怠は修正できません',
                ])->withInput();
            }
            $targetDate = Carbon::parse($attendance->date)->toDateString();
        } else {
            $targetDate = Carbon::parse($request->date)->toDateString();

            // user_id + date = unique(?)
            $attendance = Attendance::firstOrCreate(
                ['user_id' => Auth::id(), 'date' => $targetDate],
                ['clock_in' => null, 'clock_out' => null]
            );
        }

        //transaction
        \DB::transaction(function () use ($request, $attendance) {
            $workDate = Carbon::parse($attendance->date)->toDateString();

            $reqIn = $request->filled('requested_clock_in')
                ? Carbon::parse($workDate . ' ' . $request->requested_clock_in)->format('Y-m-d H:i:s')
                : null;
            $reqOut = $request->filled('requested_clock_out')
                ? Carbon::parse($workDate . ' ' . $request->requested_clock_out)->format('Y-m-d H:i:s')
                : null;

            $correction = AttendanceCorrection::create([
                'user_id' => Auth::id(),
                'attendance_id' => $attendance->id,
                'requested_clock_in' => $reqIn,
                'requested_clock_out' => $reqOut,
                'request_note' => $request->request_note,
                'status' => 'pending',
            ]);

            foreach ((array) $request->breaks as $b) {
                $s = $b['requested_break_start'] ?? null;
                $e = $b['requested_break_end'] ?? null;
                if ($s && $e) {
                    $correction->correctionBreaks()->create([
                        'requested_break_start' => Carbon::parse($workDate . ' ' . $s)->format('Y-m-d H:i:s'),
                        'requested_break_end' => Carbon::parse($workDate . ' ' . $e)->format('Y-m-d H:i:s'),
                    ]);
                }
            }
        });

        return redirect()->route('attendance.show', ['id' => 'new', 'date' => $targetDate]);
    }

    public function index(Request $request) {

        $userId = auth()->id();
        $tab = $request->input('tab', 'pending');

        if ($tab === 'approved') {
            $corrections = AttendanceCorrection::with(['user', 'attendance'])
                ->where('user_id', $userId)
                ->where('status', 'approved')
                ->orderBy('created_at', 'desc')
                ->get();
        } else {
            $corrections = AttendanceCorrection::with(['user', 'attendance'])
                ->where('user_id', $userId)
                ->where('status', 'pending')
                ->orderBy('created_at', 'desc')
                ->get();
        }

        return view('user.user_correction_list', [
            'corrections' => $corrections,
            'tab' => $tab,
        ]);
    }

}


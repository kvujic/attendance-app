<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\AttendanceRequest;
use App\Models\Attendance;
use App\Models\AttendanceCorrection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class CorrectionController extends Controller
{

    public function update(AttendanceRequest $request, $id)
    {
        \Log::debug('update() 入った', ['id' => $id, 'request' => $request->all()]);

        if ($id !== 'new') {
            $attendance = Attendance::where('id', $id)
                ->where('user_id', Auth::id())
                ->firstOrFail();

            $targetDate = Carbon::parse($attendance->date)->toDateString();

            if (Carbon::parse($attendance->date)->isFuture()) {
                return back()->withErrors([
                    'date' => '未来日の勤怠は修正できません',
                ])->withInput();
            }
        } else {
            // apply after create dummy
            if (!$request->filled('date')) {
                return back()->withErrors([
                    'date' => '日付は必須です',
                ])->withInput();
            }
            $targetDate = Carbon::parse($request->date)->toDateString();

            if (Carbon::parse($targetDate)->isFuture()) {
                return back()->withErrors([
                    'date' => '未来日の勤怠は修正できません',
                ])->withInput();
            }

            // user_id + date = unique(?)
            $attendance = Attendance::firstOrCreate(
                ['user_id' => Auth::id(), 'date' => $targetDate],
                ['clock_in' => null, 'clock_out' => null]
            );
        }

        //transaction
        \DB::transaction(function () use ($request, $attendance) {
            $reqIn = $request->filled('requested_clock_in')
                ? Carbon::parse($request->requested_clock_in)->format('H:i')
                : null;
            $reqOut = $request->filled('requested_clock_out')
                ? Carbon::parse($request->requested_clock_out)->format('H:i')
                : null;

            $correction = AttendanceCorrection::create([
                'user_id' => Auth::id(),
                'attendance_id' => $attendance->id,
                'requested_clock_in' => $reqIn,
                'requested_clock_out' => $reqOut,
                'note' => $request->note,
                'status' => 'pending',
            ]);

            foreach ((array) $request->breaks as $b) {
                $s = $b['requested_break_start'] ?? null;
                $e = $b['requested_break_end'] ?? null;
                if ($s && $e) {
                    $correction->correctionBreak()->create([
                        'requested_break_start' => Carbon::parse($s)->format('H:i'),
                        'requested_break_end' => Carbon::parse($e)->format('H:i'),
                    ]);
                }
            }
        });


        /*

        \Log::debug('correction 作成', ['id' => $correction->id ?? '失敗']);

        if ($request->has('breaks')) {
            foreach ($request->breaks as $break) {
                if (!empty($break['requested_break_start']) && !empty($break['requested_break_end'])) {
                    \Log::debug('break 登録開始', $break);
                    $correction->correctionBreaks()->create([
                        'requested_break_start' => $break['requested_break_start'] ?? null,
                        'requested_break_end' => $break['requested_break_end'] ?? null,
                    ]);
                }
            }
        }
            */

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

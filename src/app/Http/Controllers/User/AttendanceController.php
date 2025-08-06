<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\AttendanceRequest;
use App\Models\Attendance;
use App\Models\AttendanceCorrection;
use App\Models\User;
use App\Models\BreakTime;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AttendanceController extends Controller
{
    public function create() 
    {
        $user = Auth::user();
        $today = now()->toDateString();

        $attendance = Attendance::where('user_id', $user->id)
        ->whereDate('clock_in', $today)
        ->first();

        if (!$attendance) {
            $status = 'outside_work';
        } elseif ($attendance->clock_out) {
            $status = 'after_work';
        } elseif ($attendance->isOnBreak()) {
            $status = 'on_break';
        } else {
            $status = 'working';
        }

        return view('user.user_attendance_record', ['attendanceStatus' => $status,]);
    }

    public function store (Request $request) 
    {
        $user = Auth::user();
        $today = now()->toDateString();
        $now = Carbon::now();

        $attendance = Attendance::firstOrCreate(
            ['user_id' => $user->id, 'date' => $today],
            ['clock_in' => $now, 'date' => $today]
        );

        switch ($request->input('action')) {
            case 'clock_in':
                if (!$attendance->clock_in) {
                    $attendance->clock_in = $now;
                    $attendance->save();
                }
                break;

            case 'break_start':
                $attendance->breakTimes()->create([
                    'break_start' => $now,
                ]);
                break;

            case 'break_end':
                $lastBreak = $attendance->breakTimes()->whereNull('break_end')->latest()->first();
                if ($lastBreak) {
                    $lastBreak->break_end = $now;
                    $lastBreak->save();

                    $attendance->load('breakTimes');

                    //Log::debug('休憩合計を計算開始');

                    $totalBreak = $attendance->breakTimes->sum(function ($break) {
                        if ($break->break_start && $break->break_end) {
                            return \Carbon\Carbon::parse($break->break_end)->diffInMinutes(\Carbon\Carbon::parse($break->break_start));
                        }
                        return 0;
                    });

                    //Log::debug('計算結果:', ['totalBreak' => $totalBreak]);

                    $attendance->total_break_time = $totalBreak;
                    $attendance->save();
                }
                break;

            case 'clock_out':
                if (!$attendance->clock_out) {
                    $attendance->clock_out = $now;

                    $attendance->load('breakTimes');

                    if ($attendance->clock_in) {
                        $worked = \Carbon\Carbon::parse($now)->diffInMinutes(\Carbon\Carbon::parse($attendance->clock_in));
                        $attendance->total_work_time = max(0, $worked - ($attendance->total_break_time ?? 0));
                    }
                    $attendance->save();
                }
                break;
        }
        return redirect()->route('attendance.create');
    }



    public function index() 
    {
        return view('user.user_attendance_list');
    }

    public function show ($id, Request $request)
    {
        $user = Auth::user();
        $date = $request->input('date');

        if ($id === 'new') {
            $date = $request->input('date');

            //check attendance data exist or not
            $attendance = Attendance::where('user_id', $user->id)
                ->whereDate('date', $date)
                ->with('breakTimes')
                ->first();

            //pass like empty object data if not exist
            if (!$attendance) {
                $attendance = new Attendance([
                    'user_id' => $user->id,
                    'date' => $date,
                    'clock_in' => null,
                    'clock_out' => null,
                ]);
                $attendance->exists = false;
            }

            $breaksRow = old('breaks') ?? $attendance->breakTimes;
            $breaks = [];

            foreach ($breaksRow as $i => $break) {
                if (is_array($break)) {
                    $breaks[] = [
                        'start' => $break['start'] ?? '',
                        'end' => $break['end'] ?? '',
                    ];
                } else {
                    $breaks[] = [
                        'start' => $break->break_start ? \Carbon\Carbon::parse($break->break_start)->format('H:i') : '',
                        'end' => $break->break_end ? \Carbon\Carbon::parse($break->break_end)->format('H:i') : '',
                    ];
                }
            }

            if (count($breaks) === 0) {
            $breaks[] = ['start' => '', 'end' => ''];
            }

            $isPending = false;

            return view('user.user_attendance_detail', [
                'attendance' => $attendance,
                'user' => $user,
                'date' => $date,
                'breaks' => $breaks,
                'breakCount' => count($breaks),
                'isPending' => $isPending,
            ]);
        }

        $attendance = Attendance::with('breakTimes')->findOrFail($id);

        $breaksRow = old('breaks') ?? $attendance->breakTimes;
        $breaks = [];

        foreach ($breaksRow as $break) {
            $breaks[] = [
                'start' => is_array($break) ? ($break['start'] ?? '') : ($break->break_start ? Carbon::parse($break->break_start)->format('H:i') : ''),
                'end' => is_array($break) ? ($break['end'] ?? '') : ($break->break_end ? Carbon::parse($break->break_end)->format('H:i') : ''),

            ];
        }

        if (count($breaks) === 0) {
            $breaks[] = ['start' => '', 'end' => ''];
        }

        $breakCount = count($breaks);

        $isPending = AttendanceCorrection::where('attendance_id', $attendance->id)
            ->where('status', 'pending')
            ->exists();

        return view('user.user_attendance_detail', compact('attendance', 'user', 'breaks', 'breakCount', 'isPending'));
    }

}


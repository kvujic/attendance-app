<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
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
        ->whereDate('date', $today)
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

                    $totalBreak = $attendance->breakTimes->sum(function ($break) {
                        if ($break->break_start && $break->break_end) {
                            return \Carbon\Carbon::parse($break->break_end)->diffInMinutes(\Carbon\Carbon::parse($break->break_start));
                        }
                        return 0;
                    });

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

        if ($id === 'new') {
            $date = $request->input('date');
            //check attendance data exist or not
            $attendance = Attendance::where('user_id', $user->id)
                ->whereDate('date', $date)
                ->with(['breakTimes' => fn($q) => $q->orderBy('break_start')])
                ->first();

            //pass like empty object (dummy) data if not exist
            if (!$attendance) {
                $attendance = new Attendance([
                    'user_id' => $user->id,
                    'date' => $date,
                    'clock_in' => null,
                    'clock_out' => null,
                ]);
                $attendance->exists = false;
            }

            //pending priority
            $correction = $attendance->exists
                ? AttendanceCorrection::where('attendance_id', $attendance->id)
                    ->where('status', 'pending')
                    ->with(['correctionBreaks' => fn($q) => $q->orderBy('requested_break_start')])
                    ->first()
                : null;

            $isPending = $correction?->status === 'pending';

                // display (old > application > stamp)
                $oldBreaks = old('breaks');
                if (!empty($oldBreaks)) {
                    $breaksSource = $oldBreaks; //array
                } elseif ($correction && ($correction->correctionBreaks?->isNotEmpty())) {
                    $breaksSource = $correction->correctionBreaks; //correction
                } else {
                    $breaksSource = $attendance->breakTimes; //correction
                }

                // changes to requested_* all
                $breaks = $this->mapBreaksToRequested($breaksSource);

                if (empty($breaks) && !$isPending) {
                    $breaks[] = ['requested_break_start' => '', 'requested_break_end' => ''];
                }

            $requestedClockIn = old('requested_clock_in') ?? ($correction->requested_clock_in ?? $attendance->clock_in);
            $requestedClockOut = old('requested_clock_out') ?? ($correction->requested_clock_out ?? $attendance->clock_out);

            $requestedClockIn = $this->fmt($requestedClockIn);
            $requestedClockOut = $this->fmt($requestedClockOut);

            return view('user.user_attendance_detail', [
                'attendance' => $attendance,
                'user' => $user,
                'date' => $date,
                'breaks' => $breaks,
                'breakCount' => count($breaks),
                'isPending' => $isPending,
                'id' => $id,
                'correction' => $correction,
                'requestedClockIn' => $requestedClockIn,
                'requestedClockOut' => $requestedClockOut,
            ]);
        }

        // existing record details
        $attendance = Attendance::with(['breakTimes' => fn($q) => $q->orderBy('break_start')])->findOrFail($id);

        // pending priority, latest application if pending is not exist, or null
        $correction = AttendanceCorrection::where('attendance_id', $attendance->id)
            ->with(['correctionBreaks' => fn($q) => $q->orderBy('requested_break_start')])
            ->orderByDesc('created_at')
            ->first();

        $isPending = $correction?->status === 'pending';

        // display (old > application > stamp)
        $oldBreaks = old('breaks');
        if (!empty($oldBreaks)) {
            $breaksSource = $oldBreaks;
        } elseif ($correction && ($correction->correctionBreaks?->isNotEmpty())) {
            $breaksSource = $correction->correctionBreaks;
        } else {
            $breaksSource = $attendance->breakTimes;
        }

        $breaks = $this->mapBreaksToRequested($breaksSource);

        if (empty($breaks) && !$isPending) {
            $breaks[] = ['requested_break_start' => '', 'requested_break_end' => ''];
        }

        $requestedClockIn = old('requested_clock_in') ?? ($correction->requested_clock_in ?? $attendance->clock_in);
        $requestedClockOut = old('requested_clock_out') ?? ($correction->requested_clock_out ?? $attendance->clock_out);

        $requestedClockIn = $this->fmt($requestedClockIn);
        $requestedClockOut = $this->fmt($requestedClockOut);

        return view('user.user_attendance_detail', [
            'attendance' => $attendance,
            'user' => $user,
            'breaks' => $breaks,
            'breakCount' => count($breaks),
            'isPending' => $isPending,
            'id' => $id,
            'correction' => $correction,
            'requestedClockIn' => $requestedClockIn,
            'requestedClockOut' => $requestedClockOut,
        ]);
    }

     //align break-row to requested_* key for display
        private function mapBreaksToRequested($rows): array
        {
            if (empty($rows)) return []; //[['requested_break_start' => '', 'requested_break_end' => '']];
            $out = [];

            foreach ($rows as $row) {
                if (is_array($row)) {
                    $out[] = [
                        'requested_break_start' => $row['requested_break_start'] ?? ($row['start'] ?? ''),
                        'requested_break_end' => $row['requested_break_end'] ?? ($row['end'] ?? ''),
                    ];
                } elseif ($row instanceof \App\Models\CorrectionBreak) {
                    $out[] = [
                        'requested_break_start' => $this->fmt($row->requested_break_start),
                        'requested_break_end' => $this->fmt($row->requested_break_end),
                    ];
                } else {
                    //BreakTime
                    $out[] = [
                        'requested_break_start' => $this->fmt($row->break_start),
                        'requested_break_end' => $this->fmt($row->break_end),
                    ];
                }
            }
            if (empty($out)) {
                $out[] = ['requested_break_start' => '', 'requested_break_end' => ''];
            }
            return $out;
        }

        // H:i if there is data, '' if not
        private function fmt($v): string
        {
            if (empty($v)) return '';
            try {
                return Carbon::parse($v)->format('H:i');
            } catch (\Throwable $e) {
                return '';
            }
        }

}


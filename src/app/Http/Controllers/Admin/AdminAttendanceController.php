<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceCorrection;
use App\Models\Attendance;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AdminAttendanceController extends Controller
{
    public function index() {
        
        return view('admin.admin_attendance_list');
    }

    public function showDetail($id, Request $request)
    {

        if ($id === 'new') {
            $date = $request->input('date');
            $userId = (int) $request->input('user_id');

            Log::debug('Admin showDetail(new)', [
                'full_url' => $request->fullUrl(),
                'user_id'  => $userId,
                'date'     => $date,
            ]);

            abort_unless($userId && $date, 404);
            $user = User::findOrFail($userId);
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

            return view('admin.admin_attendance_detail', [
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

        $user = $attendance->user;

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

        return view('admin.admin_attendance_detail', [
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



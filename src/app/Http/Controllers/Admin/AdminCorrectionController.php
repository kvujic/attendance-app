<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\AttendanceCorrection;
use App\Models\BreakTime;
use App\Models\User;
use App\Http\Requests\AttendanceRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdminCorrectionController extends Controller
{
    public function index(Request $request)
    {
        // tab
        $tab = $request->string('tab')->toString();
        if (! in_array($tab, ['pending', 'approved'], true)) {
            $tab = 'pending';
        }
        // all users are target
        $corrections = AttendanceCorrection::query()
            ->with([
                'user:id,name',
                'attendance:id,date',
            ])
            ->where('status', $tab)
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($correction) {
                $correction->target_date = optional($correction->attendance)->date
                    ? Carbon::parse($correction->attendance->date)->format('Y/m/d')
                    : null;

                return $correction;
            });

        return view('admin.admin_correction_list', [
            'corrections' => $corrections,
            'tab' => $tab,
        ]);
    }

    public function showCorrection(AttendanceCorrection $correction)
    {
        abort_unless(Auth::guard('admin')->check(), 403);
        $correction->load(['user', 'attendance', 'correctionBreaks']);

        $correction->requested_clock_in
            ? \Carbon\Carbon::parse($correction->requested_clock_in)->format('H:i')
            : '';
        $correction->requested_clock_out
            ?\Carbon\Carbon::parse($correction->requested_clock_out)->format('H:i')
            : '';

        $breaks = $correction->correctionBreaks->map(function ($b) {
            return [
                'requested_break_start' => \Carbon\Carbon::parse($b->requested_break_start)->format('H:i'),
                'requested_break_end' => \Carbon\Carbon::parse($b->requested_break_end)->format('H:i'),
            ];
        })->values()->all();

        if(empty($breaks)) {
            $breaks[] = ['requested_break_start' => '', 'requested_break_end' => ''];
        }

        //$isPending = $correction->status === 'pending',

        return view('admin.admin_correction_approve', [
            'correction' => $correction,
            'isReadOnly' => true,
        ]);
    }

    public function approve(Request $request, AttendanceCorrection $correction)
    {
        abort_unless(Auth::guard('admin')->check(), 403);

        if ($correction->status !== 'pending') {
            return back();
        }

        // attendance was already made when it was applied (clock_in/out might be null)
        $correction->load(['attendance', 'correctionBreaks', 'user']);
        if (!$correction->attendance) {
            return back();
        }

        DB::transaction(function () use ($request, $correction) {
            $attendance = $correction->attendance()->firstOrFail();

            if ($correction->requested_clock_in) {
                $attendance->clock_in = $correction->requested_clock_in;
            }
            if ($correction->requested_clock_out) {
                $attendance->clock_out = $correction->requested_clock_out;
            }

            if ($request->filled('request_note')) {
                $correction->request_note = $request->input('request_note');
            }
            $attendance->note = $correction->request_note ?? $attendance->note;
            $attendance->save();

            $correction->status = 'approved';
            $correction->save();

            $this->applyCorrectionToAttendance($correction);
        });
        return redirect()->route('stamp_correction_request.showCorrection', $correction);
    }


    public function updateAttendance($id, AttendanceRequest $request)
    {
        abort_unless(Auth::guard('admin')->check(), 403);

        $targetUserId = (int)$request->input('user_id');
        $targetUser = User::findOrFail($targetUserId);

        $targetDate = Carbon::parse($request->input('date'))->toDateString();

        if ($id === 'new') {
            $attendance = Attendance::firstOrCreate(
                ['user_id' => $targetUserId, 'date' => $targetDate],
                ['clock_in' => null, 'clock_out' => null],
            );
        } else {
            $attendance = Attendance::with('breakTimes')->findOrFail($id);
            if ((int)$attendance->user_id !== (int)$targetUser->id) {
                abort(404);
            }
            // existing based on the date of record
            $targetDate = Carbon::parse($attendance->date)->toDateString();
        }

        $clockIn = $request->filled('requested_clock_in')
            ? Carbon::parse("{$targetDate} {$request->input('requested_clock_in')}")->format('Y-m-d H:i:s')
            : null;
        $clockOut = $request->filled('requested_clock_out')
            ? Carbon::parse("{$targetDate} {$request->input('requested_clock_out')}")->format('Y-m-d H:i:s')
            : null;

        // use same note in attendances table
        $publicNote = $request->input('request_note'); //nullable (new)

        //transaction
        \DB::transaction(function () use ($request, $attendance, $clockIn, $clockOut, $publicNote, $targetDate) {

            // new part
            $correction = AttendanceCorrection::create([
                'attendance_id' => $attendance->id,
                'user_id' => $attendance->user_id,
                'requested_clock_in' => $clockIn,
                'requested_clock_out' => $clockOut,
                'request_note' => $publicNote,
                'status' => 'approved',
            ]);

            foreach ((array) $request->input('breaks', []) as $br) {
                $s = $br['requested_break_start'] ?? null;
                $e = $br['requested_break_end'] ?? null;
                if ($s && $e) {
                    $correction->correctionBreaks()->create([
                        'requested_break_start' => Carbon::parse("{$targetDate} {$s}")->format('Y-m-d H:i:s'),
                        'requested_break_end' => Carbon::parse("{$targetDate} {$e}")->format('Y-m-d H:i:s'),
                    ]);
                }
            }

            //reflect to attendance
            $correction->load('correctionBreaks');
            $this->applyCorrectionToAttendance($correction);
        });

        return redirect()
            ->route('admin.attendance.showDetail', ['user' => $targetUser->id, 'id' => $attendance->id])
            ->with('status', 'updated');

    }

    // reflect from correction to attendance and calculate sum, copy the note, replace breaks
    protected function applyCorrectionToAttendance(AttendanceCorrection $correction): void
    {
        $correction->loadMissing(['attendance', 'correctionBreaks']);
        $attendance = $correction->attendance;
        if (! $attendance)return;

        // use date from attendance
        $workDate = Carbon::parse($attendance->date)->toDateString();

        // marge to datetime expecting 'H:i'
        $ci = $correction->requested_clock_in
            ? (preg_match('/^\d{2}:\d{2}$/', $correction->requested_clock_in)
                ? \Carbon\Carbon::parse("{$workDate} {$correction->requested_clock_in}:00")
                : \Carbon\Carbon::parse($correction->requested_clock_in))
            : null;

        $co = $correction->requested_clock_out
            ? (preg_match('/^\d{2}:\d{2}$/', $correction->requested_clock_out)
                ? \Carbon\Carbon::parse("{$workDate} {$correction->requested_clock_out}:00")
                : \Carbon\Carbon::parse($correction->requested_clock_out))
            : null;

        // reflect attendance
        $attendance->clock_in = $ci ? $ci->format('Y-m-d H:i:s') : null;
        $attendance->clock_out = $co ? $co->format('Y-m-d H:i:s') : null;

        // reflect request_note to note
        $attendance->note = $correction->request_note ?: null;
        $attendance->save();

        // breaks are replaced (delete existing break_times)
        BreakTime::where('attendance_id', $attendance->id)->delete();
        foreach ($correction->correctionBreaks as $b) {
            $bs = $b->requested_break_start
                ? (preg_match('/^\d{2}:\d{2}$/', $b->requested_break_start)
                    ? Carbon::parse("{$workDate} {$b->requested_break_start}:00")
                    : Carbon::parse($b->requested_break_start))
                : null;
            $be = $b->requested_break_end
                ? (preg_match('/^\d{2}:\d{2}$/', $b->requested_break_end)
                    ? Carbon::parse("{$workDate} {$b->requested_break_end}:00")
                    : Carbon::parse($b->requested_break_end))
                : null;

            if ($bs && $be) {
                BreakTime::create([
                    'attendance_id' => $attendance->id,
                    'break_start' => $bs->format('Y-m-d H:i:s'),
                    'break_end' => $be->format('Y-m-d H:i:s'),
                ]);
            }
        }

         // calculate sum
        $breaks = BreakTime::where('attendance_id', $attendance->id)->get();

        $breakMinutes = $breaks->sum(function ($bt) {
            return Carbon::parse($bt->break_start)->diffInMinutes(Carbon::parse($bt->break_end));
        });

        $workMinutes = null;
        if ($attendance->clock_in && $attendance->clock_out) {
            $workMinutes = Carbon::parse($attendance->clock_in)->diffInMinutes(Carbon::parse($attendance->clock_out));
            $workMinutes = max(0, $workMinutes - $breakMinutes);
        }

        $attendance->total_break_time = $breakMinutes;
        if (! is_null($workMinutes)) {
            $attendance->total_work_time = $workMinutes;
        }
            $attendance->save();
    }
}

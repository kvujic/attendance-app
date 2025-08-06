<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\AttendanceRequest;
use App\Models\Attendance;
use App\Models\AttendanceCorrection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CorrectionController extends Controller
{

    
    public function update(AttendanceRequest $request, $id)
    {
        \Log::debug('update() 入った', ['id' => $id, 'request' => $request->all()]);


        $attendance = Attendance::findOrFail($id);
        \Log::debug('attendance', ['id' => $attendance->id]);

        //make amendment application
        $correction = AttendanceCorrection::create([
            'user_id' => $attendance->user_id,
            'attendance_id' => $attendance->id,
            'requested_start_time' => $request->clock_in,
            'requested_end_time' => $request->clock_out,
            'note' => $request->input('note', ''),
            'status' => 'pending',
        ]);

        \Log::debug('correction 作成', ['id' => $correction->id ?? '失敗']);

        if ($request->has('breaks')) {
            foreach ($request->breaks as $break) {
                if (!empty($break['start']) && !empty($break['end'])) {
                    \Log::debug('break 登録開始', $break);
                    $correction->correctionBreaks()->create([
                        'requested_break_start' => $break['start'],
                        'requested_break_end' => $break['end'],
                    ]);
                }
            }
        }

        //dd($request->breaks);
        //dd($id, $request->all());

        return redirect()->route('attendance.show', ['id' => $attendance->id]);
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

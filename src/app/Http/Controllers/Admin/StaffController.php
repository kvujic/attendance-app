<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;

class StaffController extends Controller
{
    public function index()
    {
        $users = User::where('role', 2)
            ->select('id', 'name', 'email')
            ->get();
        return view('admin.admin_staff_list', compact('users'));
    }

    public function showAttendance (User $user)
    {
        return view('admin.admin_staff_attendance_list', compact('user'));
    }

    public function exportCsv(User $user)
    {
        // query: ?exportCsv=YYYY=MM
        $month = request('month');
        $start = $month 
            ? Carbon::parse($month . '-01')->startOfMonth()
            : now()->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $records = Attendance::where('user_id', $user->id)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->with('breakTimes')
            ->get();

        $byDate = $records->keyBy(function ($att) {
            return Carbon::parse($att->date)->toDateString();
        });

        $fp = fopen('php://temp', 'r+');
        fwrite($fp, chr(0xEF).chr(0xBB).chr(0xBF)); //BOM
        $eol = "\r\n";

        fputcsv($fp, ['日付', '出勤', '退勤', '休憩', '合計']);

        $toHhmm = function($min, bool $padHour = false) {
            if ($min === null) return '';
            $min = (int)$min;
            $sign = $min < 0 ? '_' : '';
            $min = abs($min);
            $h = intdiv($min, 60);
            $m = $min % 60;
            $hh = $padHour ? sprintf('%02d', $h) : (string)$h;
            return $sign . $hh . ':' . sprintf('%02d', $m);
        };

        foreach (CarbonPeriod::create($start, $end) as $day) {
            $key = $day->toDateString();
            $att = $byDate->get($key);

            $in = $att?->clock_in ? Carbon::parse($att->clock_in)->format('H:i') : '';
            $out = $att?->clock_out ? Carbon::parse($att->clock_out)->format('H:i') : '';

            fputcsv($fp, [
                $day->format('Y-m-d'),
                $in,
                $out,
                $toHhmm($att?->total_break_time),
                $toHhmm($att?->total_work_time),
            ]);
        }

        rewind($fp);
        $csv = stream_get_contents($fp);
        fclose($fp);

        $filename = sprintf('attendance_%s_%s.csv', $user->name, $start->format('Y-m'));
        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}

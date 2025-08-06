<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'date',
        'clock_in',
        'clock_out',
        'total_work_time',
        'total_break_time',
    ];

    protected $casts = [
        'date' => 'date',
        'clock_in' => 'datetime',
        'clock_out' => 'datetime',
    ];


    public function user() {
        return $this->belongsTo(User::class);
    }

    public function breakTimes() {
        return $this->hasMany(BreakTime::class);
    }

    public function attendanceCorrections() {
        return $this->hasMany(AttendanceCorrection::class);
    }

    public function isOnBreak()
    {
        return $this->breakTimes()
            ->whereNull('break_end')
            ->exists();
    }
}

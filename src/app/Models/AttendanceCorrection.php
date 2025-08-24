<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceCorrection extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'attendance_id',
        'requested_clock_in',
        'requested_clock_out',
        'request_note',
        'admin_note',
        'status',
    ];

    protected $casts = [
        'requested_start_time' => 'datetime',
        'requested_end_time' => 'datetime',
    ];


    public function user() {
        return $this->belongsTo(User::class);
    }

    public function attendance() {
        return $this->belongsTo(Attendance::class);
    }

    public function correctionBreaks() {
        return $this->hasMany(CorrectionBreak::class, 'attendance_correction_id');
    }

    public function getStatusLabelAttribute()
    {
        return match ($this->status) {
            'pending' => '承認待ち',
            'approved' => '承認済み',
        };
    }
}

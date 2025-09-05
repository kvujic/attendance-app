<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'role' => 'integer',
    ];

    const ROLE_ADMIN = 1;
    const ROLE_STAFF = 2;

    public static function roleLabels(): array
    {
        return [
            self::ROLE_ADMIN => '管理者',
            self::ROLE_STAFF => 'スタッフ',
        ];
    }


    public function attendances() {
        return $this->hasMany(Attendance::class);
    }

    public function attendanceCorrections() {
        return $this->hasMany(AttendanceCorrection::class);
    }
}

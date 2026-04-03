<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'nip', 'password', 'role', 'employee_id', 'department',
        'position', 'phone', 'address', 'join_date', 'npwp',
        'bpjs_kesehatan', 'bpjs_ketenagakerjaan', 'status', 'photo', 'shift_id',
        'base_salary', 'position_allowance', 'functional_allowance', 'special_allowance', 'meal_allowance', 'transport_allowance', 'attendance_allowance',
        'gender', 'education', 'birth_place', 'birth_date', 'city', 'bank_name', 'bank_account',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'join_date' => 'date',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin_sdm';
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function leaveRequests()
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function overtimeRequests()
    {
        return $this->hasMany(OvertimeRequest::class);
    }

    public function payrolls()
    {
        return $this->hasMany(Payroll::class);
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }
}

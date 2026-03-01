<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'role', 'employee_id', 'department',
        'position', 'base_salary', 'position_allowance', 'meal_allowance',
        'transport_allowance', 'phone', 'address', 'join_date', 'npwp',
        'bpjs_kesehatan', 'bpjs_ketenagakerjaan', 'status', 'photo', 'shift_id',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'base_salary' => 'decimal:2',
            'position_allowance' => 'decimal:2',
            'meal_allowance' => 'decimal:2',
            'transport_allowance' => 'decimal:2',
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

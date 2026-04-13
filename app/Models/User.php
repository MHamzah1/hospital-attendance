<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'nip', 'password', 'role', 'employee_id',
        'department', 'unit', 'department_id', 'unit_id',
        'position', 'approval_role', 'phone', 'address', 'join_date', 'npwp',
        'bpjs_kesehatan', 'bpjs_ketenagakerjaan', 'status', 'photo', 'shift_id',
        'base_salary', 'position_allowance', 'functional_allowance', 'special_allowance', 'meal_allowance', 'transport_allowance', 'attendance_allowance',
        'gender', 'education', 'birth_place', 'birth_date', 'city', 'bank_name', 'bank_account',
        'jatah_cuti',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'join_date' => 'date:Y-m-d',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin_sdm';
    }

    public function isKoordinator(): bool
    {
        return $this->approval_role === 'koordinator';
    }

    public function isManajer(): bool
    {
        return $this->approval_role === 'manajer';
    }

    public function isDirektur(): bool
    {
        return $this->approval_role === 'direktur';
    }

    public function isApprover(): bool
    {
        return $this->isAdmin() || $this->isKoordinator() || $this->isManajer() || $this->isDirektur();
    }

    public function getInitialApprovalLevel(): int
    {
        if ($this->isAdmin() || $this->isManajer() || $this->isDirektur()) return 3;
        if ($this->isKoordinator()) return 2;
        return 1; // staf
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

    public function schedules()
    {
        return $this->hasMany(UserSchedule::class);
    }

    public function departmentModel()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function unitModel()
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    /**
     * Get units managed by this user (for manajer role).
     */
    public function managedUnits()
    {
        return $this->hasMany(Unit::class, 'manager_id');
    }
}

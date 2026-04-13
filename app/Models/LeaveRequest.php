<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'type', 'start_date', 'end_date', 'total_days',
        'reason', 'attachment', 'status', 'current_approval_level',
        'coordinator_approved_by', 'coordinator_approved_at', 'coordinator_notes',
        'manager_approved_by', 'manager_approved_at', 'manager_notes',
        'approved_by', 'approved_at', 'admin_notes',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'approved_at' => 'datetime',
            'coordinator_approved_at' => 'datetime',
            'manager_approved_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function coordinatorApprover()
    {
        return $this->belongsTo(User::class, 'coordinator_approved_by');
    }

    public function managerApprover()
    {
        return $this->belongsTo(User::class, 'manager_approved_by');
    }

    public static function typeLabels(): array
    {
        return [
            'cuti_tahunan' => 'Cuti Tahunan',
            'cuti_sakit' => 'Cuti Sakit',
            'cuti_melahirkan' => 'Cuti Melahirkan',
            'cuti_menikah' => 'Cuti Menikah',
            'cuti_duka' => 'Cuti Duka',
            'izin_lainnya' => 'Izin Lainnya',
        ];
    }
}

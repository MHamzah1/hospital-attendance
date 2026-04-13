<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OvertimeRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'date', 'start_time', 'end_time', 'total_hours',
        'category', 'rate_per_hour', 'total_pay',
        'reason', 'status', 'current_approval_level',
        'coordinator_approved_by', 'coordinator_approved_at', 'coordinator_notes',
        'manager_approved_by', 'manager_approved_at', 'manager_notes',
        'approved_by', 'approved_at', 'admin_notes',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'total_hours' => 'decimal:2',
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
}

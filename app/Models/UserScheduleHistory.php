<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserScheduleHistory extends Model
{
    const CREATED_AT = null;
    const UPDATED_AT = null;

    protected $fillable = ['user_schedule_id', 'shift_id_old', 'shift_id_new', 'user_id', 'changed_by_id', 'changed_at'];

    protected $casts = [
        'changed_at' => 'datetime',
    ];

    public function userSchedule()
    {
        return $this->belongsTo(UserSchedule::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by_id');
    }

    public function shiftOld()
    {
        return $this->belongsTo(Shift::class, 'shift_id_old');
    }

    public function shiftNew()
    {
        return $this->belongsTo(Shift::class, 'shift_id_new');
    }
}


<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Shift extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'start_time',
        'end_time',
        'late_tolerance',
        'is_night_shift',
        'is_active',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'is_night_shift' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    /**
     * Check if clock in time is late
     */
    public function isLate(string $clockInTime): bool
    {
        $startTime = Carbon::parse($this->start_time);
        $clockIn = Carbon::parse($clockInTime);
        $tolerance = $this->late_tolerance; // in minutes
        
        return $clockIn->greaterThan($startTime->addMinutes($tolerance));
    }

    /**
     * Get formatted shift time range
     */
    public function getTimeRangeAttribute(): string
    {
        return Carbon::parse($this->start_time)->format('H:i') . ' - ' . Carbon::parse($this->end_time)->format('H:i');
    }
}

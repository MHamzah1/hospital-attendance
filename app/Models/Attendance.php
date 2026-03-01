<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'shift_id', 'date', 'clock_in', 'clock_out', 'photo_in', 'photo_out',
        'status', 'notes', 'location_in', 'location_out',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    // Accessor to ensure time is displayed in 24-hour format
    protected function clockIn(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: fn ($value) => $value ? \Carbon\Carbon::parse($value)->format('H:i:s') : null,
        );
    }

    protected function clockOut(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: fn ($value) => $value ? \Carbon\Carbon::parse($value)->format('H:i:s') : null,
        );
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }
}

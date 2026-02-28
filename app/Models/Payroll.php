<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payroll extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'month', 'year', 'total_work_days', 'present_days',
        'absent_days', 'late_days', 'leave_days', 'sick_days', 'overtime_hours',
        'base_salary', 'position_allowance', 'meal_allowance', 'transport_allowance',
        'overtime_pay', 'other_allowance', 'gross_salary',
        'bpjs_kesehatan', 'bpjs_ketenagakerjaan', 'bpjs_pensiun', 'pph21',
        'absence_deduction', 'other_deduction', 'total_deduction',
        'net_salary', 'status', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'base_salary' => 'decimal:2',
            'position_allowance' => 'decimal:2',
            'meal_allowance' => 'decimal:2',
            'transport_allowance' => 'decimal:2',
            'overtime_pay' => 'decimal:2',
            'other_allowance' => 'decimal:2',
            'gross_salary' => 'decimal:2',
            'bpjs_kesehatan' => 'decimal:2',
            'bpjs_ketenagakerjaan' => 'decimal:2',
            'bpjs_pensiun' => 'decimal:2',
            'pph21' => 'decimal:2',
            'absence_deduction' => 'decimal:2',
            'other_deduction' => 'decimal:2',
            'total_deduction' => 'decimal:2',
            'net_salary' => 'decimal:2',
            'overtime_hours' => 'decimal:2',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getMonthNameAttribute(): string
    {
        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];
        return $months[$this->month] ?? '';
    }

    public function getPeriodAttribute(): string
    {
        return $this->month_name . ' ' . $this->year;
    }

    /**
     * Hitung PPh 21 berdasarkan tarif progresif
     */
    public static function calculatePPh21(float $annualTaxableIncome): float
    {
        $tax = 0;

        if ($annualTaxableIncome <= 0) return 0;

        // Tarif PPh 21 Progresif
        $brackets = [
            ['limit' => 60000000, 'rate' => 0.05],
            ['limit' => 250000000, 'rate' => 0.15],
            ['limit' => 500000000, 'rate' => 0.25],
            ['limit' => 5000000000, 'rate' => 0.30],
            ['limit' => PHP_FLOAT_MAX, 'rate' => 0.35],
        ];

        $remaining = $annualTaxableIncome;
        $prevLimit = 0;

        foreach ($brackets as $bracket) {
            $taxable = min($remaining, $bracket['limit'] - $prevLimit);
            if ($taxable <= 0) break;
            $tax += $taxable * $bracket['rate'];
            $remaining -= $taxable;
            $prevLimit = $bracket['limit'];
        }

        return $tax / 12; // Monthly
    }
}

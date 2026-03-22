<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = ['key', 'value', 'type', 'group', 'description'];

    /**
     * Get a setting value by key
     */
    public static function getValue(string $key, $default = null)
    {
        $setting = self::where('key', $key)->first();

        if (!$setting) {
            return $default;
        }

        return match ($setting->type) {
            'number' => (float) $setting->value,
            'boolean' => filter_var($setting->value, FILTER_VALIDATE_BOOLEAN),
            'json' => json_decode($setting->value, true),
            default => $setting->value,
        };
    }

    /**
     * Set a setting value
     */
    public static function setValue(string $key, $value, string $type = 'string', string $group = 'general', ?string $description = null): self
    {
        $data = [
            'value' => is_array($value) ? json_encode($value) : (string) $value,
            'type' => $type,
            'group' => $group,
        ];

        if ($description) {
            $data['description'] = $description;
        }

        return self::updateOrCreate(['key' => $key], $data);
    }

    /**
     * Get all settings by group
     */
    public static function getByGroup(string $group): array
    {
        return self::where('group', $group)
            ->get()
            ->mapWithKeys(fn ($s) => [$s->key => self::getValue($s->key)])
            ->toArray();
    }

    /**
     * Get overtime rate per hour
     */
    public static function getOvertimeRate(): float
    {
        return self::getValue('overtime_rate_per_hour', 10000);
    }

    /**
     * Get overtime night rate
     */
    public static function getOvertimeNightRate(): float
    {
        return self::getValue('overtime_rate_night', 15000);
    }

    /**
     * Get overtime holiday rate
     */
    public static function getOvertimeHolidayRate(): float
    {
        return self::getValue('overtime_rate_holiday', 20000);
    }
}

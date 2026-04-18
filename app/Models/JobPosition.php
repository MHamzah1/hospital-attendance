<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobPosition extends Model
{
    protected $fillable = ['name'];

    public function setNameAttribute($value): void
    {
        $name = trim((string) $value);
        $name = preg_replace('/\s+/', ' ', $name);

        $this->attributes['name'] = function_exists('mb_strtoupper')
            ? mb_strtoupper($name, 'UTF-8')
            : strtoupper($name);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }
}

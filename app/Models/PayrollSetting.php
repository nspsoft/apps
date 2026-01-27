<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayrollSetting extends Model
{
    protected $table = 'hr_payroll_settings';
    protected $fillable = ['category', 'key', 'label', 'value', 'type', 'is_active'];

    public static function getByKey($key, $default = null)
    {
        $setting = self::where('key', $key)->where('is_active', true)->first();
        return $setting ? $setting->value : $default;
    }

    public static function getAllGrouped()
    {
        return self::all()->groupBy('category');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AllowableDays extends Model
{
    use HasFactory;

    protected $table = "allowable_days";

    protected $fillable = ["allowable_days"];

    protected $casts = [
        "allowable_days" => "integer",
    ];

    public static function getCurrentAllowableDays(): int
    {
        $setting = self::first();
        return $setting ? $setting->allowable_days : 0;
    }

    public static function updateAllowableDays(int $days): bool
    {
        $setting = self::first();

        if ($setting) {
            return $setting->update(["allowable_days" => $days]);
        }

        self::create(["allowable_days" => $days]);
        return true;
    }
}

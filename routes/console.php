<?php

use App\Models\AllowableDays;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command("inspire", function () {
    $this->comment(Inspiring::quote());
})
    ->purpose("Display an inspiring quote")
    ->hourly();

// Schedule::command("auto-skip:end-of-month")->monthlyOn(
//     now()->endOfMonth()->day,
//     "23:59:59"
// );

Schedule::command("auto-skip:backmonth")
    ->timezone("Asia/Manila")
    ->dailyAt("00:10")
    ->when(function () {
        $now = now("Asia/Manila");

        $allowable = (int) (AllowableDays::value("days") ?? 5);

        if ($now->day <= $allowable) {
            return false;
        }

        $key = "auto_skip_backmonth_ran_" . $now->format("Y-m");

        if (cache()->has($key)) {
            return false;
        }

        cache()->put($key, true, $now->copy()->endOfMonth());
        return true;
    });

<?php

namespace App\Helpers;

use Carbon\Carbon;
use App\Models\StoreChecklistWeeklyRecord;

class FourWeekCalendarHelper
{
    /**
     * Get the 4-week period information for a given date
     * This uses a 4-4-5 or 4-4-4 calendar system
     *
     * @param Carbon|null $date
     * @return array
     */
    public static function getFourWeekPeriod(?Carbon $date = null): array
    {
        $date = $date ?? Carbon::today();

        // Get the week of year (1-52)
        $weekOfYear = $date->weekOfYear;

        // Calculate which 4-week period this falls into (1-13 periods per year)
        // 52 weeks ÷ 4 weeks = 13 periods
        $periodNumber = (int) ceil($weekOfYear / 4);

        // Calculate which week within the period (1-4)
        $weekInPeriod = (($weekOfYear - 1) % 4) + 1;

        // Calculate which month this period primarily falls in
        // Approximately 13 periods ÷ 12 months ≈ 1.08 periods per month
        $approximateMonth = (int) ceil($periodNumber / 1.08);
        $approximateMonth = min($approximateMonth, 12); // Cap at 12

        // Get period start and end dates
        $periodStartWeek = ($periodNumber - 1) * 4 + 1;
        $periodEndWeek = $periodNumber * 4;

        $yearStart = Carbon::parse($date->year . "-01-01")->startOfWeek();
        $periodStart = $yearStart->copy()->addWeeks($periodStartWeek - 1);
        $periodEnd = $yearStart
            ->copy()
            ->addWeeks($periodEndWeek)
            ->subDay();

        return [
            "week" => $weekInPeriod, // 1-4
            "period" => $periodNumber, // 1-13
            "month" => $approximateMonth, // 1-12
            "year" => $date->year,
            "period_start" => $periodStart->format("Y-m-d"),
            "period_end" => $periodEnd->format("Y-m-d"),
            "week_of_year" => $weekOfYear,
        ];
    }

    /**
     * Alternative: Fixed month-based 4-week system
     * Divides each month into exactly 4 weeks (7 days each)
     * Note: This leaves some days at month end unaccounted
     *
     * @param Carbon|null $date
     * @return array
     */
    public static function getMonthBasedFourWeek(?Carbon $date = null): array
    {
        $date = $date ?? Carbon::today();

        $dayOfMonth = $date->day;
        $month = $date->month;
        $year = $date->year;

        // Calculate which week (1-4) based on day of month
        // Week 1: Days 1-7
        // Week 2: Days 8-14
        // Week 3: Days 15-21
        // Week 4: Days 22-28
        // Days 29-31: Roll into Week 4

        $week = min((int) ceil($dayOfMonth / 7), 4);

        // Calculate week start and end dates
        $weekStartDay = ($week - 1) * 7 + 1;
        $weekEndDay = min($week * 7, $date->daysInMonth);

        // If it's week 4, extend to end of month
        if ($week === 4) {
            $weekEndDay = $date->daysInMonth;
        }

        $weekStart = Carbon::create($year, $month, $weekStartDay);
        $weekEnd = Carbon::create($year, $month, $weekEndDay);

        return [
            "week" => $week, // Always 1-4
            "month" => $month,
            "year" => $year,
            "week_start" => $weekStart->format("Y-m-d"),
            "week_end" => $weekEnd->format("Y-m-d"),
            "day_of_month" => $dayOfMonth,
        ];
    }

    /**
     * Retail 4-5-4 Calendar System
     * Commonly used in retail: 4 weeks, 5 weeks, 4 weeks per quarter
     * Each year has 52 weeks (or 53 in leap years)
     *
     * @param Carbon|null $date
     * @return array
     */
    public static function getRetail454Calendar(?Carbon $date = null): array
    {
        $date = $date ?? Carbon::today();
        $weekOfYear = $date->weekOfYear;

        // Define the 4-5-4 pattern (weeks per month)
        $pattern = [4, 5, 4, 4, 5, 4, 4, 5, 4, 4, 5, 4]; // 52 weeks total

        $cumulativeWeeks = 0;
        $month = 0;
        $weekInMonth = 0;

        foreach ($pattern as $index => $weeksInMonth) {
            if ($weekOfYear <= $cumulativeWeeks + $weeksInMonth) {
                $month = $index + 1;
                $weekInMonth = $weekOfYear - $cumulativeWeeks;
                break;
            }
            $cumulativeWeeks += $weeksInMonth;
        }

        return [
            "week" => $weekInMonth, // 1-4 or 1-5 depending on month
            "month" => $month, // 1-12
            "year" => $date->year,
            "week_of_year" => $weekOfYear,
            "calendar_system" => "4-5-4 Retail",
        ];
    }

    /**
     * Get all 4 weeks for a given month and year
     *
     * @param int $month
     * @param int $year
     * @return array
     */
    public static function getAllWeeksInMonth(int $month, int $year): array
    {
        $weeks = [];

        for ($week = 1; $week <= 4; $week++) {
            $weekStartDay = ($week - 1) * 7 + 1;
            $weekEndDay = min(
                $week * 7,
                Carbon::create($year, $month, 1)->daysInMonth
            );

            if ($week === 4) {
                $weekEndDay = Carbon::create($year, $month, 1)->daysInMonth;
            }

            $weeks[] = [
                "week" => $week,
                "start_day" => $weekStartDay,
                "end_day" => $weekEndDay,
                "start_date" => Carbon::create(
                    $year,
                    $month,
                    $weekStartDay
                )->format("Y-m-d"),
                "end_date" => Carbon::create(
                    $year,
                    $month,
                    $weekEndDay
                )->format("Y-m-d"),
            ];
        }

        return $weeks;
    }

    public static function monthlyWeekChecker($week, $month, $year)
    {
        return StoreChecklistWeeklyRecord::where("week", $week)
            ->where("month", $month)
            ->where("year", $year)
            ->exists();
    }
}

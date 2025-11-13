<?php

namespace App\Helpers;

use Carbon\Carbon;
use App\Models\StoreChecklistWeeklyRecord;

/**
 * Helper for managing Monday-based weekly calendar calculations
 * Each week starts on Monday and belongs to the month of its Monday
 */
class MondayWeekCalendarHelper
{
    /**
     * Get week information based on Monday-start weeks
     * Each week belongs to the month of its Monday
     * Returns week number (1-5), month, and year
     *
     * @param Carbon|null $date
     * @return array
     */
    public static function getMonthBasedMonday(?Carbon $date = null): array
    {
        $date = $date ?? Carbon::today();

        // Get the Monday of the current week
        $monday = $date->copy()->startOfWeek(Carbon::MONDAY);

        // The week belongs to the month of its Monday
        $weekMonth = $monday->month;
        $weekYear = $monday->year;

        // Get all Mondays in this month
        $firstDayOfMonth = Carbon::create($weekYear, $weekMonth, 1);
        $lastDayOfMonth = $firstDayOfMonth->copy()->endOfMonth();

        // Find the first Monday of the month (or the Monday before if month doesn't start on Monday)
        $firstMonday = $firstDayOfMonth->copy()->startOfWeek(Carbon::MONDAY);

        // Count which Monday this is (1-5)
        $weekNumber = 0;
        $currentMonday = $firstMonday->copy();

        while ($currentMonday->lte($monday)) {
            // Only count Mondays that belong to this month
            if ($currentMonday->month === $weekMonth) {
                $weekNumber++;
            }
            $currentMonday->addWeek();
        }

        // Calculate week boundaries (Monday to Sunday)
        $weekStart = $monday->copy();
        $weekEnd = $monday->copy()->endOfWeek(Carbon::SUNDAY);

        return [
            "week" => $weekNumber, // 1-5
            "month" => $weekMonth, // 1-12
            "year" => $weekYear, // e.g., 2025
            "week_start" => $weekStart->format("Y-m-d"),
            "week_end" => $weekEnd->format("Y-m-d"),
            "day_of_week" => $date->dayOfWeek, // 0 (Sunday) - 6 (Saturday)
            "is_monday" => $date->isMonday(),
        ];
    }

    /**
     * Get all weeks for a given month and year
     * Each week starts on Monday and belongs to the month of its Monday
     *
     * @param int $month
     * @param int $year
     * @return array
     */
    public static function getAllWeeksInMonth(int $month, int $year): array
    {
        $weeks = [];
        $firstDayOfMonth = Carbon::create($year, $month, 1);
        $lastDayOfMonth = $firstDayOfMonth->copy()->endOfMonth();

        // Start from the first Monday in or before the month
        $currentMonday = $firstDayOfMonth->copy()->startOfWeek(Carbon::MONDAY);

        $weekNumber = 1;

        // Loop through all Mondays until we're past the month
        while (
            $currentMonday->month === $month ||
            $currentMonday->lte($lastDayOfMonth)
        ) {
            // Only include weeks whose Monday is in this month
            if ($currentMonday->month === $month) {
                $weekEnd = $currentMonday->copy()->endOfWeek(Carbon::SUNDAY);

                $weeks[] = [
                    "week" => $weekNumber,
                    "start_date" => $currentMonday->format("Y-m-d"),
                    "end_date" => $weekEnd->format("Y-m-d"),
                    "start_day" => $currentMonday->day,
                    "end_day" => $weekEnd->day,
                    "end_month" => $weekEnd->month, // May spill into next month
                ];

                $weekNumber++;
            }

            $currentMonday->addWeek();

            // Safety check: stop after 6 weeks (shouldn't happen, but prevents infinite loops)
            if ($weekNumber > 6) {
                break;
            }
        }

        return $weeks;
    }

    /**
     * Check if a checklist already exists for a given week/month/year
     *
     * @param int $week
     * @param int $month
     * @param int $year
     * @param int|null $storeChecklistId Optional: check for specific store
     * @return bool
     */
    public static function monthlyWeekChecker(
        int $week,
        int $month,
        int $year,
        ?int $storeChecklistId = null
    ): bool {
        $query = StoreChecklistWeeklyRecord::where("week", $week)
            ->where("month", $month)
            ->where("year", $year);

        if ($storeChecklistId !== null) {
            $query->where("store_checklist_id", $storeChecklistId);
        }

        return $query->exists();
    }

    /**
     * Get the week info for a specific date range
     * Useful for validation and display
     *
     * @param string $startDate Y-m-d format
     * @param string $endDate Y-m-d format
     * @return array
     */
    public static function getWeekInfoForDateRange(
        string $startDate,
        string $endDate
    ): array {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        // Verify it's a valid Monday-Sunday week
        $isValidWeek =
            $start->isMonday() &&
            $end->isSunday() &&
            $start->diffInDays($end) === 6;

        return [
            "is_valid_week" => $isValidWeek,
            "week_info" => self::getMonthBasedMonday($start),
            "days_in_range" => $start->diffInDays($end) + 1,
        ];
    }

    public static function getMonthBasedFourWeekOLD(?Carbon $date = null): array
    {
        $date = $date ?? Carbon::today();
        $dayOfMonth = $date->day;
        $month = $date->month;
        $year = $date->year;

        $week = min((int) ceil($dayOfMonth / 7), 4);
        $weekStartDay = ($week - 1) * 7 + 1;
        $weekEndDay = min($week * 7, $date->daysInMonth);

        if ($week === 4) {
            $weekEndDay = $date->daysInMonth;
        }

        $weekStart = Carbon::create($year, $month, $weekStartDay);
        $weekEnd = Carbon::create($year, $month, $weekEndDay);

        return [
            "week" => $week,
            "month" => $month,
            "year" => $year,
            "week_start" => $weekStart->format("Y-m-d"),
            "week_end" => $weekEnd->format("Y-m-d"),
            "day_of_month" => $dayOfMonth,
        ];
    }
}

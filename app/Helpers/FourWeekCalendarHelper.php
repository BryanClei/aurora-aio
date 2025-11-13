<?php

namespace App\Helpers;

use Carbon\Carbon;
use App\Models\StoreChecklistWeeklyRecord;

class FourWeekCalendarHelper
{
    /**
     * Get the current week information based on 4-week calendar system
     *
     * @param Carbon $date
     * @return array
     */
    public static function getMonthBasedFourWeek(Carbon $date): array
    {
        $month = $date->month;
        $year = $date->year;
        $dayOfMonth = $date->day;

        // Calculate the cutoff day (4 days before month end)
        $lastDayOfMonth = $date->copy()->endOfMonth()->day;
        $cutoffDay = $lastDayOfMonth - 4;

        // Determine which week we're in (1-4) - for reference only
        $week = self::calculateWeekNumber($dayOfMonth, $lastDayOfMonth);

        return [
            "week" => $week,
            "month" => $month,
            "year" => $year,
            "day_of_month" => $dayOfMonth,
            "last_day_of_month" => $lastDayOfMonth,
            "cutoff_day" => $cutoffDay,
            "is_within_submission_period" => $dayOfMonth <= $cutoffDay,
            "days_until_cutoff" => max(0, $cutoffDay - $dayOfMonth),
        ];
    }

    /**
     * Calculate which week (1-4) based on day of month
     *
     * @param int $dayOfMonth
     * @param int $lastDayOfMonth
     * @return int
     */
    private static function calculateWeekNumber(
        int $dayOfMonth,
        int $lastDayOfMonth
    ): int {
        // Divide the month into 4 equal parts
        $daysPerWeek = $lastDayOfMonth / 4;

        $week = (int) ceil($dayOfMonth / $daysPerWeek);

        // Ensure week is between 1 and 4
        return min(4, max(1, $week));
    }

    /**
     * Automatically determine next available week and validate if user can submit
     *
     * @param int $storeChecklistId
     * @param int|null $userId
     * @param Carbon|null $date
     * @return array
     */
    public static function canSubmitToday(
        int $storeChecklistId,
        ?int $userId = null,
        ?Carbon $date = null
    ): array {
        $date = $date ?? Carbon::today();
        $currentMonthInfo = self::getMonthBasedFourWeek($date);

        // Check if within submission period (4 days before month end)
        if (!$currentMonthInfo["is_within_submission_period"]) {
            return [
                "can_submit" => false,
                "reason" =>
                    "Submission period has ended (4 days before month end)",
                "week_info" => $currentMonthInfo,
                "next_available_week" => null,
            ];
        }

        // Check if already submitted today (1 submission per day limit)
        if ($userId) {
            $hasSubmittedToday = StoreChecklistWeeklyRecord::where(
                "store_checklist_id",
                $storeChecklistId
            )
                ->where("graded_by", $userId)
                ->whereDate("created_at", $date->toDateString())
                ->exists();

            if ($hasSubmittedToday) {
                return [
                    "can_submit" => false,
                    "reason" =>
                        "Already submitted for today. You can only submit once per day.",
                    "week_info" => $currentMonthInfo,
                    "next_available_week" => null,
                ];
            }

            // Get all submitted weeks for this month
            $submittedWeeks = StoreChecklistWeeklyRecord::where(
                "store_checklist_id",
                $storeChecklistId
            )
                ->where("graded_by", $userId)
                ->where("month", $currentMonthInfo["month"])
                ->where("year", $currentMonthInfo["year"])
                ->pluck("week")
                ->toArray();

            // Find the next available week (1-4)
            $allWeeks = [1, 2, 3, 4];
            $availableWeeks = array_diff($allWeeks, $submittedWeeks);

            if (empty($availableWeeks)) {
                return [
                    "can_submit" => false,
                    "reason" =>
                        "All weeks (1-4) have been submitted for this month.",
                    "week_info" => $currentMonthInfo,
                    "next_available_week" => null,
                    "submitted_weeks" => $submittedWeeks,
                ];
            }

            // Get the lowest available week number
            $nextWeek = min($availableWeeks);

            return [
                "can_submit" => true,
                "reason" => null,
                "week_info" => $currentMonthInfo,
                "next_available_week" => $nextWeek,
                "submitted_weeks" => $submittedWeeks,
                "remaining_weeks" => array_values($availableWeeks),
            ];
        }

        return [
            "can_submit" => true,
            "reason" => null,
            "week_info" => $currentMonthInfo,
            "next_available_week" => 1, // Default to week 1 if no userId
        ];
    }

    /**
     * Get which weeks are still available for submission
     *
     * @param int $storeChecklistId
     * @param int $userId
     * @param Carbon|null $date
     * @return array
     */
    public static function getAvailableWeeksForUser(
        int $storeChecklistId,
        int $userId,
        ?Carbon $date = null
    ): array {
        $date = $date ?? Carbon::today();
        $currentMonthInfo = self::getMonthBasedFourWeek($date);

        if (!$currentMonthInfo["is_within_submission_period"]) {
            return [
                "available_weeks" => [],
                "submitted_weeks" => [],
                "reason" => "Submission period has ended",
                "current_month_info" => $currentMonthInfo,
            ];
        }

        // Get all submitted weeks for this month
        $submittedWeeks = StoreChecklistWeeklyRecord::where(
            "store_checklist_id",
            $storeChecklistId
        )
            ->where("graded_by", $userId)
            ->where("month", $currentMonthInfo["month"])
            ->where("year", $currentMonthInfo["year"])
            ->pluck("week")
            ->toArray();

        // All weeks minus submitted weeks
        $allWeeks = [1, 2, 3, 4];
        $availableWeeks = array_diff($allWeeks, $submittedWeeks);

        // Check if can submit today (1 per day limit)
        $canSubmitToday = !StoreChecklistWeeklyRecord::where(
            "store_checklist_id",
            $storeChecklistId
        )
            ->where("graded_by", $userId)
            ->whereDate("created_at", $date->toDateString())
            ->exists();

        return [
            "available_weeks" => array_values($availableWeeks),
            "submitted_weeks" => $submittedWeeks,
            "can_submit_today" => $canSubmitToday,
            "current_month_info" => $currentMonthInfo,
        ];
    }

    /**
     * Get all available weeks for submission in current month
     *
     * @param Carbon $date
     * @return array
     */
    public static function getAvailableWeeks(Carbon $date): array
    {
        $weekInfo = self::getMonthBasedFourWeek($date);

        if (!$weekInfo["is_within_submission_period"]) {
            return [];
        }

        // User can submit to any week 1-4 before cutoff
        return range(1, 4);
    }

    /**
     * Get submission statistics for current month
     *
     * @param int $storeChecklistId
     * @param int $userId
     * @param Carbon $date
     * @return array
     */
    public static function getMonthSubmissionStats(
        int $storeChecklistId,
        int $userId,
        Carbon $date
    ): array {
        $weekInfo = self::getMonthBasedFourWeek($date);

        $submissions = StoreChecklistWeeklyRecord::where(
            "store_checklist_id",
            $storeChecklistId
        )
            ->where("graded_by", $userId)
            ->where("month", $weekInfo["month"])
            ->where("year", $weekInfo["year"])
            ->get();

        $submissionsByWeek = $submissions->groupBy("week")->map->count();

        return [
            "total_submissions" => $submissions->count(),
            "week_1_count" => $submissionsByWeek->get(1, 0),
            "week_2_count" => $submissionsByWeek->get(2, 0),
            "week_3_count" => $submissionsByWeek->get(3, 0),
            "week_4_count" => $submissionsByWeek->get(4, 0),
            "days_until_cutoff" => $weekInfo["days_until_cutoff"],
            "can_still_submit" => $weekInfo["is_within_submission_period"],
        ];
    }

    /**
     * Get date range for a specific week in a month
     *
     * @param int $week
     * @param int $month
     * @param int $year
     * @return array
     */
    public static function getWeekDateRange(
        int $week,
        int $month,
        int $year
    ): array {
        $date = Carbon::create($year, $month, 1);
        $lastDayOfMonth = $date->copy()->endOfMonth()->day;
        $daysPerWeek = $lastDayOfMonth / 4;

        $startDay = (int) floor(($week - 1) * $daysPerWeek) + 1;
        $endDay = (int) floor($week * $daysPerWeek);

        // Ensure last week goes to end of month
        if ($week === 4) {
            $endDay = $lastDayOfMonth;
        }

        return [
            "start_date" => Carbon::create($year, $month, $startDay),
            "end_date" => Carbon::create($year, $month, $endDay),
            "start_day" => $startDay,
            "end_day" => $endDay,
        ];
    }

    /**
     * Get total number of weeks in a month (always 4)
     *
     * @param int $month
     * @param int $year
     * @return int
     */
    public static function getTotalWeeksInMonth(int $month, int $year): int
    {
        // Always 4 weeks per month in the new system
        return 4;
    }

    /**
     * Check if a specific week exists in the month
     *
     * @param int $week
     * @param int $month
     * @param int $year
     * @return bool
     */
    public static function isValidWeek(int $week, int $month, int $year): bool
    {
        return $week >= 1 && $week <= 4;
    }
}

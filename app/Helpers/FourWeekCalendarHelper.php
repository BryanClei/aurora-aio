<?php

namespace App\Helpers;

use Carbon\Carbon;
use App\Models\StoreChecklistWeeklyRecord;
use App\Models\AllowableDays;

class FourWeekCalendarHelper
{
    /**
     * Get allowable days from AllowableDays model.
     * This value controls how many days into the new month a user can still
     * submit for the PREVIOUS month.
     * e.g. allowable_days = 5 → January survey can be submitted up to February 5.
     *
     * @return int
     */
    public static function getGracePeriodDays(): int
    {
        try {
            $allowableDays = AllowableDays::first();
            return $allowableDays ? (int) $allowableDays->allowable_days : 5;
        } catch (\Exception $e) {
            return 5;
        }
    }

    /**
     * Get the current week information based on 4-week calendar system.
     * There is NO cutoff within the current month — submissions are open
     * for the entire month. The allowable_days grace period only applies
     * to previous-month submissions.
     *
     * @param Carbon $date
     * @return array
     */
    public static function getMonthBasedFourWeek(Carbon $date): array
    {
        $month      = $date->month;
        $year       = $date->year;
        $dayOfMonth = $date->day;

        $lastDayOfMonth = $date->copy()->endOfMonth()->day;

        // Current month is always open for submission (no cutoff)
        $week = self::calculateWeekNumber($dayOfMonth, $lastDayOfMonth);

        return [
            "week"                        => $week,
            "month"                       => $month,
            "year"                        => $year,
            "day_of_month"                => $dayOfMonth,
            "last_day_of_month"           => $lastDayOfMonth,
            "is_within_submission_period" => true, // always open within the current month
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
        $daysPerWeek = $lastDayOfMonth / 4;
        $week        = (int) ceil($dayOfMonth / $daysPerWeek);
        return min(4, max(1, $week));
    }

    /**
     * Check if we're in the grace period for previous month submissions.
     * Grace period = first N days of the new month, where N = allowable_days.
     *
     * Example: allowable_days = 5
     *   → January survey can still be submitted on Feb 1–5.
     *   → From Feb 6 onwards, January is locked.
     *
     * @param Carbon $date
     * @return array
     */
    public static function isInGracePeriod(Carbon $date): array
    {
        $dayOfMonth      = $date->day;
        $gracePeriodDays = self::getGracePeriodDays();
        $isInGracePeriod = $dayOfMonth <= $gracePeriodDays;

        $previousMonth = $date->copy()->subMonth();

        return [
            "is_in_grace_period"        => $isInGracePeriod,
            "days_into_month"           => $dayOfMonth,
            "grace_period_days"         => $gracePeriodDays,
            "can_submit_previous_month" => $isInGracePeriod,
            "previous_month"            => $previousMonth->month,
            "previous_year"             => $previousMonth->year,
        ];
    }

    /**
     * Automatically determine next available week and validate if user can submit.
     *
     * Logic:
     *  1. Check 1-per-day limit — user can only submit once per day.
     *  2. If within grace period AND previous month has missing weeks → submit to previous month.
     *  3. Otherwise → submit to current month (always open, no cutoff).
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
        $date             = $date ?? Carbon::today();
        $currentMonthInfo = self::getMonthBasedFourWeek($date);
        $gracePeriodInfo  = self::isInGracePeriod($date);

        if ($userId) {
            // 1-per-day limit
            $hasSubmittedToday = StoreChecklistWeeklyRecord::where(
                "store_checklist_id",
                $storeChecklistId
            )
                ->where("graded_by", $userId)
                ->whereDate("created_at", $date->toDateString())
                ->exists();

            // if ($hasSubmittedToday) {
            //     return [
            //         "can_submit"          => false,
            //         "reason"              => "Already submitted for today. You can only submit once per day.",
            //         "week_info"           => $currentMonthInfo,
            //         "next_available_week" => null,
            //         "target_month"        => null,
            //         "target_year"         => null,
            //     ];
            // }

            // Determine which month to target
            $targetMonth = $currentMonthInfo["month"];
            $targetYear  = $currentMonthInfo["year"];

            // Priority: grace period → fill in missing previous-month weeks first
            if ($gracePeriodInfo["is_in_grace_period"]) {
                $previousMonthMissingWeeks = self::getMissingWeeks(
                    $storeChecklistId,
                    $userId,
                    $gracePeriodInfo["previous_month"],
                    $gracePeriodInfo["previous_year"]
                );

                if (!empty($previousMonthMissingWeeks)) {
                    $targetMonth = $gracePeriodInfo["previous_month"];
                    $targetYear  = $gracePeriodInfo["previous_year"];
                    $nextWeek    = min($previousMonthMissingWeeks);

                    return [
                        "can_submit"                 => true,
                        "reason"                     => null,
                        "week_info"                  => $currentMonthInfo,
                        "next_available_week"         => $nextWeek,
                        "target_month"               => $targetMonth,
                        "target_year"                => $targetYear,
                        "submitted_weeks"            => self::getSubmittedWeeks(
                            $storeChecklistId,
                            $userId,
                            $targetMonth,
                            $targetYear
                        ),
                        "remaining_weeks"            => array_values($previousMonthMissingWeeks),
                        "is_grace_period_submission" => true,
                        "grace_period_info"          => $gracePeriodInfo,
                    ];
                }
            }

            // Current month — always open, no cutoff
            $submittedWeeks = self::getSubmittedWeeks(
                $storeChecklistId,
                $userId,
                $targetMonth,
                $targetYear
            );

            $allWeeks       = [1, 2, 3, 4];
            $availableWeeks = array_diff($allWeeks, $submittedWeeks);

            if (empty($availableWeeks)) {
                return [
                    "can_submit"          => false,
                    "reason"              => "All weeks (1-4) have been submitted for this month.",
                    "week_info"           => $currentMonthInfo,
                    "next_available_week" => null,
                    "target_month"        => $targetMonth,
                    "target_year"         => $targetYear,
                    "submitted_weeks"     => $submittedWeeks,
                ];
            }

            $nextWeek = min($availableWeeks);

            return [
                "can_submit"                 => true,
                "reason"                     => null,
                "week_info"                  => $currentMonthInfo,
                "next_available_week"         => $nextWeek,
                "target_month"               => $targetMonth,
                "target_year"                => $targetYear,
                "submitted_weeks"            => $submittedWeeks,
                "remaining_weeks"            => array_values($availableWeeks),
                "is_grace_period_submission" => false,
            ];
        }

        return [
            "can_submit"          => true,
            "reason"              => null,
            "week_info"           => $currentMonthInfo,
            "next_available_week" => 1,
            "target_month"        => $currentMonthInfo["month"],
            "target_year"         => $currentMonthInfo["year"],
        ];
    }

    /**
     * Get submitted weeks for a specific month/year
     *
     * @param int $storeChecklistId
     * @param int $userId
     * @param int $month
     * @param int $year
     * @return array
     */
    private static function getSubmittedWeeks(
        int $storeChecklistId,
        int $userId,
        int $month,
        int $year
    ): array {
        return StoreChecklistWeeklyRecord::where(
            "store_checklist_id",
            $storeChecklistId
        )
            ->where("graded_by", $userId)
            ->where("month", $month)
            ->where("year", $year)
            ->pluck("week")
            ->toArray();
    }

    /**
     * Get missing weeks for a specific month/year
     *
     * @param int $storeChecklistId
     * @param int $userId
     * @param int $month
     * @param int $year
     * @return array
     */
    private static function getMissingWeeks(
        int $storeChecklistId,
        int $userId,
        int $month,
        int $year
    ): array {
        $submittedWeeks = self::getSubmittedWeeks(
            $storeChecklistId,
            $userId,
            $month,
            $year
        );
        $allWeeks = [1, 2, 3, 4];
        return array_diff($allWeeks, $submittedWeeks);
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
        $date             = $date ?? Carbon::today();
        $currentMonthInfo = self::getMonthBasedFourWeek($date);
        $gracePeriodInfo  = self::isInGracePeriod($date);

        $result = [
            "current_month_info" => $currentMonthInfo,
            "grace_period_info"  => $gracePeriodInfo,
        ];

        $result["can_submit_today"] = !StoreChecklistWeeklyRecord::where(
            "store_checklist_id",
            $storeChecklistId
        )
            ->where("graded_by", $userId)
            ->whereDate("created_at", $date->toDateString())
            ->exists();

        // Previous month (only visible if within grace period)
        if ($gracePeriodInfo["is_in_grace_period"]) {
            $previousMonthMissingWeeks = self::getMissingWeeks(
                $storeChecklistId,
                $userId,
                $gracePeriodInfo["previous_month"],
                $gracePeriodInfo["previous_year"]
            );

            $result["previous_month"] = [
                "month"           => $gracePeriodInfo["previous_month"],
                "year"            => $gracePeriodInfo["previous_year"],
                "available_weeks" => array_values($previousMonthMissingWeeks),
                "submitted_weeks" => self::getSubmittedWeeks(
                    $storeChecklistId,
                    $userId,
                    $gracePeriodInfo["previous_month"],
                    $gracePeriodInfo["previous_year"]
                ),
            ];
        }

        // Current month — always open
        $currentMonthMissingWeeks = self::getMissingWeeks(
            $storeChecklistId,
            $userId,
            $currentMonthInfo["month"],
            $currentMonthInfo["year"]
        );

        $result["current_month"] = [
            "month"           => $currentMonthInfo["month"],
            "year"            => $currentMonthInfo["year"],
            "available_weeks" => array_values($currentMonthMissingWeeks),
            "submitted_weeks" => self::getSubmittedWeeks(
                $storeChecklistId,
                $userId,
                $currentMonthInfo["month"],
                $currentMonthInfo["year"]
            ),
        ];

        return $result;
    }

    /**
     * Get all available weeks for submission in current month (always 1-4, no cutoff)
     *
     * @param Carbon $date
     * @return array
     */
    public static function getAvailableWeeks(Carbon $date): array
    {
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
            "week_1_count"      => $submissionsByWeek->get(1, 0),
            "week_2_count"      => $submissionsByWeek->get(2, 0),
            "week_3_count"      => $submissionsByWeek->get(3, 0),
            "week_4_count"      => $submissionsByWeek->get(4, 0),
            "can_still_submit"  => true, // always open within current month
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
        $date           = Carbon::create($year, $month, 1);
        $lastDayOfMonth = $date->copy()->endOfMonth()->day;
        $daysPerWeek    = $lastDayOfMonth / 4;

        $startDay = (int) floor(($week - 1) * $daysPerWeek) + 1;
        $endDay   = (int) floor($week * $daysPerWeek);

        if ($week === 4) {
            $endDay = $lastDayOfMonth;
        }

        return [
            "start_date" => Carbon::create($year, $month, $startDay),
            "end_date"   => Carbon::create($year, $month, $endDay),
            "start_day"  => $startDay,
            "end_day"    => $endDay,
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

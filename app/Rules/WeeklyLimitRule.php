<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use App\Models\StoreChecklistWeeklyRecord;
use App\Helpers\FourWeekCalendarHelper;
use Carbon\Carbon;

class WeeklyLimitRule implements Rule
{
    protected $storeChecklistId;
    protected $messageText;

    /**
     * Constructor: receive store_checklist_id from request.
     */
    public function __construct($storeChecklistId = null)
    {
        $this->storeChecklistId = $storeChecklistId;
        $this->messageText =
            __("messages.weekly_limit") ??
            "A submission for this week already exists.";
    }

    /**
     * Determine if the validation rule passes.
     */
    public function passes($attribute, $value)
    {
        // If the ID is provided in the constructor, use it; else use $value
        $storeChecklistId = $this->storeChecklistId ?? $value;

        // Get current week/month/year info
        $today = Carbon::today();
        $fourWeekInfo = FourWeekCalendarHelper::getMonthBasedFourWeek($today);

        $week = $fourWeekInfo["week"];
        $month = $fourWeekInfo["month"];
        $year = $fourWeekInfo["year"];

        // Check if a record already exists for this week, month, and year
        $exists = StoreChecklistWeeklyRecord::where(
            "store_checklist_id",
            $storeChecklistId
        )
            ->where("week", $week)
            ->where("month", $month)
            ->where("year", $year)
            ->exists();

        // Return TRUE if not existing (allowed), FALSE if already exists
        return !$exists;
    }

    /**
     * Get the validation error message.
     */
    public function message()
    {
        return $this->messageText;
    }
}

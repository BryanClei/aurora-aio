<?php

namespace App\Services\Helper;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Store;
use App\Models\Region;
use App\Models\StoreChecklist;
use App\Models\AutoSkipped;
use App\Models\StoreChecklistWeeklyRecord;

class AutoSkipHelper
{
    public static function autoSkipBackMonth(array $data)
    {
        $today = Carbon::now("Asia/Manila");

        // backmonth
        $target = $today->copy()->subMonthNoOverflow();
        $month = (int) $target->month;
        $year = (int) $target->year;

        $storeChecklistId = $data["store_checklist_id"] ?? null;

        if (!$storeChecklistId) {
            return "store_checklist_id is required.";
        }

        // ONLY active checklist
        $checklist = StoreChecklist::where("status", "active")->find(
            $storeChecklistId
        );
        if (!$checklist) {
            return "Checklist not found or inactive.";
        }

        $backMonthEnd = Carbon::createFromDate(
            $year,
            $month,
            1,
            "Asia/Manila"
        )->endOfMonth();

        if ($checklist->created_at->gt($backMonthEnd)) {
            return "Checklist created after backmonth. Skipping auto-skip.";
        }

        // Resolve approver via store -> region -> region_head
        $approver = self::resolveApproverFromStore($checklist);

        if (!$approver) {
            // You can decide if you want to block or still create weekly records.
            // Here: we block because AutoSkipped needs approver info.
            return "No approver found (store/region/region_head missing).";
        }

        $created = [];
        $skipped = [];

        for ($week = 1; $week <= 4; $week++) {
            DB::transaction(function () use (
                $storeChecklistId,
                $week,
                $month,
                $year,
                $approver,
                &$created,
                &$skipped
            ) {
                $exists = StoreChecklistWeeklyRecord::where([
                    "store_checklist_id" => $storeChecklistId,
                    "week" => $week,
                    "month" => $month,
                    "year" => $year,
                ])->exists();

                if ($exists) {
                    return;
                }

                $record = StoreChecklistWeeklyRecord::create([
                    "store_checklist_id" => $storeChecklistId,
                    "week" => $week,
                    "month" => $month,
                    "year" => $year,
                    "weekly_grade" => 0,
                    "is_auto_grade" => true,
                    "grade_source" => "auto",
                    "graded_by" => config("app.system_user_id"), // or null
                    "status" => "Overdue",
                ]);

                $autoSkipped = AutoSkipped::create([
                    "weekly_id" => $record->id,
                    "week" => $week,
                    "month" => $month,
                    "year" => $year,
                    "approver_id" => $approver->id,
                    "approver_name" =>
                        $approver->first_name . " " . $approver->last_name,
                ]);

                $created[] = $record;
                $skipped[] = $autoSkipped;
            });
        }

        return [
            "checklist_id" => $storeChecklistId,
            "month" => $month,
            "year" => $year,
            "created_weekly_count" => count($created),
            "created_auto_skipped_count" => count($skipped),
            "approver_id" => $approver->id,
        ];
    }

    private static function resolveApproverFromStore(
        StoreChecklist $checklist
    ): ?User {
        if (empty($checklist->store_id)) {
            return null;
        }

        // Store has region_id
        $store = Store::find($checklist->store_id);
        if (!$store || empty($store->region_id)) {
            return null;
        }

        // Region has region_head_id
        $region = Region::find($store->region_id);
        if (!$region || empty($region->region_head_id)) {
            return null;
        }

        // Region head is the approver
        return User::find($region->region_head_id);
    }
}

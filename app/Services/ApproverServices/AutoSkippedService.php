<?php

namespace App\Services\ApproverServices;

use Carbon\Carbon;
use App\Models\StoreChecklistWeeklyRecord;

class AutoSkippedService
{
    public static function approvedFunction($data)
    {
        $date_today = Carbon::now("Asia/Manila");

        $weekly_id = $data["weekly_id"];
        $reason = $data["approver_remarks"] ?? null;

        StoreChecklistWeeklyRecord::where("id", $weekly_id)->update([
            "status" => "Approved",
            "approver_remarks" => $reason,
        ]);

        $data->update([
            "approved_at" => $date_today,
            "rejected_at" => null,
        ]);

        return $data;
    }

    public static function rejectedFunction($data)
    {
        $date_today = Carbon::now("Asia/Manila");

        $weekly_id = $data["weekly_id"];
        $reason = $data["approver_remarks"] ?? null;

        StoreChecklistWeeklyRecord::where("id", $weekly_id)->update([
            "status" => "Rejected",
            "approver_remarks" => $reason,
        ]);

        $data->update([
            "approved_at" => null,
            "rejected_at" => $date_today,
        ]);

        return $data;
    }
}

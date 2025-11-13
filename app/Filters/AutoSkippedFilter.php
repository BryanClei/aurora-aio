<?php

namespace App\Filters;

use Essa\APIToolKit\Filters\QueryFilters;

class AutoSkippedFilter extends QueryFilters
{
    protected array $allowedFilters = [];

    protected array $columnSearch = [
        "weekly_id",
        "week",
        "month",
        "year",
        "approver_id",
        "approver_name",
    ];

    public function status($status)
    {
        $user_id = Auth()->user()->id;
        $this->builder
            ->when($status === "pending", function ($query) use ($user_id) {
                $query
                    ->whereNull("approved_at")
                    ->where("approver_id", $user_id)
                    ->whereHas("weeklyRecord", function ($q) {
                        $q->where("status", "For Approval");
                    });
            })
            ->when($status === "approved", function ($query) use ($user_id) {
                $query
                    ->whereNotNull("approved_at")
                    ->where("approver_id", $user_id)
                    ->whereHas("weeklyRecord", function ($q) {
                        $q->where("status", "Approved");
                    });
            })
            ->when($status === "rejected", function ($query) use ($user_id) {
                $query
                    ->whereNotNull("rejected_at")
                    ->where("approver_id", $user_id)
                    ->whereHas("weeklyRecord", function ($q) {
                        $q->where("status", "Rejected");
                    });
            });
    }
}

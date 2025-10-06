<?php

namespace App\Services\QAService;

use Carbon\Carbon;
use App\Helpers\GradeCalculatorHelper;

class QAServices
{
    public static function storeResponse(array $data)
    {
        $gradeData = GradeCalculatorHelper::calculate(
            $data["checklist_id"],
            $data["responses"]
        );

        $today = Carbon::today();
        $weekOfMonth = $today->weekOfMonth;

        if ($weekOfMonth === 3) {
            return $gradeData["percentage"];
        } else {
            return round($gradeData["total_score"], 2);
        }
    }
}

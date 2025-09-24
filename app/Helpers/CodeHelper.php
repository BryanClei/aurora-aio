<?php

namespace App\Helpers;

use Carbon\Carbon;
use App\Models\StoreChecklist;

class CodeHelper
{
    public static function generateNumericCode(int $length = 4): string
    {
        $min = 10 ** ($length - 1);
        $max = 10 ** $length - 1;

        return (string) rand($min, $max);
    }

    public static function generateStoreCode(string $prefix = "ASC"): string
    {
        $now = Carbon::now("Asia/Manila");
        $year = $now->format("Y");

        $lastStore = StoreChecklist::whereYear("created_at", $year)
            ->where("code", "like", "$year-$prefix-%")
            ->orderBy("id", "desc")
            ->first();

        $nextNumber = 1;
        if ($lastStore) {
            $lastNumber = (int) substr(
                $lastStore->code,
                strrpos($lastStore->code, "-") + 1
            );
            $nextNumber = $lastNumber + 1;
        }

        return sprintf("%s-%s-%03d", $year, $prefix, $nextNumber);
    }
}

<?php

namespace App\Services\StoreServices;

use Carbon\Carbon;
use App\Models\Store;

class StoreService
{
    public static function exampleMethod()
    {
        // Example logic
        return "Example method logic";
    }

    public function generateStoreCode(string $prefix = "ASC"): string
    {
        $now = Carbon::now("Asia/Manila");
        $year = $now->format("Y");

        $lastStore = Store::whereYear("created_at", $year)
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
